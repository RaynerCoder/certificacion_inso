<?php

namespace App\Http\Controllers;

use App\Models\TipoProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('tipos_productos.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tipos_productos.create');  
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $solicitud->validate([
            'form_descripcion' => 'required|string|max:1000',
            'form_codigo' => 'nullable|max:150|unique:tipos_productos,codigo',
            'form_estado' => 'nullable|max:50'
        ]);

        try {
            DB::beginTransaction();

            TipoProducto::create([
                'descripcion' => $solicitud->form_descripcion,
                'codigo' => $solicitud->form_codigo,
                'estado' => $solicitud->form_estado,
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'El tipo de producto se ha registrado correctamente.',
                'icon' => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('tipos_productos_index')
                ->with('success', 'Tipo de producto registrado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo registrar el tipo de producto.')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoProducto $tipoProducto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoProducto $tipoProducto)
    {
        $tipoProducto = TipoProducto::findOrFail($tipoProducto->id);
        return view('tipos_productos.edit', compact('tipoProducto'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, TipoProducto $tipoProducto)
    {
        $datos = $solicitud->validate([
            'form_descripcion' => 'required|string|max:1000',
            'form_codigo' => 'nullable|max:150|unique:tipos_productos,codigo,' . $tipoProducto->id,
            'form_estado' => 'nullable|max:50'
        ]);

        try {
            DB::beginTransaction();

            $tipoProducto->update([
                'descripcion' => $datos['form_descripcion'],
                'codigo' => $datos['form_codigo'],
                'estado' => $datos['form_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'El tipo de producto se ha actualizado correctamente.',
                'icon' => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('tipos_productos_index')
                ->with('success', 'Tipo de producto actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo actualizar el tipo de producto.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoProducto $tipoProducto)
    {
        try {
            DB::beginTransaction();

            if ($tipoProducto->productos()->exists()) {
                session()->flash('swal', [
                    'title' => '¡Error!',
                    'text' => 'No se puede eliminar el tipo de producto porque tiene productos relacionados.',
                    'icon' => 'error'
                ]);

                DB::rollBack();

                return redirect()->route('tipos_productos_index');
            }

            $tipoProducto->delete();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'El tipo de producto se ha eliminado correctamente.',
                'icon' => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('tipos_productos_index')
                ->with('success', 'Tipo de producto eliminado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('tipos_productos_index')
                ->with('error', 'No se pudo eliminar el tipo de producto.');
        }
    }
}
