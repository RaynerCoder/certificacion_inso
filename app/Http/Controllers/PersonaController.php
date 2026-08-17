<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Natural;
use App\Models\OcupacionCob;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Responsable;
use App\Models\Role;
use App\Models\Rubro;
use App\Models\Telefono;
use App\Models\Territorio;
use App\Models\TipoEmpresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PersonaController extends Controller
{
    /**
     * Muestra las personas naturales y juridicas registradas.
     */
    public function index()
    {
        $personas = Persona::with([
            'natural',
            'telefonos',
            'territorio',
            'rubros',
            'empresa.tipoEmpresa',
            'empresa.responsables.persona.natural',
        ])->latest()->get();

        return view('personas.index', compact('personas'));
    }

    private function mensajesValidacionPersona(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'required_if' => 'El campo :attribute es obligatorio para este tipo de registro.',
            'email' => 'El campo :attribute debe ser un correo válido.',
            'unique' => 'El valor ingresado en :attribute ya está registrado.',
            'exists' => 'El valor seleccionado en :attribute no es válido.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'date' => 'El campo :attribute debe tener una fecha válida.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'size' => 'Debe registrar exactamente :size :attribute.',
            'file' => 'El campo :attribute debe ser un archivo válido.',
            'mimes' => 'El archivo de :attribute debe ser de tipo: :values.',
        ];
    }

    private function reglasValidacionPersona(
        Role $rolSolicitante,
        Role $rolRepresentanteLegal,
        ?Persona $personaEditada = null,
        ?User $usuarioCuenta = null
    ): array {
        return [
            'form_tipo_registro' => 'required|in:NATURAL,EMPRESA',

            // Datos comunes de la persona natural o jurídica.
            'form_domicilio' => 'nullable|string|max:255',
            'form_nit' => [
                'required_if:form_tipo_registro,EMPRESA',
                'nullable',
                'string',
                'max:50',
                Rule::unique('personas', 'nit')->ignore($personaEditada?->id),
            ],
            'form_correo' => [
                'required',
                'email',
                'max:50',
                Rule::unique('personas', 'correo')->ignore($personaEditada?->id),
            ],
            'form_id_pais' => [
                'required',
                Rule::exists('territorios', 'id')->where(fn ($query) => $query->where('id_ambito', 1)),
            ],
            'form_id_territorio' => 'required|exists:territorios,id',
            'form_estado' => ['nullable', Rule::in(['ACTIVO', 'INACTIVO'])],

            // Datos exclusivos de una persona natural.
            'form_ci' => [
                'required_if:form_tipo_registro,NATURAL',
                'nullable',
                'string',
                'max:50',
                Rule::unique('naturals', 'ci')->ignore($personaEditada?->natural?->id),
            ],
            'form_complemento' => 'nullable|string|max:10',
            'form_expedido' => ['nullable', Rule::in(array_keys(Natural::EXPEDIDOS))],
            'form_nombres' => 'required_if:form_tipo_registro,NATURAL|nullable|string|max:100',
            'form_apellido_paterno' => 'required_if:form_tipo_registro,NATURAL|nullable|string|max:100',
            'form_apellido_materno' => 'nullable|string|max:100',
            'form_apellido_casado' => 'nullable|string|max:100',
            'form_fecha_nacimiento' => 'nullable|date',
            'form_genero' => 'required_if:form_tipo_registro,NATURAL|nullable',
            'form_id_ocupacion' => 'nullable|exists:ocupaciones_cob,id',

            // Datos exclusivos de una empresa.
            'form_id_tipo_empresa' => 'required_if:form_tipo_registro,EMPRESA|nullable|exists:tipos_empresas,id',
            'form_razon_social' => 'required_if:form_tipo_registro,EMPRESA|nullable|string|max:255',
            'form_matricula' => 'required_if:form_tipo_registro,EMPRESA|nullable|string|max:50',
            'form_latitud' => 'nullable',
            'form_longitud' => 'nullable',
            'form_estado_empresa' => 'nullable|max:50',

            'telefonos' => 'nullable|array',
            'telefonos.*.id' => 'nullable|integer|exists:telefonos,id',
            'telefonos.*.numero' => 'required|string|max:50',
            'telefonos.*.estado' => 'required|in:ACTIVO,INACTIVO',

            'rubros' => 'nullable|array',
            'rubros.*' => ['integer', $this->reglaRubroCaebActivo()],

            // La empresa administra un representante legal desde este formulario.
            'responsables' => 'required_if:form_tipo_registro,EMPRESA|array|size:1',
            'responsables.*.tipo' => 'required|in:NUEVO,EXISTENTE',
            'responsables.*.id_persona' => [
                'nullable',
                Rule::exists('personas', 'id')->where(fn ($query) => $query
                    ->where('estado', 'ACTIVO')
                    ->whereNull('deleted_at')),
            ],
            'responsables.*.domicilio' => 'nullable|string|max:255',
            'responsables.*.nit' => 'nullable|string|max:50',
            'responsables.*.correo' => 'nullable|email|max:50',
            'responsables.*.id_territorio' => 'nullable|exists:territorios,id',
            'responsables.*.territorio_nombre' => 'nullable|string|max:255',
            'responsables.*.nombres' => 'nullable|string|max:100',
            'responsables.*.apellido_paterno' => 'nullable|string|max:100',
            'responsables.*.apellido_materno' => 'nullable|string|max:100',
            'responsables.*.apellido_casado' => 'nullable|string|max:100',
            'responsables.*.ci' => 'nullable|string|max:50',
            'responsables.*.complemento' => 'nullable|string|max:10',
            'responsables.*.expedido' => ['nullable', Rule::in(array_keys(Natural::EXPEDIDOS))],
            'responsables.*.fecha_nacimiento' => 'nullable|date',
            'responsables.*.genero' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'responsables.*.id_ocupacion' => 'nullable|exists:ocupaciones_cob,id',
            'responsables.*.ocupacion' => 'nullable|string|max:255',
            'responsables.*.telefonos' => 'nullable|array',
            'responsables.*.telefonos.*.id' => 'nullable|integer|exists:telefonos,id',
            'responsables.*.telefonos.*.numero' => 'required|string|max:50',
            'responsables.*.telefonos.*.estado' => 'required|in:ACTIVO,INACTIVO',
            'responsables.*.rubros' => 'nullable|array',
            'responsables.*.rubros.*.id' => ['required', 'integer', $this->reglaRubroCaebActivo()],
            'responsables.*.rubros.*.nombre' => 'nullable|string|max:255',
            'responsables.*.rubros.*.estado' => 'nullable|string|max:50',
            'responsables.*.id_rol' => [
                'required_if:form_tipo_registro,EMPRESA',
                'nullable',
                Rule::in([$rolRepresentanteLegal->id]),
            ],
            'responsables.*.url_respaldo' => 'nullable|string|max:255',
            'responsables.*.archivo_respaldo' => 'nullable|file|mimes:pdf|max:5120',
            'responsables.*.estado' => ['nullable', Rule::in(['ACTIVO', 'INACTIVO'])],

            // El rol de acceso se valida aunque el campo este bloqueado en la interfaz.
            'form_usuario_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'name')->ignore($usuarioCuenta?->id),
            ],
            'form_usuario_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuarioCuenta?->id),
            ],
            'form_usuario_password' => 'nullable|string|min:8',
            'form_id_role' => ['required', Rule::in([$rolSolicitante->id])],
        ];
    }

    private function atributosValidacionPersona(): array
    {
        return [
            'form_tipo_registro' => 'tipo de registro',
            'form_correo' => 'correo electrónico',
            'form_id_pais' => 'país',
            'form_id_territorio' => 'territorio',
            'form_ci' => 'CI',
            'form_nombres' => 'nombres',
            'form_apellido_paterno' => 'apellido paterno',
            'form_genero' => 'género',
            'form_id_tipo_empresa' => 'tipo de empresa',
            'form_razon_social' => 'razón social',
            'form_matricula' => 'matrícula',
            'form_usuario_name' => 'nombre de usuario',
            'form_usuario_email' => 'correo de acceso',
            'form_usuario_password' => 'contraseña de acceso',
            'form_id_role' => 'rol de acceso',
            'responsables.*.id_rol' => 'rol del responsable',
            'responsables.*.id_persona' => 'persona responsable',
            'responsables' => 'responsable o representante legal',
        ];
    }

    /**
     * Muestra el formulario de registro.
     */
    public function create()
    {
        $territorios = Territorio::all();
        $paises = Territorio::where('id_ambito', 1)->orderBy('nombre')->get();
        $departamentos = Territorio::where('id_ambito', 2)->orderBy('nombre')->get();
        $tiposEmpresas = TipoEmpresa::all();
        $rolSolicitante = $this->rolSolicitanteActivo();
        $rolRepresentanteLegal = $this->rolRepresentanteLegalActivo();
        $rubrosCatalogo = $this->catalogoRubrosCaeb();
        $ocupacionesCob = OcupacionCob::orderBy('codigo_ocupacion')->get();
        $expedidosNatural = Natural::EXPEDIDOS;
        $personas = Persona::with([
            'natural',
            'telefonos',
            'territorio',
            'rubros' => fn ($query) => $query->where('rubros.nivel_caeb', 'SUBCLASE'),
        ])
            // El selector de responsables solo debe mostrar personas naturales con CI.
            // Así evitamos opciones como "Sin CI -" que vienen de empresas o registros incompletos.
            ->whereHas('natural', fn ($consultaNatural) => $consultaNatural
                ->whereNotNull('ci')
                ->where('ci', '!=', ''))
            ->orderBy('id')
            ->get();

        return view('personas.create', [
            'territorios' => $territorios,
            'paises' => $paises,
            'departamentos' => $departamentos,
            'personas' => $personas,
            'tiposEmpresas' => $tiposEmpresas,
            'rolSolicitante' => $rolSolicitante,
            'rolRepresentanteLegal' => $rolRepresentanteLegal,
            'rubrosCatalogo' => $rubrosCatalogo,
            'ocupacionesCob' => $ocupacionesCob,
            'expedidosNatural' => $expedidosNatural,
        ]);
    }

    // El modal solicita únicamente los territorios del nivel que el usuario está revisando.
    public function territoriosHijos(Territorio $territorio)
    {
        $territorios = $territorio->territoriosHijos()
            ->with('ambito:id,nombre')
            ->whereIn('estado', ['ACTIVO', 'Activo', 'activo', '1', 1])
            ->orderBy('nombre')
            ->get(['id', 'id_ambito', 'id_padre_territorio', 'nombre']);

        return response()->json($territorios->map(fn ($hijo) => [
            'id' => $hijo->id,
            'nombre' => $hijo->nombre,
            'nivel' => $hijo->ambito?->nombre ?: 'Territorio',
        ])->values());
    }

    // Devuelve la ruta completa para reconstruir el selector cuando se edita un responsable.
    public function rutaTerritorio(Territorio $territorio)
    {
        $ruta = [];
        $actual = $territorio;
        $idsRecorridos = [];

        while ($actual && ! in_array($actual->id, $idsRecorridos, true)) {
            $idsRecorridos[] = $actual->id;
            array_unshift($ruta, [
                'id' => $actual->id,
                'nombre' => $actual->nombre,
            ]);

            $actual = $actual->territorioPadre()
                ->first(['id', 'id_padre_territorio', 'nombre']);
        }

        return response()->json($ruta);
    }

    /**
     * Registra una persona natural o una empresa con su cuenta de acceso.
     */
    public function store(Request $solicitud)
    {
        $this->usarPaisComoTerritorioSiNoHayDepartamento($solicitud);
        $rolSolicitante = $this->rolSolicitanteActivo();
        $rolRepresentanteLegal = $this->rolRepresentanteLegalActivo();
        $this->fijarRolesEmpresaEnSolicitud($solicitud, $rolSolicitante, $rolRepresentanteLegal);
        $responsableSolicitado = $solicitud->input('responsables.0', []);
        $personaResponsableExistente = $this->personaResponsableExistente($responsableSolicitado);
        $usuarioResponsableExistente = $personaResponsableExistente?->usuario;

        try {
            $datos = $solicitud->validate(
                $this->reglasValidacionPersona(
                    $rolSolicitante,
                    $rolRepresentanteLegal,
                    usuarioCuenta: $usuarioResponsableExistente
                ),
                $this->mensajesValidacionPersona(),
                $this->atributosValidacionPersona()
            );

            // Evita errores SQL cuando se registra un responsable nuevo sin datos obligatorios de persona.
            $this->validarResponsablesAntesDeGuardar($datos['responsables'] ?? []);
            $this->validarTerritoriosResponsables($datos['responsables'] ?? []);
            $this->validarTerritorioPrincipal($datos['form_id_pais'], $datos['form_id_territorio']);

        } catch (ValidationException $e) {
            // Si falla un campo externo al modal, guardamos los PDF validos ya seleccionados.
            // Esto evita que el responsable agregado pierda su respaldo al volver con errores.
            $this->preservarRespaldosResponsablesEnInput($solicitud);

            throw $e;
        }

        try {
            DB::beginTransaction();

            $responsableActivo = $datos['form_tipo_registro'] !== 'EMPRESA'
                || strtoupper($datos['responsables'][0]['estado'] ?? 'ACTIVO') === 'ACTIVO';

            $usuarioAcceso = $datos['form_tipo_registro'] === 'EMPRESA'
                ? ($usuarioResponsableExistente ?: new User)
                : new User;
            [$usuarioAcceso, $passwordGeneradaCuenta] = $this->guardarCuentaAcceso(
                $usuarioAcceso,
                $datos,
                strtoupper($datos['form_estado'] ?? 'ACTIVO') === 'ACTIVO' && $responsableActivo
            );

            $persona = Persona::create([
                'id_usuario' => $datos['form_tipo_registro'] === 'NATURAL'
                    ? $usuarioAcceso->id
                    : null,
                'domicilio' => $datos['form_domicilio'] ?? null,
                'nit' => $datos['form_nit'] ?? null,
                'correo' => $datos['form_correo'],
                'id_territorio' => $datos['form_id_territorio'],
                'estado' => $datos['form_estado'] ?? 'ACTIVO',
            ]);

            $this->registrarTelefonosPersona($persona->id, $datos['telefonos'] ?? []);

            $this->registrarRubrosPersona($persona->id, $datos['rubros'] ?? []);

            if ($datos['form_tipo_registro'] === 'NATURAL') {
                Natural::create([
                    'id_persona' => $persona->id,
                    'id_ocupacion' => $datos['form_id_ocupacion'] ?? null,
                    'ci' => $datos['form_ci'] ?? null,
                    'complemento' => $datos['form_complemento'] ?? null,
                    'expedido' => $datos['form_expedido'] ?? null,
                    'nombres' => $this->mayuscula($datos['form_nombres'] ?? null),
                    'apellido_paterno' => $this->mayuscula($datos['form_apellido_paterno'] ?? null),
                    'apellido_materno' => $this->mayuscula($datos['form_apellido_materno'] ?? null),
                    'apellido_casado' => $this->mayuscula($datos['form_apellido_casado'] ?? null),
                    'fecha_nacimiento' => $datos['form_fecha_nacimiento'] ?? null,
                    'genero' => $datos['form_genero'],
                    'ocupacion' => $this->descripcionOcupacionCob($datos['form_id_ocupacion'] ?? null),
                ]);
            }

            if ($datos['form_tipo_registro'] === 'EMPRESA') {
                $empresa = Empresa::create([
                    'id_persona' => $persona->id,
                    'id_tipo_empresa' => $datos['form_id_tipo_empresa'],
                    'razon_social' => $datos['form_razon_social'],
                    'matricula' => $datos['form_matricula'],
                    'latitud' => $datos['form_latitud'] ?? null,
                    'longitud' => $datos['form_longitud'] ?? null,
                    'estado' => ! empty($datos['form_estado_empresa']) ? $datos['form_estado_empresa'] : 'ACTIVO',
                ]);

                $this->registrarResponsablesEmpresa(
                    $empresa->id,
                    $datos['responsables'] ?? [],
                    [],
                    $usuarioAcceso->id
                );
            }

            DB::commit();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => $passwordGeneradaCuenta
                    ? 'El registro se guardo correctamente. Contrasena generada: '.$passwordGeneradaCuenta
                    : 'El registro se guardo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('personas_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar. '.$e->getMessage())
                ->withInput();
        }
    }

    // Registra los teléfonos enviados al crear una persona o empresa.
    private function registrarTelefonosPersona($idPersona, array $telefonos): void
    {
        foreach ($telefonos as $telefono) {
            Telefono::create([
                'id_persona' => $idPersona,
                'numero' => $telefono['numero'],
                'estado' => $telefono['estado'] ?? 'ACTIVO',
            ]);
        }
    }

    // Conserva los teléfonos registrados y deja inactivos los que se retiraron del formulario.
    private function sincronizarTelefonosPersona(int $idPersona, array $telefonos): void
    {
        Telefono::where('id_persona', $idPersona)->update(['estado' => 'INACTIVO']);

        foreach ($telefonos as $telefono) {
            $datosTelefono = [
                'numero' => $telefono['numero'],
                'estado' => $telefono['estado'] ?? 'ACTIVO',
            ];

            $telefonoRegistrado = ! empty($telefono['id'])
                ? Telefono::where('id_persona', $idPersona)->find($telefono['id'])
                : null;

            if ($telefonoRegistrado) {
                $telefonoRegistrado->update($datosTelefono);

                continue;
            }

            Telefono::create(['id_persona' => $idPersona] + $datosTelefono);
        }
    }

    // El selector usa el id interno, pero solo permite registros del catalogo CAEB activo.
    private function reglaRubroCaebActivo()
    {
        return Rule::exists('rubros', 'id')->where(fn ($query) => $query
            ->whereNotNull('codigo_caeb')
            ->where('nivel_caeb', 'SUBCLASE')
            ->where('estado', 'ACTIVO')
            ->whereNull('deleted_at'));
    }

    // Los formularios reciben solo el id interno, codigo CAEB y nombre.
    private function catalogoRubrosCaeb()
    {
        return Rubro::query()
            ->whereNotNull('codigo_caeb')
            ->where('nivel_caeb', 'SUBCLASE')
            ->where('estado', 'ACTIVO')
            ->orderBy('codigo_caeb')
            ->get(['id', 'codigo_caeb', 'nombre']);
    }

    // Sincroniza el catalogo CAEB y conserva relaciones historicas sin codigo.
    private function registrarRubrosPersona($idPersona, array $rubros): void
    {
        $idsSolicitados = collect($rubros)
            ->map(function ($rubro) {
                if (is_array($rubro)) {
                    return $rubro['id'] ?? $rubro['id_rubro'] ?? null;
                }

                return $rubro;
            })
            ->filter(fn ($idRubro) => filter_var($idRubro, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($idRubro) => (int) $idRubro)
            ->unique()
            ->values();

        $idsRubros = Rubro::query()
            ->whereIn('id', $idsSolicitados)
            ->whereNotNull('codigo_caeb')
            ->where('nivel_caeb', 'SUBCLASE')
            ->where('estado', 'ACTIVO')
            ->pluck('id');

        $datosSync = $idsRubros
            ->mapWithKeys(fn ($idRubro) => [(int) $idRubro => ['estado' => 'ACTIVO']])
            ->all();

        $persona = Persona::find($idPersona);

        if (! $persona) {
            return;
        }

        // Las relaciones historicas o de niveles superiores no aparecen en el
        // selector, pero tampoco se eliminan al editar una persona o empresa.
        $datosHistoricos = $persona->rubros()
            ->where(function ($query) {
                $query->whereNull('rubros.codigo_caeb')
                    ->orWhereNull('rubros.nivel_caeb')
                    ->orWhere('rubros.nivel_caeb', '<>', 'SUBCLASE');
            })
            ->get()
            ->mapWithKeys(fn ($rubro) => [
                (int) $rubro->id => ['estado' => $rubro->pivot->estado ?? 'ACTIVO'],
            ])
            ->all();

        $persona->rubros()->sync($datosSync + $datosHistoricos);
    }

    private function descripcionOcupacionCob($idOcupacion): ?string
    {
        if (! $idOcupacion) {
            return null;
        }

        return OcupacionCob::find($idOcupacion)?->descripcion_ocupacion;
    }

    // FUNCION PARA CONSERVAR PDF DE RESPONSABLES SI FALLA LA VALIDACION DEL FORMULARIO
    private function preservarRespaldosResponsablesEnInput(Request $solicitud): void
    {
        $responsables = $solicitud->input('responsables', []);
        $archivosResponsables = $solicitud->file('responsables', []);

        foreach ($archivosResponsables as $indice => $archivos) {
            $archivo = $archivos['archivo_respaldo'] ?? null;

            if (! $archivo || ! $archivo->isValid()) {
                continue;
            }

            // Solo preservamos PDF validos y dentro del limite permitido.
            // Esto evita perder el respaldo cuando el error esta en otro campo del formulario.
            $esPdf = strtolower($archivo->getClientOriginalExtension()) === 'pdf';
            $pesoPermitido = $archivo->getSize() <= 5120 * 1024;

            if (! $esPdf || ! $pesoPermitido) {
                continue;
            }

            $responsables[$indice]['url_respaldo'] = $this->guardarRespaldoResponsable($archivo);
        }

        $solicitud->merge([
            'responsables' => $responsables,
        ]);
    }

    // Revisa los datos propios del responsable antes de crear o actualizar sus relaciones.
    private function validarResponsablesAntesDeGuardar(array $responsables): void
    {
        $errores = [];
        $personasExistentesAgregadas = [];
        $cisAgregados = [];
        $correosAgregados = [];
        $nitsAgregados = [];

        foreach ($responsables as $indice => $responsable) {
            $tipo = $responsable['tipo'] ?? null;
            $idPersonaResponsable = trim((string) ($responsable['id_persona'] ?? ''));
            $ciResponsable = strtolower(trim((string) ($responsable['ci'] ?? '')));
            $correoResponsable = strtolower(trim((string) ($responsable['correo'] ?? '')));
            $nitResponsable = strtolower(trim((string) ($responsable['nit'] ?? '')));
            $esPersonaExistente = $tipo === 'EXISTENTE' && $idPersonaResponsable !== '';

            if ($tipo === 'EXISTENTE' && empty($idPersonaResponsable)) {
                $errores["responsables.$indice.id_persona"] = 'Seleccione la persona responsable.';
            } elseif ($tipo === 'EXISTENTE') {
                $personaNaturalValida = Persona::query()
                    ->whereKey($idPersonaResponsable)
                    ->where('estado', 'ACTIVO')
                    ->whereHas('natural', fn ($natural) => $natural
                        ->whereNotNull('ci')
                        ->where('ci', '<>', ''))
                    ->exists();

                if (! $personaNaturalValida) {
                    $errores["responsables.$indice.id_persona"] =
                        'Seleccione una persona natural activa como responsable.';
                }

                if (in_array($idPersonaResponsable, $personasExistentesAgregadas, true)) {
                    $errores["responsables.$indice.id_persona"] = 'Esta persona ya fue agregada como responsable.';
                }

                $personasExistentesAgregadas[] = $idPersonaResponsable;
            }

            if ($ciResponsable !== '') {
                if (in_array($ciResponsable, $cisAgregados, true)) {
                    $errores["responsables.$indice.ci"] = 'Este CI ya fue agregado como responsable.';
                }

                $cisAgregados[] = $ciResponsable;
            }

            if ($correoResponsable !== '') {
                if (in_array($correoResponsable, $correosAgregados, true)) {
                    $errores["responsables.$indice.correo"] = 'Este correo ya fue agregado como responsable.';
                }

                $correosAgregados[] = $correoResponsable;
            }

            if ($nitResponsable !== '') {
                if (in_array($nitResponsable, $nitsAgregados, true)) {
                    $errores["responsables.$indice.nit"] = 'Este NIT ya fue agregado como responsable.';
                }

                $nitsAgregados[] = $nitResponsable;
            }

            if ($tipo === 'NUEVO' && $correoResponsable === '') {
                $errores["responsables.$indice.correo"] = 'Ingrese el correo del responsable.';
            }

            if ($correoResponsable !== '' && Persona::query()
                ->where('correo', $responsable['correo'])
                ->when($esPersonaExistente, fn ($consulta) => $consulta->where('id', '<>', $idPersonaResponsable))
                ->exists()) {
                $errores["responsables.$indice.correo"] = 'El correo del responsable ya esta registrado.';
            }

            if ($nitResponsable !== '' && Persona::query()
                ->where('nit', $responsable['nit'])
                ->when($esPersonaExistente, fn ($consulta) => $consulta->where('id', '<>', $idPersonaResponsable))
                ->exists()) {
                $errores["responsables.$indice.nit"] = 'El NIT del responsable ya esta registrado.';
            }

            if ($ciResponsable !== '' && Natural::query()
                ->where('ci', $responsable['ci'])
                ->when($esPersonaExistente, fn ($consulta) => $consulta->where('id_persona', '<>', $idPersonaResponsable))
                ->exists()) {
                $errores["responsables.$indice.ci"] = 'El CI del responsable ya esta registrado.';
            }

            if ($tipo !== 'NUEVO') {
                continue;
            }

            if (empty($responsable['id_territorio'])) {
                $errores["responsables.$indice.id_territorio"] = 'Seleccione el territorio del responsable.';
            }

            if (empty($responsable['nombres'])) {
                $errores["responsables.$indice.nombres"] = 'Ingrese los nombres del responsable.';
            }

            if (empty($responsable['apellido_paterno'])) {
                $errores["responsables.$indice.apellido_paterno"] = 'Ingrese el apellido paterno del responsable.';
            }

            if ($ciResponsable === '') {
                $errores["responsables.$indice.ci"] = 'Ingrese el CI del responsable.';
            }

            if (! isset($responsable['genero']) || $responsable['genero'] === '') {
                $errores["responsables.$indice.genero"] = 'Seleccione el género del responsable.';
            }
        }

        if (! empty($errores)) {
            throw ValidationException::withMessages($errores);
        }
    }

    // Busca el rol por su código estable para no depender del ID de cada base de datos.
    private function rolSolicitanteActivo(): Role
    {
        return Role::query()
            ->where('slug', 'solicitante')
            ->where('estado', 1)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function rolRepresentanteLegalActivo(): Role
    {
        // En responsables, Solicitante identifica a quien representa legalmente a la empresa.
        return $this->rolSolicitanteActivo();
    }

    private function relacionesRepresentanteLegal(Collection $relaciones): Collection
    {
        return $relaciones
            ->filter(fn ($responsable) => $responsable->rol?->slug === 'solicitante');
    }

    // La cuenta accede como solicitante y la persona representa legalmente a la empresa.
    private function fijarRolesEmpresaEnSolicitud(
        Request $solicitud,
        Role $rolSolicitante,
        Role $rolRepresentanteLegal
    ): void {
        $responsables = collect($solicitud->input('responsables', []))
            ->map(fn (array $responsable) => array_merge($responsable, [
                'id_rol' => $rolRepresentanteLegal->id,
            ]))
            ->all();

        $datosFijos = [
            'form_id_role' => $rolSolicitante->id,
            'responsables' => $responsables,
        ];

        if ($solicitud->input('form_tipo_registro') === 'EMPRESA') {
            $responsable = $responsables[0] ?? [];
            $ciResponsable = preg_replace('/[^A-Za-z0-9]/', '', (string) ($responsable['ci'] ?? ''));
            $nitResponsable = preg_replace('/[^A-Za-z0-9]/', '', (string) ($responsable['nit'] ?? ''));
            $nombreUsuario = trim((string) $solicitud->input('form_usuario_name', ''));
            $correoAcceso = trim((string) $solicitud->input('form_usuario_email', ''));

            // Las sugerencias de acceso pertenecen al responsable, no a la empresa.
            $datosFijos['form_usuario_name'] = $nombreUsuario !== ''
                ? $nombreUsuario
                : Str::lower($ciResponsable ?: $nitResponsable);
            $datosFijos['form_usuario_email'] = $correoAcceso !== ''
                ? $correoAcceso
                : trim((string) ($responsable['correo'] ?? ''));
        }

        $solicitud->merge($datosFijos);
    }

    private function validarTerritoriosResponsables(array $responsables): void
    {
        $errores = [];

        foreach ($responsables as $indice => $responsable) {
            $idTerritorio = $responsable['id_territorio'] ?? null;

            if (! $idTerritorio) {
                continue;
            }

            $territorio = Territorio::query()
                ->with('territorioPadre:id,id_ambito')
                ->find($idTerritorio);

            if (! $territorio) {
                continue;
            }

            $esPais = $territorio->id_ambito == 1;
            $esSegundoNivel = $territorio->territorioPadre?->id_ambito == 1;

            if (! $esPais && ! $esSegundoNivel) {
                $errores["responsables.$indice.id_territorio"] =
                    'Seleccione el territorio correspondiente al país.';
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function validarTerritorioPrincipal($idPais, $idTerritorio): void
    {
        if ((string) $idTerritorio === (string) $idPais) {
            return;
        }

        $departamentoValido = Territorio::where('id', $idTerritorio)
            ->where('id_padre_territorio', $idPais)
            ->where('id_ambito', 2)
            ->exists();

        if (! $departamentoValido) {
            throw ValidationException::withMessages([
                'form_id_territorio' => 'Seleccione un departamento valido para el pais.',
            ]);
        }
    }

    private function usarPaisComoTerritorioSiNoHayDepartamento(Request $solicitud): void
    {
        if ($solicitud->filled('form_id_pais') && ! $solicitud->filled('form_id_territorio')) {
            $solicitud->merge([
                'form_id_territorio' => $solicitud->input('form_id_pais'),
            ]);
        }
    }

    private function registrarResponsablesEmpresa(
        int $idEmpresa,
        array $responsables,
        array $fechasRegistroExistentes = [],
        ?int $idUsuarioAcceso = null
    ): void {
        if (empty($responsables)) {
            return;
        }

        foreach ($responsables as $responsable) {
            if ($responsable['tipo'] === 'EXISTENTE') {
                $idPersonaResponsable = $responsable['id_persona'];
                $personaResponsable = $this->personaResponsableExistente($responsable);

                if ($personaResponsable) {
                    $naturalResponsable = $personaResponsable->natural;
                    $generoResponsable = $responsable['genero'] ?? null;
                    $fechaNacimientoResponsable = $responsable['fecha_nacimiento'] ?? null;

                    // Al seleccionar una persona existente, el formulario puede omitir este dato.
                    // En ese caso se conserva el genero que ya tiene registrado.
                    if ($generoResponsable === null || $generoResponsable === '') {
                        $generoResponsable = $naturalResponsable?->genero;
                    }

                    if ($fechaNacimientoResponsable === null || $fechaNacimientoResponsable === '') {
                        $fechaNacimientoResponsable = $naturalResponsable?->fecha_nacimiento;
                    }

                    $this->validarRepresentanteDisponibleParaEmpresa(
                        (int) $personaResponsable->id,
                        $idEmpresa,
                        (int) $responsable['id_rol'],
                        (string) ($responsable['estado'] ?? 'ACTIVO')
                    );

                    $datosPersonaResponsable = [
                        'domicilio' => $responsable['domicilio'] ?? null,
                        'nit' => $responsable['nit'] ?? null,
                        'correo' => $responsable['correo'] ?? null,
                        'id_territorio' => $responsable['id_territorio'] ?? null,
                    ];

                    if ($idUsuarioAcceso) {
                        $datosPersonaResponsable['id_usuario'] = $idUsuarioAcceso;
                    }

                    $personaResponsable->update($datosPersonaResponsable);

                    Natural::updateOrCreate(
                        ['id_persona' => $personaResponsable->id],
                        [
                            'ci' => $responsable['ci'] ?? null,
                            'complemento' => $responsable['complemento'] ?? null,
                            'expedido' => $responsable['expedido'] ?? null,
                            'nombres' => $this->mayuscula($responsable['nombres'] ?? null),
                            'apellido_paterno' => $this->mayuscula($responsable['apellido_paterno'] ?? null),
                            'apellido_materno' => $this->mayuscula($responsable['apellido_materno'] ?? null),
                            'apellido_casado' => $this->mayuscula($responsable['apellido_casado'] ?? null),
                            'fecha_nacimiento' => $fechaNacimientoResponsable,
                            'genero' => $generoResponsable,
                            'id_ocupacion' => $responsable['id_ocupacion'] ?? null,
                            'ocupacion' => $this->descripcionOcupacionCob($responsable['id_ocupacion'] ?? null),
                        ]
                    );

                    $this->sincronizarTelefonosPersona(
                        $personaResponsable->id,
                        $responsable['telefonos'] ?? []
                    );

                    $this->registrarRubrosPersona($personaResponsable->id, $responsable['rubros'] ?? []);
                }
            } else {
                $personaResponsable = Persona::create([
                    'id_usuario' => $idUsuarioAcceso,
                    'domicilio' => $responsable['domicilio'] ?? null,
                    'nit' => $responsable['nit'] ?? null,
                    'correo' => $responsable['correo'] ?? null,
                    'id_territorio' => $responsable['id_territorio'] ?? null,
                    'estado' => 'ACTIVO',
                ]);

                Natural::create([
                    'id_persona' => $personaResponsable->id,
                    'ci' => $responsable['ci'] ?? null,
                    'complemento' => $responsable['complemento'] ?? null,
                    'expedido' => $responsable['expedido'] ?? null,
                    'nombres' => $this->mayuscula($responsable['nombres'] ?? null),
                    'apellido_paterno' => $this->mayuscula($responsable['apellido_paterno'] ?? null),
                    'apellido_materno' => $this->mayuscula($responsable['apellido_materno'] ?? null),
                    'apellido_casado' => $this->mayuscula($responsable['apellido_casado'] ?? null),
                    'fecha_nacimiento' => $responsable['fecha_nacimiento'] ?? null,
                    'genero' => $responsable['genero'] ?? null,
                    'id_ocupacion' => $responsable['id_ocupacion'] ?? null,
                    'ocupacion' => $this->descripcionOcupacionCob($responsable['id_ocupacion'] ?? null),
                ]);

                $this->registrarTelefonosPersona(
                    $personaResponsable->id,
                    $responsable['telefonos'] ?? []
                );

                $this->registrarRubrosPersona($personaResponsable->id, $responsable['rubros'] ?? []);
                $idPersonaResponsable = $personaResponsable->id;
            }

            // Guarda el PDF del respaldo en una ruta unica dentro del disco public.
            $urlRespaldo = $responsable['url_respaldo'] ?? null;

            if (! empty($responsable['archivo_respaldo'])) {
                $urlRespaldo = $this->guardarRespaldoResponsable($responsable['archivo_respaldo']);
            }

            // La relacion existente se reutiliza para conservar su historial y evitar duplicados.
            $claveResponsable = $idPersonaResponsable.':'.$responsable['id_rol'];
            $estadoResponsable = strtoupper($responsable['estado'] ?? 'ACTIVO');
            $relacionResponsable = Responsable::withTrashed()
                ->where('id_empresa', $idEmpresa)
                ->where('id_persona', $idPersonaResponsable)
                ->where('id_rol', $responsable['id_rol'])
                ->latest('id')
                ->first();

            if ($relacionResponsable?->trashed()) {
                $relacionResponsable->restore();
            }

            $datosRelacion = [
                'id_empresa' => $idEmpresa,
                'id_persona' => $idPersonaResponsable,
                'id_rol' => $responsable['id_rol'],
                'url_respaldo' => $urlRespaldo,
                // La fecha se define en el servidor y no depende de un valor enviado desde el modal.
                'fecha_registro' => $fechasRegistroExistentes[$claveResponsable]
                    ?? $relacionResponsable?->fecha_registro
                    ?? now()->toDateString(),
                'fecha_baja' => $estadoResponsable === 'INACTIVO' ? now()->toDateString() : null,
                'estado' => $estadoResponsable,
            ];

            $relacionResponsable
                ? $relacionResponsable->update($datosRelacion)
                : Responsable::create($datosRelacion);
        }
    }

    // Una misma cuenta no se comparte entre empresas cuando actua como representante legal.
    private function validarRepresentanteDisponibleParaEmpresa(
        int $idPersona,
        int $idEmpresa,
        int $idRol,
        string $estado
    ): void {
        if (mb_strtoupper($estado) !== 'ACTIVO') {
            return;
        }

        $esRepresentanteLegal = Role::query()
            ->whereKey($idRol)
            ->where('slug', 'solicitante')
            ->where('estado', 1)
            ->exists();

        if (! $esRepresentanteLegal) {
            return;
        }

        $otraRepresentacion = Responsable::query()
            ->with('empresa:id,razon_social')
            ->where('id_persona', $idPersona)
            ->where('id_empresa', '!=', $idEmpresa)
            ->whereIn('estado', ['1', 'ACTIVO'])
            ->whereHas('rol', fn ($rol) => $rol
                ->where('slug', 'solicitante')
                ->where('estado', 1))
            ->first();

        if (! $otraRepresentacion) {
            return;
        }

        throw ValidationException::withMessages([
            'responsables' => 'La persona seleccionada ya es representante legal de '
                .($otraRepresentacion->empresa?->razon_social ?? 'otra empresa')
                .'. Para otra empresa debe utilizar una cuenta separada.',
        ]);
    }

    private function personaResponsableExistente(array $responsable): ?Persona
    {
        if (($responsable['tipo'] ?? null) !== 'EXISTENTE' || empty($responsable['id_persona'])) {
            return null;
        }

        return Persona::query()
            ->with('usuario')
            ->where('estado', 'ACTIVO')
            ->whereHas('natural', fn ($natural) => $natural
                ->whereNotNull('ci')
                ->where('ci', '<>', ''))
            ->find($responsable['id_persona']);
    }

    // Guarda el respaldo del responsable como documentos/responsables/nombre_unico.pdf.
    private function guardarRespaldoResponsable($archivo): string
    {
        $nombreArchivo = now()->format('YmdHis').'_'.uniqid().'.pdf';

        $ruta = $archivo->storeAs('documentos/responsables', $nombreArchivo, 'public');

        if (! $ruta) {
            throw new \RuntimeException('No se pudo guardar el PDF de respaldo en storage/app/public.');
        }

        // Si el servidor no tiene enlace public/storage, publicamos una copia para que el PDF se pueda visualizar.
        $rutaStorage = storage_path('app/public/'.$ruta);
        $rutaPublica = public_path('storage/'.$ruta);

        if (File::exists($rutaStorage) && ! File::exists($rutaPublica)) {
            File::ensureDirectoryExists(dirname($rutaPublica));

            if (! File::copy($rutaStorage, $rutaPublica)) {
                throw new \RuntimeException('El PDF se guardo, pero no se pudo publicar en public/storage para visualizarlo.');
            }
        }

        return $ruta;
    }

    // FUNCION PARA CONVERTIR TEXTO A MAYUSCULAS
    private function mayuscula(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }

        return mb_strtoupper(trim($texto), 'UTF-8');
    }

    /**
     * Display the specified resource.
     */
    public function show(Persona $persona)
    {
        $persona->load([
            'natural',
            'telefonos',
            'territorio',
            'rubros',
            // Cuenta de acceso: permite mostrar usuario, correo y roles asignados.
            'usuario.roles',
            // Carga productos cuando la persona actua como importador.
            // Esto ayuda a que el boton Ver muestre tambien la informacion relacionada del modulo productos.
            'productos.clasificacionToxicologica',
            'productos.fabricante',
            'productos.territorio',
            'productos.ingredientes',
            'productos.presentaciones',
            'productos.registros.presentacion',
            'productos.aduanas',
            // Tramites donde esta persona participa como beneficiario o tramitador.
            // Se cargan con registros para identificar productos y presentaciones asociadas.
            'certificadosComoBeneficiario.tipoCertificado',
            'certificadosComoBeneficiario.registros.producto',
            'certificadosComoBeneficiario.registros.presentacion',
            'certificadosComoTramitador.tipoCertificado',
            'certificadosComoTramitador.registros.producto',
            'certificadosComoTramitador.registros.presentacion',
            'empresa.tipoEmpresa',
            // Carga responsables con todos sus datos de persona para mostrar una ficha completa.
            'empresa.responsables.persona.natural',
            'empresa.responsables.persona.usuario',
            'empresa.responsables.persona.territorio',
            'empresa.responsables.persona.telefonos',
            'empresa.responsables.persona.rubros',
            'empresa.responsables.rol',
        ]);

        return view('personas.show', compact('persona'));
    }

    /**
     * Muestra el formulario con los datos actuales de la persona.
     */
    public function edit(Persona $persona)
    {
        $persona->load([
            'natural',
            'telefonos',
            'territorio',
            'rubros',
            'usuario.roles',
            'empresa.tipoEmpresa',
            'empresa.responsables.persona.natural',
            'empresa.responsables.persona.usuario.roles',
            'empresa.responsables.persona.territorio',
            'empresa.responsables.persona.telefonos',
            'empresa.responsables.persona.rubros',
            'empresa.responsables.rol',
        ]);

        $territorios = Territorio::all();
        $paises = Territorio::where('id_ambito', 1)->orderBy('nombre')->get();
        $departamentos = Territorio::where('id_ambito', 2)->orderBy('nombre')->get();
        $tiposEmpresas = TipoEmpresa::all();
        $rubrosCatalogo = $this->catalogoRubrosCaeb();
        $ocupacionesCob = OcupacionCob::orderBy('codigo_ocupacion')->get();
        $expedidosNatural = Natural::EXPEDIDOS;
        $personas = Persona::with([
            'natural',
            'telefonos',
            'territorio',
            'rubros' => fn ($query) => $query->where('rubros.nivel_caeb', 'SUBCLASE'),
        ])
            ->where('id', '!=', $persona->id)
            // En edicion tambien se filtra el catalogo para que solo se elijan responsables validos.
            ->whereHas('natural', fn ($consultaNatural) => $consultaNatural
                ->whereNotNull('ci')
                ->where('ci', '!=', ''))
            ->orderBy('id')
            ->get();

        $telefonosRegistrados = $persona->telefonos
            ->map(fn ($telefono) => [
                'id' => $telefono->id,
                'numero' => $telefono->numero,
                'estado' => $telefono->estado,
            ])
            ->values();

        $rubrosRegistrados = $persona->rubros
            ->filter(fn ($rubro) => $rubro->nivel_caeb === 'SUBCLASE')
            ->pluck('id')
            ->values();

        $relacionesEmpresa = $persona->empresa?->responsables ?? collect();
        $relacionesRepresentante = $this->relacionesRepresentanteLegal($relacionesEmpresa);

        $relacionCuenta = $relacionesRepresentante
            ->sortByDesc(fn ($responsable) => (strtoupper((string) $responsable->estado) === 'ACTIVO' ? 1000000000 : 0)
                + (int) $responsable->id
            )
            ->first();
        $usuarioCuentaAcceso = $relacionCuenta?->persona?->usuario ?: $persona->usuario;
        $rolSolicitante = $usuarioCuentaAcceso?->roles->first();
        $rolRepresentanteLegal = $relacionCuenta?->rol
            ?: Role::query()
                ->where('slug', 'solicitante')
                ->where('estado', 1)
                ->whereNull('deleted_at')
                ->first();

        $responsablesRegistrados = $persona->empresa
            ? $relacionesRepresentante
                ->sortByDesc(fn ($responsable) => (strtoupper((string) $responsable->estado) === 'ACTIVO' ? 1000000000 : 0)
                    + (int) $responsable->id
                )
                ->take(1)
                ->map(fn ($responsable) => [
                    'tipo' => 'EXISTENTE',
                    'id_persona' => $responsable->id_persona,
                    'domicilio' => $responsable->persona?->domicilio,
                    'nit' => $responsable->persona?->nit,
                    'correo' => $responsable->persona?->correo,
                    'id_territorio' => $responsable->persona?->id_territorio,
                    'territorio_nombre' => $responsable->persona?->territorio?->nombre,
                    'nombres' => $responsable->persona?->natural?->nombres,
                    'apellido_paterno' => $responsable->persona?->natural?->apellido_paterno,
                    'apellido_materno' => $responsable->persona?->natural?->apellido_materno,
                    'apellido_casado' => $responsable->persona?->natural?->apellido_casado,
                    'ci' => $responsable->persona?->natural?->ci,
                    'complemento' => $responsable->persona?->natural?->complemento,
                    'expedido' => $responsable->persona?->natural?->expedido,
                    'fecha_nacimiento' => $responsable->persona?->natural?->fecha_nacimiento,
                    'genero' => $responsable->persona?->natural?->genero,
                    'id_ocupacion' => $responsable->persona?->natural?->id_ocupacion,
                    'ocupacion' => $responsable->persona?->natural?->ocupacion,
                    'nombre_completo' => trim(implode(' ', array_filter([
                        $responsable->persona?->natural?->nombres,
                        $responsable->persona?->natural?->apellido_paterno,
                        $responsable->persona?->natural?->apellido_materno,
                    ]))),
                    'id_rol' => $responsable->id_rol,
                    'rol_nombre' => $responsable->rol?->name,
                    'url_respaldo' => $responsable->url_respaldo,
                    'estado' => $responsable->estado,
                    'telefonos' => $responsable->persona?->telefonos
                        ? $responsable->persona->telefonos->map(fn ($telefono) => [
                            'id' => $telefono->id,
                            'numero' => $telefono->numero,
                            'estado' => $telefono->estado,
                        ])->values()
                        : [],
                    'rubros' => $responsable->persona?->rubros
                        ? $responsable->persona->rubros
                            ->filter(fn ($rubro) => $rubro->nivel_caeb === 'SUBCLASE')
                            ->map(fn ($rubro) => [
                                'id' => $rubro->id,
                                'codigo_caeb' => $rubro->codigo_caeb,
                                'nombre' => $rubro->nombre,
                                'estado' => $rubro->estado,
                            ])->values()
                        : [],
                ])
                ->values()
            : collect();

        return view('personas.edit', compact(
            'persona',
            'territorios',
            'paises',
            'departamentos',
            'tiposEmpresas',
            'personas',
            'telefonosRegistrados',
            'rubrosRegistrados',
            'responsablesRegistrados',
            'rolSolicitante',
            'rolRepresentanteLegal',
            'usuarioCuentaAcceso',
            'rubrosCatalogo',
            'ocupacionesCob',
            'expedidosNatural'
        ));
    }

    /**
     * Actualiza la persona y sus relaciones dentro de una sola transaccion.
     */
    public function update(Request $solicitud, Persona $persona)
    {
        $this->usarPaisComoTerritorioSiNoHayDepartamento($solicitud);
        $persona->loadMissing([
            'natural',
            'usuario.roles',
            'empresa.responsables.persona.natural',
            'empresa.responsables.persona.usuario.roles',
            'empresa.responsables.rol',
        ]);
        $relacionesEmpresa = $persona->empresa?->responsables ?? collect();
        $responsableActivoAnterior = $this->relacionesRepresentanteLegal($relacionesEmpresa)
            ->first(fn ($responsable) => strtoupper((string) $responsable->estado) === 'ACTIVO');
        $usuarioCuentaAnterior = $responsableActivoAnterior?->persona?->usuario ?: $persona->usuario;
        $rolSolicitante = $usuarioCuentaAnterior?->roles->first()
            ?: Role::query()
                ->where('slug', 'solicitante')
                ->where('estado', 1)
                ->whereNull('deleted_at')
                ->first();

        if (! $rolSolicitante) {
            throw ValidationException::withMessages([
                'form_id_role' => 'La cuenta no tiene un rol de acceso disponible.',
            ]);
        }
        $rolRepresentanteLegal = $solicitud->input('form_tipo_registro') === 'EMPRESA'
            ? $this->rolRepresentanteLegalActivo()
            : $rolSolicitante;
        $this->fijarRolesEmpresaEnSolicitud($solicitud, $rolSolicitante, $rolRepresentanteLegal);
        $responsableSolicitado = $solicitud->input('responsables.0', []);
        $personaResponsableSolicitada = $this->personaResponsableExistente($responsableSolicitado);
        $mismoResponsable = $personaResponsableSolicitada
            && (int) $personaResponsableSolicitada->id === (int) $responsableActivoAnterior?->id_persona;
        $usuarioCuentaDestino = $solicitud->input('form_tipo_registro') === 'EMPRESA'
            ? ($personaResponsableSolicitada?->usuario ?: ($mismoResponsable ? $usuarioCuentaAnterior : null))
            : $persona->usuario;

        try {
            $datos = $solicitud->validate(
                $this->reglasValidacionPersona(
                    $rolSolicitante,
                    $rolRepresentanteLegal,
                    personaEditada: $persona,
                    usuarioCuenta: $usuarioCuentaDestino
                ),
                $this->mensajesValidacionPersona(),
                $this->atributosValidacionPersona()
            );

            // Evita errores SQL tambien al editar responsables nuevos de empresa.
            $this->validarResponsablesAntesDeGuardar($datos['responsables'] ?? []);
            $this->validarTerritoriosResponsables($datos['responsables'] ?? []);
            $this->validarTerritorioPrincipal($datos['form_id_pais'], $datos['form_id_territorio']);

            $estadoPersona = strtoupper($datos['form_estado'] ?? $persona->estado ?? 'ACTIVO');

            // Antes de inactivar se comprueban las mismas relaciones usadas por el boton Eliminar.
            if ($estadoPersona === 'INACTIVO' && strtoupper((string) $persona->estado) !== 'INACTIVO') {
                $bloqueosInactivacion = $this->obtenerBloqueosEliminacionPersona($persona);

                if (! empty($bloqueosInactivacion)) {
                    throw ValidationException::withMessages([
                        'form_estado' => 'No se puede cambiar el estado porque el registro está relacionado con otros datos.',
                    ]);
                }
            }
        } catch (ValidationException $e) {
            // Mantiene los PDF agregados desde el modal si la edicion vuelve por errores.
            // Sin esto, el navegador vacia el input file y el usuario tendria que adjuntarlo otra vez.
            $this->preservarRespaldosResponsablesEnInput($solicitud);

            throw $e;
        }

        $responsableEnviado = $datos['responsables'][0] ?? null;
        $responsableActivo = $datos['form_tipo_registro'] !== 'EMPRESA'
            || strtoupper($responsableEnviado['estado'] ?? 'ACTIVO') === 'ACTIVO';

        try {
            DB::beginTransaction();

            $usuarioAcceso = $datos['form_tipo_registro'] === 'EMPRESA'
                ? ($usuarioCuentaDestino ?: new User)
                : ($persona->usuario ?: new User);
            [$usuarioAcceso, $passwordGeneradaCuenta] = $this->guardarCuentaAcceso(
                $usuarioAcceso,
                $datos,
                $estadoPersona === 'ACTIVO' && $responsableActivo
            );

            if (! $responsableActivo || $estadoPersona === 'INACTIVO') {
                $this->cerrarAccesosUsuario($usuarioAcceso);
            }

            $persona->update([
                'id_usuario' => $datos['form_tipo_registro'] === 'NATURAL'
                    ? $usuarioAcceso->id
                    : null,
                'domicilio' => $datos['form_domicilio'] ?? null,
                'nit' => $datos['form_nit'] ?? null,
                'correo' => $datos['form_correo'],
                'id_territorio' => $datos['form_id_territorio'],
                'estado' => $estadoPersona,
            ]);

            $this->sincronizarTelefonosPersona($persona->id, $datos['telefonos'] ?? []);
            $this->registrarRubrosPersona($persona->id, $datos['rubros'] ?? []);

            if ($datos['form_tipo_registro'] === 'NATURAL') {
                // Si antes era empresa, se eliminan sus datos incompatibles.
                if ($persona->empresa) {
                    Responsable::where('id_empresa', $persona->empresa->id)->delete();
                    $persona->empresa->delete();

                    if ($usuarioCuentaAnterior && $usuarioCuentaAnterior->isNot($usuarioAcceso)) {
                        $usuarioCuentaAnterior->update(['estado' => 0]);
                        $this->cerrarAccesosUsuario($usuarioCuentaAnterior);
                    }
                }

                Natural::updateOrCreate(
                    ['id_persona' => $persona->id],
                    [
                        'id_ocupacion' => $datos['form_id_ocupacion'] ?? null,
                        'ci' => $datos['form_ci'] ?? null,
                        'complemento' => $datos['form_complemento'] ?? null,
                        'expedido' => $datos['form_expedido'] ?? null,
                        'nombres' => $this->mayuscula($datos['form_nombres'] ?? null),
                        'apellido_paterno' => $this->mayuscula($datos['form_apellido_paterno'] ?? null),
                        'apellido_materno' => $this->mayuscula($datos['form_apellido_materno'] ?? null),
                        'apellido_casado' => $this->mayuscula($datos['form_apellido_casado'] ?? null),
                        'fecha_nacimiento' => $datos['form_fecha_nacimiento'] ?? null,
                        'genero' => $datos['form_genero'],
                        'ocupacion' => $this->descripcionOcupacionCob($datos['form_id_ocupacion'] ?? null),
                    ]
                );
            }

            if ($datos['form_tipo_registro'] === 'EMPRESA') {
                // Si antes era natural, se eliminan sus datos incompatibles.
                if ($persona->natural) {
                    $persona->natural->delete();
                }

                $empresa = Empresa::updateOrCreate(
                    ['id_persona' => $persona->id],
                    [
                        'id_tipo_empresa' => $datos['form_id_tipo_empresa'],
                        'razon_social' => $datos['form_razon_social'],
                        'matricula' => $datos['form_matricula'],
                        'latitud' => $datos['form_latitud'] ?? null,
                        'longitud' => $datos['form_longitud'] ?? null,
                        'estado' => $estadoPersona === 'INACTIVO'
                            ? 'INACTIVO'
                            : (! empty($datos['form_estado_empresa']) ? $datos['form_estado_empresa'] : 'ACTIVO'),
                    ]
                );

                // Solo se reemplaza la representacion legal; los tramitadores conservan su estado.
                Responsable::where('id_empresa', $empresa->id)
                    ->whereIn('id_rol', [$rolRepresentanteLegal->id, $rolSolicitante->id])
                    ->where('estado', '!=', 'INACTIVO')
                    ->update([
                        'estado' => 'INACTIVO',
                        'fecha_baja' => now()->toDateString(),
                    ]);

                $this->registrarResponsablesEmpresa(
                    $empresa->id,
                    $datos['responsables'] ?? [],
                    [],
                    $usuarioAcceso->id
                );

                if ($usuarioCuentaAnterior && $usuarioCuentaAnterior->isNot($usuarioAcceso)) {
                    $mantieneOtraAutorizacion = $responsableActivoAnterior
                        && Responsable::where('id_persona', $responsableActivoAnterior->id_persona)
                            ->where('estado', 'ACTIVO')
                            ->exists();

                    if (! $mantieneOtraAutorizacion) {
                        $usuarioCuentaAnterior->update(['estado' => 0]);
                        $this->cerrarAccesosUsuario($usuarioCuentaAnterior);
                    }
                }
            }

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => $passwordGeneradaCuenta
                    ? 'El registro se actualizo correctamente. Contrasena generada: '.$passwordGeneradaCuenta
                    : 'El registro se actualizo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('personas_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar. '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Crea o actualiza la cuenta que utilizara la persona solicitante.
     *
     * @return array{0: User, 1: ?string}
     */
    private function guardarCuentaAcceso(User $usuario, array $datos, bool $activa): array
    {
        $usuario->name = $datos['form_usuario_name'];
        $usuario->email = $datos['form_usuario_email'];
        $usuario->estado = $activa ? 1 : 0;
        $passwordGenerada = null;

        if (! $usuario->exists || ! empty($datos['form_usuario_password'])) {
            $password = $datos['form_usuario_password'] ?: $this->generarPasswordTemporalCuenta();
            $usuario->password = $password;

            if (empty($datos['form_usuario_password'])) {
                $passwordGenerada = $password;
            }
        }

        $usuario->save();
        $usuario->roles()->sync([$datos['form_id_role']]);

        return [$usuario, $passwordGenerada];
    }

    private function generarPasswordTemporalCuenta(): string
    {
        return Str::random(10);
    }

    // Revoca las sesiones cuando cambia o se inactiva quien utiliza la cuenta de la empresa.
    private function cerrarAccesosUsuario(User $usuario): void
    {
        $usuario->forceFill(['remember_token' => Str::random(60)])->save();
        $usuario->tokens()->delete();

        if (config('session.driver') === 'database') {
            $tablaSesiones = config('session.table', 'sessions');

            if (Schema::hasTable($tablaSesiones)) {
                DB::table($tablaSesiones)->where('user_id', $usuario->id)->delete();
            }
        }
    }

    /**
     * Elimina logicamente al solicitante sin borrar su historial.
     */
    public function destroy(Persona $persona)
    {
        try {
            DB::beginTransaction();

            $persona->load([
                'natural',
                'empresa.responsables.persona.usuario',
                'telefonos',
                'rubros',
                'usuario.roles',
                'usuario.permisosDirectos',
            ]);
            $bloqueosEliminacion = $this->obtenerBloqueosEliminacionPersona($persona);

            if (! empty($bloqueosEliminacion)) {
                DB::rollBack();

                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text' => 'Este registro tiene relaciones activas: '.implode(' ', $bloqueosEliminacion),
                    'icon' => 'warning',
                ]);

                return redirect()->route('personas_index');
            }

            $this->eliminarSolicitanteLogicamente($persona);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El registro se eliminó correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('personas_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo eliminar. '.$e->getMessage());
        }
    }

    /**
     * Revisa relaciones operativas antes de eliminar una persona o empresa.
     *
     * Los datos propios de la ficha como natural, empresa, telefonos, rubros y usuario
     * no bloquean porque pertenecen al mismo registro. Se bloquea solamente cuando
     * otra tabla de la base de datos usa esta persona o su empresa.
     */
    private function obtenerBloqueosEliminacionPersona(Persona $persona): array
    {
        $bloqueos = [];

        $productos = $persona->productos()->count();
        if ($productos > 0) {
            $bloqueos[] = "{$productos} producto(s) como importador.";
        }

        $certificadosBeneficiario = $persona->certificadosComoBeneficiario()->count();
        if ($certificadosBeneficiario > 0) {
            $bloqueos[] = "{$certificadosBeneficiario} tramite(s) como beneficiario.";
        }

        $certificadosTramitador = $persona->certificadosComoTramitador()->count();
        if ($certificadosTramitador > 0) {
            $bloqueos[] = "{$certificadosTramitador} tramite(s) como tramitador.";
        }

        // pagos.id_cliente apunta directamente a personas.id.
        $pagos = Pago::where('id_cliente', $persona->id)->count();
        if ($pagos > 0) {
            $bloqueos[] = "{$pagos} pago(s) registrados.";
        }

        // responsables.id_persona apunta a la persona natural que actua como responsable.
        $responsableEnEmpresas = Responsable::where('id_persona', $persona->id)->count();
        if ($responsableEnEmpresas > 0) {
            $bloqueos[] = "{$responsableEnEmpresas} relacion(es) como responsable de empresa.";
        }

        return $bloqueos;
    }

    /**
     * Elimina la ficha completa mediante deleted_at y conserva sus datos para auditoria.
     * Las cuentas de otras personas no se eliminan cuando pertenecen a la empresa.
     */
    private function eliminarSolicitanteLogicamente(Persona $persona): void
    {
        $usuarioPropio = $persona->usuario;
        $empresa = $persona->empresa;
        $usuariosRelacionados = $empresa
            ? $empresa->responsables
                ->pluck('persona.usuario')
                ->filter()
                ->unique('id')
            : collect();

        $persona->update(['estado' => 'INACTIVO']);

        // Los telefonos son parte de la ficha y no deben quedar activos al eliminarla.
        foreach ($persona->telefonos as $telefono) {
            $telefono->update(['estado' => 'INACTIVO']);
            $telefono->delete();
        }

        // La tabla pivote no usa deleted_at; se conserva y se marca como inactiva.
        DB::table('personas_rubros')
            ->where('id_persona', $persona->id)
            ->update(['estado' => 'INACTIVO']);

        if ($persona->natural) {
            $persona->natural->delete();
        }

        if ($empresa) {
            $empresa->update(['estado' => 'INACTIVO']);

            foreach ($empresa->responsables as $responsable) {
                $responsable->update([
                    'estado' => 'INACTIVO',
                    'fecha_baja' => now()->toDateString(),
                    'id_usuario_baja' => auth()->id(),
                ]);
                $responsable->delete();
            }

            $empresa->delete();
        }

        $persona->delete();

        // La cuenta propia desaparece con la persona natural o con una cuenta empresarial antigua.
        if ($usuarioPropio) {
            $usuarioPropio->update(['estado' => 0]);
            $this->cerrarAccesosUsuario($usuarioPropio);
            $usuarioPropio->delete();
        }

        // Un representante o tramitador conserva su cuenta si todavia representa otra empresa activa.
        foreach ($usuariosRelacionados as $usuarioRelacionado) {
            if ($usuarioPropio && $usuarioRelacionado->is($usuarioPropio)) {
                continue;
            }

            $mantieneOtraEmpresa = Responsable::query()
                ->where('id_persona', $usuarioRelacionado->persona?->id)
                ->whereIn('estado', ['1', 'ACTIVO'])
                ->whereHas('empresa', fn ($consulta) => $consulta
                    ->whereIn('estado', ['1', 'ACTIVO']))
                ->exists();

            if (! $mantieneOtraEmpresa) {
                $usuarioRelacionado->update(['estado' => 0]);
                $this->cerrarAccesosUsuario($usuarioRelacionado);
            }
        }
    }
}
