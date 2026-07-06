

// ==========================================
// UPDATE PERSONA NATURAL
// ==========================================
public function updateNatural(Request $request, Persona $persona)
{
    $datos = $request->validate([

        // PERSONA
        'form_domicilio'     => 'nullable|string|max:255',
        'form_nit'           => 'nullable|string|max:50',
        'form_correo'        => 'required|email|max:50|unique:personas,correo,' . $persona->id,
        'form_id_territorio' => 'required|exists:territorios,id',
        'form_estado'        => 'nullable|string|max:50',

        // NATURAL
        'form_ci'               => 'required|string|max:50',
        'form_complemento'      => 'nullable|string|max:10',
        'form_expedido'         => 'nullable|string|max:10',
        'form_nombres'          => 'required|string|max:100',
        'form_apellido_paterno' => 'required|string|max:100',
        'form_apellido_materno' => 'nullable|string|max:100',
        'form_apellido_casado'  => 'nullable|string|max:100',
        'form_fecha_nacimiento' => 'nullable|date',
        'form_genero'           => 'required',
        'form_ocupacion'        => 'nullable|string|max:255',

        // TELEFONOS
        'telefonos' => 'nullable|array',

        // RUBROS
        'rubros' => 'nullable|array',
    ]);

    DB::beginTransaction();

    try {

        // ==========================================
        // PERSONA
        // ==========================================
        $persona->update([
            'domicilio'     => $datos['form_domicilio'] ?? null,
            'nit'           => $datos['form_nit'] ?? null,
            'correo'        => $datos['form_correo'],
            'id_territorio' => $datos['form_id_territorio'],
            'estado'        => $datos['form_estado'] ?? 1,
        ]);

        // ==========================================
        // NATURAL
        // ==========================================
        Natural::updateOrCreate(
            ['id_persona' => $persona->id],
            [
                'ci'               => $datos['form_ci'],
                'complemento'      => $datos['form_complemento'] ?? null,
                'expedido'         => $datos['form_expedido'] ?? null,
                'nombres'          => $datos['form_nombres'],
                'apellido_paterno' => $datos['form_apellido_paterno'],
                'apellido_materno' => $datos['form_apellido_materno'] ?? null,
                'apellido_casado'  => $datos['form_apellido_casado'] ?? null,
                'fecha_nacimiento' => $datos['form_fecha_nacimiento'] ?? null,
                'genero'           => $datos['form_genero'],
                'ocupacion'        => $datos['form_ocupacion'] ?? null,
            ]
        );

        // ==========================================
        // TELEFONOS
        // ==========================================
        Telefono::where('id_persona', $persona->id)->delete();

        if (!empty($datos['telefonos'])) {

            foreach ($datos['telefonos'] as $telefono) {

                Telefono::create([
                    'id_persona' => $persona->id,
                    'numero'     => $telefono['numero'],
                    'estado'     => $telefono['tipo'] ?? 'CELULAR',
                ]);

            }

        }

        // ==========================================
        // RUBROS
        // ==========================================
        Rubro::where('id_persona', $persona->id)->delete();

        if (!empty($datos['rubros'])) {

            foreach ($datos['rubros'] as $rubro) {

                Rubro::create([
                    'id_persona' => $persona->id,
                    'nombre'     => $rubro['nombre'],
                    'estado'     => $rubro['estado'] ?? 'ACTIVO',
                ]);

            }

        }

        DB::commit();

        return redirect()->route('personas_index');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()
            ->withInput()
            ->with('error', $e->getMessage());
    }
}


