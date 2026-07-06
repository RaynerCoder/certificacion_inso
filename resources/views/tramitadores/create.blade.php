<x-admin-layout title="Registrar Tramitador | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Tramitadores', 'href' => Route::has('tramitadores_index') ? route('tramitadores_index') : '#'],
    ['name' => 'Registrar'],
]">

    @php
        // Colecciones seguras: permiten abrir la vista aunque el controlador todavia no envie datos.
        $empresas = collect($empresas ?? []);
        $territorios = collect($territorios ?? []);
        $roles = collect($roles ?? []);
        $rolTramitador = $roles->first(fn ($rol) => $rol->slug === 'tramitador' || str_contains(mb_strtoupper($rol->name), 'TRAMITADOR'));
        $rolSeleccionado = old('form_id_rol', $rolTramitador?->id);

        // Opciones para WireUI Select: permiten buscar sin crear estilos propios ni cambiar la logica del controlador.
        $opcionesEmpresas = $empresas->map(function ($empresa) {
            $razonSocial = $empresa->razon_social ?? $empresa->persona?->empresa?->razon_social ?? 'Empresa sin razon social';
            $nit = $empresa->persona?->nit ?: 'Sin NIT';

            return [
                'id' => $empresa->id,
                'nombre' => $razonSocial,
                'detalle' => 'NIT: ' . $nit,
            ];
        })->values()->toArray();

        // Opciones de roles disponibles para relacionar al tramitador con la empresa.
        $opcionesRoles = $roles->map(fn ($rol) => [
            'id' => $rol->id,
            'nombre' => $rol->name,
        ])->values()->toArray();

        // Opciones de territorio para ubicar a la persona natural que se registra.
        $opcionesTerritorios = $territorios->map(fn ($territorio) => [
            'id' => $territorio->id,
            'nombre' => $territorio->nombre,
        ])->values()->toArray();

        // Catalogos simples usados por WireUI Select dentro del mismo formulario.
        $opcionesEstados = [
            ['id' => 'ACTIVO', 'nombre' => 'Activo'],
            ['id' => 'INACTIVO', 'nombre' => 'Inactivo'],
        ];

        $opcionesGeneros = [
            ['id' => '1', 'nombre' => 'Masculino'],
            ['id' => '0', 'nombre' => 'Femenino'],
        ];

        $opcionesTiposTelefono = [
            ['id' => 'CELULAR', 'nombre' => 'Celular'],
            ['id' => 'FIJO', 'nombre' => 'Fijo'],
            ['id' => 'REFERENCIA', 'nombre' => 'Referencia'],
        ];

        // La ruta propia se usara cuando exista el modulo; por ahora evita romper la vista.
        $accionFormulario = Route::has('tramitadores_store') ? route('tramitadores_store') : '#';
        $rutaVolver = Route::has('tramitadores_index') ? route('tramitadores_index') : (Route::has('responsables_index') ? route('responsables_index') : '#');
    @endphp

    <style>
        /* Pantalla principal del registro de tramitadores. */
        .tramitador-shell {
            display: grid;
            gap: 14px;
        }

        .tramitador-panel {
            /* WireUI Select despliega opciones fuera del alto del panel; por eso no se debe recortar el contenido. */
            position: relative;
            overflow: visible;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }

        /* Cuando un campo esta activo, el panel queda por encima de los paneles siguientes. */
        .tramitador-panel:focus-within {
            z-index: 30;
        }

        .tramitador-panel-head {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(90deg, #f0fdfa, #ffffff);
            padding: 12px 14px;
        }

        .tramitador-panel-head.is-persona {
            background: linear-gradient(90deg, #f5f3ff, #ffffff);
        }

        .tramitador-panel-head.is-contacto {
            background: linear-gradient(90deg, #ecfdf5, #ffffff);
        }

        .tramitador-panel-head.is-rubro {
            background: linear-gradient(90deg, #fffbeb, #ffffff);
        }

        .tramitador-panel-icon {
            display: inline-flex;
            width: 32px;
            height: 32px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #0d9488;
            color: #ffffff;
            font-size: 14px;
        }

        .tramitador-panel-head.is-persona .tramitador-panel-icon {
            background: #7c3aed;
        }

        .tramitador-panel-head.is-contacto .tramitador-panel-icon {
            background: #059669;
        }

        .tramitador-panel-head.is-rubro .tramitador-panel-icon {
            background: #d97706;
        }

        .tramitador-panel-title {
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.2;
        }

        .tramitador-panel-subtitle {
            margin: 2px 0 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .tramitador-panel-body {
            padding: 16px;
        }

        .tramitador-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px;
        }

        .tramitador-col-2 { grid-column: span 2; }
        .tramitador-col-3 { grid-column: span 3; }
        .tramitador-col-4 { grid-column: span 4; }
        .tramitador-col-5 { grid-column: span 5; }
        .tramitador-col-6 { grid-column: span 6; }
        .tramitador-col-8 { grid-column: span 8; }
        .tramitador-col-12 { grid-column: span 12; }

        .tramitador-list-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            padding: 10px;
        }

        .tramitador-list-title {
            color: #334155;
            font-size: 12px;
            font-weight: 900;
        }

        .tramitador-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 8px;
        }

        .tramitador-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #ffffff;
            padding: 5px 8px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .tramitador-chip button {
            color: #dc2626;
            font-size: 11px;
            font-weight: 900;
        }

        .tramitador-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .tramitador-btn {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 7px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 900;
            line-height: 1;
            white-space: nowrap;
        }

        .tramitador-btn.is-light {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
        }

        .tramitador-btn.is-primary {
            border: 1px solid #059669;
            background: #059669;
            color: #ffffff;
        }

        .tramitador-btn.is-inline-add {
            width: 170px;
            min-height: 40px;
            padding-inline: 12px;
            font-size: 12px;
        }

        /* Control compacto para PDF: mantiene el mismo estilo usado en productos, responsables y trámites. */
        .tramitador-pdf-control {
            display: flex;
            width: 100%;
            min-height: 38px;
            align-items: center;
            justify-content: space-between;
            gap: 9px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            padding: 5px 7px;
        }

        .tramitador-pdf-input {
            display: none;
        }

        .tramitador-pdf-info {
            display: flex;
            min-width: 0;
            flex: 1 1 auto;
            align-items: center;
            gap: 7px;
        }

        .tramitador-pdf-info i {
            display: inline-flex;
            width: 18px;
            height: 26px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            color: #ef4444;
            font-size: 16px;
        }

        .tramitador-pdf-info div {
            min-width: 0;
        }

        .tramitador-pdf-info strong {
            display: block;
            overflow: hidden;
            max-width: 100%;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #334155;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.2;
        }

        .tramitador-pdf-info span {
            display: none;
        }

        .tramitador-pdf-actions {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 5px;
        }

        .tramitador-pdf-button {
            display: inline-flex;
            min-height: 27px;
            align-items: center;
            justify-content: center;
            gap: 5px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            padding: 0 8px;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
            transition: background 150ms ease, border-color 150ms ease, color 150ms ease;
        }

        .tramitador-pdf-button:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        .tramitador-pdf-button.is-select {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
            cursor: pointer;
        }

        .tramitador-pdf-button.is-view {
            border-color: #bae6fd;
            background: #f0f9ff;
            color: #0369a1;
        }

        .tramitador-pdf-button.is-remove {
            border-color: #fecaca;
            background: #fff7f7;
            color: #dc2626;
        }

        .tramitador-pdf-button:not(:disabled):hover {
            border-color: #0d9488;
            color: #0f766e;
        }

        @media (max-width: 900px) {
            .tramitador-col-2,
            .tramitador-col-3,
            .tramitador-col-4,
            .tramitador-col-5,
            .tramitador-col-6,
            .tramitador-col-8 {
                grid-column: span 12;
            }

            .tramitador-pdf-control {
                align-items: stretch;
                flex-wrap: wrap;
                min-height: auto;
            }

            .tramitador-pdf-actions {
                width: 100%;
            }

            .tramitador-pdf-button {
                flex: 1 1 0;
            }

            .tramitador-btn.is-inline-add {
                width: 170px;
            }
        }

        @media (max-width: 640px) {
            .tramitador-btn,
            .tramitador-btn.is-inline-add {
                width: 100%;
            }
        }
    </style>

    <form action="{{ $accionFormulario }}" method="POST" enctype="multipart/form-data" class="tramitador-shell" autocomplete="off">
        @csrf

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-5">
                <div class="absolute inset-x-0 top-0 h-1 bg-emerald-600"></div>
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                            Registrar tramitador
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Registre una persona natural como tramitador de una empresa.
                        </p>
                    </div>

                    <a href="{{ $rutaVolver }}" class="tramitador-btn is-light">
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </div>
        </div>

        {{-- Relacion empresa-responsable: llena responsables. --}}
        <section class="tramitador-panel">
            <div class="tramitador-panel-head">
                <span class="tramitador-panel-icon">
                    <i class="fa-regular fa-building"></i>
                </span>
                <div>
                    <h2 class="tramitador-panel-title">Empresa y rol del tramitador</h2>
                    <p class="tramitador-panel-subtitle">Estos datos relacionan la empresa con la persona natural.</p>
                </div>
            </div>

            <div class="tramitador-panel-body">
                <div class="tramitador-grid">
                    <div class="tramitador-col-6">
                        <x-wire-select label="Empresa" name="form_id_empresa" placeholder="Buscar empresa"
                            searchable :options="$opcionesEmpresas" option-label="nombre" option-value="id"
                            option-description="detalle" :value="old('form_id_empresa')" />
                    </div>

                    <div class="tramitador-col-6">
                        <x-wire-select label="Rol en la empresa" name="form_id_rol" placeholder="Seleccione rol"
                            :options="$opcionesRoles" option-label="nombre" option-value="id"
                            :value="$rolSeleccionado" />
                    </div>

                    <div class="tramitador-col-4">
                        <x-wire-datetime-picker label="Fecha de registro" name="form_fecha_registro" without-time
                            :value="old('form_fecha_registro', now()->toDateString())" />
                    </div>

                    <div class="tramitador-col-4">
                        <x-wire-select label="Estado de la relacion" name="form_estado" placeholder="Seleccione estado"
                            :options="$opcionesEstados" option-label="nombre" option-value="id"
                            :value="old('form_estado', 'ACTIVO')" />
                    </div>

                    <div class="tramitador-col-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="form_url_respaldo">
                            Respaldo PDF
                        </label>

                        {{-- El input real queda oculto; los botones mantienen el estilo usado en los demás PDF del sistema. --}}
                        <div class="tramitador-pdf-control">
                            <input id="form_url_respaldo" name="form_url_respaldo" type="file"
                                accept="application/pdf,.pdf" class="tramitador-pdf-input">

                            <div class="tramitador-pdf-info">
                                <i class="fa-regular fa-file-pdf"></i>
                                <div>
                                    <strong id="tramitadorPdfNombre">Sin PDF seleccionado</strong>
                                    <span id="tramitadorPdfEstado">Seleccione un respaldo PDF si corresponde.</span>
                                </div>
                            </div>

                            <div class="tramitador-pdf-actions">
                                <label for="form_url_respaldo" class="tramitador-pdf-button is-select">
                                    <i class="fa-solid fa-upload"></i>
                                    <span>Seleccionar</span>
                                </label>

                                <button type="button" id="btnVerPdfTramitador"
                                    class="tramitador-pdf-button is-view" disabled>
                                    <i class="fa-solid fa-eye"></i>
                                    <span>Ver</span>
                                </button>

                                <button type="button" id="btnQuitarPdfTramitador"
                                    class="tramitador-pdf-button is-remove" disabled>
                                    <i class="fa-solid fa-xmark"></i>
                                    <span>Quitar</span>
                                </button>
                            </div>
                        </div>

                        @error('form_url_respaldo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- Datos base de persona: llena personas. --}}
        <section class="tramitador-panel">
            <div class="tramitador-panel-head">
                <span class="tramitador-panel-icon">
                    <i class="fa-regular fa-address-card"></i>
                </span>
                <div>
                    <h2 class="tramitador-panel-title">Datos generales de la persona</h2>
                    <p class="tramitador-panel-subtitle">Informacion comun registrada en la tabla personas.</p>
                </div>
            </div>

            <div class="tramitador-panel-body">
                <div class="tramitador-grid">
                    <div class="tramitador-col-4">
                        <x-wire-input label="Correo" name="form_correo" type="email" placeholder="correo@dominio.com"
                            value="{{ old('form_correo') }}" />
                    </div>

                    <div class="tramitador-col-4">
                        <x-wire-input label="NIT personal (opcional)" name="form_nit" placeholder="Sin NIT"
                            value="{{ old('form_nit') }}" />
                    </div>

                    <div class="tramitador-col-4">
                        <x-wire-select label="Territorio" name="form_id_territorio" placeholder="Buscar territorio"
                            searchable :options="$opcionesTerritorios" option-label="nombre" option-value="id"
                            :value="old('form_id_territorio')" />
                    </div>

                    <div class="tramitador-col-12">
                        <x-wire-input label="Domicilio" name="form_domicilio" placeholder="Ingrese domicilio"
                            value="{{ old('form_domicilio') }}" />
                    </div>
                </div>
            </div>
        </section>

        {{-- Datos especificos de persona natural: llena naturals. --}}
        <section class="tramitador-panel">
            <div class="tramitador-panel-head is-persona">
                <span class="tramitador-panel-icon">
                    <i class="fa-regular fa-user"></i>
                </span>
                <div>
                    <h2 class="tramitador-panel-title">Datos de persona natural</h2>
                    <p class="tramitador-panel-subtitle">Identificacion personal del tramitador.</p>
                </div>
            </div>

            <div class="tramitador-panel-body">
                <div class="tramitador-grid">
                    <div class="tramitador-col-4">
                        <x-wire-input label="Nombres" name="form_nombres" placeholder="Ingrese nombres"
                            value="{{ old('form_nombres') }}" />
                    </div>

                    <div class="tramitador-col-4">
                        <x-wire-input label="Apellido paterno" name="form_apellido_paterno" placeholder="Apellido paterno"
                            value="{{ old('form_apellido_paterno') }}" />
                    </div>

                    <div class="tramitador-col-4">
                        <x-wire-input label="Apellido materno" name="form_apellido_materno" placeholder="Apellido materno"
                            value="{{ old('form_apellido_materno') }}" />
                    </div>

                    <div class="tramitador-col-3">
                        <x-wire-input label="CI" name="form_ci" placeholder="Carnet de identidad"
                            value="{{ old('form_ci') }}" />
                    </div>

                    <div class="tramitador-col-3">
                        <x-wire-input label="Complemento" name="form_complemento" placeholder="Complemento"
                            value="{{ old('form_complemento') }}" />
                    </div>

                    <div class="tramitador-col-3">
                        <x-wire-input label="Expedido" name="form_expedido" placeholder="LP, CB, SC..."
                            value="{{ old('form_expedido') }}" />
                    </div>

                    <div class="tramitador-col-3">
                        <x-wire-select label="Genero" name="form_genero" placeholder="Seleccione genero"
                            :options="$opcionesGeneros" option-label="nombre" option-value="id"
                            :value="old('form_genero')" />
                    </div>

                    <div class="tramitador-col-4">
                        <x-wire-datetime-picker label="Fecha de nacimiento" name="form_fecha_nacimiento" without-time
                            :value="old('form_fecha_nacimiento')" />
                    </div>

                    <div class="tramitador-col-8">
                        <x-wire-input label="Ocupacion" name="form_ocupacion" placeholder="Ejemplo: Tramitador externo"
                            value="{{ old('form_ocupacion') }}" />
                    </div>
                </div>
            </div>
        </section>

        {{-- Telefonos: se enviaran como JSON para llenar telefonos. --}}
        <section class="tramitador-panel">
            <div class="tramitador-panel-head is-contacto">
                <span class="tramitador-panel-icon">
                    <i class="fa-solid fa-phone"></i>
                </span>
                <div>
                    <h2 class="tramitador-panel-title">Telefonos</h2>
                    <p class="tramitador-panel-subtitle">Agregue uno o varios telefonos del tramitador.</p>
                </div>
            </div>

            <div class="tramitador-panel-body">
                <input type="hidden" name="form_telefonos_json" id="form_telefonos_json" value="{{ old('form_telefonos_json', '[]') }}">

                <div class="tramitador-grid">
                    <div class="tramitador-col-5">
                        <x-wire-input label="Telefono" id="telefonoNumero" placeholder="Ejemplo: 70123456" />
                    </div>

                    <div class="tramitador-col-3">
                        <x-wire-select label="Tipo" id="telefonoTipo" placeholder="Seleccione tipo"
                            :options="$opcionesTiposTelefono" option-label="nombre" option-value="id"
                            :value="old('telefonoTipo', 'CELULAR')" />
                    </div>

                    <div class="tramitador-col-4 flex items-end">
                        <button type="button" class="tramitador-btn is-primary is-inline-add" onclick="agregarTelefonoTramitador()">
                            <i class="fa-solid fa-plus"></i>
                            Agregar telefono
                        </button>
                    </div>

                    <div class="tramitador-col-12">
                        <div class="tramitador-list-box">
                            <p class="tramitador-list-title">Telefonos agregados</p>
                            <div id="telefonosLista" class="tramitador-chip-list">
                                <span class="text-sm text-slate-500">Sin telefonos agregados.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Rubros: se enviaran como JSON para llenar rubros. --}}
        <section class="tramitador-panel">
            <div class="tramitador-panel-head is-rubro">
                <span class="tramitador-panel-icon">
                    <i class="fa-solid fa-briefcase"></i>
                </span>
                <div>
                    <h2 class="tramitador-panel-title">Rubros o actividad</h2>
                    <p class="tramitador-panel-subtitle">Actividad relacionada con la persona natural tramitadora.</p>
                </div>
            </div>

            <div class="tramitador-panel-body">
                <input type="hidden" name="form_rubros_json" id="form_rubros_json" value="{{ old('form_rubros_json', '[]') }}">

                <div class="tramitador-grid">
                    <div class="tramitador-col-6">
                        <x-wire-input label="Nombre del rubro" id="rubroNombre" placeholder="Ejemplo: Tramitacion y representacion legal" />
                    </div>

                    <div class="tramitador-col-3">
                        <x-wire-select label="Estado" id="rubroEstado" placeholder="Seleccione estado"
                            :options="$opcionesEstados" option-label="nombre" option-value="id"
                            :value="old('rubroEstado', 'ACTIVO')" />
                    </div>

                    <div class="tramitador-col-3 flex items-end">
                        <button type="button" class="tramitador-btn is-primary is-inline-add" onclick="agregarRubroTramitador()">
                            <i class="fa-solid fa-plus"></i>
                            Agregar rubro
                        </button>
                    </div>

                    <div class="tramitador-col-12">
                        <div class="tramitador-list-box">
                            <p class="tramitador-list-title">Rubros agregados</p>
                            <div id="rubrosLista" class="tramitador-chip-list">
                                <span class="text-sm text-slate-500">Sin rubros agregados.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="tramitador-actions">
            <a href="{{ $rutaVolver }}" class="tramitador-btn is-light">
                Salir sin guardar
            </a>

            <button type="submit" class="tramitador-btn is-primary" @disabled(! Route::has('tramitadores_store'))>
                Guardar tramitador
            </button>
        </div>
    </form>

    <script>
        // Listas temporales del formulario. El controlador las recibira como JSON.
        let telefonosTramitador = JSON.parse(document.getElementById('form_telefonos_json')?.value || '[]');
        let rubrosTramitador = JSON.parse(document.getElementById('form_rubros_json')?.value || '[]');
        let pdfTemporalTramitador = null;

        // Control del PDF: permite seleccionar, ver y quitar sin cambiar el nombre del campo que recibe Laravel.
        const inputPdfTramitador = document.getElementById('form_url_respaldo');
        const nombrePdfTramitador = document.getElementById('tramitadorPdfNombre');
        const estadoPdfTramitador = document.getElementById('tramitadorPdfEstado');
        const btnVerPdfTramitador = document.getElementById('btnVerPdfTramitador');
        const btnQuitarPdfTramitador = document.getElementById('btnQuitarPdfTramitador');

        // Evita insertar texto sin escapar dentro de chips generados por JavaScript.
        function escaparTramitadorHtml(valor) {
            return String(valor ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Libera la URL temporal cuando se reemplaza o quita el PDF seleccionado.
        function liberarPdfTemporalTramitador() {
            if (pdfTemporalTramitador) {
                URL.revokeObjectURL(pdfTemporalTramitador);
                pdfTemporalTramitador = null;
            }
        }

        // Actualiza el nombre, estado y botones del control PDF.
        function actualizarVistaPdfTramitador(nombre = 'Sin PDF seleccionado', estado = '', url = '') {
            nombrePdfTramitador.textContent = nombre;
            estadoPdfTramitador.textContent = estado;
            btnVerPdfTramitador.dataset.pdfUrl = url;
            btnVerPdfTramitador.disabled = !url;
            btnQuitarPdfTramitador.disabled = !url;
        }

        // Quita el PDF temporal y deja el campo limpio antes de enviar el formulario.
        function limpiarPdfTramitador() {
            inputPdfTramitador.value = '';
            liberarPdfTemporalTramitador();
            actualizarVistaPdfTramitador();
        }

        // Actualiza el input oculto de telefonos y su lista visual.
        function renderTelefonosTramitador() {
            const input = document.getElementById('form_telefonos_json');
            const lista = document.getElementById('telefonosLista');

            input.value = JSON.stringify(telefonosTramitador);

            if (telefonosTramitador.length === 0) {
                lista.innerHTML = '<span class="text-sm text-slate-500">Sin telefonos agregados.</span>';
                return;
            }

            lista.innerHTML = telefonosTramitador.map((telefono, index) => `
                <span class="tramitador-chip">
                    ${escaparTramitadorHtml(telefono.numero)} - ${escaparTramitadorHtml(telefono.tipo)}
                    <button type="button" onclick="quitarTelefonoTramitador(${index})">Quitar</button>
                </span>
            `).join('');
        }

        // Agrega un telefono a la lista temporal del formulario.
        function agregarTelefonoTramitador() {
            const numero = document.getElementById('telefonoNumero')?.value.trim();
            const tipo = document.getElementById('telefonoTipo')?.value || 'CELULAR';

            if (!numero) {
                return;
            }

            telefonosTramitador.push({ numero, tipo });
            document.getElementById('telefonoNumero').value = '';
            renderTelefonosTramitador();
        }

        // Quita un telefono de la lista temporal.
        function quitarTelefonoTramitador(index) {
            telefonosTramitador.splice(index, 1);
            renderTelefonosTramitador();
        }

        // Actualiza el input oculto de rubros y su lista visual.
        function renderRubrosTramitador() {
            const input = document.getElementById('form_rubros_json');
            const lista = document.getElementById('rubrosLista');

            input.value = JSON.stringify(rubrosTramitador);

            if (rubrosTramitador.length === 0) {
                lista.innerHTML = '<span class="text-sm text-slate-500">Sin rubros agregados.</span>';
                return;
            }

            lista.innerHTML = rubrosTramitador.map((rubro, index) => `
                <span class="tramitador-chip">
                    ${escaparTramitadorHtml(rubro.nombre)} - ${escaparTramitadorHtml(rubro.estado)}
                    <button type="button" onclick="quitarRubroTramitador(${index})">Quitar</button>
                </span>
            `).join('');
        }

        // Agrega un rubro a la lista temporal del formulario.
        function agregarRubroTramitador() {
            const nombre = document.getElementById('rubroNombre')?.value.trim();
            const estado = document.getElementById('rubroEstado')?.value || 'ACTIVO';

            if (!nombre) {
                return;
            }

            rubrosTramitador.push({ nombre, estado });
            document.getElementById('rubroNombre').value = '';
            renderRubrosTramitador();
        }

        // Quita un rubro de la lista temporal.
        function quitarRubroTramitador(index) {
            rubrosTramitador.splice(index, 1);
            renderRubrosTramitador();
        }

        inputPdfTramitador?.addEventListener('change', () => {
            const archivo = inputPdfTramitador.files?.[0];

            if (!archivo) {
                limpiarPdfTramitador();
                return;
            }

            // Valida PDF en frontend; Laravel vuelve a validar en backend.
            const esPdf = archivo.type === 'application/pdf' || archivo.name.toLowerCase().endsWith('.pdf');

            if (!esPdf) {
                limpiarPdfTramitador();

                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivo no permitido',
                        text: 'Solo se permiten archivos PDF.',
                        confirmButtonText: 'Entendido',
                    });
                }

                return;
            }

            liberarPdfTemporalTramitador();
            pdfTemporalTramitador = URL.createObjectURL(archivo);
            actualizarVistaPdfTramitador(archivo.name, 'PDF seleccionado para guardar.', pdfTemporalTramitador);
        });

        btnVerPdfTramitador?.addEventListener('click', () => {
            const url = btnVerPdfTramitador.dataset.pdfUrl;

            if (url) {
                window.open(url, '_blank');
            }
        });

        btnQuitarPdfTramitador?.addEventListener('click', limpiarPdfTramitador);

        renderTelefonosTramitador();
        renderRubrosTramitador();
    </script>
</x-admin-layout>
