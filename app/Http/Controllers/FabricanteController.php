<?php

namespace App\Http\Controllers;

use App\Models\Fabricante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FabricanteController extends Controller
{
    public function index()
    {
        return view('fabricantes.index');
    }

    public function create()
    {
        return view('fabricantes.create');
    }

    public function store(Request $solicitud)
    {
        $datos = $this->validarFabricante($solicitud);

        try {
            DB::beginTransaction();

            Fabricante::create($this->datosFabricante($datos));

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El fabricante se ha registrado correctamente.',
                'icon' => 'success',
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

    public function show(Fabricante $fabricante)
    {
        return redirect()->route('fabricantes_index');
    }

    public function edit(Fabricante $fabricante)
    {
        return view('fabricantes.edit', ['fabricante' => $fabricante]);
    }

    public function update(Request $solicitud, Fabricante $fabricante)
    {
        $datos = $this->validarFabricante($solicitud, $fabricante);

        try {
            DB::beginTransaction();

            $fabricante->update($this->datosFabricante($datos));

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El fabricante se ha actualizado correctamente.',
                'icon' => 'success',
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

    public function destroy(Fabricante $fabricante)
    {
        try {
            DB::beginTransaction();

            if ($fabricante->productos()->exists()) {
                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text' => 'El fabricante tiene productos relacionados.',
                    'icon' => 'error',
                ]);

                DB::rollBack();

                return redirect()->route('fabricantes_index');
            }

            $fabricante->delete();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El fabricante se ha eliminado correctamente.',
                'icon' => 'success',
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

    private function validarFabricante(Request $solicitud, ?Fabricante $fabricante = null): array
    {
        return $solicitud->validate([
            'form_nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fabricantes', 'nombre')->ignore($fabricante?->id),
            ],
            'form_descripcion' => 'nullable|string',
            'form_razon_social' => 'nullable|string|max:255',
            'form_estado' => 'nullable|max:50',
        ]);
    }

    private function datosFabricante(array $datos): array
    {
        return [
            'nombre' => $datos['form_nombre'],
            'descripcion' => $datos['form_descripcion'] ?? null,
            'razon_social' => $datos['form_razon_social'] ?? null,
            'estado' => $datos['form_estado'] ?? 'ACTIVO',
        ];
    }
}