// ==========================================
// UPDATE EMPRESA
// ==========================================
public function updateEmpresa(Request $request, Persona $persona)
{
    $datos = $request->validate([

        // PERSONA
        'form_domicilio'     => 'nullable|string|max:255',
        'form_nit'           => 'nullable|string|max:50',
        'form_correo'        => 'required|email|max:50|unique:personas,correo,' . $persona->id,
        'form_id_territorio' => 'required|exists:territorios,id',
        'form_estado'        => 'nullable|string|max:50',

        // EMPRESA
        'form_id_tipo_empresa'       => 'required|exists:tipos_empresas,id',
        'form_razon_social'          => 'required|string|max:255',
        'form_matricula'             => 'required|string|max:50',
        'form_latitud'               => 'nullable',
        'form_longitud'              => 'nullable',
        'form_id_territorio_empresa' => 'required|exists:territorios,id',
        'form_estado_empresa'        => 'nullable|string|max:50',

        // TELEFONOS
        'telefonos' => 'nullable|array',

        // RESPONSABLES
        'responsables' => 'nullable|array',
    ]);

    DB::beginTransaction();

    try {

        // ==========================================
        // PERSONA
        // ==========================================
        $persona->update([
            'domicilio'     => $datos['form_domicilio'] ?? null,
            'nit'           => $datos['form_nit'] ?? null,
            'correo'        => $datos['form_correo'],
            'id_territorio' => $datos['form_id_territorio'],
            'estado'        => $datos['form_estado'] ?? 1,
        ]);

        // ==========================================
        // EMPRESA
        // ==========================================
        $empresa = Empresa::updateOrCreate(
            ['id_persona' => $persona->id],
            [
                'id_tipo_empresa' => $datos['form_id_tipo_empresa'],
                'razon_social'    => $datos['form_razon_social'],
                'matricula'       => $datos['form_matricula'],
                'latitud'         => $datos['form_latitud'] ?? null,
                'longitud'        => $datos['form_longitud'] ?? null,
                'id_territorio'   => $datos['form_id_territorio_empresa'],
                'estado'          => $datos['form_estado_empresa'] ?? 'ACTIVO',
            ]
        );

        // ==========================================
        // TELEFONOS
        // ==========================================
        Telefono::where('id_persona', $persona->id)->delete();

        if (!empty($datos['telefonos'])) {

            foreach ($datos['telefonos'] as $telefono) {

                Telefono::create([
                    'id_persona' => $persona->id,
                    'numero'     => $telefono['numero'],
                    'estado'     => $telefono['tipo'] ?? 'CELULAR',
                ]);

            }

        }

        // ==========================================
        // RESPONSABLES
        // ==========================================
        Responsable::where('id_empresa', $empresa->id)->delete();

        if (!empty($datos['responsables'])) {

            foreach ($datos['responsables'] as $responsable) {

                Responsable::create([
                    'id_empresa'     => $empresa->id,
                    'id_persona'     => $responsable['id_persona'],
                    'id_rol'         => $responsable['id_rol'],
                    'url_respaldo'   => $responsable['url_respaldo'] ?? null,
                    'fecha_registro' => $responsable['fecha_registro'] ?? null,
                    'fecha_baja'     => $responsable['fecha_baja'] ?? null,
                    'estado'         => $responsable['estado'] ?? 'ACTIVO',
                ]);

            }

        }

        DB::commit();

        return redirect()->route('personas_index');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()
            ->withInput()
            ->with('error', $e->getMessage());
    }
}


// ==========================================
// DESTROY PERSONA NATURAL
// ==========================================
public function destroyNatural(Persona $persona)
{
    DB::beginTransaction();

    try {

        Telefono::where('id_persona', $persona->id)->delete();

        Rubro::where('id_persona', $persona->id)->delete();

        if ($persona->natural) {
            $persona->natural->delete();
        }

        $persona->delete();

        DB::commit();

        return redirect()->route('personas_index');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with('error', 'No se pudo eliminar.');
    }
}

// ==========================================
// DESTROY EMPRESA
// ==========================================
public function destroyEmpresa(Persona $persona)
{
    DB::beginTransaction();

    try {

        $empresa = Empresa::where('id_persona', $persona->id)->first();

        if ($empresa) {

            Responsable::where('id_empresa', $empresa->id)->delete();

            $empresa->delete();
        }

        Telefono::where('id_persona', $persona->id)->delete();

        $persona->delete();

        DB::commit();

        return redirect()->route('personas_index');

    } catch (\Exception $e) {

        DB::rollBack();

        return back()->with('error', 'No se pudo eliminar.');
    }
}

