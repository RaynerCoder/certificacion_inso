<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Territorio;
use App\Models\Empresa;
use App\Models\TipoEmpresa;
use App\Models\Natural;
use App\Models\OcupacionCob;
use App\Models\Telefono;
use App\Models\Rubro;
use App\Models\User;
use App\Models\Role;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Responsable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PersonaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $personas = Persona::with([
            'natural',
            'telefonos',
            'territorio',
            'rubros',
            'empresa.tipoEmpresa',
            'empresa.responsables.persona.natural'
        ])->latest()->get();

        //dd($personas->all());
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
            'file' => 'El campo :attribute debe ser un archivo válido.',
            'mimes' => 'El archivo de :attribute debe ser de tipo: :values.',
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $territorios = Territorio::all();
        $paises = Territorio::where('id_ambito', 1)->orderBy('nombre')->get();
        $departamentos = Territorio::where('id_ambito', 2)->orderBy('nombre')->get();
        $tiposEmpresas = TipoEmpresa::all();
        $rolesCuentaCatalogo = Role::where('estado', 1)->orderBy('name')->get();
        $rolesResponsablesCatalogo = Role::where('estado', 1)->orderBy('name')->get();
        $rubrosCatalogo = Rubro::where('estado', 'ACTIVO')->orderBy('nombre')->get();
        $ocupacionesCob = OcupacionCob::orderBy('codigo_ocupacion')->get();
        $expedidosNatural = Natural::EXPEDIDOS;
        $personas = Persona::with([
            'natural',
            'telefonos',
            'territorio',
            'rubros'
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
            'rolesCuentaCatalogo' => $rolesCuentaCatalogo,
            'rolesResponsablesCatalogo' => $rolesResponsablesCatalogo,
            'rubrosCatalogo' => $rubrosCatalogo,
            'ocupacionesCob' => $ocupacionesCob,
            'expedidosNatural' => $expedidosNatural,
        ]);
    }

    /**
     * Store a newly created resource in storage.
    */
    public function store(Request $solicitud)
    {
        $this->usarPaisComoTerritorioSiNoHayDepartamento($solicitud);

        try {
            $datos = $solicitud->validate([
            'form_tipo_registro' => 'required|in:NATURAL,EMPRESA',

            // PERSONA GENERAL
            'form_domicilio'     => 'nullable|string|max:255',
            'form_nit'           => 'required_if:form_tipo_registro,EMPRESA|nullable|string|max:50|unique:personas,nit',
            'form_correo'        => 'required|email|max:50|unique:personas,correo',
            'form_id_pais'       => [
                'required',
                Rule::exists('territorios', 'id')->where(fn ($query) => $query->where('id_ambito', 1)),
            ],
            'form_id_territorio' => 'required|exists:territorios,id',
            'form_estado'        => 'nullable|string|max:50',

            // NATURAL
            'form_ci'               => 'required_if:form_tipo_registro,NATURAL|nullable|string|max:50|unique:naturals,ci',
            'form_complemento'      => 'nullable|string|max:10',
            'form_expedido'         => ['nullable', Rule::in(array_keys(Natural::EXPEDIDOS))],
            'form_nombres'          => 'required_if:form_tipo_registro,NATURAL|nullable|string|max:100',
            'form_apellido_paterno' => 'required_if:form_tipo_registro,NATURAL|nullable|string|max:100',
            'form_apellido_materno' => 'nullable|string|max:100',
            'form_apellido_casado'  => 'nullable|string|max:100',
            'form_fecha_nacimiento' => 'nullable|date',
            'form_genero'           => 'required_if:form_tipo_registro,NATURAL|nullable',
            'form_id_ocupacion'     => 'nullable|exists:ocupaciones_cob,id',

            // EMPRESA
            'form_id_tipo_empresa'       => 'required_if:form_tipo_registro,EMPRESA|nullable|exists:tipos_empresas,id',
            'form_razon_social'          => 'required_if:form_tipo_registro,EMPRESA|nullable|string|max:255',
            'form_matricula'             => 'required_if:form_tipo_registro,EMPRESA|nullable|string|max:50',
            'form_latitud'               => 'nullable',
            'form_longitud'              => 'nullable',
            'form_estado_empresa'        => 'nullable|max:50',

            // TELÉFONOS PERSONA PRINCIPAL
            'telefonos'          => 'nullable|array',
            'telefonos.*.numero' => 'required|string|max:50',
            'telefonos.*.tipo'   => 'nullable|max:50',

            // RUBROS PERSONA / EMPRESA
            'rubros'   => 'nullable|array',
            'rubros.*' => 'integer|exists:rubros,id',

            // RESPONSABLES EMPRESA
            'responsables'                     => 'nullable|array',
            'responsables.*.tipo'              => 'required|in:NUEVO,EXISTENTE',
            'responsables.*.id_persona'        => 'nullable|exists:personas,id',
            'responsables.*.domicilio'         => 'nullable|string|max:255',
            'responsables.*.nit'               => 'nullable|string|max:50|unique:personas,nit',
            'responsables.*.correo'            => 'nullable|email|max:50',
            'responsables.*.id_territorio'     => 'nullable|exists:territorios,id',
            'responsables.*.nombres'           => 'nullable|string|max:100',
            'responsables.*.apellido_paterno'  => 'nullable|string|max:100',
            'responsables.*.apellido_materno'  => 'nullable|string|max:100',
            'responsables.*.apellido_casado'   => 'nullable|string|max:100',
            'responsables.*.ci'                => 'nullable|string|max:50',
            'responsables.*.complemento'       => 'nullable|string|max:10',
            'responsables.*.expedido'          => 'nullable|string|max:10',
            'responsables.*.fecha_nacimiento'  => 'nullable|date',
            'responsables.*.genero'            => 'nullable',
            'responsables.*.ocupacion'         => 'nullable|string|max:255',
            'responsables.*.telefonos'         => 'nullable|array',
            'responsables.*.telefonos.*.numero'=> 'required|string|max:50',
            'responsables.*.telefonos.*.tipo'  => 'nullable|max:50',
            'responsables.*.rubros'            => 'nullable|array',
            'responsables.*.rubros.*.nombre'   => 'required|string|max:255',
            'responsables.*.rubros.*.estado'   => 'nullable|string|max:50',
            // Datos del responsable
            'responsables.*.id_rol'            => 'required_if:form_tipo_registro,EMPRESA|nullable|exists:roles,id',
            'responsables.*.url_respaldo'      => 'nullable|string|max:255',
            'responsables.*.archivo_respaldo'  => 'nullable|file|mimes:pdf|max:5120',
            'responsables.*.fecha_registro'    => 'nullable|date',
            'responsables.*.fecha_baja'        => 'nullable|date',
            'responsables.*.estado'            => 'nullable|string|max:50',

            // CUENTA DE USUARIO PARA QUE LA PERSONA/EMPRESA PUEDA INICIAR SESION
            'form_usuario_name' => 'required|string|max:255|unique:users,name',
            'form_usuario_email' => 'required|email|max:255|unique:users,email',
            'form_usuario_password' => 'nullable|string|min:8',
            'form_id_role' => [
                'required',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('estado', 1)),
            ],
            ], $this->mensajesValidacionPersona(), [
                // Nombres legibles: evitan mostrar campos tecnicos como form_id_territorio al usuario.
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
            ]);

            // Evita errores SQL cuando se registra un responsable nuevo sin datos obligatorios de persona.
            $this->validarResponsablesAntesDeGuardar($datos['responsables'] ?? []);
            $this->validarTerritorioPrincipal($datos['form_id_pais'], $datos['form_id_territorio']);
        } catch (ValidationException $e) {
            // Si falla un campo externo al modal, guardamos los PDF validos ya seleccionados.
            // Esto evita que el responsable agregado pierda su respaldo al volver con errores.
            $this->preservarRespaldosResponsablesEnInput($solicitud);

            throw $e;
        }

        try {
            DB::beginTransaction();

            $passwordGeneradaCuenta = null;
            $passwordCuenta = $datos['form_usuario_password'] ?: $this->generarPasswordTemporalCuenta();

            if (empty($datos['form_usuario_password'])) {
                $passwordGeneradaCuenta = $passwordCuenta;
            }

            // La cuenta se crea primero para enlazarla a personas.id_usuario.
            $usuarioAcceso = User::create([
                'name' => $datos['form_usuario_name'],
                'email' => $datos['form_usuario_email'],
                'password' => $passwordCuenta,
                'estado' => 1,
            ]);

            // Asigna el rol elegido para la cuenta de acceso de esta persona/empresa.
            $usuarioAcceso->roles()->sync([$datos['form_id_role']]);

            $persona = Persona::create([
                'id_usuario'          => $usuarioAcceso->id,
                'domicilio'           => $datos['form_domicilio'] ?? null,
                'nit'                 => $datos['form_nit'] ?? null,
                'correo'              => $datos['form_correo'],
                'id_territorio'       => $datos['form_id_territorio'],
                'estado'              => $datos['form_estado'] ?? 'ACTIVO',
            ]);

            if (!empty($datos['telefonos'])) {
                foreach ($datos['telefonos'] as $telefono) {
                    Telefono::create([
                        'id_persona' => $persona->id,
                        'numero'     => $telefono['numero'],
                        'estado'     => $telefono['tipo'] ?? 'CELULAR',
                    ]);
                }
            }

            $this->registrarRubrosPersona($persona->id, $datos['rubros'] ?? []);

            if ($datos['form_tipo_registro'] === 'NATURAL') {
                Natural::create([
                    'id_persona'       => $persona->id,
                    'id_ocupacion'     => $datos['form_id_ocupacion'] ?? null,
                    'ci'               => $datos['form_ci'] ?? null,
                    'complemento'      => $datos['form_complemento'] ?? null,
                    'expedido'         => $datos['form_expedido'] ?? null,
                    'nombres'          => $this->mayuscula($datos['form_nombres'] ?? null),
                    'apellido_paterno' => $this->mayuscula($datos['form_apellido_paterno'] ?? null),
                    'apellido_materno' => $this->mayuscula($datos['form_apellido_materno'] ?? null),
                    'apellido_casado'  => $this->mayuscula($datos['form_apellido_casado'] ?? null),
                    'fecha_nacimiento' => $datos['form_fecha_nacimiento'] ?? null,
                    'genero'           => $datos['form_genero'],
                    'ocupacion'        => $this->descripcionOcupacionCob($datos['form_id_ocupacion'] ?? null),
                ]);
            }

            if ($datos['form_tipo_registro'] === 'EMPRESA') {
                $empresa = Empresa::create([
                    'id_persona'      => $persona->id,
                    'id_tipo_empresa' => $datos['form_id_tipo_empresa'],
                    'razon_social'    => $datos['form_razon_social'],
                    'matricula'       => $datos['form_matricula'],
                    'latitud'         => $datos['form_latitud'] ?? null,
                    'longitud'        => $datos['form_longitud'] ?? null,
                    'estado'          => !empty($datos['form_estado_empresa']) ? $datos['form_estado_empresa'] : 'ACTIVO',
                ]);

                $this->registrarResponsablesEmpresa($empresa->id, $datos['responsables'] ?? []);
            }

            DB::commit();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text'  => $passwordGeneradaCuenta
                    ? 'El registro se guardo correctamente. Contrasena generada: ' . $passwordGeneradaCuenta
                    : 'El registro se guardo correctamente.',
                'icon'  => 'success'
            ]);

            return redirect()->route('personas_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar. ' . $e->getMessage())
                ->withInput();
        }
    }   


    // FUNCION PARA REGISTRAR TELEFONOS DE PERSONAS (NATURAL O JURIDICA-EMPRESA)
    private function registrarTelefonosPersona($idPersona, array $telefonos): void
    {
        foreach ($telefonos as $telefono) {
            Telefono::create([
                'id_persona' => $idPersona,
                'numero'     => $telefono['numero'],
                'estado'     => $telefono['tipo'] ?? 'CELULAR',
            ]);
        }
    }


    // Sincroniza rubros del catalogo con una persona o empresa sin duplicar registros.
    private function registrarRubrosPersona($idPersona, array $rubros): void
    {
        $idsRubros = collect($rubros)
            ->map(function ($rubro) {
                if (is_array($rubro)) {
                    if (!empty($rubro['id']) || !empty($rubro['id_rubro'])) {
                        return $rubro['id'] ?? $rubro['id_rubro'];
                    }

                    if (!empty($rubro['nombre'])) {
                        return Rubro::firstOrCreate(
                            ['nombre' => $this->mayuscula($rubro['nombre'])],
                            [
                                'descripcion' => null,
                                'estado' => $rubro['estado'] ?? 'ACTIVO',
                            ]
                        )->id;
                    }

                    return null;
                }

                return $rubro;
            })
            ->filter()
            ->unique()
            ->values();

        $datosSync = $idsRubros
            ->mapWithKeys(fn ($idRubro) => [(int) $idRubro => ['estado' => 'ACTIVO']])
            ->all();

        Persona::find($idPersona)?->rubros()->sync($datosSync);
    }

    private function descripcionOcupacionCob($idOcupacion): ?string
    {
        if (!$idOcupacion) {
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

            if (!$archivo || !$archivo->isValid()) {
                continue;
            }

            // Solo preservamos PDF validos y dentro del limite permitido.
            // Esto evita perder el respaldo cuando el error esta en otro campo del formulario.
            $esPdf = strtolower($archivo->getClientOriginalExtension()) === 'pdf';
            $pesoPermitido = $archivo->getSize() <= 5120 * 1024;

            if (!$esPdf || !$pesoPermitido) {
                continue;
            }

            $responsables[$indice]['url_respaldo'] = $this->guardarRespaldoResponsable($archivo);
        }

        $solicitud->merge([
            'responsables' => $responsables,
        ]);
    }

    // VALIDA RESPONSABLES NUEVOS ANTES DE CREAR PERSONAS PARA EVITAR ERRORES SQL
    private function validarResponsablesAntesDeGuardar(array $responsables): void
    {
        $errores = [];
        $personasExistentesAgregadas = [];
        $cisAgregados = [];

        foreach ($responsables as $indice => $responsable) {
            $tipo = $responsable['tipo'] ?? null;
            $idPersonaResponsable = trim((string) ($responsable['id_persona'] ?? ''));
            $ciResponsable = strtolower(trim((string) ($responsable['ci'] ?? '')));

            if ($tipo === 'EXISTENTE' && empty($idPersonaResponsable)) {
                $errores["responsables.$indice.id_persona"] = 'Seleccione la persona responsable.';
            } elseif ($tipo === 'EXISTENTE') {
                // No permite repetir a la misma persona existente como responsable de la empresa.
                if (in_array($idPersonaResponsable, $personasExistentesAgregadas, true)) {
                    $errores["responsables.$indice.id_persona"] = 'Esta persona ya fue agregada como responsable.';
                }

                $personasExistentesAgregadas[] = $idPersonaResponsable;
            }

            if ($ciResponsable !== '') {
                // Tambien se controla el CI para responsables nuevos o datos editados desde el modal.
                if (in_array($ciResponsable, $cisAgregados, true)) {
                    $errores["responsables.$indice.ci"] = 'Este CI ya fue agregado como responsable.';
                }

                $cisAgregados[] = $ciResponsable;
            }

            if ($tipo !== 'NUEVO') {
                continue;
            }

            if (empty($responsable['correo'])) {
                $errores["responsables.$indice.correo"] = 'Ingrese el correo del responsable.';
            } elseif (Persona::where('correo', $responsable['correo'])->exists()) {
                $errores["responsables.$indice.correo"] = 'El correo del responsable ya esta registrado.';
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

            if (empty($responsable['ci'])) {
                $errores["responsables.$indice.ci"] = 'Ingrese el CI del responsable.';
            } elseif (Natural::where('ci', $responsable['ci'])->exists()) {
                $errores["responsables.$indice.ci"] = 'El CI del responsable ya esta registrado.';
            }
        }

        if (! empty($errores)) {
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


    // FUNCION PARA REGISTRAR RESPONSABLES DE EMPRESAS
    private function registrarResponsablesEmpresa($idEmpresa, array $responsables)
    {
        if (empty($responsables)) {
            return;
        }

        foreach ($responsables as $responsable) {

            // RESPONSABLE EXISTENTE
            if ($responsable['tipo'] === 'EXISTENTE') {

                $idPersonaResponsable = $responsable['id_persona'];
                $personaResponsable = Persona::find($idPersonaResponsable);

                // El responsable existente es otro registro de persona.
                // Si se edita desde el modal, se actualiza su ficha propia y no la empresa.
                if ($personaResponsable) {
                    $personaResponsable->update([
                        'domicilio'     => $responsable['domicilio'] ?? null,
                        'nit'           => $responsable['nit'] ?? null,
                        'correo'        => $responsable['correo'] ?? null,
                        'id_territorio' => $responsable['id_territorio'] ?? null,
                    ]);

                    Natural::updateOrCreate(
                        ['id_persona' => $personaResponsable->id],
                        [
                            'ci'               => $responsable['ci'] ?? null,
                            'complemento'      => $responsable['complemento'] ?? null,
                            'expedido'         => $responsable['expedido'] ?? null,
                            'nombres'          => $this->mayuscula($responsable['nombres'] ?? null),
                            'apellido_paterno' => $this->mayuscula($responsable['apellido_paterno'] ?? null),
                            'apellido_materno' => $this->mayuscula($responsable['apellido_materno'] ?? null),
                            'apellido_casado'  => $this->mayuscula($responsable['apellido_casado'] ?? null),
                            'fecha_nacimiento' => $responsable['fecha_nacimiento'] ?? null,
                            'genero'           => $responsable['genero'] ?? null,
                            'ocupacion'        => $this->mayuscula($responsable['ocupacion'] ?? null),
                        ]
                    );

                    Telefono::where('id_persona', $personaResponsable->id)->delete();
                    $this->registrarTelefonosPersona($personaResponsable->id, $responsable['telefonos'] ?? []);

                    $this->registrarRubrosPersona($personaResponsable->id, $responsable['rubros'] ?? []);
                }

            } else {

                // PERSONA
                $personaResponsable = Persona::create([
                    'domicilio'           => $responsable['domicilio'] ?? null,
                    'nit'                 => $responsable['nit'] ?? null,
                    'correo'              => $responsable['correo'] ?? null,
                    'id_territorio'       => $responsable['id_territorio'] ?? null,
                    'estado'              => 'ACTIVO',
                ]);

                // NATURAL
                Natural::create([
                    'id_persona'       => $personaResponsable->id,
                    'ci'               => $responsable['ci'] ?? null,
                    'complemento'      => $responsable['complemento'] ?? null,
                    'expedido'         => $responsable['expedido'] ?? null,
                    'nombres'          => $this->mayuscula($responsable['nombres'] ?? null),
                    'apellido_paterno' => $this->mayuscula($responsable['apellido_paterno'] ?? null),
                    'apellido_materno' => $this->mayuscula($responsable['apellido_materno'] ?? null),
                    'apellido_casado'  => $this->mayuscula($responsable['apellido_casado'] ?? null),
                    'fecha_nacimiento' => $responsable['fecha_nacimiento'] ?? null,
                    'genero'           => $responsable['genero'] ?? null,
                    'ocupacion'        => $this->mayuscula($responsable['ocupacion'] ?? null),
                ]);

                // TELÉFONOS
                if (!empty($responsable['telefonos'])) {
                    foreach ($responsable['telefonos'] as $telefono) {
                        Telefono::create([
                            'id_persona' => $personaResponsable->id,
                            'numero'     => $telefono['numero'],
                            'estado'     => $telefono['tipo'] ?? 'CELULAR',
                        ]);
                    }

                }

                $this->registrarRubrosPersona($personaResponsable->id, $responsable['rubros'] ?? []);
                $idPersonaResponsable = $personaResponsable->id;
            }

            // Guarda el PDF del respaldo en una ruta unica dentro del disco public.
            $urlRespaldo = $responsable['url_respaldo'] ?? null;

            if (!empty($responsable['archivo_respaldo'])) {
                $urlRespaldo = $this->guardarRespaldoResponsable($responsable['archivo_respaldo']);
            }

            // RESPONSABLE
            Responsable::create([
                'id_empresa'      => $idEmpresa,
                'id_persona'      => $idPersonaResponsable,
                'id_rol'          => $responsable['id_rol'],
                'url_respaldo'    => $urlRespaldo,
                'fecha_registro'  => $responsable['fecha_registro'] ?? null,
                'fecha_baja'      => $responsable['fecha_baja'] ?? null,
                'estado'          => $responsable['estado'] ?? 'ACTIVO',
            ]);
        }
    }

    // Guarda el respaldo del responsable como documentos/responsables/nombre_unico.pdf.
    private function guardarRespaldoResponsable($archivo): string
    {
        $nombreArchivo = now()->format('YmdHis') . '_' . uniqid() . '.pdf';

        $ruta = $archivo->storeAs('documentos/responsables', $nombreArchivo, 'public');

        if (! $ruta) {
            throw new \RuntimeException('No se pudo guardar el PDF de respaldo en storage/app/public.');
        }

        // Si el servidor no tiene enlace public/storage, publicamos una copia para que el PDF se pueda visualizar.
        $rutaStorage = storage_path('app/public/' . $ruta);
        $rutaPublica = public_path('storage/' . $ruta);

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
            'productos.tipoProducto',
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
            'empresa.responsables.persona.territorio',
            'empresa.responsables.persona.telefonos',
            'empresa.responsables.persona.rubros',
            'empresa.responsables.rol',
        ]);

        return view('personas.show', compact('persona'));
    }

    /**
     * Show the form for editing the specified resource.
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
            'empresa.responsables.persona.territorio',
            'empresa.responsables.persona.telefonos',
            'empresa.responsables.persona.rubros',
            'empresa.responsables.rol',
        ]);

        $territorios = Territorio::all();
        $paises = Territorio::where('id_ambito', 1)->orderBy('nombre')->get();
        $departamentos = Territorio::where('id_ambito', 2)->orderBy('nombre')->get();
        $tiposEmpresas = TipoEmpresa::all();
        $rolesCuentaCatalogo = Role::where('estado', 1)->orderBy('name')->get();
        $rolesResponsablesCatalogo = Role::where('estado', 1)->orderBy('name')->get();
        $rubrosCatalogo = Rubro::where('estado', 'ACTIVO')->orderBy('nombre')->get();
        $ocupacionesCob = OcupacionCob::orderBy('codigo_ocupacion')->get();
        $expedidosNatural = Natural::EXPEDIDOS;
        $personas = Persona::with(['natural', 'telefonos', 'territorio', 'rubros'])
            ->where('id', '!=', $persona->id)
            // En edicion tambien se filtra el catalogo para que solo se elijan responsables validos.
            ->whereHas('natural', fn ($consultaNatural) => $consultaNatural
                ->whereNotNull('ci')
                ->where('ci', '!=', ''))
            ->orderBy('id')
            ->get();

        $telefonosRegistrados = $persona->telefonos
            ->map(fn ($telefono) => [
                'numero' => $telefono->numero,
                'estado' => $telefono->estado,
            ])
            ->values();

        $rubrosRegistrados = $persona->rubros
            ->pluck('id')
            ->values();

        $responsablesRegistrados = $persona->empresa
            ? $persona->empresa->responsables
                ->map(fn ($responsable) => [
                    'tipo' => 'EXISTENTE',
                    'id_persona' => $responsable->id_persona,
                    'domicilio' => $responsable->persona?->domicilio,
                    'nit' => $responsable->persona?->nit,
                    'correo' => $responsable->persona?->correo,
                    'id_territorio' => $responsable->persona?->id_territorio,
                    'nombres' => $responsable->persona?->natural?->nombres,
                    'apellido_paterno' => $responsable->persona?->natural?->apellido_paterno,
                    'apellido_materno' => $responsable->persona?->natural?->apellido_materno,
                    'apellido_casado' => $responsable->persona?->natural?->apellido_casado,
                    'ci' => $responsable->persona?->natural?->ci,
                    'complemento' => $responsable->persona?->natural?->complemento,
                    'expedido' => $responsable->persona?->natural?->expedido,
                    'fecha_nacimiento' => $responsable->persona?->natural?->fecha_nacimiento,
                    'genero' => $responsable->persona?->natural?->genero,
                    'ocupacion' => $responsable->persona?->natural?->ocupacion,
                    'nombre_completo' => trim(implode(' ', array_filter([
                        $responsable->persona?->natural?->nombres,
                        $responsable->persona?->natural?->apellido_paterno,
                        $responsable->persona?->natural?->apellido_materno,
                    ]))),
                    'id_rol' => $responsable->id_rol,
                    'rol_nombre' => $responsable->rol?->name,
                    'url_respaldo' => $responsable->url_respaldo,
                    'fecha_registro' => $responsable->fecha_registro,
                    'fecha_baja' => $responsable->fecha_baja,
                    'estado' => $responsable->estado,
                    'telefonos' => $responsable->persona?->telefonos
                        ? $responsable->persona->telefonos->map(fn ($telefono) => [
                            'numero' => $telefono->numero,
                            'tipo' => $telefono->estado,
                        ])->values()
                        : [],
                    'rubros' => $responsable->persona?->rubros
                        ? $responsable->persona->rubros->map(fn ($rubro) => [
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
            'rolesCuentaCatalogo',
            'rolesResponsablesCatalogo',
            'rubrosCatalogo',
            'ocupacionesCob',
            'expedidosNatural'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, Persona $persona)
    {
        $this->usarPaisComoTerritorioSiNoHayDepartamento($solicitud);
        $persona->loadMissing(['natural', 'usuario.roles']);
        $usuarioCuentaId = $persona->usuario?->id;
        $naturalId = $persona->natural?->id;
        $reglaPasswordCuenta = 'nullable|string|min:8';

        try {
            $datos = $solicitud->validate([
            'form_tipo_registro' => 'required|in:NATURAL,EMPRESA',

            // PERSONA GENERAL
            'form_domicilio'     => 'nullable|string|max:255',
            'form_nit'           => [
                'required_if:form_tipo_registro,EMPRESA',
                'nullable',
                'string',
                'max:50',
                Rule::unique('personas', 'nit')->ignore($persona->id),
            ],
            'form_correo'        => [
                'required',
                'email',
                'max:50',
                Rule::unique('personas', 'correo')->ignore($persona->id),
            ],
            'form_id_pais'       => [
                'required',
                Rule::exists('territorios', 'id')->where(fn ($query) => $query->where('id_ambito', 1)),
            ],
            'form_id_territorio' => 'required|exists:territorios,id',
            'form_estado'        => 'nullable|string|max:50',

            // NATURAL
            'form_ci'               => [
                'required_if:form_tipo_registro,NATURAL',
                'nullable',
                'string',
                'max:50',
                Rule::unique('naturals', 'ci')->ignore($naturalId),
            ],
            'form_complemento'      => 'nullable|string|max:10',
            'form_expedido'         => ['nullable', Rule::in(array_keys(Natural::EXPEDIDOS))],
            'form_nombres'          => 'required_if:form_tipo_registro,NATURAL|nullable|string|max:100',
            'form_apellido_paterno' => 'required_if:form_tipo_registro,NATURAL|nullable|string|max:100',
            'form_apellido_materno' => 'nullable|string|max:100',
            'form_apellido_casado'  => 'nullable|string|max:100',
            'form_fecha_nacimiento' => 'nullable|date',
            'form_genero'           => 'required_if:form_tipo_registro,NATURAL|nullable',
            'form_id_ocupacion'     => 'nullable|exists:ocupaciones_cob,id',

            // EMPRESA
            'form_id_tipo_empresa'  => 'required_if:form_tipo_registro,EMPRESA|nullable|exists:tipos_empresas,id',
            'form_razon_social'     => 'required_if:form_tipo_registro,EMPRESA|nullable|string|max:255',
            'form_matricula'        => 'required_if:form_tipo_registro,EMPRESA|nullable|string|max:50',
            'form_latitud'          => 'nullable',
            'form_longitud'         => 'nullable',
            'form_estado_empresa'   => 'nullable|max:50',

            // TELEFONOS PERSONA PRINCIPAL
            'telefonos'          => 'nullable|array',
            'telefonos.*.numero' => 'required|string|max:50',
            'telefonos.*.tipo'   => 'nullable|max:50',

            // RUBROS PERSONA / EMPRESA
            'rubros'   => 'nullable|array',
            'rubros.*' => 'integer|exists:rubros,id',

            // RESPONSABLES EMPRESA
            'responsables'                     => 'nullable|array',
            'responsables.*.tipo'              => 'required|in:NUEVO,EXISTENTE',
            'responsables.*.id_persona'        => 'nullable|exists:personas,id',
            'responsables.*.domicilio'         => 'nullable|string|max:255',
            'responsables.*.nit'               => 'nullable|string|max:50',
            'responsables.*.correo'            => 'nullable|email|max:50',
            'responsables.*.id_territorio'     => 'nullable|exists:territorios,id',
            'responsables.*.nombres'           => 'nullable|string|max:100',
            'responsables.*.apellido_paterno'  => 'nullable|string|max:100',
            'responsables.*.apellido_materno'  => 'nullable|string|max:100',
            'responsables.*.apellido_casado'   => 'nullable|string|max:100',
            'responsables.*.ci'                => 'nullable|string|max:50',
            'responsables.*.complemento'       => 'nullable|string|max:10',
            'responsables.*.expedido'          => 'nullable|string|max:10',
            'responsables.*.fecha_nacimiento'  => 'nullable|date',
            'responsables.*.genero'            => 'nullable',
            'responsables.*.ocupacion'         => 'nullable|string|max:255',
            'responsables.*.telefonos'         => 'nullable|array',
            'responsables.*.telefonos.*.numero'=> 'required|string|max:50',
            'responsables.*.telefonos.*.tipo'  => 'nullable|max:50',
            'responsables.*.rubros'            => 'nullable|array',
            'responsables.*.rubros.*.nombre'   => 'required|string|max:255',
            'responsables.*.rubros.*.estado'   => 'nullable|string|max:50',
            'responsables.*.id_rol'            => 'required_if:form_tipo_registro,EMPRESA|nullable|exists:roles,id',
            'responsables.*.url_respaldo'      => 'nullable|string|max:255',
            'responsables.*.archivo_respaldo'  => 'nullable|file|mimes:pdf|max:5120',
            'responsables.*.fecha_registro'    => 'nullable|date',
            'responsables.*.fecha_baja'        => 'nullable|date',
            'responsables.*.estado'            => 'nullable|string|max:50',

            // CUENTA DE ACCESO RELACIONADA A LA PERSONA/EMPRESA
            'form_usuario_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'name')->ignore($usuarioCuentaId),
            ],
            'form_usuario_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuarioCuentaId),
            ],
            'form_usuario_password' => $reglaPasswordCuenta,
            'form_id_role' => [
                'required',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('estado', 1)),
            ],
            ], $this->mensajesValidacionPersona(), [
                // Nombres legibles: mantienen create y edit con mensajes entendibles.
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
            ]);

            // Evita errores SQL tambien al editar responsables nuevos de empresa.
            $this->validarResponsablesAntesDeGuardar($datos['responsables'] ?? []);
            $this->validarTerritorioPrincipal($datos['form_id_pais'], $datos['form_id_territorio']);
        } catch (ValidationException $e) {
            // Mantiene los PDF agregados desde el modal si la edicion vuelve por errores.
            // Sin esto, el navegador vacia el input file y el usuario tendria que adjuntarlo otra vez.
            $this->preservarRespaldosResponsablesEnInput($solicitud);

            throw $e;
        }

        try {
            DB::beginTransaction();

            // Actualiza o crea la cuenta vinculada a personas.id_usuario.
            $usuarioAcceso = $persona->usuario ?: new User();
            $usuarioAcceso->name = $datos['form_usuario_name'];
            $usuarioAcceso->email = $datos['form_usuario_email'];
            $usuarioAcceso->estado = 1;
            $passwordGeneradaCuenta = null;

            if (!empty($datos['form_usuario_password']) || ! $usuarioCuentaId) {
                $passwordCuenta = $datos['form_usuario_password'] ?: $this->generarPasswordTemporalCuenta();
                $usuarioAcceso->password = $passwordCuenta;

                if (empty($datos['form_usuario_password'])) {
                    $passwordGeneradaCuenta = $passwordCuenta;
                }
            }

            $usuarioAcceso->save();
            $usuarioAcceso->roles()->sync([$datos['form_id_role']]);

            $persona->update([
                'id_usuario'     => $usuarioAcceso->id,
                'domicilio'     => $datos['form_domicilio'] ?? null,
                'nit'           => $datos['form_nit'] ?? null,
                'correo'        => $datos['form_correo'],
                'id_territorio' => $datos['form_id_territorio'],
                'estado'        => $datos['form_estado'] ?? 'ACTIVO',
            ]);

            // Reemplaza telefonos por la lista actual enviada desde el wizard.
            Telefono::where('id_persona', $persona->id)->delete();
            $this->registrarTelefonosPersona($persona->id, $datos['telefonos'] ?? []);
            $this->registrarRubrosPersona($persona->id, $datos['rubros'] ?? []);

            if ($datos['form_tipo_registro'] === 'NATURAL') {
                // Si antes era empresa, se eliminan sus datos incompatibles.
                if ($persona->empresa) {
                    Responsable::where('id_empresa', $persona->empresa->id)->delete();
                    $persona->empresa->delete();
                }

                Natural::updateOrCreate(
                    ['id_persona' => $persona->id],
                    [
                        'id_ocupacion'     => $datos['form_id_ocupacion'] ?? null,
                        'ci'               => $datos['form_ci'] ?? null,
                        'complemento'      => $datos['form_complemento'] ?? null,
                        'expedido'         => $datos['form_expedido'] ?? null,
                        'nombres'          => $this->mayuscula($datos['form_nombres'] ?? null),
                        'apellido_paterno' => $this->mayuscula($datos['form_apellido_paterno'] ?? null),
                        'apellido_materno' => $this->mayuscula($datos['form_apellido_materno'] ?? null),
                        'apellido_casado'  => $this->mayuscula($datos['form_apellido_casado'] ?? null),
                        'fecha_nacimiento' => $datos['form_fecha_nacimiento'] ?? null,
                        'genero'           => $datos['form_genero'],
                        'ocupacion'        => $this->descripcionOcupacionCob($datos['form_id_ocupacion'] ?? null),
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
                        'razon_social'    => $datos['form_razon_social'],
                        'matricula'       => $datos['form_matricula'],
                        'latitud'         => $datos['form_latitud'] ?? null,
                        'longitud'        => $datos['form_longitud'] ?? null,
                        'estado'          => !empty($datos['form_estado_empresa']) ? $datos['form_estado_empresa'] : 'ACTIVO',
                    ]
                );

                Responsable::where('id_empresa', $empresa->id)->delete();
                $this->registrarResponsablesEmpresa($empresa->id, $datos['responsables'] ?? []);
            }

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text'  => $passwordGeneradaCuenta
                    ? 'El registro se actualizo correctamente. Contrasena generada: ' . $passwordGeneradaCuenta
                    : 'El registro se actualizo correctamente.',
                'icon'  => 'success'
            ]);

            return redirect()->route('personas_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar. ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Genera una contrasena temporal cuando el usuario no escribe una manualmente.
     */
    private function generarPasswordTemporalCuenta(): string
    {
        return Str::random(10);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Persona $persona)
    {
        try {
            DB::beginTransaction();

            $persona->load([
                'natural',
                'empresa.responsables',
                'telefonos',
                'rubros',
                'usuario.roles',
                'usuario.permisosDirectos',
            ]);
            $bloqueosEliminacion = $this->obtenerBloqueosEliminacionPersona($persona);

            if (!empty($bloqueosEliminacion)) {
                DB::rollBack();

                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text'  => 'Este registro tiene relaciones activas: ' . implode(' ', $bloqueosEliminacion),
                    'icon'  => 'warning',
                ]);

                return redirect()->route('personas_index');
            }

            // Elimina asignaciones donde esta persona figura como responsable.
            Responsable::where('id_persona', $persona->id)->delete();

            if ($persona->empresa) {
                Responsable::where('id_empresa', $persona->empresa->id)->delete();
                $persona->empresa->delete();
            }

            $persona->telefonos()->delete();
            $persona->rubros()->detach();
            $persona->natural?->delete();

            $usuarioAcceso = $persona->usuario;
            $persona->delete();

            if ($usuarioAcceso) {
                // El usuario usa SoftDeletes: se conservan roles/permisos para historial o restauracion.
                $usuarioAcceso->delete();
            }

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text'  => 'El registro se elimino correctamente.',
                'icon'  => 'success'
            ]);

            return redirect()->route('personas_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'No se pudo eliminar. ' . $e->getMessage());
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
        $empresa = $persona->empresa;

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

        if ($empresa) {
            // responsables.id_empresa apunta a empresas.id, por eso se revisa solo si esta persona es empresa.
            $responsablesEmpresa = Responsable::where('id_empresa', $empresa->id)->count();
            if ($responsablesEmpresa > 0) {
                $bloqueos[] = "{$responsablesEmpresa} responsable(s) registrados en la empresa.";
            }
        }

        return $bloqueos;
    }
}
