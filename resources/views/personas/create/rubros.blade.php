<div id="seccion_rubros">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-amber-100 bg-gradient-to-r from-amber-50 to-orange-50 px-5 py-3">
            <h2 class="text-base font-bold text-amber-700">
                Rubros o actividad económica
            </h2>
        </div>

        <div class="p-6">
            <label for="rubrosPersona" class="mb-2 block text-sm font-semibold text-slate-700">
                Seleccione uno o varios rubros
            </label>

            <select id="rubrosPersona" name="rubros[]" multiple
                class="min-h-[132px] w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100">
                @foreach (($rubrosCatalogo ?? collect()) as $rubro)
                    <option value="{{ $rubro->id }}" @selected(collect(old('rubros', $rubrosRegistrados ?? []))->map(fn ($id) => (string) $id)->contains((string) $rubro->id))>
                        {{ $rubro->nombre }}
                    </option>
                @endforeach
            </select>

            <p class="mt-2 text-xs text-slate-500">
                Mantenga presionada la tecla Ctrl para seleccionar más de un rubro.
            </p>
        </div>
    </div>
</div>
