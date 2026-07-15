<?php

namespace App\Http\Controllers;

use App\Models\Responsable;
use App\Models\Empresa;
use App\Models\Natural;
use App\Models\Persona;
use App\Models\Rubro;
use App\Models\Role;
use App\Models\Telefono;
use App\Models\Territorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResponsableController extends Controller
{
    private const NUEVO_RESPONSABLE = 'NUEVO';

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('responsables.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $empresas = Empresa::with('persona')->orderBy('razon_social')->get();
        // Un responsable de empresa se registra como persona natural.
        $personas = Persona::with('natural')->whereHas('natural')->orderBy('id')->get();
        // Catalogo de roles para relacionar el responsable sin guardar texto duplicado.
        $roles = Role::where('estado', 1)->orderBy('name')->get();

        return view('responsables.create', [
            'empresas' => $empresas,
            'personas' => $personas,
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarResponsable($solicitud);
        $this->validarResponsableDuplicado($datos['form_id_empresa'], $datos['form_id_persona']);

        try {
            DB::beginTransaction();

            Responsable::create([
                'id_empresa' => $datos['form_id_empresa'],
                'id_persona' => $datos['form_id_persona'],
                'id_rol' => $datos['form_id_rol'],
                'url_respaldo' => $this->guardarRespaldoResponsable($solicitud),
                'fecha_registro' => $datos['form_fecha_registro'] ?? null,
                'fecha_baja' => $datos['form_fecha_baja'] ?? null,
                'estado' => $datos['form_estado'] ?? 'ACTIVO',
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Registrado',
                'text' => 'El responsable de empresa se registro correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('responsables_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el responsable.')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Responsable $responsable)
    {
        $responsable->load([
            'empresa.persona.territorio',
            'empresa.tipoEmpresa',
            'persona.natural',
            'persona.territorio',
            'persona.telefonos',
            'persona.rubros',
            'rol',
        ]);

        return view('responsables.show', compact('responsable'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Responsable $responsable)
    {
        $responsable->load([
            'empresa',
            'persona.natural',
            'persona.telefonos',
            'persona.rubros',
            'persona.territorio',
            'rol',
        ]);
        $empresas = Empresa::with('persona')->orderBy('razon_social')->get();
        // Un responsable de empresa se registra como persona natural.
        $personas = Persona::with(['natural', 'telefonos', 'rubros', 'territorio'])
            ->whereHas('natural')
            ->orderBy('id')
            ->get();
        $territorios = Territorio::orderBy('nombre')->get();
        // Se usa la tabla roles para asignar el rol del responsable.
        $roles = Role::where('estado', 1)->orderBy('name')->get();

        return view('responsables.edit', compact('responsable', 'empresas', 'personas', 'territorios', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, Responsable $responsable)
    {
        $datos = $this->validarResponsable($solicitud, true);

        if ($this->esTramitador($responsable) && ($datos['form_estado'] ?? 'ACTIVO') !== 'ACTIVO') {
            return back()->with('error', 'La baja de un tramitador debe realizarse desde el módulo Tramitadores.');
        }
        $esNuevoResponsable = $datos['form_id_persona'] === self::NUEVO_RESPONSABLE;
        $datosPersona = $this->validarPersonaResponsable(
            $solicitud,
            $esNuevoResponsable ? null : (int) $datos['form_id_persona']
        );

        if (! $esNuevoResponsable) {
            $this->validarResponsableDuplicado(
                $datos['form_id_empresa'],
                (int) $datos['form_id_persona'],
                $responsable->id
            );
        }

        try {
            DB::beginTransaction();

            $respaldo = $this->guardarRespaldoResponsable($solicitud, $responsable->url_respaldo);

            if ($esNuevoResponsable) {
                // Crea una persona natural nueva y la asigna como responsable de la empresa.
                $personaResponsable = $this->crearPersonaResponsable($datosPersona);
            } else {
                // Actualiza el registro real de la persona responsable seleccionada.
                $personaResponsable = Persona::with('natural')->findOrFail($datos['form_id_persona']);
                $this->actualizarPersonaResponsable($personaResponsable, $datosPersona);
            }

            $responsable->update([
                'id_empresa' => $datos['form_id_empresa'],
                'id_persona' => $personaResponsable->id,
                'id_rol' => $datos['form_id_rol'],
                'url_respaldo' => $respaldo,
                'fecha_registro' => $datos['form_fecha_registro'] ?? null,
                'fecha_baja' => $datos['form_fecha_baja'] ?? null,
                'estado' => $datos['form_estado'] ?? 'ACTIVO',
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => 'El responsable de empresa se actualizo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('responsables_show', $responsable);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el responsable.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Responsable $responsable)
    {
        if ($this->esTramitador($responsable)) {
            return back()->with('error', 'Un tramitador no se elimina desde Responsables. Use el módulo Tramitadores para darlo de baja.');
        }

        $responsable->delete();

        session()->flash('swal', [
            'title' => 'Eliminado',
            'text' => 'El responsable de empresa se elimino correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('responsables_index');
    }

    /**
     * Los tramitadores conservan historial; por eso no usan la eliminación general de responsables.
     */
    private function esTramitador(Responsable $responsable): bool
    {
        $responsable->loadMissing('rol');

        return $responsable->rol?->slug === 'tramitador';
    }

    /**
     * Valida los campos propios de la relacion empresa-responsable.
     */
    private function validarResponsable(Request $solicitud, bool $permiteNuevoResponsable = false): array
    {
        $datos = $solicitud->validate([
            'form_id_empresa' => ['required', 'exists:empresas,id'],
            'form_id_persona' => ['required', 'string', 'max:50'],
            'form_id_rol' => ['required', 'exists:roles,id'],
            'form_url_respaldo' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'form_quitar_respaldo' => ['nullable', 'boolean'],
            'form_fecha_registro' => ['nullable', 'date'],
            'form_fecha_baja' => ['nullable', 'date'],
            'form_estado' => ['nullable', 'string', 'max:50'],
        ], [], [
            'form_id_empresa' => 'empresa',
            'form_id_persona' => 'persona responsable',
            'form_id_rol' => 'rol del responsable',
            'form_url_respaldo' => 'respaldo PDF',
            'form_quitar_respaldo' => 'quitar respaldo PDF',
            'form_fecha_registro' => 'fecha de registro',
            'form_fecha_baja' => 'fecha de baja',
            'form_estado' => 'estado',
        ]);

        if ($datos['form_id_persona'] === self::NUEVO_RESPONSABLE) {
            if ($permiteNuevoResponsable) {
                return $datos;
            }

            throw ValidationException::withMessages([
                'form_id_persona' => 'Seleccione una persona responsable registrada.',
            ]);
        }

        if (! Persona::whereKey($datos['form_id_persona'])->exists()) {
            throw ValidationException::withMessages([
                'form_id_persona' => 'La persona responsable seleccionada no existe.',
            ]);
        }

        $datos['form_id_persona'] = (int) $datos['form_id_persona'];

        return $datos;
    }

    /**
     * Evita asignar dos veces la misma persona como responsable de la misma empresa.
     */
    private function validarResponsableDuplicado(int $idEmpresa, int $idPersona, ?int $ignorarId = null): void
    {
        $duplicado = Responsable::where('id_empresa', $idEmpresa)
            ->where('id_persona', $idPersona)
            ->when($ignorarId, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'form_id_persona' => 'Esta persona ya esta registrada como responsable de la empresa seleccionada.',
            ]);
        }
    }

    /**
     * Guarda el PDF de respaldo, permite quitar el anterior y conserva el archivo si no hubo cambios.
     */
    private function guardarRespaldoResponsable(Request $solicitud, ?string $respaldoAnterior = null): ?string
    {
        // Si el usuario marco quitar PDF, se limpia la referencia y solo se borra archivo fisico si es local.
        if ($solicitud->boolean('form_quitar_respaldo')) {
            if ($respaldoAnterior && ! str_starts_with($respaldoAnterior, 'http')) {
                Storage::disk('public')->delete($respaldoAnterior);
            }

            $respaldoAnterior = null;
        }

        if (! $solicitud->hasFile('form_url_respaldo')) {
            return $respaldoAnterior;
        }

        // Si llega un PDF nuevo, reemplaza al anterior para no dejar archivos duplicados.
        if ($respaldoAnterior && ! str_starts_with($respaldoAnterior, 'http')) {
            Storage::disk('public')->delete($respaldoAnterior);
        }

        return $solicitud->file('form_url_respaldo')->store('responsables', 'public');
    }

    /**
     * Valida los datos propios de la persona que actua como responsable.
     * Estos campos pertenecen a personas, naturals, telefonos y rubros.
     */
    private function validarPersonaResponsable(Request $solicitud, ?int $idPersona): array
    {
        $idNatural = $idPersona ? Natural::where('id_persona', $idPersona)->value('id') : null;
        $reglaNitUnico = Rule::unique('personas', 'nit');
        $reglaCorreoUnico = Rule::unique('personas', 'correo');
        $reglaCiUnico = Rule::unique('naturals', 'ci');

        if ($idPersona) {
            $reglaNitUnico->ignore($idPersona);
            $reglaCorreoUnico->ignore($idPersona);
        }

        if ($idNatural) {
            $reglaCiUnico->ignore($idNatural);
        }

        $datos = $solicitud->validate([
            'form_domicilio' => ['nullable', 'string', 'max:255'],
            'form_nit' => [
                'nullable',
                'string',
                'max:50',
                $reglaNitUnico,
            ],
            'form_correo' => [
                'required',
                'email',
                'max:50',
                $reglaCorreoUnico,
            ],
            'form_id_territorio' => ['required', 'exists:territorios,id'],
            'form_estado_persona' => ['nullable', 'string', 'max:50'],
            'form_ci' => [
                'required',
                'string',
                'max:50',
                $reglaCiUnico,
            ],
            'form_complemento' => ['nullable', 'string', 'max:10'],
            'form_expedido' => ['nullable', 'string', 'max:10'],
            'form_nombres' => ['required', 'string', 'max:100'],
            'form_apellido_paterno' => ['required', 'string', 'max:100'],
            'form_apellido_materno' => ['nullable', 'string', 'max:100'],
            'form_apellido_casado' => ['nullable', 'string', 'max:100'],
            'form_fecha_nacimiento' => ['nullable', 'date'],
            'form_genero' => ['required', 'in:0,1'],
            'form_ocupacion' => ['nullable', 'string', 'max:255'],
            'form_telefonos_json' => ['nullable', 'json'],
            'form_rubros_json' => ['nullable', 'json'],
        ], [], [
            'form_correo' => 'correo del responsable',
            'form_id_territorio' => 'territorio del responsable',
            'form_ci' => 'CI del responsable',
            'form_nombres' => 'nombres del responsable',
            'form_apellido_paterno' => 'apellido paterno del responsable',
            'form_genero' => 'genero del responsable',
        ]);

        $datos['telefonos'] = $this->normalizarListaJson($datos['form_telefonos_json'] ?? null, ['numero', 'tipo']);
        $datos['rubros'] = $this->normalizarListaJson($datos['form_rubros_json'] ?? null, ['nombre', 'estado']);

        return $datos;
    }

    /**
     * Crea una persona natural nueva para usarla como responsable de la empresa.
     */
    private function crearPersonaResponsable(array $datos): Persona
    {
        $persona = Persona::create([
            'domicilio' => $datos['form_domicilio'] ?? null,
            'nit' => $datos['form_nit'] ?? null,
            'correo' => $datos['form_correo'],
            'id_territorio' => $datos['form_id_territorio'],
            'estado' => $datos['form_estado_persona'] ?? 'ACTIVO',
        ]);

        $this->actualizarPersonaResponsable($persona, $datos);

        return $persona;
    }

    /**
     * Actualiza la persona responsable sin cambiar la empresa a la que esta asignada.
     */
    private function actualizarPersonaResponsable(Persona $persona, array $datos): void
    {
        $persona->update([
            'domicilio' => $datos['form_domicilio'] ?? null,
            'nit' => $datos['form_nit'] ?? null,
            'correo' => $datos['form_correo'],
            'id_territorio' => $datos['form_id_territorio'],
            'estado' => $datos['form_estado_persona'] ?? 'ACTIVO',
        ]);

        Natural::updateOrCreate(
            ['id_persona' => $persona->id],
            [
                'ci' => $datos['form_ci'],
                'complemento' => $datos['form_complemento'] ?? null,
                'expedido' => $datos['form_expedido'] ?? null,
                'nombres' => $this->mayuscula($datos['form_nombres']),
                'apellido_paterno' => $this->mayuscula($datos['form_apellido_paterno']),
                'apellido_materno' => $this->mayuscula($datos['form_apellido_materno'] ?? null),
                'apellido_casado' => $this->mayuscula($datos['form_apellido_casado'] ?? null),
                'fecha_nacimiento' => $datos['form_fecha_nacimiento'] ?? null,
                'genero' => $datos['form_genero'],
                'ocupacion' => $this->mayuscula($datos['form_ocupacion'] ?? null),
            ]
        );

        // Se reemplazan las listas porque el formulario edita el conjunto completo del responsable.
        Telefono::where('id_persona', $persona->id)->delete();
        foreach ($datos['telefonos'] as $telefono) {
            if (! empty($telefono['numero'])) {
                Telefono::create([
                    'id_persona' => $persona->id,
                    'numero' => $telefono['numero'],
                    'estado' => $telefono['tipo'] ?: 'CELULAR',
                ]);
            }
        }

        $this->sincronizarRubrosResponsable($persona, $datos['rubros'] ?? []);
    }

    /**
     * Guarda los rubros como catalogo y vincula la persona por la tabla pivote.
     */
    private function sincronizarRubrosResponsable(Persona $persona, array $rubros): void
    {
        $idsRubros = collect($rubros)
            ->map(function ($rubro) {
                if (is_array($rubro) && ! empty($rubro['id'])) {
                    return (int) $rubro['id'];
                }

                $nombre = is_array($rubro) ? ($rubro['nombre'] ?? null) : $rubro;
                $nombre = $this->mayuscula($nombre);

                if (! $nombre) {
                    return null;
                }

                return Rubro::firstOrCreate(
                    ['nombre' => $nombre],
                    ['descripcion' => null, 'estado' => 'ACTIVO']
                )->id;
            })
            ->filter()
            ->unique()
            ->values();

        $persona->rubros()->sync(
            $idsRubros->mapWithKeys(fn ($idRubro) => [(int) $idRubro => ['estado' => 'ACTIVO']])->all()
        );
    }
    /**
     * Convierte un JSON de listas editables en arreglo seguro para guardar.
     */
    private function normalizarListaJson(?string $json, array $camposPermitidos): array
    {
        $items = json_decode($json ?: '[]', true);

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function ($item) use ($camposPermitidos) {
                return collect($item)
                    ->only($camposPermitidos)
                    ->map(fn ($valor) => is_string($valor) ? trim($valor) : $valor)
                    ->all();
            })
            ->values()
            ->all();
    }

    /**
     * Mantiene el estilo de guardado usado en el modulo de personas.
     */
    private function mayuscula(?string $valor): ?string
    {
        return filled($valor) ? mb_strtoupper(trim($valor), 'UTF-8') : null;
    }
}


