<?php

namespace App\Http\Controllers;

use App\Models\ClasificacionToxicologica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClasificacionToxicologicaController extends Controller
{
    public function index()
    {
        return view('clasificaciones_toxicologicas.index');
    }

    public function create()
    {
        return view('clasificaciones_toxicologicas.create');
    }

    public function store(Request $solicitud)
    {
        $datos = $this->validarClasificacionToxicologica($solicitud);

        try {
            DB::beginTransaction();

            ClasificacionToxicologica::create($this->datosClasificacionToxicologica($datos));

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'La clasificación toxicológica se ha registrado correctamente.',
                'icon' => 'success',
            ]);

            DB::commit();

            return redirect()
                ->route('clasificaciones_toxicologicas_index')
                ->with('success', 'Clasificación toxicológica registrada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo registrar la clasificación toxicológica.')
                ->withInput();
        }
    }

    public function show(ClasificacionToxicologica $clasificacionToxicologica)
    {
        return redirect()->route('clasificaciones_toxicologicas_index');
    }

    public function edit(ClasificacionToxicologica $clasificacionToxicologica)
    {
        return view('clasificaciones_toxicologicas.edit', compact('clasificacionToxicologica'));
    }

    public function update(Request $solicitud, ClasificacionToxicologica $clasificacionToxicologica)
    {
        $datos = $this->validarClasificacionToxicologica($solicitud, $clasificacionToxicologica);

        try {
            DB::beginTransaction();

            $clasificacionToxicologica->update($this->datosClasificacionToxicologica($datos));

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'La clasificación toxicológica se ha actualizado correctamente.',
                'icon' => 'success',
            ]);

            DB::commit();

            return redirect()
                ->route('clasificaciones_toxicologicas_index')
                ->with('success', 'Clasificación toxicológica actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'No se pudo actualizar la clasificación toxicológica.')
                ->withInput();
        }
    }

    public function destroy(ClasificacionToxicologica $clasificacionToxicologica)
    {
        try {
            DB::beginTransaction();

            if ($clasificacionToxicologica->productos()->exists()) {
                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text' => 'La clasificación toxicológica tiene productos relacionados.',
                    'icon' => 'error',
                ]);

                DB::rollBack();

                return redirect()->route('clasificaciones_toxicologicas_index');
            }

            $clasificacionToxicologica->delete();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'La clasificación toxicológica se ha eliminado correctamente.',
                'icon' => 'success',
            ]);

            DB::commit();

            return redirect()
                ->route('clasificaciones_toxicologicas_index')
                ->with('success', 'Clasificación toxicológica eliminada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('clasificaciones_toxicologicas_index')
                ->with('error', 'No se pudo eliminar la clasificación toxicológica.');
        }
    }

    private function validarClasificacionToxicologica(
        Request $solicitud,
        ?ClasificacionToxicologica $clasificacionToxicologica = null,
    ): array
    {
        return $solicitud->validate([
            'form_descripcion' => 'required|string|max:1000',
            'form_codigo' => [
                'nullable',
                'max:150',
                Rule::unique((new ClasificacionToxicologica())->getTable(), 'codigo')
                    ->ignore($clasificacionToxicologica?->id),
            ],
            'form_estado' => 'nullable|max:50',
        ]);
    }

    private function datosClasificacionToxicologica(array $datos): array
    {
        return [
            'descripcion' => $datos['form_descripcion'],
            'codigo' => $datos['form_codigo'] ?? null,
            'estado' => $datos['form_estado'] ?? 'ACTIVO',
        ];
    }
}
