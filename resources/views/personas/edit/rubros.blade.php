{{-- RUBROS --}}
<div id="seccion_rubros">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100 px-5 py-3">
            <h2 class="text-base font-bold text-amber-700">
                Rubros o Actividad Económica
            </h2>
        </div>

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                <div class="md:col-span-8">
                    <x-wire-input label="Nombre del rubro" id="nombreRubro"
                        placeholder="Ejemplo: Importación de equipos médicos" />
                </div>

                <div class="md:col-span-2">
                    <x-wire-native-select label="Estado" id="estadoRubro">
                        {{-- Estado textual para mantener consistencia con la base de datos: ACTIVO / INACTIVO. --}}
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                    </x-wire-native-select>
                </div>

                <div class="md:col-span-2 flex items-end">
                    <button type="button" onclick="agregarRubroPersona()"
                        class="w-full px-4 py-2 rounded-lg bg-amber-600 text-white text-sm hover:bg-amber-700">
                        Agregar Rubro
                    </button>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                <h3 class="text-sm font-semibold text-gray-700">
                    Rubros agregados
                </h3>

                <div id="listaRubrosPersona" class="flex flex-wrap gap-2 mt-3">
                    <span id="mensajeSinRubros" class="text-sm text-gray-500">
                        Todavía no se agregaron rubros.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
