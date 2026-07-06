<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Territorio;
use App\Models\TipoEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('empresas.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tiposEmpresas = TipoEmpresa::all();
        $territorios = Territorio::all();
        return view('empresas.create', [
            'tiposEmpresas' => $tiposEmpresas,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_id_tipo_empresa'   => 'required|exists:tipos_empresas,id',
            'form_razon_social'      => 'required|string|max:255',
            'form_matricula'         => 'required|string|max:50',
            'form_latitud'           => 'nullable|string',
            'form_longitud'          => 'nullable|string',
            'form_estado'            => 'nullable|string|max:50',
        ]);


        try {

            DB::beginTransaction();

            Empresa::create([
                //'id_persona'         => $datos['form_id_persona'],
                'id_tipo_empresa'    => $datos['form_id_tipo_empresa'],
                'razon_social'       => $datos['form_razon_social'],
                'matricula'          => $datos['form_matricula'],
                'latitud'            => $datos['form_latitud'],
                'longitud'           => $datos['form_longitud'],
                'estado'             => $datos['form_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'La empresa se ha registrado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('empresas_index')
                ->with('success', 'Empresa registrada exitosamente');

        } catch (\Exception $e) {

            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'No se pudo registrar la empresa.')
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function buscar(Empresa $empresa)
    {
        dd($empresa);
        return response()->json([
            'id_tipo_empresa' => $empresa->id_tipo_empresa,
            'razon_social'    => $empresa->razon_social,
            'matricula'       => $empresa->matricula,
            'latitud'         => $empresa->latitud,
            'longitud'        => $empresa->longitud,
            'estado'          => $empresa->estado,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empresa $empresa)
    {
        $tiposEmpresas = TipoEmpresa::all();
        $territorios = Territorio::all();
        return view('empresas.edit', [
            'tiposEmpresas' => $tiposEmpresas,
            'territorios' => $territorios,
            'empresa' => $empresa
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, Empresa $empresa)
    {
        $data = $solicitud->validate([
            'form_id_tipo_empresa'   => 'required|exists:tipos_empresas,id',
            'form_razon_social'      => 'required|string|max:255',
            'form_matricula'         => 'required|string|max:50',
            'form_latitud'           => 'nullable|string',
            'form_longitud'          => 'nullable|string',
            'form_estado'            => 'nullable|string|max:50',
        ]);

        try {

            DB::beginTransaction();

            $empresa->update([
                'id_tipo_empresa'   => $data['form_id_tipo_empresa'],
                'razon_social'      => $data['form_razon_social'],
                'matricula'         => $data['form_matricula'],
                'latitud'           => $data['form_latitud'],
                'longitud'          => $data['form_longitud'],
                'estado'            => $data['form_estado'],
            ]);

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'La empresa se ha actualizado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('empresas_index')
                ->with('success', 'Empresa actualizada exitosamente');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo actualizar la empresa.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empresa $empresa)
    {
        try {

            DB::beginTransaction();

            if ($empresa->responsables()->exists()) {
                session()->flash('swal', [
                    'title' => '¡Error!',
                    'text'  => 'No se puede eliminar la empresa porque tiene registros relacionados.',
                    'icon'  => 'error'
                ]);
            
                DB::rollBack();
            
                return redirect()->route('empresas_index');
            }

            $empresa->delete();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => 'La empresa se ha eliminado correctamente.',
                'icon'  => 'success'
            ]);

            DB::commit();

            return redirect()
                ->route('empresas_index')
                ->with('success', 'Empresa eliminada exitosamente');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->route('empresas_index')
                ->with('error', 'No se pudo eliminar la empresa.');
        }
    }
}
