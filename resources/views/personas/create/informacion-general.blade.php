{{-- INFORMACIÓN GENERAL --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-visible">
    {{-- HEADER --}}
    <div class="rounded-t-2xl bg-gradient-to-r from-blue-50 to-sky-50 border-b border-blue-100 px-5 py-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center text-white shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 7h18M5 7v10a2 2 0 002 2h10a2 2 0 002-2V7" />
                </svg>
            </div>

            <div>
                <h2 class="text-base font-bold text-blue-700">
                    Información General
                </h2>

                <p class="text-xs text-gray-500">
                    Datos comunes para persona natural o empresa.
                </p>
            </div>
        </div>
    </div>

    {{-- BODY --}}
    <div class="p-6 space-y-5">
        @php
            $territorioSeleccionadoId = old('form_id_territorio', $persona->id_territorio ?? '');
            $territorioSeleccionado = $territorios->firstWhere('id', (int) $territorioSeleccionadoId);
            $departamentoSeleccionado = $departamentos->firstWhere('id', (int) $territorioSeleccionadoId);
            $paisSeleccionadoId = old(
                'form_id_pais',
                $territorioSeleccionado?->id_ambito == 1
                    ? $territorioSeleccionado?->id
                    : $departamentoSeleccionado?->id_padre_territorio
            );
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <x-wire-input label="Domicilio" name="form_domicilio" placeholder="Dirección o domicilio"
                value="{{ old('form_domicilio', $persona->domicilio ?? '') }}" />

            <x-wire-input label="NIT" name="form_nit" placeholder="Número de NIT" value="{{ old('form_nit', $persona->nit ?? '') }}" />

            <x-wire-input label="Correo Electrónico" name="form_correo" type="email" placeholder="ejemplo@correo.com"
                value="{{ old('form_correo', $persona->correo ?? '') }}" />

            <div class="ocupacion-persona-autocomplete" data-pais-persona
                data-error-wrapper="form_id_pais">
                <label for="buscadorPaisPersona"
                    class="mb-1 block text-sm font-medium text-slate-700">
                    País
                </label>

                <select id="form_id_pais" name="form_id_pais" class="hidden"
                    aria-hidden="true" tabindex="-1">
                    <option value="">Seleccione país</option>

                    @foreach ($paises as $pais)
                        <option value="{{ $pais->id }}"
                            data-codigo="{{ $pais->codigo }}"
                            data-nombre="{{ $pais->nombre }}"
                            @selected((string) $paisSeleccionadoId === (string) $pais->id)>
                            {{ $pais->nombre }}
                        </option>
                    @endforeach
                </select>

                <div class="ocupacion-persona-control">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>

                    <input type="search" id="buscadorPaisPersona"
                        data-pais-persona-buscar
                        placeholder="Escriba código o país" autocomplete="off">

                    <button type="button" class="ocupacion-persona-limpiar"
                        data-pais-persona-limpiar
                        aria-label="Quitar país seleccionado">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="ocupacion-persona-resultados"
                    data-pais-persona-resultados></div>
            </div>

            <div id="contenedor_departamento">
                <div class="ocupacion-persona-autocomplete" data-departamento-persona
                    data-error-wrapper="form_id_territorio">
                    <label for="buscadorDepartamentoPersona"
                        class="mb-1 block text-sm font-medium text-slate-700">
                        Departamento
                    </label>

                    <select id="form_id_territorio" name="form_id_territorio"
                        class="hidden" aria-hidden="true" tabindex="-1">
                        <option value="">Seleccione departamento</option>

                        @foreach ($departamentos as $departamento)
                            <option value="{{ $departamento->id }}"
                                data-id-pais="{{ $departamento->id_padre_territorio }}"
                                data-codigo="{{ $departamento->codigo }}"
                                data-nombre="{{ $departamento->nombre }}"
                                @selected((string) $territorioSeleccionadoId === (string) $departamento->id)>
                                {{ $departamento->nombre }}
                            </option>
                        @endforeach
                    </select>

                    <div class="ocupacion-persona-control">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>

                        <input type="search" id="buscadorDepartamentoPersona"
                            data-departamento-persona-buscar
                            placeholder="Escriba código o departamento" autocomplete="off">

                        <button type="button" class="ocupacion-persona-limpiar"
                            data-departamento-persona-limpiar
                            aria-label="Quitar departamento seleccionado">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="ocupacion-persona-resultados"
                        data-departamento-persona-resultados></div>
                </div>
            </div>

            <x-wire-native-select label="Estado" id="form_estado" name="form_estado">
                <option value="ACTIVO" @selected(old('form_estado', $persona->estado ?? 'ACTIVO') === 'ACTIVO')>Activo</option>
                <option value="INACTIVO" @selected(old('form_estado', $persona->estado ?? '') === 'INACTIVO')>Inactivo</option>
            </x-wire-native-select>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const pais = document.getElementById('form_id_pais');
        const departamento = document.getElementById('form_id_territorio');
        const contenedorDepartamento = document.getElementById('contenedor_departamento');
        const contenedorPais = document.querySelector('[data-pais-persona]');
        const buscadorPais = document.querySelector('[data-pais-persona-buscar]');
        const resultadosPais = document.querySelector('[data-pais-persona-resultados]');
        const botonLimpiarPais = document.querySelector('[data-pais-persona-limpiar]');
        const contenedorSelectorDepartamento = document.querySelector('[data-departamento-persona]');
        const buscadorDepartamento = document.querySelector('[data-departamento-persona-buscar]');
        const resultadosDepartamento = document.querySelector('[data-departamento-persona-resultados]');
        const botonLimpiarDepartamento = document.querySelector('[data-departamento-persona-limpiar]');

        if (!pais || !departamento || !contenedorDepartamento || !contenedorPais
            || !buscadorPais || !resultadosPais || !botonLimpiarPais
            || !contenedorSelectorDepartamento || !buscadorDepartamento
            || !resultadosDepartamento || !botonLimpiarDepartamento) {
            return;
        }

        const opcionesDepartamento = Array.from(departamento.options);

        const actualizarBuscadorPais = () => {
            const opcion = pais.options[pais.selectedIndex];
            const seleccionValida = Boolean(opcion?.value);

            buscadorPais.value = seleccionValida
                ? (opcion.dataset.nombre || opcion.textContent.trim())
                : '';

            botonLimpiarPais.classList.toggle('is-visible', seleccionValida);
        };

        const renderizarPaises = (filtro = '') => {
            const textoFiltro = normalizarBusquedaOcupacionPersonaWizard(filtro);
            const opciones = Array.from(pais.options)
                .filter(opcion => opcion.value)
                .filter(opcion => {
                    const codigo = normalizarBusquedaOcupacionPersonaWizard(opcion.dataset.codigo);
                    const nombre = normalizarBusquedaOcupacionPersonaWizard(opcion.dataset.nombre);

                    return codigo.includes(textoFiltro) || nombre.includes(textoFiltro);
                })
                .slice(0, 50);

            if (opciones.length === 0) {
                resultadosPais.innerHTML =
                    '<div class="ocupacion-persona-vacio">No se encontraron países.</div>';
                return;
            }

            resultadosPais.innerHTML = opciones.map(opcion => `
                <button type="button"
                    class="ocupacion-persona-opcion ${String(opcion.value) === String(pais.value) ? 'is-selected' : ''}"
                    data-pais-persona-id="${escaparHtmlPersonaWizard(opcion.value)}">
                    <span class="ocupacion-persona-codigo">
                        ${escaparHtmlPersonaWizard(opcion.dataset.codigo || '')}
                    </span>
                    <span class="ocupacion-persona-descripcion">
                        ${escaparHtmlPersonaWizard(opcion.dataset.nombre || '')}
                    </span>
                    <span class="ocupacion-persona-check">
                        ${String(opcion.value) === String(pais.value) ? '<i class="fa-solid fa-check"></i>' : ''}
                    </span>
                </button>
            `).join('');
        };

        const actualizarBuscadorDepartamento = () => {
            const opcion = departamento.options[departamento.selectedIndex];
            const seleccionValida = Boolean(opcion?.value);

            buscadorDepartamento.value = seleccionValida
                ? (opcion.dataset.nombre || opcion.textContent.trim())
                : '';

            botonLimpiarDepartamento.classList.toggle('is-visible', seleccionValida);
        };

        const renderizarDepartamentos = (filtro = '') => {
            const textoFiltro = normalizarBusquedaOcupacionPersonaWizard(filtro);
            const opciones = Array.from(departamento.options)
                .filter(opcion => opcion.value && !opcion.hidden)
                .filter(opcion => {
                    const codigo = normalizarBusquedaOcupacionPersonaWizard(opcion.dataset.codigo);
                    const nombre = normalizarBusquedaOcupacionPersonaWizard(
                        opcion.dataset.nombre || opcion.textContent
                    );

                    return codigo.includes(textoFiltro) || nombre.includes(textoFiltro);
                });

            if (opciones.length === 0) {
                resultadosDepartamento.innerHTML =
                    '<div class="ocupacion-persona-vacio">No se encontraron departamentos.</div>';
                return;
            }

            resultadosDepartamento.innerHTML = opciones.map(opcion => `
                <button type="button"
                    class="ocupacion-persona-opcion ${String(opcion.value) === String(departamento.value) ? 'is-selected' : ''}"
                    data-departamento-persona-id="${escaparHtmlPersonaWizard(opcion.value)}">
                    <span class="ocupacion-persona-codigo">
                        ${escaparHtmlPersonaWizard(opcion.dataset.codigo || '')}
                    </span>
                    <span class="ocupacion-persona-descripcion">
                        ${escaparHtmlPersonaWizard(opcion.dataset.nombre || opcion.textContent.trim())}
                    </span>
                    <span class="ocupacion-persona-check">
                        ${String(opcion.value) === String(departamento.value) ? '<i class="fa-solid fa-check"></i>' : ''}
                    </span>
                </button>
            `).join('');
        };

        const filtrarDepartamentos = () => {
            const idPais = pais.value;
            const textoPais = pais.options[pais.selectedIndex]?.textContent.trim() || 'Pais seleccionado';
            let seleccionVisible = false;
            let tieneDepartamentos = false;
            let opcionPaisSinDepartamentos = departamento.querySelector('[data-opcion-pais-sin-departamentos]');

            if (opcionPaisSinDepartamentos) {
                opcionPaisSinDepartamentos.remove();
            }

            if (!idPais) {
                contenedorDepartamento.classList.add('hidden');
                departamento.value = '';
                actualizarBuscadorDepartamento();
                return;
            }

            opcionesDepartamento.forEach((opcion) => {
                if (!opcion.value) {
                    opcion.hidden = false;
                    return;
                }

                const visible = opcion.dataset.idPais === idPais;
                opcion.hidden = !visible;
                tieneDepartamentos = tieneDepartamentos || visible;

                if (visible && opcion.selected) {
                    seleccionVisible = true;
                }
            });

            contenedorDepartamento.classList.toggle('hidden', !tieneDepartamentos);

            if (tieneDepartamentos) {
                opcionPaisSinDepartamentos = new Option('No especificar departamento', idPais, !seleccionVisible, !seleccionVisible);
                opcionPaisSinDepartamentos.dataset.opcionPaisSinDepartamentos = '1';
                departamento.add(opcionPaisSinDepartamentos, departamento.options[1] ?? null);
            }

            if (!tieneDepartamentos) {
                opcionPaisSinDepartamentos = new Option(textoPais, idPais, true, true);
                opcionPaisSinDepartamentos.dataset.opcionPaisSinDepartamentos = '1';
                departamento.add(opcionPaisSinDepartamentos);
                departamento.value = idPais;
                actualizarBuscadorDepartamento();
                return;
            }

            if (!seleccionVisible) {
                departamento.value = idPais;
            }

            actualizarBuscadorDepartamento();
        };

        const seleccionarPais = (idPais) => {
            pais.value = idPais || '';
            pais.dispatchEvent(new Event('change', { bubbles: true }));
            contenedorPais.classList.remove('is-open');
        };

        const seleccionarDepartamento = (idDepartamento) => {
            departamento.value = idDepartamento || '';
            departamento.dispatchEvent(new Event('change', { bubbles: true }));
            contenedorSelectorDepartamento.classList.remove('is-open');
        };

        pais.addEventListener('change', () => {
            filtrarDepartamentos();
            actualizarBuscadorPais();
        });

        buscadorPais.addEventListener('focus', () => {
            renderizarPaises(pais.value ? '' : buscadorPais.value);
            contenedorPais.classList.add('is-open');
        });

        buscadorPais.addEventListener('input', () => {
            const filtro = buscadorPais.value;

            pais.value = '';
            pais.dispatchEvent(new Event('change', { bubbles: true }));
            buscadorPais.value = filtro;
            botonLimpiarPais.classList.remove('is-visible');
            renderizarPaises(filtro);
            contenedorPais.classList.add('is-open');
        });

        resultadosPais.addEventListener('click', event => {
            const opcion = event.target.closest('[data-pais-persona-id]');

            if (!opcion) {
                return;
            }

            seleccionarPais(opcion.dataset.paisPersonaId);
        });

        botonLimpiarPais.addEventListener('click', () => {
            seleccionarPais('');
            buscadorPais.focus();
        });

        departamento.addEventListener('change', actualizarBuscadorDepartamento);

        buscadorDepartamento.addEventListener('focus', () => {
            renderizarDepartamentos(departamento.value ? '' : buscadorDepartamento.value);
            contenedorSelectorDepartamento.classList.add('is-open');
        });

        buscadorDepartamento.addEventListener('input', () => {
            const filtro = buscadorDepartamento.value;

            departamento.value = '';
            departamento.dispatchEvent(new Event('change', { bubbles: true }));
            buscadorDepartamento.value = filtro;
            botonLimpiarDepartamento.classList.remove('is-visible');
            renderizarDepartamentos(filtro);
            contenedorSelectorDepartamento.classList.add('is-open');
        });

        resultadosDepartamento.addEventListener('click', event => {
            const opcion = event.target.closest('[data-departamento-persona-id]');

            if (!opcion) {
                return;
            }

            seleccionarDepartamento(opcion.dataset.departamentoPersonaId);
        });

        botonLimpiarDepartamento.addEventListener('click', () => {
            const opcionPais = departamento.querySelector('[data-opcion-pais-sin-departamentos]');

            seleccionarDepartamento(opcionPais?.value || '');
            buscadorDepartamento.focus();
        });

        document.addEventListener('click', event => {
            if (!event.target.closest('[data-pais-persona]')) {
                contenedorPais.classList.remove('is-open');
            }

            if (!event.target.closest('[data-departamento-persona]')) {
                contenedorSelectorDepartamento.classList.remove('is-open');
            }
        });

        departamento.form?.addEventListener('submit', filtrarDepartamentos);
        actualizarBuscadorPais();
        filtrarDepartamentos();
    });
</script>
