{{-- INFORMACIÓN GENERAL --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    {{-- HEADER --}}
    <div class="bg-gradient-to-r from-blue-50 to-sky-50 border-b border-blue-100 px-5 py-3">
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

            <x-wire-native-select label="Pais" id="form_id_pais" name="form_id_pais">

                <option value="">
                    Seleccione pais
                </option>

                @foreach ($paises as $pais)
                    <option value="{{ $pais->id }}" @selected((string) $paisSeleccionadoId === (string) $pais->id)>
                        {{ $pais->nombre }}
                    </option>
                @endforeach
            </x-wire-native-select>

            <div id="contenedor_departamento">
            <x-wire-native-select label="Departamento" id="form_id_territorio" name="form_id_territorio">

                <option value="">
                    Seleccione departamento
                </option>

                @foreach ($departamentos as $departamento)
                    <option value="{{ $departamento->id }}" data-id-pais="{{ $departamento->id_padre_territorio }}"
                        @selected((string) $territorioSeleccionadoId === (string) $departamento->id)>
                        {{ $departamento->nombre }}
                    </option>
                @endforeach
            </x-wire-native-select>
            </div>

            <x-wire-native-select label="Estado" id="form_estado" name="form_estado">
                <option value="ACTIVO" @selected(old('form_estado', 'ACTIVO') === 'ACTIVO')>Activo</option>
                <option value="INACTIVO" @selected(old('form_estado') === 'INACTIVO')>Inactivo</option>
            </x-wire-native-select>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const pais = document.getElementById('form_id_pais');
        const departamento = document.getElementById('form_id_territorio');
        const contenedorDepartamento = document.getElementById('contenedor_departamento');

        if (!pais || !departamento || !contenedorDepartamento) {
            return;
        }

        const opcionesDepartamento = Array.from(departamento.options);

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
                return;
            }

            if (!seleccionVisible) {
                departamento.value = idPais;
            }
        };

        pais.addEventListener('change', filtrarDepartamentos);
        departamento.form?.addEventListener('submit', filtrarDepartamentos);
        filtrarDepartamentos();
    });
</script>
