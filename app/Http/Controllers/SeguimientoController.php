<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Certificado;
use App\Models\DependenciaRequisito;
use App\Models\EvidenciaRequisito;
use App\Models\Funcionario;
use App\Models\NotificacionTramite;
use App\Models\ObservacionRequisito;
use App\Models\Persona;
use App\Models\RequisitoCertificado;
use App\Models\RequisitoTipoCertificado;
use App\Models\Responsable;
use App\Models\RevisionRequisito;
use App\Models\Role;
use App\Models\Seguimiento;
use App\Models\TipoCertificado;
use App\Models\TipoEvidencia;
use App\Models\User;
use App\Services\GestionTramitadoresService;
use App\Services\ExpedienteTramiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Pago;

class SeguimientoController extends Controller
{
    private const TOKENS_INICIO_TRAMITE = 'tokens_inicio_tramite';

    private const LONGITUD_MAXIMA_EVIDENCIA_TEXTO = 50;

    // Este servicio concentra reglas de beneficiario/tramitador.
    // Se usa para saber si un usuario puede ver, corregir o enviar tramites de una empresa.
    public function __construct(
        private GestionTramitadoresService $gestionTramitadores,
        private ExpedienteTramiteService $expedientesTramites
    ) {}

    public function index(Request $request)
    {
        $configuracion = $this->configuracionBandejaTramites($request);
        $bandeja = $configuracion['bandeja'];

        // Las vistas internas no deben abrirse para solicitantes externos.
        if ($configuracion['requiere_funcionario'] && ! $this->usuarioPuedeAtenderTramites()) {
            return redirect()
                ->route('seguimientos_mis_tramites_beneficiario')
                ->with('error', 'Su usuario solo puede consultar las solicitudes que envio.');
        }

        return view($configuracion['vista'], [
            'bandeja' => $bandeja,
            'tituloPagina' => $configuracion['titulo'],
            'descripcionPagina' => $configuracion['descripcion'],
        ]);
    }

    // Relaciona cada opción del menú Trámites con su vista y el filtro de su tabla.
    // Al agregar otra bandeja, se registra aquí y luego se crea su filtro en SeguimientoTable.
    private function configuracionBandejaTramites(Request $request): array
    {
        return match (true) {
            // Menú: Mis trámites. Solo muestra los trámites del beneficiario o tramitador externo.
            $request->routeIs('seguimientos_mis_tramites_beneficiario') => [
                'bandeja' => 'enviadas',
                'titulo' => 'Mis trámites',
                'descripcion' => 'Consulte los trámites que inició y revise en qué etapa se encuentran.',
                'vista' => 'seguimientos_certificados.mis_tramites_beneficiario.index',
                'requiere_funcionario' => false,
            ],

            // Menú: Trámites registrados. Solo muestra los trámites creados por el funcionario actual.
            $request->routeIs('seguimientos_tramites_registrados_funcionario') => [
                'bandeja' => 'registrados',
                'titulo' => 'Trámites registrados',
                'descripcion' => 'Consulte los trámites que registró personalmente desde INSO.',
                'vista' => 'seguimientos_certificados.tramites_registrados_funcionario.index',
                'requiere_funcionario' => true,
            ],

            // Menú: Seguimiento de Trámites. Consulta interna de solo lectura para cualquier trámite.
            $request->routeIs('seguimientos_todos') => [
                'bandeja' => 'todos',
                'titulo' => 'Seguimiento de Trámites',
                'descripcion' => 'Listado de solo lectura para hacer seguimiento de cualquier trámite registrado.',
                'vista' => 'seguimientos_certificados.seguimiento_tramite.index',
                'requiere_funcionario' => true,
            ],

            // Menú: Trámites finalizados. Muestra los casos cerrados y listos para imprimir.
            $request->routeIs('seguimientos_finalizados') => [
                'bandeja' => 'finalizados',
                'titulo' => 'Trámites finalizados',
                'descripcion' => 'Listado de trámites cerrados y listos para imprimir su certificado.',
                'vista' => 'seguimientos_certificados.tramites_finalizados.index',
                'requiere_funcionario' => true,
            ],

            // Menú: Trámites para atender. Es la bandeja de trabajo de quien recibe el trámite.
            default => [
                'bandeja' => 'recibidas',
                'titulo' => 'Trámites para atender',
                'descripcion' => 'Bandeja de trabajo para revisar, asignar o derivar trámites que llegaron a su usuario.',
                'vista' => 'seguimientos_certificados.tramites_atender.index',
                'requiere_funcionario' => true,
            ],
        };
    }

    // Abre el formulario para iniciar una solicitud de tramite.
    public function create()
    {
        // El inicio de tramite solo requiere solicitante, tipo y documentos.
        // Si el certificado requiere pago, el trámite se guarda pendiente antes de enviarlo a revisión.
        return view('seguimientos_certificados.nuevo_tramite.create', array_merge(
            $this->datosFormularioTramite(),
            ['tokenFormulario' => $this->crearTokenEnvioTramite()]
        ));
    }

