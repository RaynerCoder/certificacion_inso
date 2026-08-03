<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Natural;
use App\Models\NotificacionTramite;
use App\Models\OcupacionCob;
use App\Models\Persona;
use App\Models\Responsable;
use App\Models\Role;
use App\Models\Rubro;
use App\Models\Telefono;
use App\Models\Territorio;
use App\Models\User;
use App\Services\GestionTramitadoresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class TramitadorController extends Controller
{
    public function __construct(private GestionTramitadoresService $gestionTramitadores)
    {
    }

    /**
     * La empresa ve sus registros; INSO ve todos cuando puede validarlos.
     */
    public function index(): View
    {
        $empresa = auth()->user()?->loadMissing('persona.empresa')?->persona?->empresa;
        $esValidadorInso = $this->esValidadorInso();

        abort_unless($empresa || $esValidadorInso, 403);

        return view('tramitadores.index', compact('empresa', 'esValidadorInso'));
    }

    /**
     * Abre el primer paso del flujo: solicitud de vinculación empresa-tramitador.
     */
    public function create(): View
    {
        $empresa = $this->empresaAutenticada();
        $paises = Territorio::query()->where('id_ambito', 1)->orderBy('nombre')->get();
        $personas = Persona::query()
            ->with([
                'natural.ocupacionCob',
                'telefonos',
                'territorio',
                'rubros' => fn ($query) => $query->where('rubros.nivel_caeb', 'SUBCLASE'),
            ])
            ->where('estado', 'ACTIVO')
            ->whereHas('natural', fn ($query) => $query->whereNotNull('ci')->where('ci', '<>', ''))
            ->orderBy('id')
            ->get();
        $rubrosCatalogo = Rubro::query()
            ->whereNotNull('codigo_caeb')
            ->where('nivel_caeb', 'SUBCLASE')
            ->where('estado', 'ACTIVO')
            ->orderBy('codigo_caeb')
            ->get(['id', 'codigo_caeb', 'nombre']);
        $ocupacionesCob = OcupacionCob::query()
            ->orderBy('codigo_ocupacion')
            ->get(['id', 'codigo_ocupacion', 'descripcion_ocupacion']);

        return view('tramitadores.create', compact(
            'empresa',
            'paises',
            'personas',
            'rubrosCatalogo',
            'ocupacionesCob'
        ));
    }

    public function territoriosHijos(Territorio $territorio): JsonResponse
    {
        $this->empresaAutenticada();

        $territorios = $territorio->territoriosHijos()
            ->with('ambito:id,nombre')
            ->whereIn('estado', ['ACTIVO', 'Activo', 'activo', '1', 1])
            ->orderBy('nombre')
            ->get(['id', 'id_ambito', 'id_padre_territorio', 'nombre']);

        return response()->json($territorios->map(fn ($hijo) => [
            'id' => $hijo->id,
            'nombre' => $hijo->nombre,
            'nivel' => $hijo->ambito?->nombre ?: 'Territorio',
        ])->values());
    }

    public function rutaTerritorio(Territorio $territorio): JsonResponse
    {
        $this->empresaAutenticada();
        $ruta = [];
        $actual = $territorio;
        $idsRecorridos = [];

        while ($actual && ! in_array($actual->id, $idsRecorridos, true)) {
            $idsRecorridos[] = $actual->id;
            array_unshift($ruta, ['id' => $actual->id, 'nombre' => $actual->nombre]);
            $actual = $actual->territorioPadre()->first(['id', 'id_padre_territorio', 'nombre']);
        }

        return response()->json($ruta);
    }

    /**
     * Registra la solicitud. La cuenta y el rol de acceso se crean después, al validar en INSO.
     */
    public function store(Request $request): RedirectResponse
    {
        $empresa = $this->empresaAutenticada();
        $seleccion = $request->validate([
            'form_id_persona' => ['nullable', 'exists:personas,id'],
        ]);

        $natural = null;

        if (! empty($seleccion['form_id_persona'])) {
            $natural = Natural::with('persona')
                ->where('id_persona', $seleccion['form_id_persona'])
                ->firstOrFail();
        } else {
            $documento = $request->validate([
                'form_ci' => ['required', 'string', 'max:50'],
                'form_complemento' => ['nullable', 'string', 'max:10'],
            ]);
            $natural = $this->buscarNaturalPorDocumento(
                $documento['form_ci'],
                $documento['form_complemento'] ?? null
            );

            if ($natural) {
                throw ValidationException::withMessages([
                    'form_id_persona' => 'La persona ya está registrada. Selecciónela en Persona registrada.',
                ]);
            }
        }

        if ($natural) {
            $this->validarIdentidadDisponible($natural);
        }

        $datos = $this->validarSolicitud($request, ! $natural);
        $rutaRespaldo = $request->file('form_url_respaldo')->store(
            "tramitadores/{$empresa->id}",
            'local'
        );

        try {
            $relacionRegistrada = DB::transaction(function () use ($empresa, $natural, $datos, $rutaRespaldo): Responsable {
                $persona = $natural?->persona;

                if (! $persona) {
                    $persona = $this->crearPersonaNatural($datos);
                }

                $idRolTramitador = $this->idRolTramitador();

                $relacionesExistentes = Responsable::query()
                    ->where('id_empresa', $empresa->id)
                    ->where('id_persona', $persona->id)
                    ->where('id_rol', $idRolTramitador)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get();

                $relacionVigente = $relacionesExistentes->first(
                    fn (Responsable $relacion) => in_array(
                        (string) $relacion->estado,
                        ['1', 'ACTIVO', 'PENDIENTE_VALIDACION'],
                        true
                    )
                );

                if ($relacionVigente) {
                    throw ValidationException::withMessages([
                        'form_ci' => 'Esta persona ya tiene una relación activa o pendiente con la empresa.',
                    ]);
                }

                $datosRelacion = [
                    'id_empresa' => $empresa->id,
                    'id_persona' => $persona->id,
                    'id_rol' => $idRolTramitador,
                    'url_respaldo' => $rutaRespaldo,
                    'fecha_registro' => now(),
                    'fecha_baja' => null,
                    'estado' => 'PENDIENTE_VALIDACION',
                    'id_usuario_validacion' => null,
                    'id_usuario_baja' => null,
                ];

                // Se reutiliza la relación anterior para evitar duplicados por empresa y tramitador.
                $relacion = $relacionesExistentes->first();
                if ($relacion) {
                    $relacion->update($datosRelacion);

                    return $relacion->fresh();
                }

                return Responsable::create($datosRelacion);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($rutaRespaldo);
            throw $exception;
        }

        $relacionRegistrada->loadMissing('persona.natural', 'empresa');
        $personaRegistrada = $relacionRegistrada->persona;
        $this->notificarValidadores($relacionRegistrada);
        $cuentaExistente = filled($personaRegistrada->id_usuario);
        $tituloRegistro = $cuentaExistente
            ? 'Solicitud de tramitador registrada'
            : 'Tramitador registrado';
        $mensajeRegistro = $cuentaExistente
            ? sprintf(
                '<p>El tramitador ya tiene una cuenta en el sistema.</p>'
                . '<p class="mt-2">Se registró la solicitud para habilitarlo como tramitador de <strong>%s</strong>.</p>'
                . '<p class="mt-2">Podrá representar a esta empresa cuando INSO apruebe la solicitud. Su usuario y contraseña actuales no cambiarán.</p>',
                e($empresa->razon_social)
            )
            : sprintf(
                '<p>El tramitador fue registrado correctamente y está pendiente de validación por INSO.</p>'
                . '<p class="mt-2">Cuando la cuenta sea habilitada podrá ingresar con:</p>'
                . '<div class="mt-3 text-left"><strong>Usuario:</strong> %s<br><strong>Contraseña:</strong> %s</div>',
                e($personaRegistrada->correo),
                e($personaRegistrada->natural?->ci)
            );

        session()->flash('swal', [
            'title' => $tituloRegistro,
            'html' => $mensajeRegistro,
            'icon' => 'success',
        ]);

        return redirect()->route('tramitadores_index');
    }

    public function edit(Responsable $tramitador): View
    {
        $this->autorizarValidacion();
        $this->validarRolTramitador($tramitador);
        $tramitador->loadMissing([
            'empresa.persona',
            'persona.natural.ocupacionCob',
            'persona.usuario',
            'persona.territorio.ambito',
            'persona.telefonos',
            'persona.rubros',
            'rol',
            'usuarioValidacion.funcionario',
            'usuarioValidacion.persona.natural',
            'usuarioValidacion.persona.empresa',
            'usuarioBaja.funcionario',
            'usuarioBaja.persona.natural',
            'usuarioBaja.persona.empresa',
        ]);

        $empresasHabilitadas = Responsable::query()
            ->with('empresa:id,razon_social')
            ->where('id_persona', $tramitador->id_persona)
            ->where('id_rol', $tramitador->id_rol)
            ->where('estado', 'ACTIVO')
            ->where('id', '<>', $tramitador->id)
            ->orderByDesc('fecha_registro')
            ->get(['id', 'id_empresa', 'fecha_registro']);

        return view('tramitadores.edit', compact('tramitador', 'empresasHabilitadas'));
    }

    public function update(Request $request, Responsable $tramitador): RedirectResponse
    {
        $this->autorizarValidacion();
        $this->validarRolTramitador($tramitador);
        $datos = $request->validate([
            'form_estado' => ['required', Rule::in(['PENDIENTE_VALIDACION', 'ACTIVO', 'RECHAZADO'])],
            'form_cambiar_password' => ['nullable', 'boolean'],
            'password' => [
                'nullable',
                Rule::requiredIf($request->boolean('form_cambiar_password')),
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        if ($request->boolean('form_cambiar_password') && $datos['form_estado'] !== 'ACTIVO') {
            throw ValidationException::withMessages([
                'password' => 'La nueva contraseña solo puede asignarse al habilitar al tramitador.',
            ]);
        }

        $nuevaPassword = $request->boolean('form_cambiar_password')
            ? $datos['password']
            : null;

        try {
            DB::transaction(function () use ($tramitador, $datos, $nuevaPassword): void {
                $relacion = Responsable::query()->whereKey($tramitador->id)->lockForUpdate()->firstOrFail();
                $registrarValidador = $relacion->estado === 'PENDIENTE_VALIDACION'
                    && ! $relacion->id_usuario_validacion
                    && in_array($datos['form_estado'], ['ACTIVO', 'RECHAZADO'], true);

                if ($datos['form_estado'] === 'ACTIVO') {
                    $this->activarTramitador(
                        $relacion,
                        $registrarValidador ? auth()->id() : null,
                        $nuevaPassword
                    );
                } else {
                    $cambios = ['estado' => $datos['form_estado']];

                    if ($registrarValidador) {
                        $cambios['id_usuario_validacion'] = auth()->id();
                    }

                    $relacion->update($cambios);
                }

            });
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        session()->flash('swal', [
            'title' => 'Estado actualizado',
            'text' => $datos['form_estado'] === 'ACTIVO'
                ? ($nuevaPassword
                    ? 'El tramitador fue habilitado y su contraseña fue actualizada.'
                    : 'El tramitador fue habilitado.')
                : 'La solicitud fue actualizada.',
            'icon' => 'success',
        ]);

        return redirect()->route('tramitadores_index');
    }

    /**
     * Muestra una relación, después de comprobar que pertenece a la empresa actual.
     */
    public function show(Responsable $tramitador): View
    {
        $this->autorizarRelacion($tramitador);
        $tramitador->loadMissing([
            'empresa.persona',
            'persona.natural',
            'persona.usuario',
            'rol',
            'usuarioBaja.funcionario',
            'usuarioBaja.persona.natural',
            'usuarioBaja.persona.empresa',
        ]);

        $empresaAutenticada = auth()->user()?->loadMissing('persona.empresa')?->persona?->empresa;
        $puedeSolicitarNuevamente = $empresaAutenticada
            && (int) $empresaAutenticada->id === (int) $tramitador->id_empresa
            && auth()->user()?->puede('tramitadores.ver')
            && in_array((string) $tramitador->estado, ['INACTIVO', 'RECHAZADO'], true)
            && ! Responsable::query()
                ->where('id_empresa', $tramitador->id_empresa)
                ->where('id_persona', $tramitador->id_persona)
                ->where('id_rol', $tramitador->id_rol)
                ->where('id', '<>', $tramitador->id)
                ->whereIn('estado', ['1', 'ACTIVO', 'PENDIENTE_VALIDACION'])
                ->exists();

        return view('tramitadores.show', compact('tramitador', 'puedeSolicitarNuevamente'));
    }

    /**
     * Reactiva la misma relación con un documento actualizado, sin crear duplicados.
     */
    public function solicitarNuevamente(Request $request, Responsable $tramitador): RedirectResponse
    {
        $this->autorizarRelacionEmpresa($tramitador);

        $datos = $request->validate([
            'form_url_respaldo' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ], [], [
            'form_url_respaldo' => 'documento de respaldo o autorización',
        ]);

        $rutaRespaldo = $datos['form_url_respaldo']->store(
            "tramitadores/{$tramitador->id_empresa}",
            'local'
        );

        try {
            $solicitud = DB::transaction(function () use ($tramitador, $rutaRespaldo): Responsable {
                $relacionAnterior = Responsable::query()
                    ->whereKey($tramitador->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array((string) $relacionAnterior->estado, ['INACTIVO', 'RECHAZADO'], true)) {
                    throw ValidationException::withMessages([
                        'form_url_respaldo' => 'Solo puede solicitar nuevamente una relación inactiva o rechazada.',
                    ]);
                }

                $existeRelacionVigente = Responsable::query()
                    ->where('id_empresa', $relacionAnterior->id_empresa)
                    ->where('id_persona', $relacionAnterior->id_persona)
                    ->where('id_rol', $relacionAnterior->id_rol)
                    ->where('id', '<>', $relacionAnterior->id)
                    ->whereIn('estado', ['1', 'ACTIVO', 'PENDIENTE_VALIDACION'])
                    ->lockForUpdate()
                    ->exists();

                if ($existeRelacionVigente) {
                    throw ValidationException::withMessages([
                        'form_url_respaldo' => 'Ya existe una relación activa o pendiente para esta empresa.',
                    ]);
                }

                $relacionAnterior->update([
                    'url_respaldo' => $rutaRespaldo,
                    'fecha_registro' => now(),
                    'fecha_baja' => null,
                    'estado' => 'PENDIENTE_VALIDACION',
                    'id_usuario_validacion' => null,
                    'id_usuario_baja' => null,
                ]);

                return $relacionAnterior->fresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($rutaRespaldo);
            throw $exception;
        }

        $solicitud->loadMissing(['persona.natural', 'empresa']);
        $this->notificarValidadores($solicitud);

        session()->flash('swal', [
            'title' => 'Solicitud actualizada',
            'text' => 'El documento fue enviado a INSO para una nueva validación.',
            'icon' => 'success',
        ]);

        return redirect()->route('tramitadores_index');
    }

    /**
     * Muestra la autorización mediante una ruta autenticada; nunca publica su URL real.
     */
    public function descargarCarta(Responsable $tramitador)
    {
        $this->autorizarRelacion($tramitador);

        abort_unless($tramitador->url_respaldo, 404);

        // Los documentos nuevos son privados. El segundo disco mantiene accesibles respaldos históricos.
        $disco = Storage::disk('local')->exists($tramitador->url_respaldo) ? 'local' : 'public';
        abort_unless(Storage::disk($disco)->exists($tramitador->url_respaldo), 404);

        return response()->file(Storage::disk($disco)->path($tramitador->url_respaldo), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"respaldo-tramitador-{$tramitador->id}.pdf\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Da de baja una relación activa sin eliminar su historial.
     */
    public function darBaja(Responsable $tramitador): RedirectResponse
    {
        $usuario = auth()->user()?->loadMissing('persona.empresa');
        $permisoRequerido = $usuario?->persona?->empresa
            ? 'tramitadores.ver'
            : 'tramitadores.validar';

        abort_unless($usuario?->puede($permisoRequerido), 403);

        // La empresa puede dar de baja sus relaciones e INSO puede hacerlo durante la supervisión.
        $this->autorizarRelacion($tramitador);

        try {
            $cantidadTransferida = DB::transaction(fn () => $this->gestionTramitadores->darDeBaja(
                $tramitador,
                $usuario
            ));

            session()->flash('swal', [
                'title' => 'Tramitador dado de baja',
                'text' => $cantidadTransferida
                    ? "Se transfirieron {$cantidadTransferida} trámite(s) pendientes al beneficiario."
                    : 'El tramitador no tenía correcciones pendientes.',
                'icon' => 'success',
            ]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('tramitadores_index');
    }

    private function validarSolicitud(Request $request, bool $personaNueva): array
    {
        $requeridoSiEsNueva = Rule::requiredIf($personaNueva);

        return $request->validate([
            'form_ci' => [$requeridoSiEsNueva, 'nullable', 'string', 'max:50'],
            'form_id_persona' => ['nullable', 'exists:personas,id'],
            'form_complemento' => ['nullable', 'string', 'max:10'],
            'form_url_respaldo' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'form_correo' => [$requeridoSiEsNueva, 'nullable', 'email', 'max:50', Rule::unique('personas', 'correo')],
            'form_id_territorio' => [$requeridoSiEsNueva, 'nullable', 'exists:territorios,id'],
            'form_domicilio' => ['nullable', 'string', 'max:255'],
            'form_nit' => ['nullable', 'string', 'max:50', Rule::unique('personas', 'nit')],
            'form_expedido' => ['nullable', Rule::in(array_keys(Natural::EXPEDIDOS))],
            'form_nombres' => [$requeridoSiEsNueva, 'nullable', 'string', 'max:100'],
            'form_apellido_paterno' => [$requeridoSiEsNueva, 'nullable', 'string', 'max:100'],
            'form_apellido_materno' => ['nullable', 'string', 'max:100'],
            'form_apellido_casado' => ['nullable', 'string', 'max:100'],
            'form_fecha_nacimiento' => ['nullable', 'date'],
            'form_genero' => [$requeridoSiEsNueva, 'nullable', Rule::in(['0', '1', 0, 1])],
            'form_id_ocupacion' => ['nullable', 'exists:ocupaciones_cob,id'],
            'form_telefonos_json' => ['nullable', 'json'],
            'form_rubros_json' => ['nullable', 'json'],
        ], [], [
            'form_ci' => 'CI',
            'form_complemento' => 'complemento',
            'form_url_respaldo' => 'documento de respaldo o autorización',
            'form_correo' => 'correo',
            'form_id_territorio' => 'territorio',
            'form_nombres' => 'nombres',
            'form_apellido_paterno' => 'apellido paterno',
            'form_genero' => 'género',
        ]);
    }

    private function crearPersonaNatural(array $datos): Persona
    {
        $persona = Persona::create([
            'domicilio' => $datos['form_domicilio'] ?? null,
            'nit' => $datos['form_nit'] ?? null,
            'correo' => mb_strtolower(trim($datos['form_correo'])),
            'id_territorio' => $datos['form_id_territorio'],
            'estado' => 'ACTIVO',
        ]);

        Natural::create([
            'id_persona' => $persona->id,
            'ci' => $this->normalizarDocumento($datos['form_ci']),
            'complemento' => $this->normalizarComplemento($datos['form_complemento'] ?? null),
            'expedido' => $datos['form_expedido'] ?? null,
            'nombres' => $this->mayuscula($datos['form_nombres']),
            'apellido_paterno' => $this->mayuscula($datos['form_apellido_paterno']),
            'apellido_materno' => $this->mayuscula($datos['form_apellido_materno'] ?? null),
            'apellido_casado' => $this->mayuscula($datos['form_apellido_casado'] ?? null),
            'fecha_nacimiento' => $datos['form_fecha_nacimiento'] ?? null,
            'genero' => $datos['form_genero'],
            'id_ocupacion' => $datos['form_id_ocupacion'] ?? null,
        ]);

        foreach ($this->normalizarListaJson($datos['form_telefonos_json'] ?? null, ['numero', 'estado']) as $telefono) {
            $numero = preg_replace('/\D+/', '', (string) ($telefono['numero'] ?? ''));

            if ($numero !== '') {
                Telefono::create([
                    'id_persona' => $persona->id,
                    'numero' => $numero,
                    'estado' => in_array(($telefono['estado'] ?? null), ['ACTIVO', 'INACTIVO'], true)
                        ? $telefono['estado']
                        : 'ACTIVO',
                ]);
            }
        }

        $idsRubros = collect($this->normalizarListaJson($datos['form_rubros_json'] ?? null, ['id']))
            ->pluck('id')
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique();

        $idsRubrosValidos = Rubro::query()
            ->whereIn('id', $idsRubros)
            ->whereNotNull('codigo_caeb')
            ->where('nivel_caeb', 'SUBCLASE')
            ->where('estado', 'ACTIVO')
            ->pluck('id');

        $persona->rubros()->sync($idsRubrosValidos->mapWithKeys(
            fn ($id) => [(int) $id => ['estado' => 'ACTIVO']]
        )->all());

        return $persona;
    }

    private function normalizarListaJson(?string $json, array $camposPermitidos): array
    {
        $items = json_decode($json ?: '[]', true);

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => collect($item)
                ->only($camposPermitidos)
                ->map(fn ($valor) => is_string($valor) ? trim($valor) : $valor)
                ->all())
            ->values()
            ->all();
    }

    /**
     * Si hay más de una persona con el mismo CI, el complemento pasa a ser obligatorio.
     */
    private function buscarNaturalPorDocumento(string $ci, ?string $complemento): ?Natural
    {
        $ci = $this->normalizarDocumento($ci);
        $coincidencias = Natural::withTrashed()
            ->with(['persona' => fn ($query) => $query->withTrashed()])
            ->whereRaw("UPPER(REPLACE(TRIM(ci), ' ', '')) = ?", [$ci])
            ->get();

        if ($coincidencias->count() <= 1) {
            return $coincidencias->first();
        }

        $complemento = $this->normalizarComplemento($complemento);

        if (! $complemento) {
            throw ValidationException::withMessages([
                'form_complemento' => 'Existe más de una persona con este CI. Ingrese el complemento para identificarla.',
                'complemento' => 'Existe más de una persona con este CI. Ingrese el complemento para identificarla.',
            ]);
        }

        return $coincidencias->first(fn (Natural $natural) =>
            $this->normalizarComplemento($natural->complemento) === $complemento
        );
    }

    private function validarIdentidadDisponible(Natural $natural): void
    {
        if ($natural->trashed() || ! $natural->persona || $natural->persona->trashed()
            || ! in_array((string) $natural->persona->estado, ['1', 'ACTIVO'], true)) {
            throw ValidationException::withMessages([
                'form_ci' => 'La identidad existe, pero está inactiva. INSO debe revisarla antes de crear una relación.',
                'ci' => 'La identidad existe, pero está inactiva. INSO debe revisarla antes de crear una relación.',
            ]);
        }
    }

    private function activarTramitador(
        Responsable $relacion,
        ?int $idUsuarioValidacion = null,
        ?string $nuevaPassword = null
    ): void
    {
        $relacion->loadMissing(['persona.natural', 'persona.usuario', 'rol']);
        $persona = $relacion->persona;
        $natural = $persona?->natural;

        if (! $persona || ! $natural || ! filled($persona->correo) || ! filled($natural->ci)) {
            throw ValidationException::withMessages([
                'form_estado' => 'La persona debe tener correo y CI antes de habilitar su cuenta.',
            ]);
        }

        $usuario = $persona->id_usuario
            ? User::withTrashed()->find($persona->id_usuario)
            : null;

        if ($usuario && ($usuario->trashed() || (string) $usuario->estado !== '1')) {
            throw ValidationException::withMessages([
                'form_estado' => 'La cuenta existente está inactiva. Debe ser revisada antes de validar la relación.',
            ]);
        }

        if (! $usuario) {
            if (User::withTrashed()->where('email', $persona->correo)->exists()) {
                throw ValidationException::withMessages([
                    'form_estado' => 'El correo ya pertenece a otra cuenta. Revise la identidad antes de validar.',
                ]);
            }

            $usuario = User::create([
                'name' => $this->nombreNatural($natural),
                'email' => mb_strtolower(trim($persona->correo)),
                'password' => $nuevaPassword ?: $natural->ci,
                'estado' => 1,
            ]);

            $persona->update(['id_usuario' => $usuario->id]);
        } elseif ($nuevaPassword) {
            $usuario->update(['password' => $nuevaPassword]);
        }

        $usuario->roles()->syncWithoutDetaching([$this->idRolTramitador()]);
        $cambiosRelacion = [
            'estado' => 'ACTIVO',
            'fecha_baja' => null,
        ];

        if ($idUsuarioValidacion) {
            $cambiosRelacion['id_usuario_validacion'] = $idUsuarioValidacion;
        }

        $relacion->update($cambiosRelacion);
    }

    private function notificarValidadores(Responsable $relacion): void
    {
        if (! Schema::hasTable('notificaciones_tramites')) {
            return;
        }

        $natural = $relacion->persona?->natural;
        $nombreTramitador = trim(implode(' ', array_filter([
            $natural?->nombres,
            $natural?->apellido_paterno,
            $natural?->apellido_materno,
        ]))) ?: 'Sin nombre';
        $nombreEmpresa = $relacion->empresa?->razon_social ?: 'Una empresa';

        $usuarios = User::query()
            ->where('estado', 1)
            ->where(function ($query) {
                $query->whereHas('permisosDirectos', fn ($permiso) =>
                    $permiso->where('nombre', 'tramitadores.validar')->where('permisos.estado', 1)
                )->orWhereHas('roles.permisos', fn ($permiso) =>
                    $permiso->where('nombre', 'tramitadores.validar')->where('permisos.estado', 1)
                );
            })
            ->get(['users.id']);

        foreach ($usuarios as $usuario) {
            NotificacionTramite::create([
                'id_usuario_destino' => $usuario->id,
                'id_usuario_emisor' => auth()->id(),
                'id_certificado' => null,
                'titulo' => 'Tramitador pendiente de validación',
                'mensaje' => sprintf(
                    '%s registró a %s como tramitador.',
                    $nombreEmpresa,
                    $nombreTramitador
                ),
                'estado' => 'ACTIVO',
            ]);
        }
    }

    private function autorizarValidacion(): void
    {
        abort_unless($this->esValidadorInso(), 403);
    }

    private function esValidadorInso(): bool
    {
        $usuario = auth()->user()?->loadMissing('persona.empresa');

        return $usuario
            && ! $usuario->persona?->empresa
            && $usuario->puede('tramitadores.validar');
    }

    private function validarRolTramitador(Responsable $tramitador): void
    {
        $tramitador->loadMissing('rol');
        abort_unless($this->esRolTramitador($tramitador->rol), 404);
    }

    private function autorizarRelacion(Responsable $tramitador): void
    {
        $tramitador->loadMissing('rol');

        if ($this->esValidadorInso() && $this->esRolTramitador($tramitador->rol)) {
            return;
        }

        $empresa = $this->empresaAutenticada();

        abort_unless(
            (int) $tramitador->id_empresa === (int) $empresa->id
                && $this->esRolTramitador($tramitador->rol),
            403
        );
    }

    private function autorizarRelacionEmpresa(Responsable $tramitador): void
    {
        $empresa = $this->empresaAutenticada();
        $tramitador->loadMissing('rol');

        abort_unless(
            (int) $tramitador->id_empresa === (int) $empresa->id
                && $this->esRolTramitador($tramitador->rol),
            403
        );
    }

    private function empresaAutenticada(): Empresa
    {
        $empresa = auth()->user()?->loadMissing('persona.empresa')?->persona?->empresa;

        abort_if(! $empresa, 403, 'Solo una empresa puede registrar tramitadores.');

        return $empresa;
    }

    private function idRolTramitador(): int
    {
        $idRol = Role::query()->where('slug', 'tramitador')->where('estado', 1)->value('id');

        abort_if(! $idRol, 422, 'No existe el rol tramitador activo.');

        return (int) $idRol;
    }

    private function esRolTramitador(?Role $rol): bool
    {
        return $rol && (string) $rol->estado === '1'
            && ($rol->slug === 'tramitador' || str_contains(mb_strtoupper((string) $rol->name), 'TRAMITADOR'));
    }

    private function normalizarDocumento(string $ci): string
    {
        return mb_strtoupper(str_replace(' ', '', trim($ci)), 'UTF-8');
    }

    private function normalizarComplemento(?string $complemento): ?string
    {
        return filled($complemento)
            ? mb_strtoupper(str_replace(' ', '', trim($complemento)), 'UTF-8')
            : null;
    }

    private function nombreNatural(Natural $natural): string
    {
        return trim(implode(' ', array_filter([
            $natural->nombres,
            $natural->apellido_paterno,
            $natural->apellido_materno,
        ]))) ?: $natural->ci;
    }

    private function mayuscula(?string $valor): ?string
    {
        return filled($valor) ? mb_strtoupper(trim($valor), 'UTF-8') : null;
    }
}
