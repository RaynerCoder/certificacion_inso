@php
    $rubrosSeleccionados = collect(old('rubros', $rubrosRegistrados ?? []))
        ->map(fn ($id) => (string) $id)
        ->filter()
        ->values();
@endphp

<div id="seccion_rubros">
    <div class="overflow-visible rounded-2xl border border-gray-200 bg-white shadow-sm">
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
                        <option value="{{ $rubro->id }}"
                            data-codigo="{{ $rubro->codigo_caeb }}"
                            data-nombre="{{ $rubro->nombre }}"
                            data-etiqueta="{{ $rubro->codigo_caeb }} - {{ $rubro->nombre }}">
                            {{ $rubro->codigo_caeb }} - {{ $rubro->nombre }}
                        </option>
                    @endforeach
                </select>

                <div class="ocupacion-persona-autocomplete" data-rubro-combobox>
                    <div class="ocupacion-persona-control">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" data-rubro-search
                            placeholder="Escriba el nombre del rubro" autocomplete="off">
                    </div>

                    <div class="ocupacion-persona-resultados" id="rubroPersonaOpciones"
                        data-rubro-options-list></div>
                </div>
            </div>

            <div class="persona-rubros-selected-card">
                <p id="rubrosPersonaResumen" class="persona-rubro-summary">
                    Rubros seleccionados ({{ $rubrosSeleccionados->count() }})
                </p>

                <div id="rubrosPersonaLista" class="flex flex-wrap gap-2">
                    <span id="rubrosPersonaVacio" class="text-sm text-slate-500">
                        Todavía no se agregaron rubros.
                    </span>
                </div>
            </div>

            {{-- Este select queda oculto porque mantiene el envío real como rubros[]. --}}
            <select id="rubrosPersona" name="rubros[]" multiple class="hidden">
                @foreach (($rubrosCatalogo ?? collect()) as $rubro)
                    <option value="{{ $rubro->id }}" @selected($rubrosSeleccionados->contains((string) $rubro->id))>
                        {{ $rubro->codigo_caeb }} - {{ $rubro->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
