{{-- DATOS PERSONA NATURAL --}}
<div id="seccion_natural">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        {{-- HEADER --}}
        <div class="bg-gradient-to-r from-violet-50 to-purple-50 border-b border-violet-100 px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-violet-600 flex items-center justify-center text-white shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0zm-3 4a6 6 0 00-6 6h12a6 6 0 00-6-6z" />
                    </svg>
                </div>

                <div>
                    <h2 class="text-base font-bold text-violet-700">
                        Datos de Persona Natural
                    </h2>

                    <p class="text-xs text-gray-500">
                        Información de identificación personal.
                    </p>
                </div>
            </div>
        </div>

        {{-- BODY --}}
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

            <x-wire-input label="Nombres" name="form_nombres" placeholder="Ingrese los nombres"
                value="{{ old('form_nombres') }}" />

            <x-wire-input label="Apellido Paterno" name="form_apellido_paterno"
                placeholder="Ingrese el apellido paterno" value="{{ old('form_apellido_paterno') }}" />

            <x-wire-input label="Apellido Materno" name="form_apellido_materno"
                placeholder="Ingrese el apellido materno" value="{{ old('form_apellido_materno') }}" />

            <x-wire-input label="Apellido de Casado" name="form_apellido_casado"
                placeholder="Ingrese el apellido de casado o casada" value="{{ old('form_apellido_casado') }}" />

            <x-wire-input label="Carnet de Identidad" name="form_ci" placeholder="Ingrese CI o pasaporte"
                value="{{ old('form_ci') }}" />

            <x-wire-input label="Complemento del Carnet" name="form_complemento" placeholder="Complemento"
                value="{{ old('form_complemento') }}" />

            <x-wire-input label="Expedido" name="form_expedido" placeholder="LP, CB, SC..."
                value="{{ old('form_expedido') }}" />

            <x-wire-datetime-picker label="Fecha de Nacimiento" name="form_fecha_nacimiento" without-time
                :value="old('form_fecha_nacimiento')" />

            <x-wire-native-select label="Género" name="form_genero" id="form_genero">

                <option value="">
                    Seleccione una opción
                </option>

                <option value="1" @selected(old('form_genero') == '1')>
                    Masculino
                </option>

                <option value="0" @selected(old('form_genero') == '0')>
                    Femenino
                </option>

            </x-wire-native-select>

            <x-wire-input label="Ocupación" name="form_ocupacion" placeholder="Ocupación"
                value="{{ old('form_ocupacion') }}" />
        </div>
    </div>
</div>
