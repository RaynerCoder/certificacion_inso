<div id="seccion_natural">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-violet-100 bg-gradient-to-r from-violet-50 to-purple-50 px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-600 text-white shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
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

        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
            <x-wire-input label="Nombres" name="form_nombres" placeholder="Ingrese los nombres"
                value="{{ old('form_nombres', $persona->natural->nombres ?? '') }}" />

            <x-wire-input label="Apellido Paterno" name="form_apellido_paterno"
                placeholder="Ingrese el apellido paterno"
                value="{{ old('form_apellido_paterno', $persona->natural->apellido_paterno ?? '') }}" />

            <x-wire-input label="Apellido Materno" name="form_apellido_materno"
                placeholder="Ingrese el apellido materno"
                value="{{ old('form_apellido_materno', $persona->natural->apellido_materno ?? '') }}" />

            <x-wire-input label="Apellido de Casado" name="form_apellido_casado"
                placeholder="Ingrese el apellido de casado o casada"
                value="{{ old('form_apellido_casado', $persona->natural->apellido_casado ?? '') }}" />

            <x-wire-input label="Carnet de Identidad" name="form_ci" placeholder="Ingrese CI o pasaporte"
                value="{{ old('form_ci', $persona->natural->ci ?? '') }}" />

            <x-wire-input label="Complemento del Carnet" name="form_complemento" placeholder="Complemento"
                value="{{ old('form_complemento', $persona->natural->complemento ?? '') }}" />

            <x-wire-native-select label="Expedido" name="form_expedido" id="form_expedido">
                <option value="">Seleccione expedido</option>
                @foreach (($expedidosNatural ?? \App\Models\Natural::EXPEDIDOS) as $codigoExpedido => $nombreExpedido)
                    <option value="{{ $codigoExpedido }}" @selected(old('form_expedido', $persona->natural->expedido ?? '') === $codigoExpedido)>
                        {{ $codigoExpedido }} - {{ $nombreExpedido }}
                    </option>
                @endforeach
            </x-wire-native-select>

            <x-wire-datetime-picker label="Fecha de Nacimiento" name="form_fecha_nacimiento" without-time
                :value="old('form_fecha_nacimiento', $persona->natural->fecha_nacimiento ?? null)" />

            <x-wire-native-select label="Género" name="form_genero" id="form_genero">
                <option value="">Seleccione una opción</option>
                <option value="1" @selected(old('form_genero', $persona->natural->genero ?? '') == '1')>Masculino</option>
                <option value="0" @selected(old('form_genero', $persona->natural->genero ?? '') == '0')>Femenino</option>
            </x-wire-native-select>

            <x-wire-native-select label="Ocupación" name="form_id_ocupacion" id="form_id_ocupacion">
                <option value="">Seleccione ocupación</option>
                @foreach (($ocupacionesCob ?? collect()) as $ocupacionCob)
                    <option value="{{ $ocupacionCob->id }}" @selected((string) old('form_id_ocupacion', $persona->natural->id_ocupacion ?? '') === (string) $ocupacionCob->id)>
                        {{ $ocupacionCob->codigo_ocupacion }} - {{ $ocupacionCob->descripcion_ocupacion }}
                    </option>
                @endforeach
            </x-wire-native-select>
        </div>
    </div>
</div>
