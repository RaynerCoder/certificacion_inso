<x-admin-layout title="Persona y Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Personas y Empresas',
        'href' => '#',
    ],
    [
        'name' => 'Registrar Persona',
        'href' => route('personas_create'),
    ],
]">

    @include('personas.create.estilos')

    @php
        // Mensajes cortos para que el usuario no vea nombres tecnicos como form_id_territorio.
        function mensajePersonaWizard(string $campo): string
        {
            $campoBase = preg_replace('/\.\d+\..*/', '', $campo);

            $mensajes = [
                'form_tipo_registro' => 'Seleccione el tipo de registro.',
                'form_id_pais' => 'Seleccione pais.',
                'form_id_territorio' => 'Seleccione departamento.',
                'form_correo' => 'Ingrese un correo electronico valido.',
                'form_nit' => 'Ingrese un NIT unico.',
                'form_ci' => 'Ingrese el CI.',
                'form_nombres' => 'Ingrese los nombres.',
                'form_apellido_paterno' => 'Ingrese el apellido paterno.',
                'form_genero' => 'Seleccione el genero.',
                'form_id_tipo_empresa' => 'Seleccione el tipo de empresa.',
                'form_razon_social' => 'Ingrese la razon social.',
                'form_matricula' => 'Ingrese la matricula.',
                'form_usuario_name' => 'Ingrese el nombre de usuario.',
                'form_usuario_email' => 'Ingrese un correo de acceso valido.',
                'form_usuario_password' => 'Ingrese una contrasena de al menos 8 caracteres.',
                'form_id_role' => 'Seleccione el rol de acceso.',
                'responsables.id_rol' => 'Seleccione el rol del responsable.',
                'telefonos' => 'Agregue al menos un telefono valido.',
                'rubros' => 'Revise los rubros agregados.',
                'responsables' => 'Revise los responsables agregados.',
            ];

            if (str_starts_with($campo, 'responsables.') && str_contains($campo, '.id_rol')) {
                return 'Seleccione el rol del responsable.';
            }

            if (str_starts_with($campo, 'responsables.') && str_contains($campo, '.correo')) {
                return 'Ingrese el correo del responsable.';
            }

            if (str_starts_with($campo, 'responsables.') && str_contains($campo, '.nit')) {
                return 'Ingrese un NIT de responsable que no este registrado.';
            }

            if (str_starts_with($campo, 'responsables.') && str_contains($campo, '.ci')) {
                return 'Ingrese un CI de responsable que no este registrado.';
            }

            if (str_starts_with($campo, 'responsables.') && str_contains($campo, '.id_territorio')) {
                return 'Seleccione el territorio del responsable.';
            }

            if (str_starts_with($campo, 'responsables.') && str_contains($campo, '.id_persona')) {
                return 'Seleccione la persona responsable.';
            }

            return $mensajes[$campo] ?? $mensajes[$campoBase] ?? 'Revise este dato: ' . str_replace(['form_', '_'], ['', ' '], $campoBase) . '.';
        }
    @endphp

    {{-- Formulario principal: conserva la ruta actual y envia todo al controlador existente. --}}
    <form id="formPersonaWizard" action="{{ route('personas_store') }}" method="POST" enctype="multipart/form-data"
        class="persona-wizard" autocomplete="off" data-modo-formulario="create"
        data-tiene-errores="{{ $errors->any() ? '1' : '0' }}">
        @csrf

        {{-- Encabezado principal --}}
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-6">
                {{-- Decoración superior --}}
                <div class="absolute inset-x-0 top-0 h-1 bg-emerald-600"></div>
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

                    {{-- Información --}}
                    <div>

                        <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                            Registrar Persona
                        </h1>

                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Complete la información de la persona natural o jurídica/empresa
                            para continuar con el proceso de registro.
                        </p>

                    </div>

                    {{-- Acciones superiores: permiten salir del registro sin enviar nada al controlador. --}}
                    <div class="persona-edit-top-actions">
                        <a href="{{ route('personas_index') }}" class="persona-btn persona-btn-light">
                            Salir sin registrar
                        </a>

                        <div id="estadoBorrador"
                            class="inline-flex items-center gap-3 rounded-2xl
                           border border-slate-200 bg-slate-50
                           px-5 py-3 text-sm font-medium text-slate-700 shadow-sm">

                            <span class="relative flex h-3 w-3">

                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75">
                                </span>

                                <span class="relative inline-flex h-3 w-3 rounded-full bg-amber-500">
                                </span>

                            </span>

                            Formulario en edición

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Burbujas horizontales: siempre se mantienen en fila, con scroll en pantallas pequenas. --}}
        <div class="persona-stepper-card">
            <div id="pasosPersonaWizard" class="persona-stepper">
                <button type="button" data-wizard-ir="0" class="paso-burbuja">
                    <span class="paso-circulo">1</span>
                    <span>Tipo</span>
                </button>

                <button type="button" data-wizard-ir="1" class="paso-burbuja">
                    <span class="paso-circulo">2</span>
                    <span>Datos generales</span>
                </button>

                <button type="button" data-wizard-ir="2" class="paso-burbuja">
                    <span class="paso-circulo">3</span>
                    <span>Datos específicos</span>
                </button>

                <button type="button" data-wizard-ir="3" class="paso-burbuja">
                    <span class="paso-circulo">4</span>
                    <span>Complementos</span>
                </button>

                <button type="button" data-wizard-ir="4" class="paso-burbuja">
                    <span class="paso-circulo">5</span>
                    <span>Cuenta</span>
                </button>

                <button type="button" data-wizard-ir="5" class="paso-burbuja">
                    <span class="paso-circulo">6</span>
                    <span>Revisión</span>
                </button>
            </div>
        </div>

        <div class="persona-wizard-layout">
            {{-- Columna izquierda: formulario paso a paso. --}}
            <section class="persona-form-card">
                <div class="persona-form-head">
                    <div class="persona-form-head-left">
                        <div class="persona-form-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                            </svg>
                        </div>

                        <div>
                            <h2 id="tituloPasoWizard" class="persona-form-title">
                                Tipo de Registro
                            </h2>
                            <p id="subtituloPasoWizard" class="persona-form-subtitle">
                                Elija si registrara una persona natural o una empresa.
                            </p>
                        </div>
                    </div>

                    <span id="etiquetaTipoWizard" class="persona-type-pill">
                        Sin tipo seleccionado
                    </span>
                </div>

                {{-- Selector de tipo visual; el select real queda oculto, pero se sigue enviando igual. --}}
                <div class="persona-type-tabs">
                    <div class="persona-type-tabs-inner">
                        <button type="button" data-tipo-rapido="NATURAL" class="tipo-rapido">
                            Persona Natural
                            <span>
                                CI, teléfonos y rubros
                            </span>
                        </button>

                        <button type="button" data-tipo-rapido="EMPRESA" class="tipo-rapido">
                            Empresa
                            <span>
                                Tipo, matrícula y responsables
                            </span>
                        </button>
                    </div>
                </div>

                <div class="persona-form-body">
                    <div class="wizard-persona-step" data-wizard-step="0">
                        {{-- Select real para mantener el nombre form_tipo_registro del controlador. --}}
                        <div class="sr-only">
                            <x-wire-native-select label="Tipo de registro" id="tipo_registro" name="form_tipo_registro">
                                <option value="">Seleccione el tipo de registro</option>
                                <option value="NATURAL" @selected(old('form_tipo_registro') === 'NATURAL')>Persona natural</option>
                                <option value="EMPRESA" @selected(old('form_tipo_registro') === 'EMPRESA')>Empresa</option>
                            </x-wire-native-select>
                        </div>

                        {{-- <div class="persona-empty-step">
                            <strong>Seleccione una opcion para empezar.</strong>
                            <span>Despues podra avanzar y retroceder con las burbujas superiores.</span>
                        </div> --}}
                    </div>

                    <div class="wizard-persona-step persona-wizard-flat hidden" data-wizard-step="1">
                        @include('personas.create.informacion-general')
                    </div>

                    <div class="wizard-persona-step persona-wizard-flat hidden" data-wizard-step="2">
                        @include('personas.create.persona-natural')
                        @include('personas.create.empresa')
                    </div>

                    <div class="wizard-persona-step persona-wizard-flat hidden space-y-5" data-wizard-step="3">
                        {{-- Bloque separado: telefonos comunes para persona natural y empresa. --}}
                        <div class="wizard-section-block">
                            <div class="wizard-section-heading">
                                <span class="wizard-section-number">1</span>
                                <div>
                                    <h3>Telefonos de contacto</h3>
                                    <p>Agregue uno o varios numeros para comunicarse.</p>
                                </div>
                            </div>

                            @include('personas.create.telefonos')
                        </div>

                        {{-- Bloque separado: solo se muestra cuando el registro es Persona Natural. --}}
                        <div id="bloque_rubros_wizard" class="wizard-section-block is-soft">
                            <div class="wizard-section-heading">
                                <span class="wizard-section-number">2</span>
                                <div>
                                    <h3>Rubros o actividad economica</h3>
                                    <p>Registre los rubros relacionados con la persona natural.</p>
                                </div>
                            </div>

                            @include('personas.create.rubros')
                        </div>

                        {{-- Boton de responsables visible para empresa; no crea rutas ni cambia guardado. --}}
                        <div id="bloque_responsables_wizard" class="wizard-section-block is-soft hidden">
                            <div class="wizard-section-heading">
                                <span class="wizard-section-number">2</span>
                                <div>
                                    <h3>Responsables de la empresa</h3>
                                    <p>Agregue responsables existentes o registre uno nuevo desde el modal.</p>
                                </div>
                            </div>

                            <div id="accion_responsables_wizard" class="responsables-wizard-action">
                                <div>
                                    <h3>Agregar responsable</h3>
                                    <p>Puede seleccionar una persona existente o registrar una nueva.</p>
                                </div>

                                <button type="button" onclick="abrirModalResponsable()">
                                    Agregar Responsable
                                </button>
                            </div>

                            @include('personas.create.responsables')
                        </div>
                    </div>

                    <div class="wizard-persona-step persona-wizard-flat hidden" data-wizard-step="4">
                        @include('personas.create.cuenta-usuario')
                    </div>

                    <div class="wizard-persona-step hidden" data-wizard-step="5">
                        <div class="persona-review-intro">
                            <h3>Resumen antes de guardar</h3>
                            <p>Verifique los datos principales antes de enviar el registro definitivo.</p>
                        </div>

                        <div id="resumenPersonaWizard" class="persona-review-grid"></div>
                    </div>
                </div>

                {{-- Acciones inferiores del wizard. --}}
                <div class="persona-actions">
                    <p id="ayudaPersonaWizard" class="persona-help">
                        Seleccione el tipo de registro para comenzar.
                    </p>

                    {{-- Estado de envio: confirma que el guardado final ya esta en proceso. --}}
                    <div id="estadoEnvioPersona" class="persona-submit-status" aria-live="polite">
                        <span class="persona-submit-spinner"></span>
                        <span>Registrando datos...</span>
                        <span class="persona-submit-progress"></span>
                    </div>

                    <div class="persona-buttons">
                        <a href="{{ route('personas_index') }}" class="persona-btn persona-btn-light">
                            Salir sin registrar
                        </a>

                        <button type="button" id="btnPasoAnterior" class="persona-btn persona-btn-light">
                            Anterior
                        </button>

                        {{-- Guarda solo el avance temporal del navegador; la base de datos se guarda al final. --}}
                        <button type="button" id="btnGuardarBorrador" class="persona-btn persona-btn-light">
                            Guardar avance
                        </button>

                        <button type="button" id="btnPasoSiguiente" class="persona-btn persona-btn-primary">
                            Siguiente
                        </button>

                        {{-- Boton final: este es el unico que guarda en base de datos. --}}
                        <button id="btnGuardarRegistro" type="submit" class="persona-btn persona-btn-primary hidden">
                            Guardar registro
                        </button>
                    </div>
                </div>
            </section>

            {{-- Columna derecha: progreso del registro. --}}
            <aside class="persona-progress-card">
                <h2 class="persona-progress-title">
                    Progreso
                </h2>
                <p class="persona-progress-subtitle">
                    Avance de las secciones principales.
                </p>

                <div class="persona-progress-list">
                    <div id="progresoTipo" class="progreso-item">
                        <span class="progreso-punto">1</span>
                        <div class="progreso-item-box">
                            <div class="progreso-item-top">
                                <p class="progreso-label">Tipo seleccionado</p>
                                <span class="progreso-estado">Pendiente</span>
                            </div>
                        </div>
                    </div>

                    <div id="progresoGenerales" class="progreso-item">
                        <span class="progreso-punto">2</span>
                        <div class="progreso-item-box">
                            <div class="progreso-item-top">
                                <p class="progreso-label">Datos generales</p>
                                <span class="progreso-estado">Pendiente</span>
                            </div>
                        </div>
                    </div>

                    <div id="progresoEspecificos" class="progreso-item">
                        <span class="progreso-punto">3</span>
                        <div class="progreso-item-box">
                            <div class="progreso-item-top">
                                <p class="progreso-label">Datos especificos</p>
                                <span class="progreso-estado">Pendiente</span>
                            </div>
                        </div>
                    </div>

                    <div id="progresoTelefonos" class="progreso-item">
                        <span class="progreso-punto">4</span>
                        <div class="progreso-item-box">
                            <div class="progreso-item-top">
                                <p class="progreso-label">Telefonos pendientes</p>
                                <span class="progreso-estado">Pendiente</span>
                            </div>
                        </div>
                    </div>

                    <div id="progresoComplementos" class="progreso-item">
                        <span class="progreso-punto">5</span>
                        <div class="progreso-item-box">
                            <div class="progreso-item-top">
                                <p class="progreso-label">Rubros / Responsables</p>
                                <span class="progreso-estado">Pendiente</span>
                            </div>
                        </div>
                    </div>

                    <div id="progresoCuentaUsuario" class="progreso-item">
                        <span class="progreso-punto">6</span>
                        <div class="progreso-item-box">
                            <div class="progreso-item-top">
                                <p class="progreso-label">Cuenta de usuario</p>
                                <span class="progreso-estado">Pendiente</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if (session('error') || $errors->any())
                    {{-- Historial de errores: se coloca debajo del progreso para no cortar el llenado del wizard. --}}
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700">
                        <p class="font-bold">No se pudo guardar</p>
                        <p class="mt-1 text-xs text-red-600">Corrige estos datos para continuar:</p>

                        <ul class="mt-3 space-y-2">
                            @if (session('error'))
                                <li class="flex gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-red-500"></span>
                                    <span>{{ session('error') }}</span>
                                </li>
                            @endif

                            @foreach ($errors->getBag('default')->keys() as $campoError)
                                <li class="flex gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-red-500"></span>
                                    <span>{{ mensajePersonaWizard($campoError) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </form>

    @include('personas.create.scripts')

</x-admin-layout>
