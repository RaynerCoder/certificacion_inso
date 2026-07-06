<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\NotificacionTramite;
use App\Models\Persona;
use App\Models\Procedencia;
use App\Models\RequisitoCertificado;
use App\Models\TipoCertificado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class CertificadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Antes de listar se actualizan los certificados que ya pasaron su fecha final.
        Certificado::actualizarCertificadosEstadosVencidos();

        return view('certificados.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('certificados.create', $this->datosFormularioCertificado());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarCertificado($solicitud);

        try {
            DB::beginTransaction();

            // Primero se guarda el certificado principal.
            $certificado = Certificado::create([
                'id_tipo_certificado' => $datos['form_id_tipo_certificado'],
                'id_persona_beneficiario' => $datos['form_id_persona_beneficiario'],
                'id_persona_tramitador' => $datos['form_id_persona_tramitador'],
                'codigo' => $this->mayuscula($datos['form_codigo']),
                'fecha_inicio' => $datos['form_fecha_inicio'] ?? null,
                'fecha_fin' => $datos['form_fecha_fin'] ?? null,
                'descripcion' => $datos['form_descripcion'] ?? null,
                'url_documento' => $this->guardarDocumentoCertificado($solicitud),
                'estado' => $datos['form_estado'],
            ]);

            // Luego se guardan los requisitos evaluados para ese certificado.
            $this->guardarRequisitosCertificado(
                $certificado,
                $solicitud->input('requisitos_certificados', [])
            );

            // Despues de guardar requisitos se define el estado real: APROBADO, OBSERVADO o VENCIDO.
            $certificado->actualizarEstadoVencido();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El certificado se registro correctamente.',
                'icon' => 'success',
            ]);

            return $solicitud->input('accion') === 'guardar_otro'
                ? redirect()->route('certificados_create')
                : redirect()->route('certificados_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el certificado. ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Certificado $certificado)
    {
        // Al abrir el detalle tambien se verifica si este certificado ya vencio.
        $certificado->actualizarEstadoVencido();
        $certificado->refresh();

        $certificado->load([
            'tipoCertificado',
            'beneficiario.natural',
            'beneficiario.empresa.tipoEmpresa',
            'beneficiario.territorio',
            'beneficiario.usuario',
            'tramitador.natural',
            'tramitador.empresa.tipoEmpresa',
            'tramitador.territorio',
            'tramitador.usuario',
            'certificadoRequisitos.requisito',
            'certificadoRequisitos.evidenciasRequisitos.tipoEvidencia',
            'certificadoRequisitos.revisionesRequisitos.usuarioRevisor.funcionario.cargos',
            'certificadoRequisitos.revisionesRequisitos.observacionesRequisitos',
            'registros.producto.tipoProducto',
            'registros.producto.fabricante',
            'registros.producto.territorio',
            'registros.producto.importadorPersona.natural',
            'registros.producto.importadorPersona.empresa.tipoEmpresa',
            'registros.producto.importadorPersona.territorio',
            'registros.producto.ingredientesProductos.ingrediente',
            'registros.producto.presentaciones',
            'registros.presentacion',
            'pagos.procedencia',
            'pagos.clientePersona.natural',
            'pagos.clientePersona.empresa',
            'pagos.funcionarioUsuario.funcionario.cargos',
            'seguimientos.usuarioOrigen.funcionario.cargos',
            'seguimientos.usuarioAnterior.funcionario.cargos',
            'seguimientos.usuarioSiguiente.roles',
            'seguimientos.usuarioSiguiente.funcionario.cargos',
        ]);

        $usuarioActual = auth()->user();
        $usuarioActual?->loadMissing('funcionario.cargos');
        // Consulta general: cualquier funcionario puede ver el tramite, pero sin acciones de revision.
        $consultaGeneral = request('bandeja') === 'todos';
        $puedeConsultaGeneral = $consultaGeneral && $usuarioActual && (
            $usuarioActual->tieneRol('administrador')
            || $usuarioActual->tieneRol('tecnico-evaluador')
            || $this->usuarioTieneCargoActivo($usuarioActual)
        );
        // El solicitante dueño del tramite es el beneficiario vinculado a la cuenta.
        // El tramitador no abre "Mis tramites" si no es tambien beneficiario.
        $esSolicitante = $usuarioActual
            && (
                (int) $certificado->beneficiario?->id_usuario === (int) $usuarioActual->id
                || (int) $certificado->tramitador?->id_usuario === (int) $usuarioActual->id
            );
        $esUsuarioInterno = $usuarioActual && (
            $usuarioActual->tieneRol('administrador')
            || $usuarioActual->tieneRol('tecnico-evaluador')
            || $this->usuarioTieneCargoActivo($usuarioActual)
        );
        // Ultima etapa abierta: desde aqui el jefe puede asignar o revisar si aun no derivo a tecnico.
        $seguimientoAtencionActual = $certificado->seguimientos
            ->sortByDesc('id')
            ->first(fn ($seguimiento) => !$seguimiento->fecha_derivacion && $seguimiento->estado === 'ACTIVO');

        // Jefatura UTHSI: puede atender cuando el tramite esta actualmente en su bandeja.
        $esJefeUnidad = $usuarioActual
            && $usuarioActual->tieneRol('administrador')
            && (int) $seguimientoAtencionActual?->id_usuario_siguiente === (int) $usuarioActual->id;

        // Seguridad del detalle: solo ven el tramite quienes participan en el flujo.
        // Evita que un usuario autenticado abra una solicitud ajena escribiendo la URL.
        $participaEnSeguimiento = $usuarioActual && $certificado->seguimientos->contains(function ($seguimiento) use ($usuarioActual, $esUsuarioInterno) {
            return (
                    $esUsuarioInterno
                    && (int) $seguimiento->id_usuario_origen === (int) $usuarioActual->id
                )
                || (int) $seguimiento->id_usuario_siguiente === (int) $usuarioActual->id
                || (int) $seguimiento->id_usuario_anterior === (int) $usuarioActual->id;
        });

        if (!$esSolicitante && !$participaEnSeguimiento && !$puedeConsultaGeneral) {
            abort(403, 'No tiene permiso para ver este tramite.');
        }

        // Etapa activa que puede revisar el usuario actual: jefe, tecnico o funcionario con cargo asignado.
        $seguimientoTecnicoActual = $certificado->seguimientos
            ->sortByDesc('id')
            ->first(function ($seguimiento) use ($usuarioActual, $esJefeUnidad) {
                $usuarioDestino = $seguimiento->usuarioSiguiente;

                return !$seguimiento->fecha_derivacion
                    && $seguimiento->estado === 'ACTIVO'
                    && (
                        (
                            $esJefeUnidad
                            && (int) $seguimiento->id_usuario_siguiente === (int) $usuarioActual?->id
                        )
                        || (
                            (
                                $usuarioDestino?->tieneRol('tecnico-evaluador')
                                || $this->usuarioTieneCargoActivo($usuarioDestino)
                            )
                            && (int) $seguimiento->id_usuario_siguiente === (int) $usuarioActual?->id
                        )
                    );
            });

        // Etapa activa del solicitante cuando el tramite fue observado y debe corregirse.
        $seguimientoCorreccionActual = $certificado->seguimientos
            ->sortByDesc('id')
            ->first(function ($seguimiento) use ($certificado, $esSolicitante) {
                return $certificado->estado === 'OBSERVADO'
                    && $esSolicitante
                    && !$seguimiento->fecha_derivacion
                    && $seguimiento->estado === 'ACTIVO'
                    && (int) $seguimiento->id_usuario_siguiente === (int) auth()->id();
            });

        // Permisos de pantalla: el solicitante solo consulta/corrige; el jefe puede asignar y revisar.
        $puedeAsignarTecnico = !$consultaGeneral && $esJefeUnidad && $seguimientoAtencionActual;
        $puedeRevisarRequisitos = !$consultaGeneral && !$esSolicitante && $seguimientoTecnicoActual;
        // Permite registrar correccion presencial cuando el tramite esta observado y la etapa activa esta en el solicitante.
        $puedeRegistrarCorreccionRecibida = !$consultaGeneral
            && !$esSolicitante
            && $esUsuarioInterno
            && $certificado->estado === 'OBSERVADO'
            && $seguimientoAtencionActual
            && !$seguimientoAtencionActual->fecha_derivacion
            && $seguimientoAtencionActual->estado === 'ACTIVO';
        $puedeNotificarCorreccion = $puedeRevisarRequisitos
            && $certificado->certificadoRequisitos->contains(fn ($requisito) => $requisito->cumple === 'NO' && $requisito->estado === 'REVISION_OBSERVADA');
        // Boton de emision: aparece cuando todos los requisitos cumplen y el usuario es interno.
        $puedeEmitirCertificado = !$consultaGeneral
            && $esUsuarioInterno
            && $certificado->puedeEmitirse();

        // Funcionarios disponibles para asignar/derivar desde el detalle del tramite.
        // Se usa users + funcionario + cargos activos, asi puede volver al jefe UTHSI u otro cargo INSO.
        $tecnicosDerivacion = User::query()
            ->with([
                'roles',
                'funcionario.cargos' => fn ($query) => $query->where('estado', 1),
            ])
            ->withCount([
                'tramiteSeguimientosAsignados as carga_actual' => function ($query) {
                    $query->where('estado', 'ACTIVO')
                        ->whereNull('fecha_derivacion');
                },
            ])
            ->where('estado', 1)
            ->whereHas('funcionario', function ($query) {
                $query->where('estado', 1)
                    ->whereHas('cargos', fn ($cargo) => $cargo->where('estado', 1));
            })
            ->orderBy('name')
            ->get();
        // Procedencias disponibles para registrar el pago desde el detalle del tramite.
        $procedenciasPago = Procedencia::orderBy('codigo')->get();

        $vistaDetalle = request('bandeja') === 'enviadas'
            ? 'seguimientos_certificados.mis_tramites.ver_tramite'
            : 'certificados.show';

        return view($vistaDetalle, compact(
            'certificado',
            'esSolicitante',
            'esJefeUnidad',
            'seguimientoAtencionActual',
            'seguimientoTecnicoActual',
            'seguimientoCorreccionActual',
            'puedeAsignarTecnico',
            'puedeRevisarRequisitos',
            'puedeRegistrarCorreccionRecibida',
            'puedeNotificarCorreccion',
            'puedeEmitirCertificado',
            'tecnicosDerivacion',
            'procedenciasPago'
        ));
    }

    // VISTA DE EMISION DEL CERTIFICADO
    // Solo se permite entrar cuando todos los requisitos ya cumplen.
    public function emitir(Certificado $certificado)
    {
        $certificado->load($this->relacionesParaEmision());

        if (!$this->usuarioPuedeEmitirCertificado($certificado)) {
            abort(403, 'No puede emitir este certificado.');
        }

        return view('certificados.emision.create', compact('certificado'));
    }

    // GUARDA LA EMISION
    // Por ahora se registra la emision cambiando el estado del certificado a EMITIDO.
    public function guardarEmision(Certificado $certificado)
    {
        $certificado->load($this->relacionesParaEmision());

        if (!$this->usuarioPuedeEmitirCertificado($certificado)) {
            abort(403, 'No puede emitir este certificado.');
        }

        if ($certificado->estado !== 'EMITIDO') {
            $certificado->update(['estado' => 'EMITIDO']);
        }

        session()->flash('swal', [
            'title' => 'Certificado emitido',
            'text' => 'El certificado fue marcado como emitido correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('certificados_emitir', $certificado);
    }

    // ENVIA AVISO AL BENEFICIARIO Y TRAMITADOR
    // Se guarda una notificacion por cada cuenta vinculada, sin usar JSON.
    public function enviarCertificadoSolicitante(Certificado $certificado)
    {
        $certificado->load($this->relacionesParaEmision());

        if (!$this->usuarioPuedeEmitirCertificado($certificado)) {
            abort(403, 'No puede enviar este certificado.');
        }

        if ($certificado->estado !== 'EMITIDO') {
            return back()->with('error', 'Primero debe emitir el certificado.');
        }

        $usuariosSolicitantes = $this->usuariosSolicitantesCertificado($certificado);

        if ($usuariosSolicitantes->isEmpty()) {
            return back()->with('error', 'El beneficiario o tramitador no tiene cuenta de usuario para notificar.');
        }

        foreach ($usuariosSolicitantes as $usuarioSolicitante) {
            NotificacionTramite::create([
                'id_usuario_destino' => $usuarioSolicitante->id,
                'id_usuario_origen' => auth()->id(),
                'id_certificado' => $certificado->id,
                'titulo' => 'Certificado emitido',
                'mensaje' => 'El certificado de su trámite ya fue emitido.',
                'estado' => 'ACTIVO',
            ]);
        }

        session()->flash('swal', [
            'title' => 'Certificado enviado',
            'text' => 'Se notificó al beneficiario y tramitador vinculados al trámite.',
            'icon' => 'success',
        ]);

        return redirect()->route('certificados_emitir', $certificado);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Certificado $certificado)
    {
        $certificado->load('certificadoRequisitos.requisito');

        return view('certificados.edit', array_merge(
            $this->datosFormularioCertificado($certificado),
            compact('certificado')
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, Certificado $certificado)
    {
        $datos = $this->validarCertificado($solicitud, $certificado);

        try {
            DB::beginTransaction();

            // Si no se sube otro PDF, se conserva el documento anterior.
            $rutaDocumento = $this->guardarDocumentoCertificado($solicitud) ?? $certificado->url_documento;

            $certificado->update([
                'id_tipo_certificado' => $datos['form_id_tipo_certificado'],
                'id_persona_beneficiario' => $datos['form_id_persona_beneficiario'],
                'id_persona_tramitador' => $datos['form_id_persona_tramitador'],
                'codigo' => $this->mayuscula($datos['form_codigo']),
                'fecha_inicio' => $datos['form_fecha_inicio'] ?? null,
                'fecha_fin' => $datos['form_fecha_fin'] ?? null,
                'descripcion' => $datos['form_descripcion'] ?? null,
                'url_documento' => $rutaDocumento,
                'estado' => $datos['form_estado'],
            ]);

            // Se reemplazan los requisitos para que coincidan con lo mostrado en pantalla.
            $this->sincronizarRequisitosCertificado(
                $certificado,
                $solicitud->input('requisitos_certificados', [])
            );

            // Despues de sincronizar requisitos se recalcula el estado visible del certificado.
            $certificado->actualizarEstadoVencido();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El certificado se actualizo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('certificados_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el certificado. ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Certificado $certificado)
    {
        try {
            DB::beginTransaction();

            // Los requisitos quedan inactivos y eliminados logicamente junto al certificado.
            $this->eliminarRequisitosCertificado($certificado);

            // Antes del soft delete se marca como anulado para no dejarlo como activo.
            $certificado->update(['estado' => 'ANULADO']);
            $certificado->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El certificado se elimino correctamente.',
                'icon' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            session()->flash('swal', [
                'title' => 'No se pudo eliminar',
                'text' => 'Ocurrio un problema al eliminar el certificado.',
                'icon' => 'error',
            ]);
        }

        return redirect()->route('certificados_index');
    }

    // DATOS BASE DEL FORMULARIO DE CERTIFICADOS
    // Prepara selects y requisitos para create/edit sin repetir consultas.
    private function datosFormularioCertificado(?Certificado $certificado = null): array
    {
        $tiposCertificados = TipoCertificado::query()
            ->with('tipoCertificadoRequisitos.requisito')
            ->where(function ($consulta) use ($certificado) {
                // En editar se incluye el tipo actual aunque luego haya sido marcado inactivo.
                $consulta->where('estado', 'ACTIVO');

                if ($certificado) {
                    $consulta->orWhere('id', $certificado->id_tipo_certificado);
                }
            })
            ->orderBy('nombre')
            ->get();

        $personas = Persona::query()
            ->with(['natural', 'empresa'])
            ->where('estado', 'ACTIVO')
            ->orderBy('id')
            ->get()
            ->map(fn (Persona $persona) => [
                'id' => $persona->id,
                'nombre' => $this->nombrePersona($persona),
                'detalle' => $persona->empresa ? 'Empresa' : 'Persona natural',
            ]);

        $requisitosPorTipoCertificado = $tiposCertificados->mapWithKeys(function ($tipo) use ($certificado) {
            return [
                $tipo->id => $tipo->tipoCertificadoRequisitos
                    ->where('estado', 'ACTIVO')
                    ->map(function ($asignacion) use ($certificado) {
                        $requisitoGuardado = $certificado?->certificadoRequisitos
                            ?->firstWhere('id_requisito', $asignacion->id_requisito);

                        return [
                            'id_requisito' => $asignacion->id_requisito,
                            'descripcion' => $asignacion->requisito?->descripcion ?? 'Requisito no encontrado',
                            'cumple' => $requisitoGuardado?->cumple,
                            'estado' => $requisitoGuardado?->estado ?? 'PENDIENTE_REVISION',
                        ];
                    })
                    ->values(),
            ];
        });

        return compact('tiposCertificados', 'personas', 'requisitosPorTipoCertificado');
    }

    // VALIDACION PRINCIPAL DEL CRUD
    // Se usan nombres legibles para no mostrar form_* al usuario.
    private function validarCertificado(Request $solicitud, ?Certificado $certificado = null): array
    {
        return $solicitud->validate([
            'form_id_tipo_certificado' => ['required', 'exists:tipos_certificados,id'],
            'form_id_persona_beneficiario' => ['required', 'exists:personas,id'],
            'form_id_persona_tramitador' => ['required', 'exists:personas,id'],
            'form_codigo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('certificados', 'codigo')
                    ->ignore($certificado?->id)
                    ->whereNull('deleted_at'),
            ],
            'form_fecha_inicio' => ['nullable', 'date'],
            'form_fecha_fin' => ['nullable', 'date', 'after_or_equal:form_fecha_inicio'],
            'form_descripcion' => ['nullable', 'string'],
            'form_url_documento' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'form_estado' => ['required', Rule::in(array_keys(Certificado::ESTADOS_CERTIFICADO))],
            'requisitos_certificados' => ['nullable', 'array'],
            'requisitos_certificados.*.id_requisito' => ['required', 'exists:requisitos,id'],
            'requisitos_certificados.*.cumple' => ['nullable', 'in:SI,NO'],
            'requisitos_certificados.*.estado' => ['nullable', 'string', 'max:50'],
        ], [], [
            'form_id_tipo_certificado' => 'tipo de certificado',
            'form_id_persona_beneficiario' => 'persona beneficiaria',
            'form_id_persona_tramitador' => 'persona tramitadora',
            'form_codigo' => 'codigo del certificado',
            'form_fecha_inicio' => 'fecha de vigencia inicial',
            'form_fecha_fin' => 'fecha de vigencia final',
            'form_url_documento' => 'documento PDF',
            'form_estado' => 'estado',
        ]);
    }

    // NOMBRE LEGIBLE DE PERSONA
    // Devuelve razon social si es empresa o nombre completo si es persona natural.
    private function nombrePersona(Persona $persona): string
    {
        if ($persona->empresa) {
            return $persona->empresa->razon_social;
        }

        if ($persona->natural) {
            return trim(implode(' ', array_filter([
                $persona->natural->nombres,
                $persona->natural->apellido_paterno,
                $persona->natural->apellido_materno,
            ])));
        }

        return 'Persona #' . $persona->id;
    }

    // FUNCIONARIO HABILITADO PARA ATENDER TRAMITES
    // Permite derivar/revisar a cualquier usuario interno con ficha de funcionario y cargo activo.
    private function usuarioTieneCargoActivo(?User $usuario): bool
    {
        return (bool) $usuario?->funcionario
            && (string) $usuario->funcionario->estado === '1'
            && $usuario->funcionario->cargos->contains(fn ($cargo) => (string) $cargo->estado === '1');
    }

    // GUARDA PDF DEL CERTIFICADO
    // Retorna la ruta relativa para publicarla luego con asset('storage/...').
    private function guardarDocumentoCertificado(Request $solicitud): ?string
    {
        if (!$solicitud->hasFile('form_url_documento')) {
            return null;
        }

        $archivo = $solicitud->file('form_url_documento');
        $nombreArchivo = 'certificado_' . now()->format('Ymd_His') . '_' . uniqid() . '.pdf';

        $ruta = $archivo->storeAs('documentos/certificados', $nombreArchivo, 'public');

        // Si no existe el enlace public/storage, se copia para que el PDF sea visible en Laragon.
        $rutaStorage = storage_path('app/public/' . $ruta);
        $rutaPublica = public_path('storage/' . $ruta);
        File::ensureDirectoryExists(dirname($rutaPublica));

        if (File::exists($rutaStorage)) {
            File::copy($rutaStorage, $rutaPublica);
        }

        return $ruta;
    }

    // GUARDA REQUISITOS DEL CERTIFICADO
    // Cada fila representa el cumplimiento de un requisito para este certificado.
    private function guardarRequisitosCertificado(Certificado $certificado, array $requisitos): void
    {
        $procesados = [];

        foreach ($requisitos as $item) {
            $idRequisito = $item['id_requisito'] ?? null;

            if (!$idRequisito || in_array((int) $idRequisito, $procesados, true)) {
                continue;
            }

            RequisitoCertificado::create([
                'id_certificado' => $certificado->id,
                'id_requisito' => $idRequisito,
                'cumple' => $item['cumple'] ?? null,
                'estado' => $item['estado'] ?? 'PENDIENTE_REVISION',
            ]);

            $procesados[] = (int) $idRequisito;
        }
    }

    // SINCRONIZA REQUISITOS EN EDICION
    // Elimina las filas anteriores y vuelve a guardar lo que quedo en pantalla.
    private function sincronizarRequisitosCertificado(Certificado $certificado, array $requisitos): void
    {
        $this->eliminarRequisitosCertificado($certificado);
        $this->guardarRequisitosCertificado($certificado, $requisitos);
    }

    // ELIMINA LOGICAMENTE REQUISITOS DEL CERTIFICADO
    // Se hace por modelo para que Auditable registre el usuario que elimino.
    private function eliminarRequisitosCertificado(Certificado $certificado): void
    {
        $certificado->certificadoRequisitos()
            ->get()
            ->each(function (RequisitoCertificado $requisitoCertificado) {
                $requisitoCertificado->update(['estado' => 'INACTIVO']);
                $requisitoCertificado->delete();
            });
    }

    // RELACIONES PARA EMISION
    // Carga solo los datos que necesita la vista de emitir certificado.
    private function relacionesParaEmision(): array
    {
        return [
            'tipoCertificado',
            'beneficiario.natural',
            'beneficiario.empresa.tipoEmpresa',
            'beneficiario.usuario',
            'tramitador.natural',
            'tramitador.empresa.tipoEmpresa',
            'tramitador.usuario',
            'certificadoRequisitos.requisito',
            'certificadoRequisitos.evidenciasRequisitos.tipoEvidencia',
            'certificadoRequisitos.revisionesRequisitos.usuarioRevisor.funcionario.cargos',
            'certificadoRequisitos.revisionesRequisitos.observacionesRequisitos',
            'registros.producto.tipoProducto',
            'registros.producto.fabricante',
            'registros.presentacion',
            'pagos.procedencia',
            'seguimientos.usuarioOrigen.funcionario.cargos',
            'seguimientos.usuarioSiguiente.funcionario.cargos',
        ];
    }

    // PERMISO PARA EMITIR
    // Protege la ruta aunque alguien escriba la URL manualmente.
    private function usuarioPuedeEmitirCertificado(Certificado $certificado): bool
    {
        $usuario = auth()->user();
        $usuario?->loadMissing('funcionario.cargos');

        return (bool) $usuario
            && $certificado->puedeEmitirse()
            && (
                $usuario->tieneRol('administrador')
                || $usuario->tieneRol('tecnico-evaluador')
                || $this->usuarioTieneCargoActivo($usuario)
            );
    }

    // USUARIOS A NOTIFICAR
    // Beneficiario y tramitador pueden estar a cargo; se evita duplicar si son la misma cuenta.
    private function usuariosSolicitantesCertificado(Certificado $certificado)
    {
        $certificado->loadMissing('beneficiario.usuario', 'tramitador.usuario');

        return collect([
            $certificado->beneficiario?->usuario,
            $certificado->tramitador?->usuario,
        ])
            ->filter()
            ->unique('id')
            ->values();
    }

    // FUNCION PARA CONVERTIR TEXTO A MAYUSCULAS
    private function mayuscula(?string $texto): ?string
    {
        return $texto === null ? null : mb_strtoupper(trim($texto), 'UTF-8');
    }
}

