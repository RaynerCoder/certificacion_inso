<x-admin-layout title="Registrar tramitador | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Tramitadores', 'href' => route('tramitadores_index')],
    ['name' => 'Registrar tramitador', 'href' => '#'],
]">
    {{-- Reutiliza exactamente los estilos del modal de responsables. --}}
    @include('personas.create.estilos')

    <style>
        #formTramitador .grid > *,
        #formTramitador fieldset {
            min-width: 0;
            width: 100%;
        }

        #datosPersonaTramitador[disabled] input:not([type="hidden"]),
        #datosPersonaTramitador[disabled] select,
        #datosPersonaTramitador[disabled] button {
            cursor: not-allowed !important;
        }

        #datosPersonaTramitador[disabled] input:not([type="hidden"]),
        #datosPersonaTramitador[disabled] select {
            background-color: #f8fafc !important;
            color: #475569 !important;
        }

        #datosPersonaTramitador[disabled] .ocupacion-persona-limpiar {
            display: none !important;
        }

        /* Acciones compactas para abrir, cerrar y limpiar los buscadores de catálogo. */
        .ocupacion-persona-alternar {
            display: inline-flex;
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            color: #64748b;
            transition: background-color 150ms ease, color 150ms ease;
        }

        .ocupacion-persona-alternar:hover,
        .ocupacion-persona-alternar[aria-expanded="true"] {
            background: #f0fdfa;
            color: #0d9488;
        }

        .ocupacion-persona-alternar i {
            pointer-events: none;
            font-size: 12px;
        }

    </style>

    <div class="w-full max-w-none">
        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="font-semibold">Revise los datos del tramitador.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="formTramitador" action="{{ route('tramitadores_store') }}" method="POST" novalidate
            enctype="multipart/form-data"
            class="w-full overflow-hidden rounded-2xl bg-white shadow-xl">
            @csrf

            <div class="flex items-center justify-between border-b border-violet-100 bg-gradient-to-r from-violet-50 to-purple-50 px-4 py-4 sm:px-6">
                <div>
                    <h1 class="text-lg font-bold text-gray-700">Registrar tramitador</h1>
                    <p class="text-xs text-gray-500">Registro y asignación de un tramitador para {{ $empresa->razon_social }}.</p>
                </div>
            </div>

            <div class="space-y-4 p-3 sm:space-y-6 sm:p-6">
                <section class="relative z-40 overflow-visible rounded-xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-100 px-4 py-2">
                        <h2 class="text-sm font-bold text-slate-700">1. Buscar persona por CI</h2>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                            <div class="md:col-span-5">
                                <label for="form_ci" class="mb-1 block text-sm font-medium text-gray-700">CI</label>
                                <input type="text" id="form_ci" name="form_ci" value="{{ old('form_ci') }}"
                                    maxlength="50" required autocomplete="off" placeholder="Ingrese el número de CI"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div class="md:col-span-3">
                                <label for="form_complemento" class="mb-1 block text-sm font-medium text-gray-700">Complemento</label>
                                <input type="text" id="form_complemento" name="form_complemento"
                                    value="{{ old('form_complemento') }}" maxlength="10" autocomplete="off"
                                    placeholder="Ej.: 1A"
                                    class="w-full rounded-lg border-gray-300 text-sm uppercase focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div class="md:col-span-4 md:flex md:items-end">
                                <button type="button" id="btnBuscarPersona"
                                    data-url="{{ route('tramitadores_buscar_persona') }}"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-wait disabled:opacity-60">
                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    <span>Buscar por CI</span>
                                </button>
                            </div>
                        </div>
                        <p id="estadoBusquedaPersona" role="status" aria-live="polite"
                            class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            Ingrese el CI y presione “Buscar por CI”. Si existe, los datos se completarán automáticamente.
                        </p>
                    </div>
                </section>

                <fieldset id="datosPersonaTramitador"
                    class="m-0 min-w-0 w-full space-y-4 border-0 p-0 sm:space-y-6">
                <section class="relative z-30 overflow-visible rounded-xl border border-blue-200">
                    <div class="border-b border-blue-100 bg-blue-50 px-4 py-2">
                        <h2 class="text-sm font-bold text-blue-700">2. Información general del tramitador</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                        <x-wire-input errorless label="Domicilio" id="form_domicilio" name="form_domicilio"
                            value="{{ old('form_domicilio') }}" placeholder="Dirección o domicilio" />
                        <x-wire-input errorless label="NIT" id="form_nit" name="form_nit"
                            value="{{ old('form_nit') }}" placeholder="NIT si corresponde" />
                        <x-wire-input errorless label="Correo electrónico" id="form_correo" name="form_correo" type="email"
                            value="{{ old('form_correo') }}" placeholder="correo@ejemplo.com" />

                        <div id="contenedorPaisTramitador" class="ocupacion-persona-autocomplete"
                            data-url-hijos="{{ route('tramitadores_territorios_hijos', ['territorio' => '__ID__']) }}"
                            data-url-ruta="{{ route('tramitadores_territorio_ruta', ['territorio' => '__ID__']) }}">
                            <input type="hidden" id="form_id_territorio" name="form_id_territorio" value="{{ old('form_id_territorio') }}">
                            <label for="buscadorPaisTramitador" class="mb-1 block text-sm font-medium text-slate-700">País</label>
                            <select id="paisTramitador" class="hidden" aria-hidden="true" tabindex="-1">
                                <option value="">Seleccione país</option>
                                @foreach ($paises as $pais)
                                    <option value="{{ $pais->id }}" data-codigo="{{ $pais->codigo }}" data-nombre="{{ $pais->nombre }}">
                                        {{ $pais->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="ocupacion-persona-control">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search" id="buscadorPaisTramitador" placeholder="Seleccione un país" autocomplete="off">
                                <button type="button" id="limpiarPaisTramitador" class="ocupacion-persona-limpiar" aria-label="Quitar país">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div id="resultadosPaisTramitador" class="ocupacion-persona-resultados"></div>
                        </div>

                        <div id="nivelesTerritorioTramitador" class="contents"></div>
                    </div>
                </section>

                <section class="relative z-20 overflow-visible rounded-xl border border-violet-200">
                    <div class="border-b border-violet-100 bg-violet-50 px-4 py-2">
                        <h2 class="text-sm font-bold text-violet-700">3. Datos personales del tramitador</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                        <x-wire-input errorless label="Nombres" id="form_nombres" name="form_nombres" value="{{ old('form_nombres') }}" />
                        <x-wire-input errorless label="Apellido paterno" id="form_apellido_paterno" name="form_apellido_paterno" value="{{ old('form_apellido_paterno') }}" />
                        <x-wire-input errorless label="Apellido materno" id="form_apellido_materno" name="form_apellido_materno" value="{{ old('form_apellido_materno') }}" />
                        <x-wire-input errorless label="Apellido de casado" id="form_apellido_casado" name="form_apellido_casado" value="{{ old('form_apellido_casado') }}" />
                        <x-wire-native-select errorless label="Expedido" id="form_expedido" name="form_expedido">
                            <option value="">Seleccione expedido</option>
                            @foreach (\App\Models\Natural::EXPEDIDOS as $codigo => $nombre)
                                <option value="{{ $codigo }}" @selected(old('form_expedido') === $codigo)>{{ $codigo }} - {{ $nombre }}</option>
                            @endforeach
                        </x-wire-native-select>
                        <div>
                            <label for="form_fecha_nacimiento" class="mb-1 block text-sm font-medium text-slate-700">
                                Fecha de nacimiento
                            </label>
                            <input type="date" id="form_fecha_nacimiento" name="form_fecha_nacimiento"
                                value="{{ old('form_fecha_nacimiento') }}" max="{{ today()->toDateString() }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <x-wire-native-select errorless label="Género" id="form_genero" name="form_genero">
                            <option value="">Seleccione una opción</option>
                            <option value="1" @selected(old('form_genero') === '1')>Masculino</option>
                            <option value="0" @selected(old('form_genero') === '0')>Femenino</option>
                        </x-wire-native-select>
                        <div id="contenedorOcupacionTramitador" class="ocupacion-persona-autocomplete">
                            <label for="buscadorOcupacionTramitador" class="mb-1 block text-sm font-medium text-slate-700">
                                Ocupación
                            </label>
                            <select id="form_id_ocupacion" name="form_id_ocupacion" class="hidden" aria-hidden="true">
                                <option value="">Seleccione ocupación</option>
                                @foreach ($ocupacionesCob as $ocupacionCob)
                                    <option value="{{ $ocupacionCob->id }}"
                                        data-codigo="{{ $ocupacionCob->codigo_ocupacion }}"
                                        data-descripcion="{{ $ocupacionCob->descripcion_ocupacion }}"
                                        @selected((string) old('form_id_ocupacion') === (string) $ocupacionCob->id)>
                                        {{ $ocupacionCob->codigo_ocupacion }} - {{ $ocupacionCob->descripcion_ocupacion }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="ocupacion-persona-control">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" id="buscadorOcupacionTramitador"
                                    placeholder="Escriba código o descripción" autocomplete="off">
                                <button type="button" id="limpiarOcupacionTramitador"
                                    class="ocupacion-persona-limpiar" aria-label="Quitar ocupación seleccionada">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                                <button type="button" id="alternarOcupacionTramitador"
                                    class="ocupacion-persona-alternar" aria-expanded="false"
                                    aria-controls="resultadosOcupacionTramitador" aria-label="Abrir lista de ocupaciones"
                                    title="Abrir lista de ocupaciones">
                                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="resultadosOcupacionTramitador" class="ocupacion-persona-resultados"></div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-emerald-200">
                    <div class="border-b border-emerald-100 bg-emerald-50 px-4 py-2">
                        <h2 class="text-sm font-bold text-emerald-700">4. Teléfonos del tramitador</h2>
                    </div>
                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                            <div class="md:col-span-10"><x-wire-input errorless label="Teléfono" id="telefonoTramitador" placeholder="Ejemplo: 70123456" /></div>
                            <div class="md:col-span-2 md:flex md:h-full md:items-end">
                                <button type="button" id="btnAgregarTelefono" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">Agregar</button>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <h3 class="text-sm font-semibold text-gray-700">Teléfonos registrados</h3>
                            <div id="listaTelefonos" class="mt-3 flex flex-wrap gap-2"></div>
                        </div>
                        <input type="hidden" id="form_telefonos_json" name="form_telefonos_json" value="{{ old('form_telefonos_json', '[]') }}">
                    </div>
                </section>

                <section class="relative z-30 overflow-visible rounded-xl border border-sky-200">
                    <div class="border-b border-sky-100 bg-sky-50 px-4 py-2">
                        <h2 class="text-sm font-bold text-sky-700">5. Rubros o actividad económica</h2>
                    </div>
                    <div class="space-y-4 p-4">
                        <div>
                            <label for="buscadorRubroTramitador" class="mb-2 block text-sm font-semibold text-slate-700">Buscar y agregar rubro</label>
                            <select id="rubroTramitador" class="hidden">
                                <option value="">Seleccione un rubro</option>
                                @foreach ($rubrosCatalogo as $rubro)
                                    <option value="{{ $rubro->id }}" data-codigo="{{ $rubro->codigo_caeb }}"
                                        data-nombre="{{ $rubro->nombre }}" data-etiqueta="{{ $rubro->codigo_caeb }} - {{ $rubro->nombre }}">
                                        {{ $rubro->codigo_caeb }} - {{ $rubro->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="contenedorRubroTramitador" class="ocupacion-persona-autocomplete">
                                <div class="ocupacion-persona-control">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="search" id="buscadorRubroTramitador" placeholder="Escriba el nombre del rubro" autocomplete="off">
                                    <button type="button" id="limpiarRubroTramitador"
                                        class="ocupacion-persona-limpiar" aria-label="Quitar todos los rubros seleccionados"
                                        title="Quitar todos los rubros seleccionados">
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" id="alternarRubroTramitador"
                                        class="ocupacion-persona-alternar" aria-expanded="false"
                                        aria-controls="resultadosRubroTramitador" aria-label="Abrir lista de rubros"
                                        title="Abrir lista de rubros">
                                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div id="resultadosRubroTramitador" class="ocupacion-persona-resultados"></div>
                            </div>
                        </div>
                        <div class="persona-rubros-selected-card">
                            <p id="resumenRubros" class="persona-rubro-summary">Rubros seleccionados (0)</p>
                            <div id="listaRubros" class="flex flex-wrap gap-2"></div>
                        </div>
                        <input type="hidden" id="form_rubros_json" name="form_rubros_json" value="{{ old('form_rubros_json', '[]') }}">
                    </div>
                </section>
                </fieldset>

                <section class="overflow-visible rounded-xl border border-lime-200">
                    <div class="border-b border-lime-100 bg-lime-50 px-4 py-2">
                        <h2 class="text-sm font-bold text-lime-700">6. Documento de respaldo o autorización</h2>
                    </div>
                    <div class="p-4">
                        <div data-error-wrapper="form_url_respaldo">
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="form_url_respaldo">Documento de respaldo o autorización en PDF (obligatorio)</label>
                            <input type="file" id="form_url_respaldo" name="form_url_respaldo" accept=".pdf,application/pdf" aria-required="true" class="sr-only">
                            <p class="mb-2 text-xs text-gray-500">Adjunte un PDF de hasta 5 MB. Si el formulario tiene errores, deberá volver a seleccionar el archivo.</p>
                            <div id="responsableModalPdfControl" class="responsable-modal-pdf">
                                <div class="responsable-modal-pdf-info">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <div>
                                        <strong id="responsableModalPdfNombre">Sin PDF seleccionado</strong>
                                        <span id="responsableModalPdfEstado">Seleccione un documento PDF.</span>
                                    </div>
                                </div>
                                <div class="responsable-modal-pdf-actions">
                                    <label for="form_url_respaldo" class="responsable-modal-pdf-button is-select">
                                        <i class="fa-solid fa-upload"></i><span>Seleccionar</span>
                                    </label>
                                    <button type="button" id="btnVerRespaldoResponsableModal" class="responsable-modal-pdf-button is-view" disabled>
                                        <i class="fa-solid fa-eye"></i><span>Ver</span>
                                    </button>
                                    <button type="button" id="btnQuitarRespaldoResponsableModal" class="responsable-modal-pdf-button is-remove" disabled>
                                        <i class="fa-solid fa-xmark"></i><span>Quitar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t bg-gray-50 px-4 py-4 sm:flex-row sm:justify-end sm:px-6">
                <x-wire-button href="{{ route('tramitadores_index') }}" class="w-full justify-center sm:w-auto" secondary>Cancelar</x-wire-button>
                <x-wire-button type="submit" class="w-full justify-center sm:w-auto" emerald>Registrar tramitador</x-wire-button>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btnBuscarPersona = document.getElementById('btnBuscarPersona');
                const estadoBusquedaPersona = document.getElementById('estadoBusquedaPersona');
                const camposPersona = [
                    'form_domicilio', 'form_nit', 'form_correo', 'form_nombres', 'form_apellido_paterno',
                    'form_apellido_materno', 'form_apellido_casado', 'form_expedido',
                    'form_fecha_nacimiento', 'form_genero', 'form_id_ocupacion'
                ];
                let telefonos = JSON.parse(document.getElementById('form_telefonos_json').value || '[]');
                let rubros = JSON.parse(document.getElementById('form_rubros_json').value || '[]');
                let urlPdfTemporal = null;
                let documentoConsultado = '';

                const formulario = document.getElementById('formTramitador');
                const controlesVisibles = {
                    form_id_territorio: 'buscadorPaisTramitador',
                    form_id_ocupacion: 'buscadorOcupacionTramitador',
                    form_telefonos_json: 'telefonoTramitador',
                    form_rubros_json: 'buscadorRubroTramitador',
                    form_url_respaldo: 'responsableModalPdfControl',
                };
                const mostrarErrorCampo = (nombre, mensaje) => {
                    const campo = document.getElementById(nombre);
                    const visible = document.getElementById(controlesVisibles[nombre] || nombre) || campo;
                    if (!visible) return null;
                    const idError = `error-tramitador-${nombre}`;
                    let aviso = document.getElementById(idError);
                    if (!aviso) {
                        aviso = document.createElement('p');
                        aviso.id = idError;
                        aviso.setAttribute('role', 'alert');
                        aviso.style.cssText = 'color:#b91c1c;font-size:12px;margin-top:6px;';
                    }
                    // WireUI envuelve el input en un label flexible: el error va fuera de ese label.
                    const cajaInput = visible.closest('label[name="form.wrapper.container"]');
                    if (cajaInput) {
                        cajaInput.insertAdjacentElement('afterend', aviso);
                    } else {
                        const contenedor = visible.closest('[form-wrapper]')
                            || visible.closest('.ocupacion-persona-autocomplete')
                            || visible.parentElement;
                        contenedor.appendChild(aviso);
                    }
                    aviso.textContent = mensaje;
                    visible.setAttribute('aria-invalid', 'true');
                    visible.setAttribute('aria-describedby', idError);
                    visible.style.borderColor = '#dc2626';
                    return visible;
                };
                const erroresServidor = @json($errors->messages());
                let primerError;
                Object.entries(erroresServidor).forEach(([nombre, mensajes]) => {
                    const visible = mostrarErrorCampo(nombre, mensajes.join(' '));
                    primerError ||= visible;
                });
                if (primerError) {
                    primerError.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
                formulario.addEventListener('submit', (evento) => {
                    formulario.querySelectorAll('[id^="error-tramitador-"]').forEach((aviso) => aviso.remove());
                    formulario.querySelectorAll('[aria-invalid="true"]').forEach((control) => {
                        control.removeAttribute('aria-invalid');
                        control.removeAttribute('aria-describedby');
                        control.style.borderColor = '';
                    });
                    const errores = {};
                    if (!document.getElementById('form_ci').value.trim()) {
                        errores.form_ci = 'Ingrese el CI del tramitador.';
                    }
                    if (!document.getElementById('datosPersonaTramitador').disabled) {
                        const obligatorios = {
                            form_correo: 'Ingrese el correo electrónico del tramitador.',
                            form_id_territorio: 'Seleccione el país y la ubicación del tramitador.',
                            form_nombres: 'Ingrese los nombres del tramitador.',
                            form_apellido_paterno: 'Ingrese el apellido paterno del tramitador.',
                            form_genero: 'Seleccione el género del tramitador.',
                            form_fecha_nacimiento: 'Ingrese la fecha de nacimiento del tramitador.',
                        };
                        Object.entries(obligatorios).forEach(([nombre, mensaje]) => {
                            if (!document.getElementById(nombre).value.trim()) errores[nombre] = mensaje;
                        });
                        const correo = document.getElementById('form_correo');
                        if (correo.value.trim() && correo.validity.typeMismatch) {
                            errores.form_correo = 'Ingrese un correo electrónico válido, por ejemplo: nombre@correo.com.';
                        }
                    }
                    const pdf = document.getElementById('form_url_respaldo').files[0];
                    let mensaje = '';
                    if (!pdf) mensaje = 'Debe adjuntar el documento de respaldo o autorización en PDF.';
                    else if (!pdf.name.toLowerCase().endsWith('.pdf')) mensaje = 'El documento de respaldo debe ser un archivo PDF.';
                    else if (pdf.size > 5 * 1024 * 1024) mensaje = 'El PDF de respaldo no debe superar los 5 MB.';
                    if (mensaje) errores.form_url_respaldo = mensaje;
                    if (Object.keys(errores).length) {
                        evento.preventDefault();
                        let primerCampo;
                        Object.entries(errores).forEach(([nombre, texto]) => {
                            const control = mostrarErrorCampo(nombre, texto);
                            primerCampo ||= control;
                        });
                        primerCampo?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        if (primerCampo?.matches('input, select, button')) primerCampo.focus({ preventScroll: true });
                        else document.getElementById('form_url_respaldo').focus({ preventScroll: true });
                    }
                });
                document.getElementById('form_url_respaldo').addEventListener('change', () => {
                    document.getElementById('error-tramitador-form_url_respaldo')?.remove();
                    const control = document.getElementById('responsableModalPdfControl');
                    control.removeAttribute('aria-invalid');
                    control.removeAttribute('aria-describedby');
                    control.style.borderColor = '';
                });

                const asignar = (id, valor) => {
                    const campo = document.getElementById(id);
                    if (campo) campo.value = valor ?? '';
                };

                const bloquearPersona = (bloquear) => {
                    document.getElementById('datosPersonaTramitador').disabled = bloquear;
                };

                const mostrarEstadoBusqueda = (mensaje, tipo = 'neutral') => {
                    const estilos = {
                        neutral: 'bg-slate-50 text-slate-600',
                        cargando: 'bg-blue-50 text-blue-700',
                        encontrado: 'bg-emerald-50 text-emerald-700',
                        nuevo: 'bg-amber-50 text-amber-800',
                        error: 'bg-red-50 text-red-700',
                    };
                    estadoBusquedaPersona.className = `rounded-lg px-3 py-2 text-xs ${estilos[tipo] || estilos.neutral}`;
                    estadoBusquedaPersona.textContent = mensaje;
                };

                const renderTelefonos = () => {
                    const lista = document.getElementById('listaTelefonos');
                    lista.innerHTML = '';
                    if (!telefonos.length) lista.innerHTML = '<span class="text-sm text-gray-500">No tiene teléfonos registrados.</span>';
                    telefonos.forEach((telefono, indice) => {
                        const item = document.createElement('span');
                        item.className = 'inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1 text-sm';
                        const texto = document.createElement('span');
                        texto.textContent = telefono.numero;
                        const quitar = document.createElement('button');
                        quitar.type = 'button';
                        quitar.className = 'font-bold text-red-500';
                        quitar.textContent = '×';
                        quitar.addEventListener('click', () => { telefonos.splice(indice, 1); renderTelefonos(); });
                        item.append(texto, quitar);
                        lista.appendChild(item);
                    });
                    document.getElementById('form_telefonos_json').value = JSON.stringify(telefonos);
                };

                const renderRubros = () => {
                    const lista = document.getElementById('listaRubros');
                    lista.innerHTML = '';
                    document.getElementById('resumenRubros').textContent = `Rubros seleccionados (${rubros.length})`;
                    if (!rubros.length) lista.innerHTML = '<span class="text-sm text-gray-500">No tiene rubros registrados.</span>';
                    rubros.forEach((rubro, indice) => {
                        const item = document.createElement('span');
                        item.className = 'inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white px-3 py-1 text-sm';
                        const texto = document.createElement('span');
                        texto.textContent = rubro.etiqueta || rubro.nombre || `Rubro ${rubro.id}`;
                        const quitar = document.createElement('button');
                        quitar.type = 'button';
                        quitar.className = 'font-bold text-red-500';
                        quitar.textContent = '×';
                        quitar.addEventListener('click', () => { rubros.splice(indice, 1); renderRubros(); });
                        item.append(texto, quitar);
                        lista.appendChild(item);
                    });
                    document.getElementById('form_rubros_json').value = JSON.stringify(rubros);
                    actualizarLimpiarRubro();
                };

                const urlTerritorio = (tipo, id) => {
                    const contenedor = document.getElementById('contenedorPaisTramitador');
                    return contenedor.dataset[tipo === 'ruta' ? 'urlRuta' : 'urlHijos'].replace('__ID__', id);
                };

                const guardarTerritorio = (id) => asignar('form_id_territorio', id);

                const seleccionarOcupacion = (id, descripcion = '') => {
                    const select = document.getElementById('form_id_ocupacion');
                    select.value = String(id || '');
                    const opcion = select.options[select.selectedIndex];
                    const buscador = document.getElementById('buscadorOcupacionTramitador');
                    buscador.value = descripcion ||
                        (opcion?.value ? `${opcion.dataset.codigo} - ${opcion.dataset.descripcion}` : '');
                    document.getElementById('limpiarOcupacionTramitador').classList.toggle(
                        'is-visible',
                        Boolean(id || buscador.value.trim())
                    );
                };

                const agregarNivelTerritorio = async (idPadre, idSeleccionado = '') => {
                    const respuesta = await fetch(urlTerritorio('hijos', idPadre), { headers: { Accept: 'application/json' } });
                    if (!respuesta.ok) return false;
                    const hijos = await respuesta.json();
                    if (!hijos.length) return false;

                    const bloque = document.createElement('div');
                    bloque.innerHTML = `<label class="mb-1 block text-sm font-medium text-slate-700">${hijos[0].nivel || 'Territorio'}</label>`;
                    const select = document.createElement('select');
                    select.className = 'w-full rounded-lg border-gray-300 text-sm';
                    select.innerHTML = '<option value="">Seleccione territorio</option>' + hijos.map((hijo) =>
                        `<option value="${hijo.id}">${hijo.nombre}</option>`).join('');
                    select.value = String(idSeleccionado || '');
                    select.addEventListener('change', async () => {
                        while (bloque.nextElementSibling) bloque.nextElementSibling.remove();
                        guardarTerritorio(select.value || idPadre);
                        if (select.value) await agregarNivelTerritorio(select.value);
                    });
                    bloque.appendChild(select);
                    document.getElementById('nivelesTerritorioTramitador').appendChild(bloque);
                    return true;
                };

                const seleccionarPais = async (id, nombre = '') => {
                    const select = document.getElementById('paisTramitador');
                    select.value = String(id || '');
                    document.getElementById('buscadorPaisTramitador').value = id
                        ? (nombre || select.options[select.selectedIndex]?.textContent.trim() || '')
                        : '';
                    document.getElementById('limpiarPaisTramitador').classList.toggle('is-visible', Boolean(id));
                    document.getElementById('nivelesTerritorioTramitador').innerHTML = '';
                    guardarTerritorio(id || '');
                    if (id) await agregarNivelTerritorio(id);
                };

                const cargarRutaTerritorio = async (idTerritorio) => {
                    if (!idTerritorio) return seleccionarPais('', '');
                    const respuesta = await fetch(urlTerritorio('ruta', idTerritorio), { headers: { Accept: 'application/json' } });
                    if (!respuesta.ok) return;
                    const ruta = await respuesta.json();
                    if (!ruta.length) return;
                    await seleccionarPais(ruta[0].id, ruta[0].nombre);
                    const niveles = document.getElementById('nivelesTerritorioTramitador');
                    niveles.innerHTML = '';
                    let padre = ruta[0].id;
                    for (const nivel of ruta.slice(1)) {
                        await agregarNivelTerritorio(padre, nivel.id);
                        guardarTerritorio(nivel.id);
                        padre = nivel.id;
                    }
                };

                const limpiarDatosPersona = async () => {
                    camposPersona.forEach((id) => asignar(id, ''));
                    telefonos = [];
                    rubros = [];
                    renderTelefonos();
                    renderRubros();
                    seleccionarOcupacion('', '');
                    await seleccionarPais('', '');
                    bloquearPersona(false);
                };

                const cargarPersona = async (persona) => {
                    asignar('form_domicilio', persona.domicilio);
                    asignar('form_nit', persona.nit);
                    asignar('form_correo', persona.correo);
                    asignar('form_nombres', persona.nombres);
                    asignar('form_apellido_paterno', persona.apellido_paterno);
                    asignar('form_apellido_materno', persona.apellido_materno);
                    asignar('form_apellido_casado', persona.apellido_casado);
                    asignar('form_complemento', persona.complemento);
                    asignar('form_expedido', persona.expedido);
                    asignar('form_fecha_nacimiento', String(persona.fecha_nacimiento || '').slice(0, 10));
                    asignar('form_genero', persona.genero);
                    seleccionarOcupacion(persona.id_ocupacion, persona.ocupacion);
                    telefonos = (persona.telefonos || []).map((item) => ({
                        numero: item.numero,
                        estado: item.estado || 'ACTIVO',
                    }));
                    rubros = (persona.rubros || []).map((item) => ({
                        id: item.id,
                        etiqueta: `${item.codigo || ''}${item.codigo ? ' - ' : ''}${item.nombre || ''}`,
                    }));
                    renderTelefonos();
                    renderRubros();
                    await cargarRutaTerritorio(persona.id_territorio);
                    bloquearPersona(true);
                };

                const claveDocumentoActual = () => {
                    const ci = document.getElementById('form_ci').value.trim().toUpperCase().replace(/\s+/g, '');
                    const complemento = document.getElementById('form_complemento').value.trim().toUpperCase().replace(/\s+/g, '');
                    return `${ci}|${complemento}`;
                };

                const buscarPersona = async () => {
                    const ci = document.getElementById('form_ci').value.trim();
                    const complemento = document.getElementById('form_complemento').value.trim();

                    if (!ci) {
                        mostrarEstadoBusqueda('Ingrese el CI antes de realizar la búsqueda.', 'error');
                        document.getElementById('form_ci').focus();
                        return;
                    }

                    btnBuscarPersona.disabled = true;
                    btnBuscarPersona.querySelector('i').className = 'fa-solid fa-spinner fa-spin';
                    mostrarEstadoBusqueda('Buscando el CI ingresado…', 'cargando');

                    try {
                        const url = new URL(btnBuscarPersona.dataset.url, window.location.origin);
                        url.searchParams.set('form_ci', ci);
                        if (complemento) url.searchParams.set('form_complemento', complemento);

                        const respuesta = await fetch(url, {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        const datos = await respuesta.json().catch(() => ({}));

                        if (!respuesta.ok) {
                            const mensaje = Object.values(datos.errors || {}).flat()[0]
                                || datos.message
                                || 'No fue posible consultar el CI.';
                            throw new Error(mensaje);
                        }

                        documentoConsultado = claveDocumentoActual();

                        if (!datos.encontrado) {
                            await limpiarDatosPersona();
                            mostrarEstadoBusqueda(
                                'El CI no está registrado. Complete los datos para registrar una nueva persona.',
                                'nuevo'
                            );
                            document.getElementById('form_nombres').focus();
                            return;
                        }

                        await cargarPersona(datos.persona);
                        documentoConsultado = claveDocumentoActual();
                        mostrarEstadoBusqueda(
                            'Persona encontrada. Los datos se completaron y quedaron protegidos contra modificaciones.',
                            'encontrado'
                        );
                    } catch (error) {
                        documentoConsultado = '';
                        await limpiarDatosPersona();
                        mostrarEstadoBusqueda(error.message, 'error');
                    } finally {
                        btnBuscarPersona.disabled = false;
                        btnBuscarPersona.querySelector('i').className = 'fa-solid fa-magnifying-glass';
                    }
                };

                const mostrarOpciones = (contenedor, opciones) => {
                    contenedor.innerHTML = opciones.length ? '' : '<div class="ocupacion-persona-vacio">No se encontraron resultados.</div>';
                    opciones.forEach((opcion) => contenedor.appendChild(opcion));
                };

                // Mantiene sincronizados el chevrón, la accesibilidad y la lista visible de cada buscador.
                const cambiarEstadoSelectorCatalogo = (contenedorId, botonId, abierto) => {
                    const contenedor = document.getElementById(contenedorId);
                    const boton = document.getElementById(botonId);

                    if (!contenedor || !boton) return;

                    contenedor.classList.toggle('is-open', abierto);
                    boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                    boton.setAttribute('aria-label', abierto ? 'Cerrar lista' : 'Abrir lista');
                    boton.title = abierto ? 'Cerrar lista' : 'Abrir lista';

                    const icono = boton.querySelector('i');
                    if (icono) {
                        icono.className = `fa-solid ${abierto ? 'fa-chevron-up' : 'fa-chevron-down'}`;
                    }
                };

                const cerrarSelectoresCatalogo = (excepto = '') => {
                    if (excepto !== 'ocupacion') {
                        cambiarEstadoSelectorCatalogo(
                            'contenedorOcupacionTramitador',
                            'alternarOcupacionTramitador',
                            false
                        );
                    }

                    if (excepto !== 'rubro') {
                        cambiarEstadoSelectorCatalogo(
                            'contenedorRubroTramitador',
                            'alternarRubroTramitador',
                            false
                        );
                    }
                };

                const actualizarLimpiarRubro = () => {
                    const buscador = document.getElementById('buscadorRubroTramitador');
                    document.getElementById('limpiarRubroTramitador').classList.toggle(
                        'is-visible',
                        Boolean(buscador.value.trim() || rubros.length)
                    );
                };

                const renderPaises = () => {
                    const texto = document.getElementById('buscadorPaisTramitador').value.toLowerCase();
                    const botones = Array.from(document.getElementById('paisTramitador').options)
                        .filter((opcion) => opcion.value && `${opcion.dataset.codigo} ${opcion.dataset.nombre}`.toLowerCase().includes(texto))
                        .slice(0, 50)
                        .map((opcion) => {
                            const boton = document.createElement('button');
                            boton.type = 'button';
                            boton.className = 'ocupacion-persona-opcion';
                            boton.textContent = opcion.dataset.nombre;
                            boton.addEventListener('click', () => {
                                seleccionarPais(opcion.value, opcion.dataset.nombre);
                                document.getElementById('contenedorPaisTramitador').classList.remove('is-open');
                            });
                            return boton;
                        });
                    mostrarOpciones(document.getElementById('resultadosPaisTramitador'), botones);
                };

                const renderOpcionesRubros = () => {
                    const texto = document.getElementById('buscadorRubroTramitador').value.toLowerCase();
                    const botones = Array.from(document.getElementById('rubroTramitador').options)
                        .filter((opcion) => opcion.value && `${opcion.dataset.codigo} ${opcion.dataset.nombre}`.toLowerCase().includes(texto))
                        .filter((opcion) => !rubros.some((rubro) => String(rubro.id) === String(opcion.value)))
                        .slice(0, 50)
                        .map((opcion) => {
                            const boton = document.createElement('button');
                            boton.type = 'button';
                            boton.className = 'ocupacion-persona-opcion rubro-responsable-opcion';
                            boton.textContent = opcion.dataset.etiqueta;
                            boton.addEventListener('click', () => {
                                rubros.push({ id: Number(opcion.value), etiqueta: opcion.dataset.etiqueta });
                                renderRubros();
                                document.getElementById('buscadorRubroTramitador').value = '';
                                actualizarLimpiarRubro();
                                cambiarEstadoSelectorCatalogo(
                                    'contenedorRubroTramitador',
                                    'alternarRubroTramitador',
                                    false
                                );
                            });
                            return boton;
                        });
                    mostrarOpciones(document.getElementById('resultadosRubroTramitador'), botones);
                };

                const renderOpcionesOcupaciones = () => {
                    const texto = document.getElementById('buscadorOcupacionTramitador').value.toLowerCase();
                    const botones = Array.from(document.getElementById('form_id_ocupacion').options)
                        .filter((opcion) => opcion.value &&
                            `${opcion.dataset.codigo} ${opcion.dataset.descripcion}`.toLowerCase().includes(texto))
                        .slice(0, 50)
                        .map((opcion) => {
                            const boton = document.createElement('button');
                            const codigo = document.createElement('span');
                            const descripcion = document.createElement('span');
                            const marca = document.createElement('span');

                            boton.type = 'button';
                            boton.className = 'ocupacion-persona-opcion';
                            codigo.className = 'ocupacion-persona-codigo';
                            codigo.textContent = opcion.dataset.codigo || '';
                            descripcion.className = 'ocupacion-persona-descripcion';
                            descripcion.textContent = opcion.dataset.descripcion || '';
                            marca.className = 'ocupacion-persona-check';

                            if (String(opcion.value) === String(document.getElementById('form_id_ocupacion').value)) {
                                boton.classList.add('is-selected');
                                marca.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
                            }

                            boton.append(codigo, descripcion, marca);
                            boton.addEventListener('click', () => {
                                seleccionarOcupacion(opcion.value);
                                cambiarEstadoSelectorCatalogo(
                                    'contenedorOcupacionTramitador',
                                    'alternarOcupacionTramitador',
                                    false
                                );
                            });
                            return boton;
                        });
                    mostrarOpciones(document.getElementById('resultadosOcupacionTramitador'), botones);
                };

                document.getElementById('btnAgregarTelefono').addEventListener('click', () => {
                    const campo = document.getElementById('telefonoTramitador');
                    const numero = campo.value.replace(/\D+/g, '');
                    if (!numero) return;
                    telefonos.push({ numero, estado: 'ACTIVO' });
                    campo.value = '';
                    renderTelefonos();
                });

                const buscadorPais = document.getElementById('buscadorPaisTramitador');
                buscadorPais.addEventListener('focus', () => { document.getElementById('contenedorPaisTramitador').classList.add('is-open'); renderPaises(); });
                buscadorPais.addEventListener('input', renderPaises);
                document.getElementById('limpiarPaisTramitador').addEventListener('click', () => seleccionarPais('', ''));

                const buscadorOcupacion = document.getElementById('buscadorOcupacionTramitador');
                buscadorOcupacion.addEventListener('focus', () => {
                    cerrarSelectoresCatalogo('ocupacion');
                    cambiarEstadoSelectorCatalogo(
                        'contenedorOcupacionTramitador',
                        'alternarOcupacionTramitador',
                        true
                    );
                    renderOpcionesOcupaciones();
                });
                buscadorOcupacion.addEventListener('input', () => {
                    document.getElementById('limpiarOcupacionTramitador').classList.toggle(
                        'is-visible',
                        Boolean(buscadorOcupacion.value.trim())
                    );
                    renderOpcionesOcupaciones();
                });
                buscadorOcupacion.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        cambiarEstadoSelectorCatalogo(
                            'contenedorOcupacionTramitador',
                            'alternarOcupacionTramitador',
                            false
                        );
                        document.getElementById('alternarOcupacionTramitador').focus();
                    }
                });
                document.getElementById('limpiarOcupacionTramitador').addEventListener('click', (event) => {
                    event.stopPropagation();
                    seleccionarOcupacion('', '');
                    cambiarEstadoSelectorCatalogo(
                        'contenedorOcupacionTramitador',
                        'alternarOcupacionTramitador',
                        true
                    );
                    renderOpcionesOcupaciones();
                    buscadorOcupacion.focus();
                });
                document.getElementById('alternarOcupacionTramitador').addEventListener('click', (event) => {
                    event.stopPropagation();
                    const contenedor = document.getElementById('contenedorOcupacionTramitador');
                    const abrir = !contenedor.classList.contains('is-open');
                    cerrarSelectoresCatalogo(abrir ? 'ocupacion' : '');
                    cambiarEstadoSelectorCatalogo(
                        'contenedorOcupacionTramitador',
                        'alternarOcupacionTramitador',
                        abrir
                    );

                    if (abrir) {
                        renderOpcionesOcupaciones();
                        buscadorOcupacion.focus();
                    }
                });

                const buscadorRubro = document.getElementById('buscadorRubroTramitador');
                buscadorRubro.addEventListener('focus', () => {
                    cerrarSelectoresCatalogo('rubro');
                    cambiarEstadoSelectorCatalogo(
                        'contenedorRubroTramitador',
                        'alternarRubroTramitador',
                        true
                    );
                    renderOpcionesRubros();
                });
                buscadorRubro.addEventListener('input', () => {
                    actualizarLimpiarRubro();
                    renderOpcionesRubros();
                });
                buscadorRubro.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        cambiarEstadoSelectorCatalogo(
                            'contenedorRubroTramitador',
                            'alternarRubroTramitador',
                            false
                        );
                        document.getElementById('alternarRubroTramitador').focus();
                    }
                });
                document.getElementById('limpiarRubroTramitador').addEventListener('click', (event) => {
                    event.stopPropagation();
                    buscadorRubro.value = '';
                    rubros = [];
                    renderRubros();
                    actualizarLimpiarRubro();
                    cambiarEstadoSelectorCatalogo(
                        'contenedorRubroTramitador',
                        'alternarRubroTramitador',
                        true
                    );
                    renderOpcionesRubros();
                    buscadorRubro.focus();
                });
                document.getElementById('alternarRubroTramitador').addEventListener('click', (event) => {
                    event.stopPropagation();
                    const contenedor = document.getElementById('contenedorRubroTramitador');
                    const abrir = !contenedor.classList.contains('is-open');
                    cerrarSelectoresCatalogo(abrir ? 'rubro' : '');
                    cambiarEstadoSelectorCatalogo(
                        'contenedorRubroTramitador',
                        'alternarRubroTramitador',
                        abrir
                    );

                    if (abrir) {
                        renderOpcionesRubros();
                        buscadorRubro.focus();
                    }
                });

                // Cierra los desplegables al hacer clic fuera, como cualquier select nativo.
                document.addEventListener('click', (event) => {
                    const ocupacion = document.getElementById('contenedorOcupacionTramitador');
                    const rubro = document.getElementById('contenedorRubroTramitador');

                    if (!ocupacion.contains(event.target) && !rubro.contains(event.target)) {
                        cerrarSelectoresCatalogo();
                    }
                });

                btnBuscarPersona.addEventListener('click', buscarPersona);

                ['form_ci', 'form_complemento'].forEach((id) => {
                    const campo = document.getElementById(id);
                    campo.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            buscarPersona();
                        }
                    });
                    campo.addEventListener('input', () => {
                        if (!documentoConsultado || claveDocumentoActual() === documentoConsultado) return;

                        documentoConsultado = '';
                        limpiarDatosPersona();
                        mostrarEstadoBusqueda(
                            'El CI cambió. Presione “Buscar por CI” para verificar el nuevo documento.',
                            'neutral'
                        );
                    });
                });

                const actualizarPdf = (nombre = 'Sin PDF seleccionado', estado = 'Seleccione un documento PDF.', url = '') => {
                    document.getElementById('responsableModalPdfNombre').textContent = nombre;
                    document.getElementById('responsableModalPdfEstado').textContent = estado;
                    const ver = document.getElementById('btnVerRespaldoResponsableModal');
                    const quitar = document.getElementById('btnQuitarRespaldoResponsableModal');
                    ver.dataset.pdfUrl = url;
                    ver.disabled = !url;
                    quitar.disabled = !url;
                };

                document.getElementById('form_url_respaldo').addEventListener('change', function () {
                    const archivo = this.files[0];
                    if (!archivo) return actualizarPdf();
                    const esPdf = archivo.type === 'application/pdf' || archivo.name.toLowerCase().endsWith('.pdf');
                    if (!esPdf) {
                        this.value = '';
                        actualizarPdf('Sin PDF seleccionado', 'Solo se permiten archivos PDF.');
                        return;
                    }
                    if (urlPdfTemporal) URL.revokeObjectURL(urlPdfTemporal);
                    urlPdfTemporal = URL.createObjectURL(archivo);
                    actualizarPdf(archivo.name, 'Documento listo para guardar.', urlPdfTemporal);
                });
                document.getElementById('btnVerRespaldoResponsableModal').addEventListener('click', function () {
                    if (this.dataset.pdfUrl) window.open(this.dataset.pdfUrl, '_blank');
                });
                document.getElementById('btnQuitarRespaldoResponsableModal').addEventListener('click', () => {
                    document.getElementById('form_url_respaldo').value = '';
                    if (urlPdfTemporal) URL.revokeObjectURL(urlPdfTemporal);
                    urlPdfTemporal = null;
                    actualizarPdf();
                });

                renderTelefonos();
                renderRubros();
                if (document.getElementById('form_id_ocupacion').value) seleccionarOcupacion(document.getElementById('form_id_ocupacion').value);
                bloquearPersona(false);

                if (document.getElementById('form_id_territorio').value) {
                    cargarRutaTerritorio(document.getElementById('form_id_territorio').value);
                }
            });
        </script>
    @endpush
    @include('personas.mayusculas')
</x-admin-layout>
