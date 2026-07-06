<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RolController extends Controller
{
    /**
     * Muestra el listado principal de roles.
     */
    public function index()
    {
        return view('roles.index');
    }

    /**
     * Abre el formulario para crear rol y carga permisos activos disponibles.
     */
    public function create()
    {
        return view('roles.create', [
            'permisos' => Permiso::where('estado', 1)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Guarda el rol y relaciona los permisos seleccionados o creados en el formulario.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarRol($solicitud);

        try {
            DB::beginTransaction();

            $rol = Role::create([
                'name' => $datos['form_name'],
                'slug' => $datos['form_slug'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'especial' => $datos['form_especial'] ?? null,
                'estado' => $datos['form_estado'],
            ]);

            $rol->permisos()->sync($this->obtenerPermisosDelRol($datos));

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El rol se registro correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('roles_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el rol.')
                ->withInput();
        }
    }

    /**
     * Reservado para una vista de detalle del rol si el modulo la necesita.
     */
    public function show(Role $rol)
    {
        //
    }

    /**
     * Abre el formulario de edicion con los permisos actuales del rol.
     */
    public function edit(Role $rol)
    {
        $rol->load('permisos');

        return view('roles.edit', [
            'rol' => $rol,
            'permisos' => Permiso::where('estado', 1)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Actualiza el rol y vuelve a sincronizar todos sus permisos.
     */
    public function update(Request $solicitud, Role $rol)
    {
        $datos = $this->validarRol($solicitud, $rol);

        try {
            DB::beginTransaction();

            $rol->update([
                'name' => $datos['form_name'],
                'slug' => $datos['form_slug'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'especial' => $datos['form_especial'] ?? null,
                'estado' => $datos['form_estado'],
            ]);

            $rol->permisos()->sync($this->obtenerPermisosDelRol($datos));

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => 'El rol se actualizo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('roles_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el rol.')
                ->withInput();
        }
    }

    /**
     * Elimina el rol y limpia sus relaciones con usuarios y permisos.
     */
    public function destroy(Role $rol)
    {
        try {
            DB::beginTransaction();

            $rol->users()->detach();
            $rol->permisos()->detach();
            $rol->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El rol se elimino correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('roles_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('roles_index')
                ->with('error', 'No se pudo eliminar el rol.');
        }
    }

    // Valida los datos del rol y permite ignorar el mismo registro cuando se edita.
    private function validarRol(Request $solicitud, ?Role $rol = null): array
    {
        return $solicitud->validate([
            'form_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($rol?->id)->whereNull('deleted_at'),
            ],
            'form_slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'slug')->ignore($rol?->id)->whereNull('deleted_at'),
            ],
            'form_descripcion' => ['nullable', 'string'],
            'form_especial' => ['nullable', 'string', 'max:255'],
            'form_estado' => ['required', 'in:0,1'],
            'form_permisos' => ['nullable', 'array'],
            'form_permisos.*' => ['integer', 'exists:permisos,id'],
            'form_permisos_nuevos' => ['nullable', 'array'],
            'form_permisos_nuevos.*' => ['nullable', 'string', 'max:255', 'distinct:ignore_case'],
        ], [], [
            'form_name' => 'nombre del rol',
            'form_slug' => 'codigo interno',
            'form_descripcion' => 'descripcion',
            'form_especial' => 'marca especial',
            'form_estado' => 'estado',
            'form_permisos' => 'permisos',
            'form_permisos_nuevos' => 'permisos nuevos',
            'form_permisos_nuevos.*' => 'permiso nuevo',
        ]);
    }

    // Une permisos existentes con permisos nuevos creados desde el formulario.
    private function obtenerPermisosDelRol(array $datos): array
    {
        // Convierte los ids seleccionados en numeros validos antes de sincronizar.
        $permisos = collect($datos['form_permisos'] ?? [])
            ->map(fn ($idPermiso) => (int) $idPermiso)
            ->filter();

        // Normaliza nombres para evitar duplicados como "Crear   usuario" y "Crear usuario".
        collect($datos['form_permisos_nuevos'] ?? [])
            ->map(fn ($nombrePermiso) => trim(preg_replace('/\s+/', ' ', (string) $nombrePermiso)))
            ->filter()
            ->unique(fn ($nombrePermiso) => mb_strtolower($nombrePermiso))
            ->each(function (string $nombrePermiso) use ($permisos) {
                // Si el permiso ya existe, se reutiliza; si no, se crea activo.
                $permiso = Permiso::firstOrCreate(
                    ['nombre' => $nombrePermiso],
                    ['estado' => 1]
                );

                $permisos->push($permiso->id);
            });

        return $permisos->unique()->values()->all();
    }
}
