<?php

namespace App\Http\Controllers;

use App\Models\ClasificacionToxicologica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'form_codigo' => [
                'required',
                'string',
                'max:150',
                function (string $atributo, mixed $valor, \Closure $fallar) use ($solicitud, $clasificacionToxicologica) {
                    $codigo = trim((string) $valor);
                    $descripcion = trim((string) $solicitud->input('form_descripcion', ''));

                    $consulta = ClasificacionToxicologica::withTrashed()->where('codigo', $codigo);

                    if ($clasificacionToxicologica) {
                        $consulta->whereKeyNot($clasificacionToxicologica->id);
                    }

                    $existente = $consulta->first();

                    if (!$existente) {
                        return;
                    }

                    $descripcionExistente = trim((string) $existente->descripcion);

                    if (strcasecmp($descripcionExistente, $descripcion) === 0) {
                        $fallar('Ya existe una clasificación toxicológica con el mismo código y descripción.');
                        return;
                    }

                    // El código identifica de forma única a cada clasificación del catálogo.
                    $fallar('El código ya está registrado en otra clasificación toxicológica.');
                },
            ],
            'form_descripcion' => 'nullable|string|max:1000',
            'form_estado' => 'nullable|max:50',
        ]);
    }

    private function datosClasificacionToxicologica(array $datos): array
    {
        $descripcion = trim((string) ($datos['form_descripcion'] ?? ''));

        return [
            'codigo' => trim($datos['form_codigo']),
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'estado' => $datos['form_estado'] ?? 'ACTIVO',
        ];
    }
}
