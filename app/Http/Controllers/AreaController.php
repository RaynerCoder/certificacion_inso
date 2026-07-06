<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    /**
     * Muestra el listado principal de areas.
     */
    public function index()
    {
        return view('areas.index', [
            'areasPadre' => Area::where('estado', 1)->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Redirige al listado porque el area se crea desde modal.
     */
    public function create()
    {
        return redirect()->route('areas_index');
    }

    /**
     * Guarda una nueva area o subarea.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarArea($solicitud);

        try {
            DB::beginTransaction();

            Area::create([
                'id_area_padre' => $datos['form_id_area_padre'] ?? null,
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'estado' => $datos['form_estado'],
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El area se registro correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('areas_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el area.')
                ->withInput()
                ->with('modal_area', 'crear');
        }
    }

    /**
     * Reservado para una vista de detalle si el modulo la necesita.
     */
    public function show(Area $area)
    {
        //
    }

    /**
     * Redirige al listado porque el area se edita desde modal.
     */
    public function edit(Area $area)
    {
        return redirect()->route('areas_index');
    }

    /**
     * Actualiza el area seleccionada desde el modal de edicion.
     */
    public function update(Request $solicitud, Area $area)
    {
        $datos = $this->validarArea($solicitud, $area);

        try {
            DB::beginTransaction();

            // Primero llena el modelo para distinguir entre una edicion real y un envio sin cambios.
            $area->fill([
                'id_area_padre' => $datos['form_id_area_padre'] ?? null,
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'estado' => $datos['form_estado'],
            ]);

            if (!$area->isDirty()) {
                DB::commit();

                session()->flash('swal', [
                    'title' => 'Sin cambios',
                    'text' => 'No se detectaron cambios en el area.',
                    'icon' => 'info',
                ]);

                return redirect()->route('areas_index');
            }

            $area->save();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => 'El area se actualizo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('areas_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el area.')
                ->withInput()
                ->with('modal_area', 'editar')
                ->with('area_editar', $area->id);
        }
    }

    /**
     * Elimina un area solo si no tiene subareas ni cargos relacionados.
     */
    public function destroy(Area $area)
    {
        if ($area->subareas()->exists()) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'El area tiene subareas relacionadas.',
                'icon' => 'error',
            ]);

            return redirect()->route('areas_index');
        }

        if ($area->cargos()->exists()) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'El area tiene cargos relacionados.',
                'icon' => 'error',
            ]);

            return redirect()->route('areas_index');
        }

        try {
            DB::beginTransaction();

            $area->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El area se elimino correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('areas_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('areas_index')
                ->with('error', 'No se pudo eliminar el area.');
        }
    }

    // Valida el catalogo de areas y evita jerarquias circulares.
    private function validarArea(Request $solicitud, ?Area $area = null): array
    {
        $idsProhibidos = $area
            ? array_merge([$area->id], $this->idsSubareas($area))
            : [];

        return $solicitud->validate([
            'form_id_area_padre' => [
                'nullable',
                'integer',
                'exists:areas,id',
                Rule::notIn($idsProhibidos),
            ],
            'form_nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('areas', 'nombre')->ignore($area?->id),
            ],
            'form_descripcion' => ['nullable', 'string'],
            'form_estado' => ['required', 'in:0,1'],
        ], [], [
            'form_id_area_padre' => 'area superior',
            'form_nombre' => 'nombre del area',
            'form_descripcion' => 'descripcion',
            'form_estado' => 'estado',
        ]);
    }

    // Devuelve las subareas descendientes para impedir ciclos al editar.
    private function idsSubareas(Area $area): array
    {
        $ids = [];

        $area->loadMissing('subareas');

        foreach ($area->subareas as $subarea) {
            $ids[] = $subarea->id;
            $ids = array_merge($ids, $this->idsSubareas($subarea));
        }

        return $ids;
    }
}
