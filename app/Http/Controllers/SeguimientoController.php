<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Certificado;
use App\Models\DependenciaRequisito;
use App\Models\EvidenciaRequisito;
use App\Models\NotificacionTramite;
use App\Models\ObservacionRequisito;
use App\Models\Persona;
use App\Models\RequisitoCertificado;
use App\Models\RequisitoTipoCertificado;
use App\Models\Responsable;
use App\Models\RevisionRequisito;
use App\Models\Seguimiento;
use App\Models\TipoCertificado;
use App\Models\TipoEvidencia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SeguimientoController extends Controller
{
    private const TOKENS_INICIO_TRAMITE = 'tokens_inicio_tramite';

    /**
     * Muestra una sola bandeja de tramites segun la ruta actual:
     * enviadas, recibidas o consulta general.
     */
    public function index(Request $request)
    {
        // Funcionarios disponibles para asignar solicitudes desde la bandeja.
        // Salen de usuarios con ficha de funcionario y al menos un cargo activo.
        $tecnicos = $this->tecnicosDisponiblesParaAsignacion();

        // La misma vista se usa para las bandejas del tramite, pero siempre con una sola tabla visible.
        $bandeja = match (true) {
            $request->routeIs('seguimientos_mis_solicitudes') => 'enviadas',
            $request->routeIs('seguimientos_todos') => 'todos',
            default => 'recibidas',
        };

        if (in_array($bandeja, ['recibidas', 'todos'], true) && !$this->usuarioPuedeAtenderTramites()) {
            return redirect()
                ->route('seguimientos_mis_solicitudes')
                ->with('error', 'Su usuario solo puede consultar las solicitudes que envio.');
        }

        $tituloPagina = match ($bandeja) {
            'enviadas' => 'Solicitudes enviadas por mi',
            'todos' => 'Consulta general de tramites',
            default => 'Solicitudes recibidas para atender',
        };
        $descripcionPagina = match ($bandeja) {
            'enviadas' => 'Consulte los tramites que usted inicio y revise en que etapa se encuentran.',
            'todos' => 'Listado de solo lectura para hacer seguimiento de cualquier tramite registrado.',
            default => 'Bandeja de trabajo para revisar, asignar o derivar tramites que llegaron a su usuario.',
        };

        // Cada bandeja usa su propia carpeta de vista para ubicar rapido botones, estilos y textos.
        $vistaBandeja = match ($bandeja) {
            'enviadas' => 'seguimientos_certificados.mis_tramites.index',
            'todos' => 'seguimientos_certificados.seguimiento_tramite.index',
            default => 'seguimientos_certificados.tramites_atender.index',
        };

        return view($vistaBandeja, compact('tecnicos', 'bandeja', 'tituloPagina', 'descripcionPagina'));
    }

    /**
     * Abre el formulario para iniciar una solicitud de tramite.
     */
    public function create()
    {
        // El inicio de tramite solo requiere solicitante, tipo y documentos.
        // El pago queda para una revision posterior del funcionario/tecnico.
        return view('seguimientos_certificados.nuevo_tramite.create', array_merge(
            $this->datosFormularioTramite(),
            ['tokenFormulario' => $this->crearTokenEnvioTramite()]
        ));
    }

    /**
     * Registra el tramite inicial, sus requisitos y el primer movimiento de seguimiento.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarTramite($solicitud);

        // Esta validacion tambien cubre peticiones hechas sin usar la pantalla del sistema.
        $this->validarQueUsuarioPuedeRegistrarBeneficiario(
            $solicitud->user(),
            (int) $datos['form_id_persona_beneficiario']
        );

        // El tramitador final se decide en backend para no confiar en datos manipulados desde la vista.
        $datos['form_id_persona_tramitador'] = $this->resolverTramitadorDelBeneficiario(
            (int) $datos['form_id_persona_beneficiario'],
            isset($datos['form_id_persona_tramitador']) ? (int) $datos['form_id_persona_tramitador'] : null
        );

        $this->validarCertificadosPrevios(
            (int) $datos['form_id_persona_beneficiario'],
            (int) $datos['form_id_tipo_certificado']
        );

        $tipoCertificado = $this->buscarTipoCertificadoActivo((int) $datos['form_id_tipo_certificado']);
        $asignacionesRequisitos = $this->requisitosConfiguradosDelTipoCertificado((int) $tipoCertificado->id);

        $this->validarArchivosSubidosDeRequisitos($solicitud, $asignacionesRequisitos);

        $this->validarTokenEnvioTramite($solicitud);

        try {
            DB::beginTransaction();

            $codigo = $this->generarCodigoTramite();
            $estado = 'EN_REVISION';

            $areaDestino = $tipoCertificado->area;

            if (!$areaDestino) {
                throw new \Exception('El tipo de certificado no tiene un area configurada para iniciar el tramite.');
            }

            if ((string) $areaDestino->estado !== '1' && (string) $areaDestino->estado !== 'ACTIVO') {
                throw new \Exception('El area configurada para este tipo de certificado no esta activa.');
            }

            // El receptor inicial se calcula desde areas, cargos, funcionarios y usuarios activos.
            $usuarioDestino = $this->usuarioReceptorArea((int) $areaDestino->id);

            if (!$usuarioDestino) {
                throw new \Exception('No existe un funcionario activo para recibir solicitudes en el area seleccionada.');
            }

            $descripcion = $this->descripcionTramite(
                $datos['form_descripcion'] ?? null,
                $solicitud->input('requisitos_certificados', [])
            );

            // La solicitud del tramite se guarda en certificados porque esa tabla ya representa
            // la autorizacion/certificado solicitada por beneficiario, tramitador y tipo.
            $certificado = Certificado::create([
                'id_tipo_certificado' => $tipoCertificado->id,
                'id_persona_beneficiario' => $datos['form_id_persona_beneficiario'],
                'id_persona_tramitador' => $datos['form_id_persona_tramitador'],
                'codigo' => $codigo,
                // La fecha no se solicita al usuario: se toma automaticamente al registrar el tramite.
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => null,
                'descripcion' => $descripcion,
                'url_documento' => null,
                'estado' => $estado,
            ]);

            $this->guardarRequisitosTramite(
                $certificado,
                $asignacionesRequisitos,
                $this->posicionesDeArchivosPorRequisitoConfigurado($solicitud),
                $solicitud->file('documentos_requisitos', [])
            );

            // Primer movimiento del flujo: el tramite queda enviado al responsable inicial del area.
            Seguimiento::create([
                'id_seguimiento_padre' => null,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now()->toDateString(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => 'Solicitud enviada a ' . $areaDestino->nombre . ' para iniciar la tramitacion.',
                'referencia' => 'Inicio de tramite ' . $codigo,
                'id_usuario_anterior' => null,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $usuarioDestino->id,
                'estado' => 'ACTIVO',
            ]);

            // Notifica solamente al responsable inicial para que atienda la nueva solicitud.
            $this->notificarTramiteRecibido($certificado, $usuarioDestino, $areaDestino);

            DB::commit();

            // Mensaje unico para evitar alertas duplicadas al volver a la bandeja.
            session()->flash('swal', [
                'title' => 'Tramite registrado',
                'text' => 'La solicitud fue enviada y derivada al area correspondiente.',
                'icon' => 'success',
            ]);

            // Si el usuario autenticado es el solicitante real, va a su bandeja "Mis tramites".
            // Si un funcionario INSO cargo el tramite por primera vez, va a seguimiento general.
            return redirect()->route($this->rutaDespuesDeRegistrarTramite($certificado, $solicitud->user()));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo iniciar el tramite. ' . $e->getMessage())
                ->withInput();
        }
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

    // El token se consume antes de crear el tramite; si llega otra vez, no se vuelve a registrar.
    private function validarTokenEnvioTramite(Request $request): void
    {
        $token = $request->input('form_token');
        $tokens = session(self::TOKENS_INICIO_TRAMITE, []);

        if (!$token || !isset($tokens[$token])) {
            throw ValidationException::withMessages([
                'form_token' => 'La solicitud ya fue enviada o el formulario vencio. Actualice la pantalla e intente nuevamente.',
            ]);
        }

        unset($tokens[$token]);
        session()->put(self::TOKENS_INICIO_TRAMITE, $tokens);
    }

    /**
     * Abre el detalle completo del tramite desde la fila de seguimiento.
     */
    public function show(Seguimiento $seguimiento)
    {
        /*
         * Ver tramite usa el detalle existente del certificado.
         * No se crea otra vista para mantener el flujo en los archivos ya existentes.
         */
        if (!$seguimiento->certificado) {
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
        if (!$seguimiento->certificado) {
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

        if (!$esSolicitante && !$participaEnSeguimiento && !$puedeConsultaGeneral) {
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
        if (!$usuario) {
            return false;
        }

        return in_array((int) $usuario->id, [
            (int) $certificado->beneficiario?->id_usuario,
            (int) $certificado->tramitador?->id_usuario,
        ], true);
    }

    // Usuario interno participante: quien registro, recibio o tuvo antes el tramite.
    private function usuarioParticipaEnHistorial(?User $usuario, Certificado $certificado, bool $esUsuarioInterno): bool
    {
        if (!$usuario) {
            return false;
        }

        return $certificado->seguimientos->contains(function ($movimiento) use ($usuario, $esUsuarioInterno) {
            return (
                    $esUsuarioInterno
                    && (int) $movimiento->id_usuario_origen === (int) $usuario->id
                )
                || (int) $movimiento->id_usuario_siguiente === (int) $usuario->id
                || (int) $movimiento->id_usuario_anterior === (int) $usuario->id;
        });
    }

    // NOTIFICACIONES DE TRAMITES
    // Devuelve las notificaciones no leidas para refrescar la campana sin recargar la pagina.
    public function notificacionesTramites(Request $request)
    {
        if (!Schema::hasTable('notificaciones_tramites')) {
            return response()->json([
                'total' => 0,
                'notificaciones' => [],
            ]);
        }

        $consultaBase = NotificacionTramite::query()
            ->with(
                'usuarioEmisor.funcionario.cargos',
                'usuarioEmisor.persona.empresa',
                'usuarioEmisor.persona.natural',
                'certificado.tipoCertificado',
                'certificado.beneficiario.natural',
                'certificado.beneficiario.empresa',
                'certificado.tramitador.natural',
                'certificado.tramitador.empresa'
            )
            ->where('id_usuario_destino', $request->user()->id)
            ->whereNull('fecha_visto')
            ->where('estado', 'ACTIVO');

        $notificaciones = (clone $consultaBase)
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($notificacion) use ($request) {
                $certificado = $notificacion->certificado;
                $remitente = $this->datosUsuarioNotificacion($notificacion->usuarioEmisor);
                // La notificacion puede llegar al beneficiario o al tramitador que representa el tramite.
                $esSolicitante = $certificado
                    && (
                        (int) $certificado->beneficiario?->id_usuario === (int) $request->user()->id
                        || (int) $certificado->tramitador?->id_usuario === (int) $request->user()->id
                    );

                return [
                    'id' => $notificacion->id,
                    'titulo' => $notificacion->titulo,
                    'mensaje' => $notificacion->mensaje ?? '',
                    'codigo' => $certificado?->codigo ?? '',
                    'tipo' => $certificado?->tipoCertificado?->nombre ?? 'Tramite',
                    'beneficiario' => $this->nombrePersona($certificado?->beneficiario),
                    'quien_envia' => $remitente['nombre'],
                    'quien_envia_detalle' => $remitente['detalle'],
                    'url' => $certificado
                        ? route('certificados_show', [
                            'certificado' => $certificado,
                            'bandeja' => $esSolicitante ? 'enviadas' : 'recibidas',
                        ])
                        : ($esSolicitante ? route('seguimientos_mis_solicitudes') : route('seguimientos_index')),
                    'fecha' => $notificacion->created_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    'fecha_humana' => $notificacion->created_at?->diffForHumans() ?? 'Sin fecha',
                ];
            });

        return response()->json([
            'total' => $consultaBase->count(),
            'notificaciones' => $notificaciones,
        ]);
    }

    // Marca una notificacion como vista cuando el usuario decide atenderla.
    public function marcarNotificacionTramite(Request $request, string $notificacion)
    {
        if (!Schema::hasTable('notificaciones_tramites')) {
            return response()->json(['ok' => true]);
        }

        $notificacion = NotificacionTramite::query()
            ->where('id_usuario_destino', $request->user()->id)
            ->whereNull('fecha_visto')
            ->whereKey($notificacion)
            ->firstOrFail();

        $notificacion->update([
            'fecha_visto' => now(),
            'estado' => 'VISTO',
        ]);

        return response()->json(['ok' => true]);
    }

    // Marca todas como vistas desde la campana.
    public function marcarTodasNotificacionesTramite(Request $request)
    {
        if (!Schema::hasTable('notificaciones_tramites')) {
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
        if (!$this->usuarioPuedeAsignarTecnico($seguimiento)) {
            abort(403, 'No tiene permiso para asignar tecnico a este tramite.');
        }

        $datos = $request->validate([
            'id_tecnico' => ['required', 'exists:users,id'],
            'descripcion_derivacion' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'id_tecnico' => 'tecnico',
            'descripcion_derivacion' => 'descripcion de derivacion',
        ]);

        if ((int) $datos['id_tecnico'] === (int) $seguimiento->id_usuario_siguiente) {
            return back()
                ->withErrors(['id_tecnico' => 'Seleccione un funcionario diferente al funcionario actual.'])
                ->withInput();
        }

        $tecnico = User::query()
            ->whereKey($datos['id_tecnico'])
            ->where('estado', 1)
            ->whereHas('funcionario', function ($query) {
                $query->where('estado', 1)
                    ->whereHas('cargos', fn ($cargo) => $cargo->where('estado', 1));
            })
            ->first();

        if (!$tecnico) {
            return back()->with('error', 'Seleccione un funcionario activo con cargo asignado.');
        }

        try {
            DB::beginTransaction();

            $seguimientoBloqueado = Seguimiento::query()
                ->whereKey($seguimiento->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$this->usuarioPuedeAsignarTecnico($seguimientoBloqueado)) {
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
                'fecha_inicio' => now()->toDateString(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => 'Funcionario asignado para revision.',
                'referencia' => $datos['descripcion_derivacion'] ?: 'Asignacion de solicitud.',
                'id_usuario_anterior' => $seguimientoBloqueado->id_usuario_siguiente ?: $seguimientoBloqueado->id_usuario_origen,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $tecnico->id,
                'estado' => 'ACTIVO',
            ]);

            $certificado->update(['estado' => 'EN_REVISION']);

            // Aviso simple para que el tecnico vea la solicitud en su bandeja.
            $this->notificarUsuarioTramite(
                $tecnico,
                $certificado,
                'Tramite asignado',
                'Tiene una solicitud pendiente para revision.'
            );

            DB::commit();

            session()->flash('swal', [
                'title' => 'Funcionario asignado',
                'text' => 'La solicitud fue derivada para revision.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo asignar el tecnico. ' . $e->getMessage());
        }
    }

    // DERIVAR TRAMITE A OTRO FUNCIONARIO
    // Mantiene el mismo certificado/tramite y solo crea un nuevo movimiento en seguimientos.
    public function derivarTecnico(Request $request, Seguimiento $seguimiento)
    {
        $datos = $request->validate([
            'id_tecnico_destino' => ['required', 'exists:users,id'],
            'motivo_derivacion' => ['required', 'string', 'max:1000'],
        ], [], [
            'id_tecnico_destino' => 'funcionario destino',
            'motivo_derivacion' => 'motivo de derivacion',
        ]);

        if (!$this->usuarioPuedeRevisarSeguimiento($seguimiento)) {
            return back()->with('error', 'Solo el funcionario asignado puede derivar este tramite.');
        }

        if ((int) $datos['id_tecnico_destino'] === (int) $seguimiento->id_usuario_siguiente) {
            return back()
                ->withErrors(['id_tecnico_destino' => 'Seleccione un funcionario diferente al funcionario actual.'])
                ->withInput();
        }

        $funcionarioDestino = User::query()
            ->whereKey($datos['id_tecnico_destino'])
            ->where('estado', 1)
            ->whereHas('funcionario', function ($query) {
                $query->where('estado', 1)
                    ->whereHas('cargos', fn ($cargo) => $cargo->where('estado', 1));
            })
            ->first();

        if (!$funcionarioDestino) {
            return back()->with('error', 'El usuario destino debe ser un funcionario activo con cargo asignado.');
        }

        try {
            DB::beginTransaction();

            $seguimientoBloqueado = Seguimiento::query()
                ->whereKey($seguimiento->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$this->usuarioPuedeRevisarSeguimiento($seguimientoBloqueado)) {
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
                'fecha_inicio' => now()->toDateString(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => 'Tramite derivado a otro tecnico.',
                'referencia' => trim($datos['motivo_derivacion']),
                'id_usuario_anterior' => $seguimientoBloqueado->id_usuario_siguiente,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $funcionarioDestino->id,
                'estado' => 'ACTIVO',
            ]);

            $seguimientoBloqueado->certificado?->update(['estado' => 'EN_REVISION']);

            // Notifica al tecnico destino para que el cambio se refleje en su bandeja de trabajo.
            if ($seguimientoBloqueado->certificado) {
                $this->notificarUsuarioTramite(
                    $funcionarioDestino,
                    $seguimientoBloqueado->certificado,
                    'Tramite derivado',
                    'Recibio una solicitud derivada para su revision.'
                );
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

            return back()->with('error', 'No se pudo derivar el tramite. ' . $e->getMessage());
        }
    }

    // REVISION TECNICA DE REQUISITOS
    // Guarda los checks de cumple/no cumple. No notifica al solicitante hasta presionar el boton de correccion.
    public function revisarTecnico(Request $request, Seguimiento $seguimiento)
    {
        $datos = $request->validate([
            'requisitos_revision' => ['required', 'array'],
            'requisitos_revision.*.id' => ['required', 'exists:requisitos_certificados,id'],
            'requisitos_revision.*.tocado' => ['nullable', 'in:0,1'],
            // La revision puede ser parcial: cada funcionario marca solo lo que reviso.
            'requisitos_revision.*.cumple' => ['nullable', 'in:SI,NO'],
            'requisitos_revision.*.observacion' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'requisitos_revision' => 'requisitos',
            'requisitos_revision.*.cumple' => 'cumplimiento',
            'requisitos_revision.*.observacion' => 'observacion tecnica',
        ]);

        if (!$this->usuarioPuedeRevisarSeguimiento($seguimiento)) {
            return back()->with('error', 'Este tramite no esta asignado al tecnico actual.');
        }

        $requisitosMarcados = collect($datos['requisitos_revision'])
            ->filter(fn ($item) => ($item['tocado'] ?? '0') === '1' && filled($item['cumple'] ?? null))
            ->values();

        if ($requisitosMarcados->isEmpty()) {
            return back()
                ->withErrors(['requisitos_revision' => 'Seleccione al menos un requisito para guardar la revision.'])
                ->withInput();
        }

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
                $requisitoCertificado = RequisitoCertificado::query()
                    ->where('id_certificado', $seguimiento->id_certificado)
                    ->findOrFail($item['id']);

                $cumple = $item['cumple'];
                $observacion = trim($item['observacion'] ?? '');

                $requisitoCertificado->update([
                    'cumple' => $cumple,
                    // REVISION_OBSERVADA guarda la observacion sin notificar todavia al solicitante.
                    'estado' => $cumple === 'SI' ? 'ACTIVO' : 'REVISION_OBSERVADA',
                ]);

                // Cada revision queda registrada como historial tecnico del requisito.
                $revision = RevisionRequisito::create([
                    'id_requisito_certificado' => $requisitoCertificado->id,
                    'id_evidencia_requisito' => $requisitoCertificado->evidenciasRequisitos()->latest('id')->value('id'),
                    'id_usuario_revisor' => auth()->id(),
                    'resultado_cumple' => $cumple,
                    'estado' => 'ACTIVO',
                ]);

                if ($cumple === 'NO') {
                    $hayObservados = true;

                    // La observacion pertenece a la revision exacta, tal como esta en el diagrama.
                    ObservacionRequisito::create([
                        'id_revision_requisito' => $revision->id,
                        'observacion' => $observacion,
                        'estado' => 'ACTIVA',
                    ]);
                }
            }

            $certificado = $seguimiento->certificado()->firstOrFail();
            $certificado->load('certificadoRequisitos');
            $todosRequisitosCumplen = $certificado->certificadoRequisitos
                ->every(fn ($requisito) => $requisito->cumple === 'SI');

            if ($hayObservados) {
                // Se mantiene en revision hasta que el jefe/tecnico pulse "Notificar al solicitante".
                $certificado->update(['estado' => 'EN_REVISION']);
            } elseif ($todosRequisitosCumplen) {
                $certificado->update(['estado' => 'APROBADO']);

                // Si no hay observaciones, se cierra la etapa activa porque la revision concluyo.
                $seguimiento->update([
                    'fecha_derivacion' => now()->toDateString(),
                    'fecha_final' => now()->toDateString(),
                ]);

                // Movimiento de cierre tecnico cuando todos los requisitos cumplen.
                Seguimiento::create([
                    'id_seguimiento_padre' => $seguimiento->id,
                    'id_certificado' => $certificado->id,
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_derivacion' => now()->toDateString(),
                    'fecha_final' => now()->toDateString(),
                    'descripcion_final' => 'Revision tecnica aprobada.',
                    'referencia' => 'Todos los requisitos cumplen.',
                    'id_usuario_anterior' => $seguimiento->id_usuario_siguiente,
                    'id_usuario_origen' => auth()->id(),
                    'id_usuario_siguiente' => null,
                    'estado' => 'ACTIVO',
                ]);
            } else {
                // Revision parcial: queda pendiente para que otro funcionario complete lo faltante.
                $certificado->update(['estado' => 'EN_REVISION']);
            }

            DB::commit();

            session()->flash('swal', [
                'title' => $todosRequisitosCumplen && !$hayObservados ? 'Tramite aprobado' : 'Revision guardada',
                'text' => $hayObservados
                    ? 'Hay requisitos observados. Use el boton para notificar al solicitante cuando termine la revision.'
                    : ($todosRequisitosCumplen
                        ? 'La revision tecnica fue aprobada correctamente.'
                        : 'Se guardo la revision parcial. Los requisitos sin marcar siguen pendientes.'),
                'icon' => $hayObservados ? 'warning' : 'success',
            ]);

            return redirect()->route('seguimientos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo guardar la revision tecnica. ' . $e->getMessage());
        }
    }

    // NOTIFICAR OBSERVACIONES AL SOLICITANTE
    // Devuelve el mismo tramite al solicitante para que corrija los requisitos observados.
    public function notificarCorreccionSolicitante(Seguimiento $seguimiento)
    {
        if (!$this->usuarioPuedeRevisarSeguimiento($seguimiento)) {
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

            // La observacion debe llegar al beneficiario y al tramitador cuando ambos tienen cuenta.
            // El seguimiento solo admite un responsable siguiente, por eso se usa el primero como etapa operativa.
            $usuariosSolicitantes = $this->usuariosSolicitantesParaNotificacion($certificado);
            $usuarioResponsableCorreccion = $usuariosSolicitantes->first();

            if (!$usuarioResponsableCorreccion) {
                DB::rollBack();

                return back()->with('error', 'El tramite no tiene usuario beneficiario o tramitador para notificar.');
            }

            $observaciones = $requisitosObservados
                ->map(function ($requisitoCertificado) {
                    $ultimaObservacion = $this->ultimaObservacionRequisito($requisitoCertificado);

                    return ($requisitoCertificado->requisito?->descripcion ?? 'Requisito #' . $requisitoCertificado->id)
                        . ': ' . ($ultimaObservacion?->observacion ?? 'Sin observacion');
                })
                ->implode(PHP_EOL);

            // Desde este punto la observacion ya se comunica al solicitante.
            foreach ($requisitosObservados as $requisitoCertificado) {
                $requisitoCertificado->update(['estado' => 'OBSERVADO']);
            }

            // Cierra la etapa del revisor y abre una etapa para que el solicitante corrija.
            $seguimiento->update([
                'fecha_derivacion' => now()->toDateString(),
            ]);

            $certificado->update(['estado' => 'OBSERVADO']);

            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now()->toDateString(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => 'Solicitud observada para correccion del solicitante.',
                'referencia' => $observaciones,
                'id_usuario_anterior' => $seguimiento->id_usuario_siguiente,
                'id_usuario_origen' => auth()->id(),
                'id_usuario_siguiente' => $usuarioResponsableCorreccion->id,
                'estado' => 'ACTIVO',
            ]);

            foreach ($usuariosSolicitantes as $usuarioSolicitante) {
                $this->notificarUsuarioTramite(
                    $usuarioSolicitante,
                    $certificado,
                    'Tramite observado',
                    'Tiene requisitos observados para corregir en el mismo tramite.'
                );
            }

            DB::commit();

            session()->flash('swal', [
                'title' => 'Tramite devuelto',
                'text' => 'El tramite fue notificado a las cuentas vinculadas del beneficiario y tramitador.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo notificar al solicitante. ' . $e->getMessage());
        }
    }

    // REGISTRAR CORRECCION RECIBIDA PRESENCIALMENTE
    // Se usa cuando el solicitante corrige en INSO y no corresponde subir un PDF desde el sistema.
    public function registrarCorreccionRecibida(Seguimiento $seguimiento)
    {
        if (!$this->usuarioPuedeRegistrarCorreccionRecibida($seguimiento)) {
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
            $ultimaObservacion = $this->ultimaObservacionPorRequisitos($certificado, $idsRequisitosObservados);

            $tecnicoDestinoId = $ultimaObservacion?->revisionRequisito?->id_usuario_revisor
                ?: $this->ultimoRevisorPorRequisitos($requisitosObservados)
                ?: $seguimiento->id_usuario_origen;

            $tecnicoDestino = User::query()
                ->whereKey($tecnicoDestinoId)
                ->where('estado', 1)
                ->first();

            if (!$tecnicoDestino) {
                DB::rollBack();

                return back()->with('error', 'No se encontro el funcionario revisor para devolver el tramite.');
            }

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
                'fecha_derivacion' => now()->toDateString(),
            ]);

            // Abre una nueva etapa para el mismo tramite, devolviendolo al revisor.
            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now()->toDateString(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => 'Correccion recibida presencialmente.',
                'referencia' => 'Correccion presencial',
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

            DB::commit();

            session()->flash('swal', [
                'title' => 'Correccion recibida',
                'text' => 'El tramite fue devuelto al revisor tecnico para continuar la evaluacion.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo registrar la correccion recibida. ' . $e->getMessage());
        }
    }

    // REENVIO DE CORRECCIONES DEL SOLICITANTE
    // El usuario corrige documentos observados y devuelve el mismo tramite al tecnico que observo.
    public function reenviarCorreccion(Request $request, Seguimiento $seguimiento)
    {
        if (!$this->usuarioPuedeReenviarCorreccion($seguimiento)) {
            return back()->with('error', 'Este tramite no esta disponible para correccion del usuario actual.');
        }

        // Por ahora la correccion se recibe presencialmente en INSO.
        // Se bloquea este reenvio digital para evitar movimientos duplicados del solicitante.
        return back()->with('error', 'La correccion de este tramite debe registrarla un funcionario cuando reciba la documentacion en INSO.');

        $datos = $request->validate([
            'accion_correccion' => ['required', 'in:guardar,enviar'],
            'documentos_correccion' => ['nullable', 'array'],
            'documentos_correccion.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [], [
            'accion_correccion' => 'accion de correccion',
            'documentos_correccion.*' => 'documento corregido',
        ]);

        try {
            DB::beginTransaction();

            $certificado = $seguimiento->certificado()->with('certificadoRequisitos')->firstOrFail();
            $tecnicoDestinoId = $seguimiento->id_usuario_origen;
            $archivos = $request->file('documentos_correccion', []);
            $soloGuardar = $datos['accion_correccion'] === 'guardar';

            foreach ($certificado->certificadoRequisitos->where('cumple', 'NO') as $requisitoCertificado) {
                if (!empty($archivos[$requisitoCertificado->id])) {
                    $idTipoEvidencia = $requisitoCertificado->evidenciasRequisitos()
                        ->latest('id')
                        ->value('id_tipo_evidencia');

                    $this->guardarDocumentoRequisito(
                        $archivos[$requisitoCertificado->id],
                        $requisitoCertificado,
                        $idTipoEvidencia ? (int) $idTipoEvidencia : null
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

            foreach ($certificado->certificadoRequisitos->where('cumple', 'NO') as $requisitoCertificado) {
                // Vuelve a pendiente para que el mismo tecnico revise el documento corregido.
                $requisitoCertificado->update([
                    'cumple' => null,
                    'estado' => 'PENDIENTE_REVISION',
                ]);
            }

            $certificado->update(['estado' => 'EN_REVISION']);

            // Cierra la etapa del solicitante y devuelve el tramite al tecnico que observo.
            $seguimiento->update([
                'fecha_derivacion' => now()->toDateString(),
            ]);

            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now()->toDateString(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => 'Correccion reenviada por el solicitante.',
                // El solicitante solo corrige documentos; la descripcion queda automatica para no duplicar notas.
                'referencia' => 'Documentos corregidos reenviados.',
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

            DB::commit();

            session()->flash('swal', [
                'title' => 'Correccion enviada',
                'text' => 'El tramite fue devuelto al tecnico para nueva revision.',
                'icon' => 'success',
            ]);

            return redirect()->route('seguimientos_mis_solicitudes');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo reenviar la correccion. ' . $e->getMessage());
        }
    }

    /**
     * No se usa por ahora: el seguimiento se modifica con acciones especificas
     * como asignar, derivar, revisar o reenviar correcciones.
     */
    public function edit(Seguimiento $seguimiento)
    {
        abort(404);
    }

    /**
     * No se usa por ahora: evita actualizaciones genericas sobre el historial.
     */
    public function update(Request $request, Seguimiento $seguimiento)
    {
        abort(404);
    }

    /**
     * No se elimina historial de seguimiento desde CRUD generico.
     */
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

        // Personas que pueden iniciar un tramite como beneficiarias.
        // Se manda como arreglo simple para que la vista no haga consultas.
        $personas = $personasBase
            ->map(fn (Persona $persona) => $this->opcionPersonaTramite($persona))
            ->values();
        $beneficiarioBloqueado = !$this->usuarioPuedeAtenderTramites() && $personas->count() === 1;
        $beneficiarioAutomatico = $beneficiarioBloqueado ? $personas->first() : null;

        // Tramitadores permitidos por beneficiario.
        // Empresa: se toman sus responsables activos con rol TRAMITADOR.
        // Persona natural: se permite que la misma persona actue como tramitadora.
        $tramitadoresPorBeneficiario = $personasBase->mapWithKeys(function (Persona $persona) {
            if ($persona->empresa) {
                $tramitadores = $persona->empresa->responsables
                    ->filter(fn (Responsable $responsable) => $this->responsableEsTramitadorActivo($responsable))
                    ->map(fn (Responsable $responsable) => $this->opcionPersonaTramite($responsable->persona))
                    ->filter(fn (array $opcion) => filled($opcion['id']))
                    ->unique('id')
                    ->values();

                return [$persona->id => $tramitadores];
            }

            return [
                $persona->id => collect([
                    $this->opcionPersonaTramite($persona),
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
                            'tipo_evidencia_descripcion' => $asignacion->tipoEvidencia?->descripcion ?? 'Sin descripcion registrada.',
                            'tipo_evidencia_tamanio_maximo_mb' => (int) ($asignacion->tipoEvidencia?->tamanio_maximo_mb ?? 0),
                            'certificados_requeridos' => $certificadosRequeridos,
                        ];
                    })
                    ->values(),
            ];
        });

        $dependenciasPorTipoCertificado = $this->dependenciasPorTipoCertificado($tiposCertificados);
        $certificadosVigentesPorPersona = $this->certificadosVigentesPorPersona($personasBase->pluck('id')->all());

        return compact(
            'tiposCertificados',
            'personas',
            'tramitadoresPorBeneficiario',
            'requisitosPorTipoCertificado',
            'dependenciasPorTipoCertificado',
            'certificadosVigentesPorPersona',
            'beneficiarioBloqueado',
            'beneficiarioAutomatico'
        );
    }

    // OPCION DE PERSONA PARA SELECTS DEL INICIO DE TRAMITE
    // Centraliza id, nombre y tipo para que los selects muestren una descripcion debajo de cada item.
    private function opcionPersonaTramite(?Persona $persona): array
    {
        if (!$persona) {
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
            'detalle' => $esEmpresa ? 'Empresa' : 'Persona natural',
            'tipo' => $esEmpresa ? 'EMPRESA' : 'NATURAL',
        ];
    }

    // FILTRO DE RESPONSABLES QUE PUEDEN SER TRAMITADORES
    // Evita mostrar responsables inactivos o roles que no corresponden al tramite.
    private function responsableEsTramitadorActivo(Responsable $responsable): bool
    {
        $estadoActivo = in_array((string) $responsable->estado, ['1', 'ACTIVO'], true);
        $rol = $responsable->rol;
        $esTramitador = $rol
            && (
                $rol->slug === 'tramitador'
                || str_contains(mb_strtoupper($rol->name), 'TRAMITADOR')
            );

        return $estadoActivo && $esTramitador && $responsable->persona;
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
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', now()->toDateString());
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
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', now()->toDateString());
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
            'form_id_tipo_certificado' => 'No puede solicitar este tramite. Primero debe contar con: ' . $faltantes->implode(', ') . '.',
        ]);
    }

    private function beneficiariosVisiblesParaIniciarTramite($personasBase)
    {
        if ($this->usuarioPuedeAtenderTramites()) {
            return $personasBase;
        }

        $idsPermitidos = $this->idsBeneficiariosPermitidosParaUsuario(auth()->user());

        return $personasBase
            ->whereIn('id', $idsPermitidos)
            ->values();
    }

    // Evita que un usuario externo inicie tramites para personas o empresas ajenas.
    private function validarQueUsuarioPuedeRegistrarBeneficiario(?User $usuario, int $idBeneficiario): void
    {
        if (!$usuario || !$this->usuarioPuedeIniciarTramiteParaBeneficiario($usuario, $idBeneficiario)) {
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
        if (!$usuario) {
            return [];
        }

        $personaUsuario = Persona::query()
            ->where('id_usuario', $usuario->id)
            ->where('estado', 'ACTIVO')
            ->first();

        if (!$personaUsuario) {
            return [];
        }

        $idsPermitidos = [(int) $personaUsuario->id];

        // Un tramitador activo puede iniciar tramites para las empresas donde esta registrado.
        $idsEmpresas = Persona::query()
            ->where('estado', 'ACTIVO')
            ->whereHas('empresa.responsables', function ($query) use ($personaUsuario) {
                $query->where('id_persona', $personaUsuario->id)
                    ->whereIn('estado', ['1', 'ACTIVO'])
                    ->whereHas('rol', function ($rol) {
                        $rol->where('estado', 1)
                            ->where(function ($query) {
                                $query->where('slug', 'tramitador')
                                    ->orWhere('name', 'like', '%TRAMITADOR%');
                            });
                    });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($idsPermitidos, $idsEmpresas)));
    }

    // RESUELVE Y VALIDA LA RELACION BENEFICIARIO/TRAMITADOR
    // Persona natural: el backend fuerza que el tramitador sea la misma persona.
    // Empresa: solo acepta tramitadores activos de esa empresa o la misma empresa cuando se marca esa opcion.
    private function resolverTramitadorDelBeneficiario(int $idBeneficiario, ?int $idTramitador): int
    {
        $beneficiario = Persona::query()
            ->with(['empresa.responsables.rol'])
            ->find($idBeneficiario);

        if (!$beneficiario) {
            throw ValidationException::withMessages([
                'form_id_persona_beneficiario' => 'El beneficiario seleccionado no es valido.',
            ]);
        }

        if (!$beneficiario->empresa) {
            return $idBeneficiario;
        }

        if ($idBeneficiario === $idTramitador) {
            return $idBeneficiario;
        }

        if (!$idTramitador) {
            throw ValidationException::withMessages([
                'form_id_persona_tramitador' => 'Debe seleccionar un tramitador para la empresa.',
            ]);
        }

        $tramitadorValido = $beneficiario->empresa?->responsables
            ->contains(fn (Responsable $responsable) => $this->responsableEsTramitadorActivo($responsable)
                && (int) $responsable->id_persona === $idTramitador);

        if (!$tramitadorValido) {
            throw ValidationException::withMessages([
                'form_id_persona_tramitador' => 'El tramitador seleccionado no pertenece al beneficiario.',
            ]);
        }

        return $idTramitador;
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
            'requisitos_certificados.*.id_requisito_tipo_certificado' => ['nullable', 'exists:requisitos_tipos_certificados,id'],
            'requisitos_certificados.*.id_requisito' => ['required', 'exists:requisitos,id'],
            'requisitos_certificados.*.id_tipo_evidencia' => ['nullable', 'exists:tipos_evidencias,id'],
            'requisitos_certificados.*.observacion' => ['nullable', 'string', 'max:500'],
            'documentos_requisitos' => ['nullable', 'array'],
            'documentos_requisitos.*' => ['nullable', 'file'],
        ], [
            // Mensajes claros para que Laravel muestre los errores debajo de cada campo.
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El :attribute seleccionado no es valido.',
            'array' => 'El campo :attribute debe tener un formato valido.',
            'string' => 'El campo :attribute debe ser texto.',
            'file' => 'El :attribute debe ser un archivo valido.',
        ], [
            'form_id_persona_beneficiario' => 'beneficiario',
            'form_id_persona_tramitador' => 'tramitador',
            'form_id_tipo_certificado' => 'tipo de tramite',
            'requisitos_certificados' => 'requisitos del tramite',
            'requisitos_certificados.*.id_requisito_tipo_certificado' => 'configuracion del requisito',
            'requisitos_certificados.*.id_requisito' => 'requisito del tramite',
            'requisitos_certificados.*.id_tipo_evidencia' => 'tipo de evidencia',
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

        if (!$tipoCertificado) {
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

    // Valida archivos con la configuracion guardada para cada requisito.
    private function validarArchivosSubidosDeRequisitos(Request $request, $asignacionesRequisitos): void
    {
        $archivos = $request->file('documentos_requisitos', []);
        $indicesPorAsignacion = $this->posicionesDeArchivosPorRequisitoConfigurado($request);
        $asignacionesPorId = $asignacionesRequisitos->keyBy('id');

        foreach ($asignacionesRequisitos as $asignacion) {
            $tipoEvidencia = $asignacion->tipoEvidencia;
            $codigo = mb_strtoupper((string) $tipoEvidencia?->codigo);

            if (!$this->evidenciaRequiereArchivo($codigo)) {
                continue;
            }

            $indice = $indicesPorAsignacion[$asignacion->id] ?? null;

            if ($indice === null || !$request->hasFile("documentos_requisitos.$indice")) {
                throw ValidationException::withMessages([
                    $indice === null ? 'documentos_requisitos' : "documentos_requisitos.$indice" =>
                        'Debe adjuntar la evidencia solicitada para "' . ($asignacion->requisito?->descripcion ?? 'este requisito') . '".',
                ]);
            }
        }

        foreach ($archivos as $indice => $archivo) {
            if (!$archivo) {
                continue;
            }

            $idAsignacion = $this->buscarIdRequisitoConfiguradoPorPosicionArchivo($indicesPorAsignacion, $indice);
            $asignacion = $idAsignacion ? $asignacionesPorId->get((int) $idAsignacion) : null;
            $tipoEvidencia = $asignacion?->tipoEvidencia;

            if (!$tipoEvidencia) {
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
                !in_array($extension, $extensionesPermitidas, true)
                || !$this->archivoTieneFormatoPermitidoPorEvidencia($codigo, $mime)
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

    // Decide a que bandeja volver despues de registrar un tramite nuevo.
    // Solo el beneficiario ve su solicitud en "Mis tramites"; funcionarios van al seguimiento general.
    private function rutaDespuesDeRegistrarTramite(Certificado $certificado, ?User $usuario): string
    {
        if ($this->usuarioEsBeneficiarioDelTramite($certificado, $usuario)) {
            return 'seguimientos_mis_solicitudes';
        }

        return $usuario?->puede('seguimientos_tramite.consulta_general')
            ? 'seguimientos_todos'
            : 'seguimientos_index';
    }

    // Verifica si la cuenta autenticada pertenece al beneficiario real del tramite.
    private function usuarioEsBeneficiarioDelTramite(Certificado $certificado, ?User $usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return Persona::query()
            ->where('id_usuario', $usuario->id)
            ->where('id', $certificado->id_persona_beneficiario)
            ->exists();
    }

    // GUARDA LOS REQUISITOS DE LA SOLICITUD
    // Cada requisito queda pendiente para que el tecnico lo revise despues.
    private function guardarRequisitosTramite(Certificado $certificado, $asignacionesRequisitos, array $indicesDocumentos, array $documentos): void
    {
        $procesados = [];

        foreach ($asignacionesRequisitos as $asignacion) {
            $idRequisito = $asignacion->id_requisito;
            $idTipoEvidencia = $asignacion->id_tipo_evidencia;
            $claveProceso = 'asignacion_' . $asignacion->id;

            if (!$idRequisito || in_array($claveProceso, $procesados, true)) {
                continue;
            }

            $requisitoCertificado = RequisitoCertificado::create([
                'id_certificado' => $certificado->id,
                'id_requisito' => $idRequisito,
                // Mientras no exista revision tecnica, no se afirma si cumple o no cumple.
                'cumple' => null,
                'estado' => 'PENDIENTE_REVISION',
            ]);

            // Deja marcada la evidencia que este requisito debe cumplir.
            // El valor se llenara cuando se adjunte o valide el dato correspondiente.
            if ($idTipoEvidencia) {
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

            if ($indiceDocumento !== null && !empty($documentos[$indiceDocumento])) {
                $this->guardarDocumentoRequisito(
                    $documentos[$indiceDocumento],
                    $requisitoCertificado,
                    $idTipoEvidencia ? (int) $idTipoEvidencia : null
                );
            }

            $procesados[] = $claveProceso;
        }
    }

    // GUARDA EL ARCHIVO DE UN REQUISITO
    // No crea columnas nuevas: el archivo se ubica por el id de requisitos_certificados.
    private function guardarDocumentoRequisito($archivo, RequisitoCertificado $requisitoCertificado, ?int $idTipoEvidencia): void
    {
        $extension = mb_strtolower($archivo->getClientOriginalExtension() ?: 'pdf');

        $ruta = $archivo->storeAs(
            'tramites/' . $requisitoCertificado->id_certificado . '/requisitos/' . $requisitoCertificado->id,
            'documento.' . $extension,
            'public'
        );

        $rutaStorage = storage_path('app/public/' . $ruta);
        $rutaPublica = public_path('storage/' . $ruta);
        File::ensureDirectoryExists(dirname($rutaPublica));

        if (File::exists($rutaStorage)) {
            File::copy($rutaStorage, $rutaPublica);
        }

        // Guarda la ruta en evidencias_requisitos para no mezclar documentos con la revision tecnica.
        EvidenciaRequisito::updateOrCreate(
            [
                'id_requisito_certificado' => $requisitoCertificado->id,
                'id_tipo_evidencia' => $idTipoEvidencia ?: $this->idTipoEvidencia('PDF'),
            ],
            [
                'valor' => 'storage/' . $ruta,
                'estado' => 'REGISTRADO',
                'id_usuario_registro' => auth()->id(),
                'id_usuario_modificacion' => auth()->id(),
            ]
        );
    }

    // Busca el id del tipo de evidencia configurado. PDF es el respaldo por defecto para documentos.
    private function idTipoEvidencia(string $codigo): int
    {
        $idTipoEvidencia = TipoEvidencia::query()
            ->where('codigo', $codigo)
            ->value('id');

        if (!$idTipoEvidencia) {
            throw new \RuntimeException('No existe el tipo de evidencia ' . $codigo . '. Revise el seeder de tipos de evidencias.');
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
                'fecha_derivacion' => now()->toDateString(),
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

    // Toma el ultimo usuario revisor desde revisiones_requisitos cuando no hay observacion activa.
    private function ultimoRevisorPorRequisitos($requisitosObservados): ?int
    {
        return RevisionRequisito::query()
            ->whereIn('id_requisito_certificado', $requisitosObservados->pluck('id')->all())
            ->latest('id')
            ->value('id_usuario_revisor');
    }

    // CODIGO INTERNO PARA CUMPLIR LA COLUMNA OBLIGATORIA
    // No se muestra como codigo de solicitud; solo evita guardar certificados.codigo vacio.
    private function generarCodigoTramite(): string
    {
        $prefijo = 'TRM-' . now()->format('Y') . '-';
        $numero = Certificado::withTrashed()
            ->where('codigo', 'like', $prefijo . '%')
            ->count() + 1;

        do {
            $codigo = $prefijo . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
            $numero++;
        } while (Certificado::withTrashed()->where('codigo', $codigo)->exists());

        return $codigo;
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
            ->map(fn ($item) => 'Requisito ' . ($item['id_requisito'] ?? '-') . ': ' . trim($item['observacion']))
            ->values();

        if ($observaciones->isNotEmpty()) {
            $partes[] = 'Observaciones por requisito:' . PHP_EOL . $observaciones->implode(PHP_EOL);
        }

        return $partes ? implode(PHP_EOL . PHP_EOL, $partes) : null;
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
        $idsAreas = $this->idsAreaConSuperiores($idArea);

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

                        return $prioridadCargo . '-' . $ordenArea . '-' . $cargo->nombre;
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
                    str_contains($nombreCargo, 'JEFE') => '0-' . $ordenArea . '-' . $nombreOrden,
                    str_contains($nombreCargo, 'DIRECTOR') => '1-' . $ordenArea . '-' . $nombreOrden,
                    default => '2-' . $ordenArea . '-' . $nombreOrden,
                };
            })
            ->first();
    }

    // Devuelve el area seleccionada y sus superiores, en ese orden.
    private function idsAreaConSuperiores(int $idArea): array
    {
        $idsAreas = [];
        $areaActual = Area::query()
            ->select('id', 'id_area_padre')
            ->find($idArea);

        while ($areaActual && !in_array((int) $areaActual->id, $idsAreas, true)) {
            $idsAreas[] = (int) $areaActual->id;

            $areaActual = $areaActual->id_area_padre
                ? Area::query()->select('id', 'id_area_padre')->find($areaActual->id_area_padre)
                : null;
        }

        return $idsAreas;
    }

    // Envia aviso al funcionario responsable del area inicial.
    private function notificarTramiteRecibido(Certificado $certificado, ?User $usuarioDestino, ?Area $areaDestino = null): void
    {
        if (!$usuarioDestino || !Schema::hasTable('notificaciones_tramites')) {
            return;
        }

        $datos = [
            'id_usuario_destino' => $usuarioDestino->id,
            'id_certificado' => $certificado->id,
            'titulo' => 'Nueva solicitud de tramite',
            'mensaje' => 'Se registro una solicitud para atencion inicial en ' . ($areaDestino?->nombre ?? 'el area asignada') . '.',
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
        if (!$usuario || !Schema::hasTable('notificaciones_tramites')) {
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

    // Verifica que el tecnico actual pueda revisar esta etapa.
    // Administrador tambien puede hacerlo para pruebas y soporte interno.
    private function usuarioPuedeRevisarSeguimiento(Seguimiento $seguimiento): bool
    {
        $seguimiento->loadMissing('usuarioSiguiente.roles', 'usuarioSiguiente.funcionario.cargos');

        if (!auth()->check() || $seguimiento->estado !== 'ACTIVO' || $seguimiento->fecha_derivacion) {
            return false;
        }

        if (auth()->user()->tieneRol('administrador')) {
            return true;
        }

        return (int) $seguimiento->id_usuario_siguiente === (int) auth()->id()
            && (
                $seguimiento->usuarioSiguiente?->tieneRol('tecnico-evaluador')
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
            && !$seguimiento->fecha_derivacion;
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
                || $usuario->tieneRol('tecnico-evaluador')
                || $this->usuarioTieneCargoActivo($usuario)
            );
    }

    // FUNCIONARIO HABILITADO PARA RECIBIR TRAMITES
    // Permite que cualquier usuario interno con cargo activo atienda una derivacion.
    private function usuarioTieneCargoActivo(?User $usuario): bool
    {
        $usuario?->loadMissing('funcionario.cargos');

        return (bool) $usuario?->funcionario
            && (string) $usuario->funcionario->estado === '1'
            && $usuario->funcionario->cargos->contains(fn ($cargo) => (string) $cargo->estado === '1');
    }

    // Verifica que el usuario logueado sea quien recibio la observacion.
    private function usuarioPuedeReenviarCorreccion(Seguimiento $seguimiento): bool
    {
        return auth()->check()
            && (int) $seguimiento->id_usuario_siguiente === (int) auth()->id()
            && $seguimiento->certificado?->estado === 'OBSERVADO';
    }

    // Verifica si un funcionario puede registrar que la correccion presencial ya fue recibida.
    private function usuarioPuedeRegistrarCorreccionRecibida(Seguimiento $seguimiento): bool
    {
        if (
            !auth()->check()
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
            || auth()->user()->tieneRol('tecnico-evaluador')
            || $this->usuarioTieneCargoActivo(auth()->user());
    }

    // DESTINOS DE NOTIFICACION PARA OBSERVACIONES
    // Incluye beneficiario y tramitador, evitando duplicar si ambos usan la misma cuenta.
    private function usuariosSolicitantesParaNotificacion(Certificado $certificado)
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

    // REMITENTE DE NOTIFICACION
    // Muestra funcionario con cargo o, si es solicitante, empresa/persona natural.
    private function datosUsuarioNotificacion(?User $usuario): array
    {
        if (!$usuario) {
            return [
                'nombre' => 'Sin remitente',
                'detalle' => 'Sin dato',
            ];
        }

        $usuario->loadMissing('funcionario.cargos', 'persona.empresa', 'persona.natural');

        if ($usuario->funcionario) {
            $nombreFuncionario = trim(implode(' ', array_filter([
                $usuario->funcionario->nombres,
                $usuario->funcionario->apellido_paterno,
                $usuario->funcionario->apellido_materno,
            ])));

            return [
                'nombre' => $nombreFuncionario ?: ($usuario->name ?: 'Sin funcionario'),
                'detalle' => $usuario->funcionario->cargos?->pluck('nombre')->filter()->implode(', ') ?: 'Sin cargo',
            ];
        }

        if ($usuario->persona) {
            return [
                'nombre' => $this->nombrePersona($usuario->persona),
                'detalle' => $usuario->persona->empresa ? 'Empresa' : 'Persona natural',
            ];
        }

        return [
            'nombre' => $usuario->name ?: 'Sin remitente',
            'detalle' => 'Sin persona vinculada',
        ];
    }

    // NOMBRE LEGIBLE DE PERSONA
    // Devuelve razon social si es empresa o nombre completo si es natural.
    private function nombrePersona(?Persona $persona): string
    {
        if (!$persona) {
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

        return 'Persona #' . $persona->id;
    }
}
