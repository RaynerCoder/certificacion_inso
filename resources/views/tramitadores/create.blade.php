<x-admin-layout title="Registrar tramitador | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Tramitadores', 'href' => route('tramitadores_index')],
    ['name' => 'Registrar tramitador', 'href' => '#'],
]">
    {{-- Reutiliza exactamente los estilos del modal de responsables. --}}
    @include('personas.create.estilos')

    {{-- Mismo TomSelect y mismos estilos usados por el selector del modal de responsables. --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <style>
        .ts-wrapper.single .ts-control {
            min-height: 42px !important;
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
            border-radius: 0.5rem !important;
            border: 1px solid #d1d5db !important;
            background: #fff !important;
            padding: 0 0.75rem !important;
            box-shadow: none !important;
        }

        .ts-wrapper.single .ts-control input {
            margin: 0 !important;
            padding: 0 !important;
            height: auto !important;
            line-height: 1.25rem !important;
            font-size: 0.875rem !important;
            color: #374151 !important;
        }

        .ts-wrapper.single .ts-control .item {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.25rem !important;
            font-size: 0.875rem !important;
            color: #374151 !important;
            display: flex !important;
            align-items: center !important;
        }

        .ts-wrapper.focus .ts-control {
            border-color: #9ca3af !important;
            box-shadow: 0 0 0 3px rgba(156, 163, 175, 0.15) !important;
        }

        .ts-dropdown {
            border-radius: 0.65rem !important;
            border: 1px solid #e5e7eb !important;
            overflow: hidden !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08) !important;
        }

        .ts-dropdown .option {
            padding: 0.7rem 0.85rem !important;
            font-size: 0.875rem !important;
        }

        .ts-dropdown .active {
            background: #f3f4f6 !important;
            color: #111827 !important;
        }

        #formTramitador .grid > *,
        #formTramitador .ts-wrapper {
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

        @media (max-width: 640px) {
            .ts-wrapper.single .ts-control {
                height: auto !important;
                min-height: 42px !important;
            }

            .ts-wrapper.single .ts-control .item {
                white-space: normal !important;
            }

            .ts-dropdown {
                max-width: calc(100vw - 2rem) !important;
            }
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

        <form id="formTramitador" action="{{ route('tramitadores_store') }}" method="POST"
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
                <section class="relative z-50 overflow-visible rounded-xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-100 px-4 py-2">
                        <h2 class="text-sm font-bold text-slate-700">1. Persona registrada</h2>
                    </div>
                    <div class="p-4">
                        <label for="form_id_persona" class="mb-1 block text-sm font-medium text-gray-700">
                            Seleccionar persona existente
                        </label>
                        <select id="form_id_persona" name="form_id_persona" class="w-full">
                            <option value="">Nueva persona / No registrada</option>
                            @foreach ($personas as $persona)
                                @php($natural = $persona->natural)
                                <option value="{{ $persona->id }}" @selected((string) old('form_id_persona') === (string) $persona->id)
                                    data-domicilio="{{ $persona->domicilio }}"
                                    data-nit="{{ $persona->nit }}"
                                    data-correo="{{ $persona->correo }}"
                                    data-territorio="{{ $persona->id_territorio }}"
                                    data-nombres="{{ $natural?->nombres }}"
                                    data-paterno="{{ $natural?->apellido_paterno }}"
                                    data-materno="{{ $natural?->apellido_materno }}"
                                    data-casado="{{ $natural?->apellido_casado }}"
                                    data-ci="{{ $natural?->ci }}"
                                    data-complemento="{{ $natural?->complemento }}"
                                    data-expedido="{{ $natural?->expedido }}"
                                    data-fecha="{{ $natural?->fecha_nacimiento }}"
                                    data-genero="{{ $natural?->genero }}"
                                    data-id-ocupacion="{{ $natural?->id_ocupacion }}"
                                    data-ocupacion="{{ $natural?->ocupacionCob?->descripcion_ocupacion }}"
                                    data-telefonos='@json($persona->telefonos)'
                                    data-rubros='@json($persona->rubros)'>
                                    {{ $natural?->ci ?: 'Sin CI' }} -
                                    {{ trim(($natural?->nombres ?? '') . ' ' . ($natural?->apellido_paterno ?? '') . ' ' . ($natural?->apellido_materno ?? '')) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500">Busque y seleccione una persona registrada para autocompletar los datos.</p>
                    </div>
                </section>

                <fieldset id="datosPersonaTramitador"
                    class="m-0 min-w-0 w-full space-y-4 border-0 p-0 sm:space-y-6">
                <section class="relative z-30 overflow-visible rounded-xl border border-blue-200">
                    <div class="border-b border-blue-100 bg-blue-50 px-4 py-2">
                        <h2 class="text-sm font-bold text-blue-700">2. Información general del tramitador</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                        <x-wire-input label="Domicilio" id="form_domicilio" name="form_domicilio"
                            value="{{ old('form_domicilio') }}" placeholder="Dirección o domicilio" />
                        <x-wire-input label="NIT" id="form_nit" name="form_nit"
                            value="{{ old('form_nit') }}" placeholder="NIT si corresponde" />
                        <x-wire-input label="Correo electrónico" id="form_correo" name="form_correo" type="email"
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
                                <input type="search" id="buscadorPaisTramitador" placeholder="Escriba código o país" autocomplete="off">
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
                        <x-wire-input label="Nombres" id="form_nombres" name="form_nombres" value="{{ old('form_nombres') }}" />
                        <x-wire-input label="Apellido paterno" id="form_apellido_paterno" name="form_apellido_paterno" value="{{ old('form_apellido_paterno') }}" />
                        <x-wire-input label="Apellido materno" id="form_apellido_materno" name="form_apellido_materno" value="{{ old('form_apellido_materno') }}" />
                        <x-wire-input label="Apellido de casado" id="form_apellido_casado" name="form_apellido_casado" value="{{ old('form_apellido_casado') }}" />
                        <x-wire-input label="CI" id="form_ci" name="form_ci" value="{{ old('form_ci') }}" />
                        <x-wire-input label="Complemento" id="form_complemento" name="form_complemento" value="{{ old('form_complemento') }}" />
                        <x-wire-native-select label="Expedido" id="form_expedido" name="form_expedido">
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
                                value="{{ old('form_fecha_nacimiento') }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('form_fecha_nacimiento')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-wire-native-select label="Género" id="form_genero" name="form_genero">
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
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
                            <div class="md:col-span-6"><x-wire-input label="Teléfono" id="telefonoTramitador" placeholder="Ejemplo: 70123456" /></div>
                            <div class="md:col-span-4">
                                <x-wire-native-select label="Estado" id="estadoTelefonoTramitador">
                                    <option value="ACTIVO">Activo</option>
                                    <option value="INACTIVO">Inactivo</option>
                                </x-wire-native-select>
                            </div>
                            <div class="md:col-span-2">
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
                            <label class="mb-1 block text-sm font-medium text-gray-700" for="form_url_respaldo">Documento de respaldo o autorización en PDF</label>
                            <input type="file" id="form_url_respaldo" name="form_url_respaldo" accept=".pdf,application/pdf" class="sr-only">
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
                const personaSelect = document.getElementById('form_id_persona');
                const camposPersona = [
                    'form_domicilio', 'form_nit', 'form_correo', 'form_nombres', 'form_apellido_paterno',
                    'form_apellido_materno', 'form_apellido_casado', 'form_ci', 'form_complemento',
                    'form_expedido', 'form_fecha_nacimiento', 'form_genero', 'form_id_ocupacion'
                ];
                let telefonos = JSON.parse(document.getElementById('form_telefonos_json').value || '[]');
                let rubros = JSON.parse(document.getElementById('form_rubros_json').value || '[]');
                let urlPdfTemporal = null;

                const asignar = (id, valor) => {
                    const campo = document.getElementById(id);
                    if (campo) campo.value = valor ?? '';
                };

                const bloquearPersona = (bloquear) => {
                    document.getElementById('datosPersonaTramitador').disabled = bloquear;
                };

                const renderTelefonos = () => {
                    const lista = document.getElementById('listaTelefonos');
                    lista.innerHTML = '';
                    if (!telefonos.length) lista.innerHTML = '<span class="text-sm text-gray-500">No tiene teléfonos registrados.</span>';
                    telefonos.forEach((telefono, indice) => {
                        const item = document.createElement('span');
                        item.className = 'inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1 text-sm';
                        const texto = document.createElement('span');
                        texto.textContent = `${telefono.numero} - ${telefono.estado || 'ACTIVO'}`;
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
                    document.getElementById('buscadorOcupacionTramitador').value = descripcion ||
                        (opcion?.value ? `${opcion.dataset.codigo} - ${opcion.dataset.descripcion}` : '');
                    document.getElementById('limpiarOcupacionTramitador').classList.toggle('is-visible', Boolean(id));
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
                    document.getElementById('buscadorPaisTramitador').value = nombre || select.options[select.selectedIndex]?.textContent.trim() || '';
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

                const cargarPersona = async () => {
                    const opcion = personaSelect.options[personaSelect.selectedIndex];
                    if (!opcion?.value) {
                        camposPersona.forEach((id) => asignar(id, ''));
                        telefonos = [];
                        rubros = [];
                        renderTelefonos();
                        renderRubros();
                        seleccionarOcupacion('', '');
                        await seleccionarPais('', '');
                        bloquearPersona(false);
                        return;
                    }

                    bloquearPersona(true);
                    asignar('form_domicilio', opcion.dataset.domicilio);
                    asignar('form_nit', opcion.dataset.nit);
                    asignar('form_correo', opcion.dataset.correo);
                    asignar('form_nombres', opcion.dataset.nombres);
                    asignar('form_apellido_paterno', opcion.dataset.paterno);
                    asignar('form_apellido_materno', opcion.dataset.materno);
                    asignar('form_apellido_casado', opcion.dataset.casado);
                    asignar('form_ci', opcion.dataset.ci);
                    asignar('form_complemento', opcion.dataset.complemento);
                    asignar('form_expedido', opcion.dataset.expedido);
                    asignar('form_fecha_nacimiento', String(opcion.dataset.fecha || '').slice(0, 10));
                    asignar('form_genero', opcion.dataset.genero);
                    seleccionarOcupacion(opcion.dataset.idOcupacion, opcion.dataset.ocupacion);
                    telefonos = JSON.parse(opcion.dataset.telefonos || '[]').map((item) => ({ numero: item.numero, estado: item.estado || 'ACTIVO' }));
                    rubros = JSON.parse(opcion.dataset.rubros || '[]').map((item) => ({
                        id: item.id,
                        etiqueta: `${item.codigo_caeb || ''}${item.codigo_caeb ? ' - ' : ''}${item.nombre || ''}`,
                    }));
                    renderTelefonos();
                    renderRubros();
                    await cargarRutaTerritorio(opcion.dataset.territorio);
                };

                const mostrarOpciones = (contenedor, opciones) => {
                    contenedor.innerHTML = opciones.length ? '' : '<div class="ocupacion-persona-vacio">No se encontraron resultados.</div>';
                    opciones.forEach((opcion) => contenedor.appendChild(opcion));
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
                                document.getElementById('contenedorRubroTramitador').classList.remove('is-open');
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
                                document.getElementById('contenedorOcupacionTramitador').classList.remove('is-open');
                            });
                            return boton;
                        });
                    mostrarOpciones(document.getElementById('resultadosOcupacionTramitador'), botones);
                };

                document.getElementById('btnAgregarTelefono').addEventListener('click', () => {
                    const campo = document.getElementById('telefonoTramitador');
                    const numero = campo.value.replace(/\D+/g, '');
                    if (!numero) return;
                    telefonos.push({ numero, estado: document.getElementById('estadoTelefonoTramitador').value });
                    campo.value = '';
                    renderTelefonos();
                });

                const buscadorPais = document.getElementById('buscadorPaisTramitador');
                buscadorPais.addEventListener('focus', () => { document.getElementById('contenedorPaisTramitador').classList.add('is-open'); renderPaises(); });
                buscadorPais.addEventListener('input', renderPaises);
                document.getElementById('limpiarPaisTramitador').addEventListener('click', () => seleccionarPais('', ''));

                const buscadorOcupacion = document.getElementById('buscadorOcupacionTramitador');
                buscadorOcupacion.addEventListener('focus', () => {
                    document.getElementById('contenedorOcupacionTramitador').classList.add('is-open');
                    renderOpcionesOcupaciones();
                });
                buscadorOcupacion.addEventListener('input', renderOpcionesOcupaciones);
                document.getElementById('limpiarOcupacionTramitador').addEventListener('click', () => seleccionarOcupacion('', ''));

                const buscadorRubro = document.getElementById('buscadorRubroTramitador');
                buscadorRubro.addEventListener('focus', () => { document.getElementById('contenedorRubroTramitador').classList.add('is-open'); renderOpcionesRubros(); });
                buscadorRubro.addEventListener('input', renderOpcionesRubros);

                if (typeof TomSelect !== 'undefined' && !personaSelect.tomselect) {
                    new TomSelect(personaSelect, {
                        create: false,
                        placeholder: 'Buscar por CI o nombre completo',
                        allowEmptyOption: true,
                        maxOptions: 500,
                        onChange: cargarPersona,
                    });
                } else {
                    personaSelect.addEventListener('change', cargarPersona);
                }

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
                if (personaSelect.value) {
                    cargarPersona();
                } else {
                    // Una persona nueva debe poder completar toda su ficha.
                    bloquearPersona(false);

                    if (document.getElementById('form_id_territorio').value) {
                        cargarRutaTerritorio(document.getElementById('form_id_territorio').value);
                    }
                }
            });
        </script>
    @endpush
</x-admin-layout>
