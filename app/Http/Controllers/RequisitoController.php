<?php

namespace App\Http\Controllers;

use App\Models\Requisito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RequisitoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('requisitos.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_descripcion' => [
                'required',
                'string',
                Rule::unique('requisitos', 'descripcion')->whereNull('deleted_at'),
            ],
            'form_estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ]);

        try {
            DB::beginTransaction();

            Requisito::create([
                'descripcion' => $datos['form_descripcion'],
                'estado' => $datos['form_estado'],
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El requisito se registro correctamente.',
                'icon'  => 'success',
            ]);

            return redirect()->route('requisitos_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el requisito.')
                ->withInput()
                ->with('modal_requisito', 'crear');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Requisito $requisito)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Requisito $requisito)
    {
        // return redirect()
        //     ->route('requisitos_index')
        //     ->with('modal_requisito', 'editar')
        //     ->with('requisito_editar', $requisito->id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, Requisito $requisito)
    {
        $datos = $solicitud->validate([
            'form_descripcion' => [
                'required',
                'string',
                Rule::unique('requisitos', 'descripcion')
                    ->ignore($requisito->id)
                    ->whereNull('deleted_at'),
            ],
            'form_estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ]);

        if ($requisito->estado !== 'INACTIVO' && $datos['form_estado'] === 'INACTIVO') {
            $relacionesEncontradas = $this->relacionesQueImpidenInactivar($requisito);

            if ($relacionesEncontradas !== []) {
                session()->flash('swal', [
                    'title' => 'No se puede cambiar a Inactivo',
                    'text' => 'El requisito está relacionado con otros datos.',
                    'icon' => 'error',
                ]);

                return redirect()->route('requisitos_index');
            }
        }

        try {
            DB::beginTransaction();

            $requisito->update([
                'descripcion' => $datos['form_descripcion'],
                'estado' => $datos['form_estado'],
            ]);

            DB::commit();
            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El requisito se actualizo correctamente.',
                'icon'  => 'success',
            ]);
            return redirect()->route('requisitos_index');

        } 
        catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'No se pudo actualizar el requisito.')
                ->withInput()
                ->with('modal_requisito', 'editar')
                ->with('requisito_editar', $requisito->id);
        }
    }

    /**
     * Elimina lógicamente el requisito después de dejarlo Inactivo.
     */
    public function destroy(Requisito $requisito)
    {
        $relacionesEncontradas = $this->relacionesQueImpidenInactivar($requisito);

        if ($relacionesEncontradas !== []) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'El requisito está relacionado con otros datos.',
                'icon' => 'error',
            ]);

            return redirect()->route('requisitos_index');
        }

        try {
            DB::beginTransaction();

            $requisito->update(['estado' => 'INACTIVO']);
            $requisito->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El requisito se eliminó correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('requisitos_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('requisitos_index')
                ->with('error', 'No se pudo eliminar el requisito.');
        }
    }

    /**
     * Revisa las relaciones que todavía utilizan el requisito.
     */
    private function relacionesQueImpidenInactivar(Requisito $requisito): array
    {
        $relacionesEncontradas = [];

        if ($requisito->requisitoTiposCertificados()->withTrashed()->exists()) {
            $relacionesEncontradas[] = 'tipos de certificados';
        }

        if ($requisito->requisitoCertificados()->withTrashed()->exists()) {
            $relacionesEncontradas[] = 'certificados';
        }

        return $relacionesEncontradas;
    }
}
