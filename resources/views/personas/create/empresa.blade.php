<div id="seccion_empresa" class="hidden">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-green-100 bg-gradient-to-r from-green-50 to-emerald-50 px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-600 text-white shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4" />
                    </svg>
                </div>

                <div>
                    <h2 class="text-base font-bold text-green-700">
                        Datos de Empresa
                    </h2>

                    <p class="text-xs text-gray-500">
                        Información general, contacto y ubicación de la empresa.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
            <x-wire-native-select label="Tipo de empresa" id="form_id_tipo_empresa" name="form_id_tipo_empresa">
                <option value="">Seleccione el tipo de empresa</option>
                @foreach ($tiposEmpresas as $elemento)
                    <option value="{{ $elemento->id }}" @selected(old('form_id_tipo_empresa', $persona->empresa->id_tipo_empresa ?? '') == $elemento->id)>
                        {{ $elemento->descripcion }}
                    </option>
                @endforeach
            </x-wire-native-select>

            <x-wire-input label="Razón social" id="form_razon_social" name="form_razon_social"
                placeholder="Razón social de la empresa"
                value="{{ old('form_razon_social', $persona->empresa->razon_social ?? '') }}" />

            <x-wire-input label="Matrícula" id="form_matricula" name="form_matricula"
                placeholder="Número de matrícula de la empresa"
                value="{{ old('form_matricula', $persona->empresa->matricula ?? '') }}" />

            <x-wire-input label="Latitud" id="form_latitud" name="form_latitud" placeholder="Ej: -16.500000"
                value="{{ old('form_latitud', $persona->empresa->latitud ?? '') }}" />

            <x-wire-input label="Longitud" id="form_longitud" name="form_longitud" placeholder="Ej: -68.150000"
                value="{{ old('form_longitud', $persona->empresa->longitud ?? '') }}" />

            <x-wire-input label="Estado" id="form_estado_empresa" name="form_estado_empresa"
                placeholder="Estado de la empresa"
                value="{{ old('form_estado_empresa', $persona->empresa->estado ?? 'ACTIVO') }}" />

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-gray-700">
                    Ubicación de la empresa
                </label>

                <div id="map" class="w-full rounded-xl border border-gray-200" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>
