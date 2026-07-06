<?php

namespace App\Http\Controllers;

use App\Models\Rubro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RubroController extends Controller
{
    /**
     * Muestra el listado de rubros existente.
     */
    public function index()
    {
        return view('rubros.index');
    }

    /**
     * El modulo de rubros se administra dentro de Personas por ahora.
     */
    public function create()
    {
        return redirect()->route('rubros_index');
    }

    /**
     * Guarda un rubro si llega desde un formulario simple.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_id_persona' => ['required', 'exists:personas,id'],
            'form_nombre' => ['required', 'string', 'max:255'],
            'form_estado' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            DB::beginTransaction();

            Rubro::create([
                'id_persona' => $datos['form_id_persona'],
                'nombre' => $datos['form_nombre'],
                'estado' => $datos['form_estado'] ?? 'ACTIVO',
            ]);

            DB::commit();

            return redirect()->route('rubros_index')->with('success', 'Rubro registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo registrar el rubro.')->withInput();
        }
    }

    /**
     * La edicion individual todavia no tiene vista propia.
     */
    public function edit(Rubro $rubro)
    {
        return redirect()->route('rubros_index');
    }

    /**
     * Actualiza campos basicos si se reutiliza el endpoint.
     */
    public function update(Request $solicitud, Rubro $rubro)
    {
        $datos = $solicitud->validate([
            'form_nombre' => ['required', 'string', 'max:255'],
            'form_estado' => ['nullable', 'string', 'max:50'],
        ]);

        $rubro->update([
            'nombre' => $datos['form_nombre'],
            'estado' => $datos['form_estado'] ?? $rubro->estado,
        ]);

        return redirect()->route('rubros_index')->with('success', 'Rubro actualizado correctamente.');
    }

    /**
     * Elimina el rubro con soft delete para mantener historial.
     */
    public function destroy(Rubro $rubro)
    {
        $rubro->delete();

        return redirect()->route('rubros_index')->with('success', 'Rubro eliminado correctamente.');
    }
}
