<?php

namespace App\Http\Controllers;

use App\Models\Fabricante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FabricanteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('fabricantes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fabricantes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_nombre'        => 'required|string|max:255|unique:fabricantes,nombre',
            'form_descripcion'   => 'nullable|string',
            'form_razon_social'  => 'nullable|string|max:255',
            'form_estado'        => 'nullable|max:50'
        ]);

        try {
            DB::beginTransaction();

            Fabricante::create([
                'nombre'        => $datos['form_nombre'],
                'descripcion'   => $datos['form_descripcion'],
                'razon_social'  => $datos['form_razon_social'],
                'estado'        => $datos['form_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'El fabricante se ha registrado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('fabricantes_index')
                ->with('success', 'Fabricante registrado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo registrar el fabricante.')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Fabricante $fabricante)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fabricante $fabricante)
    {
        return view('fabricantes.edit', ['fabricante' => $fabricante]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fabricante $fabricante)
    {
        $datos = $request->validate([
            'form_nombre'       => 'required|string|max:255|unique:fabricantes,nombre,' . $fabricante->id,
            'form_descripcion'  => 'nullable|string',
            'form_razon_social' => 'nullable|string|max:255',
            'form_estado'       => 'nullable|max:50'
        ]);

        try {
            DB::beginTransaction();

            $fabricante->update([
                'nombre'       => $datos['form_nombre'],
                'descripcion'  => $datos['form_descripcion'],
                'razon_social' => $datos['form_razon_social'],
                'estado'       => $datos['form_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'El fabricante se ha actualizado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('fabricantes_index')
                ->with('success', 'Fabricante actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo actualizar el fabricante.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fabricante $fabricante)
    {
        try {
            DB::beginTransaction();

            if ($fabricante->productos()->exists()) {
                session()->flash('swal', [
                    'title' => '¡Error!',
                    'text'  => 'No se puede eliminar el fabricante porque tiene registros relacionados.',
                    'icon'  => 'error'
                ]);

                DB::rollBack();

                return redirect()->route('fabricantes_index');
            }

            $fabricante->delete();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'El fabricante se ha eliminado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('fabricantes_index')
                ->with('success', 'Fabricante eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('fabricantes_index')
                ->with('error', 'No se pudo eliminar el fabricante.');
        }
    }
}
