<?php

namespace App\Http\Controllers;

use App\Models\TipoEvidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TipoEvidenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiposEvidencias = TipoEvidencia::all();
        return view('tipos_evidencias.index', compact('tiposEvidencias'));
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
            'form_codigo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tipos_evidencias', 'codigo')->whereNull('deleted_at'),
            ],
            'form_nombre' => 'required|string|max:255',
            'form_descripcion' => 'nullable|string',
            'form_tamanio_maximo_mb' => 'required|integer|min:0|max:100',
            'form_estado' => 'string|max:50',
        ]);

        try {
            DB::beginTransaction();

            TipoEvidencia::create([
                'codigo' => $datos['form_codigo'],
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'tamanio_maximo_mb' => $datos['form_tamanio_maximo_mb'],
                'estado' => $datos['form_estado'],
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El tipo de evidencia se registro correctamente.',
                'icon'  => 'success',
            ]);

            return redirect()->route('tipos_evidencias_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el tipo de evidencia.')
                ->withInput()
                ->with('modal_tipo_evidencia', 'crear');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoEvidencia $tipoEvidencia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoEvidencia $tipoEvidencia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, TipoEvidencia $tipoEvidencia)
    {
        $datos = $solicitud->validate([
            'form_codigo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tipos_evidencias', 'codigo')
                    ->ignore($tipoEvidencia->id)
                    ->whereNull('deleted_at'),
            ],
            'form_nombre' => 'required|string|max:255',
            'form_descripcion' => 'nullable|string',
            'form_tamanio_maximo_mb' => 'required|integer|min:0|max:100',
            'form_estado' => 'string|max:50',
        ]);

        try {
            DB::beginTransaction();

            $tipoEvidencia->update([
                'codigo' => $datos['form_codigo'],
                'nombre' => $datos['form_nombre'],
                'descripcion' => $datos['form_descripcion'] ?? null,
                'tamanio_maximo_mb' => $datos['form_tamanio_maximo_mb'],
                'estado' => $datos['form_estado'],
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El tipo de evidencia se actualizo correctamente.',
                'icon'  => 'success',
            ]);

            return redirect()->route('tipos_evidencias_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el tipo de evidencia.')
                ->withInput()
                ->with('modal_tipo_evidencia', 'editar')
                ->with('tipo_evidencia_editar', $tipoEvidencia->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoEvidencia $tipoEvidencia)
    {
        try {
            DB::beginTransaction();

            // Evita eliminar tipos de evidencias que ya se usan en requisitos o tramites.
            if ($tipoEvidencia->requisitoTiposCertificados()->exists() || $tipoEvidencia->evidenciasRequisitos()->exists()) {
                DB::rollBack();

                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text'  => 'Este tipo de evidencia tiene registros relacionados.',
                    'icon'  => 'error',
                ]);

                return redirect()->route('tipos_evidencias_index');
            }

            $tipoEvidencia->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El tipo de evidencia se elimino correctamente.',
                'icon'  => 'success',
            ]);

            return redirect()->route('tipos_evidencias_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('tipos_evidencias_index')
                ->with('error', 'No se pudo eliminar el tipo de evidencia.');
        }
    }
}
