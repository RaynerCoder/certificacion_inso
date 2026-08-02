<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Natural;
use App\Models\Persona;
use App\Models\Responsable;
use App\Models\Role;
use App\Models\Territorio;
use App\Services\GestionTramitadoresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Muestra exclusivamente los tramitadores vinculados con la empresa autenticada.
     */
    public function index(): View
    {
        $empresa = $this->empresaAutenticada();

        return view('tramitadores.index', compact('empresa'));
    }

    /**
     * Abre el primer paso del flujo: solicitud de vinculación empresa-tramitador.
     */
    public function create(): View
    {
        $empresa = $this->empresaAutenticada();
        $territorios = Territorio::query()->orderBy('nombre')->get();

        return view('tramitadores.create', compact('empresa', 'territorios'));
    }

    /**
     * Busca una identidad existente sin exponer datos personales completos.
     */
    public function buscarPersona(Request $request): JsonResponse
    {
        $empresa = $this->empresaAutenticada();
        $datos = $request->validate([
            'ci' => ['required', 'string', 'max:50'],
            'complemento' => ['nullable', 'string', 'max:10'],
        ]);

        $natural = $this->buscarNaturalPorDocumento($datos['ci'], $datos['complemento'] ?? null);

        if (! $natural) {
            return response()->json(['existe' => false]);
        }

        $persona = $natural->persona;
        $this->validarIdentidadDisponible($natural);

        $relacion = Responsable::withTrashed()
            ->where('id_empresa', $empresa->id)
            ->where('id_persona', $persona->id)
            ->where('id_rol', $this->idRolTramitador())
            ->latest('id')
            ->first();

        return response()->json([
            'existe' => true,
            'nombre' => $this->nombreNatural($natural),
            'correo' => $this->ocultarCorreo($persona->correo),
            'tiene_cuenta' => (bool) $persona->id_usuario,
            'relacion' => $relacion ? [
                'existe' => true,
                'estado' => $relacion->estado,
            ] : ['existe' => false],
        ]);
    }

    /**
     * Registra la solicitud. La cuenta y el rol de acceso se crean después, al validar en INSO.
     */
    public function store(Request $request): RedirectResponse
    {
        $empresa = $this->empresaAutenticada();
        $documento = $request->validate([
            'form_ci' => ['required', 'string', 'max:50'],
            'form_complemento' => ['nullable', 'string', 'max:10'],
        ]);

        $natural = $this->buscarNaturalPorDocumento(
            $documento['form_ci'],
            $documento['form_complemento'] ?? null
        );

        if ($natural) {
            $this->validarIdentidadDisponible($natural);
        }

        $datos = $this->validarSolicitud($request, ! $natural);
        $rutaRespaldo = $request->file('form_carta_autorizacion')->store(
            "tramitadores/{$empresa->id}",
            'local'
        );

        try {
            DB::transaction(function () use ($empresa, $natural, $datos, $rutaRespaldo): void {
                $persona = $natural?->persona;

                if (! $persona) {
                    $persona = $this->crearPersonaNatural($datos);
                }

                $idRolTramitador = $this->idRolTramitador();

                // Se consideran también relaciones dadas de baja para conservar el historial.
                $relacionExistente = Responsable::withTrashed()
                    ->where('id_empresa', $empresa->id)
                    ->where('id_persona', $persona->id)
                    ->where('id_rol', $idRolTramitador)
                    ->lockForUpdate()
                    ->first();

                if ($relacionExistente) {
                    throw ValidationException::withMessages([
                        'form_ci' => 'Esta persona ya tiene una relación como tramitador con la empresa. INSO debe revisar la relación existente.',
                    ]);
                }

                Responsable::create([
                    'id_empresa' => $empresa->id,
                    'id_persona' => $persona->id,
                    'id_rol' => $idRolTramitador,
                    'url_respaldo' => $rutaRespaldo,
                    'fecha_registro' => $datos['form_fecha_registro'],
                    'fecha_baja' => null,
                    'estado' => 'PENDIENTE_VALIDACION',
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($rutaRespaldo);
            throw $exception;
        }

        session()->flash('swal', [
            'title' => 'Solicitud registrada',
            'text' => 'INSO debe validar la carta antes de habilitar al tramitador.',
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
        $tramitador->loadMissing(['persona.natural', 'persona.usuario', 'rol']);

        return view('tramitadores.show', compact('tramitador'));
    }

    /**
     * Entrega la carta mediante una ruta autenticada; nunca se publica su URL real.
     */
    public function descargarCarta(Responsable $tramitador)
    {
        $this->autorizarRelacion($tramitador);

        abort_unless($tramitador->url_respaldo, 404);

        // Los documentos nuevos son privados. El segundo disco mantiene accesibles respaldos históricos.
        $disco = Storage::disk('local')->exists($tramitador->url_respaldo) ? 'local' : 'public';
        abort_unless(Storage::disk($disco)->exists($tramitador->url_respaldo), 404);

        return Storage::disk($disco)->download(
            $tramitador->url_respaldo,
            "carta-autorizacion-tramitador-{$tramitador->id}.pdf"
        );
    }

    /**
     * Da de baja una relación activa sin eliminar su historial.
     */
    public function darBaja(Responsable $tramitador): RedirectResponse
    {
        $this->autorizarRelacion($tramitador);

        try {
            $cantidadTransferida = DB::transaction(fn () => $this->gestionTramitadores->darDeBaja(
                $tramitador,
                auth()->user()
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
            'form_ci' => ['required', 'string', 'max:50'],
            'form_complemento' => ['nullable', 'string', 'max:10'],
            'form_carta_autorizacion' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'form_fecha_registro' => ['required', 'date'],
            'form_correo' => [$requeridoSiEsNueva, 'nullable', 'email', 'max:50', Rule::unique('personas', 'correo')],
            'form_id_territorio' => [$requeridoSiEsNueva, 'nullable', 'exists:territorios,id'],
            'form_domicilio' => ['nullable', 'string', 'max:255'],
            'form_expedido' => ['nullable', Rule::in(array_keys(Natural::EXPEDIDOS))],
            'form_nombres' => [$requeridoSiEsNueva, 'nullable', 'string', 'max:100'],
            'form_apellido_paterno' => [$requeridoSiEsNueva, 'nullable', 'string', 'max:100'],
            'form_apellido_materno' => ['nullable', 'string', 'max:100'],
            'form_genero' => [$requeridoSiEsNueva, 'nullable', Rule::in(['0', '1', 0, 1])],
        ], [], [
            'form_ci' => 'CI',
            'form_complemento' => 'complemento',
            'form_carta_autorizacion' => 'carta de autorización',
            'form_fecha_registro' => 'fecha de registro',
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
            'genero' => $datos['form_genero'],
        ]);

        return $persona;
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

    private function autorizarRelacion(Responsable $tramitador): void
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
        ]))) ?: 'Sin nombre';
    }

    private function ocultarCorreo(?string $correo): ?string
    {
        if (! $correo || ! str_contains($correo, '@')) {
            return null;
        }

        [$usuario, $dominio] = explode('@', $correo, 2);
        $visible = mb_substr($usuario, 0, min(2, mb_strlen($usuario)));

        return $visible . '***@' . $dominio;
    }

    private function mayuscula(?string $valor): ?string
    {
        return filled($valor) ? mb_strtoupper(trim($valor), 'UTF-8') : null;
    }
}
