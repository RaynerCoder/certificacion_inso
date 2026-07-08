<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\NotificacionTramite;
use App\Models\Persona;
use App\Models\PlantillaCertificado;
use App\Models\PlantillaElemento;
use App\Models\Procedencia;
use App\Models\RequisitoCertificado;
use App\Models\TipoCertificado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
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

    public function emitidos()
    {
        Certificado::actualizarCertificadosEstadosVencidos();

        return view('certificados.emitir_certificado.index');
    }

    
    public function plantillas()
    {
        $tiposCertificados = $this->tiposCertificadosParaPlantilla();

        return view('certificados.plantilla_certificado.index', compact('tiposCertificados'));
    }

    public function crearPlantilla()
    {
        return view('certificados.plantilla_certificado.create', $this->datosPlantillaCertificado());
    }

    public function guardarPlantilla(Request $solicitud)
    {
        $datos = $this->validarPlantillaCertificado($solicitud);

        try {
            DB::beginTransaction();

            if ($datos['form_estado'] === 'ACTIVO') {
                $this->desactivarOtrasPlantillasActivas((int) $datos['form_id_tipo_certificado']);
            }

            $plantilla = PlantillaCertificado::create([
                'id_tipo_certificado' => $datos['form_id_tipo_certificado'],
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'tamano_papel' => $datos['form_tamano_papel'],
                'orientacion' => $datos['form_orientacion'],
                'url_fondo' => $this->guardarFondoPlantilla($solicitud),
                'estado' => $datos['form_estado'],
            ]);

            $this->guardarElementosPlantilla($plantilla, $solicitud->input('elementos_plantilla', '[]'));

            DB::commit();

            session()->flash('swal', [
                'title' => 'Plantilla guardada',
                'text' => 'La plantilla del certificado se registró correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('certificados_plantillas_edit', $plantilla->id_tipo_certificado);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo guardar la plantilla. ' . $e->getMessage())
                ->withInput();
        }
    }

    public function verPlantilla(TipoCertificado $tipoCertificado)
    {
        $tipoCertificado->load($this->relacionesPlantillaCertificado());
        $plantilla = $tipoCertificado->plantillaActiva()
            ->with('elementos.columnas')
            ->first();

        return view('certificados.plantilla_certificado.show', compact('tipoCertificado', 'plantilla'));
    }

    public function editarPlantilla(TipoCertificado $tipoCertificado)
    {
        $tipoCertificado->load($this->relacionesPlantillaCertificado());
        $plantilla = $tipoCertificado->plantillaActiva()
            ->with('elementos.columnas')
            ->first();

        return view('certificados.plantilla_certificado.edit', array_merge(
            $this->datosPlantillaCertificado(),
            compact('tipoCertificado', 'plantilla')
        ));
    }

    public function actualizarPlantilla(Request $solicitud, PlantillaCertificado $plantillaCertificado)
    {
        $datos = $this->validarPlantillaCertificado($solicitud);

        try {
            DB::beginTransaction();

            if ($datos['form_estado'] === 'ACTIVO') {
                $this->desactivarOtrasPlantillasActivas(
                    (int) $datos['form_id_tipo_certificado'],
                    $plantillaCertificado->id
                );
            }

            $urlFondo = $this->guardarFondoPlantilla($solicitud);
            if (!$urlFondo && !$solicitud->boolean('quitar_fondo_plantilla')) {
                $urlFondo = $plantillaCertificado->url_fondo;
            }

            $plantillaCertificado->update([
                'id_tipo_certificado' => $datos['form_id_tipo_certificado'],
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'tamano_papel' => $datos['form_tamano_papel'],
                'orientacion' => $datos['form_orientacion'],
                'url_fondo' => $urlFondo,
                'estado' => $datos['form_estado'],
            ]);

            $this->reemplazarElementosPlantilla($plantillaCertificado, $solicitud->input('elementos_plantilla', '[]'));

            DB::commit();

            session()->flash('swal', [
                'title' => 'Plantilla actualizada',
                'text' => 'Los cambios se guardaron correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('certificados_plantillas_edit', $plantillaCertificado->id_tipo_certificado);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar la plantilla. ' . $e->getMessage())
                ->withInput();
        }
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
        $todosRequisitosCumplen = $certificado->cumpleTodosLosRequisitos();
        $puedeFinalizarTramite = !$consultaGeneral
            && !$esSolicitante
            && $seguimientoTecnicoActual
            && $todosRequisitosCumplen
            && !in_array($certificado->estado, ['APROBADO', 'EMITIDO'], true);
        // Boton de emision: aparece despues de finalizar el tramite.
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
            'puedeFinalizarTramite',
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

        $plantillaCertificado = $this->plantillaActivaParaEmision($certificado);
        $valoresPlantilla = $this->valoresPlantillaCertificado($certificado);

        return view('certificados.emitir_certificado.create', compact(
            'certificado',
            'plantillaCertificado',
            'valoresPlantilla'
        ));
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

    private function datosPlantillaCertificado(): array
    {
        $tiposCertificados = $this->tiposCertificadosParaPlantilla();

        return [
            'tiposCertificados' => $tiposCertificados,
            'tiposCertificadosPlantillaJson' => $this->resumenTiposCertificadosParaPlantilla($tiposCertificados),
            'camposPlantilla' => $this->camposDisponiblesPlantilla(),
        ];
    }

    private function tiposCertificadosParaPlantilla()
    {
        return TipoCertificado::query()
            ->with($this->relacionesPlantillaCertificado())
            ->orderBy('nombre')
            ->get();
    }

    private function relacionesPlantillaCertificado(): array
    {
        return [
            'area',
            'tipoCertificadoRequisitos.requisito',
            'tipoCertificadoRequisitos.tipoEvidencia',
            'tipoCertificadoRequisitos.dependenciasRequisitos.tipoCertificadoRequerido',
            'plantillaActiva.elementos.columnas',
        ];
    }

    private function resumenTiposCertificadosParaPlantilla($tiposCertificados)
    {
        // Este resumen alimenta el panel lateral del editor sin exponer toda la estructura del modelo.
        return $tiposCertificados
            ->map(function ($tipo) {
                return [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre,
                    'area' => $tipo->area?->nombre,
                    'estado' => $tipo->estado,
                    'requisitos' => $tipo->tipoCertificadoRequisitos
                        ->where('estado', 'ACTIVO')
                        ->map(function ($asignacion) {
                            return [
                                'descripcion' => $asignacion->requisito?->descripcion,
                                'evidencia' => $asignacion->tipoEvidencia?->codigo,
                                'certificado_requerido' => $asignacion->dependenciasRequisitos
                                    ->pluck('tipoCertificadoRequerido.nombre')
                                    ->filter()
                                    ->implode(', '),
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();
    }

    private function camposDisponiblesPlantilla(): array
    {
        // Cada código representa un dato que luego se puede resolver al emitir el certificado.
        return [
            'Certificado y trámite' => [
                ['codigo' => 'certificado.codigo', 'nombre' => 'Código del certificado'],
                ['codigo' => 'certificado.fecha_inicio', 'nombre' => 'Fecha de inicio'],
                ['codigo' => 'certificado.fecha_fin', 'nombre' => 'Fecha final'],
                ['codigo' => 'certificado.descripcion', 'nombre' => 'Descripción del trámite'],
                ['codigo' => 'certificado.estado', 'nombre' => 'Estado del certificado'],
                ['codigo' => 'tipo_certificado.nombre', 'nombre' => 'Tipo de certificado'],
                ['codigo' => 'area.nombre', 'nombre' => 'Área responsable'],
            ],
            'Beneficiario' => [
                ['codigo' => 'beneficiario.nombre', 'nombre' => 'Nombre o razón social'],
                ['codigo' => 'beneficiario.documento', 'nombre' => 'CI o NIT'],
                ['codigo' => 'beneficiario.correo', 'nombre' => 'Correo electrónico'],
                ['codigo' => 'beneficiario.domicilio', 'nombre' => 'Domicilio'],
                ['codigo' => 'beneficiario.telefono', 'nombre' => 'Teléfono'],
                ['codigo' => 'beneficiario.territorio', 'nombre' => 'Territorio'],
                ['codigo' => 'beneficiario.tipo_persona', 'nombre' => 'Tipo de persona'],
                ['codigo' => 'empresa.razon_social', 'nombre' => 'Razón social'],
                ['codigo' => 'empresa.matricula', 'nombre' => 'Matrícula de comercio'],
                ['codigo' => 'empresa.tipo_empresa', 'nombre' => 'Tipo de empresa'],
                ['codigo' => 'natural.nombres', 'nombre' => 'Nombres'],
                ['codigo' => 'natural.apellido_paterno', 'nombre' => 'Apellido paterno'],
                ['codigo' => 'natural.apellido_materno', 'nombre' => 'Apellido materno'],
                ['codigo' => 'natural.ocupacion', 'nombre' => 'Ocupación'],
            ],
            'Tramitador' => [
                ['codigo' => 'tramitador.nombre', 'nombre' => 'Nombre del tramitador'],
                ['codigo' => 'tramitador.documento', 'nombre' => 'CI o NIT'],
                ['codigo' => 'tramitador.correo', 'nombre' => 'Correo electrónico'],
                ['codigo' => 'tramitador.telefono', 'nombre' => 'Teléfono'],
                ['codigo' => 'tramitador.rol', 'nombre' => 'Rol en la empresa'],
                ['codigo' => 'tramitador.respaldo', 'nombre' => 'Respaldo registrado'],
            ],
            'Producto' => [
                ['codigo' => 'producto.tabla', 'nombre' => 'Tabla de productos'],
                ['codigo' => 'producto.codigo', 'nombre' => 'Código del producto'],
                ['codigo' => 'producto.nombre_comercial', 'nombre' => 'Nombre comercial'],
                ['codigo' => 'producto.nombre_cientifico', 'nombre' => 'Nombre científico'],
                ['codigo' => 'producto.clasificacion', 'nombre' => 'Clasificación'],
                ['codigo' => 'fabricante.nombre', 'nombre' => 'Fabricante'],
                ['codigo' => 'tipo_producto.codigo', 'nombre' => 'Tipo de producto'],
                ['codigo' => 'producto.estado', 'nombre' => 'Estado del producto'],
            ],
            'Registro y presentación' => [
                ['codigo' => 'registro.codigo', 'nombre' => 'Código de registro'],
                ['codigo' => 'registro.fecha_vigencia', 'nombre' => 'Fecha de vigencia'],
                ['codigo' => 'registro.cantidad', 'nombre' => 'Cantidad registrada'],
                ['codigo' => 'registro.unidad', 'nombre' => 'Unidad registrada'],
                ['codigo' => 'registro.estado', 'nombre' => 'Estado del registro'],
                ['codigo' => 'presentacion.descripcion', 'nombre' => 'Presentación'],
                ['codigo' => 'presentacion.cantidad', 'nombre' => 'Cantidad de presentación'],
                ['codigo' => 'presentacion.unidad', 'nombre' => 'Unidad de presentación'],
                ['codigo' => 'presentacion.url_etiqueta', 'nombre' => 'Etiqueta'],
                ['codigo' => 'presentacion.estado', 'nombre' => 'Estado de presentación'],
            ],
            'Ingredientes' => [
                ['codigo' => 'ingrediente.tabla', 'nombre' => 'Tabla de ingredientes'],
                ['codigo' => 'ingrediente.nombre', 'nombre' => 'Ingrediente'],
                ['codigo' => 'ingrediente.composicion', 'nombre' => 'Composición'],
                ['codigo' => 'ingrediente.riesgo_salud', 'nombre' => 'Riesgo para la salud'],
                ['codigo' => 'ingrediente.porcentaje', 'nombre' => 'Porcentaje en el producto'],
            ],
            'Pago' => [
                ['codigo' => 'pago.resumen', 'nombre' => 'Resumen de pago'],
                ['codigo' => 'pago.tipo_pago', 'nombre' => 'Tipo de pago'],
                ['codigo' => 'pago.fecha', 'nombre' => 'Fecha de pago'],
                ['codigo' => 'pago.monto', 'nombre' => 'Monto pagado'],
                ['codigo' => 'pago.comprobante', 'nombre' => 'Comprobante'],
                ['codigo' => 'pago.factura', 'nombre' => 'Factura'],
                ['codigo' => 'pago.procedencia', 'nombre' => 'Procedencia'],
                ['codigo' => 'pago.fecha_validacion', 'nombre' => 'Fecha de validación'],
                ['codigo' => 'pago.funcionario_validador', 'nombre' => 'Funcionario que validó'],
            ],
            'Requisitos y evidencias' => [
                ['codigo' => 'requisito.tabla', 'nombre' => 'Tabla de requisitos'],
                ['codigo' => 'requisito.descripcion', 'nombre' => 'Requisito'],
                ['codigo' => 'requisito.tipo_evidencia', 'nombre' => 'Tipo de evidencia'],
                ['codigo' => 'requisito.cumple', 'nombre' => 'Cumple requisito'],
                ['codigo' => 'requisito.estado_revision', 'nombre' => 'Estado de revisión'],
                ['codigo' => 'requisito.observacion', 'nombre' => 'Observación'],
                ['codigo' => 'evidencia.valor', 'nombre' => 'Valor o archivo de evidencia'],
                ['codigo' => 'evidencia.estado', 'nombre' => 'Estado de evidencia'],
            ],
            'Seguimiento y revisión' => [
                ['codigo' => 'seguimiento.tabla', 'nombre' => 'Tabla de seguimiento'],
                ['codigo' => 'seguimiento.fecha_inicio', 'nombre' => 'Fecha de inicio'],
                ['codigo' => 'seguimiento.fecha_derivacion', 'nombre' => 'Fecha de derivación'],
                ['codigo' => 'seguimiento.referencia', 'nombre' => 'Referencia'],
                ['codigo' => 'seguimiento.descripcion_final', 'nombre' => 'Descripción final'],
                ['codigo' => 'seguimiento.usuario_origen', 'nombre' => 'Derivado por'],
                ['codigo' => 'seguimiento.usuario_siguiente', 'nombre' => 'Derivado a'],
                ['codigo' => 'seguimiento.estado', 'nombre' => 'Estado del seguimiento'],
                ['codigo' => 'revision.usuario', 'nombre' => 'Usuario revisor'],
                ['codigo' => 'revision.resultado', 'nombre' => 'Resultado de revisión'],
                ['codigo' => 'revision.observacion', 'nombre' => 'Observación de revisión'],
            ],
            'Firmas y QR' => [
                ['codigo' => 'firma.director', 'nombre' => 'Firma director'],
                ['codigo' => 'firma.responsable_area', 'nombre' => 'Firma responsable de área'],
                ['codigo' => 'qr.verificacion', 'nombre' => 'QR de verificación'],
            ],
        ];
    }
    private function validarPlantillaCertificado(Request $solicitud): array
    {
        return $solicitud->validate([
            'form_id_tipo_certificado' => ['required', 'exists:tipos_certificados,id'],
            'form_nombre' => ['required', 'string', 'max:255'],
            'form_descripcion' => ['nullable', 'string'],
            'form_tamano_papel' => ['required', Rule::in(['CARTA', 'OFICIO'])],
            'form_orientacion' => ['required', Rule::in(['VERTICAL', 'HORIZONTAL'])],
            'form_url_fondo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'quitar_fondo_plantilla' => ['nullable', 'boolean'],
            'form_estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
            'elementos_plantilla' => ['nullable', 'string'],
        ], [], [
            'form_id_tipo_certificado' => 'tipo de certificado',
            'form_nombre' => 'nombre de la plantilla',
            'form_tamano_papel' => 'tamaño de papel',
            'form_orientacion' => 'orientación',
            'form_url_fondo' => 'fondo o plantilla',
            'form_estado' => 'estado',
        ]);
    }

    private function guardarFondoPlantilla(Request $solicitud): ?string
    {
        if (!$solicitud->hasFile('form_url_fondo')) {
            return null;
        }

        $archivo = $solicitud->file('form_url_fondo');
        $extension = $archivo->getClientOriginalExtension();
        $nombreArchivo = 'plantilla_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . $extension;
        $ruta = $archivo->storeAs('documentos/plantillas_certificados', $nombreArchivo, 'public');

        $rutaStorage = storage_path('app/public/' . $ruta);
        $rutaPublica = public_path('storage/' . $ruta);
        File::ensureDirectoryExists(dirname($rutaPublica));

        if (File::exists($rutaStorage)) {
            File::copy($rutaStorage, $rutaPublica);
        }

        return $ruta;
    }

    private function desactivarOtrasPlantillasActivas(int $idTipoCertificado, ?int $idPlantillaActual = null): void
    {
        $consulta = PlantillaCertificado::where('id_tipo_certificado', $idTipoCertificado)
            ->where('estado', 'ACTIVO');

        if ($idPlantillaActual) {
            $consulta->where('id', '!=', $idPlantillaActual);
        }

        $consulta->update(['estado' => 'INACTIVO']);
    }

    private function reemplazarElementosPlantilla(PlantillaCertificado $plantilla, string $elementosJson): void
    {
        // Al editar se reemplaza el diseño completo para evitar elementos viejos que ya no están en el lienzo.
        $plantilla->elementos()
            ->get()
            ->each(function (PlantillaElemento $elemento) {
                $elemento->columnas()->delete();
                $elemento->delete();
            });

        $this->guardarElementosPlantilla($plantilla, $elementosJson);
    }

    private function guardarElementosPlantilla(PlantillaCertificado $plantilla, string $elementosJson): void
    {
        $elementos = json_decode($elementosJson, true);

        if (!is_array($elementos)) {
            return;
        }

        $codigosPermitidos = collect($this->camposDisponiblesPlantilla())
            ->flatten(1)
            ->pluck('codigo')
            ->all();

        foreach ($elementos as $orden => $item) {
            $codigoCampo = $item['codigo_campo'] ?? null;
            $tipoElemento = $item['tipo_elemento'] ?? 'CAMPO';

            // Solo se guardan campos definidos por el sistema; así no se aceptan códigos inventados desde el navegador.
            if ($codigoCampo && !in_array($codigoCampo, $codigosPermitidos, true)) {
                continue;
            }

            $datosElemento = [
                'id_plantilla_certificado' => $plantilla->id,
                'tipo_elemento' => $tipoElemento,
                'codigo_campo' => $codigoCampo,
                'texto_fijo' => $item['texto_fijo'] ?? null,
                'pagina' => (int) ($item['pagina'] ?? 1),
                'posicion_x' => (float) ($item['posicion_x'] ?? 0),
                'posicion_y' => (float) ($item['posicion_y'] ?? 0),
                'ancho' => (float) ($item['ancho'] ?? 180),
                'alto' => (float) ($item['alto'] ?? 30),
                'tamano_letra' => (int) ($item['tamano_letra'] ?? 12),
                'alineacion' => $item['alineacion'] ?? 'IZQUIERDA',
                'negrita' => (bool) ($item['negrita'] ?? false),
                'orden' => $orden + 1,
                'estado' => $item['estado'] ?? 'ACTIVO',
            ];

            if (Schema::hasColumn('plantillas_elementos', 'cursiva')) {
                $datosElemento['cursiva'] = (bool) ($item['cursiva'] ?? false);
            }

            if (Schema::hasColumn('plantillas_elementos', 'subrayado')) {
                $datosElemento['subrayado'] = (bool) ($item['subrayado'] ?? false);
            }

            if (Schema::hasColumn('plantillas_elementos', 'color_texto')) {
                $datosElemento['color_texto'] = $item['color_texto'] ?? '#0f172a';
            }

            $elemento = PlantillaElemento::create($datosElemento);

            if ($tipoElemento === 'TABLA') {
                $this->guardarColumnasPlantilla($elemento, $item['columnas'] ?? [], $codigosPermitidos);
            }
        }
    }

    private function guardarColumnasPlantilla(PlantillaElemento $elemento, array $columnas, array $codigosPermitidos): void
    {
        foreach ($columnas as $orden => $columna) {
            $codigoCampo = $columna['codigo_campo'] ?? null;

            if (!$codigoCampo || !in_array($codigoCampo, $codigosPermitidos, true)) {
                continue;
            }

            $elemento->columnas()->create([
                'codigo_campo' => $codigoCampo,
                'titulo_columna' => $columna['titulo_columna'] ?? $codigoCampo,
                'ancho' => (float) ($columna['ancho'] ?? 120),
                'orden' => $orden + 1,
                'estado' => $columna['estado'] ?? 'ACTIVO',
            ]);
        }
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
            'tipoCertificado.area',
            'beneficiario.natural',
            'beneficiario.empresa.tipoEmpresa',
            'beneficiario.territorio',
            'beneficiario.usuario',
            'beneficiario.telefonos',
            'tramitador.natural',
            'tramitador.empresa.tipoEmpresa',
            'tramitador.territorio',
            'tramitador.usuario',
            'tramitador.telefonos',
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

    // PLANTILLA DE EMISION
    // Usa la plantilla activa del tipo de certificado; si aun no existe, usa el modelo base cargado en el sistema.
    private function plantillaActivaParaEmision(Certificado $certificado): ?PlantillaCertificado
    {
        if (!Schema::hasTable('plantillas_certificados') || !Schema::hasTable('plantillas_elementos')) {
            return null;
        }

        return PlantillaCertificado::query()
            ->with('elementosActivos.columnas')
            ->where('id_tipo_certificado', $certificado->id_tipo_certificado)
            ->where('estado', 'ACTIVO')
            ->latest('id')
            ->first();
    }

    // VALORES PARA PLANTILLA
    // La clave debe coincidir con el codigo_campo guardado en plantillas_elementos.
    private function valoresPlantillaCertificado(Certificado $certificado): array
    {
        $beneficiario = $certificado->beneficiario;
        $tramitador = $certificado->tramitador;
        $primerRegistro = $certificado->registros->first();
        $primerPago = $certificado->pagos->first();
        $ultimoSeguimiento = $certificado->seguimientos->sortByDesc('id')->first();

        return [
            'certificado.codigo' => $certificado->codigo ?: 'Sin código',
            'certificado.fecha_inicio' => $certificado->fecha_inicio?->format('d/m/Y') ?: 'Sin fecha',
            'certificado.fecha_fin' => $certificado->fecha_fin?->format('d/m/Y') ?: 'Sin fecha',
            'certificado.descripcion' => $certificado->descripcion ?: 'Sin descripción',
            'certificado.estado' => Certificado::textoEstadoCertificado($certificado->estado),
            'tipo_certificado.nombre' => $certificado->tipoCertificado?->nombre ?: 'Sin tipo',
            'area.nombre' => $certificado->tipoCertificado?->area?->nombre ?: 'Sin área',

            'beneficiario.nombre' => $beneficiario ? $this->nombrePersona($beneficiario) : 'Sin beneficiario',
            'beneficiario.documento' => $this->documentoPersona($beneficiario),
            'beneficiario.correo' => $beneficiario?->correo ?: 'Sin correo',
            'beneficiario.domicilio' => $beneficiario?->domicilio ?: 'Sin domicilio',
            'beneficiario.telefono' => $beneficiario?->telefonos?->first()?->numero ?: 'Sin teléfono',
            'beneficiario.territorio' => $beneficiario?->territorio?->nombre ?: 'Sin territorio',
            'beneficiario.tipo_persona' => $beneficiario?->empresa ? 'Empresa' : 'Persona natural',

            'tramitador.nombre' => $tramitador ? $this->nombrePersona($tramitador) : 'Sin tramitador',
            'tramitador.documento' => $this->documentoPersona($tramitador),
            'tramitador.correo' => $tramitador?->correo ?: 'Sin correo',
            'tramitador.telefono' => $tramitador?->telefonos?->first()?->numero ?: 'Sin teléfono',

            'producto.codigo' => $primerRegistro?->producto?->codigo ?: 'Sin producto',
            'producto.nombre_comercial' => $primerRegistro?->producto?->nombre_comercial ?: 'Sin producto',
            'producto.nombre_cientifico' => $primerRegistro?->producto?->nombre_cientifico ?: 'Sin dato',
            'producto.clasificacion' => $primerRegistro?->producto?->clasificacion ?: 'Sin clasificación',
            'fabricante.nombre' => $primerRegistro?->producto?->fabricante?->nombre ?: 'Sin fabricante',
            'tipo_producto.codigo' => $primerRegistro?->producto?->tipoProducto?->codigo ?: 'Sin tipo',
            'producto.estado' => $primerRegistro?->producto?->estado ?: 'Sin estado',

            'registro.codigo' => $primerRegistro?->codigo_autorizacion ?: 'Sin registro',
            'registro.fecha_vigencia' => $primerRegistro?->fecha_vigencia ? \Illuminate\Support\Carbon::parse($primerRegistro->fecha_vigencia)->format('d/m/Y') : 'Sin fecha',
            'registro.cantidad' => $primerRegistro?->cantidad ?: 'Sin cantidad',
            'registro.unidad' => $primerRegistro?->unidad ?: 'Sin unidad',
            'registro.estado' => $primerRegistro?->estado ?: 'Sin estado',
            'presentacion.descripcion' => $primerRegistro?->presentacion?->descripcion ?: 'Sin presentación',
            'presentacion.cantidad' => $primerRegistro?->presentacion?->cantidad ?: 'Sin cantidad',
            'presentacion.unidad' => $primerRegistro?->presentacion?->unidad ?: 'Sin unidad',

            'pago.tipo_pago' => $primerPago?->tipo_pago ?: 'Sin pago',
            'pago.fecha' => $primerPago?->fecha ? \Illuminate\Support\Carbon::parse($primerPago->fecha)->format('d/m/Y') : 'Sin fecha',
            'pago.monto' => $primerPago?->monto ? number_format((float) $primerPago->monto, 2, ',', '.') : 'Sin monto',
            'pago.factura' => $primerPago?->factura ?: 'Sin factura',
            'pago.procedencia' => $primerPago?->procedencia?->descripcion ?: 'Sin procedencia',

            'seguimiento.fecha_inicio' => $ultimoSeguimiento?->fecha_inicio ? \Illuminate\Support\Carbon::parse($ultimoSeguimiento->fecha_inicio)->format('d/m/Y') : 'Sin fecha',
            'seguimiento.fecha_derivacion' => $ultimoSeguimiento?->fecha_derivacion ? \Illuminate\Support\Carbon::parse($ultimoSeguimiento->fecha_derivacion)->format('d/m/Y') : 'Sin fecha',
            'seguimiento.referencia' => $ultimoSeguimiento?->referencia ?: 'Sin referencia',
            'seguimiento.descripcion_final' => $ultimoSeguimiento?->descripcion_final ?: 'Sin descripción',
            'seguimiento.estado' => $ultimoSeguimiento?->estado ?: 'Sin estado',
        ];
    }

    private function documentoPersona(?Persona $persona): string
    {
        if (!$persona) {
            return 'Sin documento';
        }

        if ($persona->empresa) {
            return $persona->nit ?: 'Sin NIT';
        }

        return $persona->natural?->ci ?: ($persona->nit ?: 'Sin CI/NIT');
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
