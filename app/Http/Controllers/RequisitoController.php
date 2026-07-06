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
        $requisitos = Requisito::all();
        return view('requisitos.index', compact('requisitos'));
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
            'form_descripcion' => ['required','string',
                Rule::unique('requisitos', 'descripcion')->whereNull('deleted_at'),],
            'form_estado' => 'string|max:50',
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
        $datos = $solicitud->validate(['form_descripcion' => ['required','string',
                Rule::unique('requisitos', 'descripcion')
                    ->ignore($requisito->id)
                    ->whereNull('deleted_at'),
            ],
            'form_estado' => 'string|max:50',
        ]);

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
     * Remove the specified resource from storage.
     */
    public function destroy(Requisito $requisito)
    {
        try {
            DB::beginTransaction();

            // Evita eliminar requisitos que ya se usan en certificados.
            if ($requisito->certificados()->exists() || $requisito->tiposCertificados()->exists()) {
                DB::rollBack();

                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text'  => 'Este requisito tiene certificados relacionados.',
                    'icon'  => 'error',
                ]);

                return redirect()->route('requisitos_index');
            }

            $requisito->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El requisito se elimino correctamente.',
                'icon'  => 'success',
            ]);

            return redirect()->route('requisitos_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('requisitos_index')
                ->with('error', 'No se pudo eliminar el requisito.');
        }
    }
}
