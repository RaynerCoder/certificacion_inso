<x-admin-layout title="Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Empresas',
        'href' => route('empresas_index'),
    ],
    [
        'name' => 'Crear',
    ],
]">


    <x-wire-card>
        <form action="{{ route('empresas_store') }}" method="POST" class="space-y-4">
            @csrf

            {{-- TIPO DE EMPRESA --}}
            <x-wire-native-select label="Tipo de empresa" name="form_id_tipo_empresa">
                <option value="">Seleccione el tipo de empresa</option>
                @foreach ($tiposEmpresas as $elemento)
                    <option value="{{ $elemento->id }}" @selected(old('form_id_tipo_empresa') == $elemento->id)>
                        {{ $elemento->descripcion }}
                    </option>
                @endforeach
            </x-wire-native-select>

            {{-- RAZÓN SOCIAL --}}
            <x-wire-input label="Razón social" name="form_razon_social"
                placeholder="Razón social o nombre de la empresa. Ej: ENTEL S.A. | YPFB | Banco Unión S.A. | Empresa XYZ Ltda."
                value="{{ old('form_razon_social') }}" />

            {{-- MATRÍCULA O NIT --}}
            <x-wire-input label="Matrícula o NIT" name="form_matricula"
                placeholder="Número de matrícula o NIT de la empresa. Ej: 1020304050"
                value="{{ old('form_matricula') }}" />

            {{-- LATITUD --}}
            <x-wire-input label="Latitud" id="latitud" name="form_latitud"
                placeholder="Ej: -16.500000" value="{{ old('form_latitud') }}" />

            {{-- LONGITUD --}}
            <x-wire-input label="Longitud" id="longitud" name="form_longitud"
                placeholder="Ej: -68.150000" value="{{ old('form_longitud') }}" />

            {{-- MAPA --}}
            <div class="mt-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Seleccione la ubicación de la empresa en el mapa
                </label>

                <div id="map" style="height: 400px; border-radius: 10px;"></div>
            </div>


            {{-- ESTADO --}}
            <x-wire-input label="Estado" id="estado" name="form_estado" type="text"
                placeholder="Estado de la empresa" value="{{ old('form_estado') }}" />

            {{-- BOTÓN --}}
            <div class="flex justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>
    </x-wire-card>


    <script>
        // Centro inicial (La Paz)
        const map = L.map('map').setView([-16.5000, -68.1500], 13);

        // Capa de mapa
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker = null;

        const latInput = document.getElementById('latitud');
        const lngInput = document.getElementById('longitud');

        // CLICK EN MAPA
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            setMarker(lat, lng);
            setInputs(lat, lng);
        });

        // INPUT MANUAL
        latInput.addEventListener('input', syncFromInputs);
        lngInput.addEventListener('input', syncFromInputs);

        // Crear o mover marcador
        function setMarker(lat, lng) {
            const pos = [lat, lng];

            if (marker) {
                marker.setLatLng(pos);
            } else {
                marker = L.marker(pos, {
                    draggable: true
                }).addTo(map);

                // 🟦 mover marcador
                marker.on('dragend', function(e) {
                    const p = e.target.getLatLng();
                    setInputs(p.lat, p.lng);
                });
            }

            map.setView(pos, 15);
        }

        // actualizar inputs
        function setInputs(lat, lng) {
            latInput.value = lat;
            lngInput.value = lng;
        }

        // sincronizar desde inputs
        function syncFromInputs() {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);

            if (!isNaN(lat) && !isNaN(lng)) {
                setMarker(lat, lng);
            }
        }
    </script>

</x-admin-layout>
