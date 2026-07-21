<?php

namespace App\Http\Controllers;

use App\Models\Territorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TerritorioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // La lista se usa en los select de territorio superior de ambos modales.
        $territorios = Territorio::query()->orderBy('nombre')->get();

        return view('territorios.index', compact('territorios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_id_padre_territorio' => 'nullable|exists:territorios,id',
            'form_nombre'              => 'required|string|max:255',
            'form_codigo'              => 'nullable|max:150|unique:territorios,codigo',
            // Estados manejados por ahora: ACTIVO e INACTIVO.
            'form_id_estado'           => 'required|in:ACTIVO,INACTIVO',
        ]);

        try {
            DB::beginTransaction();

            Territorio::create([
                'id_padre_territorio' => $datos['form_id_padre_territorio'] ?? null,
                'nombre'              => $datos['form_nombre'],
                'codigo'              => $datos['form_codigo'],
                'estado'              => $datos['form_id_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'El territorio se ha registrado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()->route('territorios_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo registrar el territorio.')
                ->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, Territorio $territorio)
    {
        $datos = $solicitud->validate([
            // Puede venir vacio (sin territorio padre), el ID debe existir en la tabla territorios y evita que el territorio sea su propio padre
            'form_id_padre_territorio' => 'nullable|exists:territorios,id|not_in:' . $territorio->id,
            'form_nombre' => 'required|string|max:255|unique:territorios,nombre,' . $territorio->id,
            'form_codigo'              => 'nullable|max:150|unique:territorios,codigo,' . $territorio->id,
            // Estados manejados por ahora: ACTIVO e INACTIVO.
            'form_id_estado'           => 'required|in:ACTIVO,INACTIVO',
        ]);

        try {

            DB::beginTransaction();

            $territorio->update([
                'id_padre_territorio' => $datos['form_id_padre_territorio'] ?? null,
                'nombre'              => $datos['form_nombre'],
                'codigo'              => $datos['form_codigo'],
                'estado'              => $datos['form_id_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'El territorio se ha actualizado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('territorios_index')
                ->with('success', 'Territorio actualizado exitosamente');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo actualizar el territorio.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Territorio $territorio)
    {
        // Verifica si tiene territorios hijos relacionados
        if ($territorio->territoriosHijos()->exists()) {
            session()->flash('swal', [
                'title' => '¡Error!',
                'text'  => 'No se puede eliminar el territorio porque tiene territorios relacionados.',
                'icon'  => 'error'
            ]);
            return redirect()->route('territorios_index');
        }

        $territorio->delete();

        session()->flash('swal', [
            'title' => '¡Bien hecho!',
            'text'  => 'El territorio se ha eliminado correctamente.',
            'icon'  => 'success'
        ]);

        return redirect()
            ->route('territorios_index')
            ->with('success', 'Territorio eliminado exitosamente');
    }
}
