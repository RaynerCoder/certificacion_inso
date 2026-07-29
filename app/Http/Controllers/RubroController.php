<?php

namespace App\Http\Controllers;

use App\Models\Rubro;
use Illuminate\Http\Request;

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
     * El catalogo CAEB es normativo y se actualiza unicamente mediante su SQL oficial.
     */
    public function store(Request $solicitud)
    {
        return redirect()->route('rubros_index')
            ->with('error', 'El catálogo de rubros CAEB no permite registros manuales.');
    }

    /**
     * La edicion individual todavia no tiene vista propia.
     */
    public function edit(Rubro $rubro)
    {
        return redirect()->route('rubros_index');
    }

    /**
     * Los nombres y codigos oficiales no se modifican desde el sistema.
     */
    public function update(Request $solicitud, Rubro $rubro)
    {
        return redirect()->route('rubros_index')
            ->with('error', 'El catálogo de rubros CAEB no permite modificaciones manuales.');
    }

    /**
     * La baja manual se bloquea para conservar el catalogo normativo completo.
     */
    public function destroy(Rubro $rubro)
    {
        return redirect()->route('rubros_index')
            ->with('error', 'El catálogo de rubros CAEB no permite eliminaciones manuales.');
    }
}
