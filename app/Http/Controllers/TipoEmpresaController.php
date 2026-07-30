<?php

namespace App\Http\Controllers;

use App\Models\TipoEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        return redirect()->route('tipos_empresas_index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarTipoEmpresa($solicitud);

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
        $datos = $this->validarTipoEmpresa($solicitud, $tipoEmpresa);

        if (
            $tipoEmpresa->estado !== 'INACTIVO'
            && $datos['form_estado'] === 'INACTIVO'
            && $this->tipoEmpresaEstaRelacionado($tipoEmpresa)
        ) {
            session()->flash('swal', [
                'title' => 'No se puede cambiar a Inactivo',
                'text' => 'El tipo de empresa está relacionado con otros datos.',
                'icon' => 'error',
            ]);

            return redirect()->route('tipos_empresas_index');
        }

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
     * Elimina lógicamente el tipo de empresa después de dejarlo Inactivo.
     */
    public function destroy(TipoEmpresa $tipoEmpresa)
    {
        if ($this->tipoEmpresaEstaRelacionado($tipoEmpresa)) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'El tipo de empresa está relacionado con otros datos.',
                'icon' => 'error',
            ]);

            return redirect()->route('tipos_empresas_index');
        }

        try {
            DB::beginTransaction();

            $tipoEmpresa->update(['estado' => 'INACTIVO']);
            $tipoEmpresa->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El tipo de empresa se eliminó correctamente.',
                'icon' => 'success',
            ]);

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

    /**
     * Valida los dos datos administrados por este catálogo.
     */
    private function validarTipoEmpresa(
        Request $solicitud,
        ?TipoEmpresa $tipoEmpresa = null
    ): array {
        return $solicitud->validate([
            'form_descripcion' => [
                'required',
                'string',
                Rule::unique('tipos_empresas', 'descripcion')
                    ->ignore($tipoEmpresa?->id)
                    ->whereNull('deleted_at'),
            ],
            'form_estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ], [], [
            'form_descripcion' => 'descripción',
            'form_estado' => 'estado',
        ]);
    }

    /**
     * Incluye empresas eliminadas lógicamente porque conservan esta relación.
     */
    private function tipoEmpresaEstaRelacionado(TipoEmpresa $tipoEmpresa): bool
    {
        return $tipoEmpresa->empresas()->withTrashed()->exists();
    }
}
