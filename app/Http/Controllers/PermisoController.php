<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PermisoController extends Controller
{
    /**
     * Muestra el listado principal de permisos.
     */
    public function index()
    {
        return view('permisos.index');
    }

    /**
     * Redirige al listado porque el permiso se crea desde modal.
     */
    public function create()
    {
        return redirect()->route('permisos_index');
    }

    /**
     * Guarda un nuevo permiso creado desde el modal del listado.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarPermiso($solicitud);

        try {
            DB::beginTransaction();

            Permiso::create([
                'nombre' => $datos['form_nombre'],
                'estado' => $datos['form_estado'],
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El permiso se registro correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('permisos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el permiso.')
                ->withInput()
                ->with('modal_permiso', 'crear');
        }
    }

    /**
     * Reservado para una vista de detalle del permiso si el modulo la necesita.
     */
    public function show(Permiso $permiso)
    {
        //
    }

    /**
     * Redirige al listado porque el permiso se edita desde modal.
     */
    public function edit(Permiso $permiso)
    {
        return redirect()->route('permisos_index');
    }

    /**
     * Actualiza el nombre y estado de un permiso desde el modal de edicion.
     */
    public function update(Request $solicitud, Permiso $permiso)
    {
        $datos = $this->validarPermiso($solicitud, $permiso);

        try {
            DB::beginTransaction();

            $permiso->update([
                'nombre' => $datos['form_nombre'],
                'estado' => $datos['form_estado'],
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => 'El permiso se actualizo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('permisos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el permiso.')
                ->withInput()
                ->with('modal_permiso', 'editar')
                ->with('permiso_editar', $permiso->id);
        }
    }

    /**
     * Elimina el permiso y lo desasocia antes de roles y usuarios directos.
     */
    public function destroy(Permiso $permiso)
    {
        try {
            DB::beginTransaction();

            $permiso->roles()->detach();
            $permiso->users()->detach();
            $permiso->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El permiso se elimino correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('permisos_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('permisos_index')
                ->with('error', 'No se pudo eliminar el permiso.');
        }
    }

    // Valida el permiso y evita nombres duplicados, ignorando el mismo registro al editar.
    private function validarPermiso(Request $solicitud, ?Permiso $permiso = null): array
    {
        return $solicitud->validate([
            'form_nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permisos', 'nombre')->ignore($permiso?->id)->whereNull('deleted_at'),
            ],
            'form_estado' => ['required', 'in:0,1'],
        ], [], [
            'form_nombre' => 'nombre del permiso',
            'form_estado' => 'estado',
        ]);
    }
}
