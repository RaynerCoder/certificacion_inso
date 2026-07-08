<?php

namespace App\Http\Controllers;

use App\Models\TipoProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TipoProductoController extends Controller
{
    public function index()
    {
        return view('tipos_productos.index');
    }

    public function create()
    {
        return view('tipos_productos.create');
    }

    public function store(Request $solicitud)
    {
        $datos = $this->validarTipoProducto($solicitud);

        try {
            DB::beginTransaction();

            TipoProducto::create($this->datosTipoProducto($datos));

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El tipo de producto se ha registrado correctamente.',
                'icon' => 'success',
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

    public function show(TipoProducto $tipoProducto)
    {
        return redirect()->route('tipos_productos_index');
    }

    public function edit(TipoProducto $tipoProducto)
    {
        return view('tipos_productos.edit', compact('tipoProducto'));
    }

    public function update(Request $solicitud, TipoProducto $tipoProducto)
    {
        $datos = $this->validarTipoProducto($solicitud, $tipoProducto);

        try {
            DB::beginTransaction();

            $tipoProducto->update($this->datosTipoProducto($datos));

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El tipo de producto se ha actualizado correctamente.',
                'icon' => 'success',
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

    public function destroy(TipoProducto $tipoProducto)
    {
        try {
            DB::beginTransaction();

            if ($tipoProducto->productos()->exists()) {
                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text' => 'El tipo de producto tiene productos relacionados.',
                    'icon' => 'error',
                ]);

                DB::rollBack();

                return redirect()->route('tipos_productos_index');
            }

            $tipoProducto->delete();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El tipo de producto se ha eliminado correctamente.',
                'icon' => 'success',
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

    private function validarTipoProducto(Request $solicitud, ?TipoProducto $tipoProducto = null): array
    {
        return $solicitud->validate([
            'form_descripcion' => 'required|string|max:1000',
            'form_codigo' => [
                'nullable',
                'max:150',
                Rule::unique('tipos_productos', 'codigo')->ignore($tipoProducto?->id),
            ],
            'form_estado' => 'nullable|max:50',
        ]);
    }

    private function datosTipoProducto(array $datos): array
    {
        return [
            'descripcion' => $datos['form_descripcion'],
            'codigo' => $datos['form_codigo'] ?? null,
            'estado' => $datos['form_estado'] ?? 'ACTIVO',
        ];
    }
}
