@php
    $rubrosSeleccionados = collect(old('rubros', $rubrosRegistrados ?? []))
        ->map(fn ($id) => (string) $id)
        ->filter()
        ->values();
@endphp

<div id="seccion_rubros">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-amber-100 bg-gradient-to-r from-amber-50 to-orange-50 px-5 py-3">
            <h2 class="text-base font-bold text-amber-700">
                Rubros o actividad económica
            </h2>
        </div>

        <div class="space-y-4 p-6">
            <div>
                <label for="rubroPersonaSelector" class="mb-2 block text-sm font-semibold text-slate-700">
                    Buscar y agregar rubro
                </label>

                <select id="rubroPersonaSelector" class="hidden">
                    <option value="">Seleccione un rubro</option>
                    @foreach (($rubrosCatalogo ?? collect()) as $rubro)
                        <option value="{{ $rubro->id }}" data-nombre="{{ $rubro->nombre }}">
                            {{ $rubro->nombre }}
                        </option>
                    @endforeach
                </select>

                <div class="persona-rubro-select" data-rubro-combobox>
                    <button type="button" class="persona-rubro-trigger" data-rubro-trigger
                        aria-expanded="false" aria-controls="rubroPersonaOpciones">
                        <span data-rubro-label>Seleccione un rubro</span>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>

                    <div class="persona-rubro-options" id="rubroPersonaOpciones">
                        <div class="persona-rubro-search-wrap">
                            <input type="search" class="persona-rubro-search" data-rubro-search
                                placeholder="Buscar rubro">
                        </div>

                        <div data-rubro-options-list></div>
                    </div>
                </div>
            </div>

            {{-- Este select queda oculto porque mantiene el envío real como rubros[]. --}}
            <select id="rubrosPersona" name="rubros[]" multiple class="hidden">
                @foreach (($rubrosCatalogo ?? collect()) as $rubro)
                    <option value="{{ $rubro->id }}" @selected($rubrosSeleccionados->contains((string) $rubro->id))>
                        {{ $rubro->nombre }}
                    </option>
                @endforeach
            </select>

            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Rubros seleccionados
                </p>

                <div id="rubrosPersonaLista" class="flex flex-wrap gap-2">
                    <span id="rubrosPersonaVacio" class="text-sm text-slate-500">
                        Todavía no se agregaron rubros.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
