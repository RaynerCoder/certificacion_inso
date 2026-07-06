<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CargoController extends Controller
{
    /**
     * Muestra el listado principal de cargos.
     */
    public function index()
    {
        return view('cargos.index', [
            'areas' => Area::where('estado', 1)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Redirige al listado porque el cargo se crea desde modal.
     */
    public function create()
    {
        return redirect()->route('cargos_index');
    }

    /**
     * Guarda un cargo nuevo creado desde el modal.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarCargo($solicitud);

        try {
            DB::beginTransaction();

            Cargo::create([
                'id_area' => $datos['form_id_area'],
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'estado' => $datos['form_estado'],
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El cargo se registro correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('cargos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el cargo.')
                ->withInput()
                ->with('modal_cargo', 'crear');
        }
    }

    /**
     * Reservado para una vista de detalle si el modulo la necesita.
     */
    public function show(Cargo $cargo)
    {
        //
    }

    /**
     * Redirige al listado porque el cargo se edita desde modal.
     */
    public function edit(Cargo $cargo)
    {
        return redirect()->route('cargos_index');
    }

    /**
     * Actualiza un cargo desde el modal de edicion.
     */
    public function update(Request $solicitud, Cargo $cargo)
    {
        $datos = $this->validarCargo($solicitud, $cargo);

        try {
            DB::beginTransaction();

            // Primero llena el modelo para saber si realmente cambio algun dato.
            $cargo->fill([
                'id_area' => $datos['form_id_area'],
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'estado' => $datos['form_estado'],
            ]);

            if (!$cargo->isDirty()) {
                DB::commit();

                session()->flash('swal', [
                    'title' => 'Sin cambios',
                    'text' => 'No se detectaron cambios en el cargo.',
                    'icon' => 'info',
                ]);

                return redirect()->route('cargos_index');
            }

            $cargo->save();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => 'El cargo se actualizo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('cargos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el cargo.')
                ->withInput()
                ->with('modal_cargo', 'editar')
                ->with('cargo_editar', $cargo->id);
        }
    }

    /**
     * Elimina un cargo si no esta asignado a funcionarios.
     */
    public function destroy(Cargo $cargo)
    {
        if ($cargo->funcionarios()->exists()) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'El cargo esta asignado a uno o mas funcionarios.',
                'icon' => 'error',
            ]);

            return redirect()->route('cargos_index');
        }

        try {
            DB::beginTransaction();

            $cargo->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El cargo se elimino correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('cargos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('cargos_index')
                ->with('error', 'No se pudo eliminar el cargo.');
        }
    }

    // Valida los datos del cargo y evita nombres duplicados.
    private function validarCargo(Request $solicitud, ?Cargo $cargo = null): array
    {
        return $solicitud->validate([
            'form_nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cargos', 'nombre')->ignore($cargo?->id),
            ],
            'form_descripcion' => ['nullable', 'string'],
            'form_id_area' => ['required', 'integer', 'exists:areas,id'],
            'form_estado' => ['required', 'in:0,1'],
        ], [], [
            'form_nombre' => 'nombre del cargo',
            'form_descripcion' => 'descripcion',
            'form_id_area' => 'area',
            'form_estado' => 'estado',
        ]);
    }
}
