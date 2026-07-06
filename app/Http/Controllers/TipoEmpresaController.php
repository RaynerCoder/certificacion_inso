<?php

namespace App\Http\Controllers;

use App\Models\TipoEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoEmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('tipos_empresas.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tipos_empresas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_descripcion' => 'required|string|unique:tipos_empresas,descripcion',
            'form_estado'      => 'nullable|max:50'
        ]);

        try {

            DB::beginTransaction();

            TipoEmpresa::create([
                'descripcion' => $datos['form_descripcion'],
                'estado' => $datos['form_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'El tipo de empresa se ha registrado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('tipos_empresas_index')
                ->with('success', 'Tipo de empresa registrado exitosamente');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo registrar el tipo de empresa.')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoEmpresa $tipoEmpresa)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoEmpresa $tipoEmpresa)
    {
        return view('tipos_empresas.edit', compact('tipoEmpresa'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, TipoEmpresa $tipoEmpresa)
    {
        $datos = $solicitud->validate([
            'form_descripcion' => 'required|string|unique:tipos_empresas,descripcion,' . $tipoEmpresa->id,
            'form_estado'      => 'nullable|max:50'
        ]);

        try {

            DB::beginTransaction();

            $tipoEmpresa->update([
                'descripcion' => $datos['form_descripcion'],
                'estado'      => $datos['form_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'El tipo de empresa se ha actualizado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('tipos_empresas_index')
                ->with('success', 'Tipo de empresa actualizado exitosamente');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo actualizar el tipo de empresa.')
                ->withInput();
        }
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoEmpresa $tipoEmpresa)
    {
        try {

            DB::beginTransaction();

            if ($tipoEmpresa->empresas()->exists()) {

                session()->flash('swal', [
                    'title' => '¡Error!',
                    'text'  => 'No se puede eliminar el tipo de empresa porque tiene empresas relacionadas.',
                    'icon'  => 'error'
                ]);

                DB::rollBack();

                return redirect()
                    ->route('tipos_empresas_index');
            }

            $tipoEmpresa->delete();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'El tipo de empresa se ha eliminado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('tipos_empresas_index')
                ->with('success', 'Tipo de empresa eliminado exitosamente');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->route('tipos_empresas_index')
                ->with('error', 'No se pudo eliminar el tipo de empresa.');
        }
    }
}
