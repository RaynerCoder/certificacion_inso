<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredienteController extends Controller
{
    public function index()
    {
        return view('ingredientes.index');
    }

    public function create()
    {
        return view('ingredientes.create');
    }

    public function store(Request $request)
    {
        $datos = $this->validarIngrediente($request);

        try {
            DB::beginTransaction();

            Ingrediente::create($this->datosIngrediente($datos));

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El ingrediente se ha registrado correctamente.',
                'icon' => 'success',
            ]);

            DB::commit();

            return redirect()->route('ingredientes_index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo registrar el ingrediente.');
        }
    }

    public function show(Ingrediente $ingrediente)
    {
        return redirect()->route('ingredientes_index');
    }

    public function edit(Ingrediente $ingrediente)
    {
        return view('ingredientes.edit', ['ingrediente' => $ingrediente]);
    }

    public function update(Request $request, Ingrediente $ingrediente)
    {
        $datos = $this->validarIngrediente($request, $ingrediente);

        try {
            DB::beginTransaction();

            $ingrediente->update($this->datosIngrediente($datos));

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El ingrediente se ha actualizado correctamente.',
                'icon' => 'success',
            ]);

            DB::commit();

            return redirect()->route('ingredientes_index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se pudo actualizar el ingrediente.');
        }
    }

    public function destroy(Ingrediente $ingrediente)
    {
        try {
            DB::beginTransaction();

            if ($ingrediente->ingredienteProductos()->exists()) {
                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text' => 'El ingrediente tiene productos relacionados.',
                    'icon' => 'error',
                ]);

                DB::rollBack();

                return redirect()->route('ingredientes_index');
            }

            $ingrediente->delete();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El ingrediente se ha eliminado correctamente.',
                'icon' => 'success',
            ]);

            DB::commit();

            return redirect()->route('ingredientes_index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('ingredientes_index')
                ->with('error', 'No se pudo eliminar el ingrediente.');
        }
    }

    private function validarIngrediente(Request $request, ?Ingrediente $ingrediente = null): array
    {
        // Se limpia el nombre antes de compararlo y guardarlo.
        $request->merge([
            'form_nombre' => trim((string) $request->input('form_nombre')),
        ]);

        return $request->validate([
            'form_nombre' => [
                'required',
                'string',
                'max:255',
                function (string $atributo, mixed $valor, \Closure $fallar) use ($ingrediente) {
                    $consulta = Ingrediente::withTrashed()
                        ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim((string) $valor))]);

                    if ($ingrediente) {
                        $consulta->whereKeyNot($ingrediente->id);
                    }

                    if ($consulta->exists()) {
                        $fallar('Ya existe un ingrediente con el mismo nombre.');
                    }
                },
            ],
            'form_composicion' => ['nullable', 'string', 'max:255'],
            'form_riesgo_salud' => ['nullable', 'string', 'max:255'],
            'form_estado' => ['nullable', 'in:ACTIVO,INACTIVO'],
        ]);
    }

    private function datosIngrediente(array $datos): array
    {
        return [
            'nombre' => trim($datos['form_nombre']),
            'composicion' => $datos['form_composicion'] ?? null,
            'riesgo_salud' => $datos['form_riesgo_salud'] ?? null,
            'estado' => $datos['form_estado'] ?? 'ACTIVO',
        ];
    }
}
