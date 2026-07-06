<x-admin-layout title="Editar Responsable | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Responsables de Empresas', 'href' => route('responsables_index')],
    ['name' => 'Editar responsable'],
]">

    @php
        $valorNuevoResponsable = 'NUEVO';
        $personaActual = $responsable->persona;
        $naturalActual = $personaActual?->natural;

        // Devuelve un texto claro para identificar a cada persona natural en el selector.
        $nombrePersonaResponsable = function ($persona) {
            $natural = $persona->natural;
            $nombre = trim(implode(' ', array_filter([
                $natural?->nombres,
                $natural?->apellido_paterno,
                $natural?->apellido_materno,
            ])));

            return trim(($natural?->ci ? $natural->ci . ' - ' : '') . ($nombre ?: 'Persona sin nombre'));
        };

        // Convierte fechas de BD a formato compatible con input date.
        $fechaCampo = function ($fecha) {
            return $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('Y-m-d') : '';
        };

        // Catálogo para autocompletar los datos cuando se selecciona una persona existente.
        $personasCatalogo = $personas->map(function ($persona) use ($fechaCampo) {
            $natural = $persona->natural;

            return [
                'id' => $persona->id,
                'domicilio' => $persona->domicilio,
                'nit' => $persona->nit,
                'correo' => $persona->correo,
                'id_territorio' => $persona->id_territorio,
                'estado' => $persona->estado ?: 'ACTIVO',
                'ci' => $natural?->ci,
                'complemento' => $natural?->complemento,
                'expedido' => $natural?->expedido,
                'nombres' => $natural?->nombres,
                'apellido_paterno' => $natural?->apellido_paterno,
                'apellido_materno' => $natural?->apellido_materno,
                'apellido_casado' => $natural?->apellido_casado,
                'fecha_nacimiento' => $fechaCampo($natural?->fecha_nacimiento),
                'genero' => is_null($natural?->genero) ? '' : (string) $natural->genero,
                'ocupacion' => $natural?->ocupacion,
                'telefonos' => $persona->telefonos->map(fn ($telefono) => [
                    'numero' => (string) $telefono->numero,
                    'tipo' => $telefono->estado ?: 'CELULAR',
                ])->values(),
                'rubros' => $persona->rubros->map(fn ($rubro) => [
                    'nombre' => $rubro->nombre,
                    'estado' => $rubro->estado ?: 'ACTIVO',
                ])->values(),
            ];
        })->values();

        $telefonosIniciales = old('form_telefonos_json')
            ? collect(json_decode(old('form_telefonos_json'), true) ?: [])->values()
            : $personaActual?->telefonos->map(fn ($telefono) => [
                'numero' => (string) $telefono->numero,
                'tipo' => $telefono->estado ?: 'CELULAR',
            ])->values();

        $rubrosIniciales = old('form_rubros_json')
            ? collect(json_decode(old('form_rubros_json'), true) ?: [])->values()
            : $personaActual?->rubros->map(fn ($rubro) => [
                'nombre' => $rubro->nombre,
                'estado' => $rubro->estado ?: 'ACTIVO',
            ])->values();

        // Datos del respaldo actual: se usan para mostrar Ver/Quitar PDF sin tocar la BD hasta guardar.
        $urlRespaldoActual = $responsable->url_respaldo
            ? (\Illuminate\Support\Str::startsWith($responsable->url_respaldo, ['http://', 'https://'])
                ? $responsable->url_respaldo
                : asset('storage/' . $responsable->url_respaldo))
            : null;
        $nombreRespaldoActual = $responsable->url_respaldo ? basename($responsable->url_respaldo) : null;
        $respaldoFueQuitado = old('form_quitar_respaldo') === '1';
    @endphp

    <style>
        .responsable-edit-page {
            display: grid;
            gap: 16px;
        }

        .responsable-edit-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }

        .responsable-edit-heading h1 {
            margin: 0;
            color: #0f172a;
            font-size: 22px;
            font-weight: 900;
        }

        .responsable-edit-heading p {
            margin-top: 4px;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .responsable-edit-section {
            position: relative;
            overflow: visible;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        .responsable-edit-section + .responsable-edit-section {
            margin-top: 18px;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
        }

        .responsable-edit-section-head {
            border-bottom: 0;
            background: transparent;
            padding: 0 0 12px;
        }

        .responsable-edit-section-head strong {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: 900;
        }

        .responsable-edit-section-head strong::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #64748b;
        }

        .responsable-edit-section-head span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .responsable-edit-section.is-blue strong {
            color: #1d4ed8;
        }

        .responsable-edit-section.is-blue {
            border-color: transparent;
        }

        .responsable-edit-section.is-blue .responsable-edit-section-head {
            border-bottom-color: transparent;
            background: transparent;
        }

        .responsable-edit-section.is-blue strong::before {
            background: #3b82f6;
        }

        .responsable-edit-section.is-violet strong {
            color: #6d28d9;
        }

        .responsable-edit-section.is-violet {
            border-color: transparent;
        }

        .responsable-edit-section.is-violet .responsable-edit-section-head {
            border-bottom-color: transparent;
            background: transparent;
        }

        .responsable-edit-section.is-violet strong::before {
            background: #8b5cf6;
        }

        .responsable-edit-section.is-emerald strong {
            color: #047857;
        }

        .responsable-edit-section.is-emerald {
            border-color: transparent;
        }

        .responsable-edit-section.is-emerald .responsable-edit-section-head {
            border-bottom-color: transparent;
            background: transparent;
        }

        .responsable-edit-section.is-emerald strong::before {
            background: #10b981;
        }

        .responsable-edit-section.is-sky strong {
            color: #0284c7;
        }

        .responsable-edit-section.is-sky {
            border-color: transparent;
        }

        .responsable-edit-section.is-sky .responsable-edit-section-head {
            border-bottom-color: transparent;
            background: transparent;
        }

        .responsable-edit-section.is-sky strong::before {
            background: #0ea5e9;
        }

        .responsable-edit-section.is-lime strong {
            color: #4d7c0f;
        }

        .responsable-edit-section.is-lime {
            border-color: transparent;
        }

        .responsable-edit-section.is-lime .responsable-edit-section-head {
            border-bottom-color: transparent;
            background: transparent;
        }

        .responsable-edit-section.is-lime strong::before {
            background: #65a30d;
        }

        .responsable-edit-body {
            padding: 0;
        }

        .responsable-field-label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-size: 13px;
            font-weight: 800;
        }

        .responsable-field {
            width: 100%;
            min-height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #ffffff;
            color: #1f2937;
            padding: 9px 11px;
            font-size: 14px;
            outline: none;
        }

        .responsable-field:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }

        .responsable-error {
            margin-top: 5px;
            color: #dc2626;
            font-size: 12px;
            font-weight: 700;
        }

        .responsable-list-box {
            margin-top: 14px;
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: 0;
        }

        .responsable-list-box h3 {
            margin: 0 0 10px;
            color: #334155;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .responsable-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .responsable-chip {
            display: inline-flex;
            max-width: 100%;
            align-items: center;
            gap: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            color: #334155;
            padding: 8px 11px;
            font-size: 13px;
            font-weight: 800;
            white-space: normal;
        }

        .responsable-chip small {
            border-radius: 999px;
            background: #dcfce7;
            color: #047857;
            padding: 2px 7px;
            font-size: 11px;
            font-weight: 900;
        }

        .responsable-chip button {
            color: #ef4444;
            font-weight: 900;
        }

        .responsable-empty-list {
            display: inline-flex;
            width: 100%;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            background: #f9fafb;
            color: #64748b;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .responsable-pdf-control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid #dbe3ea;
            border-radius: 8px;
            background: #fbfcfd;
            padding: 8px 9px;
        }

        .responsable-pdf-info {
            display: flex;
            min-width: 0;
            flex: 1;
            align-items: center;
            gap: 10px;
        }

        .responsable-pdf-info i {
            display: inline-flex;
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: #fff1f2;
            color: #be123c;
            font-size: 13px;
        }

        .responsable-pdf-info strong {
            display: block;
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .responsable-pdf-info span {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .responsable-pdf-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 6px;
        }

        .responsable-pdf-button {
            display: inline-flex;
            min-height: 30px;
            align-items: center;
            justify-content: center;
            gap: 5px;
            border-radius: 6px;
            padding: 0 8px;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease;
        }

        .responsable-pdf-button.is-select {
            border: 1px solid #99f6e4;
            background: #f0fdfa;
            color: #0f766e;
        }

        .responsable-pdf-button.is-view {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .responsable-pdf-button.is-select:hover {
            border-color: #5eead4;
            background: #ccfbf1;
            color: #115e59;
        }

        .responsable-pdf-button.is-view:hover {
            border-color: #93c5fd;
            background: #dbeafe;
            color: #1e40af;
        }

        .responsable-pdf-button.is-remove {
            border: 1px solid #fecaca;
            background: #fff1f2;
            color: #be123c;
        }

        .responsable-pdf-button.is-remove:hover {
            border-color: #fca5a5;
            background: #ffe4e6;
        }

        .responsable-pdf-button:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .responsable-edit-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 16px;
        }

        .responsable-btn-cancel,
        .responsable-btn-save {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            padding: 0 18px;
            font-size: 15px;
            font-weight: 800;
            text-decoration: none;
        }

        .responsable-btn-cancel {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
        }

        .responsable-btn-cancel:hover {
            background: #f3f4f6;
        }

        .responsable-btn-save {
            border: 1px solid #0d9488;
            background: #0d9488;
            color: #ffffff;
        }

        .responsable-btn-save:hover {
            background: #0f766e;
        }

        @media (max-width: 768px) {
            .responsable-pdf-control {
                align-items: stretch;
                flex-direction: column;
            }

            .responsable-pdf-actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="responsable-edit-page">
        <div class="responsable-edit-heading">
            <div>
                <h1>Editar responsable</h1>
                <p>Actualice la relación con la empresa y los datos de la persona responsable.</p>
            </div>
        </div>

        <form action="{{ route('responsables_update', $responsable) }}" method="POST" enctype="multipart/form-data"
            class="rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="p-4">
                <section class="responsable-edit-section">
                    <div class="responsable-edit-section-head">
                        <strong>1. Persona registrada</strong>
                        <span>Seleccione una persona existente o cree un nuevo responsable para esta empresa.</span>
                    </div>

                    <div class="responsable-edit-body">
                        <label class="responsable-field-label" for="form_id_persona">Seleccionar persona responsable</label>
                        <select id="form_id_persona" name="form_id_persona" class="responsable-field">
                            <option value="">Seleccione la persona</option>
                            <option value="{{ $valorNuevoResponsable }}" @selected(old('form_id_persona') === $valorNuevoResponsable)>
                                + Crear nuevo responsable
                            </option>
                            @foreach ($personas as $persona)
                                <option value="{{ $persona->id }}" @selected(old('form_id_persona', $responsable->id_persona) == $persona->id)>
                                    {{ $nombrePersonaResponsable($persona) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs font-semibold text-slate-500">
                            Si elige crear uno nuevo, se limpiarán los datos para registrar otra persona responsable.
                        </p>
                        @error('form_id_persona')
                            <p class="responsable-error">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <section class="responsable-edit-section is-blue">
                    <div class="responsable-edit-section-head">
                        <strong>2. Información general del responsable</strong>
                    </div>

                    <div class="responsable-edit-body grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="responsable-field-label" for="form_domicilio">Domicilio del responsable</label>
                            <input id="form_domicilio" name="form_domicilio" type="text" class="responsable-field"
                                value="{{ old('form_domicilio', $personaActual?->domicilio) }}">
                            @error('form_domicilio')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_nit">NIT del responsable</label>
                            <input id="form_nit" name="form_nit" type="text" class="responsable-field"
                                value="{{ old('form_nit', $personaActual?->nit) }}">
                            @error('form_nit')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_correo">Correo del responsable</label>
                            <input id="form_correo" name="form_correo" type="email" class="responsable-field"
                                value="{{ old('form_correo', $personaActual?->correo) }}">
                            @error('form_correo')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_id_territorio">Territorio del responsable</label>
                            <select id="form_id_territorio" name="form_id_territorio" class="responsable-field">
                                <option value="">Seleccione territorio</option>
                                @foreach ($territorios as $territorio)
                                    <option value="{{ $territorio->id }}" @selected(old('form_id_territorio', $personaActual?->id_territorio) == $territorio->id)>
                                        {{ $territorio->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_id_territorio')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="responsable-edit-section is-violet">
                    <div class="responsable-edit-section-head">
                        <strong>3. Datos personales del responsable</strong>
                    </div>

                    <div class="responsable-edit-body grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="responsable-field-label" for="form_nombres">Nombres</label>
                            <input id="form_nombres" name="form_nombres" type="text" class="responsable-field"
                                value="{{ old('form_nombres', $naturalActual?->nombres) }}">
                            @error('form_nombres')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_apellido_paterno">Apellido paterno</label>
                            <input id="form_apellido_paterno" name="form_apellido_paterno" type="text"
                                class="responsable-field" value="{{ old('form_apellido_paterno', $naturalActual?->apellido_paterno) }}">
                            @error('form_apellido_paterno')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_apellido_materno">Apellido materno</label>
                            <input id="form_apellido_materno" name="form_apellido_materno" type="text"
                                class="responsable-field" value="{{ old('form_apellido_materno', $naturalActual?->apellido_materno) }}">
                            @error('form_apellido_materno')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_apellido_casado">Apellido de casado</label>
                            <input id="form_apellido_casado" name="form_apellido_casado" type="text"
                                class="responsable-field" value="{{ old('form_apellido_casado', $naturalActual?->apellido_casado) }}">
                            @error('form_apellido_casado')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_ci">CI</label>
                            <input id="form_ci" name="form_ci" type="text" class="responsable-field"
                                value="{{ old('form_ci', $naturalActual?->ci) }}">
                            @error('form_ci')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_complemento">Complemento</label>
                            <input id="form_complemento" name="form_complemento" type="text" class="responsable-field"
                                value="{{ old('form_complemento', $naturalActual?->complemento) }}">
                            @error('form_complemento')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_expedido">Expedido</label>
                            <input id="form_expedido" name="form_expedido" type="text" class="responsable-field"
                                value="{{ old('form_expedido', $naturalActual?->expedido) }}">
                            @error('form_expedido')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_fecha_nacimiento">Fecha de nacimiento</label>
                            <input id="form_fecha_nacimiento" name="form_fecha_nacimiento" type="date" class="responsable-field"
                                value="{{ old('form_fecha_nacimiento', $fechaCampo($naturalActual?->fecha_nacimiento)) }}">
                            @error('form_fecha_nacimiento')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_genero">Género</label>
                            <select id="form_genero" name="form_genero" class="responsable-field">
                                <option value="">Seleccione una opción</option>
                                <option value="1" @selected((string) old('form_genero', $naturalActual?->genero) === '1')>Masculino</option>
                                <option value="0" @selected((string) old('form_genero', $naturalActual?->genero) === '0')>Femenino</option>
                            </select>
                            @error('form_genero')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_ocupacion">Ocupación</label>
                            <input id="form_ocupacion" name="form_ocupacion" type="text" class="responsable-field"
                                value="{{ old('form_ocupacion', $naturalActual?->ocupacion) }}">
                            @error('form_ocupacion')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="responsable-edit-section is-emerald">
                    <div class="responsable-edit-section-head">
                        <strong>4. Teléfonos del responsable</strong>
                        <span>Puede actualizar los teléfonos del registro propio de la persona responsable.</span>
                    </div>

                    <div class="responsable-edit-body">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                            <div class="md:col-span-6">
                                <label class="responsable-field-label" for="telefono_numero">Teléfono</label>
                                <input id="telefono_numero" type="text" class="responsable-field" placeholder="Ejemplo: 70123456">
                            </div>

                            <div class="md:col-span-4">
                                <label class="responsable-field-label" for="telefono_tipo">Tipo</label>
                                <select id="telefono_tipo" class="responsable-field">
                                    <option value="CELULAR">Celular</option>
                                    <option value="FIJO">Fijo</option>
                                    <option value="REFERENCIA">Referencia</option>
                                </select>
                            </div>

                            <div class="flex items-end md:col-span-2">
                                <button type="button" onclick="agregarTelefonoResponsable()" class="responsable-btn-save w-full">
                                    Agregar
                                </button>
                            </div>
                        </div>

                        <div class="responsable-list-box">
                            <h3>Teléfonos registrados</h3>
                            <div id="listaTelefonosResponsable" class="responsable-chip-list"></div>
                        </div>

                        <input type="hidden" id="form_telefonos_json" name="form_telefonos_json"
                            value="{{ old('form_telefonos_json', $telefonosIniciales->toJson()) }}">
                        @error('form_telefonos_json')
                            <p class="responsable-error">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <section class="responsable-edit-section is-sky">
                    <div class="responsable-edit-section-head">
                        <strong>5. Rubros o actividad económica</strong>
                        <span>Puede actualizar los rubros del registro propio de la persona responsable.</span>
                    </div>

                    <div class="responsable-edit-body">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                            <div class="md:col-span-8">
                                <label class="responsable-field-label" for="rubro_nombre">Nombre del rubro</label>
                                <input id="rubro_nombre" type="text" class="responsable-field" placeholder="Ej: Control de plagas">
                            </div>

                            <div class="md:col-span-2">
                                <label class="responsable-field-label" for="rubro_estado">Estado</label>
                                <select id="rubro_estado" class="responsable-field">
                                    <option value="ACTIVO">Activo</option>
                                    <option value="INACTIVO">Inactivo</option>
                                </select>
                            </div>

                            <div class="flex items-end md:col-span-2">
                                <button type="button" onclick="agregarRubroResponsable()"
                                    class="responsable-btn-save w-full !bg-sky-600 !border-sky-600 hover:!bg-sky-700">
                                    Agregar
                                </button>
                            </div>
                        </div>

                        <div class="responsable-list-box">
                            <h3>Rubros registrados</h3>
                            <div id="listaRubrosResponsable" class="responsable-chip-list"></div>
                        </div>

                        <input type="hidden" id="form_rubros_json" name="form_rubros_json"
                            value="{{ old('form_rubros_json', $rubrosIniciales->toJson()) }}">
                        @error('form_rubros_json')
                            <p class="responsable-error">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <section class="responsable-edit-section is-lime">
                    <div class="responsable-edit-section-head">
                        <strong>6. Datos del responsable</strong>
                        <span>Datos de la asignación del responsable dentro de la empresa.</span>
                    </div>

                    <div class="responsable-edit-body grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="responsable-field-label" for="form_id_empresa">Empresa donde es responsable</label>
                            <select id="form_id_empresa" name="form_id_empresa" class="responsable-field">
                                <option value="">Seleccione la empresa</option>
                                @foreach ($empresas as $empresa)
                                    <option value="{{ $empresa->id }}" @selected(old('form_id_empresa', $responsable->id_empresa) == $empresa->id)>
                                        {{ $empresa->razon_social }}{{ $empresa->persona?->nit ? ' - NIT: ' . $empresa->persona->nit : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_id_empresa')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_url_respaldo">Respaldo en formato PDF</label>
                            <div class="responsable-pdf-control">
                                <input id="form_url_respaldo" name="form_url_respaldo" type="file"
                                    accept="application/pdf" class="sr-only">
                                <input id="form_quitar_respaldo" name="form_quitar_respaldo" type="hidden"
                                    value="{{ $respaldoFueQuitado ? '1' : '0' }}">

                                <div class="responsable-pdf-info">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <div>
                                        <strong id="responsablePdfNombre">
                                            {{ $respaldoFueQuitado ? 'Sin PDF seleccionado' : ($nombreRespaldoActual ?: 'Sin PDF seleccionado') }}
                                        </strong>
                                        <span id="responsablePdfEstado">
                                            {{ $respaldoFueQuitado
                                                ? 'Seleccione un respaldo PDF si corresponde.'
                                                : ($urlRespaldoActual ? 'PDF guardado actualmente.' : 'Seleccione un respaldo PDF si corresponde.') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="responsable-pdf-actions">
                                    <label for="form_url_respaldo" class="responsable-pdf-button is-select">
                                        <i class="fa-solid fa-upload"></i>
                                        <span>Seleccionar</span>
                                    </label>

                                    <button type="button" id="btnVerRespaldoResponsable"
                                        class="responsable-pdf-button is-view"
                                        data-pdf-url="{{ $respaldoFueQuitado ? '' : $urlRespaldoActual }}"
                                        @disabled($respaldoFueQuitado || ! $urlRespaldoActual)>
                                        <i class="fa-solid fa-eye"></i>
                                        <span>Ver</span>
                                    </button>

                                    <button type="button" id="btnQuitarRespaldoResponsable"
                                        class="responsable-pdf-button is-remove"
                                        @disabled($respaldoFueQuitado || ! $urlRespaldoActual)>
                                        <i class="fa-solid fa-xmark"></i>
                                        <span>Quitar</span>
                                    </button>
                                </div>
                            </div>
                            @error('form_url_respaldo')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                            @error('form_quitar_respaldo')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_id_rol">Rol o cargo</label>
                            <select id="form_id_rol" name="form_id_rol" class="responsable-field">
                                <option value="">Seleccione rol</option>
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->id }}" @selected(old('form_id_rol', $responsable->id_rol) == $rol->id)>
                                        {{ $rol->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_id_rol')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_estado">Estado</label>
                            <select id="form_estado" name="form_estado" class="responsable-field">
                                @foreach (['ACTIVO', 'INACTIVO'] as $estado)
                                    <option value="{{ $estado }}" @selected(old('form_estado', $responsable->estado ?: 'ACTIVO') === $estado)>
                                        {{ ucfirst(strtolower($estado)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_estado')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_fecha_registro">Fecha de registro</label>
                            <input id="form_fecha_registro" name="form_fecha_registro" type="date" class="responsable-field"
                                value="{{ old('form_fecha_registro', $fechaCampo($responsable->fecha_registro)) }}">
                            @error('form_fecha_registro')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_fecha_baja">Fecha de baja</label>
                            <input id="form_fecha_baja" name="form_fecha_baja" type="date" class="responsable-field"
                                value="{{ old('form_fecha_baja', $fechaCampo($responsable->fecha_baja)) }}">
                            @error('form_fecha_baja')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="responsable-field-label" for="form_estado_persona">Estado de la persona</label>
                            <select id="form_estado_persona" name="form_estado_persona" class="responsable-field">
                                @foreach (['ACTIVO', 'INACTIVO'] as $estado)
                                    <option value="{{ $estado }}" @selected(old('form_estado_persona', $personaActual?->estado ?: 'ACTIVO') === $estado)>
                                        {{ ucfirst(strtolower($estado)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_estado_persona')
                                <p class="responsable-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>
            </div>

            <div class="responsable-edit-actions">
                <a href="{{ route('responsables_show', $responsable) }}" class="responsable-btn-cancel">
                    Cancelar
                </a>

                <button type="submit" class="responsable-btn-save">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const valorNuevoResponsable = @json($valorNuevoResponsable);
            const personasResponsables = @json($personasCatalogo);
            let telefonosResponsable = JSON.parse(document.getElementById('form_telefonos_json').value || '[]');
            let rubrosResponsable = JSON.parse(document.getElementById('form_rubros_json').value || '[]');
            let pdfTemporalResponsable = null;

            // Controla el respaldo PDF sin guardar cambios hasta que el formulario se envie.
            const respaldoInicialResponsable = {
                url: @json($respaldoFueQuitado ? '' : $urlRespaldoActual),
                nombre: @json($respaldoFueQuitado ? null : $nombreRespaldoActual),
            };
            const pdfInputResponsable = document.getElementById('form_url_respaldo');
            const quitarPdfResponsable = document.getElementById('form_quitar_respaldo');
            const nombrePdfResponsable = document.getElementById('responsablePdfNombre');
            const estadoPdfResponsable = document.getElementById('responsablePdfEstado');
            const btnVerPdfResponsable = document.getElementById('btnVerRespaldoResponsable');
            const btnQuitarPdfResponsable = document.getElementById('btnQuitarRespaldoResponsable');

            const camposPersona = {
                domicilio: document.getElementById('form_domicilio'),
                nit: document.getElementById('form_nit'),
                correo: document.getElementById('form_correo'),
                id_territorio: document.getElementById('form_id_territorio'),
                estado: document.getElementById('form_estado_persona'),
                ci: document.getElementById('form_ci'),
                complemento: document.getElementById('form_complemento'),
                expedido: document.getElementById('form_expedido'),
                nombres: document.getElementById('form_nombres'),
                apellido_paterno: document.getElementById('form_apellido_paterno'),
                apellido_materno: document.getElementById('form_apellido_materno'),
                apellido_casado: document.getElementById('form_apellido_casado'),
                fecha_nacimiento: document.getElementById('form_fecha_nacimiento'),
                genero: document.getElementById('form_genero'),
                ocupacion: document.getElementById('form_ocupacion'),
            };

            function actualizarVistaRespaldoResponsable(nombre, estado, url = '', permiteQuitar = false) {
                nombrePdfResponsable.textContent = nombre || 'Sin PDF seleccionado';
                estadoPdfResponsable.textContent = estado || 'Seleccione un respaldo PDF si corresponde.';
                btnVerPdfResponsable.dataset.pdfUrl = url || '';
                btnVerPdfResponsable.disabled = !url;
                btnQuitarPdfResponsable.disabled = !permiteQuitar;
            }

            function liberarPdfTemporalResponsable() {
                if (pdfTemporalResponsable) {
                    URL.revokeObjectURL(pdfTemporalResponsable);
                    pdfTemporalResponsable = null;
                }
            }

            function limpiarRespaldoResponsable(mensaje = 'El respaldo se quitara al guardar.') {
                pdfInputResponsable.value = '';
                liberarPdfTemporalResponsable();
                quitarPdfResponsable.value = '1';
                actualizarVistaRespaldoResponsable(null, mensaje, '', false);
            }

            function guardarListasResponsable() {
                document.getElementById('form_telefonos_json').value = JSON.stringify(telefonosResponsable);
                document.getElementById('form_rubros_json').value = JSON.stringify(rubrosResponsable);
            }

            function renderTelefonosResponsable() {
                const contenedor = document.getElementById('listaTelefonosResponsable');
                contenedor.innerHTML = '';

                if (!telefonosResponsable.length) {
                    contenedor.innerHTML = '<span class="responsable-empty-list">Sin teléfonos registrados.</span>';
                    return;
                }

                telefonosResponsable.forEach((telefono, indice) => {
                    const chip = document.createElement('span');
                    chip.className = 'responsable-chip';
                    chip.innerHTML = `
                        <span>${telefono.numero || 'Sin número'}</span>
                        <small>${telefono.tipo || 'CELULAR'}</small>
                        <button type="button" aria-label="Quitar teléfono" onclick="quitarTelefonoResponsable(${indice})">×</button>
                    `;
                    contenedor.appendChild(chip);
                });
            }

            function renderRubrosResponsable() {
                const contenedor = document.getElementById('listaRubrosResponsable');
                contenedor.innerHTML = '';

                if (!rubrosResponsable.length) {
                    contenedor.innerHTML = '<span class="responsable-empty-list">Sin rubros registrados.</span>';
                    return;
                }

                rubrosResponsable.forEach((rubro, indice) => {
                    const chip = document.createElement('span');
                    chip.className = 'responsable-chip';
                    chip.innerHTML = `
                        <span>${rubro.nombre || 'Sin rubro'}</span>
                        <small>${rubro.estado || 'ACTIVO'}</small>
                        <button type="button" aria-label="Quitar rubro" onclick="quitarRubroResponsable(${indice})">×</button>
                    `;
                    contenedor.appendChild(chip);
                });
            }

            function limpiarDatosParaNuevoResponsable() {
                Object.values(camposPersona).forEach(elemento => {
                    elemento.value = '';
                });

                camposPersona.estado.value = 'ACTIVO';
                camposPersona.genero.value = '';
                document.getElementById('form_id_rol').value = '';
                document.getElementById('form_fecha_registro').value = '';
                document.getElementById('form_fecha_baja').value = '';
                document.getElementById('form_estado').value = 'ACTIVO';
                limpiarRespaldoResponsable('Seleccione un respaldo PDF si corresponde.');

                telefonosResponsable = [];
                rubrosResponsable = [];
                guardarListasResponsable();
                renderTelefonosResponsable();
                renderRubrosResponsable();
            }

            window.agregarTelefonoResponsable = function () {
                const numero = document.getElementById('telefono_numero');
                const tipo = document.getElementById('telefono_tipo');

                if (!numero.value.trim()) return;

                telefonosResponsable.push({
                    numero: numero.value.trim(),
                    tipo: tipo.value || 'CELULAR'
                });

                numero.value = '';
                guardarListasResponsable();
                renderTelefonosResponsable();
            };

            window.quitarTelefonoResponsable = function (indice) {
                telefonosResponsable.splice(indice, 1);
                guardarListasResponsable();
                renderTelefonosResponsable();
            };

            window.agregarRubroResponsable = function () {
                const nombre = document.getElementById('rubro_nombre');
                const estado = document.getElementById('rubro_estado');

                if (!nombre.value.trim()) return;

                rubrosResponsable.push({
                    nombre: nombre.value.trim(),
                    estado: estado.value || 'ACTIVO'
                });

                nombre.value = '';
                guardarListasResponsable();
                renderRubrosResponsable();
            };

            window.quitarRubroResponsable = function (indice) {
                rubrosResponsable.splice(indice, 1);
                guardarListasResponsable();
                renderRubrosResponsable();
            };

            pdfInputResponsable.addEventListener('change', () => {
                const archivo = pdfInputResponsable.files?.[0];
                if (!archivo) return;

                // Solo se permite PDF para mantener el respaldo uniforme.
                const esPdf = archivo.type === 'application/pdf' || archivo.name.toLowerCase().endsWith('.pdf');
                if (!esPdf) {
                    pdfInputResponsable.value = '';
                    actualizarVistaRespaldoResponsable(
                        respaldoInicialResponsable.nombre,
                        respaldoInicialResponsable.url ? 'PDF guardado actualmente.' : 'Seleccione un respaldo PDF si corresponde.',
                        respaldoInicialResponsable.url,
                        Boolean(respaldoInicialResponsable.url)
                    );
                    alert('Solo se permiten archivos PDF.');
                    return;
                }

                liberarPdfTemporalResponsable();
                pdfTemporalResponsable = URL.createObjectURL(archivo);
                quitarPdfResponsable.value = '0';
                actualizarVistaRespaldoResponsable(
                    archivo.name,
                    'PDF seleccionado para guardar como respaldo.',
                    pdfTemporalResponsable,
                    true
                );
            });

            btnVerPdfResponsable.addEventListener('click', () => {
                const url = btnVerPdfResponsable.dataset.pdfUrl;
                if (url) window.open(url, '_blank');
            });

            btnQuitarPdfResponsable.addEventListener('click', () => {
                limpiarRespaldoResponsable();
            });

            document.getElementById('form_id_persona').addEventListener('change', (evento) => {
                if (evento.target.value === valorNuevoResponsable) {
                    limpiarDatosParaNuevoResponsable();
                    return;
                }

                const persona = personasResponsables.find(item => String(item.id) === String(evento.target.value));

                if (!persona) return;

                // Si se elige una persona existente, se cargan sus datos reales para editarlos.
                Object.entries(camposPersona).forEach(([campo, elemento]) => {
                    elemento.value = persona[campo] ?? '';
                });

                telefonosResponsable = Array.isArray(persona.telefonos) ? persona.telefonos : [];
                rubrosResponsable = Array.isArray(persona.rubros) ? persona.rubros : [];
                // Al volver a una persona existente, se conserva el respaldo original mientras no se suba otro PDF.
                if (!pdfInputResponsable.files.length && respaldoInicialResponsable.url) {
                    quitarPdfResponsable.value = '0';
                    actualizarVistaRespaldoResponsable(
                        respaldoInicialResponsable.nombre,
                        'PDF guardado actualmente.',
                        respaldoInicialResponsable.url,
                        true
                    );
                }
                guardarListasResponsable();
                renderTelefonosResponsable();
                renderRubrosResponsable();
            });

            guardarListasResponsable();
            renderTelefonosResponsable();
            renderRubrosResponsable();
        });
    </script>
</x-admin-layout>
