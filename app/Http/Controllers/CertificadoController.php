<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\CertificadoEmitido;
use App\Models\CertificadoRegistro;
use App\Models\EvidenciaRequisito;
use App\Models\NotificacionTramite;
use App\Models\Persona;
use App\Models\PlantillaCertificado;
use App\Models\PlantillaElemento;
use App\Models\Procedencia;
use App\Models\Producto;
use App\Models\RequisitoCertificado;
use App\Models\RequisitoTipoCertificado;
use App\Models\TipoCertificado;
use App\Models\User;
use App\Services\DescargaCertificadoService;
use App\Services\ArchivoPrivadoService;
use App\Services\ExpedienteTramiteService;
use App\Services\GestionTramitadoresService;
use App\Services\PlantillaCertificadoStorageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CertificadoController extends Controller
{
    public function __construct(
        private GestionTramitadoresService $gestionTramitadores,
        private DescargaCertificadoService $descargasCertificados,
        private ExpedienteTramiteService $expedientesTramites,
        private PlantillaCertificadoStorageService $archivosPlantillas,
        private ArchivoPrivadoService $archivosPrivados
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Antes de listar se actualizan los certificados que ya pasaron su fecha final.
        Certificado::actualizarCertificadosEstadosVencidos();
        CertificadoEmitido::actualizarEstadosVencidos();

        return view('certificados.index');
    }

    public function emitidos()
    {
        Certificado::actualizarCertificadosEstadosVencidos();
        CertificadoEmitido::actualizarEstadosVencidos();

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

            $datosPlantilla = $this->datosPlantillaParaGuardar([
                'id_tipo_certificado' => $datos['form_id_tipo_certificado'],
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'tamano_papel' => $datos['form_tamano_papel'],
                'orientacion' => $datos['form_orientacion'],
                'url_fondo' => null,
                'estado' => $datos['form_estado'],
            ], $datos, $solicitud);

            $plantilla = PlantillaCertificado::create($datosPlantilla);

            $urlFondo = $this->guardarFondoPlantilla($solicitud, $plantilla);
            if ($urlFondo) {
                $plantilla->update(['url_fondo' => $urlFondo]);
            }

            $this->guardarElementosPlantilla($plantilla, $solicitud->input('form_elementos_plantilla', '[]'));

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
                ->with('error', 'No se pudo guardar la plantilla. '.$e->getMessage())
                ->withInput();
        }
    }

    public function verPlantilla(TipoCertificado $tipoCertificado)
    {
        $tipoCertificado->load($this->relacionesPlantillaCertificado());
        $plantilla = $tipoCertificado->plantillaActiva()
            ->with('elementos.columnas')
            ->first()
            ?? $tipoCertificado->ultimaPlantilla()
                ->with('elementos.columnas')
                ->first();

        return view('certificados.plantilla_certificado.show', compact('tipoCertificado', 'plantilla'));
    }

    public function archivoPlantilla(PlantillaCertificado $plantillaCertificado)
    {
        return $this->archivosPrivados->respuesta($plantillaCertificado->url_fondo, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function editarPlantilla(TipoCertificado $tipoCertificado)
    {
        $tipoCertificado->load($this->relacionesPlantillaCertificado());
        $plantilla = $tipoCertificado->plantillaActiva()
            ->with('elementos.columnas')
            ->first()
            ?? $tipoCertificado->ultimaPlantilla()
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

        if ($plantillaCertificado->estado !== 'INACTIVO' && $datos['form_estado'] === 'INACTIVO') {
            $relacionesEncontradas = $this->relacionesQueImpidenInactivarPlantilla($plantillaCertificado);

            if ($relacionesEncontradas !== []) {
                session()->flash('swal', [
                    'title' => 'No se puede cambiar a Inactivo',
                    'text' => 'La plantilla está relacionada con certificados.',
                    'icon' => 'error',
                ]);

                return redirect()->route(
                    'certificados_plantillas_edit',
                    $plantillaCertificado->id_tipo_certificado
                );
            }
        }

        try {
            DB::beginTransaction();

            if ($datos['form_estado'] === 'ACTIVO') {
                $this->desactivarOtrasPlantillasActivas(
                    (int) $datos['form_id_tipo_certificado'],
                    $plantillaCertificado->id
                );
            }

            $urlFondo = $this->guardarFondoPlantilla($solicitud, $plantillaCertificado);
            if (! $urlFondo && ! $solicitud->boolean('form_quitar_fondo_plantilla')) {
                $urlFondo = $plantillaCertificado->url_fondo;
            }

            $datosPlantilla = $this->datosPlantillaParaGuardar([
                'id_tipo_certificado' => $datos['form_id_tipo_certificado'],
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'tamano_papel' => $datos['form_tamano_papel'],
                'orientacion' => $datos['form_orientacion'],
                'url_fondo' => $urlFondo,
                'estado' => $datos['form_estado'],
            ], $datos, $solicitud);

            $plantillaCertificado->update($datosPlantilla);

            $this->reemplazarElementosPlantilla($plantillaCertificado, $solicitud->input('form_elementos_plantilla', '[]'));

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
                ->with('error', 'No se pudo actualizar la plantilla. '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Elimina lógicamente la plantilla después de dejarla Inactiva.
     */
    public function eliminarPlantilla(PlantillaCertificado $plantillaCertificado)
    {
        $relacionesEncontradas = $this->relacionesQueImpidenInactivarPlantilla($plantillaCertificado);

        if ($relacionesEncontradas !== []) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'La plantilla está relacionada con certificados.',
                'icon' => 'error',
            ]);

            return redirect()->route('certificados_plantillas_index');
        }

        try {
            DB::beginTransaction();

            $plantillaCertificado->update(['estado' => 'INACTIVO']);
            $plantillaCertificado->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'La plantilla se eliminó correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('certificados_plantillas_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('certificados_plantillas_index')
                ->with('error', 'No se pudo eliminar la plantilla.');
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
                'descripcion' => $datos['form_descripcion'] ?? null,
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

            return $solicitud->input('form_accion') === 'guardar_otro'
                ? redirect()->route('certificados_create')
                : redirect()->route('certificados_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el certificado. '.$e->getMessage())
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
            'beneficiario.natural.ocupacionCob',
            'beneficiario.empresa.tipoEmpresa',
            'beneficiario.territorio',
            'beneficiario.usuario',
            'tramitador.natural.ocupacionCob',
            'tramitador.empresa.tipoEmpresa',
            'tramitador.territorio',
            'tramitador.usuario',
            'certificadoRequisitos.requisito',
            'certificadoRequisitos.evidenciasRequisitos.tipoEvidencia',
            'certificadoRequisitos.revisionesRequisitos.usuarioRevisor.funcionario.cargos',
            'certificadoRequisitos.revisionesRequisitos.observacionesRequisitos',
            'registros.producto.clasificacionToxicologica',
            'registros.producto.fabricante',
            'registros.producto.clasificacionQuimica',
            'registros.producto.territorio',
            'registros.producto.importadorPersona.natural',
            'registros.producto.importadorPersona.empresa.tipoEmpresa',
            'registros.producto.importadorPersona.territorio',
            'registros.producto.ingredientesProductos.ingrediente',
            'registros.producto.presentaciones.catalogoUnidad',
            'registros.producto.registros.catalogoUnidad',
            'registros.producto.registros.presentacion.catalogoUnidad',
            'registros.presentacion.catalogoUnidad',
            'registros.catalogoUnidad',
            'certificadosRegistros.registro.producto.clasificacionToxicologica',
            'certificadosRegistros.registro.producto.fabricante',
            'certificadosRegistros.registro.producto.clasificacionQuimica',
            'certificadosRegistros.registro.presentacion.catalogoUnidad',
            'certificadosRegistros.registro.catalogoUnidad',
            'certificadosRegistros.emision',
            'emisiones.certificadoRegistro.registro.producto',
            'pagos.procedencia',
            'pagos.clientePersona.natural',
            'pagos.clientePersona.empresa',
            'pagos.funcionarioUsuario.funcionario.cargos',
            'seguimientos.usuarioOrigen.funcionario.cargos',
            'seguimientos.usuarioAnterior.funcionario.cargos',
            'seguimientos.usuarioSiguiente.roles',
            'seguimientos.usuarioSiguiente.funcionario.cargos',
        ]);

        // Esta configuracion indica que control debe mostrar cada requisito durante la revision.
        $configuracionesRequisitos = RequisitoTipoCertificado::query()
            ->with('tipoEvidencia')
            ->where('id_tipo_certificado', $certificado->id_tipo_certificado)
            ->where('estado', 'ACTIVO')
            ->get()
            ->keyBy('id_requisito');

        $usuarioActual = auth()->user();
        $usuarioActual?->loadMissing('funcionario.cargos');
        // Consulta general: cualquier funcionario puede ver el tramite, pero sin acciones de revision.
        $consultaGeneral = request('bandeja') === 'todos';
        $puedeConsultaGeneral = $consultaGeneral && $usuarioActual && (
            $usuarioActual->tieneRol('administrador')
            || $usuarioActual->tieneRol('funcionario')
            || $this->usuarioTieneCargoActivo($usuarioActual)
        );
        // El solicitante dueño del tramite es el beneficiario vinculado a la cuenta.
        // El tramitador no abre "Mis trámites" si no es tambien beneficiario.
        $esSolicitante = $usuarioActual
            && $this->gestionTramitadores->usuarioPuedeConsultarTramite($usuarioActual, $certificado);
        $esUsuarioInterno = $usuarioActual && (
            $usuarioActual->tieneRol('administrador')
            || $usuarioActual->tieneRol('funcionario')
            || $this->usuarioTieneCargoActivo($usuarioActual)
        );
        // Un funcionario puede abrir desde esta bandeja solo los trámites que registró con su cuenta.
        $esRegistroPropio = request('bandeja') === 'registrados'
            && $usuarioActual
            && (int) $certificado->id_usuario_registro === (int) $usuarioActual->id;
        // Ultima etapa abierta: desde aqui el jefe puede asignar o revisar si aun no derivo a tecnico.
        $seguimientoAtencionActual = $certificado->seguimientos
            ->sortByDesc('id')
            ->first(fn ($seguimiento) => ! $seguimiento->fecha_derivacion && $seguimiento->estado === 'ACTIVO');

        // Jefatura UTHSI: puede atender cuando el tramite esta actualmente en su bandeja.
        $esJefeUnidad = $usuarioActual
            && $usuarioActual->tieneRol('administrador')
            && (int) $seguimientoAtencionActual?->id_usuario_siguiente === (int) $usuarioActual->id;

        // Seguridad del detalle: solo ven el tramite quienes participan en el flujo.
        // Evita que un usuario autenticado abra una solicitud ajena escribiendo la URL.
        $participaEnSeguimiento = $esUsuarioInterno && $usuarioActual && $certificado->seguimientos->contains(
            fn ($seguimiento) => in_array((int) $usuarioActual->id, [
                (int) $seguimiento->id_usuario_origen,
                (int) $seguimiento->id_usuario_siguiente,
                (int) $seguimiento->id_usuario_anterior,
            ], true)
        );

        if (! $esSolicitante && ! $esRegistroPropio && ! $participaEnSeguimiento && ! $puedeConsultaGeneral) {
            abort(403, 'No tiene permiso para ver este tramite.');
        }

        // Etapa activa que puede revisar el usuario actual: jefe, tecnico o funcionario con cargo asignado.
        $seguimientoTecnicoActual = $certificado->seguimientos
            ->sortByDesc('id')
            ->first(function ($seguimiento) use ($usuarioActual, $esJefeUnidad) {
                $usuarioDestino = $seguimiento->usuarioSiguiente;

                return ! $seguimiento->fecha_derivacion
                    && $seguimiento->estado === 'ACTIVO'
                    && (
                        (
                            $esJefeUnidad
                            && (int) $seguimiento->id_usuario_siguiente === (int) $usuarioActual?->id
                        )
                        || (
                            (
                                $usuarioDestino?->tieneRol('funcionario')
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
                    && ! $seguimiento->fecha_derivacion
                    && $seguimiento->estado === 'ACTIVO'
                    && (int) $seguimiento->id_usuario_siguiente === (int) auth()->id();
            });

        // La revision tecnica es interna. El solicitante solo la ve cuando tiene una correccion notificada.
        $mostrarRevisionRequisitos = ! $esSolicitante || (bool) $seguimientoCorreccionActual;

        // Los permisos de consulta no habilitan por si solos acciones que cambian el tramite.
        $puedeGestionarTramite = $usuarioActual?->puede('seguimientos_tramite.gestionar') ?? false;
        $puedeAsignarTecnico = $puedeGestionarTramite
            && ! $consultaGeneral
            && $esJefeUnidad
            && $seguimientoAtencionActual;
        $puedeRevisarRequisitos = $puedeGestionarTramite
            && ! $consultaGeneral
            && ! $esSolicitante
            && $seguimientoTecnicoActual;
        // Permite registrar correccion presencial cuando el tramite esta observado y la etapa activa esta en el solicitante.
        $puedeRegistrarCorreccionRecibida = $puedeGestionarTramite
            && ! $consultaGeneral
            && ! $esSolicitante
            && $esUsuarioInterno
            && $certificado->estado === 'OBSERVADO'
            && $seguimientoAtencionActual
            && ! $seguimientoAtencionActual->fecha_derivacion
            && $seguimientoAtencionActual->estado === 'ACTIVO';
        $puedeNotificarCorreccion = $puedeRevisarRequisitos
            && $certificado->certificadoRequisitos->contains(fn ($requisito) => $requisito->cumple === 'NO' && $requisito->estado === 'REVISION_OBSERVADA');
        $destinatariosCorreccion = $puedeNotificarCorreccion
            ? $this->gestionTramitadores->destinatariosCorreccion($certificado)
            : collect();
        $idDestinatarioCorreccion = $destinatariosCorreccion->isNotEmpty()
            ? $this->gestionTramitadores->idDestinatarioPredeterminado($certificado)
            : null;
        $destinatarioCorreccionBloqueado = ! $certificado->beneficiario?->empresa;
        $todosRequisitosCumplen = $certificado->cumpleTodosLosRequisitos();
        $puedeFinalizarTramite = $puedeGestionarTramite
            && ! $consultaGeneral
            && ! $esSolicitante
            && $seguimientoTecnicoActual
            && $todosRequisitosCumplen
            && ! in_array($certificado->estado, ['APROBADO', 'EMITIDO'], true);
        $tramiteRequiereHabilitarTramitador = $certificado->certificadoRequisitos
            ->flatMap(fn (RequisitoCertificado $requisito) => $requisito->evidenciasRequisitos)
            ->contains(fn ($evidencia) => mb_strtoupper((string) $evidencia->tipoEvidencia?->codigo) === 'TRAMITADOR')
            && (bool) $certificado->beneficiario?->empresa
            && (bool) $certificado->tramitador?->natural;
        // Boton de emision: aparece despues de finalizar el tramite.
        $puedeEmitirCertificado = ! $consultaGeneral
            && $esUsuarioInterno
            && $this->usuarioPuedeEmitirCertificado($certificado);
        $puedeConsultarCertificadoEmitido = $this->usuarioPuedeImprimirCertificado($certificado);

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

        // El límite se cuenta por cuenta de usuario para que beneficiario y tramitador no se afecten entre sí.
        $resumenDescargas = $this->descargasCertificados->resumen($certificado, $usuarioActual);
        $puedeDescargarCertificado = filled($certificado->url_documento)
            && $this->usuarioPuedeDescargarCertificado($certificado);
        $resumenEvaluacionCalificaciones = $certificado->resumenEvaluacionCalificaciones();

        $requiereProductoTramite = $certificado->requiereEvidencia('PRODUCTO');
        $puedeGestionarProductoTramite = $requiereProductoTramite
            && ! $esSolicitante
            && ($puedeAsignarTecnico || $puedeRevisarRequisitos)
            && filled($certificado->id_persona_beneficiario);
        $productosDisponiblesTramite = $puedeGestionarProductoTramite
            ? $this->productosDisponiblesParaTramite($certificado)
            : collect();

        $vistaDetalle = request('bandeja') === 'enviadas'
            ? 'seguimientos_certificados.mis_tramites_beneficiario.ver_tramite'
            : 'certificados.show';

        return view($vistaDetalle, compact(
            'certificado',
            'esSolicitante',
            'esJefeUnidad',
            'seguimientoAtencionActual',
            'seguimientoTecnicoActual',
            'seguimientoCorreccionActual',
            'mostrarRevisionRequisitos',
            'puedeAsignarTecnico',
            'puedeRevisarRequisitos',
            'puedeRegistrarCorreccionRecibida',
            'puedeNotificarCorreccion',
            'destinatariosCorreccion',
            'idDestinatarioCorreccion',
            'destinatarioCorreccionBloqueado',
            'puedeFinalizarTramite',
            'tramiteRequiereHabilitarTramitador',
            'puedeEmitirCertificado',
            'puedeConsultarCertificadoEmitido',
            'puedeDescargarCertificado',
            'resumenDescargas',
            'tecnicosDerivacion',
            'procedenciasPago',
            'configuracionesRequisitos',
            'resumenEvaluacionCalificaciones',
            'puedeGestionarProductoTramite',
            'productosDisponiblesTramite'
        ));
    }

    /**
     * Relaciona un producto ya registrado con el trámite mediante todos sus registros activos.
     */
    public function vincularProductoExistente(Request $request, Certificado $certificado)
    {
        $this->autorizarGestionProductoTramite($request, $certificado);

        $datos = $request->validate(
            [
                'id_producto' => ['required', 'integer', 'exists:productos,id'],
                'bandeja' => ['nullable', 'string', 'max:30'],
            ],
            [
                'id_producto.required' => 'Seleccione un producto registrado.',
                'id_producto.exists' => 'El producto seleccionado ya no se encuentra disponible.',
            ]
        );

        DB::transaction(function () use ($certificado, $datos) {
            $producto = $this->consultaProductosDisponiblesParaTramite($certificado)
                ->lockForUpdate()
                ->find($datos['id_producto']);

            if (! $producto) {
                throw ValidationException::withMessages([
                    'id_producto' => 'El producto no pertenece al beneficiario, está inactivo o ya fue asociado a otro trámite.',
                ]);
            }

            $idsRegistros = $producto->registros()
                ->where('estado', 'ACTIVO')
                ->whereDoesntHave('certificadosRegistros')
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if ($idsRegistros === []) {
                throw ValidationException::withMessages([
                    'id_producto' => 'El producto no tiene registros activos disponibles para asociar.',
                ]);
            }

            $certificado->registros()->syncWithoutDetaching($idsRegistros);
        });

        return redirect()
            ->route('certificados_show', [
                'certificado' => $certificado,
                'bandeja' => $datos['bandeja'] ?? 'recibidas',
            ])
            ->with('success', 'Producto asociado correctamente al trámite.');
    }

    /**
     * La acción queda limitada al funcionario que actualmente atiende el trámite.
     */
    private function autorizarGestionProductoTramite(Request $request, Certificado $certificado): void
    {
        $usuario = $request->user()?->loadMissing('funcionario.cargos', 'roles');
        $seguimientoActual = $certificado->seguimientos()
            ->whereNull('fecha_derivacion')
            ->where('estado', 'ACTIVO')
            ->latest('id')
            ->first();
        $esUsuarioInterno = $usuario && (
            $usuario->tieneRol('administrador')
            || $usuario->tieneRol('funcionario')
            || $this->usuarioTieneCargoActivo($usuario)
        );

        if (
            ! $usuario
            || ! $usuario->puede('seguimientos_tramite.gestionar')
            || ! $esUsuarioInterno
            || $request->input('bandeja') === 'todos'
            || (int) $seguimientoActual?->id_usuario_siguiente !== (int) $usuario->id
        ) {
            abort(403, 'No tiene permiso para asociar productos a este trámite.');
        }

        if (! $certificado->requiereEvidencia('PRODUCTO')) {
            throw ValidationException::withMessages([
                'id_producto' => 'Este trámite no tiene producto configurado como requisito.',
            ]);
        }

        if (! $certificado->id_persona_beneficiario) {
            throw ValidationException::withMessages([
                'id_producto' => 'El trámite no tiene un beneficiario al cual pertenezca el producto.',
            ]);
        }
    }

    /**
     * Devuelve productos activos del beneficiario, con registros activos y todavía libres.
     */
    private function consultaProductosDisponiblesParaTramite(Certificado $certificado)
    {
        $certificado->loadMissing('registros.producto');

        $tiposProductoAsociados = $certificado->registros
            ->pluck('producto.tipo_producto')
            ->filter()
            ->unique();
        $tipoProductoAsociado = $tiposProductoAsociados->count() === 1
            ? $tiposProductoAsociados->first()
            : null;

        return Producto::query()
            ->where('id_importador_persona', $certificado->id_persona_beneficiario)
            ->where('estado', 'ACTIVO')
            ->whereIn('tipo_producto', array_keys(Producto::TIPOS_PRODUCTO))
            ->when($tipoProductoAsociado, fn ($consulta, $tipo) => $consulta->where('tipo_producto', $tipo))
            ->whereHas('registros', fn ($consulta) => $consulta->where('estado', 'ACTIVO'))
            ->whereDoesntHave('registros.certificadosRegistros');
    }

    private function productosDisponiblesParaTramite(Certificado $certificado)
    {
        return $this->consultaProductosDisponiblesParaTramite($certificado)
            ->with([
                'importadorPersona.natural',
                'importadorPersona.empresa',
            ])
            ->withCount([
                'presentaciones as presentaciones_activas_count' => fn ($consulta) => $consulta->where('estado', 'ACTIVO'),
                'registros as registros_activos_count' => fn ($consulta) => $consulta->where('estado', 'ACTIVO'),
            ])
            ->orderBy('nombre_comercial')
            ->orderBy('codigo')
            ->get();
    }

    // VISTA DE EMISION DEL CERTIFICADO
    // Solo se permite entrar cuando todos los requisitos ya cumplen.
    public function emitir(Certificado $certificado)
    {
        $certificado->load($this->relacionesParaEmision());

        $puedeGestionarEmision = $this->usuarioPuedeEmitirCertificado($certificado);

        if (! $puedeGestionarEmision && ! $this->usuarioPuedeImprimirCertificado($certificado)) {
            abort(403, 'No puede imprimir este certificado.');
        }

        $plantillaCertificado = $this->plantillaActivaParaEmision($certificado);
        $registroSeleccionado = $this->registroSeleccionadoParaEmision($certificado, request()->integer('registro'));
        $emisionSeleccionada = $registroSeleccionado?->emision;
        $valoresPlantilla = $this->valoresPlantillaCertificado($certificado, $emisionSeleccionada, $registroSeleccionado);
        $resumenDescargas = $this->descargasCertificados->resumen($certificado, auth()->user());

        return view('certificados.emitir_certificado.create', compact(
            'certificado',
            'plantillaCertificado',
            'valoresPlantilla',
            'resumenDescargas',
            'puedeGestionarEmision',
            'registroSeleccionado',
            'emisionSeleccionada'
        ));
    }

    // GUARDA LA EMISION
    // Por ahora se registra la emision cambiando el estado del certificado a EMITIDO.
    public function guardarEmision(Request $solicitud, Certificado $certificado)
    {
        $certificado->load($this->relacionesParaEmision());

        if (! $this->usuarioPuedeEmitirCertificado($certificado)) {
            abort(403, 'No puede emitir este certificado.');
        }

        $plantillaCertificado = $this->plantillaActivaParaEmision($certificado);

        if (! $plantillaCertificado) {
            return back()->with('error', 'No se puede emitir el certificado porque no tiene una plantilla activa configurada.');
        }

        $datos = $this->validarEmisionCertificado($solicitud, $certificado);
        $medidasBasePlantilla = $this->medidasLienzoPlantilla(
            $plantillaCertificado->tamano_papel,
            $plantillaCertificado->orientacion
        );
        $imagenRenderizada = $this->validarImagenRenderizada(
            $datos['form_documento_renderizado'],
            (int) ($plantillaCertificado->ancho_lienzo_px ?: $medidasBasePlantilla['ancho']),
            (int) ($plantillaCertificado->alto_lienzo_px ?: $medidasBasePlantilla['alto'])
        );

        if (! $imagenRenderizada) {
            throw ValidationException::withMessages([
                'form_documento_renderizado' => 'No se pudo validar el diseño final. Recargue la página antes de emitir.',
            ])->errorBag('emisionCertificado');
        }

        DB::transaction(function () use ($certificado, $plantillaCertificado, $datos, $solicitud, $imagenRenderizada) {
            $tramite = Certificado::query()
                ->with($this->relacionesParaEmision())
                ->lockForUpdate()
                ->findOrFail($certificado->id);

            $registroSeleccionado = $this->registroSeleccionadoParaEmision(
                $tramite,
                isset($datos['form_id_certificado_registro']) ? (int) $datos['form_id_certificado_registro'] : null
            );

            if ($registroSeleccionado?->emision) {
                throw ValidationException::withMessages([
                    'form_id_certificado_registro' => 'El lote seleccionado ya tiene un certificado emitido.',
                ])->errorBag('emisionCertificado');
            }

            if ($tramite->tipoCertificado?->modo_emision !== 'POR_LOTE' && $tramite->emisiones()->exists()) {
                throw ValidationException::withMessages([
                    'form_documento_renderizado' => 'Este trámite ya tiene su certificado emitido.',
                ])->errorBag('emisionCertificado');
            }

            $fechaFin = $this->resolverFechaFinEmision($solicitud, $datos);
            $emision = CertificadoEmitido::create([
                'id_certificado' => $tramite->id,
                'id_certificado_registro' => $registroSeleccionado?->id,
                'id_plantilla_certificado' => $plantillaCertificado->id,
                'orden_emision' => ((int) $tramite->emisiones()->lockForUpdate()->max('orden_emision')) + 1,
                'numero_certificado' => $this->siguienteNumeroCertificado($tramite->tipoCertificado),
                'fecha_emision' => now(),
                'fecha_inicio' => $datos['form_fecha_inicio'],
                'fecha_fin' => $fechaFin,
                'vigencia_indefinida' => $solicitud->boolean('form_vigencia_indefinida'),
                'estado' => 'EMITIDO',
            ]);
            $emision->setRelation('certificadoRegistro', $registroSeleccionado);

            $emision->update([
                'url_documento' => $this->generarDocumentoEmitido(
                    $tramite,
                    $emision,
                    $plantillaCertificado,
                    $imagenRenderizada
                ),
            ]);

            // El trámite se considera emitido al completar todos sus lotes requeridos.
            if ($this->tramiteCompletoParaEmision($tramite)) {
                $tramite->update(['estado' => 'EMITIDO']);
            }
        });

        session()->flash('swal', [
            'title' => 'Certificado emitido',
            'text' => 'El certificado fue marcado como emitido correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('certificados_emitir', [
            'certificado' => $certificado,
            'bandeja' => $solicitud->input('form_bandeja', 'finalizados'),
            'registro' => $datos['form_id_certificado_registro'] ?? null,
        ]);
    }

    // ENTREGA EL PDF Y GUARDA QUIEN LO DESCARGÓ
    // El archivo nunca se expone con una URL directa; primero se valida acceso y límite.
    public function descargar(Request $solicitud, Certificado $certificado)
    {
        $usuario = auth()->user();
        $certificado->loadMissing('tipoCertificado', 'beneficiario.usuario', 'tramitador.usuario', 'seguimientos');

        if (! $usuario || ! $this->usuarioPuedeDescargarCertificado($certificado)) {
            abort(403, 'No tiene permiso para descargar este certificado.');
        }

        $rutaDocumento = $this->descargasCertificados->rutaDocumento($certificado);

        if (! $rutaDocumento) {
            return back()->with('error', 'El documento del certificado no está disponible.');
        }

        $soloVisualizar = $solicitud->boolean('visualizar');

        // Ver el PDF en pantalla no consume el límite. Solo se registra una descarga real.
        if (! $soloVisualizar && ! $solicitud->isMethod('head')) {
            try {
                $this->descargasCertificados->registrar($certificado, $usuario);
            } catch (\RuntimeException $error) {
                return back()->with('error', $error->getMessage());
            }
        }

        if ($soloVisualizar) {
            return response()->file($rutaDocumento, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$this->descargasCertificados->nombreDocumento($certificado).'"',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]);
        }

        return response()->download(
            $rutaDocumento,
            $this->descargasCertificados->nombreDocumento($certificado),
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]
        );
    }

    // Descarga un documento concreto cuando un trámite tiene emisiones por lote.
    public function descargarEmitido(Request $solicitud, Certificado $certificado, CertificadoEmitido $emision)
    {
        abort_unless((int) $emision->id_certificado === (int) $certificado->id, 404);

        $usuario = auth()->user();
        $certificado->loadMissing('tipoCertificado', 'beneficiario.usuario', 'tramitador.usuario', 'seguimientos');
        abort_unless($usuario && $this->usuarioPuedeDescargarCertificado($certificado), 403);

        $rutaDocumento = $this->descargasCertificados->rutaDocumento($emision);
        abort_unless($rutaDocumento, 404, 'El documento emitido no está disponible.');

        if (! $solicitud->boolean('visualizar') && ! $solicitud->isMethod('head')) {
            \App\Models\DescargaCertificado::create([
                'id_certificado' => $certificado->id,
                'id_certificado_emitido' => $emision->id,
                'id_usuario' => $usuario->id,
                'fecha_descarga' => now(),
            ]);
        }

        $cabeceras = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ];

        return $solicitud->boolean('visualizar')
            ? response()->file($rutaDocumento, $cabeceras + ['Content-Disposition' => 'inline; filename="'.$this->descargasCertificados->nombreDocumento($emision).'"'])
            : response()->download($rutaDocumento, $this->descargasCertificados->nombreDocumento($emision), $cabeceras);
    }

    // Entrega una evidencia del expediente sin publicar la carpeta privada.
    public function descargarEvidencia(
        Request $solicitud,
        Certificado $certificado,
        EvidenciaRequisito $evidenciaRequisito
    ) {
        $evidenciaRequisito->loadMissing('requisitoCertificado');

        abort_unless(
            (int) $evidenciaRequisito->requisitoCertificado?->id_certificado === (int) $certificado->id,
            404
        );

        $usuario = $solicitud->user();
        $puedeConsultarComoFuncionario = $usuario?->puede([
            'certificados.ver',
            'seguimientos_tramite.ver',
            'seguimientos_tramite.registrados',
            'seguimientos_tramite.atender',
            'seguimientos_tramite.gestionar',
            'seguimientos_tramite.consulta_general',
        ]);

        abort_unless(
            $usuario && ($puedeConsultarComoFuncionario
                || $this->gestionTramitadores->usuarioPuedeConsultarTramite($usuario, $certificado)),
            403,
            'No tiene permiso para consultar esta evidencia.'
        );

        $ruta = $this->expedientesTramites->rutaAbsoluta($evidenciaRequisito->valor);

        abort_if(blank($ruta), 404, 'La evidencia no fue encontrada.');

        $nombre = basename($ruta);
        $nombre = str_replace(['"', "\r", "\n"], '', $nombre);

        return response()->file($ruta, [
            'Content-Type' => File::mimeType($ruta) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$nombre.'"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    // ENVIA AVISO AL BENEFICIARIO Y TRAMITADOR
    // Se guarda una notificacion por cada cuenta vinculada, sin usar JSON.
    public function enviarCertificadoSolicitante(Certificado $certificado)
    {
        $certificado->load($this->relacionesParaEmision());

        if (! $this->usuarioPuedeEmitirCertificado($certificado)) {
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

            $certificado->update([
                'id_tipo_certificado' => $datos['form_id_tipo_certificado'],
                'id_persona_beneficiario' => $datos['form_id_persona_beneficiario'],
                'id_persona_tramitador' => $datos['form_id_persona_tramitador'],
                'descripcion' => $datos['form_descripcion'] ?? null,
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
                ->with('error', 'No se pudo actualizar el certificado. '.$e->getMessage())
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
        // Cada codigo es una llave segura que el backend sabe resolver al emitir el certificado.
        // El nombre es el texto que vera el usuario que configura la plantilla.
        return [
            'Certificado y trámite' => [
                ['codigo' => 'certificado.codigo', 'nombre' => 'Código del certificado'],
                ['codigo' => 'emision.numero_certificado', 'nombre' => 'Número del documento emitido'],
                ['codigo' => 'emision.fecha', 'nombre' => 'Fecha de emisión'],
                ['codigo' => 'certificado.fecha_inicio', 'nombre' => 'Fecha de inicio'],
                ['codigo' => 'certificado.fecha_fin', 'nombre' => 'Fecha final'],
                ['codigo' => 'certificado.descripcion', 'nombre' => 'Descripción del trámite'],
                ['codigo' => 'certificado.estado', 'nombre' => 'Estado del certificado'],
                ['codigo' => 'evaluacion.resultado', 'nombre' => 'Resultado de la evaluación'],
                ['codigo' => 'evaluacion.calificaciones', 'nombre' => 'Calificaciones obtenidas'],
                ['codigo' => 'evaluacion.calificaciones_minimas', 'nombre' => 'Calificaciones mínimas'],
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
                ['codigo' => 'empresa.representante_legal_nombre', 'nombre' => 'Representante legal'],
                ['codigo' => 'empresa.representante_legal_documento', 'nombre' => 'CI del representante legal'],
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
            'Importador del producto' => [
                ['codigo' => 'importador.nombre', 'nombre' => 'Nombre o razón social del importador'],
                ['codigo' => 'importador.documento', 'nombre' => 'CI o NIT del importador'],
                ['codigo' => 'importador.tipo_persona', 'nombre' => 'Tipo de persona del importador'],
                ['codigo' => 'importador.domicilio', 'nombre' => 'Domicilio del importador'],
                ['codigo' => 'importador.territorio', 'nombre' => 'Territorio del importador'],
                ['codigo' => 'importador.ubicacion', 'nombre' => 'Territorio y domicilio del importador'],
                ['codigo' => 'importador.correo', 'nombre' => 'Correo del importador'],
                ['codigo' => 'importador.telefono', 'nombre' => 'Teléfono del importador'],
                ['codigo' => 'importador.representante_legal_nombre', 'nombre' => 'Representante legal del importador'],
                ['codigo' => 'importador.representante_legal_documento', 'nombre' => 'CI del representante legal del importador'],
            ],
            'Productos' => [
                ['codigo' => 'producto.tabla', 'nombre' => 'Tabla de productos y registros'],
                ['codigo' => 'producto.nombres_comerciales', 'nombre' => 'Productos separados por coma'],
                ['codigo' => 'producto.codigos', 'nombre' => 'Códigos de productos separados por coma'],
                ['codigo' => 'producto.nombres_tecnicos', 'nombre' => 'Nombres técnicos separados por coma'],
                ['codigo' => 'producto.clasificaciones_quimicas', 'nombre' => 'Clasificaciones químicas separadas por coma'],
                ['codigo' => 'fabricante.nombres', 'nombre' => 'Fabricantes separados por coma'],
                ['codigo' => 'clasificacion_toxicologica.codigos', 'nombre' => 'Clasificaciones toxicológicas separadas por coma'],
                ['codigo' => 'producto.estados', 'nombre' => 'Estados de productos separados por coma'],
                ['codigo' => 'producto.codigo', 'nombre' => 'Código del primer producto'],
                ['codigo' => 'producto.nombre_comercial', 'nombre' => 'Nombre comercial del primer producto'],
                ['codigo' => 'producto.nombre_tecnico', 'nombre' => 'Nombre técnico del primer producto'],
                ['codigo' => 'producto.tipo_producto', 'nombre' => 'Tipo del primer producto'],
                ['codigo' => 'producto.pais_origen', 'nombre' => 'País de origen del primer producto'],
                ['codigo' => 'producto.clasificacion_quimica', 'nombre' => 'Clasificación química del primer producto'],
                ['codigo' => 'producto.clasificacion_quimica_descripcion', 'nombre' => 'Descripción de la clasificación química'],
                ['codigo' => 'fabricante.nombre', 'nombre' => 'Fabricante del primer producto'],
                ['codigo' => 'fabricante.razon_social', 'nombre' => 'Razón social del fabricante'],
                ['codigo' => 'fabricante.descripcion', 'nombre' => 'Descripción del fabricante'],
                ['codigo' => 'clasificacion_toxicologica.codigo', 'nombre' => 'Clasificación toxicológica del primer producto'],
                ['codigo' => 'clasificacion_toxicologica.descripcion', 'nombre' => 'Descripción toxicológica'],
                ['codigo' => 'clasificacion_toxicologica.completa', 'nombre' => 'Clasificación toxicológica completa'],
                ['codigo' => 'producto.estado', 'nombre' => 'Estado del primer producto'],
            ],
            'Registros y presentaciones' => [
                ['codigo' => 'registro.codigos', 'nombre' => 'Registros separados por coma'],
                ['codigo' => 'registro.lotes', 'nombre' => 'Números de lote separados por coma'],
                ['codigo' => 'registro.fechas_vigencia', 'nombre' => 'Vigencias separadas por coma'],
                ['codigo' => 'registro.cantidades', 'nombre' => 'Cantidades registradas separadas por coma'],
                ['codigo' => 'registro.unidades', 'nombre' => 'Unidades registradas separadas por coma'],
                ['codigo' => 'presentacion.descripciones', 'nombre' => 'Presentaciones separadas por coma'],
                ['codigo' => 'presentacion.lista', 'nombre' => 'Presentaciones con cantidad y unidad'],
                ['codigo' => 'presentacion.cantidades', 'nombre' => 'Cantidades de presentación separadas por coma'],
                ['codigo' => 'presentacion.unidades', 'nombre' => 'Unidades de presentación separadas por coma'],
                ['codigo' => 'registro.codigo', 'nombre' => 'Código del primer registro'],
                ['codigo' => 'registro.numero_lote', 'nombre' => 'Número de lote del primer registro'],
                ['codigo' => 'registro.fecha_vigencia', 'nombre' => 'Fecha de vigencia'],
                ['codigo' => 'registro.cantidad', 'nombre' => 'Cantidad registrada'],
                ['codigo' => 'registro.unidad', 'nombre' => 'Unidad registrada'],
                ['codigo' => 'registro.estado', 'nombre' => 'Estado del registro'],
                ['codigo' => 'presentacion.descripcion', 'nombre' => 'Presentación'],
                ['codigo' => 'presentacion.tipo_presentacion', 'nombre' => 'Tipo de presentación'],
                ['codigo' => 'presentacion.cantidad', 'nombre' => 'Cantidad de presentación'],
                ['codigo' => 'presentacion.unidad', 'nombre' => 'Unidad de presentación'],
                ['codigo' => 'presentacion.url_etiqueta', 'nombre' => 'Etiqueta'],
                ['codigo' => 'presentacion.estado', 'nombre' => 'Estado de presentación'],
            ],
            'Ingredientes' => [
                ['codigo' => 'ingrediente.tabla', 'nombre' => 'Tabla de ingredientes'],
                ['codigo' => 'ingrediente.nombre', 'nombre' => 'Ingrediente'],
                ['codigo' => 'ingrediente.nombres', 'nombre' => 'Ingredientes separados por coma'],
                ['codigo' => 'ingrediente.composicion', 'nombre' => 'Composición'],
                ['codigo' => 'ingrediente.composiciones', 'nombre' => 'Composiciones separadas por coma'],
                ['codigo' => 'ingrediente.riesgo_salud', 'nombre' => 'Riesgo para la salud'],
                ['codigo' => 'ingrediente.riesgos_salud', 'nombre' => 'Riesgos para la salud separados por coma'],
                ['codigo' => 'ingrediente.porcentaje', 'nombre' => 'Porcentaje en el producto'],
                ['codigo' => 'ingrediente.porcentajes', 'nombre' => 'Porcentajes separados por coma'],
                ['codigo' => 'ingrediente.resumen', 'nombre' => 'Ingredientes con sus porcentajes'],
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
            'Funcionarios para firma' => [
                ['codigo' => 'funcionario.revisor_nombre', 'nombre' => 'Nombre del revisor'],
                ['codigo' => 'funcionario.revisor_cargo', 'nombre' => 'Cargo del revisor'],
                ['codigo' => 'funcionario.destino_nombre', 'nombre' => 'Nombre del funcionario destino'],
                ['codigo' => 'funcionario.destino_cargo', 'nombre' => 'Cargo del funcionario destino'],
                ['codigo' => 'funcionario.origen_nombre', 'nombre' => 'Nombre del funcionario que derivó'],
                ['codigo' => 'funcionario.origen_cargo', 'nombre' => 'Cargo del funcionario que derivó'],
                ['codigo' => 'funcionario.validador_pago_nombre', 'nombre' => 'Nombre del validador de pago'],
                ['codigo' => 'funcionario.validador_pago_cargo', 'nombre' => 'Cargo del validador de pago'],
                ['codigo' => 'funcionario.director_nombre', 'nombre' => 'Nombre del director'],
                ['codigo' => 'funcionario.director_cargo', 'nombre' => 'Cargo del director'],
                ['codigo' => 'funcionario.responsable_area_nombre', 'nombre' => 'Nombre para firma del área'],
                ['codigo' => 'funcionario.responsable_area_cargo', 'nombre' => 'Cargo para firma del área'],
                ['codigo' => 'funcionario.emisor_nombre', 'nombre' => 'Funcionario que emitió el certificado'],
                ['codigo' => 'funcionario.emisor_cargo', 'nombre' => 'Cargo del funcionario emisor'],
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
            'form_ajuste_fondo' => ['required', Rule::in(['ESTIRAR', 'CONTENER', 'CUBRIR'])],
            'form_fondo_trabajo' => ['required', Rule::in(['PLANTILLA', 'BLANCO'])],
            'form_imprimir_transparente' => ['nullable', 'boolean'],
            'form_imprimir_fondo' => ['nullable', 'boolean'],
            'form_url_fondo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
            'form_quitar_fondo_plantilla' => ['nullable', 'boolean'],
            'form_estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
            'form_elementos_plantilla' => ['nullable', 'string'],
            'form_diseno_fabric_json' => ['nullable', 'json', 'max:10000000'],
        ], [], [
            'form_id_tipo_certificado' => 'tipo de certificado',
            'form_nombre' => 'nombre de la plantilla',
            'form_tamano_papel' => 'tamaño de papel',
            'form_orientacion' => 'orientación',
            'form_ajuste_fondo' => 'forma de adaptar la plantilla',
            'form_fondo_trabajo' => 'fondo de trabajo',
            'form_url_fondo' => 'archivo de plantilla',
            'form_estado' => 'estado',
        ]);
    }

    private function datosPlantillaParaGuardar(array $datosPlantilla, array $datos, Request $solicitud): array
    {
        $medidasLienzo = $this->medidasLienzoPlantilla(
            $datos['form_tamano_papel'] ?? 'CARTA',
            $datos['form_orientacion'] ?? 'VERTICAL'
        );

        if (Schema::hasColumn('plantillas_certificados', 'ancho_lienzo_px')) {
            $datosPlantilla['ancho_lienzo_px'] = $medidasLienzo['ancho'];
        }

        if (Schema::hasColumn('plantillas_certificados', 'alto_lienzo_px')) {
            $datosPlantilla['alto_lienzo_px'] = $medidasLienzo['alto'];
        }

        if (Schema::hasColumn('plantillas_certificados', 'ajuste_fondo')) {
            $datosPlantilla['ajuste_fondo'] = $datos['form_ajuste_fondo'] ?? 'ESTIRAR';
        }

        if (Schema::hasColumn('plantillas_certificados', 'fondo_trabajo')) {
            $datosPlantilla['fondo_trabajo'] = $datos['form_fondo_trabajo'] ?? 'PLANTILLA';
        }

        if (Schema::hasColumn('plantillas_certificados', 'imprimir_transparente')) {
            $datosPlantilla['imprimir_transparente'] = $solicitud->has('form_imprimir_fondo')
                ? ! $solicitud->boolean('form_imprimir_fondo')
                : $solicitud->boolean('form_imprimir_transparente');
        }

        if (Schema::hasColumn('plantillas_certificados', 'diseno_fabric_json')) {
            $disenoFabric = json_decode((string) ($datos['form_diseno_fabric_json'] ?? ''), true);
            $datosPlantilla['diseno_fabric_json'] = is_array($disenoFabric) ? $disenoFabric : null;
        }

        return $datosPlantilla;
    }

    private function medidasLienzoPlantilla(string $tamanoPapel, string $orientacion): array
    {
        $medidas = strtoupper($tamanoPapel) === 'OFICIO'
            ? ['ancho' => 816, 'alto' => 1344]
            : ['ancho' => 816, 'alto' => 1056];

        if (strtoupper($orientacion) === 'HORIZONTAL') {
            return [
                'ancho' => $medidas['alto'],
                'alto' => $medidas['ancho'],
            ];
        }

        return $medidas;
    }

    private function guardarFondoPlantilla(
        Request $solicitud,
        PlantillaCertificado $plantillaCertificado
    ): ?string
    {
        if (! $solicitud->hasFile('form_url_fondo')) {
            return null;
        }

        return $this->archivosPlantillas->guardar(
            $solicitud->file('form_url_fondo'),
            $plantillaCertificado
        );
    }

    private function desactivarOtrasPlantillasActivas(int $idTipoCertificado, ?int $idPlantillaActual = null): void
    {
        $consulta = PlantillaCertificado::where('id_tipo_certificado', $idTipoCertificado)
            ->where('estado', 'ACTIVO');

        if ($idPlantillaActual) {
            $consulta->where('id', '!=', $idPlantillaActual);
        }

        $plantillasActivas = $consulta->get();

        foreach ($plantillasActivas as $plantillaActiva) {
            if ($this->relacionesQueImpidenInactivarPlantilla($plantillaActiva) !== []) {
                throw ValidationException::withMessages([
                    'form_estado' => 'No se puede reemplazar la plantilla activa porque está relacionada con certificados.',
                ]);
            }
        }

        $plantillasActivas->each(
            fn (PlantillaCertificado $plantillaActiva) => $plantillaActiva->update(['estado' => 'INACTIVO'])
        );
    }

    /**
     * Revisa si existen certificados que todavía dependen de la plantilla.
     */
    private function relacionesQueImpidenInactivarPlantilla(PlantillaCertificado $plantillaCertificado): array
    {
        $relacionesEncontradas = [];
        $tipoCertificado = $plantillaCertificado->tipoCertificado()->first();

        if ($tipoCertificado && $tipoCertificado->certificados()->withTrashed()->exists()) {
            $relacionesEncontradas[] = 'certificados';
        }

        return $relacionesEncontradas;
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

        if (! is_array($elementos)) {
            return;
        }

        $codigosPermitidos = collect($this->camposDisponiblesPlantilla())
            ->flatten(1)
            ->pluck('codigo')
            ->all();

        foreach ($elementos as $orden => $item) {
            $codigoElemento = $item['codigo_elemento'] ?? null;
            $tiposPermitidos = ['TEXTO', 'CAMPO', 'TABLA', 'FIRMA', 'QR', 'IMAGEN', 'FORMA', 'LINEA'];
            $tipoElemento = strtoupper((string) ($item['tipo_elemento'] ?? 'CAMPO'));
            $textoFijo = $item['texto_fijo'] ?? null;

            if (! in_array($tipoElemento, $tiposPermitidos, true)) {
                continue;
            }

            // Solo se guardan campos definidos por el sistema; así no se aceptan códigos inventados desde el navegador.
            if ($codigoElemento && ! in_array($codigoElemento, $codigosPermitidos, true)) {
                continue;
            }

            $datosElemento = [
                'id_plantilla_certificado' => $plantilla->id,
                'tipo_elemento' => $tipoElemento,
                'codigo_elemento' => $codigoElemento,
                'texto_fijo' => $textoFijo,
                'pagina' => (int) ($item['pagina'] ?? 1),
                'posicion_x' => (float) ($item['posicion_x'] ?? 0),
                'posicion_y' => (float) ($item['posicion_y'] ?? 0),
                'ancho' => (float) ($item['ancho'] ?? 180),
                'alto' => (float) ($item['alto'] ?? 30),
                'tamano_letra' => (int) ($item['tamano_letra'] ?? 12),
                'alineacion' => $this->normalizarAlineacionPlantilla($item['alineacion'] ?? null),
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

            if (Schema::hasColumn('plantillas_elementos', 'tipo_letra')) {
                $datosElemento['tipo_letra'] = $item['tipo_letra'] ?? 'Arial';
            }

            if (Schema::hasColumn('plantillas_elementos', 'padding_x')) {
                $datosElemento['padding_x'] = (float) ($item['padding_x'] ?? 7);
            }

            if (Schema::hasColumn('plantillas_elementos', 'padding_y')) {
                $datosElemento['padding_y'] = (float) ($item['padding_y'] ?? 5);
            }

            if (Schema::hasColumn('plantillas_elementos', 'interlineado')) {
                $datosElemento['interlineado'] = (float) ($item['interlineado'] ?? 1.25);
            }

            if (Schema::hasColumn('plantillas_elementos', 'z_index')) {
                $datosElemento['z_index'] = (int) ($item['z_index'] ?? ($orden + 3));
            }

            if (Schema::hasColumn('plantillas_elementos', 'configuracion')) {
                $datosElemento['configuracion'] = $this->normalizarConfiguracionElementoPlantilla(
                    is_array($item['configuracion'] ?? null) ? $item['configuracion'] : [],
                    $tipoElemento
                );
            }

            $elemento = PlantillaElemento::create($datosElemento);

            if ($tipoElemento === 'TABLA') {
                $this->guardarColumnasPlantilla($elemento, $item['columnas'] ?? [], $codigosPermitidos);
            }
        }
    }

    private function normalizarAlineacionPlantilla(?string $alineacion): string
    {
        $valor = strtoupper(trim((string) $alineacion));

        return in_array($valor, ['IZQUIERDA', 'CENTRO', 'DERECHA', 'JUSTIFICADO'], true)
            ? $valor
            : 'IZQUIERDA';
    }

    /**
     * Conserva únicamente propiedades visuales conocidas por el editor.
     * Esto evita guardar estilos arbitrarios enviados desde el navegador.
     */
    private function normalizarConfiguracionElementoPlantilla(array $configuracion, string $tipoElemento): array
    {
        $color = function ($valor, string $predeterminado, bool $permiteTransparente = false): string {
            $valor = strtolower(trim((string) $valor));

            if ($permiteTransparente && $valor === 'transparent') {
                return 'transparent';
            }

            return preg_match('/^#[0-9a-f]{6}$/i', $valor) ? $valor : $predeterminado;
        };

        $bordeEstilo = strtoupper((string) ($configuracion['borde_estilo'] ?? 'SOLIDO'));
        $forma = strtoupper((string) ($configuracion['forma'] ?? 'RECTANGULO'));
        $ajusteImagen = strtoupper((string) ($configuracion['ajuste_imagen'] ?? 'CONTENER'));

        $resultado = [
            'rotacion' => max(-360, min(360, (float) ($configuracion['rotacion'] ?? 0))),
            'opacidad' => max(0.05, min(1, (float) ($configuracion['opacidad'] ?? 1))),
            'fondo_color' => $color($configuracion['fondo_color'] ?? 'transparent', 'transparent', true),
            'borde_color' => $color($configuracion['borde_color'] ?? '#0f766e', '#0f766e'),
            'borde_ancho' => max(0, min(12, (float) ($configuracion['borde_ancho'] ?? 0))),
            'borde_estilo' => in_array($bordeEstilo, ['SOLIDO', 'PUNTEADO', 'DISCONTINUO', 'DOBLE', 'NINGUNO'], true)
                ? $bordeEstilo
                : 'SOLIDO',
            'radio_borde' => max(0, min(100, (float) ($configuracion['radio_borde'] ?? 0))),
            'bloqueado' => (bool) ($configuracion['bloqueado'] ?? false),
            // El nombre y la visibilidad pertenecen al panel de capas; se guardan dentro del JSON existente.
            'nombre_capa' => mb_substr(trim((string) ($configuracion['nombre_capa'] ?? '')), 0, 100),
            // El identificador permite conservar grupos de capas sin crear tablas adicionales.
            'grupo' => preg_replace('/[^a-zA-Z0-9_-]/', '', mb_substr((string) ($configuracion['grupo'] ?? ''), 0, 80)),
            'visible' => (bool) ($configuracion['visible'] ?? true),
            'forma' => in_array($forma, ['RECTANGULO', 'ELIPSE'], true) ? $forma : 'RECTANGULO',
            'ajuste_imagen' => in_array($ajusteImagen, ['CONTENER', 'CUBRIR', 'ESTIRAR'], true)
                ? $ajusteImagen
                : 'CONTENER',
        ];

        if ($tipoElemento === 'TEXTO') {
            $resultado['estilos_texto'] = $this->normalizarEstilosTextoPlantilla(
                is_array($configuracion['estilos_texto'] ?? null) ? $configuracion['estilos_texto'] : []
            );
        }

        if ($tipoElemento !== 'TABLA') {
            return $resultado;
        }

        $tabla = is_array($configuracion['tabla'] ?? null) ? $configuracion['tabla'] : [];
        $tipoTabla = strtoupper((string) ($tabla['tipo'] ?? 'AUTOMATICA'));
        $bordes = strtoupper((string) ($tabla['bordes'] ?? 'TODOS'));
        $estilo = strtoupper((string) ($tabla['borde_estilo'] ?? 'SOLIDO'));
        $alineacionVertical = strtoupper((string) ($tabla['alineacion_vertical'] ?? 'MEDIO'));

        $resultado['tabla'] = [
            'tipo' => $tipoTabla === 'CAMPO_VALOR' ? 'CAMPO_VALOR' : 'AUTOMATICA',
            'pares_por_fila' => (int) ($tabla['pares_por_fila'] ?? 1) === 2 ? 2 : 1,
            'mostrar_encabezado' => (bool) ($tabla['mostrar_encabezado'] ?? true),
            'etiquetas_negrita' => (bool) ($tabla['etiquetas_negrita'] ?? true),
            'ajustar_texto' => (bool) ($tabla['ajustar_texto'] ?? true),
            'tamano_minimo' => max(5, min(24, (float) ($tabla['tamano_minimo'] ?? 7))),
            'bordes' => in_array($bordes, ['TODOS', 'EXTERIOR', 'HORIZONTAL', 'VERTICAL', 'ENCABEZADO', 'NINGUNO'], true)
                ? $bordes
                : 'TODOS',
            'borde_color' => $color($tabla['borde_color'] ?? '#0f172a', '#0f172a'),
            'borde_ancho' => max(0, min(8, (float) ($tabla['borde_ancho'] ?? 1))),
            'borde_estilo' => in_array($estilo, ['SOLIDO', 'PUNTEADO', 'DISCONTINUO', 'DOBLE'], true)
                ? $estilo
                : 'SOLIDO',
            'encabezado_fondo' => $color($tabla['encabezado_fondo'] ?? '#f1f5f9', '#f1f5f9'),
            'encabezado_texto' => $color($tabla['encabezado_texto'] ?? '#0f172a', '#0f172a'),
            'celdas_fondo' => $tipoTabla === 'CAMPO_VALOR'
                ? 'transparent'
                : $color($tabla['celdas_fondo'] ?? '#ffffff', '#ffffff'),
            'filas_alternas' => (bool) ($tabla['filas_alternas'] ?? false),
            'filas_alternas_color' => $color($tabla['filas_alternas_color'] ?? '#f8fafc', '#f8fafc'),
            'padding_x' => max(0, min(30, (float) ($tabla['padding_x'] ?? 6))),
            'padding_y' => max(0, min(30, (float) ($tabla['padding_y'] ?? 4))),
            'alineacion_vertical' => in_array($alineacionVertical, ['SUPERIOR', 'MEDIO', 'INFERIOR'], true)
                ? $alineacionVertical
                : 'MEDIO',
        ];

        return $resultado;
    }

    /**
     * Normaliza los estilos por carácter generados por Fabric.js.
     * Solo admite negrita, cursiva y subrayado para evitar CSS arbitrario.
     */
    private function normalizarEstilosTextoPlantilla(array $estilos): array
    {
        $resultado = [];
        $cantidad = 0;

        foreach ($estilos as $indiceLinea => $caracteres) {
            if (!ctype_digit((string) $indiceLinea) || !is_array($caracteres)) {
                continue;
            }

            foreach ($caracteres as $indiceCaracter => $estilo) {
                if ($cantidad >= 5000 || !ctype_digit((string) $indiceCaracter) || !is_array($estilo)) {
                    continue;
                }

                $estiloSeguro = [];
                if (in_array($estilo['fontWeight'] ?? null, ['bold', 'normal'], true)) {
                    $estiloSeguro['fontWeight'] = $estilo['fontWeight'];
                }
                if (in_array($estilo['fontStyle'] ?? null, ['italic', 'normal'], true)) {
                    $estiloSeguro['fontStyle'] = $estilo['fontStyle'];
                }
                if (array_key_exists('underline', $estilo) && is_bool($estilo['underline'])) {
                    $estiloSeguro['underline'] = $estilo['underline'];
                }
                if ($estiloSeguro === []) {
                    continue;
                }

                $resultado[(string) $indiceLinea][(string) $indiceCaracter] = $estiloSeguro;
                $cantidad++;
            }
        }

        return $resultado;
    }

    private function guardarColumnasPlantilla(PlantillaElemento $elemento, array $columnas, array $codigosPermitidos): void
    {
        foreach ($columnas as $orden => $columna) {
            $codigoCampo = $columna['codigo_campo'] ?? null;
            $esColumnaManual = is_string($codigoCampo) && str_starts_with($codigoCampo, 'tabla.columna_');

            if (! $codigoCampo || (! in_array($codigoCampo, $codigosPermitidos, true) && ! $esColumnaManual)) {
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
            'form_descripcion' => ['nullable', 'string'],
            'form_estado' => ['required', Rule::in(array_keys(Certificado::ESTADOS_CERTIFICADO))],
            'requisitos_certificados' => ['nullable', 'array'],
            'requisitos_certificados.*.id_requisito' => ['required', 'exists:requisitos,id'],
            'requisitos_certificados.*.cumple' => ['nullable', 'in:SI,NO'],
            'requisitos_certificados.*.estado' => ['nullable', 'string', 'max:50'],
        ], [], [
            'form_id_tipo_certificado' => 'tipo de certificado',
            'form_id_persona_beneficiario' => 'persona beneficiaria',
            'form_id_persona_tramitador' => 'persona tramitadora',
            'form_estado' => 'estado',
        ]);
    }

    // VALIDACION DE EMISION
    // Estos datos se completan cuando el certificado ya esta listo para emitirse.
    private function validarEmisionCertificado(Request $solicitud, Certificado $certificado): array
    {
        return $solicitud->validateWithBag('emisionCertificado', [
            'form_fecha_inicio' => ['required', 'date'],
            'form_fecha_fin' => ['nullable', 'date', 'after_or_equal:form_fecha_inicio'],
            'form_vigencia_dias' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'form_vigencia_indefinida' => ['nullable', 'boolean'],
            'form_id_certificado_registro' => ['nullable', 'integer'],
            // Es obligatorio para que el PDF salga del mismo render Fabric.js mostrado al funcionario.
            'form_documento_renderizado' => ['required', 'string', 'max:50000000'],
            'bandeja' => ['nullable', 'string', 'max:30'],
        ], [], [
            'form_fecha_inicio' => 'fecha de inicio',
            'form_fecha_fin' => 'fecha final',
            'form_vigencia_dias' => 'días de vigencia',
            'form_documento_renderizado' => 'render final de la plantilla',
        ]);
    }

    private function resolverFechaFinEmision(Request $solicitud, array $datos): ?\Illuminate\Support\Carbon
    {
        if ($solicitud->boolean('form_vigencia_indefinida')) {
            return null;
        }

        $fechaInicio = \Illuminate\Support\Carbon::parse($datos['form_fecha_inicio']);
        $diasVigencia = $datos['form_vigencia_dias'] ?? null;

        if ($diasVigencia) {
            return $fechaInicio->copy()->addDays((int) $diasVigencia);
        }

        if (! empty($datos['form_fecha_fin'])) {
            return \Illuminate\Support\Carbon::parse($datos['form_fecha_fin']);
        }

        throw ValidationException::withMessages([
            'form_fecha_fin' => 'Ingrese una fecha final, días de vigencia o marque vigencia indefinida.',
        ])->errorBag('emisionCertificado');
    }

    private function generarDocumentoEmitido(
        Certificado $certificado,
        CertificadoEmitido $emision,
        PlantillaCertificado $plantillaCertificado,
        ?string $imagenRenderizada = null
    ): string
    {
        $documento = $this->datosDocumentoEmitido($certificado, $emision, $plantillaCertificado, $imagenRenderizada);
        $pdf = Pdf::loadView('certificados.emitir_certificado.documento_pdf', compact('documento'))
            ->setPaper($documento['papel'], $documento['orientacion']);

        return $this->expedientesTramites->guardarContenido(
            $certificado,
            $pdf->output(),
            ExpedienteTramiteService::CERTIFICADO_EMITIDO,
            'pdf',
            'certificado_'.preg_replace('/[^A-Za-z0-9_-]+/', '_', $emision->numero_certificado).'_'.now()->format('Ymd_His').'.pdf'
        );
    }

    private function datosDocumentoEmitido(
        Certificado $certificado,
        CertificadoEmitido $emision,
        PlantillaCertificado $plantillaCertificado,
        ?string $imagenRenderizada = null
    ): array
    {
        $medidas = $this->medidasLienzoPlantilla(
            $plantillaCertificado->tamano_papel,
            $plantillaCertificado->orientacion
        );
        $valores = $this->valoresPlantillaCertificado($certificado, $emision, $emision->certificadoRegistro);
        $valoresConAlias = array_merge(
            $valores,
            collect($valores)
                ->mapWithKeys(fn ($valor, $clave) => [str_replace('.', '_', $clave) => $valor])
                ->all()
        );

        return [
            'ancho' => (int) ($plantillaCertificado->ancho_lienzo_px ?: $medidas['ancho']),
            'alto' => (int) ($plantillaCertificado->alto_lienzo_px ?: $medidas['alto']),
            'papel' => strtoupper($plantillaCertificado->tamano_papel) === 'OFICIO' ? 'legal' : 'letter',
            'orientacion' => strtoupper($plantillaCertificado->orientacion) === 'HORIZONTAL' ? 'landscape' : 'portrait',
            'ajuste_fondo' => match (strtoupper((string) ($plantillaCertificado->ajuste_fondo ?? 'ESTIRAR'))) {
                'CONTENER' => 'contain',
                'CUBRIR' => 'cover',
                default => 'fill',
            },
            'imagen_renderizada' => $this->validarImagenRenderizada(
                $imagenRenderizada,
                (int) ($plantillaCertificado->ancho_lienzo_px ?: $medidas['ancho']),
                (int) ($plantillaCertificado->alto_lienzo_px ?: $medidas['alto'])
            ),
            'fondo' => $plantillaCertificado->imprimir_transparente
                ? null
                : $this->fondoPlantillaBase64($plantillaCertificado->url_fondo),
            'valores' => $valoresConAlias,
            'elementos' => $plantillaCertificado->elementosActivos
                ->filter(fn (PlantillaElemento $elemento) => (bool) data_get($elemento->configuracion, 'visible', true))
                ->map(fn (PlantillaElemento $elemento) => $this->elementoDocumentoEmitido($elemento, $valores, $valoresConAlias))
                ->values(),
        ];
    }

    private function validarImagenRenderizada(?string $imagen, int $anchoEsperado, int $altoEsperado): ?string
    {
        if (! is_string($imagen)
            || ! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/\r\n]+={0,2})$/', $imagen, $coincidencias)) {
            return null;
        }

        $contenido = base64_decode($coincidencias[1], true);
        if ($contenido === false || strlen($contenido) > 36_000_000) {
            return null;
        }

        $medidas = @getimagesizefromstring($contenido);
        if (! is_array($medidas) || ($medidas['mime'] ?? null) !== 'image/png') {
            return null;
        }

        $ancho = (int) ($medidas[0] ?? 0);
        $alto = (int) ($medidas[1] ?? 0);
        $proporcionEsperada = $altoEsperado > 0 ? $anchoEsperado / $altoEsperado : 0;
        $proporcionImagen = $alto > 0 ? $ancho / $alto : 0;

        if ($ancho < 100 || $alto < 100 || $ancho * $alto > 20_000_000
            || abs($proporcionImagen - $proporcionEsperada) > 0.01) {
            return null;
        }

        return $imagen;
    }

    private function elementoDocumentoEmitido(
        PlantillaElemento $elemento,
        array $valores,
        array $valoresConAlias
    ): array
    {
        $tipoElemento = $elemento->tipo_elemento;
        $valor = match ($tipoElemento) {
            'TEXTO' => $this->resolverMarcadoresDocumento((string) $elemento->texto_fijo, $valoresConAlias),
            'IMAGEN' => $this->fondoPlantillaBase64($elemento->texto_fijo),
            'QR' => $elemento->texto_fijo ?: ($valores[$elemento->codigo_elemento] ?? ''),
            'TABLA', 'FORMA', 'LINEA' => null,
            default => $valores[$elemento->codigo_elemento] ?? '',
        };
        return [
            'elemento' => $elemento,
            'valor' => $valor,
            'filas' => $tipoElemento === 'TABLA'
                ? $this->filasTablaDocumento($elemento, $valoresConAlias)
                : [],
        ];
    }

    private function resolverMarcadoresDocumento(string $texto, array $valores): string
    {
        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($coincidencia) use ($valores) {
            $codigo = trim($coincidencia[1]);

            return $valores[$codigo] ?? $coincidencia[0];
        }, $texto);
    }

    private function filasTablaDocumento(PlantillaElemento $elemento, array $valores): array
    {
        $contenido = json_decode((string) $elemento->texto_fijo, true);
        $filas = is_array($contenido['filas'] ?? null) ? $contenido['filas'] : [];

        if (! $filas) {
            $filas = [[...$elemento->columnas
                ->map(fn ($columna) => '{{'.str_replace('.', '_', $columna->codigo_campo).'}}')
                ->all()]];
        }

        return collect($filas)
            ->map(fn ($fila) => collect($fila)
                ->map(fn ($celda) => $this->resolverMarcadoresDocumento((string) $celda, $valores))
                ->values()
                ->all())
            ->values()
            ->all();
    }

    private function fondoPlantillaBase64(?string $ruta): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        // Las imágenes insertadas desde el editor ya llegan como Data URL.
        if (str_starts_with((string) $ruta, 'data:image/')) {
            return (string) $ruta;
        }

        $rutaLimpia = ltrim((string) $ruta, '/');
        $rutaRelativa = str_starts_with($rutaLimpia, 'storage/')
            ? substr($rutaLimpia, strlen('storage/'))
            : $rutaLimpia;
        $rutaArchivo = $this->archivosPrivados->rutaAbsoluta($rutaRelativa);

        if (! $rutaArchivo || ! File::exists($rutaArchivo) || ! in_array(strtolower(File::extension($rutaArchivo)), ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $tipo = File::mimeType($rutaArchivo) ?: 'image/png';

        return 'data:'.$tipo.';base64,'.base64_encode(File::get($rutaArchivo));
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

        return 'Persona #'.$persona->id;
    }

    private function nombreFuncionarioUsuario(?User $usuario): string
    {
        $usuario?->loadMissing('funcionario');

        $funcionario = $usuario?->funcionario;
        if (! $funcionario) {
            return 'Sin funcionario';
        }

        $nombre = trim(implode(' ', array_filter([
            $funcionario->nombres,
            $funcionario->apellido_paterno,
            $funcionario->apellido_materno,
        ])));

        return $nombre !== '' ? $nombre : 'Sin funcionario';
    }

    private function cargoFuncionarioUsuario(?User $usuario): string
    {
        $usuario?->loadMissing('funcionario.cargos');

        $cargo = $usuario?->funcionario?->cargos?->pluck('nombre')->filter()->implode(', ');

        return $cargo ?: 'Sin cargo';
    }

    // FUNCIONARIO HABILITADO PARA ATENDER TRAMITES
    // Permite derivar/revisar a cualquier usuario interno con ficha de funcionario y cargo activo.
    private function usuarioTieneCargoActivo(?User $usuario): bool
    {
        return (string) $usuario?->estado === '1'
            && (bool) $usuario?->funcionario
            && $usuario->funcionario->cargos->contains(fn ($cargo) => (string) $cargo->estado === '1');
    }

    // GUARDA PDF DEL CERTIFICADO
    // El documento queda privado; la ruta de descarga valida usuario y límite.
    private function guardarDocumentoCertificado(Request $solicitud, Certificado $certificado): ?string
    {
        if (! $solicitud->hasFile('form_url_documento')) {
            return null;
        }

        return $this->expedientesTramites->guardarArchivoSubido(
            $certificado,
            $solicitud->file('form_url_documento'),
            ExpedienteTramiteService::CERTIFICADO_ADJUNTO
        );
    }

    // GUARDA REQUISITOS DEL CERTIFICADO
    // Cada fila representa el cumplimiento de un requisito para este certificado.
    private function guardarRequisitosCertificado(Certificado $certificado, array $requisitos): void
    {
        $procesados = [];

        foreach ($requisitos as $item) {
            $idRequisito = $item['id_requisito'] ?? null;

            if (! $idRequisito || in_array((int) $idRequisito, $procesados, true)) {
                continue;
            }

            RequisitoCertificado::create([
                'id_certificado' => $certificado->id,
                'id_requisito' => $idRequisito,
                'orden' => count($procesados) + 1,
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
            'beneficiario.natural.ocupacionCob',
            'beneficiario.empresa.tipoEmpresa',
            'beneficiario.empresa.representanteLegalActivo.persona.natural',
            'beneficiario.territorio',
            'beneficiario.usuario',
            'beneficiario.telefonos',
            'tramitador.natural.ocupacionCob',
            'tramitador.empresa.tipoEmpresa',
            'tramitador.territorio',
            'tramitador.usuario',
            'tramitador.telefonos',
            'tramitador.responsabilidadesEmpresariales.rol',
            'certificadoRequisitos.requisito',
            'certificadoRequisitos.evidenciasRequisitos.tipoEvidencia',
            'certificadoRequisitos.revisionesRequisitos.usuarioRevisor.funcionario.cargos',
            'certificadoRequisitos.revisionesRequisitos.observacionesRequisitos',
            'registros.producto.clasificacionToxicologica',
            'registros.producto.fabricante',
            'registros.producto.clasificacionQuimica',
            'registros.producto.territorio',
            'registros.producto.ingredientesProductos.ingrediente',
            'registros.producto.importadorPersona.natural',
            'registros.producto.importadorPersona.empresa.tipoEmpresa',
            'registros.producto.importadorPersona.empresa.representanteLegalActivo.persona.natural',
            'registros.producto.importadorPersona.territorio',
            'registros.producto.importadorPersona.telefonos',
            'registros.presentacion.catalogoUnidad',
            'registros.catalogoUnidad',
            'certificadosRegistros.registro.producto.clasificacionToxicologica',
            'certificadosRegistros.registro.producto.fabricante',
            'certificadosRegistros.registro.producto.clasificacionQuimica',
            'certificadosRegistros.registro.producto.territorio',
            'certificadosRegistros.registro.producto.ingredientesProductos.ingrediente',
            'certificadosRegistros.registro.producto.importadorPersona.natural',
            'certificadosRegistros.registro.producto.importadorPersona.empresa.tipoEmpresa',
            'certificadosRegistros.registro.producto.importadorPersona.empresa.representanteLegalActivo.persona.natural',
            'certificadosRegistros.registro.producto.importadorPersona.territorio',
            'certificadosRegistros.registro.producto.importadorPersona.telefonos',
            'certificadosRegistros.registro.presentacion.catalogoUnidad',
            'certificadosRegistros.registro.catalogoUnidad',
            'certificadosRegistros.emision',
            'emisiones.certificadoRegistro.registro.producto',
            'pagos.procedencia',
            'pagos.funcionarioUsuario.funcionario.cargos',
            'seguimientos.usuarioOrigen.funcionario.cargos',
            'seguimientos.usuarioSiguiente.funcionario.cargos',
        ];
    }

    // PLANTILLA DE EMISION
    // Usa la plantilla activa del tipo de certificado; si aun no existe, usa el modelo base cargado en el sistema.
    private function plantillaActivaParaEmision(Certificado $certificado): ?PlantillaCertificado
    {
        if (! Schema::hasTable('plantillas_certificados') || ! Schema::hasTable('plantillas_elementos')) {
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
    // La clave debe coincidir con el codigo_elemento guardado en plantillas_elementos.
    private function valoresPlantillaCertificado(
        Certificado $certificado,
        ?CertificadoEmitido $emision = null,
        ?CertificadoRegistro $certificadoRegistro = null
    ): array
    {
        $beneficiario = $certificado->beneficiario;
        $tramitador = $certificado->tramitador;
        // En emisión por lote, la plantilla solo usa el registro que originará este documento.
        $registros = $certificadoRegistro?->registro
            ? collect([$certificadoRegistro->registro])
            : $certificado->registros;
        $primerRegistro = $registros->first();
        $primerPago = $certificado->pagos->first();
        $ultimoSeguimiento = $certificado->seguimientos->sortByDesc('id')->first();

        $productos = $registros->map(fn ($registro) => $registro->producto)->filter()->unique('id')->values();
        $presentaciones = $registros->map(fn ($registro) => $registro->presentacion)->filter()->unique('id')->values();
        $primerProducto = $productos->first();
        $importadorProducto = $primerProducto?->importadorPersona;
        $empresaImportadora = $importadorProducto?->empresa;
        $representanteImportador = $empresaImportadora?->representanteLegalActivo?->persona;
        $ubicacionImportador = collect([
            $importadorProducto?->territorio?->nombre,
            $importadorProducto?->domicilio,
        ])->filter()->implode(', ');
        $empresaBeneficiaria = $beneficiario?->empresa;
        $representanteLegal = $empresaBeneficiaria?->representanteLegalActivo;
        $personaRepresentanteLegal = $representanteLegal?->persona;
        $relacionTramitador = collect($tramitador?->responsabilidadesEmpresariales ?? [])
            ->filter(fn ($responsabilidad) => ! $empresaBeneficiaria
                || (int) $responsabilidad->id_empresa === (int) $empresaBeneficiaria->id)
            ->filter(fn ($responsabilidad) => in_array(strtoupper((string) $responsabilidad->estado), ['1', 'ACTIVO'], true))
            ->sortByDesc('id')
            ->first();
        $ingredientesProducto = collect($primerProducto?->ingredientesProductos ?? [])
            ->filter(fn ($relacion) => in_array(strtoupper((string) $relacion->estado), ['1', 'ACTIVO'], true))
            ->filter(fn ($relacion) => $relacion->ingrediente)
            ->values();
        $primerIngredienteProducto = $ingredientesProducto->first();
        $primerRequisito = $certificado->certificadoRequisitos->sortBy('orden')->first();
        $primeraEvidencia = $primerRequisito?->evidenciasRequisitos?->first();
        $ultimaRevision = $certificado->certificadoRequisitos
            ->flatMap(fn ($requisito) => $requisito->revisionesRequisitos)
            ->sortByDesc('id')
            ->first();
        $ultimaObservacion = $ultimaRevision?->observacionesRequisitos?->sortByDesc('id')->first();
        $usuarioRevisor = $ultimaRevision?->usuarioRevisor;
        $usuarioDestino = $ultimoSeguimiento?->usuarioSiguiente;
        $usuarioOrigen = $ultimoSeguimiento?->usuarioOrigen;
        $usuarioValidadorPago = $primerPago?->funcionarioUsuario;
        $usuarioFirmaArea = $usuarioDestino ?: $usuarioRevisor ?: $usuarioOrigen;
        $usuarioDirector = $this->usuarioDirectorPlantilla();
        $usuarioEmisor = $emision?->id_usuario_registro
            ? User::withTrashed()->with('funcionario.cargos')->find($emision->id_usuario_registro)
            : auth()->user();
        $presentacionesCompletas = $presentaciones->map(function ($presentacion) {
            $tipo = trim((string) $presentacion->tipo_presentacion);
            $cantidad = $this->formatearDecimalPlantilla($presentacion->cantidad);
            $unidad = $this->textoCatalogoUnidad($presentacion->catalogoUnidad);

            return trim(implode(' ', array_filter([
                $tipo !== '' ? $tipo : 'Presentación',
                $cantidad !== '-' ? 'de '.$cantidad : null,
                $unidad !== 'Sin unidad' ? $unidad : null,
            ])));
        });
        $ingredientesConPorcentaje = $ingredientesProducto->map(function ($relacion) {
            $nombre = trim((string) $relacion->ingrediente?->nombre);
            $porcentaje = $this->formatearDecimalPlantilla($relacion->porcentaje);

            return trim($nombre.($porcentaje !== '-' ? ' '.$porcentaje.'%' : ''));
        });
        $resumenProductos = $registros->map(function ($registro) {
            $producto = $registro->producto;
            $presentacion = $registro->presentacion;

            return collect([
                $producto?->codigo,
                $producto?->nombre_comercial,
                $registro->codigo_autorizacion,
                $presentacion ? $this->formatearDecimalPlantilla($presentacion->cantidad).' '.$this->textoCatalogoUnidad($presentacion->catalogoUnidad) : null,
            ])->filter()->implode(' | ');
        });
        $resumenRequisitos = $certificado->certificadoRequisitos->map(function ($requisito) {
            $revision = $requisito->revisionesRequisitos->sortByDesc('id')->first();

            return collect([
                $requisito->requisito?->descripcion,
                $requisito->cumple,
                $revision?->estado,
            ])->filter()->implode(' | ');
        });
        $resumenSeguimientos = $certificado->seguimientos->sortBy('id')->map(function ($seguimiento) {
            return collect([
                $seguimiento->fecha_inicio?->format('d/m/Y H:i'),
                $seguimiento->referencia,
                $seguimiento->estado,
            ])->filter()->implode(' | ');
        });
        $resumenCalificaciones = $certificado->resumenEvaluacionCalificaciones();
        $resultadoEvaluacion = match ($resumenCalificaciones['resultado']) {
            'APROBADO' => 'APROBADO',
            'PARTICIPACION' => 'PARTICIPADO',
            'PENDIENTE' => 'Pendiente',
            default => 'No aplica',
        };
        $calificacionesObtenidas = $resumenCalificaciones['detalles']->pluck('calificacion_obtenida')->filter(fn ($calificacion) => $calificacion !== null);
        $calificacionesMinimas = $resumenCalificaciones['detalles']->pluck('calificacion_minima')->filter(fn ($calificacion) => $calificacion !== null);

        return [
            'certificado.codigo' => $emision?->numero_certificado ?: ($certificado->codigo ?: 'Sin código'),
            'certificado.fecha_inicio' => $emision?->fecha_inicio?->format('d/m/Y') ?: ($certificado->fecha_inicio?->format('d/m/Y') ?: 'Sin fecha'),
            'certificado.fecha_fin' => $emision?->fecha_fin?->format('d/m/Y') ?: ($certificado->fecha_fin?->format('d/m/Y') ?: 'Sin fecha'),
            'emision.numero_certificado' => $emision?->numero_certificado ?: 'Se asignará al emitir',
            'emision.fecha' => $emision?->fecha_emision?->format('d/m/Y') ?: now()->format('d/m/Y'),
            'certificado.descripcion' => $certificado->descripcion ?: 'Sin descripción',
            'certificado.estado' => Certificado::textoEstadoCertificado($certificado->estado),
            'evaluacion.resultado' => $resultadoEvaluacion,
            'evaluacion.calificaciones' => $calificacionesObtenidas->isEmpty() ? 'Sin calificación' : $calificacionesObtenidas->implode(', '),
            'evaluacion.calificaciones_minimas' => $calificacionesMinimas->isEmpty() ? 'Sin calificación mínima' : $calificacionesMinimas->implode(', '),
            'tipo_certificado.nombre' => $certificado->tipoCertificado?->nombre ?: 'Sin tipo',
            'area.nombre' => $certificado->tipoCertificado?->area?->nombre ?: 'Sin área',

            'beneficiario.nombre' => $beneficiario ? $this->nombrePersona($beneficiario) : 'Sin beneficiario',
            'beneficiario.documento' => $this->documentoPersona($beneficiario),
            'beneficiario.correo' => $beneficiario?->correo ?: 'Sin correo',
            'beneficiario.domicilio' => $beneficiario?->domicilio ?: 'Sin domicilio',
            'beneficiario.telefono' => $beneficiario?->telefonos?->first()?->numero ?: 'Sin teléfono',
            'beneficiario.territorio' => $beneficiario?->territorio?->nombre ?: 'Sin territorio',
            'beneficiario.tipo_persona' => $beneficiario?->empresa ? 'Empresa' : 'Persona natural',
            'empresa.razon_social' => $empresaBeneficiaria?->razon_social ?: 'Sin razón social',
            'empresa.matricula' => $empresaBeneficiaria?->matricula ?: 'Sin matrícula',
            'empresa.tipo_empresa' => $empresaBeneficiaria?->tipoEmpresa?->descripcion ?: 'Sin tipo de empresa',
            'empresa.representante_legal_nombre' => $personaRepresentanteLegal
                ? $this->nombrePersona($personaRepresentanteLegal)
                : 'Sin representante legal',
            'empresa.representante_legal_documento' => $this->documentoPersona($personaRepresentanteLegal),
            'natural.nombres' => $beneficiario?->natural?->nombres ?: 'Sin nombres',
            'natural.apellido_paterno' => $beneficiario?->natural?->apellido_paterno ?: 'Sin apellido paterno',
            'natural.apellido_materno' => $beneficiario?->natural?->apellido_materno ?: 'Sin apellido materno',
            'natural.ocupacion' => $beneficiario?->natural?->ocupacionCob?->descripcion_ocupacion ?: 'Sin ocupación',

            'tramitador.nombre' => $tramitador ? $this->nombrePersona($tramitador) : 'Sin tramitador',
            'tramitador.documento' => $this->documentoPersona($tramitador),
            'tramitador.correo' => $tramitador?->correo ?: 'Sin correo',
            'tramitador.telefono' => $tramitador?->telefonos?->first()?->numero ?: 'Sin teléfono',
            'tramitador.rol' => $relacionTramitador?->rol?->name ?: 'Sin rol empresarial',
            'tramitador.respaldo' => $relacionTramitador?->url_respaldo ?: 'Sin respaldo',

            'importador.nombre' => $importadorProducto
                ? $this->nombrePersona($importadorProducto)
                : 'Sin importador',
            'importador.documento' => $this->documentoPersona($importadorProducto),
            'importador.tipo_persona' => $empresaImportadora ? 'Empresa' : ($importadorProducto ? 'Persona natural' : 'Sin importador'),
            'importador.domicilio' => $importadorProducto?->domicilio ?: 'Sin domicilio',
            'importador.territorio' => $importadorProducto?->territorio?->nombre ?: 'Sin territorio',
            'importador.ubicacion' => $ubicacionImportador !== '' ? $ubicacionImportador : 'Sin ubicación',
            'importador.correo' => $importadorProducto?->correo ?: 'Sin correo',
            'importador.telefono' => $importadorProducto?->telefonos?->first()?->numero ?: 'Sin teléfono',
            'importador.representante_legal_nombre' => $representanteImportador
                ? $this->nombrePersona($representanteImportador)
                : 'Sin representante legal',
            'importador.representante_legal_documento' => $this->documentoPersona($representanteImportador),

            'producto.tabla' => $resumenProductos->filter()->implode("\n") ?: 'Sin productos',
            'producto.nombres_comerciales' => $this->unirValoresPlantilla($productos->pluck('nombre_comercial')),
            'producto.codigos' => $this->unirValoresPlantilla($productos->pluck('codigo')),
            'producto.nombres_tecnicos' => $this->unirValoresPlantilla($productos->pluck('nombre_tecnico')),
            'producto.clasificaciones_quimicas' => $this->unirValoresPlantilla($productos->map(fn ($producto) => $producto?->clasificacionQuimica?->nombre)),
            'fabricante.nombres' => $this->unirValoresPlantilla($productos->map(fn ($producto) => $producto?->fabricante?->nombre)),
            'clasificacion_toxicologica.codigos' => $this->unirValoresPlantilla($productos->map(fn ($producto) => $producto?->clasificacionToxicologica?->codigo)),
            'producto.estados' => $this->unirValoresPlantilla($productos->pluck('estado')),
            'producto.codigo' => $primerRegistro?->producto?->codigo ?: 'Sin producto',
            'producto.nombre_comercial' => $primerRegistro?->producto?->nombre_comercial ?: 'Sin producto',
            'producto.nombre_tecnico' => $primerRegistro?->producto?->nombre_tecnico ?: 'Sin dato',
            'producto.tipo_producto' => $primerProducto?->tipo_producto ?: 'Sin tipo de producto',
            'producto.pais_origen' => $primerProducto?->territorio?->nombre ?: 'Sin país de origen',
            'producto.clasificacion_quimica' => $primerRegistro?->producto?->clasificacionQuimica?->nombre ?: 'Sin clasificación química',
            'producto.clasificacion_quimica_descripcion' => $primerProducto?->clasificacionQuimica?->descripcion ?: 'Sin descripción química',
            'fabricante.nombre' => $primerProducto?->fabricante?->nombre
                ?: ($primerProducto?->fabricante?->razon_social ?: 'Sin fabricante'),
            'fabricante.razon_social' => $primerProducto?->fabricante?->razon_social
                ?: ($primerProducto?->fabricante?->nombre ?: 'Sin fabricante'),
            'fabricante.descripcion' => $primerProducto?->fabricante?->descripcion ?: 'Sin descripción del fabricante',
            'clasificacion_toxicologica.codigo' => $primerProducto?->clasificacionToxicologica?->codigo ?: 'Sin clasificación toxicológica',
            'clasificacion_toxicologica.descripcion' => $primerProducto?->clasificacionToxicologica?->descripcion ?: 'Sin descripción toxicológica',
            'clasificacion_toxicologica.completa' => trim(implode(' - ', array_filter([
                $primerProducto?->clasificacionToxicologica?->codigo,
                $primerProducto?->clasificacionToxicologica?->descripcion,
            ]))) ?: 'Sin clasificación toxicológica',
            'producto.estado' => $primerRegistro?->producto?->estado ?: 'Sin estado',

            'registro.codigos' => $this->unirValoresPlantilla($registros->pluck('codigo_autorizacion')),
            'registro.lotes' => $this->unirValoresPlantilla($registros->pluck('numero_lote')),
            'registro.fechas_vigencia' => $this->unirValoresPlantilla($registros->map(fn ($registro) => $registro->fecha_vigencia ? \Illuminate\Support\Carbon::parse($registro->fecha_vigencia)->format('d/m/Y') : null)),
            'registro.cantidades' => $this->unirValoresPlantilla($registros->map(fn ($registro) => $this->formatearDecimalPlantilla($registro->cantidad))),
            'registro.unidades' => $this->unirValoresPlantilla($registros->map(fn ($registro) => $this->textoCatalogoUnidad($registro->catalogoUnidad))),
            'presentacion.descripciones' => $this->unirValoresPlantilla($presentaciones->pluck('descripcion')),
            'presentacion.lista' => $this->unirValoresPlantilla($presentacionesCompletas),
            'presentacion.cantidades' => $this->unirValoresPlantilla($presentaciones->map(fn ($presentacion) => $this->formatearDecimalPlantilla($presentacion->cantidad))),
            'presentacion.unidades' => $this->unirValoresPlantilla($presentaciones->map(fn ($presentacion) => $this->textoCatalogoUnidad($presentacion?->catalogoUnidad))),
            'registro.codigo' => $primerRegistro?->codigo_autorizacion ?: 'Sin registro',
            'registro.numero_lote' => $primerRegistro?->numero_lote ?: 'Sin lote',
            'registro.fecha_vigencia' => $primerRegistro?->fecha_vigencia ? \Illuminate\Support\Carbon::parse($primerRegistro->fecha_vigencia)->format('d/m/Y') : 'Sin fecha',
            'registro.cantidad' => $primerRegistro?->cantidad ?: 'Sin cantidad',
            'registro.unidad' => $this->textoCatalogoUnidad($primerRegistro?->catalogoUnidad),
            'registro.estado' => $primerRegistro?->estado ?: 'Sin estado',
            'presentacion.descripcion' => $primerRegistro?->presentacion?->descripcion ?: 'Sin presentación',
            'presentacion.tipo_presentacion' => $primerRegistro?->presentacion?->tipo_presentacion ?: 'Sin tipo de presentación',
            'presentacion.cantidad' => $this->formatearDecimalPlantilla($primerRegistro?->presentacion?->cantidad),
            'presentacion.unidad' => $this->textoCatalogoUnidad($primerRegistro?->presentacion?->catalogoUnidad),
            'presentacion.url_etiqueta' => $primerRegistro?->presentacion?->url_etiqueta ?: 'Sin etiqueta',
            'presentacion.estado' => $primerRegistro?->presentacion?->estado ?: 'Sin estado',

            'ingrediente.tabla' => $ingredientesProducto->map(function ($relacion) {
                return collect([
                    $relacion->ingrediente?->nombre,
                    $relacion->ingrediente?->composicion,
                    $this->formatearDecimalPlantilla($relacion->porcentaje).'%',
                ])->filter()->implode(' | ');
            })->filter()->implode("\n") ?: 'Sin ingredientes',
            'ingrediente.nombre' => $primerIngredienteProducto?->ingrediente?->nombre ?: 'Sin ingrediente',
            'ingrediente.nombres' => $this->unirValoresPlantilla($ingredientesProducto->map(fn ($relacion) => $relacion->ingrediente?->nombre)),
            'ingrediente.composicion' => $primerIngredienteProducto?->ingrediente?->composicion ?: 'Sin composición',
            'ingrediente.composiciones' => $this->unirValoresPlantilla($ingredientesProducto->map(fn ($relacion) => $relacion->ingrediente?->composicion)),
            'ingrediente.riesgo_salud' => $primerIngredienteProducto?->ingrediente?->riesgo_salud ?: 'Sin riesgo registrado',
            'ingrediente.riesgos_salud' => $this->unirValoresPlantilla($ingredientesProducto->map(fn ($relacion) => $relacion->ingrediente?->riesgo_salud)),
            'ingrediente.porcentaje' => $primerIngredienteProducto
                ? $this->formatearDecimalPlantilla($primerIngredienteProducto->porcentaje).' %'
                : 'Sin porcentaje',
            'ingrediente.porcentajes' => $this->unirValoresPlantilla($ingredientesProducto->map(fn ($relacion) => $this->formatearDecimalPlantilla($relacion->porcentaje).' %')),
            'ingrediente.resumen' => $this->unirValoresPlantilla($ingredientesConPorcentaje),

            'pago.tipo_pago' => $primerPago?->tipo_pago ?: 'Sin pago',
            'pago.fecha' => $primerPago?->fecha ? \Illuminate\Support\Carbon::parse($primerPago->fecha)->format('d/m/Y') : 'Sin fecha',
            'pago.monto' => $primerPago?->monto ? number_format((float) $primerPago->monto, 2, ',', '.') : 'Sin monto',
            'pago.factura' => $primerPago?->factura ?: 'Sin factura',
            'pago.procedencia' => $primerPago?->procedencia?->descripcion ?: 'Sin procedencia',
            'pago.resumen' => $primerPago
                ? collect([
                    $primerPago->tipo_pago,
                    $primerPago->fecha ? \Illuminate\Support\Carbon::parse($primerPago->fecha)->format('d/m/Y') : null,
                    $primerPago->monto !== null ? number_format((float) $primerPago->monto, 2, ',', '.') : null,
                    $primerPago->factura,
                ])->filter()->implode(' | ')
                : 'Sin pago',
            'pago.comprobante' => $primerPago?->comprobante
                ? basename(str_replace('\\', '/', $primerPago->comprobante))
                : 'Sin comprobante',
            'pago.fecha_validacion' => $primerPago?->fecha_validacion?->format('d/m/Y') ?: 'Sin validación',
            'pago.funcionario_validador' => $this->nombreUsuarioPlantilla($usuarioValidadorPago),

            'requisito.tabla' => $resumenRequisitos->filter()->implode("\n") ?: 'Sin requisitos',
            'requisito.descripcion' => $primerRequisito?->requisito?->descripcion ?: 'Sin requisito',
            'requisito.tipo_evidencia' => $primeraEvidencia?->tipoEvidencia?->nombre ?: 'Sin tipo de evidencia',
            'requisito.cumple' => $primerRequisito?->cumple ?: 'Sin evaluación',
            'requisito.estado_revision' => $ultimaRevision?->estado ?: 'Sin revisión',
            'requisito.observacion' => $ultimaObservacion?->observacion ?: 'Sin observación',
            'evidencia.valor' => $primeraEvidencia?->valor ?: 'Sin evidencia',
            'evidencia.estado' => $primeraEvidencia?->estado ?: 'Sin estado',

            'seguimiento.tabla' => $resumenSeguimientos->filter()->implode("\n") ?: 'Sin seguimientos',
            'seguimiento.fecha_inicio' => $ultimoSeguimiento?->fecha_inicio ? \Illuminate\Support\Carbon::parse($ultimoSeguimiento->fecha_inicio)->format('d/m/Y H:i') : 'Sin fecha',
            'seguimiento.fecha_derivacion' => $ultimoSeguimiento?->fecha_derivacion ? \Illuminate\Support\Carbon::parse($ultimoSeguimiento->fecha_derivacion)->format('d/m/Y H:i') : 'Sin fecha',
            'seguimiento.referencia' => $ultimoSeguimiento?->referencia ?: 'Sin referencia',
            'seguimiento.descripcion_final' => $ultimoSeguimiento?->descripcion_final ?: 'Sin descripción',
            'seguimiento.estado' => $ultimoSeguimiento?->estado ?: 'Sin estado',
            'seguimiento.usuario_origen' => $this->nombreUsuarioPlantilla($usuarioOrigen),
            'seguimiento.usuario_siguiente' => $this->nombreUsuarioPlantilla($usuarioDestino),
            'revision.usuario' => $this->nombreUsuarioPlantilla($usuarioRevisor),
            'revision.resultado' => $ultimaRevision?->resultado_cumple ?: 'Sin resultado',
            'revision.observacion' => $ultimaObservacion?->observacion ?: 'Sin observación',

            'funcionario.emisor_nombre' => $this->nombreUsuarioPlantilla($usuarioEmisor),
            'funcionario.emisor_cargo' => $this->cargoFuncionarioUsuario($usuarioEmisor),
            'funcionario.revisor_nombre' => $this->nombreFuncionarioUsuario($usuarioRevisor),
            'funcionario.revisor_cargo' => $this->cargoFuncionarioUsuario($usuarioRevisor),
            'funcionario.destino_nombre' => $this->nombreFuncionarioUsuario($usuarioDestino),
            'funcionario.destino_cargo' => $this->cargoFuncionarioUsuario($usuarioDestino),
            'funcionario.origen_nombre' => $this->nombreFuncionarioUsuario($usuarioOrigen),
            'funcionario.origen_cargo' => $this->cargoFuncionarioUsuario($usuarioOrigen),
            'funcionario.validador_pago_nombre' => $this->nombreFuncionarioUsuario($usuarioValidadorPago),
            'funcionario.validador_pago_cargo' => $this->cargoFuncionarioUsuario($usuarioValidadorPago),
            'funcionario.director_nombre' => $this->nombreFuncionarioUsuario($usuarioDirector),
            'funcionario.director_cargo' => $this->cargoFuncionarioUsuario($usuarioDirector),
            'funcionario.responsable_area_nombre' => $this->nombreFuncionarioUsuario($usuarioFirmaArea),
            'funcionario.responsable_area_cargo' => $this->cargoFuncionarioUsuario($usuarioFirmaArea),
            'firma.director' => $this->textoFirmaPlantilla($usuarioDirector),
            'firma.responsable_area' => $this->textoFirmaPlantilla($usuarioFirmaArea),
        ];
    }

    /**
     * Resuelve la fila que corresponde al documento actual. Un trámite único no
     * lleva fila; un trámite por lote exige una fila activa del mismo trámite.
     */
    private function registroSeleccionadoParaEmision(Certificado $certificado, ?int $idCertificadoRegistro): ?CertificadoRegistro
    {
        $porLote = $certificado->tipoCertificado?->modo_emision === 'POR_LOTE';

        if (! $porLote) {
            return null;
        }

        $registros = $certificado->certificadosRegistros
            ->filter(fn (CertificadoRegistro $fila) => $fila->registro && filled($fila->registro->numero_lote));
        $registro = $idCertificadoRegistro
            ? $registros->firstWhere('id', $idCertificadoRegistro)
            : ($registros->first(fn (CertificadoRegistro $fila) => ! $fila->emision) ?: $registros->first());

        if (! $registro || ! $registro->registro || blank($registro->registro->numero_lote)) {
            throw ValidationException::withMessages([
                'form_id_certificado_registro' => 'Seleccione un lote válido del trámite antes de emitir.',
            ])->errorBag('emisionCertificado');
        }

        return $registro;
    }

    // Un trámite por lote termina de emitirse cuando cada fila vinculada tiene su documento.
    private function tramiteCompletoParaEmision(Certificado $certificado): bool
    {
        if ($certificado->tipoCertificado?->modo_emision !== 'POR_LOTE') {
            return $certificado->emisiones()->exists();
        }

        $lotes = $certificado->certificadosRegistros()
            ->whereHas('registro', fn ($query) => $query->whereNotNull('numero_lote')->where('numero_lote', '!=', ''))
            ->count();

        return $lotes > 0 && $certificado->emisiones()
            ->whereNotNull('id_certificado_registro')
            ->count() >= $lotes;
    }

    // Reserva el correlativo dentro de la misma transacción para evitar números duplicados.
    private function siguienteNumeroCertificado(TipoCertificado $tipoCertificado): string
    {
        $tipoBloqueado = TipoCertificado::query()->lockForUpdate()->findOrFail($tipoCertificado->id);
        $correlativo = max(1, (int) ($tipoBloqueado->siguiente_correlativo ?? 1));
        $prefijo = trim((string) ($tipoBloqueado->prefijo_numero_certificado ?? ''));

        $tipoBloqueado->update(['siguiente_correlativo' => $correlativo + 1]);

        return $prefijo === '' ? (string) $correlativo : $prefijo.$correlativo;
    }

    private function usuarioDirectorPlantilla(): ?User
    {
        return User::query()
            ->with('funcionario.cargos')
            ->whereHas('funcionario.cargos', function ($consulta) {
                $consulta->where('estado', 1)
                    ->where('nombre', 'like', '%DIRECTOR%');
            })
            ->first();
    }

    private function textoFirmaPlantilla(?User $usuario): string
    {
        return trim($this->nombreFuncionarioUsuario($usuario)."\n".$this->cargoFuncionarioUsuario($usuario));
    }

    private function unirValoresPlantilla($valores): string
    {
        $texto = collect($valores)
            ->filter(fn ($valor) => filled($valor) && $valor !== 'Sin unidad')
            ->unique()
            ->values()
            ->implode(', ');

        return $texto !== '' ? $texto : 'Sin dato';
    }

    private function formatearDecimalPlantilla(mixed $valor): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '-';
        }

        if (str_contains($texto, '.')) {
            $texto = rtrim(rtrim($texto, '0'), '.');
        }

        return $texto === '-0' ? '0' : $texto;
    }

    private function nombreUsuarioPlantilla(?User $usuario): string
    {
        if (! $usuario) {
            return 'Sin usuario';
        }

        $nombreFuncionario = $this->nombreFuncionarioUsuario($usuario);

        return $nombreFuncionario !== 'Sin funcionario'
            ? $nombreFuncionario
            : ($usuario->name ?: ($usuario->email ?: 'Sin usuario'));
    }

    private function textoCatalogoUnidad($unidad): string
    {
        if (! $unidad) {
            return 'Sin unidad';
        }

        return trim($unidad->nombre.($unidad->abreviatura ? ' ('.$unidad->abreviatura.')' : ''));
    }

    private function documentoPersona(?Persona $persona): string
    {
        if (! $persona) {
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
            && $usuario->puede('certificados.emitir')
            && (
                $usuario->tieneRol('administrador')
                || $usuario->tieneRol('funcionario')
                || $this->usuarioTieneCargoActivo($usuario)
            );
    }

    // Permite consultar el documento ya emitido, incluso cuando su vigencia terminó.
    // Esta autorización no permite modificar fechas ni generar una nueva emisión.
    private function usuarioPuedeImprimirCertificado(Certificado $certificado): bool
    {
        $usuario = auth()->user();

        if (! $usuario
            || ! in_array($certificado->estado, ['EMITIDO', 'VENCIDO'], true)
            || ! $certificado->emisiones()->whereNotNull('url_documento')->exists()) {
            return false;
        }

        $usuario->loadMissing('funcionario.cargos');
        $certificado->loadMissing('beneficiario.usuario', 'tramitador.usuario');

        $esUsuarioInterno = $usuario->puede('certificados.emitir')
            && (
                $usuario->tieneRol('administrador')
                || $usuario->tieneRol('funcionario')
                || $this->usuarioTieneCargoActivo($usuario)
            );

        if ($esUsuarioInterno) {
            return true;
        }

        return collect([
            $certificado->beneficiario?->usuarioAcceso(),
            $certificado->tramitador?->usuario,
        ])
            ->filter()
            ->contains(fn ($usuarioVinculado) => (int) $usuarioVinculado->id === (int) $usuario->id);
    }

    // Autoriza la descarga tanto al solicitante vinculado como al personal interno del sistema.
    private function usuarioPuedeDescargarCertificado(Certificado $certificado): bool
    {
        $usuario = auth()->user();
        $usuario?->loadMissing('funcionario.cargos');

        if (! $usuario) {
            return false;
        }

        $esUsuarioInterno = $usuario->tieneRol('administrador')
            || $usuario->tieneRol('funcionario')
            || $this->usuarioTieneCargoActivo($usuario);

        if ($esUsuarioInterno) {
            return true;
        }

        // El solicitante recibe el documento una vez emitido; si vence conserva su respaldo histórico.
        return in_array($certificado->estado, ['EMITIDO', 'VENCIDO'], true)
            && $this->gestionTramitadores->usuarioPuedeConsultarTramite($usuario, $certificado);
    }

    // USUARIOS A NOTIFICAR
    // Beneficiario y tramitador pueden estar a cargo; se evita duplicar si son la misma cuenta.
    private function usuariosSolicitantesCertificado(Certificado $certificado)
    {
        $certificado->loadMissing('beneficiario.usuario', 'tramitador.usuario');

        return collect([
            $certificado->beneficiario?->usuarioAcceso(),
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
