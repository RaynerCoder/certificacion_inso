<?php

namespace App\Http\Controllers;

use App\Models\Ambito;
use App\Models\Territorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TerritorioController extends Controller
{
    /**
     * Muestra el listado y carga los datos usados por los formularios.
     */
    public function index()
    {
        $territorios = Territorio::query()->orderBy('nombre')->get();
        $ambitos = Ambito::query()->orderBy('nombre')->get();

        return view('territorios.index', compact('territorios', 'ambitos'));
    }

    /**
     * Registra un territorio desde el modal del listado.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_id_ambito' => 'required|exists:ambitos,id',
            'form_id_padre_territorio' => 'nullable|exists:territorios,id',
            'form_nombre' => 'required|string|max:255',
            'form_codigo' => 'nullable|max:150|unique:territorios,codigo',
            'form_id_estado' => 'required|in:ACTIVO,INACTIVO',
        ]);

        try {
            DB::beginTransaction();

            Territorio::create([
                'id_ambito' => $datos['form_id_ambito'],
                'id_padre_territorio' => $datos['form_id_padre_territorio'] ?? null,
                'nombre' => $datos['form_nombre'],
                'codigo' => $datos['form_codigo'] ?? null,
                'estado' => $datos['form_id_estado'],
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El territorio se registró correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('territorios_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el territorio.')
                ->withInput();
        }
    }

    /**
     * Devuelve los datos que necesita el modal de edición.
     */
    public function edit(Territorio $territorio)
    {
        return response()->json([
            'id' => $territorio->id,
            'id_ambito' => $territorio->id_ambito,
            'id_padre_territorio' => $territorio->id_padre_territorio,
            'nombre' => $territorio->nombre,
            'codigo' => $territorio->codigo,
            'estado' => $territorio->estado,
        ]);
    }

    /**
     * Actualiza el territorio seleccionado desde el modal de edición.
     */
    public function update(Request $solicitud, Territorio $territorio)
    {
        $datos = $solicitud->validate([
            'form_id_ambito' => 'required|exists:ambitos,id',
            'form_id_padre_territorio' => 'nullable|exists:territorios,id|not_in:' . $territorio->id,
            'form_nombre' => 'required|string|max:255|unique:territorios,nombre,' . $territorio->id,
            'form_codigo' => 'nullable|max:150|unique:territorios,codigo,' . $territorio->id,
            'form_id_estado' => 'required|in:ACTIVO,INACTIVO',
        ]);

        if ($territorio->estado !== 'INACTIVO' && $datos['form_id_estado'] === 'INACTIVO') {
            $relacionesEncontradas = $this->relacionesQueImpidenInactivar($territorio);

            if ($relacionesEncontradas !== []) {
                session()->flash('swal', [
                    'title' => 'No se puede cambiar a Inactivo',
                    'text' => 'El territorio está relacionado con otros datos.',
                    'icon' => 'error',
                ]);

                return redirect()->route('territorios_index');
            }
        }

        try {
            DB::beginTransaction();

            // fill permite distinguir una edición real de un envío sin cambios.
            $territorio->fill([
                'id_ambito' => $datos['form_id_ambito'],
                'id_padre_territorio' => $datos['form_id_padre_territorio'] ?? null,
                'nombre' => $datos['form_nombre'],
                'codigo' => $datos['form_codigo'] ?? null,
                'estado' => $datos['form_id_estado'],
            ]);

            if (!$territorio->isDirty()) {
                DB::commit();

                session()->flash('swal', [
                    'title' => 'Sin cambios',
                    'text' => 'No se detectaron cambios en el territorio.',
                    'icon' => 'info',
                ]);

                return redirect()->route('territorios_index');
            }

            $territorio->save();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => 'El territorio se actualizó correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('territorios_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el territorio.')
                ->withInput();
        }
    }

    /**
     * Inactiva el territorio sin eliminarlo de la base de datos.
     */
    public function destroy(Territorio $territorio)
    {
        if ($territorio->estado === 'INACTIVO') {
            session()->flash('swal', [
                'title' => 'Sin cambios',
                'text' => 'El territorio ya tiene estado Inactivo.',
                'icon' => 'info',
            ]);

            return redirect()->route('territorios_index');
        }

        $relacionesEncontradas = $this->relacionesQueImpidenInactivar($territorio);

        if ($relacionesEncontradas !== []) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'El territorio está relacionado con otros datos.',
                'icon' => 'error',
            ]);

            return redirect()->route('territorios_index');
        }

        try {
            DB::beginTransaction();

            $territorio->update(['estado' => 'INACTIVO']);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El estado del territorio cambió a Inactivo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('territorios_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('territorios_index')
                ->with('error', 'No se pudo eliminar el territorio.');
        }
    }

    /**
     * Revisa las relaciones que todavía utilizan el territorio.
     */
    private function relacionesQueImpidenInactivar(Territorio $territorio): array
    {
        $relacionesEncontradas = [];

        if ($territorio->territoriosHijos()->withTrashed()->exists()) {
            $relacionesEncontradas[] = 'territorios dependientes';
        }

        if ($territorio->personas()->withTrashed()->exists()) {
            $relacionesEncontradas[] = 'personas o empresas';
        }

        if ($territorio->productos()->withTrashed()->exists()) {
            $relacionesEncontradas[] = 'productos';
        }

        return $relacionesEncontradas;
    }
}
