        {{-- TELÉFONOS --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            {{-- HEADER --}}
            <div class="bg-gradient-to-r from-emerald-50 to-green-50 border-b border-emerald-100 px-5 py-3">

                <div class="flex items-center gap-3">

                    <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C10.716 21 3 13.284 3 4V3z" />

                        </svg>

                    </div>

                    <div>

                        <h2 class="text-base font-bold text-emerald-700">
                            Teléfonos
                        </h2>

                        <p class="text-xs text-gray-500">
                            Registro de números telefónicos y contactos de referencia.
                        </p>

                    </div>

                </div>

            </div>

            {{-- BODY --}}
            <div class="p-6 space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-5">

                    <div class="md:col-span-6">
                        <x-wire-input label="Teléfono" id="numeroTelefono" placeholder="Ejemplo: 70123456" />
                    </div>

                    <div class="md:col-span-4">
                        <x-wire-native-select label="Estado" id="tipoTelefono">

                            <option value="CELULAR">
                                Celular
                            </option>

                            <option value="FIJO">
                                Fijo
                            </option>

                            <option value="REFERENCIA">
                                Referencia
                            </option>

                        </x-wire-native-select>
                    </div>

                    <div class="md:col-span-2 flex items-end">

                        <button type="button" onclick="agregarTelefonoPersona()"
                            class="w-full px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">

                            Agregar Teléfono

                        </button>

                    </div>

                </div>

                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">

                    <h3 class="text-sm font-semibold text-gray-700">
                        Teléfonos agregados
                    </h3>

                    <div id="listaTelefonosPersona" class="flex flex-wrap gap-2 mt-3">

                        <span id="mensajeSinTelefonos" class="text-sm text-gray-500">

                            Todavía no se agregaron teléfonos.

                        </span>
                    </div>

                </div>

            </div>

        </div>
