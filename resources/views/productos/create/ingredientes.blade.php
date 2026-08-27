{{-- Paso 2: tabla pivote ingredientes_productos. --}}
<div class="producto-step" data-producto-step="1">
    <section class="producto-section">
        <div class="producto-step-grid space-y-5">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <label class="producto-field-label !mb-0" for="form_ingrediente_select">Ingrediente</label>
                        <button type="button" class="text-xs font-bold text-teal-700 hover:text-teal-900"
                            onclick="abrirModalProducto('modalIngredienteProducto')">
                            + Nuevo ingrediente
                        </button>
                    </div>
                    {{-- Select real: mantiene el valor que usa el JS y el controlador. El diseño visible se arma abajo. --}}
                    <select class="producto-select producto-ingredient-native-select" id="form_ingrediente_select">
                        <option value="">Seleccione un ingrediente</option>
                        @foreach ($ingredientesCatalogo as $ingrediente)
                            <option value="{{ $ingrediente->id }}"
                                data-nombre="{{ $ingrediente->nombre }}">
                                {{ $ingrediente->nombre }}
                            </option>
                        @endforeach
                    </select>

                    {{-- El buscador y los resultados comparten el mismo borde para evitar dos controles separados. --}}
                    <div class="producto-ingredient-select" data-ingrediente-combobox>
                        <button type="button" class="producto-ingredient-trigger" data-ingrediente-trigger
                            aria-expanded="false" aria-controls="form_ingrediente_dropdown">
                            <span class="producto-ingredient-trigger-text">
                                <strong data-ingrediente-label>Seleccione un ingrediente</strong>
                            </span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                        <div class="producto-ingredient-dropdown" id="form_ingrediente_dropdown">
                            <label class="producto-ingredient-search" for="form_ingrediente_search">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input id="form_ingrediente_search" type="search" placeholder="Buscar ingrediente"
                                    autocomplete="off" data-ingrediente-search>
                                <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                            </label>
                            <div class="producto-ingredient-options" id="form_ingrediente_options" role="listbox"></div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <label class="producto-field-label" for="form_ingrediente_porcentaje">Porcentaje</label>
                    <input class="producto-input" id="form_ingrediente_porcentaje" type="number" min="0"
                        max="100" step="0.00001" placeholder="Ej: 0.005">
                </div>

                <div class="lg:col-span-3">
                    <label class="producto-field-label" for="form_ingrediente_estado">Estado</label>
                    <select class="producto-select" id="form_ingrediente_estado">
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" class="producto-btn producto-btn-verde" onclick="agregarIngredienteProducto()">
                    <i class="fa-solid fa-plus"></i>
                    Agregar ingrediente
                </button>
            </div>

            <div class="producto-table-wrap overflow-x-auto rounded-lg border border-slate-200">
                <table class="producto-table producto-table-ingredientes">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre ingrediente</th>
                            <th>Porcentaje</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaIngredientesProducto">
                        @forelse ($ingredientesOld as $indice => $ingredienteOld)
                            @php
                                $ingredienteCatalogo = $ingredientesCatalogo->firstWhere(
                                    'id',
                                    (int) ($ingredienteOld['id_ingrediente'] ?? 0)
                                );
                                $nombreIngrediente = $ingredienteOld['nombre']
                                    ?? $ingredienteCatalogo?->nombre
                                    ?? 'Ingrediente sin nombre';
                                $estadoIngrediente = $ingredienteOld['estado'] ?? 'ACTIVO';
                            @endphp
                            <tr>
                                <td class="producto-ingredient-index">{{ $loop->iteration }}</td>
                                <td class="producto-ingredient-name">
                                    {{ $nombreIngrediente }}
                                    <input type="hidden"
                                        name="ingredientes_productos[{{ $indice }}][id_ingrediente]"
                                        value="{{ $ingredienteOld['id_ingrediente'] ?? '' }}">
                                    <input type="hidden" name="ingredientes_productos[{{ $indice }}][nombre]"
                                        value="{{ $nombreIngrediente }}">
                                    <input type="hidden" name="ingredientes_productos[{{ $indice }}][porcentaje]"
                                        value="{{ $ingredienteOld['porcentaje'] ?? '' }}">
                                    <input type="hidden" name="ingredientes_productos[{{ $indice }}][estado]"
                                        value="{{ $estadoIngrediente }}">
                                </td>
                                <td class="producto-ingredient-percentage">
                                    {{ rtrim(rtrim(number_format((float) ($ingredienteOld['porcentaje'] ?? 0), 5, '.', ''), '0'), '.') }}%
                                </td>
                                <td><span class="producto-pill">{{ $estadoIngrediente }}</span></td>
                                <td>
                                    <button type="button" class="producto-btn producto-btn-danger"
                                        onclick="quitarFilaProducto(this)">
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                        <span>Quitar</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="sinIngredientesProducto">
                                <td colspan="5">
                                    <div class="producto-empty">Todavía no agregaste ingredientes.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">
                                <div class="producto-ingredientes-total">
                                    <div class="producto-ingredientes-total-head">
                                        <span>Total de porcentaje de ingredientes</span>
                                        <strong id="totalPorcentajeIngredientesProducto">0%</strong>
                                    </div>
                                    <div class="producto-ingredientes-progress" aria-hidden="true">
                                        <span id="barraPorcentajeIngredientesProducto"></span>
                                    </div>
                                    <p class="hidden producto-ingredientes-alert" id="alertaPorcentajeIngredientesProducto">
                                        La suma de los porcentajes excedió el 100%.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </section>
</div>
