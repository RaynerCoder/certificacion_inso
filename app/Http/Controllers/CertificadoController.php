<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\CertificadoEmitido;
use App\Models\CertificadoProducto;
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
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Color\Alpha;
use BaconQrCode\Renderer\Color\Gray;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
            'productos.clasificacionToxicologica',
            'productos.fabricante',
            'productos.clasificacionQuimica',
            'productos.territorio',
            'productos.importadorPersona.natural',
            'productos.importadorPersona.empresa.tipoEmpresa',
            'productos.importadorPersona.territorio',
            'productos.ingredientesProductos.ingrediente',
            'productos.presentaciones.catalogoUnidad',
            'productos.registros.catalogoUnidad',
            'productos.registros.presentacion.catalogoUnidad',
            'certificadosRegistros.registro.producto.clasificacionToxicologica',
            'certificadosRegistros.registro.producto.fabricante',
            'certificadosRegistros.registro.producto.clasificacionQuimica',
            'certificadosRegistros.registro.presentacion.catalogoUnidad',
            'certificadosRegistros.registro.catalogoUnidad',
            'certificadosRegistros.emision',
            'certificadosRegistros.emisiones',
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
     * Relaciona un producto ya registrado con el trámite. Sus registros activos,
     * si existen, se conservan aparte para emisión por lote e historial.
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

            $relacionProducto = CertificadoProducto::withTrashed()
                ->where('id_certificado', $certificado->id)
                ->where('id_producto', $producto->id)
                ->first();

            if ($relacionProducto?->trashed()) {
                $relacionProducto->restore();
            } elseif (! $relacionProducto) {
                CertificadoProducto::create([
                    'id_certificado' => $certificado->id,
                    'id_producto' => $producto->id,
                ]);
            }

            $idsRegistros = $producto->registros()
                ->where('estado', 'ACTIVO')
                ->whereDoesntHave('certificadosRegistros')
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if ($idsRegistros !== []) {
                $certificado->registros()->syncWithoutDetaching($idsRegistros);
            }
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
     * Devuelve productos activos del beneficiario que todavía no pertenecen a un trámite.
     * Un producto puede no tener registros: la relación principal es trámite-producto.
     */
    private function consultaProductosDisponiblesParaTramite(Certificado $certificado)
    {
        $certificado->loadMissing(['registros.producto', 'productos']);

        $productosYaAsociados = $certificado->productos
            ->concat($certificado->registros->map(fn ($registro) => $registro->producto))
            ->filter()
            ->unique('id');
        $tiposProductoAsociados = $productosYaAsociados
            ->pluck('tipo_producto')
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
            ->whereDoesntHave('certificadosProductos')
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
        // En emisión única no hay fila de certificado_registro. Se debe tomar
        // la emisión ya guardada del trámite para volver a dibujar su QR real,
        // no el marcador de diseño "QR".
        $emisionSeleccionada = $registroSeleccionado
            ? $registroSeleccionado->emision
            : $certificado->emisiones
                ->filter(fn (CertificadoEmitido $emision) => $emision->estaVigente())
                ->sortByDesc('orden_emision')
                ->first();
        $datosVigenciaEmision = $this->datosVigenciaParaEmision($certificado);
        $valoresPlantilla = $this->valoresPlantillaCertificado($certificado, $emisionSeleccionada, $registroSeleccionado);
        $usaQrVerificacion = $this->usaQrVerificacion($certificado, $plantillaCertificado);

        // Antes de emitir se muestra en la plantilla la misma vigencia que se guardará.
        if (! $emisionSeleccionada && $datosVigenciaEmision['disponible']) {
            $valoresPlantilla['certificado.fecha_inicio'] = $datosVigenciaEmision['fecha_inicio']->format('d/m/Y');
            $valoresPlantilla['certificado.fecha_fin'] = $datosVigenciaEmision['es_indefinida']
                ? 'Vigencia indefinida'
                : $datosVigenciaEmision['fecha_fin']->format('d/m/Y');
        }

        // Esta lista es la misma que verá una persona al escanear el QR final.
        // No existe aún un QR consultable antes de guardar la emisión.
        $informacionPublicaQr = $usaQrVerificacion
            ? $this->datosPublicosQr($certificado, null, $datosVigenciaEmision)
            : [];

        $resumenDescargas = $this->descargasCertificados->resumen($certificado, auth()->user());
        $anulacionDisponible = $this->camposAnulacionDisponibles();

        return view('certificados.emitir_certificado.create', compact(
            'certificado',
            'plantillaCertificado',
            'valoresPlantilla',
            'resumenDescargas',
            'puedeGestionarEmision',
            'registroSeleccionado',
            'emisionSeleccionada',
            'datosVigenciaEmision',
            'usaQrVerificacion',
            'informacionPublicaQr',
            'anulacionDisponible'
        ));
    }

    /**
     * Prepara un QR temporal para la vista previa. No crea una emisión ni guarda
     * archivos: ambos ocurren solamente cuando se confirma la emisión final.
     */
    public function prepararQrEmision(Certificado $certificado)
    {
        $certificado->load($this->relacionesParaEmision());

        if (! $this->usuarioPuedeEmitirCertificado($certificado)) {
            abort(403, 'No puede emitir este certificado.');
        }

        $plantillaCertificado = $this->plantillaActivaParaEmision($certificado);

        if (! $this->usaQrVerificacion($certificado, $plantillaCertificado)) {
            return response()->json([
                'message' => 'Active la verificación QR en el tipo de certificado y marque una imagen de la plantilla como ubicación del QR.',
            ], 422);
        }

        if (! Schema::hasColumn('certificados_emitidos', 'codigo_verificacion')) {
            return response()->json([
                'message' => 'Falta configurar el campo de verificación en la base de datos antes de emitir con QR.',
            ], 422);
        }

        $codigoVerificacion = $this->nuevoCodigoVerificacion();
        $urlVerificacion = route('certificados_verificar_publico', [
            'codigoVerificacion' => $codigoVerificacion,
        ]);

        return response()->json([
            'codigo_verificacion' => $codigoVerificacion,
            'url_verificacion' => $urlVerificacion,
            'imagen_qr' => $this->imagenQrParaUrl($urlVerificacion),
        ]);
    }

    /**
     * Muestra solamente datos oficiales mínimos al leer un QR. Esta ruta no
     * entrega el PDF ni archivos privados.
     */
    public function verificarPublico(string $codigoVerificacion)
    {
        if (! Schema::hasTable('certificados_emitidos')
            || ! Schema::hasColumn('certificados_emitidos', 'codigo_verificacion')) {
            abort(404);
        }

        CertificadoEmitido::actualizarEstadosVencidos();

        $emision = CertificadoEmitido::query()
            ->with('certificado.tipoCertificado')
            ->where('codigo_verificacion', $codigoVerificacion)
            ->whereNotNull('url_documento')
            ->first();

        if (! $emision?->certificado) {
            return view('certificados.verificacion_publica', ['verificacion' => null]);
        }

        $certificado = $emision->certificado;
        $certificado->loadMissing($this->relacionesParaEmision());
        // El estado del documento concreto prevalece sobre el trámite. Esto
        // permite que un QR histórico informe "Anulado" aun si el trámite se
        // aprobó de nuevo para una reemisión.
        $estado = $emision->estado;

        return view('certificados.verificacion_publica', [
            'verificacion' => [
                'estado' => $estado,
                'titulo' => $certificado->tipoCertificado?->configuracionQr()['titulo'] ?? 'Verificación de certificado',
                'filas' => $this->datosPublicosQr($certificado, $emision),
            ],
        ]);
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

        $usaQrVerificacion = $this->usaQrVerificacion($certificado, $plantillaCertificado);
        $datos = $this->validarEmisionCertificado($solicitud, $certificado);

        if ($usaQrVerificacion && ! Schema::hasColumn('certificados_emitidos', 'codigo_verificacion')) {
            throw ValidationException::withMessages([
                'form_codigo_verificacion' => 'Falta ejecutar la actualización de base de datos para emitir certificados con QR.',
            ])->errorBag('emisionCertificado');
        }

        if ($usaQrVerificacion && blank($datos['form_codigo_verificacion'] ?? null)) {
            throw ValidationException::withMessages([
                'form_codigo_verificacion' => 'No se pudo preparar el código QR. Intente emitir nuevamente.',
            ])->errorBag('emisionCertificado');
        }
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

        DB::transaction(function () use ($certificado, $plantillaCertificado, $datos, $solicitud, $imagenRenderizada, $usaQrVerificacion) {
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

            if ($tramite->tipoCertificado?->modo_emision !== 'POR_LOTE' && $tramite->emisiones()->vigentes()->exists()) {
                throw ValidationException::withMessages([
                    'form_documento_renderizado' => 'Este trámite ya tiene su certificado emitido.',
                ])->errorBag('emisionCertificado');
            }

            $datosVigenciaEmision = $this->datosVigenciaParaEmision($tramite);

            if (! $datosVigenciaEmision['disponible']) {
                throw ValidationException::withMessages([
                    'form_documento_renderizado' => $datosVigenciaEmision['mensaje'],
                ])->errorBag('emisionCertificado');
            }

            if ($usaQrVerificacion && CertificadoEmitido::withTrashed()
                ->where('codigo_verificacion', $datos['form_codigo_verificacion'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'form_codigo_verificacion' => 'El código QR ya fue utilizado. Intente emitir nuevamente.',
                ])->errorBag('emisionCertificado');
            }

            $datosEmision = [
                'id_certificado' => $tramite->id,
                'id_certificado_registro' => $registroSeleccionado?->id,
                'id_plantilla_certificado' => $plantillaCertificado->id,
                'orden_emision' => ((int) $tramite->emisiones()->lockForUpdate()->max('orden_emision')) + 1,
                'numero_certificado' => $this->siguienteNumeroCertificado($tramite->tipoCertificado),
                'fecha_emision' => now(),
                'fecha_inicio' => $datosVigenciaEmision['fecha_inicio'],
                'fecha_fin' => $datosVigenciaEmision['fecha_fin'],
                'estado' => 'EMITIDO',
            ];

            if ($usaQrVerificacion) {
                $datosEmision['codigo_verificacion'] = $datos['form_codigo_verificacion'];
            }

            $emision = CertificadoEmitido::create($datosEmision);
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
    public function enviarCertificadoSolicitante(Request $solicitud, Certificado $certificado)
    {
        $certificado->load($this->relacionesParaEmision());

        if (! $this->usuarioPuedeEmitirCertificado($certificado)) {
            abort(403, 'No puede enviar este certificado.');
        }

        $idEmision = $solicitud->integer('form_id_certificado_emitido');
        $emision = $idEmision
            ? $certificado->emisiones->firstWhere('id', $idEmision)
            : $certificado->emisiones
                ->filter(fn (CertificadoEmitido $documento) => $documento->estado === 'EMITIDO')
                ->sortByDesc('orden_emision')
                ->first();

        if (! $emision || $emision->estado !== 'EMITIDO') {
            return back()->with('error', 'Seleccione un certificado emitido y vigente para notificar al solicitante.');
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
                'mensaje' => 'El certificado N.º '.$emision->numero_certificado.' de su trámite ya fue emitido y está disponible para su consulta.',
                'estado' => 'ACTIVO',
            ]);
        }

        session()->flash('swal', [
            'title' => 'Certificado enviado',
            'text' => 'Se notificó al beneficiario y tramitador vinculados al trámite.',
            'icon' => 'success',
        ]);

        return redirect()->route('certificados_emitir', [
            'certificado' => $certificado,
            'registro' => $emision->id_certificado_registro,
        ]);
    }

    /**
     * Anula un documento emitido sin eliminarlo. El número, QR y PDF quedan
     * disponibles como evidencia histórica; una reemisión crea otra fila.
     */
    public function anularEmision(Request $solicitud, Certificado $certificado, CertificadoEmitido $emision)
    {
        abort_unless((int) $emision->id_certificado === (int) $certificado->id, 404);

        if (! $this->camposAnulacionDisponibles()) {
            return back()->with('error', 'Falta agregar los campos de anulación en certificados_emitidos. Ejecute primero el SQL de actualización.');
        }

        $datos = $solicitud->validateWithBag('anulacionCertificado', [
            'form_motivo_anulacion' => ['required', 'string', 'max:1000'],
        ], [], [
            'form_motivo_anulacion' => 'motivo de anulación',
        ]);

        $certificado->load($this->relacionesParaEmision());

        if (! $this->usuarioPuedeEmitirCertificado($certificado)) {
            abort(403, 'No puede anular este certificado.');
        }

        if (! $emision->estaVigente()) {
            return back()->with('error', 'Este documento ya fue anulado y no puede volver a anularse.');
        }

        DB::transaction(function () use ($certificado, $emision, $datos) {
            $tramite = Certificado::query()
                ->with('tipoCertificado')
                ->lockForUpdate()
                ->findOrFail($certificado->id);

            $documento = CertificadoEmitido::query()
                ->lockForUpdate()
                ->findOrFail($emision->id);

            abort_unless((int) $documento->id_certificado === (int) $tramite->id, 404);

            if (! $documento->estaVigente()) {
                throw ValidationException::withMessages([
                    'form_motivo_anulacion' => 'El documento ya no está vigente y no puede anularse nuevamente.',
                ])->errorBag('anulacionCertificado');
            }

            $documento->update([
                'estado' => 'ANULADO',
                'fecha_anulacion' => now(),
                'id_usuario_anulacion' => auth()->id(),
                'motivo_anulacion' => trim($datos['form_motivo_anulacion']),
            ]);

            // En una emisión por lote otros documentos pueden seguir vigentes.
            // El trámite único vuelve a APROBADO y el lote anulado queda listo
            // para reemitirse con un nuevo número y un QR distinto.
            $hayDocumentosVigentes = $tramite->emisiones()->vigentes()->exists();
            $tramite->update([
                'estado' => $hayDocumentosVigentes ? 'EMITIDO' : 'APROBADO',
            ]);
        });

        session()->flash('swal', [
            'title' => 'Certificado anulado',
            'text' => 'El documento fue anulado y se conserva en el historial. Ya puede emitir uno nuevo cuando corresponda.',
            'icon' => 'success',
        ]);

        return redirect()->route('certificados_emitidos_index');
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
                ['codigo' => 'emision.numero_certificado', 'nombre' => 'Número de registro autorizado', 'ayuda' => 'Es la numeración oficial asignada al guardar la emisión del certificado.'],
                ['codigo' => 'emision.fecha', 'nombre' => 'Fecha de emisión del certificado', 'ayuda' => 'Puede acompañarla de un texto fijo para el lugar: por ejemplo, “La Paz, ”.'],
                ['codigo' => 'certificado.fecha_inicio', 'nombre' => 'Fecha de inicio de vigencia'],
                ['codigo' => 'certificado.fecha_fin', 'nombre' => 'Fecha final de vigencia'],
                ['codigo' => 'certificado.descripcion', 'nombre' => 'Descripción del trámite'],
                ['codigo' => 'evaluacion.resultado', 'nombre' => 'Resultado de la evaluación'],
                ['codigo' => 'evaluacion.calificaciones', 'nombre' => 'Calificaciones obtenidas'],
                ['codigo' => 'evaluacion.calificaciones_minimas', 'nombre' => 'Calificaciones mínimas'],
                ['codigo' => 'tipo_certificado.nombre', 'nombre' => 'Tipo de certificado'],
                ['codigo' => 'area.nombre', 'nombre' => 'Área responsable'],
            ],
            'Finalización del trámite' => [
                ['codigo' => 'tramite.fecha_registro', 'nombre' => 'Fecha de registro del trámite', 'ayuda' => 'Corresponde a la fecha en la que el funcionario finalizó el trámite.'],
                ['codigo' => 'tramite.funcionario_finalizador', 'nombre' => 'Funcionario que finalizó el trámite', 'ayuda' => 'Es la persona que realizó la finalización del trámite.'],
                ['codigo' => 'tramite.funcionario_finalizador_cargo', 'nombre' => 'Cargo de quien finalizó el trámite'],
            ],
            'Beneficiario' => [
                ['codigo' => 'beneficiario.nombre', 'nombre' => 'Nombre o razón social'],
                ['codigo' => 'beneficiario.documento', 'nombre' => 'CI o NIT'],
                ['codigo' => 'beneficiario.expedido', 'nombre' => 'Expedido del CI'],
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
                ['codigo' => 'empresa.representante_legal_expedido', 'nombre' => 'Expedido del CI del representante legal'],
                ['codigo' => 'natural.nombres', 'nombre' => 'Nombres'],
                ['codigo' => 'natural.apellido_paterno', 'nombre' => 'Apellido paterno'],
                ['codigo' => 'natural.apellido_materno', 'nombre' => 'Apellido materno'],
                ['codigo' => 'natural.ocupacion', 'nombre' => 'Ocupación'],
            ],
            'Tramitador' => [
                ['codigo' => 'tramitador.nombre', 'nombre' => 'Nombre del tramitador'],
                ['codigo' => 'tramitador.documento', 'nombre' => 'CI o NIT'],
                ['codigo' => 'tramitador.expedido', 'nombre' => 'Expedido del CI'],
                ['codigo' => 'tramitador.correo', 'nombre' => 'Correo electrónico'],
                ['codigo' => 'tramitador.telefono', 'nombre' => 'Teléfono'],
                ['codigo' => 'tramitador.rol', 'nombre' => 'Rol en la empresa'],
                ['codigo' => 'tramitador.respaldo', 'nombre' => 'Respaldo registrado'],
            ],
            'Importador asociado' => [
                ['codigo' => 'importador.nombre', 'nombre' => 'Nombre o razón social del importador', 'ayuda' => 'Se completa cuando los productos seleccionados tienen el mismo importador.'],
                ['codigo' => 'importador.documento', 'nombre' => 'CI o NIT del importador'],
                ['codigo' => 'importador.expedido', 'nombre' => 'Expedido del importador'],
                ['codigo' => 'importador.tipo_persona', 'nombre' => 'Tipo de persona del importador'],
                ['codigo' => 'importador.domicilio', 'nombre' => 'Domicilio del importador'],
                ['codigo' => 'importador.territorio', 'nombre' => 'Territorio del importador'],
                ['codigo' => 'importador.ubicacion', 'nombre' => 'Territorio y domicilio del importador'],
                ['codigo' => 'importador.correo', 'nombre' => 'Correo del importador'],
                ['codigo' => 'importador.telefono', 'nombre' => 'Teléfono del importador'],
                ['codigo' => 'importador.representante_legal_nombre', 'nombre' => 'Representante legal del importador'],
                ['codigo' => 'importador.representante_legal_documento', 'nombre' => 'CI del representante legal del importador'],
                ['codigo' => 'importador.representante_legal_expedido', 'nombre' => 'Expedido del representante legal'],
            ],
            'Productos asociados' => [
                ['codigo' => 'producto.tabla', 'nombre' => 'Tabla de productos asociados', 'ayuda' => 'Úsela cuando el trámite tenga varios productos; muestra un producto por fila.'],
                ['codigo' => 'producto.nombres_comerciales', 'nombre' => 'Nombres comerciales de todos los productos', 'ayuda' => 'Ejemplo: Producto A, Producto B.'],
                ['codigo' => 'producto.codigos', 'nombre' => 'Códigos de todos los productos'],
                ['codigo' => 'producto.nombres_tecnicos', 'nombre' => 'Nombres técnicos de todos los productos'],
                ['codigo' => 'producto.clasificaciones_quimicas', 'nombre' => 'Clasificaciones químicas de todos los productos'],
                ['codigo' => 'fabricante.nombres', 'nombre' => 'Fabricantes de todos los productos'],
                ['codigo' => 'clasificacion_toxicologica.codigos', 'nombre' => 'Clasificaciones toxicológicas de todos los productos'],
            ],
            'Producto individual o del lote' => [
                ['codigo' => 'producto.codigo', 'nombre' => 'Código del producto individual', 'ayuda' => 'Se completa cuando el trámite tiene un único producto o al emitir por lote.'],
                ['codigo' => 'producto.nombre_comercial', 'nombre' => 'Nombre comercial del producto individual'],
                ['codigo' => 'producto.nombre_tecnico', 'nombre' => 'Nombre técnico del producto individual'],
                ['codigo' => 'producto.tipo_producto', 'nombre' => 'Tipo del producto individual'],
                ['codigo' => 'producto.pais_origen', 'nombre' => 'País de origen del producto individual'],
                ['codigo' => 'producto.clasificacion_quimica', 'nombre' => 'Clasificación química del producto individual'],
                ['codigo' => 'producto.clasificacion_quimica_descripcion', 'nombre' => 'Descripción de la clasificación química'],
                ['codigo' => 'fabricante.nombre', 'nombre' => 'Fabricante del producto individual'],
                ['codigo' => 'fabricante.razon_social', 'nombre' => 'Razón social del fabricante'],
                ['codigo' => 'fabricante.descripcion', 'nombre' => 'Descripción del fabricante'],
                ['codigo' => 'clasificacion_toxicologica.codigo', 'nombre' => 'Clase toxicológica'],
                ['codigo' => 'clasificacion_toxicologica.descripcion', 'nombre' => 'Descripción de la clase toxicológica'],
                ['codigo' => 'producto.observacion_toxicologica_muestra', 'nombre' => 'Observación toxicológica de muestra'],
                ['codigo' => 'clasificacion_toxicologica.completa', 'nombre' => 'Clasificación toxicológica completa (clase y descripción)'],
                ['codigo' => 'clasificacion_toxicologica.con_observacion', 'nombre' => 'Clasificación toxicológica completa con observación de muestra'],
            ],
            'Registros y presentaciones asociados' => [
                ['codigo' => 'registro.codigos', 'nombre' => 'Códigos de todos los registros'],
                ['codigo' => 'registro.lotes', 'nombre' => 'Números de lote de todos los registros'],
                ['codigo' => 'registro.fechas_vigencia', 'nombre' => 'Fechas de vigencia de todos los registros'],
                ['codigo' => 'registro.cantidades', 'nombre' => 'Cantidades de todos los registros'],
                ['codigo' => 'registro.unidades', 'nombre' => 'Unidades de todos los registros'],
                ['codigo' => 'presentacion.descripciones', 'nombre' => 'Descripciones de todas las presentaciones'],
                ['codigo' => 'presentacion.lista', 'nombre' => 'Presentaciones agrupadas por tipo', 'ayuda' => 'No repite el tipo: Envase de 210 ml y de 500 ml (aerosol); Frasco de 1 litro.'],
                ['codigo' => 'presentacion.cantidades', 'nombre' => 'Cantidades de todas las presentaciones'],
                ['codigo' => 'presentacion.unidades', 'nombre' => 'Unidades de todas las presentaciones'],
            ],
            'Registro y presentación individual o del lote' => [
                ['codigo' => 'registro.codigo', 'nombre' => 'Código del registro individual', 'ayuda' => 'Se completa cuando el trámite tiene un único registro o al emitir por lote.'],
                ['codigo' => 'registro.numero_lote', 'nombre' => 'Número de lote del registro individual'],
                ['codigo' => 'registro.fecha_vigencia', 'nombre' => 'Fecha de vigencia del registro individual'],
                ['codigo' => 'registro.cantidad', 'nombre' => 'Cantidad del registro individual'],
                ['codigo' => 'registro.unidad', 'nombre' => 'Unidad del registro individual'],
                ['codigo' => 'presentacion.descripcion', 'nombre' => 'Descripción de la presentación individual'],
                ['codigo' => 'presentacion.tipo_presentacion', 'nombre' => 'Tipo de la presentación individual'],
                ['codigo' => 'presentacion.cantidad', 'nombre' => 'Cantidad de la presentación individual'],
                ['codigo' => 'presentacion.unidad', 'nombre' => 'Unidad de la presentación individual'],
                ['codigo' => 'presentacion.url_etiqueta', 'nombre' => 'Etiqueta de la presentación individual'],
            ],
            'Ingredientes del producto individual o del lote' => [
                ['codigo' => 'ingrediente.tabla', 'nombre' => 'Tabla de ingredientes', 'ayuda' => 'Se completa cuando el trámite tiene un único producto o al emitir por lote.'],
                ['codigo' => 'ingrediente.nombre', 'nombre' => 'Ingrediente del producto individual'],
                ['codigo' => 'ingrediente.nombres', 'nombre' => 'Ingredientes del producto individual'],
                ['codigo' => 'ingrediente.formula_quimica', 'nombre' => 'Fórmula química', 'ayuda' => 'Se completa solamente cuando el producto tiene un único ingrediente activo.'],
                ['codigo' => 'ingrediente.formulas_quimicas', 'nombre' => 'Fórmulas químicas de los ingredientes', 'ayuda' => 'Cuando existen varios ingredientes, muestra cada ingrediente con su fórmula química.'],
                ['codigo' => 'ingrediente.composicion', 'nombre' => 'Composición'],
                ['codigo' => 'ingrediente.composiciones', 'nombre' => 'Composiciones separadas por coma'],
                ['codigo' => 'ingrediente.riesgo_salud', 'nombre' => 'Riesgo para la salud'],
                ['codigo' => 'ingrediente.riesgos_salud', 'nombre' => 'Riesgos para la salud separados por coma'],
                ['codigo' => 'ingrediente.porcentaje', 'nombre' => 'Porcentaje en el producto'],
                ['codigo' => 'ingrediente.porcentajes', 'nombre' => 'Porcentajes separados por coma'],
                ['codigo' => 'ingrediente.resumen', 'nombre' => 'Ingredientes con sus porcentajes'],
                ['codigo' => 'ingrediente.composicion_cuantitativa', 'nombre' => 'Composición cuantitativa de ingredientes y otras sustancias', 'ayuda' => 'Combina el ingrediente, su porcentaje y la composición registrada.'],
            ],
            'Frases reglamentarias del producto individual o del lote' => [
                ['codigo' => 'frases.riesgo.codigos', 'nombre' => 'Códigos de frases de riesgo'],
                ['codigo' => 'frases.riesgo.completas', 'nombre' => 'Frases de riesgo: código y texto', 'ayuda' => 'Se completa cuando el trámite tiene un único producto o al emitir por lote.'],
                ['codigo' => 'frases.seguridad.codigos', 'nombre' => 'Códigos de frases de seguridad'],
                ['codigo' => 'frases.seguridad.completas', 'nombre' => 'Frases de seguridad: código y texto'],
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
                ['codigo' => 'requisito.observacion', 'nombre' => 'Observación'],
                ['codigo' => 'evidencia.valor', 'nombre' => 'Valor o archivo de evidencia'],
            ],
            'Seguimiento y revisión' => [
                ['codigo' => 'seguimiento.tabla', 'nombre' => 'Tabla de seguimiento'],
                ['codigo' => 'seguimiento.fecha_inicio', 'nombre' => 'Fecha de inicio'],
                ['codigo' => 'seguimiento.fecha_derivacion', 'nombre' => 'Fecha de derivación'],
                ['codigo' => 'seguimiento.referencia', 'nombre' => 'Referencia'],
                ['codigo' => 'seguimiento.descripcion_final', 'nombre' => 'Descripción final'],
                ['codigo' => 'seguimiento.usuario_origen', 'nombre' => 'Derivado por'],
                ['codigo' => 'seguimiento.usuario_siguiente', 'nombre' => 'Derivado a'],
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

            if ($tipoElemento === 'TABLA') {
                $textoFijo = $this->normalizarContenidoTablaPlantilla($textoFijo);
            }

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
     * Normaliza el contenido y los estilos individuales de una tabla.
     * La configuración vive junto a las filas existentes para no crear una tabla adicional.
     */
    private function normalizarContenidoTablaPlantilla(mixed $contenido): ?string
    {
        if (! is_string($contenido)) {
            return null;
        }

        $datos = json_decode($contenido, true);
        if (! is_array($datos)) {
            return $contenido;
        }

        $filasEntrada = is_array($datos['filas'] ?? null) ? array_slice($datos['filas'], 0, 100) : [];
        $estilosEntrada = is_array($datos['estilos'] ?? null) ? $datos['estilos'] : [];
        $filas = [];
        $estilos = [];

        foreach ($filasEntrada as $indiceFila => $fila) {
            if (! is_array($fila)) {
                continue;
            }

            $filaNormalizada = [];
            $estilosFila = [];
            foreach (array_slice($fila, 0, 8) as $indiceCelda => $celda) {
                $filaNormalizada[] = mb_substr((string) $celda, 0, 5000);
                $estilosFila[] = $this->normalizarEstiloCeldaTablaPlantilla(
                    is_array(data_get($estilosEntrada, $indiceFila.'.'.$indiceCelda))
                        ? data_get($estilosEntrada, $indiceFila.'.'.$indiceCelda)
                        : []
                );
            }

            $filas[] = $filaNormalizada;
            $estilos[] = $estilosFila;
        }

        return json_encode([
            'filas' => $filas,
            'estilos' => $estilos,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return array<string, bool|float|string> */
    private function normalizarEstiloCeldaTablaPlantilla(array $estilo): array
    {
        $resultado = [];
        $fuentesPermitidas = [
            'Arial', 'Arial Narrow', 'Agency FB', 'Bahnschrift SemiBold Condensed',
            'Calibri', 'Cambria', 'Times New Roman', 'Lucida Bright',
            'Monotype Corsiva', 'Georgia', 'Verdana', 'Tahoma', 'Courier New',
        ];

        if (in_array($estilo['tipo_letra'] ?? null, $fuentesPermitidas, true)) {
            $resultado['tipo_letra'] = $estilo['tipo_letra'];
        }
        if (array_key_exists('tamano_letra', $estilo) && is_numeric($estilo['tamano_letra'])) {
            $resultado['tamano_letra'] = max(6, min(160, (float) $estilo['tamano_letra']));
        }
        foreach (['negrita', 'cursiva', 'subrayado'] as $propiedad) {
            if (array_key_exists($propiedad, $estilo) && is_bool($estilo[$propiedad])) {
                $resultado[$propiedad] = $estilo[$propiedad];
            }
        }
        if (isset($estilo['color_texto']) && preg_match('/^#[0-9a-f]{6}$/i', (string) $estilo['color_texto'])) {
            $resultado['color_texto'] = strtolower((string) $estilo['color_texto']);
        }
        if (isset($estilo['alineacion'])) {
            $resultado['alineacion'] = $this->normalizarAlineacionPlantilla((string) $estilo['alineacion']);
        }

        return $resultado;
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
            // La tabla Campo/Valor aumenta una fila completa cuando un valor
            // ocupa varias líneas. Se alinea arriba para conservar el campo y
            // la primera línea del valor en la misma altura.
            'alineacion_vertical' => $tipoTabla === 'CAMPO_VALOR'
                ? 'SUPERIOR'
                : (in_array($alineacionVertical, ['SUPERIOR', 'MEDIO', 'INFERIOR'], true)
                    ? $alineacionVertical
                    : 'MEDIO'),
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
        $reglaCodigoVerificacion = ['nullable', 'uuid'];

        // La instalación puede desplegar código antes de ejecutar el SQL manual.
        // En ese intervalo validamos la forma y luego mostramos el mensaje claro
        // de configuración, sin consultar una columna que todavía no existe.
        if (Schema::hasColumn('certificados_emitidos', 'codigo_verificacion')) {
            $reglaCodigoVerificacion[] = Rule::unique('certificados_emitidos', 'codigo_verificacion');
        }

        return $solicitud->validateWithBag('emisionCertificado', [
            'form_id_certificado_registro' => ['nullable', 'integer'],
            'form_codigo_verificacion' => $reglaCodigoVerificacion,
            // Es obligatorio para que el PDF salga del mismo render Fabric.js mostrado al funcionario.
            'form_documento_renderizado' => ['required', 'string', 'max:50000000'],
            'bandeja' => ['nullable', 'string', 'max:30'],
        ], [], [
            'form_documento_renderizado' => 'render final de la plantilla',
        ]);
    }

    private function plantillaUsaQrVerificacion(?PlantillaCertificado $plantillaCertificado): bool
    {
        if (! $plantillaCertificado) {
            return false;
        }

        return $plantillaCertificado->elementosActivos
            ->contains(fn (PlantillaElemento $elemento) => strtoupper((string) $elemento->tipo_elemento) === 'QR'
                && strtolower((string) $elemento->codigo_elemento) === 'qr.verificacion'
                && (bool) data_get($elemento->configuracion, 'visible', true));
    }

    /**
     * Comprueba la configuración del tipo y la ubicación del QR en su plantilla.
     */
    private function usaQrVerificacion(
        Certificado $certificado,
        ?PlantillaCertificado $plantillaCertificado
    ): bool {
        return (bool) ($certificado->tipoCertificado?->configuracionQr()['habilitado'] ?? false)
            && $this->plantillaUsaQrVerificacion($plantillaCertificado);
    }

    /**
     * Datos públicos seleccionados por el administrador para este tipo de certificado.
     * Esta estructura se utiliza tanto antes de emitir (vista previa) como al
     * consultar el QR definitivo, para que ambas pantallas coincidan.
     *
     * @return array<int, array{etiqueta: string, valor: string}>
     */
    private function datosPublicosQr(
        Certificado $certificado,
        ?CertificadoEmitido $emision = null,
        ?array $datosVigencia = null
    ): array {
        $valores = $this->valoresPlantillaCertificado(
            $certificado,
            $emision,
            $emision?->certificadoRegistro
        );

        $fechaFinPrevia = data_get($datosVigencia, 'fecha_fin');
        $vigencia = $emision
            ? ($emision->fecha_fin?->format('d/m/Y') ?: 'Vigencia indefinida')
            : (data_get($datosVigencia, 'es_indefinida')
                ? 'Vigencia indefinida'
                : ($fechaFinPrevia?->format('d/m/Y') ?? 'Se calculará al emitir'));

        $referencia = collect([
            $valores['producto.nombres_comerciales'] ?? null,
            $valores['presentacion.lista'] ?? null,
        ])->filter(fn ($valor) => filled($valor) && ! in_array($valor, ['-', 'Sin presentación', 'Sin productos'], true))
            ->unique()
            ->implode(' · ');

        $estado = $emision
            ? ($certificado->estado === 'ANULADO' ? 'ANULADO' : $emision->estado)
            : 'PENDIENTE DE EMISIÓN';

        $valores['certificado.fecha_fin'] = $vigencia;
        $valores['producto.referencia_publica'] = $referencia !== '' ? $referencia : 'Sin productos asociados';
        $valores['estado'] = $estado;
        return $certificado->tipoCertificado?->filasPublicasQr($valores) ?? [];
    }

    private function nuevoCodigoVerificacion(): string
    {
        for ($intento = 0; $intento < 5; $intento++) {
            $codigo = (string) Str::uuid();

            if (! CertificadoEmitido::withTrashed()
                ->where('codigo_verificacion', $codigo)
                ->exists()) {
                return $codigo;
            }
        }

        throw new \RuntimeException('No se pudo generar un código único de verificación.');
    }

    private function imagenQrParaUrl(string $url): string
    {
        $renderizador = new ImageRenderer(
            new RendererStyle(360, 4, fill: Fill::uniformColor(new Alpha(0, new Gray(100)), new Gray(0))),
            new SvgImageBackEnd()
        );
        $svg = (new Writer($renderizador))->writeString($url);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Calcula la vigencia del documento con la regla configurada para su tipo.
     * La fecha de inicio es el cierre real del trámite, no una fecha elegida manualmente al emitir.
     *
     * @return array{disponible: bool, mensaje: string|null, fecha_inicio: \Illuminate\Support\Carbon|null, fecha_fin: \Illuminate\Support\Carbon|null, es_indefinida: bool, texto: string|null}
     */
    private function datosVigenciaParaEmision(Certificado $certificado): array
    {
        $tipoCertificado = $certificado->tipoCertificado;
        $reglaVigencia = $tipoCertificado?->datosVigencia() ?? ['cantidad' => null, 'unidad' => null];
        $cantidad = $reglaVigencia['cantidad'];
        $unidad = $reglaVigencia['unidad'];

        if (! $unidad || ($unidad !== 'INDEFINIDA' && ! $cantidad)) {
            return [
                'disponible' => false,
                'mensaje' => 'Configure la vigencia del tipo de certificado antes de emitir.',
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'es_indefinida' => false,
                'texto' => null,
            ];
        }

        $seguimientoFinalizado = $certificado->seguimientos()
            ->where('estado', 'FINALIZADO')
            ->whereNotNull('fecha_final')
            ->orderByDesc('fecha_final')
            ->orderByDesc('id')
            ->first();

        if (! $seguimientoFinalizado?->fecha_final) {
            return [
                'disponible' => false,
                'mensaje' => 'El trámite debe estar finalizado para calcular la vigencia del certificado.',
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'es_indefinida' => false,
                'texto' => null,
            ];
        }

        $fechaInicio = $seguimientoFinalizado->fecha_final->copy()->startOfDay();
        $esIndefinida = $unidad === 'INDEFINIDA';
        $fechaFin = $esIndefinida ? null : match ($unidad) {
            'DIA' => $fechaInicio->copy()->addDays($cantidad),
            'MES' => $fechaInicio->copy()->addMonthsNoOverflow($cantidad),
            'ANIO' => $fechaInicio->copy()->addYearsNoOverflow($cantidad),
        };

        return [
            'disponible' => true,
            'mensaje' => null,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'es_indefinida' => $esIndefinida,
            'texto' => $tipoCertificado->textoVigencia(),
        ];
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
            'QR' => $valores[$elemento->codigo_elemento] ?? '',
            'TABLA', 'FORMA', 'LINEA' => null,
            default => $valores[$elemento->codigo_elemento] ?? '',
        };
        return [
            'elemento' => $elemento,
            'valor' => $valor,
            'filas' => $tipoElemento === 'TABLA'
                ? $this->filasTablaDocumento($elemento, $valoresConAlias)
                : [],
            'estilos_celdas' => $tipoElemento === 'TABLA'
                ? $this->estilosCeldasTablaDocumento($elemento)
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

    private function estilosCeldasTablaDocumento(PlantillaElemento $elemento): array
    {
        $contenido = json_decode((string) $elemento->texto_fijo, true);
        $estilos = is_array($contenido['estilos'] ?? null) ? $contenido['estilos'] : [];

        return collect($estilos)
            ->map(fn ($fila) => is_array($fila)
                ? collect($fila)
                    ->map(fn ($estilo) => $this->normalizarEstiloCeldaTablaPlantilla(
                        is_array($estilo) ? $estilo : []
                    ))
                    ->values()
                    ->all()
                : [])
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
            'registros.producto.frasesReglamentarias',
            'registros.producto.importadorPersona.natural',
            'registros.producto.importadorPersona.empresa.tipoEmpresa',
            'registros.producto.importadorPersona.empresa.representanteLegalActivo.persona.natural',
            'registros.producto.importadorPersona.territorio',
            'registros.producto.importadorPersona.telefonos',
            'registros.presentacion.catalogoUnidad',
            'registros.catalogoUnidad',
            'productos.clasificacionToxicologica',
            'productos.fabricante',
            'productos.clasificacionQuimica',
            'productos.territorio',
            'productos.ingredientesProductos.ingrediente',
            'productos.frasesReglamentarias',
            'productos.importadorPersona.natural',
            'productos.importadorPersona.empresa.tipoEmpresa',
            'productos.importadorPersona.empresa.representanteLegalActivo.persona.natural',
            'productos.importadorPersona.territorio',
            'productos.importadorPersona.telefonos',
            'productos.presentaciones.catalogoUnidad',
            'productos.registros.catalogoUnidad',
            'productos.registros.presentacion.catalogoUnidad',
            'certificadosRegistros.registro.producto.clasificacionToxicologica',
            'certificadosRegistros.registro.producto.fabricante',
            'certificadosRegistros.registro.producto.clasificacionQuimica',
            'certificadosRegistros.registro.producto.territorio',
            'certificadosRegistros.registro.producto.ingredientesProductos.ingrediente',
            'certificadosRegistros.registro.producto.frasesReglamentarias',
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
        $seguimientoFinalizacion = $certificado->seguimientos
            ->filter(fn ($seguimiento) => strtoupper((string) $seguimiento->estado) === 'FINALIZADO' && $seguimiento->fecha_final)
            ->sortByDesc(fn ($seguimiento) => $seguimiento->fecha_final?->getTimestamp() ?? 0)
            ->first();

        $productos = $certificadoRegistro?->registro
            ? collect([$certificadoRegistro->registro->producto])->filter()
            : $certificado->productos
                ->concat($registros->map(fn ($registro) => $registro->producto))
                ->filter()
                ->unique('id')
                ->values();
        $presentaciones = $certificadoRegistro?->registro
            ? collect([$certificadoRegistro->registro->presentacion])->filter()
            : $productos
                ->flatMap(fn ($producto) => $producto->presentaciones ?? collect())
                ->filter(fn ($presentacion) => $presentacion->estado === 'ACTIVO')
                ->concat($registros->map(fn ($registro) => $registro->presentacion)->filter())
                ->unique('id')
                ->values();
        // No se toma un "primer" producto de manera arbitraria para los campos individuales.
        $productoIndividual = $productos->count() === 1 ? $productos->first() : null;
        $registroIndividual = $registros->count() === 1 ? $registros->first() : null;
        $importadoresProductos = $productos
            ->map(fn ($producto) => $producto?->importadorPersona)
            ->filter()
            ->unique('id')
            ->values();
        $importadorProducto = $importadoresProductos->count() === 1 ? $importadoresProductos->first() : null;
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
        $ingredientesProducto = collect($productoIndividual?->ingredientesProductos ?? [])
            ->filter(fn ($relacion) => in_array(strtoupper((string) $relacion->estado), ['1', 'ACTIVO'], true))
            ->filter(fn ($relacion) => $relacion->ingrediente)
            ->values();
        $frasesProducto = collect($productoIndividual?->frasesReglamentarias ?? [])
            ->filter(fn ($frase) => in_array(strtoupper((string) $frase->estado), ['1', 'ACTIVO'], true))
            ->filter(fn ($frase) => in_array(strtoupper((string) ($frase->pivot?->estado ?? 'ACTIVO')), ['1', 'ACTIVO'], true))
            ->sortBy('codigo')
            ->values();
        $frasesRiesgo = $frasesProducto->where('tipo', 'RIESGO')->values();
        $frasesSeguridad = $frasesProducto->where('tipo', 'SEGURIDAD')->values();
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
        $usuarioFinalizador = $seguimientoFinalizacion?->usuarioOrigen;
        $usuarioValidadorPago = $primerPago?->funcionarioUsuario;
        $usuarioFirmaArea = $usuarioDestino ?: $usuarioRevisor ?: $usuarioOrigen;
        $usuarioDirector = $this->usuarioDirectorPlantilla();
        $usuarioEmisor = $emision?->id_usuario_registro
            ? User::withTrashed()->with('funcionario.cargos')->find($emision->id_usuario_registro)
            : auth()->user();
        $presentacionesCompletas = $this->resumenPresentacionesPlantilla($presentaciones);
        $ingredientesConPorcentaje = $ingredientesProducto->map(function ($relacion) {
            $nombre = trim((string) $relacion->ingrediente?->nombre);
            $porcentaje = $this->formatearDecimalPlantilla($relacion->porcentaje);

            return trim($nombre.($porcentaje !== '-' ? ' '.$porcentaje.'%' : ''));
        });
        $formulasQuimicasIngredientes = $ingredientesProducto->map(function ($relacion) {
            $formula = $this->formatearFormulaQuimicaPlantilla($relacion->ingrediente?->formula_quimica);

            return [
                'nombre' => trim((string) $relacion->ingrediente?->nombre),
                'formula' => $formula,
            ];
        })->filter(fn ($ingrediente) => $ingrediente['formula'] !== '')->values();
        $formulaQuimicaIndividual = $formulasQuimicasIngredientes->count() === 1
            ? $formulasQuimicasIngredientes->first()['formula']
            : '-';
        $formulasQuimicasConIngredientes = $formulasQuimicasIngredientes
            ->map(function ($ingrediente) use ($formulasQuimicasIngredientes) {
                if ($formulasQuimicasIngredientes->count() === 1 || $ingrediente['nombre'] === '') {
                    return $ingrediente['formula'];
                }

                return $ingrediente['nombre'].': '.$ingrediente['formula'];
            })
            ->unique()
            ->values()
            ->implode('; ') ?: 'Sin fórmula química';
        $composicionCuantitativa = $ingredientesProducto->map(function ($relacion) {
            $nombre = trim((string) $relacion->ingrediente?->nombre);
            $porcentaje = $this->formatearDecimalPlantilla($relacion->porcentaje);
            $composicion = trim((string) $relacion->ingrediente?->composicion);
            $ingrediente = trim($nombre.($porcentaje !== '-' ? ' '.$porcentaje.' %' : ''));

            return collect([$ingrediente, $composicion])->filter()->implode('; ');
        })->filter()->implode('; ') ?: 'Sin composición';
        $textoFrasesReglamentarias = static fn ($frases) => $frases
            ->map(fn ($frase) => trim(implode(' - ', array_filter([$frase->codigo, $frase->descripcion]))))
            ->filter()
            ->implode('; ') ?: '-';
        $resumenProductos = $productos->map(function ($producto) use ($presentaciones) {
            $presentacion = $presentaciones
                ->first(fn ($item) => (int) $item->id_producto === (int) $producto?->id);

            return collect([
                $producto?->codigo,
                $producto?->nombre_comercial,
                $presentacion ? $this->formatearDecimalPlantilla($presentacion->cantidad).' '.$this->textoCatalogoUnidad($presentacion->catalogoUnidad) : null,
            ])->filter()->implode(' | ');
        });
        $resumenRequisitos = $certificado->certificadoRequisitos->map(function ($requisito) {
            return collect([
                $requisito->requisito?->descripcion,
                $requisito->cumple,
            ])->filter()->implode(' | ');
        });
        $resumenSeguimientos = $certificado->seguimientos->sortBy('id')->map(function ($seguimiento) {
            return collect([
                $seguimiento->fecha_inicio?->format('d/m/Y H:i'),
                $seguimiento->referencia,
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
            'qr.verificacion' => $emision?->codigo_verificacion
                ? $this->imagenQrParaUrl(route('certificados_verificar_publico', [
                    'codigoVerificacion' => $emision->codigo_verificacion,
                ]))
                : '',
            'certificado.descripcion' => $certificado->descripcion ?: 'Sin descripción',
            'evaluacion.resultado' => $resultadoEvaluacion,
            'evaluacion.calificaciones' => $calificacionesObtenidas->isEmpty() ? 'Sin calificación' : $calificacionesObtenidas->implode(', '),
            'evaluacion.calificaciones_minimas' => $calificacionesMinimas->isEmpty() ? 'Sin calificación mínima' : $calificacionesMinimas->implode(', '),
            'tipo_certificado.nombre' => $certificado->tipoCertificado?->nombre ?: 'Sin tipo',
            'area.nombre' => $certificado->tipoCertificado?->area?->nombre ?: 'Sin área',
            'tramite.fecha_registro' => $seguimientoFinalizacion?->fecha_final?->format('d/m/Y') ?: '-',
            'tramite.funcionario_finalizador' => $usuarioFinalizador ? $this->nombreUsuarioPlantilla($usuarioFinalizador) : '-',
            'tramite.funcionario_finalizador_cargo' => $usuarioFinalizador ? $this->cargoFuncionarioUsuario($usuarioFinalizador) : '-',

            'beneficiario.nombre' => $beneficiario ? $this->nombrePersona($beneficiario) : 'Sin beneficiario',
            'beneficiario.documento' => $this->documentoPersona($beneficiario),
            'beneficiario.expedido' => $this->expedidoPersona($beneficiario),
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
            'empresa.representante_legal_expedido' => $this->expedidoPersona($personaRepresentanteLegal),
            'natural.nombres' => $beneficiario?->natural?->nombres ?: 'Sin nombres',
            'natural.apellido_paterno' => $beneficiario?->natural?->apellido_paterno ?: 'Sin apellido paterno',
            'natural.apellido_materno' => $beneficiario?->natural?->apellido_materno ?: 'Sin apellido materno',
            'natural.ocupacion' => $beneficiario?->natural?->ocupacionCob?->descripcion_ocupacion ?: 'Sin ocupación',

            'tramitador.nombre' => $tramitador ? $this->nombrePersona($tramitador) : 'Sin tramitador',
            'tramitador.documento' => $this->documentoPersona($tramitador),
            'tramitador.expedido' => $this->expedidoPersona($tramitador),
            'tramitador.correo' => $tramitador?->correo ?: 'Sin correo',
            'tramitador.telefono' => $tramitador?->telefonos?->first()?->numero ?: 'Sin teléfono',
            'tramitador.rol' => $relacionTramitador?->rol?->name ?: 'Sin rol empresarial',
            'tramitador.respaldo' => $relacionTramitador?->url_respaldo ?: 'Sin respaldo',

            'importador.nombre' => $importadorProducto
                ? $this->nombrePersona($importadorProducto)
                : 'Sin importador',
            'importador.documento' => $this->documentoPersona($importadorProducto),
            'importador.expedido' => $this->expedidoPersona($importadorProducto),
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
            'importador.representante_legal_expedido' => $this->expedidoPersona($representanteImportador),

            'producto.tabla' => $resumenProductos->filter()->implode("\n") ?: 'Sin productos',
            'producto.nombres_comerciales' => $this->unirValoresPlantilla($productos->pluck('nombre_comercial')),
            'producto.codigos' => $this->unirValoresPlantilla($productos->pluck('codigo')),
            'producto.nombres_tecnicos' => $this->unirValoresPlantilla($productos->pluck('nombre_tecnico')),
            'producto.clasificaciones_quimicas' => $this->unirValoresPlantilla($productos->map(fn ($producto) => $producto?->clasificacionQuimica?->nombre)),
            'fabricante.nombres' => $this->unirValoresPlantilla($productos->map(fn ($producto) => $producto?->fabricante?->nombre)),
            'clasificacion_toxicologica.codigos' => $this->unirValoresPlantilla($productos->map(fn ($producto) => $producto?->clasificacionToxicologica?->codigo)),
            'producto.codigo' => $productoIndividual?->codigo ?: '-',
            'producto.nombre_comercial' => $productoIndividual?->nombre_comercial ?: '-',
            'producto.nombre_tecnico' => $productoIndividual?->nombre_tecnico ?: '-',
            'producto.tipo_producto' => $productoIndividual?->tipo_producto ?: '-',
            'producto.pais_origen' => $productoIndividual?->territorio?->nombre ?: '-',
            'producto.clasificacion_quimica' => $productoIndividual?->clasificacionQuimica?->nombre ?: '-',
            'producto.clasificacion_quimica_descripcion' => $productoIndividual?->clasificacionQuimica?->descripcion ?: '-',
            'fabricante.nombre' => $productoIndividual?->fabricante?->nombre
                ?: ($productoIndividual?->fabricante?->razon_social ?: '-'),
            'fabricante.razon_social' => $productoIndividual?->fabricante?->razon_social
                ?: ($productoIndividual?->fabricante?->nombre ?: '-'),
            'fabricante.descripcion' => $productoIndividual?->fabricante?->descripcion ?: '-',
            'clasificacion_toxicologica.codigo' => $productoIndividual?->clasificacionToxicologica?->codigo ?: '-',
            'clasificacion_toxicologica.descripcion' => $productoIndividual?->clasificacionToxicologica?->descripcion ?: '-',
            'producto.observacion_toxicologica_muestra' => $productoIndividual?->observacion_toxicologica_muestra ?: '-',
            'clasificacion_toxicologica.completa' => trim(implode(' - ', array_filter([
                $productoIndividual?->clasificacionToxicologica?->codigo,
                $productoIndividual?->clasificacionToxicologica?->descripcion,
            ]))) ?: '-',
            'clasificacion_toxicologica.con_observacion' => trim(implode(' | ', array_filter([
                trim(implode(' - ', array_filter([
                    $productoIndividual?->clasificacionToxicologica?->codigo,
                    $productoIndividual?->clasificacionToxicologica?->descripcion,
                ]))),
                $productoIndividual?->observacion_toxicologica_muestra,
            ]))) ?: '-',

            'registro.codigos' => $this->unirValoresPlantilla($registros->pluck('codigo_autorizacion')),
            'registro.lotes' => $this->unirValoresPlantilla($registros->pluck('numero_lote')),
            'registro.fechas_vigencia' => $this->unirValoresPlantilla($registros->map(fn ($registro) => $registro->fecha_vigencia ? \Illuminate\Support\Carbon::parse($registro->fecha_vigencia)->format('d/m/Y') : null)),
            'registro.cantidades' => $this->unirValoresPlantilla($registros->map(fn ($registro) => $this->formatearDecimalPlantilla($registro->cantidad))),
            'registro.unidades' => $this->unirValoresPlantilla($registros->map(fn ($registro) => $this->textoCatalogoUnidad($registro->catalogoUnidad))),
            'presentacion.descripciones' => $this->unirValoresPlantilla($presentaciones->pluck('descripcion')),
            'presentacion.lista' => $presentacionesCompletas,
            'presentacion.cantidades' => $this->unirValoresPlantilla($presentaciones->map(fn ($presentacion) => $this->formatearDecimalPlantilla($presentacion->cantidad))),
            'presentacion.unidades' => $this->unirValoresPlantilla($presentaciones->map(fn ($presentacion) => $this->textoCatalogoUnidad($presentacion?->catalogoUnidad))),
            'registro.codigo' => $registroIndividual?->codigo_autorizacion ?: '-',
            'registro.numero_lote' => $registroIndividual?->numero_lote ?: '-',
            'registro.fecha_vigencia' => $registroIndividual?->fecha_vigencia ? \Illuminate\Support\Carbon::parse($registroIndividual->fecha_vigencia)->format('d/m/Y') : '-',
            'registro.cantidad' => $registroIndividual?->cantidad !== null ? $this->formatearDecimalPlantilla($registroIndividual->cantidad) : '-',
            'registro.unidad' => $registroIndividual ? $this->textoCatalogoUnidad($registroIndividual->catalogoUnidad) : '-',
            'presentacion.descripcion' => $registroIndividual?->presentacion?->descripcion ?: '-',
            'presentacion.tipo_presentacion' => $registroIndividual?->presentacion?->tipo_presentacion ?: '-',
            'presentacion.cantidad' => $registroIndividual ? $this->formatearDecimalPlantilla($registroIndividual->presentacion?->cantidad) : '-',
            'presentacion.unidad' => $registroIndividual ? $this->textoCatalogoUnidad($registroIndividual->presentacion?->catalogoUnidad) : '-',
            'presentacion.url_etiqueta' => $registroIndividual?->presentacion?->url_etiqueta ?: '-',

            'ingrediente.tabla' => $ingredientesProducto->map(function ($relacion) {
                return collect([
                    $relacion->ingrediente?->nombre,
                    $relacion->ingrediente?->composicion,
                    $this->formatearDecimalPlantilla($relacion->porcentaje).'%',
                ])->filter()->implode(' | ');
            })->filter()->implode("\n") ?: 'Sin ingredientes',
            'ingrediente.nombre' => $primerIngredienteProducto?->ingrediente?->nombre ?: 'Sin ingrediente',
            'ingrediente.nombres' => $this->unirValoresPlantilla($ingredientesProducto->map(fn ($relacion) => $relacion->ingrediente?->nombre)),
            'ingrediente.formula_quimica' => $formulaQuimicaIndividual,
            'ingrediente.formulas_quimicas' => $formulasQuimicasConIngredientes,
            'ingrediente.composicion' => $primerIngredienteProducto?->ingrediente?->composicion ?: 'Sin composición',
            'ingrediente.composiciones' => $this->unirValoresPlantilla($ingredientesProducto->map(fn ($relacion) => $relacion->ingrediente?->composicion)),
            'ingrediente.riesgo_salud' => $primerIngredienteProducto?->ingrediente?->riesgo_salud ?: 'Sin riesgo registrado',
            'ingrediente.riesgos_salud' => $this->unirValoresPlantilla($ingredientesProducto->map(fn ($relacion) => $relacion->ingrediente?->riesgo_salud)),
            'ingrediente.porcentaje' => $primerIngredienteProducto
                ? $this->formatearDecimalPlantilla($primerIngredienteProducto->porcentaje).' %'
                : 'Sin porcentaje',
            'ingrediente.porcentajes' => $this->unirValoresPlantilla($ingredientesProducto->map(fn ($relacion) => $this->formatearDecimalPlantilla($relacion->porcentaje).' %')),
            'ingrediente.resumen' => $this->unirValoresPlantilla($ingredientesConPorcentaje),
            'ingrediente.composicion_cuantitativa' => $composicionCuantitativa,
            'frases.riesgo.codigos' => $this->unirValoresPlantilla($frasesRiesgo->pluck('codigo')),
            'frases.riesgo.completas' => $textoFrasesReglamentarias($frasesRiesgo),
            'frases.seguridad.codigos' => $this->unirValoresPlantilla($frasesSeguridad->pluck('codigo')),
            'frases.seguridad.completas' => $textoFrasesReglamentarias($frasesSeguridad),

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
            'requisito.observacion' => $ultimaObservacion?->observacion ?: 'Sin observación',
            'evidencia.valor' => $primeraEvidencia?->valor ?: 'Sin evidencia',

            'seguimiento.tabla' => $resumenSeguimientos->filter()->implode("\n") ?: 'Sin seguimientos',
            'seguimiento.fecha_inicio' => $ultimoSeguimiento?->fecha_inicio ? \Illuminate\Support\Carbon::parse($ultimoSeguimiento->fecha_inicio)->format('d/m/Y H:i') : 'Sin fecha',
            'seguimiento.fecha_derivacion' => $ultimoSeguimiento?->fecha_derivacion ? \Illuminate\Support\Carbon::parse($ultimoSeguimiento->fecha_derivacion)->format('d/m/Y H:i') : 'Sin fecha',
            'seguimiento.referencia' => $ultimoSeguimiento?->referencia ?: 'Sin referencia',
            'seguimiento.descripcion_final' => $ultimoSeguimiento?->descripcion_final ?: 'Sin descripción',
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
            return $certificado->emisiones()->vigentes()->exists();
        }

        $lotes = $certificado->certificadosRegistros()
            ->whereHas('registro', fn ($query) => $query->whereNotNull('numero_lote')->where('numero_lote', '!=', ''))
            ->count();

        return $lotes > 0 && $certificado->emisiones()
            ->vigentes()
            ->whereNotNull('id_certificado_registro')
            ->distinct('id_certificado_registro')
            ->count('id_certificado_registro') >= $lotes;
    }

    // Las instalaciones existentes aplican este cambio con SQL manual. Antes
    // de permitir una anulación se verifica la estructura para no perder el
    // motivo ni la trazabilidad del funcionario responsable.
    private function camposAnulacionDisponibles(): bool
    {
        return Schema::hasColumns('certificados_emitidos', [
            'fecha_anulacion',
            'id_usuario_anulacion',
            'motivo_anulacion',
        ]);
    }

    // Reserva el correlativo dentro de la misma transacción. El número se
    // conserva aun si el documento fue anulado, por lo que nunca se reutiliza.
    private function siguienteNumeroCertificado(TipoCertificado $tipoCertificado): string
    {
        $tipoBloqueado = TipoCertificado::query()->lockForUpdate()->findOrFail($tipoCertificado->id);
        $correlativo = max(1, (int) ($tipoBloqueado->siguiente_correlativo ?? 1));
        $prefijo = trim((string) ($tipoBloqueado->prefijo_numero_certificado ?? ''));

        do {
            $numero = $prefijo === '' ? (string) $correlativo : $prefijo.$correlativo;
            $correlativo++;
        } while (CertificadoEmitido::withTrashed()
            ->where('numero_certificado', $numero)
            ->exists());

        $tipoBloqueado->update(['siguiente_correlativo' => $correlativo]);

        return $numero;
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

    /**
     * Crea una sola frase por tipo de presentación para evitar textos repetidos
     * como "Envase de 100 ml, Envase de 210 ml".
     */
    private function resumenPresentacionesPlantilla($presentaciones): string
    {
        $grupos = collect($presentaciones)
            ->filter()
            ->groupBy(function ($presentacion) {
                $tipo = trim((string) $presentacion->tipo_presentacion);

                return mb_strtolower($tipo !== '' ? $tipo : 'Presentación');
            });

        $resumen = $grupos->map(function ($presentacionesTipo) {
            $primeraPresentacion = $presentacionesTipo->first();
            $tipo = trim((string) $primeraPresentacion?->tipo_presentacion) ?: 'Presentación';
            $descripciones = $presentacionesTipo
                ->map(fn ($presentacion) => trim((string) $presentacion->descripcion))
                ->filter()
                ->unique()
                ->values();
            $descripcionComun = $descripciones->count() === 1 ? $descripciones->first() : null;
            $detalles = $presentacionesTipo->map(function ($presentacion) use ($descripcionComun) {
                $cantidad = $this->formatearDecimalPlantilla($presentacion->cantidad);
                $unidad = $this->textoCatalogoUnidad($presentacion->catalogoUnidad);
                $descripcion = trim((string) $presentacion->descripcion);
                $detalle = trim(implode(' ', array_filter([
                    $cantidad !== '-' ? 'de '.$cantidad : null,
                    $unidad !== 'Sin unidad' ? $unidad : null,
                ])));

                if ($descripcionComun === null && $descripcion !== '') {
                    $detalle = trim($detalle.' ('.$descripcion.')');
                }

                return $detalle;
            })->filter()->unique()->values();

            if ($detalles->isEmpty()) {
                return $tipo;
            }

            $resultado = $tipo.' '.$this->unirListaNaturalPlantilla($detalles);

            return $descripcionComun !== null ? $resultado.' ('.$descripcionComun.')' : $resultado;
        })->filter()->values();

        return $resumen->isEmpty() ? 'Sin presentación' : $resumen->implode('; ');
    }

    private function unirListaNaturalPlantilla($valores): string
    {
        $valores = collect($valores)
            ->filter(fn ($valor) => filled($valor))
            ->unique()
            ->values();

        if ($valores->count() <= 1) {
            return (string) $valores->first();
        }

        if ($valores->count() === 2) {
            return $valores->first().' y '.$valores->last();
        }

        return $valores->slice(0, -1)->implode(', ').' y '.$valores->last();
    }

    private function formatearFormulaQuimicaPlantilla(?string $formula): string
    {
        return strtr(trim((string) $formula), [
            '0' => '₀',
            '1' => '₁',
            '2' => '₂',
            '3' => '₃',
            '4' => '₄',
            '5' => '₅',
            '6' => '₆',
            '7' => '₇',
            '8' => '₈',
            '9' => '₉',
        ]);
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
            return '-';
        }

        if ($persona->empresa) {
            return $persona->nit ?: '-';
        }

        $ci = trim((string) $persona->natural?->ci);
        $complemento = trim((string) $persona->natural?->complemento);

        if ($ci !== '') {
            return $ci.($complemento !== '' ? '-'.$complemento : '');
        }

        return $persona->nit ?: '-';
    }

    // Solo aplica a personas naturales. El certificado muestra únicamente el código expedido.
    private function expedidoPersona(?Persona $persona): string
    {
        $codigo = $persona?->natural?->expedido;

        if (! $codigo) {
            return '-';
        }

        return $codigo;
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
