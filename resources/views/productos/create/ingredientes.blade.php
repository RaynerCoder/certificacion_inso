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
                                data-nombre="{{ $ingrediente->nombre }}"
                                data-composicion="{{ $ingrediente->composicion }}"
                                data-riesgo-salud="{{ $ingrediente->riesgo_salud }}">
                                {{ $ingrediente->nombre }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Selector visual: permite mostrar nombre y composicion en dos lineas dentro del desplegable. --}}
                    <div class="producto-ingredient-select" data-ingrediente-combobox>
                        <button type="button" class="producto-ingredient-trigger" data-ingrediente-trigger
                            aria-expanded="false" aria-controls="form_ingrediente_options">
                            <span class="producto-ingredient-trigger-text">
                                <strong data-ingrediente-label>Seleccione un ingrediente</strong>
                                <small data-ingrediente-detail>Composicion como detalle</small>
                            </span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                        <div class="producto-ingredient-options" id="form_ingrediente_options" role="listbox"></div>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <label class="producto-field-label" for="form_ingrediente_porcentaje">Porcentaje</label>
                    {{-- La columna porcentaje es entera en la base de datos, por eso se permite de 0 a 100 sin decimales. --}}
                    <input class="producto-input" id="form_ingrediente_porcentaje" type="number" min="0"
                        max="100" step="1" placeholder="Ej: 48">
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
                <table class="producto-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Nombre ingrediente</th>
                            <th>Composición</th>
                            <th>Riesgo de salud</th>
                            <th>Porcentaje</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaIngredientesProducto">
                        <tr id="sinIngredientesProducto">
                            <td colspan="8">
                                <div class="producto-empty">Todavía no agregaste ingredientes.
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