    // Registra el tramite inicial, sus requisitos y el primer movimiento de seguimiento.
    // Flujo:
    // 1. Valida los datos del formulario.
    // 2. Obtiene el tipo de certificado y sus requisitos.
    // 3. Verifica si el trámite requiere pago.
    // 4. Registra el certificado y sus requisitos.
    // 5. Si requiere pago, crea el pago pendiente.
    // 6. Si no requiere pago, envía el trámite al área responsable.
    public function store(Request $solicitud)
    {
        // 1. Validar y preparar los datos enviados desde el formulario.
        $datos = $this->validarDatosInicialesDelTramite($solicitud);

        // 2. Obtener la configuración del tipo de certificado seleccionado.
        $tipoCertificado = $this->buscarTipoCertificadoActivo(
            (int) $datos['form_id_tipo_certificado']
        );

        $asignacionesRequisitos = $this->requisitosConfiguradosDelTipoCertificado(
            (int) $tipoCertificado->id
        );

        // 3. Saber si el trámite debe esperar un pago antes de entrar a revisión.
        $tramiteRequierePago = $this->tramiteRequierePago($asignacionesRequisitos);

        $this->validarCostoDelCertificado($tipoCertificado, $tramiteRequierePago);
        $this->validarArchivosSubidosDeRequisitos($solicitud, $asignacionesRequisitos);
        $this->validarTextosIngresadosDeRequisitos($solicitud, $asignacionesRequisitos);
        $this->validarTokenEnvioTramite($solicitud);

        try {
            DB::beginTransaction();

            // 4. Definir el estado inicial y, cuando corresponda, el funcionario receptor.
            $estadoInicial = $tramiteRequierePago ? 'PENDIENTE_PAGO' : 'EN_REVISION';
            [$areaDestino, $usuarioDestino] = $this->obtenerDestinoInicialDelTramite(
                $tipoCertificado,
                $tramiteRequierePago
            );

            // 5. Registrar la solicitud principal.
            $certificado = $this->registrarCertificado(
                $solicitud,
                $datos,
                $tipoCertificado,
                $estadoInicial
            );

            // 6. Guardar los documentos y textos correspondientes a los requisitos.
            $this->guardarRequisitosTramite(
                $certificado,
                $asignacionesRequisitos,
                $this->posicionesDeArchivosPorRequisitoConfigurado($solicitud),
                $solicitud->file('documentos_requisitos', []),
                $this->valoresTextoPorRequisitoConfigurado($solicitud)
            );

            if ($tramiteRequierePago) {
                // Registra el monto que deberá pagarse mediante OpenBCB.
                $this->registrarPagoPendiente($certificado, $tipoCertificado);

                // Permite encontrar el trámite aunque el usuario salga sin pagar.
                $this->registrarSeguimientoPendientePago($certificado);
            } 
            else {
                // El trámite entra directamente al flujo de revisión.
                $this->registrarSeguimientoInicial($certificado, $usuarioDestino);
                $this->notificarTramiteRecibido($certificado, $usuarioDestino, $areaDestino);
            }

            DB::commit();
            $this->consumirTokenEnvioTramite($solicitud);

            return $this->respuestaDespuesDeRegistrarTramite($solicitud, $certificado, $tramiteRequierePago);
        } 
        catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        }
        catch (\Exception $e) {
            DB::rollBack();

            return $this->respuestaErrorRegistro($solicitud, $e);
        }
    }

    // Valida los datos principales del formulario y determina quién realiza el trámite.
    private function validarDatosInicialesDelTramite(Request $solicitud): array
    {
        $datos = $this->validarTramite($solicitud);

        // Evita que desde el navegador se intente registrar un trámite para
        // un beneficiario que el usuario autenticado no tiene permitido utilizar.
        $this->validarQueUsuarioPuedeRegistrarBeneficiario(
            $solicitud->user(),
            (int) $datos['form_id_persona_beneficiario']
        );

        // El tramitador se determina nuevamente en el backend por seguridad.
        $datos['form_id_persona_tramitador'] = $this->resolverTramitadorDelBeneficiario(
            $solicitud->user(),
            (int) $datos['form_id_persona_beneficiario'],
            isset($datos['form_id_persona_tramitador'])
                ? (int) $datos['form_id_persona_tramitador']
                : null
        );

        $this->validarCertificadosPrevios(
            (int) $datos['form_id_persona_beneficiario'],
            (int) $datos['form_id_tipo_certificado']
        );

        return $datos;
    }

    // Indica si los requisitos del certificado incluyen un pago.
    private function tramiteRequierePago(Collection $asignacionesRequisitos): bool
    {
        return $asignacionesRequisitos->contains(function ($asignacionRequisito) {
            return strtoupper((string) $asignacionRequisito->tipoEvidencia?->codigo) === 'PAGO';
        });
    }

    // Verifica que exista un costo válido cuando el certificado requiere pago.
    private function validarCostoDelCertificado(TipoCertificado $tipoCertificado, bool $tramiteRequierePago): void
    {
        if ($tramiteRequierePago && (float) $tipoCertificado->costo <= 0) {
            throw ValidationException::withMessages([
                'form_id_tipo_certificado' => 'Este tipo de certificado no tiene un costo configurado.',
            ]);
        }
    }

    // Obtiene el área y funcionario que recibirán inicialmente el trámite.
    // Si existe un pago pendiente, todavía no se asigna a ningún funcionario.
    private function obtenerDestinoInicialDelTramite(TipoCertificado $tipoCertificado, bool $tramiteRequierePago): array
    {
        $areaDestino = $tipoCertificado->area;

        if (! $areaDestino) {
            throw new \Exception('El tipo de certificado no tiene un area configurada para iniciar el tramite.');
        }

        if ((string) $areaDestino->estado !== '1' && (string) $areaDestino->estado !== 'ACTIVO') {
            throw new \Exception('El area configurada para este tipo de certificado no esta activa.');
        }

        $usuarioDestino = $this->usuarioReceptorArea((int) $areaDestino->id);

        if (! $usuarioDestino) {
            throw ValidationException::withMessages([
                'form_id_tipo_certificado' => 'No se tiene un responsable para atender el trámite.',
            ]);
        }

        if ($tramiteRequierePago) {
            return [null, null];
        }

        return [$areaDestino, $usuarioDestino];
    }

    // Registra la solicitud principal en la tabla certificados.
    private function registrarCertificado(
        Request $solicitud,
        array $datos,
        TipoCertificado $tipoCertificado,
        string $estadoInicial
    ): Certificado {
        $descripcion = $this->descripcionTramite(
            $datos['form_descripcion'] ?? null,
            $solicitud->input('requisitos_certificados', [])
        );

        return Certificado::create([
            'id_tipo_certificado' => $tipoCertificado->id,
            'id_persona_beneficiario' => $datos['form_id_persona_beneficiario'],
            'id_persona_tramitador' => $datos['form_id_persona_tramitador'],
            'descripcion' => $descripcion,
            'estado' => $estadoInicial,
        ]);
    }

    // Crea el pago pendiente y lo relaciona con el certificado.
    private function registrarPagoPendiente(Certificado $certificado, TipoCertificado $tipoCertificado): void{
        $pago = Pago::create([
            'id_procedencia' => 1,
            'tipo_pago' => 'QR',
            'fecha' => now()->toDateString(),
            'comprobante' => null,
            'monto' => $tipoCertificado->costo,
            'id_cliente' => $certificado->id_persona_beneficiario,
            'id_funcionario' => null,
            'fecha_validacion' => null,
            'factura' => null,
        ]);

        $certificado->pagos()->attach($pago->id, [
            'id_usuario_registro' => auth()->id(),
            'id_usuario_modificacion' => auth()->id(),
        ]);
    }

    // Registra el trámite sin asignarlo a revisión mientras falta el pago.
    private function registrarSeguimientoPendientePago(Certificado $certificado): void {
        Seguimiento::create([
            'id_seguimiento_padre' => null,
            'id_certificado' => $certificado->id,
            'fecha_inicio' => now(),
            'fecha_derivacion' => null,
            'fecha_final' => null,
            'descripcion_final' => null,
            'referencia' => null,
            'id_usuario_anterior' => null,

            // Usuario que inició el trámite.
            'id_usuario_origen' => auth()->id(),

            // Todavía no se asigna a un funcionario.
            'id_usuario_siguiente' => null,

            // Espera la confirmación del pago.
            'estado' => 'PENDIENTE_PAGO',
        ]);
    }

    // Registra el primer movimiento del seguimiento cuando el trámite entra a revisión.
    private function registrarSeguimientoInicial(Certificado $certificado, User $usuarioDestino): void
    {
        Seguimiento::create([
            'id_seguimiento_padre' => null,
            'id_certificado' => $certificado->id,
            'fecha_inicio' => now(),
            'fecha_derivacion' => null,
            'fecha_final' => null,
            'descripcion_final' => null,
            'referencia' => null,
            'id_usuario_anterior' => null,
            'id_usuario_origen' => auth()->id(),
            'id_usuario_siguiente' => $usuarioDestino->id,
            'estado' => 'ACTIVO',
        ]);
    }

    // Envía al usuario al pago o a su bandeja, según los requisitos del trámite.
    private function respuestaDespuesDeRegistrarTramite(Request $solicitud, Certificado $certificado, bool $tramiteRequierePago) {
        if ($tramiteRequierePago) {
            session()->flash('swal', [
                'title' => 'Trámite guardado',
                'text' => 'La solicitud fue guardada. Ahora debe completar el pago.',
                'icon' => 'success',
            ]);

            $urlDestino = route('pagos_openbcb_mostrar_pago', [
                'certificado' => $certificado->id,
            ]);
        } else {
            session()->flash('swal', [
                'title' => 'Trámite registrado',
                'text' => 'La solicitud fue enviada al área correspondiente.',
                'icon' => 'success',
            ]);

            $rutaDestino = $this->rutaDespuesDeRegistrarTramite(
                $solicitud->user()
            );

            $urlDestino = route($rutaDestino);
        }

        // El formulario puede enviar una petición normal o mediante JavaScript.
        if ($solicitud->expectsJson()) {
            return response()->json([
                'redirect' => $urlDestino,
            ]);
        }

        return redirect()->to($urlDestino);
    }

    // Devuelve la respuesta cuando ocurre un error durante el registro.
    private function respuestaErrorRegistro(Request $solicitud, \Exception $e)
    {
        if ($solicitud->expectsJson()) {
            return response()->json([
                'message' => 'No se pudo iniciar el tramite.',
                'errors' => [
                    'formulario' => ['No se pudo iniciar el tramite. '.$e->getMessage()],
                ],
            ], 422);
        }

        return back()
            ->with('error', 'No se pudo iniciar el tramite. '.$e->getMessage())
            ->withInput();
    }

    // Crea un token de un solo uso para evitar registros repetidos por doble clic o reenvio del formulario.
    private function crearTokenEnvioTramite(): string
    {
        $token = (string) Str::uuid();
        $tokens = session(self::TOKENS_INICIO_TRAMITE, []);
        $tokens[$token] = true;

        // Se conservan los ultimos tokens por si el usuario abre mas de una pestaña del formulario.
        session()->put(self::TOKENS_INICIO_TRAMITE, array_slice($tokens, -10, null, true));

        return $token;
    }

    // Comprueba que el formulario siga vigente, pero permite corregir errores sin generar otro token.
    private function validarTokenEnvioTramite(Request $request): void
    {
        $token = $request->input('form_token');
        $tokens = session(self::TOKENS_INICIO_TRAMITE, []);

        if (! $token || ! isset($tokens[$token])) {
            throw ValidationException::withMessages([
                'form_token' => 'La solicitud ya fue enviada o el formulario vencio. Actualice la pantalla e intente nuevamente.',
            ]);
        }

    }

    // Se elimina únicamente después de guardar el trámite para impedir un segundo envío exitoso.
    private function consumirTokenEnvioTramite(Request $request): void
    {
        $token = $request->input('form_token');
        $tokens = session(self::TOKENS_INICIO_TRAMITE, []);

        unset($tokens[$token]);
        session()->put(self::TOKENS_INICIO_TRAMITE, $tokens);
    }

    // Abre el detalle completo del tramite desde la fila de seguimiento.
    public function show(Seguimiento $seguimiento)
    {
        // Ver tramite usa el detalle existente del certificado.
        // No se crea otra vista para mantener el flujo en los archivos ya existentes.
        if (! $seguimiento->certificado) {
            return redirect()
                ->route('seguimientos_index')
                ->with('error', 'No se encontro el certificado relacionado al tramite.');
        }

        return redirect()->route('certificados_show', [
            'certificado' => $seguimiento->certificado,
            'bandeja' => request('bandeja', 'recibidas'),
        ]);
    }

    // Muestra el historial del tramite sin cargar la pantalla de revision tecnica.
    public function historial(Seguimiento $seguimiento, Request $request)
    {
        if (! $seguimiento->certificado) {
            return redirect()
                ->route('seguimientos_index')
                ->with('error', 'No se encontro el certificado relacionado al tramite.');
        }

        // Se cargan solo las relaciones necesarias para reconstruir origen, receptor y derivaciones.
        $certificado = $seguimiento->certificado()
            ->with($this->relacionesHistorialTramite())
            ->firstOrFail();

        $usuarioActual = $request->user();
        $bandeja = $request->query('bandeja', 'recibidas');

        // Seguridad del historial:
        // 1) solicitante real, 2) usuario interno participante, o 3) consulta general autorizada.
        $esSolicitante = $this->usuarioEsSolicitanteHistorial($usuarioActual, $certificado);
        $esUsuarioInterno = $this->usuarioPuedeVerBandejasInternas($usuarioActual);
        $puedeConsultaGeneral = $bandeja === 'todos' && $esUsuarioInterno;
        $participaEnSeguimiento = $this->usuarioParticipaEnHistorial($usuarioActual, $certificado, $esUsuarioInterno);

        if (! $esSolicitante && ! $participaEnSeguimiento && ! $puedeConsultaGeneral) {
            abort(403, 'No tiene permiso para ver el historial de este tramite.');
        }

        return view('seguimientos_certificados.seguimiento_tramite.historial', compact('seguimiento', 'certificado', 'bandeja'));
    }

    // RELACIONES DEL HISTORIAL DE TRAMITE
    // Mantener esta lista ayuda a modificar la pantalla sin buscar eager-loads por todo el metodo.
    private function relacionesHistorialTramite(): array
    {
        return [
            'tipoCertificado',
            'beneficiario.natural',
            'beneficiario.empresa',
            'tramitador.natural',
            'tramitador.empresa',
            'seguimientos.usuarioOrigen.funcionario.cargos',
            'seguimientos.usuarioOrigen.persona.empresa',
            'seguimientos.usuarioOrigen.persona.natural',
            'seguimientos.usuarioAnterior.funcionario.cargos',
            'seguimientos.usuarioAnterior.persona.empresa',
            'seguimientos.usuarioAnterior.persona.natural',
            'seguimientos.usuarioSiguiente.funcionario.cargos',
            'seguimientos.usuarioSiguiente.persona.empresa',
            'seguimientos.usuarioSiguiente.persona.natural',
        ];
    }

    // El solicitante puede ver el historial si su cuenta pertenece al beneficiario o al tramitador.
    private function usuarioEsSolicitanteHistorial(?User $usuario, Certificado $certificado): bool
    {
        return $usuario && $this->gestionTramitadores->usuarioPuedeConsultarTramite($usuario, $certificado);
    }

    // Usuario interno participante: quien registro, recibio o tuvo antes el tramite.
    private function usuarioParticipaEnHistorial(?User $usuario, Certificado $certificado, bool $esUsuarioInterno): bool
    {
        if (! $usuario || ! $esUsuarioInterno) {
            return false;
        }

        return $certificado->seguimientos->contains(function ($movimiento) use ($usuario) {
            return in_array((int) $usuario->id, [
                (int) $movimiento->id_usuario_origen,
                (int) $movimiento->id_usuario_siguiente,
                (int) $movimiento->id_usuario_anterior,
            ], true);
        });
    }

    // NOTIFICACIONES DE TRAMITES
    // Devuelve las cinco notificaciones más recientes; el contador conserva solo las no leídas.
    public function notificacionesTramites(Request $request)
    {
        if (! Schema::hasTable('notificaciones_tramites')) {
            return response()->json([
                'total' => 0,
                'resumen' => [
                    'pendientes' => 0,
                    'atendidas' => 0,
                ],
                'notificaciones' => [],
            ]);
        }

        $consultaBase = NotificacionTramite::query()
            ->with(
                'usuarioEmisor.funcionario.cargos',
                'usuarioEmisor.persona.empresa',
                'usuarioEmisor.persona.natural',
                'usuarioDestino.funcionario.cargos',
                'usuarioDestino.persona.empresa',
                'usuarioDestino.persona.natural',
                'certificado.tipoCertificado',
                'certificado.beneficiario.natural',
                'certificado.beneficiario.empresa',
                'certificado.beneficiario.empresa.responsables.persona.natural',
                'certificado.tramitador.natural',
                'certificado.tramitador.empresa'
            )
            // El historial conserva las ultimas cinco, aunque ya hayan sido vistas.
            ->where('id_usuario_destino', $request->user()->id);

        $notificaciones = (clone $consultaBase)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notificacion) use ($request) {
                $certificado = $notificacion->certificado;
                $esValidacionTramitador = ! $certificado;
                $presentacion = $notificacion->datosPresentacion();
                $esSolicitante = $certificado
                    && $this->gestionTramitadores->usuarioPuedeConsultarTramite($request->user(), $certificado);
                $estadoNotificacion = mb_strtoupper((string) $notificacion->estado);
                $esAtendida = $estadoNotificacion === 'ATENDIDA';
                $datosAtencion = $esAtendida ? $notificacion->datosAtencion() : null;

                return [
                    'id' => $notificacion->id,
                    'titulo' => $notificacion->titulo,
                    'mensaje' => $notificacion->mensaje ?? '',
                    'codigo' => $certificado ? '#'.$certificado->id : '',
                    'categoria' => $esValidacionTramitador ? 'tramitador' : 'tramite',
                    'tipo' => $esValidacionTramitador ? 'Validación de tramitador' : ($certificado?->tipoCertificado?->nombre ?? 'Trámite'),
                    'beneficiario' => $esValidacionTramitador
                        ? ($notificacion->mensaje ?: 'Solicitud de tramitador')
                        : $presentacion['solicitante'],
                    'etiqueta_relacion' => $esValidacionTramitador ? 'Solicitud' : 'Solicitante',
                    'tipo_solicitante' => $presentacion['tipo_solicitante'],
                    'estado_notificacion' => mb_strtolower($estadoNotificacion ?: 'ACTIVO'),
                    'etiqueta_estado' => match ($estadoNotificacion) {
                        'ATENDIDA' => 'Atendida',
                        'VISTO' => 'Pendiente',
                        default => 'Nueva',
                    },
                    'accion' => $esValidacionTramitador
                        ? 'Ver tramitadores'
                        : ($esAtendida ? 'Ver trámite' : 'Atender solicitud'),
                    'quien_envia' => $presentacion['enviado_por'],
                    'quien_envia_detalle' => $presentacion['actua_como'],
                    'atendida_por' => $datosAtencion['nombre'] ?? null,
                    'fecha_atencion' => $esAtendida
                        ? ($notificacion->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha')
                        : null,
                    'fecha_visto' => $notificacion->fecha_visto?->format('d/m/Y H:i'),
                    'url' => $esValidacionTramitador
                        ? route('tramitadores_index')
                        : ($certificado
                        ? route('certificados_show', [
                            'certificado' => $certificado,
                            'bandeja' => $esSolicitante ? 'enviadas' : 'recibidas',
                        ])
                        : ($esSolicitante ? route('seguimientos_mis_tramites_beneficiario') : route('seguimientos_index'))),
                    'fecha' => $notificacion->created_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    'fecha_humana' => $notificacion->created_at?->diffForHumans() ?? 'Sin fecha',
                ];
            });

        return response()->json([
            'total' => (clone $consultaBase)
                ->whereNull('fecha_visto')
                ->where('estado', 'ACTIVO')
                ->count(),
            'resumen' => [
                'pendientes' => $notificaciones
                    ->whereIn('estado_notificacion', ['activo', 'visto'])
                    ->count(),
                'atendidas' => $notificaciones
                    ->where('estado_notificacion', 'atendida')
                    ->count(),
            ],
            'notificaciones' => $notificaciones,
        ]);
    }

    // Marca una notificacion como vista cuando el usuario decide atenderla.
    public function marcarNotificacionTramite(Request $request, string $notificacion)
    {
        if (! Schema::hasTable('notificaciones_tramites')) {
            return response()->json(['ok' => true]);
        }

        $notificacion = NotificacionTramite::query()
            ->where('id_usuario_destino', $request->user()->id)
            ->whereKey($notificacion)
            ->firstOrFail();

        if (! $notificacion->fecha_visto
            && mb_strtoupper((string) $notificacion->estado) !== 'ATENDIDA') {
            $notificacion->update([
                'fecha_visto' => now(),
                'estado' => 'VISTO',
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // Marca todas como vistas desde la campana.
    public function marcarTodasNotificacionesTramite(Request $request)
    {
        if (! Schema::hasTable('notificaciones_tramites')) {
            return response()->json(['ok' => true]);
        }

        NotificacionTramite::query()
            ->where('id_usuario_destino', $request->user()->id)
            ->whereNull('fecha_visto')
            ->where('estado', 'ACTIVO')
            ->update([
                'fecha_visto' => now(),
                'estado' => 'VISTO',
                'id_usuario_modificacion' => $request->user()->id,
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    // ASIGNAR TECNICO A UNA SOLICITUD
    // Crea un nuevo tramite_seguimiento para dejar historial de quien recibira la revision tecnica.
    public function asignarTecnico(Request $request, Seguimiento $seguimiento)
    {
        if (! $this->usuarioPuedeAsignarTecnico($seguimiento)) {
            abort(403, 'No tiene permiso para asignar tecnico a este tramite.');
        }

        $datos = $request->validate([
            'form_id_funcionario' => ['required', 'exists:funcionarios,id'],
            'form_descripcion_derivacion' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'form_id_funcionario' => 'funcionario',
            'form_descripcion_derivacion' => 'descripcion de derivacion',
        ]);

        // El formulario trabaja con funcionarios.id. El seguimiento conserva users.id
        // porque id_usuario_siguiente identifica la cuenta que recibira el tramite.
        $funcionario = Funcionario::query()
            ->with('usuario')
            ->whereKey($datos['form_id_funcionario'])
            ->whereHas('usuario', fn ($query) => $query->where('users.estado', 1))
            ->whereHas('cargos', fn ($query) => $query->where('cargos.estado', 1))
            ->first();

        if (! $funcionario?->usuario) {
            return back()->with('error', 'Seleccione un funcionario activo con cargo asignado.');
        }

        $usuarioFuncionario = $funcionario->usuario;

        if ((int) $usuarioFuncionario->id === (int) $seguimiento->id_usuario_siguiente) {
            return back()
                ->withErrors(['form_id_funcionario' => 'Seleccione un funcionario diferente al funcionario actual.'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $seguimientoBloqueado = Seguimiento::query()
                ->whereKey($seguimiento->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->usuarioPuedeAsignarTecnico($seguimientoBloqueado)) {
                DB::rollBack();

                return redirect()
                    ->route('seguimientos_index')
                    ->with('error', 'Este tramite ya fue derivado o no esta disponible para asignacion.');
            }

            $certificado = $seguimientoBloqueado->certificado()->firstOrFail();

            $this->cerrarEtapasActivasDelTramite((int) $seguimientoBloqueado->id_certificado);

            // La nueva etapa queda pendiente para el funcionario responsable y conserva el historial.
            Seguimiento::create([
                'id_seguimiento_padre' => $seguimientoBloqueado->id,
                'id_certificado' => $seguimientoBloqueado->id_certificado,
                'fecha_inicio' => now(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => null,
                // La referencia solo se registra cuando fue escrita en el formulario de asignación.
                'referencia' => filled($datos['form_descripcion_derivacion'] ?? null)
                    ? trim($datos['form_descripcion_derivacion'])
                    : null,
                'id_usuario_anterior' => $seguimientoBloqueado->id_usuario_siguiente ?: $seguimientoBloqueado->id_usuario_origen,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $usuarioFuncionario->id,
                'estado' => 'ACTIVO',
            ]);

            $certificado->update(['estado' => 'EN_REVISION']);

            // Aviso simple para que el tecnico vea la solicitud en su bandeja.
            $this->notificarUsuarioTramite(
                $usuarioFuncionario,
                $certificado,
                'Tramite asignado',
                'Tiene una solicitud pendiente para revision.'
            );

            $this->marcarNotificacionesAtendidas((int) $certificado->id);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Funcionario asignado',
                'text' => 'La solicitud fue derivada para revision.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo asignar el tecnico. '.$e->getMessage());
        }
    }

    // DERIVAR TRAMITE A OTRO FUNCIONARIO
    // Mantiene el mismo certificado/tramite y solo crea un nuevo movimiento en seguimientos.
    public function derivarTecnico(Request $request, Seguimiento $seguimiento)
    {
        $datos = $request->validate([
            'form_id_funcionario_destino' => ['required', 'exists:funcionarios,id'],
            'form_motivo_derivacion' => ['required', 'string', 'max:1000'],
        ], [], [
            'form_id_funcionario_destino' => 'funcionario destino',
            'form_motivo_derivacion' => 'motivo de derivacion',
        ]);

        if (! $this->usuarioPuedeRevisarSeguimiento($seguimiento)) {
            return back()->with('error', 'Solo el funcionario asignado puede derivar este tramite.');
        }

        $funcionarioDestino = Funcionario::query()
            ->with('usuario')
            ->whereKey($datos['form_id_funcionario_destino'])
            ->whereHas('usuario', fn ($query) => $query->where('users.estado', 1))
            ->whereHas('cargos', fn ($query) => $query->where('cargos.estado', 1))
            ->first();

        if (! $funcionarioDestino?->usuario) {
            return back()->with('error', 'El usuario destino debe ser un funcionario activo con cargo asignado.');
        }

        $usuarioDestino = $funcionarioDestino->usuario;

        if ((int) $usuarioDestino->id === (int) $seguimiento->id_usuario_siguiente) {
            return back()
                ->withErrors(['form_id_funcionario_destino' => 'Seleccione un funcionario diferente al funcionario actual.'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $seguimientoBloqueado = Seguimiento::query()
                ->whereKey($seguimiento->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->usuarioPuedeRevisarSeguimiento($seguimientoBloqueado)) {
                DB::rollBack();

                return redirect()
                    ->route('seguimientos_index')
                    ->with('error', 'Este tramite ya fue derivado o no esta disponible para otra derivacion.');
            }

            $this->cerrarEtapasActivasDelTramite((int) $seguimientoBloqueado->id_certificado);

            // La nueva fila deja trazabilidad: funcionario anterior, origen y destino.
            Seguimiento::create([
                'id_seguimiento_padre' => $seguimientoBloqueado->id,
                'id_certificado' => $seguimientoBloqueado->id_certificado,
                'fecha_inicio' => now(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => null,
                // En una derivación, la referencia corresponde exactamente al texto ingresado.
                'referencia' => trim($datos['form_motivo_derivacion']),
                'id_usuario_anterior' => $seguimientoBloqueado->id_usuario_siguiente,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $usuarioDestino->id,
                'estado' => 'ACTIVO',
            ]);

            $seguimientoBloqueado->certificado?->update(['estado' => 'EN_REVISION']);

            // Notifica al tecnico destino para que el cambio se refleje en su bandeja de trabajo.
            if ($seguimientoBloqueado->certificado) {
                $this->notificarUsuarioTramite(
                    $usuarioDestino,
                    $seguimientoBloqueado->certificado,
                    'Tramite derivado',
                    'Recibio una solicitud derivada para su revision.'
                );

                $this->marcarNotificacionesAtendidas((int) $seguimientoBloqueado->certificado->id);
            }
            DB::commit();

            session()->flash('swal', [
                'title' => 'Tramite derivado',
                'text' => 'La solicitud fue enviada al nuevo responsable.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo derivar el tramite. '.$e->getMessage());
        }
    }

    // REVISION TECNICA DE REQUISITOS
    // Guarda la decisión manual o la calificación, según la evidencia configurada para cada requisito.
    public function revisarTecnico(Request $request, Seguimiento $seguimiento)
    {
        $datos = $request->validate([
            'requisitos_revision' => ['required', 'array'],
            'requisitos_revision.*.id' => ['required', 'exists:requisitos_certificados,id'],
            'requisitos_revision.*.tocado' => ['nullable', 'in:0,1'],
            // La revision puede ser parcial: cada funcionario marca solo lo que reviso.
            'requisitos_revision.*.cumple' => ['nullable', 'in:SI,NO'],
            'requisitos_revision.*.calificacion_obtenida' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'requisitos_revision.*.observacion' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'requisitos_revision' => 'requisitos',
            'requisitos_revision.*.cumple' => 'cumplimiento',
            'requisitos_revision.*.calificacion_obtenida' => 'calificación obtenida',
            'requisitos_revision.*.observacion' => 'observacion tecnica',
        ]);

        if (! $this->usuarioPuedeRevisarSeguimiento($seguimiento)) {
            return back()->with('error', 'Este tramite no esta asignado al tecnico actual.');
        }

        $certificado = $seguimiento->certificado()->firstOrFail();
        $itemsTocados = collect($datos['requisitos_revision'])
            ->filter(fn ($item) => ($item['tocado'] ?? '0') === '1')
            ->values();

        if ($itemsTocados->isEmpty()) {
            return back()
                ->withErrors(['requisitos_revision' => 'Seleccione al menos un requisito para guardar la revision.'])
                ->withInput();
        }

        // Los requisitos se vuelven a buscar dentro del certificado actual para impedir ids manipulados.
        $requisitosPorId = RequisitoCertificado::query()
            ->where('id_certificado', $certificado->id)
            ->whereIn('id', $itemsTocados->pluck('id'))
            ->get()
            ->keyBy('id');

        if ($requisitosPorId->count() !== $itemsTocados->count()) {
            throw ValidationException::withMessages([
                'requisitos_revision' => 'Uno de los requisitos no pertenece a este tramite.',
            ]);
        }

        $configuracionesPorRequisito = RequisitoTipoCertificado::query()
            ->with('tipoEvidencia')
            ->where('id_tipo_certificado', $certificado->id_tipo_certificado)
            ->whereIn('id_requisito', $requisitosPorId->pluck('id_requisito'))
            ->where('estado', 'ACTIVO')
            ->get()
            ->keyBy('id_requisito');

        $requisitosMarcados = $itemsTocados->map(function ($item) use ($requisitosPorId, $configuracionesPorRequisito) {
            $requisito = $requisitosPorId->get((int) $item['id']);
            $configuracion = $configuracionesPorRequisito->get((int) $requisito->id_requisito);
            $esCalificacion = mb_strtoupper((string) $configuracion?->tipoEvidencia?->codigo) === 'CALIFICACION';
            // Una decision vacia enviada como campo tocado restablece el requisito a pendiente.
            $esPendiente = blank($item['cumple'] ?? null)
                && (! $esCalificacion || blank($item['calificacion_obtenida'] ?? null));

            if ($esCalificacion && ! $esPendiente && blank($item['calificacion_obtenida'] ?? null)) {
                throw ValidationException::withMessages([
                    'requisitos_revision' => 'Ingrese la calificación obtenida antes de guardar la revisión.',
                ]);
            }

            if ($esCalificacion && ! $esPendiente && $configuracion?->calificacion_minima_aprobacion === null) {
                throw ValidationException::withMessages([
                    'requisitos_revision' => 'El requisito de calificación no tiene configurada una calificación mínima de aprobación.',
                ]);
            }

            return array_merge($item, [
                '_requisito' => $requisito,
                '_configuracion' => $configuracion,
                '_es_calificacion' => $esCalificacion,
                '_es_pendiente' => $esPendiente,
            ]);
        });

        $observacionesPendientes = $requisitosMarcados
            ->filter(fn ($item) => ($item['cumple'] ?? null) === 'NO' && blank($item['observacion'] ?? null));

        if ($observacionesPendientes->isNotEmpty()) {
            return back()
                ->withErrors(['requisitos_revision' => 'Todo requisito marcado como No cumple debe tener observacion tecnica.'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $hayObservados = false;

            foreach ($requisitosMarcados as $item) {
                $requisitoCertificado = $item['_requisito'];
                $esCalificacion = $item['_es_calificacion'];
                $esPendiente = $item['_es_pendiente'];
                $calificacionObtenida = $esCalificacion && ! $esPendiente
                    ? round((float) $item['calificacion_obtenida'], 2)
                    : null;
                // Una calificación registrada completa el requisito; el mínimo define el resultado del certificado.
                $cumple = $esPendiente ? null : ($esCalificacion ? 'SI' : $item['cumple']);
                $observacion = trim($item['observacion'] ?? '');

                $requisitoCertificado->update([
                    'cumple' => $cumple,
                    // REVISION_OBSERVADA guarda la observacion sin notificar todavia al solicitante.
                    'estado' => $esPendiente
                        ? 'PENDIENTE_REVISION'
                        : ($cumple === 'SI' ? 'ACTIVO' : 'REVISION_OBSERVADA'),
                ]);

                // Cada revision queda registrada como historial tecnico del requisito.
                $revision = RevisionRequisito::create([
                    'id_requisito_certificado' => $requisitoCertificado->id,
                    'id_evidencia_requisito' => $esCalificacion
                        ? null
                        : $requisitoCertificado->evidenciasRequisitos()->latest('id')->value('id'),
                    'id_usuario_revisor' => auth()->id(),
                    'resultado_cumple' => $esPendiente ? 'PENDIENTE' : $cumple,
                    'calificacion_obtenida' => $calificacionObtenida,
                    'estado' => 'ACTIVO',
                ]);

                if (! $esPendiente && $cumple === 'NO') {
                    $hayObservados = true;

                    // La observacion pertenece a la revision exacta, tal como esta en el diagrama.
                    ObservacionRequisito::create([
                        'id_revision_requisito' => $revision->id,
                        'observacion' => $observacion,
                        'estado' => 'ACTIVA',
                    ]);
                }
            }

            $certificado->load('certificadoRequisitos');
            $hayObservados = $certificado->certificadoRequisitos
                ->contains(fn ($requisito) => $requisito->estado === 'REVISION_OBSERVADA');
            $todosRequisitosCumplen = $certificado->certificadoRequisitos
                ->every(fn ($requisito) => $requisito->cumple === 'SI');

            // Guardar revision no cierra el tramite. El cierre se hace con el boton "Finalizar tramite".
            $certificado->update(['estado' => 'EN_REVISION']);

            $this->marcarNotificacionesAtendidas((int) $certificado->id);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Revision guardada',
                'text' => $hayObservados
                    ? 'Hay requisitos observados. Use el boton para notificar al solicitante cuando termine la revision.'
                    : ($todosRequisitosCumplen
                        ? 'Todos los requisitos cumplen. Ahora puede finalizar el tramite.'
                        : 'Se guardo la revision parcial. Los requisitos sin marcar siguen pendientes.'),
                'icon' => $hayObservados ? 'warning' : 'success',
            ]);

            return back();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo guardar la revision tecnica. '.$e->getMessage());
        }
    }

    // FINALIZAR TRAMITE
    // Cierra la etapa tecnica solo cuando todos los requisitos ya fueron marcados como cumplidos.
    public function finalizarTramite(Request $request, Seguimiento $seguimiento)
    {
        if (! $this->usuarioPuedeRevisarSeguimiento($seguimiento)) {
            return back()->with('error', 'Este tramite no esta asignado al tecnico actual.');
        }

        $datos = $request->validate([
            'form_descripcion_final' => ['required', 'string', 'max:2000'],
        ], [], [
            'form_descripcion_final' => 'descripcion final del tramite',
        ]);

        try {
            DB::beginTransaction();

            $certificado = $seguimiento->certificado()
                ->with([
                    'beneficiario.empresa',
                    'tramitador.natural',
                    'certificadoRequisitos.evidenciasRequisitos.tipoEvidencia',
                ])
                ->firstOrFail();

            if (! $certificado->cumpleTodosLosRequisitos()) {
                DB::rollBack();

                return back()->with('error', 'No se puede finalizar el tramite porque aun existen requisitos pendientes u observados.');
            }

            if ($this->tramiteRequiereHabilitarTramitador($certificado)) {
                $request->validate([
                    'form_aceptar_tramitador' => ['accepted'],
                ], [], [
                    'form_aceptar_tramitador' => 'aceptacion del tramitador',
                ]);

                $this->habilitarTramitadorDelTramite($certificado);
            }

            $certificado->update([
                'estado' => 'APROBADO',
            ]);

            $seguimiento->update([
                'fecha_derivacion' => now(),
                'fecha_final' => now(),
            ]);

            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now(),
                'fecha_derivacion' => now(),
                'fecha_final' => now(),
                'descripcion_final' => trim($datos['form_descripcion_final']),
                'referencia' => null,
                'id_usuario_anterior' => $seguimiento->id_usuario_siguiente,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => null,
                'estado' => 'FINALIZADO',
            ]);

            $this->marcarNotificacionesAtendidas((int) $certificado->id);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Tramite finalizado',
                'text' => 'El tramite fue cerrado correctamente y ya puede imprimirse el certificado.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_finalizados');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo finalizar el tramite. '.$e->getMessage());
        }
    }

    // NOTIFICAR OBSERVACIONES AL SOLICITANTE
    // Devuelve el mismo tramite al solicitante para que corrija los requisitos observados.
    public function notificarCorreccionSolicitante(Request $request, Seguimiento $seguimiento)
    {
        if (! $this->usuarioPuedeRevisarSeguimiento($seguimiento)) {
            return back()->with('error', 'No puede notificar observaciones desde esta etapa.');
        }

        try {
            DB::beginTransaction();

            $certificado = $seguimiento->certificado()
                ->with(['beneficiario.usuario', 'tramitador.usuario', 'certificadoRequisitos.requisito'])
                ->firstOrFail();

            $requisitosObservados = $certificado->certificadoRequisitos
                ->where('cumple', 'NO')
                ->where('estado', 'REVISION_OBSERVADA');

            if ($requisitosObservados->isEmpty()) {
                DB::rollBack();

                return back()->with('error', 'No hay requisitos observados para notificar al solicitante.');
            }

            $datos = $request->validate([
                'form_id_usuario_responsable_correccion' => ['required', 'integer'],
            ], [], [
                'form_id_usuario_responsable_correccion' => 'destinatario de la correccion',
            ]);

            // El servicio vuelve a validar que la cuenta elegida pertenece al beneficiario o es un tramitador habilitado.
            $usuarioResponsableCorreccion = $this->gestionTramitadores->destinatarioCorreccionValido(
                $certificado,
                (int) $datos['form_id_usuario_responsable_correccion']
            );

            $observaciones = $requisitosObservados
                ->map(function ($requisitoCertificado) {
                    $ultimaObservacion = $this->ultimaObservacionRequisito($requisitoCertificado);

                    return ($requisitoCertificado->requisito?->descripcion ?? 'Requisito #'.$requisitoCertificado->id)
                        .': '.($ultimaObservacion?->observacion ?? 'Sin observacion');
                })
                ->implode(PHP_EOL);

            // Desde este punto la observacion ya se comunica al solicitante.
            foreach ($requisitosObservados as $requisitoCertificado) {
                $requisitoCertificado->update(['estado' => 'OBSERVADO']);
            }

            // Cierra la etapa del revisor y abre una etapa para que el solicitante corrija.
            $seguimiento->update([
                'fecha_derivacion' => now(),
            ]);

            $certificado->update(['estado' => 'OBSERVADO']);

            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => null,
                // Las observaciones ya tienen su propio historial y no se duplican como referencia.
                'referencia' => null,
                'id_usuario_anterior' => $seguimiento->id_usuario_siguiente,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $usuarioResponsableCorreccion->id,
                'estado' => 'ACTIVO',
            ]);

            $this->notificarUsuarioTramite(
                $usuarioResponsableCorreccion,
                $certificado,
                'Tramite observado',
                'Tiene requisitos observados para corregir en el mismo tramite.'
            );

            $this->marcarNotificacionesAtendidas((int) $certificado->id);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Tramite devuelto',
                'text' => 'El tramite fue notificado a la persona seleccionada para la correccion.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo notificar al solicitante. '.$e->getMessage());
        }
    }

    // REGISTRAR CORRECCION RECIBIDA PRESENCIALMENTE
    // Se usa cuando el solicitante corrige en INSO y no corresponde subir un PDF desde el sistema.
    public function registrarCorreccionRecibida(Seguimiento $seguimiento)
    {
        if (! $this->usuarioPuedeRegistrarCorreccionRecibida($seguimiento)) {
            abort(403, 'No tiene permiso para registrar la correccion recibida.');
        }

        try {
            DB::beginTransaction();

            $certificado = $seguimiento->certificado()
                ->with(['certificadoRequisitos.revisionesRequisitos.observacionesRequisitos'])
                ->firstOrFail();

            // Solo se reabren requisitos que ya fueron comunicados al solicitante como observados.
            $requisitosObservados = $certificado->certificadoRequisitos
                ->where('cumple', 'NO')
                ->where('estado', 'OBSERVADO')
                ->values();

            if ($requisitosObservados->isEmpty()) {
                DB::rollBack();

                return back()->with('error', 'No hay requisitos observados pendientes de correccion.');
            }

            $idsRequisitosObservados = $requisitosObservados
                ->pluck('id_requisito')
                ->filter()
                ->values()
                ->all();

            // El tramite vuelve al ultimo funcionario que observo/reviso un requisito.
            $tecnicoDestino = $this->revisorDestinoCorreccion($certificado, $seguimiento, $requisitosObservados);

            foreach ($requisitosObservados as $requisitoCertificado) {
                // Se reinicia la decision tecnica para que el revisor vuelva a evaluar el requisito corregido.
                $requisitoCertificado->update([
                    'cumple' => null,
                    'estado' => 'PENDIENTE_REVISION',
                ]);
            }

            // Las observaciones quedan como historial, pero dejan de ser la observacion activa.
            ObservacionRequisito::query()
                ->whereHas('revisionRequisito.requisitoCertificado', function ($query) use ($certificado, $idsRequisitosObservados) {
                    $query->where('id_certificado', $certificado->id)
                        ->whereIn('id_requisito', $idsRequisitosObservados);
                })
                ->where('estado', 'ACTIVA')
                ->update([
                    'estado' => 'INACTIVA',
                    'id_usuario_modificacion' => auth()->id(),
                    'updated_at' => now(),
                ]);

            $certificado->update(['estado' => 'EN_REVISION']);

            // Cierra la etapa que estaba en manos del solicitante.
            $seguimiento->update([
                'fecha_derivacion' => now(),
            ]);

            // Abre una nueva etapa para el mismo tramite, devolviendolo al revisor.
            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => null,
                'referencia' => null,
                'id_usuario_anterior' => $seguimiento->id_usuario_siguiente,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $tecnicoDestino->id,
                'estado' => 'ACTIVO',
            ]);

            $this->notificarUsuarioTramite(
                $tecnicoDestino,
                $certificado,
                'Correccion recibida',
                'La correccion presencial fue registrada y el tramite vuelve a revision tecnica.'
            );

            $this->marcarNotificacionesAtendidas((int) $certificado->id);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Correccion recibida',
                'text' => 'El tramite fue devuelto al revisor tecnico para continuar la evaluacion.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo registrar la correccion recibida. '.$e->getMessage());
        }
    }

    // REENVIO DE CORRECCIONES DEL SOLICITANTE
    // El usuario corrige documentos observados y devuelve el mismo tramite al tecnico que observo.
    public function reenviarCorreccion(Request $request, Seguimiento $seguimiento)
    {
        if (! $this->usuarioPuedeReenviarCorreccion($seguimiento)) {
            return back()->with('error', 'Este tramite no esta disponible para correccion del usuario actual.');
        }

        $datos = $request->validate([
            'form_accion_correccion' => ['required', 'in:guardar,enviar'],
            'documentos_correccion' => ['nullable', 'array'],
            'documentos_correccion.*' => ['nullable', 'file'],
            'textos_correccion' => ['nullable', 'array'],
            'textos_correccion.*' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'form_accion_correccion' => 'accion de correccion',
            'documentos_correccion.*' => 'documento corregido',
            'textos_correccion.*' => 'texto corregido',
        ]);

        try {
            DB::beginTransaction();

            $certificado = $seguimiento->certificado()
                ->with([
                    'certificadoRequisitos.evidenciasRequisitos.tipoEvidencia',
                    'registros',
                    'pagos',
                ])
                ->firstOrFail();
            $archivos = $request->file('documentos_correccion', []);
            $textos = $request->input('textos_correccion', []);
            $soloGuardar = $datos['form_accion_correccion'] === 'guardar';
            $requisitosObservados = $certificado->certificadoRequisitos->where('cumple', 'NO');
            $idsRequisitosObservados = $requisitosObservados
                ->pluck('id_requisito')
                ->filter()
                ->values()
                ->all();

            $tecnicoDestinoId = $soloGuardar ? null : $this->revisorDestinoCorreccion(
                $certificado, $seguimiento, $requisitosObservados
            )->id;

            if (! $soloGuardar) {
                $this->validarCorreccionesAntesDeDevolver($certificado, $requisitosObservados, $archivos, $textos);
            }

            foreach ($requisitosObservados as $requisitoCertificado) {
                $evidencia = $this->evidenciaPrincipalDelRequisito($requisitoCertificado);
                $tipoEvidencia = $evidencia?->tipoEvidencia
                    ?? RequisitoTipoCertificado::with('tipoEvidencia')
                        ->where('id_tipo_certificado', $certificado->id_tipo_certificado)
                        ->where('id_requisito', $requisitoCertificado->id_requisito)
                        ->first()?->tipoEvidencia;
                $codigoEvidencia = mb_strtoupper((string) ($tipoEvidencia?->codigo ?? 'PDF'));
                $archivoCorreccion = $archivos[$requisitoCertificado->id] ?? null;
                $textoCorreccion = trim((string) ($textos[$requisitoCertificado->id] ?? ''));

                if ($archivoCorreccion) {
                    $this->validarArchivoCorreccionRequisito(
                        $archivoCorreccion,
                        $tipoEvidencia,
                        "documentos_correccion.{$requisitoCertificado->id}"
                    );

                    $this->guardarDocumentoRequisito(
                        $archivoCorreccion,
                        $requisitoCertificado,
                        $tipoEvidencia?->id ? (int) $tipoEvidencia->id : null,
                        true
                    );
                }

                if ($codigoEvidencia === 'TEXTO' && $textoCorreccion !== '') {
                    $this->guardarValorEvidenciaRequisito(
                        $requisitoCertificado,
                        $tipoEvidencia?->id ? (int) $tipoEvidencia->id : null,
                        $textoCorreccion,
                        true
                    );
                }
            }

            if ($soloGuardar) {
                DB::commit();

                session()->flash('swal', [
                    'title' => 'Correccion guardada',
                    'text' => 'Los documentos fueron guardados. El tramite sigue pendiente de envio al tecnico.',
                    'icon' => 'success',
                ]);

                return back();
            }

            foreach ($requisitosObservados as $requisitoCertificado) {
                // Vuelve a pendiente para que el mismo tecnico revise el documento corregido.
                $requisitoCertificado->update([
                    'cumple' => null,
                    'estado' => 'PENDIENTE_REVISION',
                ]);
            }

            // La observacion anterior se conserva en el historial, pero ya no pertenece
            // a la nueva revision iniciada con el reenvio del solicitante.
            ObservacionRequisito::query()
                ->whereHas('revisionRequisito.requisitoCertificado', function ($query) use ($certificado, $idsRequisitosObservados) {
                    $query->where('id_certificado', $certificado->id)
                        ->whereIn('id_requisito', $idsRequisitosObservados);
                })
                ->where('estado', 'ACTIVA')
                ->update([
                    'estado' => 'INACTIVA',
                    'id_usuario_modificacion' => auth()->id(),
                    'updated_at' => now(),
                ]);

            $certificado->update(['estado' => 'EN_REVISION']);

            // Cierra la etapa del solicitante y devuelve el tramite al mismo revisor que observo.
            $seguimiento->update([
                'fecha_derivacion' => now(),
            ]);

            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => null,
                'referencia' => null,
                'id_usuario_anterior' => $seguimiento->id_usuario_siguiente,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $tecnicoDestinoId,
                'estado' => 'ACTIVO',
            ]);

            if ($tecnicoDestinoId) {
                $this->notificarUsuarioTramite(
                    User::find($tecnicoDestinoId),
                    $certificado,
                    'Correccion reenviada',
                    'El solicitante envio documentos corregidos para nueva revision.'
                );
            }

            $this->marcarNotificacionesAtendidas((int) $certificado->id);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Correccion enviada',
                'text' => 'El tramite fue devuelto al tecnico para nueva revision.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_mis_tramites_beneficiario');
        } catch (ValidationException $e) {
            DB::rollBack();

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo reenviar la correccion. '.$e->getMessage());
        }
    }

    // No se usa por ahora: el seguimiento se modifica con acciones especificas
    // como asignar, derivar, revisar o reenviar correcciones.
    public function edit(Seguimiento $seguimiento)
    {
        abort(404);
    }

    // No se usa por ahora: evita actualizaciones genericas sobre el historial.
    public function update(Request $request, Seguimiento $seguimiento)
    {
        abort(404);
    }

    // No se elimina historial de seguimiento desde CRUD generico.
    public function destroy(Seguimiento $seguimiento)
    {
        abort(404);
    }

    // DATOS BASE PARA INICIAR TRAMITE
    // Reutiliza personas, tipos de certificados y requisitos ya configurados.
    private function datosFormularioTramite(): array
    {
        $tiposCertificados = TipoCertificado::query()
            ->with([
                'area',
                'tipoCertificadoRequisitos.requisito',
                'tipoCertificadoRequisitos.tipoEvidencia',
                'tipoCertificadoRequisitos.dependenciasRequisitos.tipoCertificadoRequerido',
            ])
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $personasBase = Persona::query()
            ->with([
                'natural',
                'empresa.responsables.persona.natural',
                'empresa.responsables.persona.empresa',
                'empresa.responsables.rol',
            ])
            ->where('estado', 'ACTIVO')
            ->orderBy('id')
            ->get();

        $personasBase = $this->beneficiariosVisiblesParaIniciarTramite($personasBase);

        // El registro interno permite elegir ambas personas; una cuenta externa solo usa su propia persona.
        $registroInterno = $this->usuarioPuedeAtenderTramites();
        $personaUsuario = $registroInterno
            ? null
            : Persona::query()
                ->with(['natural', 'empresa'])
                ->where('id_usuario', auth()->id())
                ->where('estado', 'ACTIVO')
                ->first();
        $tramitadorAutomatico = $personaUsuario ? $this->opcionPersonaTramite($personaUsuario) : null;
        $tramitadorBloqueado = (bool) $tramitadorAutomatico;
        $mostrarTramitador = $registroInterno
            || $this->personaAutorizadaParaTramitar($personaUsuario);
        $detallesEmpresasAutorizadas = $registroInterno
            ? []
            : $this->detallesEmpresasAutorizadasParaUsuario(auth()->user());

        // Personas que pueden iniciar un tramite como beneficiarias.
        // Se manda como arreglo simple para que la vista no haga consultas.
        $personas = $personasBase
            ->map(fn (Persona $persona) => $this->opcionPersonaTramite(
                $persona,
                $detallesEmpresasAutorizadas[$persona->id] ?? null
            ))
            ->values();
        $beneficiarioBloqueado = ! $registroInterno && $personas->count() === 1;
        $beneficiarioAutomatico = $beneficiarioBloqueado ? $personas->first() : null;

        // Construye las opciones válidas de cada beneficiario en un solo lugar.
        // Externo: queda únicamente la persona autenticada.
        // Interno: aparecen el representante legal y los tramitadores autorizados.
        $tramitadoresPorBeneficiario = $personasBase->mapWithKeys(function (Persona $persona) use ($tramitadorAutomatico) {
            if ($persona->empresa) {
                return [
                    $persona->id => $this->opcionesTramitadoresEmpresa(
                        $persona,
                        $tramitadorAutomatico['id'] ?? null
                    ),
                ];
            }

            return [
                $persona->id => collect([
                    $this->opcionPersonaTramite(
                        $persona,
                        null,
                        $tramitadorAutomatico ? 'Beneficiario y tramitador' : 'Persona natural'
                    ),
                ]),
            ];
        });

        $requisitosPorTipoCertificado = $tiposCertificados->mapWithKeys(function ($tipo) {
            return [
                $tipo->id => $tipo->tipoCertificadoRequisitos
                    ->where('estado', 'ACTIVO')
                    ->map(function ($asignacion) {
                        // Datos que necesita la vista para mostrar el requisito completo.
                        $certificadosRequeridos = $asignacion->dependenciasRequisitos
                            ->where('estado', 'ACTIVO')
                            ->map(fn (DependenciaRequisito $dependencia) => [
                                'id_tipo_certificado' => $dependencia->id_tipo_certificado_requerido,
                                'nombre' => $dependencia->tipoCertificadoRequerido?->nombre ?? 'Certificado no encontrado',
                            ])
                            ->values();

                        return [
                            'id_requisito_tipo_certificado' => $asignacion->id,
                            'id_requisito' => $asignacion->id_requisito,
                            'descripcion' => $asignacion->requisito?->descripcion ?? 'Requisito no encontrado',
                            'id_tipo_evidencia' => $asignacion->id_tipo_evidencia,
                            'tipo_evidencia_codigo' => $asignacion->tipoEvidencia?->codigo ?? 'PDF',
                            'tipo_evidencia_nombre' => $asignacion->tipoEvidencia?->nombre ?? 'PDF',
                            'tipo_evidencia_tamanio_maximo_mb' => (int) ($asignacion->tipoEvidencia?->tamanio_maximo_mb ?? 0),
                            'certificados_requeridos' => $certificadosRequeridos,
                        ];
                    })
                    ->values(),
            ];
        });

        $dependenciasPorTipoCertificado = $this->dependenciasPorTipoCertificado($tiposCertificados);
        $certificadosVigentesPorPersona = $this->certificadosVigentesPorPersona($personasBase->pluck('id')->all());

        return array_merge(
            compact(
                'tiposCertificados',
                'personas',
                'tramitadoresPorBeneficiario',
                'requisitosPorTipoCertificado',
                'dependenciasPorTipoCertificado',
                'certificadosVigentesPorPersona',
                'beneficiarioBloqueado',
                'beneficiarioAutomatico',
                'tramitadorAutomatico',
                'tramitadorBloqueado',
                'mostrarTramitador'
            ),
            ['longitudMaximaEvidenciaTexto' => self::LONGITUD_MAXIMA_EVIDENCIA_TEXTO]
        );
    }

    // OPCION DE PERSONA PARA SELECTS DEL INICIO DE TRAMITE
    // Centraliza id, nombre y tipo para que los selects muestren una descripcion debajo de cada item.
    private function opcionPersonaTramite(
        ?Persona $persona,
        ?string $detalleEmpresa = null,
        ?string $detallePersonaNatural = null
    ): array
    {
        if (! $persona) {
            return [
                'id' => null,
                'nombre' => 'Sin persona',
                'detalle' => 'Sin clasificacion',
                'tipo' => null,
            ];
        }

        $esEmpresa = (bool) $persona->empresa;

        return [
            'id' => $persona->id,
            'nombre' => $this->nombrePersona($persona),
            'detalle' => $esEmpresa
                ? $this->detalleRepresentanteLegalEmpresa($persona, $detalleEmpresa)
                : ($detallePersonaNatural ?: 'Persona natural'),
            'tipo' => $esEmpresa ? 'EMPRESA' : 'NATURAL',
        ];
    }

    // Devuelve las personas naturales que pueden tramitar por una empresa.
    // El segundo parámetro se usa en el acceso externo para conservar únicamente
    // a la persona autenticada; en el registro interno se omite para mostrar todas.
    private function opcionesTramitadoresEmpresa(
        Persona $beneficiario,
        ?int $idPersonaPermitida = null
    ): Collection {
        if (! $this->empresaTieneRepresentanteLegalActivo($beneficiario)) {
            return collect();
        }

        return $beneficiario->empresa->responsables
            ->filter(fn (Responsable $responsable) => $this->responsableAutorizadoParaTramitar($responsable))
            ->when($idPersonaPermitida, fn (Collection $relaciones) => $relaciones
                ->where('id_persona', $idPersonaPermitida))
            ->groupBy('id_persona')
            // El representante legal se muestra antes que los tramitadores autorizados.
            ->sortBy(fn (Collection $relaciones) => $relaciones->contains(
                fn (Responsable $responsable) => $responsable->rol?->slug === 'solicitante'
            ) ? 0 : 1)
            ->map(fn (Collection $relaciones) => $this->opcionPersonaTramite(
                $relaciones->first()?->persona,
                null,
                $this->detalleRelacionEmpresa($relaciones)
            ))
            ->filter(fn (array $opcion) => filled($opcion['id']))
            ->values();
    }

    // Una persona puede tener ambos vínculos; la etiqueta evita perder esa información en el selector.
    private function detalleRelacionEmpresa(Collection $relaciones): string
    {
        $roles = $relaciones->pluck('rol.slug')->filter()->unique();

        return match (true) {
            $roles->contains('solicitante') && $roles->contains('tramitador') => 'Representante legal y tramitador autorizado',
            $roles->contains('solicitante') => 'Representante legal',
            default => 'Tramitador autorizado',
        };
    }

    // En el selector de beneficiarios la empresa muestra quién puede representarla legalmente.
    private function detalleRepresentanteLegalEmpresa(Persona $persona, ?string $detalleAlternativo = null): string
    {
        $representantes = $persona->empresa?->responsables
            ?->filter(function (Responsable $responsable) {
                $relacionActiva = in_array((string) $responsable->estado, ['1', 'ACTIVO'], true);
                $rolActivo = $responsable->rol
                    && (string) $responsable->rol->estado === '1'
                    && $responsable->rol->slug === 'solicitante';
                $personaActiva = $responsable->persona
                    && in_array((string) $responsable->persona->estado, ['1', 'ACTIVO'], true)
                    && (bool) $responsable->persona->natural;

                return $relacionActiva && $rolActivo && $personaActiva;
            })
            ->map(fn (Responsable $responsable) => $this->nombrePersona($responsable->persona))
            ->filter()
            ->unique()
            ->values() ?? collect();

        if ($representantes->isEmpty()) {
            return $detalleAlternativo ?: 'Empresa · Sin representante legal activo';
        }

        $etiqueta = $representantes->count() === 1 ? 'Representante legal' : 'Representantes legales';

        return $etiqueta.': '.$representantes->implode(', ');
    }

    // Identifica por qué la cuenta puede iniciar trámites para cada empresa visible.
    private function detallesEmpresasAutorizadasParaUsuario(?User $usuario): array
    {
        if (! $usuario) {
            return [];
        }

        return $usuario->relacionesEmpresarialesParaTramites()
            ->groupBy(fn (Responsable $relacion) => $relacion->empresa?->id_persona)
            ->filter(fn ($relaciones, $idPersonaEmpresa) => filled($idPersonaEmpresa))
            ->map(function ($relaciones) {
                $roles = $relaciones->pluck('rol.slug')->filter()->unique();

                return match (true) {
                    $roles->contains('solicitante') && $roles->contains('tramitador') => 'Empresa · Representante legal y tramitador',
                    $roles->contains('solicitante') => 'Empresa · Representante legal',
                    default => 'Empresa · Tramitador',
                };
            })
            ->all();
    }

    // Permite actuar al tramitador autorizado y al representante legal de la empresa.
    private function responsableAutorizadoParaTramitar(Responsable $responsable): bool
    {
        $estadoActivo = in_array((string) $responsable->estado, ['1', 'ACTIVO'], true);
        $rol = $responsable->rol;
        $rolAutoriza = $rol
            && (string) $rol->estado === '1'
            && in_array($rol->slug, ['tramitador', 'solicitante'], true);
        $personaActiva = $responsable->persona
            && in_array((string) $responsable->persona->estado, ['1', 'ACTIVO'], true)
            && (bool) $responsable->persona->natural;

        return $estadoActivo && $rolAutoriza && $personaActiva;
    }

    // Regla obligatoria para cualquier trámite empresarial.
    // Un tramitador autorizado puede realizar la gestión, pero no sustituye la existencia
    // del representante legal registrado con el rol solicitante.
    private function empresaTieneRepresentanteLegalActivo(Persona $personaEmpresa): bool
    {
        $empresa = $personaEmpresa->empresa;

        if (! $empresa || ! in_array((string) $empresa->estado, ['1', 'ACTIVO'], true)) {
            return false;
        }

        return $empresa->responsables->contains(function (Responsable $responsable) {
            return in_array((string) $responsable->estado, ['1', 'ACTIVO'], true)
                && $responsable->rol
                && (string) $responsable->rol->estado === '1'
                && $responsable->rol->slug === 'solicitante'
                && $responsable->persona
                && in_array((string) $responsable->persona->estado, ['1', 'ACTIVO'], true)
                && (bool) $responsable->persona->natural;
        });
    }

    // Evita que un representante o tramitador empresarial se duplique como beneficiario personal.
    private function personaNaturalVinculadaAUnaEmpresa(Persona $persona): bool
    {
        if (! $persona->natural) {
            return false;
        }

        return Responsable::query()
            ->where('id_persona', $persona->id)
            ->whereIn('estado', ['1', 'ACTIVO'])
            ->whereHas('rol', fn ($rol) => $rol
                ->where('estado', 1)
                ->whereIn('slug', ['solicitante', 'tramitador']))
            ->whereHas('empresa', fn ($empresa) => $empresa
                ->whereIn('estado', ['1', 'ACTIVO'])
                ->whereHas('persona', fn ($personaEmpresa) => $personaEmpresa
                    ->whereIn('estado', ['1', 'ACTIVO'])))
            ->exists();
    }

    // Reconoce a la persona que puede actuar como representante legal o tramitador.
    private function personaAutorizadaParaTramitar(?Persona $persona): bool
    {
        if (! $persona?->natural) {
            return false;
        }

        return Responsable::query()
            ->where('id_persona', $persona->id)
            ->whereIn('estado', ['1', 'ACTIVO'])
            ->whereHas('empresa', fn ($empresa) => $empresa
                ->whereIn('estado', ['1', 'ACTIVO'])
                ->whereHas('persona', fn ($empresaPersona) => $empresaPersona
                    ->whereIn('estado', ['1', 'ACTIVO'])))
            ->whereHas('rol', function ($rol) {
                $rol->where('estado', 1)
                    ->whereIn('slug', ['tramitador', 'solicitante']);
            })
            ->exists();
    }

    // Agrupa los certificados previos que exige cada tipo de certificado.
    private function dependenciasPorTipoCertificado($tiposCertificados)
    {
        return $tiposCertificados->mapWithKeys(function (TipoCertificado $tipoCertificado) {
            $dependencias = $tipoCertificado->tipoCertificadoRequisitos
                ->where('estado', 'ACTIVO')
                ->flatMap(fn ($asignacion) => $asignacion->dependenciasRequisitos->where('estado', 'ACTIVO'))
                ->map(fn (DependenciaRequisito $dependencia) => [
                    'id_tipo_certificado' => $dependencia->id_tipo_certificado_requerido,
                    'nombre' => $dependencia->tipoCertificadoRequerido?->nombre ?? 'Certificado no encontrado',
                ])
                ->unique('id_tipo_certificado')
                ->values();

            return [$tipoCertificado->id => $dependencias];
        });
    }

    // Lista los tipos de certificado que cada beneficiario ya tiene emitidos y vigentes.
    private function certificadosVigentesPorPersona(array $idsPersonas)
    {
        return Certificado::query()
            ->select('id_persona_beneficiario', 'id_tipo_certificado')
            ->whereIn('id_persona_beneficiario', $idsPersonas)
            ->where('estado', 'EMITIDO')
            ->whereHas('emisiones', function ($query) {
                // El certificado previo debe tener al menos un documento emitido y vigente.
                $query->where('estado', 'EMITIDO')
                    ->where(function ($vigencia) {
                        $vigencia->where('vigencia_indefinida', true)
                            ->orWhereDate('fecha_fin', '>=', today()->toDateString());
                    });
            })
            ->get()
            ->groupBy('id_persona_beneficiario')
            ->map(fn ($certificados) => $certificados
                ->pluck('id_tipo_certificado')
                ->unique()
                ->values()
            );
    }

    // No permite iniciar un tramite si falta un certificado previo obligatorio.
    private function validarCertificadosPrevios(int $idBeneficiario, int $idTipoCertificado): void
    {
        $dependencias = DependenciaRequisito::query()
            ->with('tipoCertificadoRequerido')
            ->where('estado', 'ACTIVO')
            ->whereHas('requisitoTipoCertificado', function ($query) use ($idTipoCertificado) {
                $query->where('id_tipo_certificado', $idTipoCertificado)
                    ->where('estado', 'ACTIVO');
            })
            ->get()
            ->unique('id_tipo_certificado_requerido')
            ->values();

        if ($dependencias->isEmpty()) {
            return;
        }

        $certificadosVigentes = Certificado::query()
            ->where('id_persona_beneficiario', $idBeneficiario)
            ->where('estado', 'EMITIDO')
            ->whereHas('emisiones', function ($query) {
                // El certificado previo debe tener al menos un documento emitido y vigente.
                $query->where('estado', 'EMITIDO')
                    ->where(function ($vigencia) {
                        $vigencia->where('vigencia_indefinida', true)
                            ->orWhereDate('fecha_fin', '>=', today()->toDateString());
                    });
            })
            ->pluck('id_tipo_certificado')
            ->map(fn ($id) => (int) $id);

        $faltantes = $dependencias
            ->reject(fn (DependenciaRequisito $dependencia) => $certificadosVigentes->contains((int) $dependencia->id_tipo_certificado_requerido))
            ->map(fn (DependenciaRequisito $dependencia) => $dependencia->tipoCertificadoRequerido?->nombre ?? 'Certificado no encontrado')
            ->values();

        if ($faltantes->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'form_id_tipo_certificado' => 'No puede solicitar este tramite. Primero debe contar con: '.$faltantes->implode(', ').'.',
        ]);
    }

    private function beneficiariosVisiblesParaIniciarTramite($personasBase)
    {
        // Una empresa sin representante legal activo no puede iniciar trámites y por eso
        // tampoco se ofrece en el selector, independientemente de quién abra el formulario.
        $personasHabilitadas = $personasBase
            ->filter(fn (Persona $persona) => ! $persona->empresa
                || $this->empresaTieneRepresentanteLegalActivo($persona));

        if ($this->usuarioPuedeAtenderTramites()) {
            // En el registro interno, una persona vinculada a una empresa actua como su
            // representante o tramitador; no debe duplicarse como beneficiaria individual.
            $idsPersonasVinculadas = $personasBase
                ->filter(fn (Persona $persona) => $persona->empresa)
                ->flatMap(fn (Persona $persona) => $persona->empresa->responsables)
                ->filter(fn (Responsable $responsable) => $this->responsableAutorizadoParaTramitar($responsable))
                ->pluck('id_persona')
                ->map(fn ($id) => (int) $id);

            return $personasHabilitadas
                ->reject(fn (Persona $persona) => $persona->natural
                    && $idsPersonasVinculadas->contains((int) $persona->id))
                ->values();
        }

        $idsPermitidos = $this->idsBeneficiariosPermitidosParaUsuario(auth()->user());

        return $personasHabilitadas
            ->whereIn('id', $idsPermitidos)
            ->values();
    }

    // Evita que un usuario externo inicie tramites para personas o empresas ajenas.
    private function validarQueUsuarioPuedeRegistrarBeneficiario(?User $usuario, int $idBeneficiario): void
    {
        if (! $usuario || ! $this->usuarioPuedeIniciarTramiteParaBeneficiario($usuario, $idBeneficiario)) {
            throw ValidationException::withMessages([
                'form_id_persona_beneficiario' => 'No puede iniciar tramites para el beneficiario seleccionado.',
            ]);
        }
    }

    private function usuarioPuedeIniciarTramiteParaBeneficiario(User $usuario, int $idBeneficiario): bool
    {
        if ($this->usuarioPuedeVerBandejasInternas($usuario)) {
            return true;
        }

        return in_array($idBeneficiario, $this->idsBeneficiariosPermitidosParaUsuario($usuario), true);
    }

    private function idsBeneficiariosPermitidosParaUsuario(?User $usuario): array
    {
        if (! $usuario) {
            return [];
        }

        $personaUsuario = Persona::query()
            ->where('id_usuario', $usuario->id)
            ->where('estado', 'ACTIVO')
            ->first();

        if (! $personaUsuario) {
            return [];
        }

        $idsPermitidos = [(int) $personaUsuario->id];

        // Usa la misma regla del encabezado: relación, rol, persona y empresa deben estar activos.
        $relacionesEmpresariales = $usuario->relacionesEmpresarialesParaTramites();
        $idsEmpresas = $relacionesEmpresariales
            ->pluck('empresa.id_persona')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        // Si la cuenta actua por una empresa, inicia tramites solo para esa relacion empresarial.
        // La persona natural se conserva como beneficiaria unicamente cuando no representa ni tramita empresas.
        return $relacionesEmpresariales->isNotEmpty()
            ? array_values(array_unique($idsEmpresas))
            : $idsPermitidos;
    }

    // El servidor conserva al titular de la cuenta externa como tramitador para evitar cambios desde el navegador.
    private function resolverTramitadorDelBeneficiario(?User $usuario, int $idBeneficiario, ?int $idTramitador): int
    {
        $beneficiario = Persona::query()
            ->with(['empresa.responsables.rol', 'empresa.responsables.persona.natural'])
            ->find($idBeneficiario);

        if (! $beneficiario) {
            throw ValidationException::withMessages([
                'form_id_persona_beneficiario' => 'El beneficiario seleccionado no es válido.',
            ]);
        }

        if ($beneficiario->empresa && ! $this->empresaTieneRepresentanteLegalActivo($beneficiario)) {
            throw ValidationException::withMessages([
                'form_id_persona_beneficiario' => 'La empresa no puede iniciar trámites porque no tiene un representante legal activo.',
            ]);
        }

        if (! $this->usuarioPuedeVerBandejasInternas($usuario)) {
            // Segunda barrera de seguridad: aunque alteren el select o envien el POST manualmente,
            // el beneficiario debe seguir perteneciendo a las relaciones autorizadas de la cuenta.
            if (! $usuario || ! $this->usuarioPuedeIniciarTramiteParaBeneficiario($usuario, $idBeneficiario)) {
                throw ValidationException::withMessages([
                    'form_id_persona_beneficiario' => 'No puede iniciar tramites para la empresa seleccionada.',
                ]);
            }

            $personaUsuario = Persona::query()
                ->with('natural')
                ->where('id_usuario', $usuario?->id)
                ->where('estado', 'ACTIVO')
                ->first();

            if (! $personaUsuario) {
                throw ValidationException::withMessages([
                    'form_id_persona_tramitador' => 'Su cuenta no está vinculada a una persona activa.',
                ]);
            }

            if ($beneficiario->empresa) {
                $relacionValida = $personaUsuario->natural
                    && $beneficiario->empresa->responsables->contains(
                        fn (Responsable $responsable) => $this->responsableAutorizadoParaTramitar($responsable)
                            && (int) $responsable->id_persona === (int) $personaUsuario->id
                    );

                if (! $relacionValida) {
                    throw ValidationException::withMessages([
                        'form_id_persona_tramitador' => 'Su cuenta no es representante legal ni tramitador autorizado de la empresa.',
                    ]);
                }
            }

            // En solicitudes externas el backend ignora cualquier ID recibido y conserva
            // siempre a la persona autenticada como quien realiza el trámite.
            return $personaUsuario->id;
        }

        if (! $beneficiario->empresa) {
            if ($this->personaNaturalVinculadaAUnaEmpresa($beneficiario)) {
                throw ValidationException::withMessages([
                    'form_id_persona_beneficiario' => 'Esta persona está vinculada a una empresa; seleccione la empresa como beneficiaria.',
                ]);
            }

            return $idBeneficiario;
        }

        if ($idBeneficiario === $idTramitador) {
            throw ValidationException::withMessages([
                'form_id_persona_tramitador' => 'Para una empresa debe seleccionar a su representante legal o a un tramitador autorizado.',
            ]);
        }

        if (! $idTramitador) {
            throw ValidationException::withMessages([
                'form_id_persona_tramitador' => 'Debe seleccionar un tramitador para la empresa.',
            ]);
        }

        $tramitadorValido = $beneficiario->empresa?->responsables
            ->contains(fn (Responsable $responsable) => $this->responsableAutorizadoParaTramitar($responsable)
                && (int) $responsable->id_persona === $idTramitador);

        if (! $tramitadorValido) {
            throw ValidationException::withMessages([
                'form_id_persona_tramitador' => 'El tramitador seleccionado no pertenece al beneficiario.',
            ]);
        }

        return $idTramitador;
    }

    // La evidencia TRAMITADOR solo habilita a una persona natural vinculada a una empresa.
    private function tramiteRequiereHabilitarTramitador(Certificado $certificado): bool
    {
        $tieneEvidenciaTramitador = $certificado->certificadoRequisitos
            ->flatMap(fn (RequisitoCertificado $requisito) => $requisito->evidenciasRequisitos)
            ->contains(fn (EvidenciaRequisito $evidencia) => mb_strtoupper((string) $evidencia->tipoEvidencia?->codigo) === 'TRAMITADOR');

        return $tieneEvidenciaTramitador
            && (bool) $certificado->beneficiario?->empresa
            && (bool) $certificado->tramitador?->natural;
    }

    // La relación responsable activa autoriza al tramitador a iniciar trámites para la empresa.
    private function habilitarTramitadorDelTramite(Certificado $certificado): void
    {
        $idRolTramitador = Role::query()
            ->where('slug', 'tramitador')
            ->where('estado', 1)
            ->value('id');

        if (! $idRolTramitador) {
            throw new \Exception('No existe el rol tramitador activo.');
        }

        $responsable = Responsable::withTrashed()->firstOrNew([
            'id_empresa' => $certificado->beneficiario->empresa->id,
            'id_persona' => $certificado->tramitador->id,
            'id_rol' => $idRolTramitador,
        ]);

        $responsable->fill([
            'fecha_registro' => $responsable->fecha_registro ?: now()->toDateString(),
            'fecha_baja' => null,
            'estado' => 'ACTIVO',
        ]);

        if ($responsable->trashed()) {
            $responsable->restore();
        }

        $responsable->save();
    }

    // VALIDACION DEL INICIO DE TRAMITE
    // El solicitante adjunta documentos, pero no decide si cumple o no.
    private function validarTramite(Request $request): array
    {
        return $request->validate([
            'form_id_persona_beneficiario' => ['required', 'exists:personas,id'],
            // Se permite nullable porque en persona natural el backend lo fuerza al mismo beneficiario.
            'form_id_persona_tramitador' => ['nullable', 'exists:personas,id'],
            'form_id_tipo_certificado' => ['required', 'exists:tipos_certificados,id'],
            'form_descripcion' => ['nullable', 'string'],
            'requisitos_certificados' => ['nullable', 'array'],
            'requisitos_certificados.*' => ['array'],
            'requisitos_certificados.*.id_requisito_tipo_certificado' => ['nullable', 'exists:requisitos_tipos_certificados,id'],
            'requisitos_certificados.*.id_requisito' => ['required', 'exists:requisitos,id'],
            'requisitos_certificados.*.id_tipo_evidencia' => ['nullable', 'exists:tipos_evidencias,id'],
            'requisitos_certificados.*.observacion' => ['nullable', 'string', 'max:500'],
            'requisitos_certificados.*.valor_texto' => [
                'nullable',
                'string',
                'max:'.self::LONGITUD_MAXIMA_EVIDENCIA_TEXTO,
            ],
            'documentos_requisitos' => ['nullable', 'array'],
            'documentos_requisitos.*' => ['nullable', 'file'],
        ], [
            // Mensajes claros para que Laravel muestre los errores debajo de cada campo.
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El :attribute seleccionado no es valido.',
            'array' => 'El campo :attribute debe tener un formato valido.',
            'string' => 'El campo :attribute debe ser texto.',
            'file' => 'El :attribute debe ser un archivo valido.',
            'requisitos_certificados.*.valor_texto.max' => 'El dato ingresado es demasiado extenso.',
        ], [
            'form_id_persona_beneficiario' => 'beneficiario',
            'form_id_persona_tramitador' => 'tramitador',
            'form_id_tipo_certificado' => 'tipo de tramite',
            'requisitos_certificados' => 'requisitos del tramite',
            'requisitos_certificados.*.id_requisito_tipo_certificado' => 'configuracion del requisito',
            'requisitos_certificados.*.id_requisito' => 'requisito del tramite',
            'requisitos_certificados.*.id_tipo_evidencia' => 'tipo de evidencia',
            'requisitos_certificados.*.valor_texto' => 'dato solicitado',
            'documentos_requisitos.*' => 'evidencia',
        ]);
    }

    // Busca solo tipos de certificado activos y con su area lista para iniciar tramite.
    private function buscarTipoCertificadoActivo(int $idTipoCertificado): TipoCertificado
    {
        $tipoCertificado = TipoCertificado::query()
            ->with('area')
            ->where('estado', 'ACTIVO')
            ->find($idTipoCertificado);

        if (! $tipoCertificado) {
            throw ValidationException::withMessages([
                'form_id_tipo_certificado' => 'El tipo de tramite seleccionado no esta disponible.',
            ]);
        }

        return $tipoCertificado;
    }

    // Carga los requisitos reales configurados para el tipo de certificado.
    private function requisitosConfiguradosDelTipoCertificado(int $idTipoCertificado)
    {
        return RequisitoTipoCertificado::query()
            ->with(['requisito', 'tipoEvidencia'])
            ->where('id_tipo_certificado', $idTipoCertificado)
            ->where('estado', 'ACTIVO')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    // Relaciona cada requisito configurado con la posicion del archivo enviado desde el formulario.
    private function posicionesDeArchivosPorRequisitoConfigurado(Request $request): array
    {
        $indices = [];

        foreach ($request->input('requisitos_certificados', []) as $indice => $item) {
            $idAsignacion = isset($item['id_requisito_tipo_certificado'])
                ? (int) $item['id_requisito_tipo_certificado']
                : null;

            if ($idAsignacion) {
                $indices[$idAsignacion] = $indice;
            }
        }

        return $indices;
    }

    // Relaciona cada requisito configurado con el texto ingresado por el solicitante.
    private function valoresTextoPorRequisitoConfigurado(Request $request): array
    {
        $valores = [];

        foreach ($request->input('requisitos_certificados', []) as $item) {
            $idAsignacion = isset($item['id_requisito_tipo_certificado'])
                ? (int) $item['id_requisito_tipo_certificado']
                : null;

            if ($idAsignacion && array_key_exists('valor_texto', $item)) {
                $valores[$idAsignacion] = trim((string) $item['valor_texto']);
            }
        }

        return $valores;
    }

    // Exige el dato solamente cuando la configuracion real del requisito usa evidencia TEXTO.
    private function validarTextosIngresadosDeRequisitos(Request $request, $asignacionesRequisitos): void
    {
        $indicesPorAsignacion = $this->posicionesDeArchivosPorRequisitoConfigurado($request);
        $valoresPorAsignacion = $this->valoresTextoPorRequisitoConfigurado($request);

        foreach ($asignacionesRequisitos as $asignacion) {
            $codigo = mb_strtoupper((string) $asignacion->tipoEvidencia?->codigo);

            if ($codigo !== 'TEXTO') {
                continue;
            }

            $indice = $indicesPorAsignacion[$asignacion->id] ?? null;
            $valor = $valoresPorAsignacion[$asignacion->id] ?? '';

            if ($indice === null || $valor === '') {
                throw ValidationException::withMessages([
                    $indice === null
                        ? 'requisitos_certificados'
                        : "requisitos_certificados.$indice.valor_texto" => 'Debe ingresar el dato solicitado para "'.($asignacion->requisito?->descripcion ?? 'este requisito').'".',
                ]);
            }
        }
    }

    // Valida archivos con la configuracion guardada para cada requisito.
    private function validarArchivosSubidosDeRequisitos(Request $request, $asignacionesRequisitos): void
    {
        $archivos = $request->file('documentos_requisitos', []);
        $indicesPorAsignacion = $this->posicionesDeArchivosPorRequisitoConfigurado($request);
        $asignacionesPorId = $asignacionesRequisitos->keyBy('id');

        foreach ($asignacionesRequisitos as $asignacion) {
            $tipoEvidencia = $asignacion->tipoEvidencia;
            $codigo = mb_strtoupper((string) $tipoEvidencia?->codigo);

            if (! $this->evidenciaRequiereArchivo($codigo)) {
                continue;
            }

            $indice = $indicesPorAsignacion[$asignacion->id] ?? null;

            if ($indice === null || ! $request->hasFile("documentos_requisitos.$indice")) {
                if (app()->environment('local')) {
                    \Illuminate\Support\Facades\Log::warning('inicio_tramite_evidencia_no_recibida', [
                        'tipo_certificado' => $asignacion->id_tipo_certificado,
                        'asignacion_esperada' => $asignacion->id,
                        'indice_esperado' => $indice,
                        'asignaciones_recibidas' => $indicesPorAsignacion,
                        'archivos_recibidos' => collect($archivos)->map(fn ($archivo) => [
                            'error_subida' => $archivo?->getError(),
                            'valido' => $archivo?->isValid(),
                        ])->all(),
                    ]);
                }
                throw ValidationException::withMessages([
                    $indice === null ? 'documentos_requisitos' : "documentos_requisitos.$indice" => 'Debe adjuntar la evidencia solicitada para "'.($asignacion->requisito?->descripcion ?? 'este requisito').'".',
                ]);
            }
        }

        foreach ($archivos as $indice => $archivo) {
            if (! $archivo) {
                continue;
            }

            $idAsignacion = $this->buscarIdRequisitoConfiguradoPorPosicionArchivo($indicesPorAsignacion, $indice);
            $asignacion = $idAsignacion ? $asignacionesPorId->get((int) $idAsignacion) : null;
            $tipoEvidencia = $asignacion?->tipoEvidencia;

            if (! $tipoEvidencia) {
                throw ValidationException::withMessages([
                    "documentos_requisitos.$indice" => 'No se encontro la configuracion del requisito para validar el archivo.',
                ]);
            }

            $codigo = mb_strtoupper((string) $tipoEvidencia->codigo);
            $extension = mb_strtolower($archivo->getClientOriginalExtension());
            $mime = (string) $archivo->getMimeType();
            $extensionesPermitidas = $this->extensionesPermitidasPorEvidencia($codigo);

            if (empty($extensionesPermitidas)) {
                throw ValidationException::withMessages([
                    "documentos_requisitos.$indice" => 'Este tipo de evidencia no permite subir archivo.',
                ]);
            }

            if (
                ! in_array($extension, $extensionesPermitidas, true)
                || ! $this->archivoTieneFormatoPermitidoPorEvidencia($codigo, $mime)
            ) {
                throw ValidationException::withMessages([
                    "documentos_requisitos.$indice" => 'El archivo no corresponde al tipo de evidencia seleccionado.',
                ]);
            }

            $tamanioMaximoMb = (int) $tipoEvidencia->tamanio_maximo_mb;

            if ($tamanioMaximoMb > 0 && $archivo->getSize() > ($tamanioMaximoMb * 1024 * 1024)) {
                throw ValidationException::withMessages([
                    "documentos_requisitos.$indice" => "El archivo no debe superar {$tamanioMaximoMb} MB.",
                ]);
            }
        }
    }

    private function evidenciaRequiereArchivo(string $codigo): bool
    {
        return in_array($codigo, ['PDF', 'IMAGEN'], true);
    }

    private function extensionesPermitidasPorEvidencia(string $codigo): array
    {
        return match ($codigo) {
            'PDF' => ['pdf'],
            'IMAGEN' => ['jpg', 'jpeg', 'png', 'webp'],
            default => [],
        };
    }

    private function buscarIdRequisitoConfiguradoPorPosicionArchivo(array $indicesPorAsignacion, int|string $indiceDocumento): ?int
    {
        foreach ($indicesPorAsignacion as $idAsignacion => $indice) {
            if ((string) $indice === (string) $indiceDocumento) {
                return (int) $idAsignacion;
            }
        }

        return null;
    }

    // Revisa el tipo real del archivo, no solo la extension escrita en el nombre.
    private function archivoTieneFormatoPermitidoPorEvidencia(string $codigo, string $mime): bool
    {
        return match ($codigo) {
            'PDF' => in_array($mime, ['application/pdf', 'application/x-pdf'], true),
            'IMAGEN' => str_starts_with($mime, 'image/'),
            default => false,
        };
    }

    // Decide a qué bandeja volver después de registrar un trámite nuevo.
    // El solicitante ve Mis trámites; el personal INSO ve únicamente los trámites que registró.
    private function rutaDespuesDeRegistrarTramite(?User $usuario): string
    {
        return $this->usuarioPuedeVerBandejasInternas($usuario)
            ? 'seguimientos_tramites_registrados_funcionario'
            : 'seguimientos_mis_tramites_beneficiario';
    }

    // GUARDA LOS REQUISITOS DE LA SOLICITUD
    // Cada requisito queda pendiente para que el tecnico lo revise despues.
    private function guardarRequisitosTramite(
        Certificado $certificado,
        $asignacionesRequisitos,
        array $indicesDocumentos,
        array $documentos,
        array $valoresTexto
    ): void {
        $procesados = [];

        foreach ($asignacionesRequisitos as $asignacion) {
            $idRequisito = $asignacion->id_requisito;
            $idTipoEvidencia = $asignacion->id_tipo_evidencia;
            $codigoEvidencia = mb_strtoupper((string) $asignacion->tipoEvidencia?->codigo);
            $claveProceso = 'asignacion_'.$asignacion->id;

            if (! $idRequisito || in_array($claveProceso, $procesados, true)) {
                continue;
            }

            $requisitoCertificado = RequisitoCertificado::create([
                'id_certificado' => $certificado->id,
                'id_requisito' => $idRequisito,
                'orden' => $asignacion->orden,
                // Mientras no exista revision tecnica, no se afirma si cumple o no cumple.
                'cumple' => null,
                'estado' => 'PENDIENTE_REVISION',
            ]);

            // CALIFICACION se registra durante la revisión técnica y no necesita una fila vacía de evidencia.
            if ($idTipoEvidencia && $codigoEvidencia !== 'CALIFICACION') {
                EvidenciaRequisito::firstOrCreate(
                    [
                        'id_requisito_certificado' => $requisitoCertificado->id,
                        'id_tipo_evidencia' => $idTipoEvidencia,
                    ],
                    [
                        'valor' => null,
                        'estado' => 'PENDIENTE',
                    ]
                );
            }

            $indiceDocumento = $indicesDocumentos[$asignacion->id] ?? null;

            if ($indiceDocumento !== null && ! empty($documentos[$indiceDocumento])) {
                $this->guardarDocumentoRequisito(
                    $documentos[$indiceDocumento],
                    $requisitoCertificado,
                    $idTipoEvidencia ? (int) $idTipoEvidencia : null
                );
            }

            $valorTexto = $valoresTexto[$asignacion->id] ?? '';

            if ($codigoEvidencia === 'TEXTO' && $valorTexto !== '') {
                $this->guardarValorEvidenciaRequisito(
                    $requisitoCertificado,
                    $idTipoEvidencia ? (int) $idTipoEvidencia : null,
                    $valorTexto
                );
            }

            $procesados[] = $claveProceso;
        }
    }

    // Guarda cada archivo en la carpeta privada del requisito sin sobrescribir otro documento.
    private function guardarDocumentoRequisito(
        $archivo,
        RequisitoCertificado $requisitoCertificado,
        ?int $idTipoEvidencia,
        bool $mantenerHistorial = false
    ): void {
        $datosEvidencia = [
            'valor' => null,
            'estado' => 'REGISTRADO',
            'id_usuario_registro' => auth()->id(),
            'id_usuario_modificacion' => auth()->id(),
        ];

        if ($mantenerHistorial) {
            $evidencia = EvidenciaRequisito::create([
                'id_requisito_certificado' => $requisitoCertificado->id,
                'id_tipo_evidencia' => $idTipoEvidencia ?: $this->idTipoEvidencia('PDF'),
            ] + $datosEvidencia);
        } else {
            // En el registro inicial se actualiza la evidencia configurada; en correcciones se crea historial.
            $evidencia = EvidenciaRequisito::updateOrCreate(
                [
                    'id_requisito_certificado' => $requisitoCertificado->id,
                    'id_tipo_evidencia' => $idTipoEvidencia ?: $this->idTipoEvidencia('PDF'),
                ],
                $datosEvidencia
            );
        }

        $certificado = $requisitoCertificado->certificado()->firstOrFail();
        $ruta = $this->expedientesTramites->guardarArchivoSubido(
            $certificado,
            $archivo,
            ExpedienteTramiteService::EVIDENCIA_REQUISITO,
            $requisitoCertificado
        );

        $evidencia->update(['valor' => $ruta]);
    }

    // Usa la evidencia configurada mas reciente para saber que debe corregir el solicitante.
    private function evidenciaPrincipalDelRequisito(RequisitoCertificado $requisitoCertificado): ?EvidenciaRequisito
    {
        return $requisitoCertificado->evidenciasRequisitos
            ->sortByDesc('id')
            ->first();
    }

    // Guarda respuestas de texto sin crear columnas nuevas; queda en evidencias_requisitos.valor.
    private function guardarValorEvidenciaRequisito(
        RequisitoCertificado $requisitoCertificado,
        ?int $idTipoEvidencia,
        string $valor,
        bool $mantenerHistorial = false
    ): void {
        $datosEvidencia = [
            'valor' => $valor,
            'estado' => 'REGISTRADO',
            'id_usuario_registro' => auth()->id(),
            'id_usuario_modificacion' => auth()->id(),
        ];

        if ($mantenerHistorial) {
            EvidenciaRequisito::create([
                'id_requisito_certificado' => $requisitoCertificado->id,
                'id_tipo_evidencia' => $idTipoEvidencia ?: $this->idTipoEvidencia('TEXTO'),
            ] + $datosEvidencia);

            return;
        }

        EvidenciaRequisito::updateOrCreate(
            [
                'id_requisito_certificado' => $requisitoCertificado->id,
                'id_tipo_evidencia' => $idTipoEvidencia ?: $this->idTipoEvidencia('TEXTO'),
            ],
            $datosEvidencia
        );
    }

    // Protege la correccion: no se acepta cualquier archivo si el requisito no lo permite.
    private function validarArchivoCorreccionRequisito($archivo, ?TipoEvidencia $tipoEvidencia, string $campo): void
    {
        $codigo = mb_strtoupper((string) ($tipoEvidencia?->codigo ?? 'PDF'));
        $extensionesPermitidas = $this->extensionesPermitidasPorEvidencia($codigo);

        if (empty($extensionesPermitidas)) {
            throw ValidationException::withMessages([
                $campo => 'Este requisito no permite subir archivo como correccion.',
            ]);
        }

        $extension = mb_strtolower($archivo->getClientOriginalExtension());
        $mime = (string) $archivo->getMimeType();

        if (
            ! in_array($extension, $extensionesPermitidas, true)
            || ! $this->archivoTieneFormatoPermitidoPorEvidencia($codigo, $mime)
        ) {
            throw ValidationException::withMessages([
                $campo => 'El archivo no corresponde al tipo de evidencia solicitado.',
            ]);
        }

        $tamanioMaximoMb = (int) ($tipoEvidencia?->tamanio_maximo_mb ?? 0);

        if ($tamanioMaximoMb > 0 && $archivo->getSize() > ($tamanioMaximoMb * 1024 * 1024)) {
            throw ValidationException::withMessages([
                $campo => "El archivo no debe superar {$tamanioMaximoMb} MB.",
            ]);
        }
    }

    // Antes de devolver al revisor, verifica que cada tipo de evidencia tenga una correccion posible.
    private function validarCorreccionesAntesDeDevolver(Certificado $certificado, $requisitosObservados, array $archivos, array $textos): void
    {
        $errores = [];
        $configuraciones = RequisitoTipoCertificado::with('tipoEvidencia', 'dependenciasRequisitos')
            ->where('id_tipo_certificado', $certificado->id_tipo_certificado)
            ->get()->keyBy('id_requisito');

        foreach ($requisitosObservados as $requisitoCertificado) {
            $evidencia = $this->evidenciaPrincipalDelRequisito($requisitoCertificado);
            $configuracion = $configuraciones->get($requisitoCertificado->id_requisito);
            $codigo = mb_strtoupper((string) ($evidencia?->tipoEvidencia?->codigo ?? $configuracion?->tipoEvidencia?->codigo ?? ''));
            $nombreRequisito = $requisitoCertificado->requisito?->descripcion ?? 'Requisito observado';
            $tieneArchivoNuevo = ! empty($archivos[$requisitoCertificado->id]);
            $textoNuevo = trim((string) ($textos[$requisitoCertificado->id] ?? ''));
            $ultimaObservacion = $this->ultimaObservacionRequisito($requisitoCertificado);
            $yaTieneCorreccionGuardada = $evidencia
                && filled($evidencia->valor)
                && $ultimaObservacion
                && $evidencia->created_at?->gt($ultimaObservacion->created_at);

            if (in_array($codigo, ['PDF', 'IMAGEN'], true) && ! $tieneArchivoNuevo && ! $yaTieneCorreccionGuardada) {
                $errores["documentos_correccion.{$requisitoCertificado->id}"] = "Debe adjuntar la evidencia corregida para: {$nombreRequisito}.";
            }

            if ($codigo === 'TEXTO' && $textoNuevo === '' && ! $yaTieneCorreccionGuardada) {
                $errores["textos_correccion.{$requisitoCertificado->id}"] = "Debe registrar el texto corregido para: {$nombreRequisito}.";
            }

            if (in_array($codigo, ['PDF', 'IMAGEN'], true) && ! $tieneArchivoNuevo && $yaTieneCorreccionGuardada
                && ! Storage::disk('local')->exists($evidencia->valor) && ! Storage::disk('public')->exists($evidencia->valor)) {
                $errores["documentos_correccion.{$requisitoCertificado->id}"] = "Debe adjuntar el archivo corregido para: {$nombreRequisito}. El archivo guardado no está disponible.";
            }

            if ($codigo === 'PAGO' && ! $certificado->pagos()->whereNotNull('fecha_validacion')->exists()) {
                $errores["requisito_correccion_{$requisitoCertificado->id}"] = "Debe contar con el pago validado para: {$nombreRequisito}.";
            }

            if ($codigo === 'CERTIFICADO') {
                if (! $configuracion?->dependenciasRequisitos->where('estado', 'ACTIVO')->isNotEmpty()) {
                    $errores["requisito_correccion_{$requisitoCertificado->id}"] = "INSO debe configurar el certificado requerido para: {$nombreRequisito}.";
                } else {
                    try {
                        $this->validarCertificadosPrevios((int) $certificado->id_persona_beneficiario, (int) $certificado->id_tipo_certificado);
                    } catch (ValidationException $e) {
                        $errores["requisito_correccion_{$requisitoCertificado->id}"] = implode(' ', array_merge(...array_values($e->errors())));
                    }
                }
            }

            // Producto, atención presencial y calificación se completan por personal de INSO.
            if (! in_array($codigo, ['PDF', 'IMAGEN', 'TEXTO', 'PRODUCTO', 'PAGO', 'CERTIFICADO', 'PRESENCIAL', 'CALIFICACION', 'TRAMITADOR'], true)) {
                $errores["requisito_correccion_{$requisitoCertificado->id}"] = "No se puede enviar: INSO debe configurar la evidencia de {$nombreRequisito}.";
            }

        }

        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
    }

    // Busca el id del tipo de evidencia configurado. PDF es el respaldo por defecto para documentos.
    private function idTipoEvidencia(string $codigo): int
    {
        $idTipoEvidencia = TipoEvidencia::query()
            ->where('codigo', $codigo)
            ->value('id');

        if (! $idTipoEvidencia) {
            throw new \RuntimeException('No existe el tipo de evidencia '.$codigo.'. Revise el seeder de tipos de evidencias.');
        }

        return (int) $idTipoEvidencia;
    }

    private function cerrarEtapasActivasDelTramite(int $idCertificado): void
    {
        Seguimiento::query()
            ->where('id_certificado', $idCertificado)
            ->where('estado', 'ACTIVO')
            ->whereNull('fecha_derivacion')
            ->update([
                'fecha_derivacion' => now(),
                'id_usuario_modificacion' => auth()->id(),
                'updated_at' => now(),
            ]);
    }

    // Ultima observacion registrada para un requisito concreto del tramite.
    private function ultimaObservacionRequisito(RequisitoCertificado $requisitoCertificado): ?ObservacionRequisito
    {
        return ObservacionRequisito::query()
            ->with('revisionRequisito.usuarioRevisor')
            ->whereHas('revisionRequisito', function ($query) use ($requisitoCertificado) {
                $query->where('id_requisito_certificado', $requisitoCertificado->id);
            })
            ->latest('id')
            ->first();
    }

    // Ultima observacion dentro de varios requisitos del mismo certificado.
    private function ultimaObservacionPorRequisitos(Certificado $certificado, array $idsRequisitos): ?ObservacionRequisito
    {
        return ObservacionRequisito::query()
            ->with('revisionRequisito.usuarioRevisor')
            ->whereHas('revisionRequisito.requisitoCertificado', function ($query) use ($certificado, $idsRequisitos) {
                $query->where('id_certificado', $certificado->id)
                    ->whereIn('id_requisito', $idsRequisitos);
            })
            ->latest('id')
            ->first();
    }

    // La persona que registra una baja o transferencia no sustituye al revisor técnico.
    private function revisorDestinoCorreccion(Certificado $certificado, Seguimiento $seguimiento, Collection $requisitosObservados): User
    {
        $revision = RevisionRequisito::query()
            ->whereIn('id_requisito_certificado', $requisitosObservados->pluck('id'))
            ->whereHas('requisitoCertificado', fn ($query) => $query->where('id_certificado', $certificado->id))
            ->where('resultado_cumple', 'NO')
            ->where('estado', 'ACTIVO')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $idRevisor = $revision?->id_usuario_revisor;

        // Compatibilidad con trámites anteriores sin revisiones: recorre solo su cadena de movimientos.
        if (! $revision) {
            $visitados = [];
            $movimiento = $seguimiento;
            while ($movimiento && (int) $movimiento->id_certificado === (int) $certificado->id
                && ! in_array($movimiento->id, $visitados, true)) {
                $visitados[] = $movimiento->id;
                $origen = User::find($movimiento->id_usuario_origen);
                $esTransferenciaPorBaja = str_contains(mb_strtolower((string) $movimiento->descripcion_final), 'baja del tramitador');
                if (! $esTransferenciaPorBaja && $this->usuarioTieneCargoActivo($origen)) {
                    $idRevisor = $origen->id;
                    break;
                }
                $movimiento = $movimiento->seguimientoPadre;
            }
        }

        $revisor = $idRevisor ? User::find($idRevisor) : null;
        if (! $this->usuarioTieneCargoActivo($revisor)
            || (int) $revisor?->id === (int) $seguimiento->id_usuario_siguiente) {
            throw ValidationException::withMessages([
                'form_accion_correccion' => 'No se encontró un revisor técnico activo para recibir las correcciones. Comuníquese con INSO.',
            ]);
        }

        return $revisor;
    }

    // Toma el ultimo usuario revisor desde revisiones_requisitos cuando no hay observacion activa.
    private function ultimoRevisorPorRequisitos($requisitosObservados): ?int
    {
        return RevisionRequisito::query()
            ->whereIn('id_requisito_certificado', $requisitosObservados->pluck('id')->all())
            ->latest('id')
            ->value('id_usuario_revisor');
    }

    // AGRUPA OBSERVACIONES DEL FORMULARIO
    // Como requisitos_certificados no tiene columna de observacion, se conserva en certificados.descripcion.
    private function descripcionTramite(?string $descripcionGeneral, array $requisitos): ?string
    {
        $partes = [];

        if ($descripcionGeneral) {
            $partes[] = trim($descripcionGeneral);
        }

        $observaciones = collect($requisitos)
            ->filter(fn ($item) => filled($item['observacion'] ?? null))
            ->map(fn ($item) => 'Requisito '.($item['id_requisito'] ?? '-').': '.trim($item['observacion']))
            ->values();

        if ($observaciones->isNotEmpty()) {
            $partes[] = 'Observaciones por requisito:'.PHP_EOL.$observaciones->implode(PHP_EOL);
        }

        return $partes ? implode(PHP_EOL.PHP_EOL, $partes) : null;
    }

    // LISTA DE FUNCIONARIOS PARA ASIGNACION / DERIVACION
    // La carga se calcula por tramites pendientes y el destino debe tener cargo activo.
    private function tecnicosDisponiblesParaAsignacion()
    {
        return User::query()
            ->with([
                'roles',
                // Carga cargo y area para mostrar el selector tecnico sin consultas adicionales por fila.
                'funcionario.cargos' => fn ($query) => $query->with('area')->where('estado', 1),
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
    }

    // Busca quien recibe primero el tramite segun el area configurada en el tipo de certificado.
    private function usuarioReceptorArea(int $idArea): ?User
    {
        $idsAreas = [$idArea];

        return User::query()
            ->with('funcionario.cargos')
            ->where('estado', 1)
            ->whereHas('funcionario', function ($query) use ($idsAreas) {
                $query->where('estado', 1)
                    ->whereHas('cargos', function ($cargo) use ($idsAreas) {
                        $cargo->whereIn('id_area', $idsAreas)
                            ->where('estado', 1);
                    });
            })
            ->get()
            ->sortBy(function (User $usuario) use ($idsAreas) {
                $cargo = $usuario->funcionario?->cargos
                    ->whereIn('id_area', $idsAreas)
                    ->where('estado', 1)
                    ->sortBy(function ($cargo) use ($idsAreas) {
                        $nombreCargo = strtoupper((string) $cargo->nombre);
                        $ordenArea = array_search((int) $cargo->id_area, $idsAreas, true);

                        $prioridadCargo = match (true) {
                            str_contains($nombreCargo, 'JEFE') => 0,
                            str_contains($nombreCargo, 'DIRECTOR') => 1,
                            default => 2,
                        };

                        return $prioridadCargo.'-'.$ordenArea.'-'.$cargo->nombre;
                    })
                    ->first();

                $nombreCargo = strtoupper((string) $cargo?->nombre);
                $ordenArea = array_search((int) $cargo?->id_area, $idsAreas, true);
                $funcionario = $usuario->funcionario;
                $nombreFuncionario = $funcionario
                    ? trim(implode(' ', array_filter([
                        $funcionario->nombres,
                        $funcionario->apellido_paterno,
                        $funcionario->apellido_materno,
                    ])))
                    : '';
                $nombreOrden = $nombreFuncionario ?: ($usuario->email ?: 'Sin funcionario');

                return match (true) {
                    str_contains($nombreCargo, 'JEFE') => '0-'.$ordenArea.'-'.$nombreOrden,
                    str_contains($nombreCargo, 'DIRECTOR') => '1-'.$ordenArea.'-'.$nombreOrden,
                    default => '2-'.$ordenArea.'-'.$nombreOrden,
                };
            })
            ->first();
    }

    // Envia aviso al funcionario responsable del area inicial.
    private function notificarTramiteRecibido(Certificado $certificado, ?User $usuarioDestino, ?Area $areaDestino = null): void
    {
        if (! $usuarioDestino || ! Schema::hasTable('notificaciones_tramites')) {
            return;
        }

        $datos = [
            'id_usuario_destino' => $usuarioDestino->id,
            'id_certificado' => $certificado->id,
            'titulo' => 'Nueva solicitud de tramite',
            'mensaje' => 'Se registro una solicitud para atencion inicial en '.($areaDestino?->nombre ?? 'el area asignada').'.',
            'estado' => 'ACTIVO',
        ];

        if (Schema::hasColumn('notificaciones_tramites', 'id_usuario_emisor')) {
            $datos['id_usuario_emisor'] = auth()->id();
        }

        NotificacionTramite::create($datos);
    }

    // Envia una notificacion puntual sin guardar datos JSON ni estructuras libres.
    private function notificarUsuarioTramite(?User $usuario, Certificado $certificado, string $titulo, string $mensaje): void
    {
        if (! $usuario || ! Schema::hasTable('notificaciones_tramites')) {
            return;
        }

        $datos = [
            'id_usuario_destino' => $usuario->id,
            'id_certificado' => $certificado->id,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'estado' => 'ACTIVO',
        ];

        if (Schema::hasColumn('notificaciones_tramites', 'id_usuario_emisor')) {
            $datos['id_usuario_emisor'] = auth()->id();
        }

        NotificacionTramite::create($datos);
    }

    // Una notificacion queda atendida solo despues de ejecutar una accion real sobre el tramite.
    private function marcarNotificacionesAtendidas(int $idCertificado, ?int $idUsuario = null): void
    {
        if (! Schema::hasTable('notificaciones_tramites')) {
            return;
        }

        $idUsuario = $idUsuario ?: auth()->id();

        if (! $idUsuario) {
            return;
        }

        $consulta = NotificacionTramite::query()
            ->where('id_certificado', $idCertificado)
            ->where('id_usuario_destino', $idUsuario)
            ->whereIn('estado', ['ACTIVO', 'VISTO']);

        // Si se atendio directamente desde la bandeja, tambien queda registrada como vista.
        (clone $consulta)
            ->whereNull('fecha_visto')
            ->update([
                'fecha_visto' => now(),
                'id_usuario_modificacion' => $idUsuario,
                'updated_at' => now(),
            ]);

        $consulta->update([
            'estado' => 'ATENDIDA',
            'id_usuario_modificacion' => $idUsuario,
            'updated_at' => now(),
        ]);
    }

    // Verifica que el tecnico actual pueda revisar esta etapa.
    // Administrador tambien puede hacerlo para pruebas y soporte interno.
    private function usuarioPuedeRevisarSeguimiento(Seguimiento $seguimiento): bool
    {
        $seguimiento->loadMissing('usuarioSiguiente.roles', 'usuarioSiguiente.funcionario.cargos');

        if (! auth()->check() || $seguimiento->estado !== 'ACTIVO' || $seguimiento->fecha_derivacion) {
            return false;
        }

        if (auth()->user()->tieneRol('administrador')) {
            return true;
        }

        return (int) $seguimiento->id_usuario_siguiente === (int) auth()->id()
            && (
                $seguimiento->usuarioSiguiente?->tieneRol('funcionario')
                || $this->usuarioTieneCargoActivo($seguimiento->usuarioSiguiente)
            );
    }

    // Verifica que solo el jefe/administrador pueda asignar tecnico.
    // Esta validacion protege el POST aunque alguien intente mostrar el boton desde el navegador.
    private function usuarioPuedeAsignarTecnico(Seguimiento $seguimiento): bool
    {
        return auth()->check()
            && auth()->user()->tieneRol('administrador')
            && (int) $seguimiento->id_usuario_siguiente === (int) auth()->id()
            && $seguimiento->estado === 'ACTIVO'
            && ! $seguimiento->fecha_derivacion;
    }

    // Define quien puede ver la bandeja de solicitudes recibidas.
    // El solicitante solo debe ver "Enviadas por mi"; no se autoevalua ni atiende tramites.
    private function usuarioPuedeAtenderTramites(): bool
    {
        return $this->usuarioPuedeVerBandejasInternas(auth()->user());
    }

    // Regla central para pantallas internas de seguimiento:
    // administrador, tecnico evaluador o funcionario con cargo activo.
    private function usuarioPuedeVerBandejasInternas(?User $usuario): bool
    {
        return (bool) $usuario
            && (
                $usuario->tieneRol('administrador')
                || $usuario->tieneRol('funcionario')
                || $this->usuarioTieneCargoActivo($usuario)
            );
    }

    // FUNCIONARIO HABILITADO PARA RECIBIR TRAMITES
    // Permite que cualquier usuario interno con cargo activo atienda una derivacion.
    private function usuarioTieneCargoActivo(?User $usuario): bool
    {
        $usuario?->loadMissing('funcionario.cargos');

        return (string) $usuario?->estado === '1'
            && (bool) $usuario?->funcionario
            && $usuario->funcionario->cargos->contains(fn ($cargo) => (string) $cargo->estado === '1');
    }

    // Verifica que el usuario logueado sea quien recibio la observacion.
    private function usuarioPuedeReenviarCorreccion(Seguimiento $seguimiento): bool
    {
        $usuario = auth()->user();
        $certificado = $seguimiento->certificado;

        return $usuario
            && $certificado
            && (int) $seguimiento->id_usuario_siguiente === (int) $usuario->id
            && $seguimiento->estado === 'ACTIVO'
            && ! $seguimiento->fecha_derivacion
            && $certificado->estado === 'OBSERVADO'
            && $this->gestionTramitadores->usuarioPuedeResponderCorreccion($usuario, $certificado);
    }

    // Verifica si un funcionario puede registrar que la correccion presencial ya fue recibida.
    private function usuarioPuedeRegistrarCorreccionRecibida(Seguimiento $seguimiento): bool
    {
        if (
            ! auth()->check()
            || $seguimiento->estado !== 'ACTIVO'
            || $seguimiento->fecha_derivacion
            || $seguimiento->certificado?->estado !== 'OBSERVADO'
        ) {
            return false;
        }

        // El solicitante no registra la recepcion institucional de su propia correccion.
        if ((int) $seguimiento->id_usuario_siguiente === (int) auth()->id()) {
            return false;
        }

        return auth()->user()->tieneRol('administrador')
            || auth()->user()->tieneRol('funcionario')
            || $this->usuarioTieneCargoActivo(auth()->user());
    }

    // NOMBRE LEGIBLE DE PERSONA
    // Devuelve razon social si es empresa o nombre completo si es natural.
    private function nombrePersona(?Persona $persona): string
    {
        if (! $persona) {
            return 'Sin persona';
        }

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
}
