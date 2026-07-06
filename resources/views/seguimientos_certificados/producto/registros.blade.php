{{-- Paso 4: registros del producto. --}}
<div class="producto-step" data-producto-step="3">
    <section class="producto-section">
        <div class="space-y-5 p-5">
            {{-- Cada registro pertenece al producto y puede apuntar a una presentacion agregada. --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label class="producto-field-label" for="form_registro_codigo_autorizacion">
                        Codigo de autorizacion
                    </label>
                    <input class="producto-input" id="form_registro_codigo_autorizacion"
                        type="text" placeholder="Ej: SENASAG-RS-001">
                </div>

                <div class="lg:col-span-3">
                    <label class="producto-field-label" for="form_registro_fecha_vigencia">
                        Fecha vigencia
                    </label>
                    <input class="producto-input" id="form_registro_fecha_vigencia" type="date">
                </div>

                <div class="lg:col-span-2">
                    <label class="producto-field-label" for="form_registro_cantidad">Cantidad</label>
                    <input class="producto-input" id="form_registro_cantidad" type="number" min="0" step="1">
                </div>

                <div class="lg:col-span-3">
                    <label class="producto-field-label" for="form_registro_unidad">Unidad</label>
                    <input class="producto-input" id="form_registro_unidad" type="text" placeholder="Ej: Litros">
                </div>

                <div class="lg:col-span-8">
                    <label class="producto-field-label" for="form_registro_presentacion_temporal">
                        Presentacion seleccionada
                    </label>
                    <select class="producto-select" id="form_registro_presentacion_temporal">
                        <option value="">Seleccione una presentacion agregada</option>
                    </select>
                </div>

                <div class="lg:col-span-4">
                    <label class="producto-field-label" for="form_registro_estado">Estado del registro</label>
                    <select class="producto-select" id="form_registro_estado">
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" class="producto-btn producto-btn-primary" onclick="agregarRegistroProducto()">
                    <i class="fa-solid fa-clipboard-list"></i>
                    Agregar registro
                </button>
            </div>

            <div class="producto-table-wrap overflow-x-auto rounded-lg border border-slate-200">
                <table class="producto-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Codigo</th>
                            <th>Vigencia</th>
                            <th>Cantidad</th>
                            <th>Unidad</th>
                            <th>Presentacion</th>
                            <th>Estado</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody id="tablaRegistrosProducto">
                        @forelse ($registrosOld as $indice => $registroOld)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong class="registro-producto-nombre">{{ old('form_nombre_comercial', 'Sin nombre comercial') }}</strong>
                                    <div class="text-xs text-slate-500 registro-producto-codigo">{{ old('form_codigo', 'Sin codigo') }}</div>
                                </td>
                                <td>
                                    {{ $registroOld['codigo_autorizacion'] ?? 'Sin codigo' }}
                                    <input type="hidden" name="registros[{{ $indice }}][codigo_autorizacion]" value="{{ $registroOld['codigo_autorizacion'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][fecha_vigencia]" value="{{ $registroOld['fecha_vigencia'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][cantidad]" value="{{ $registroOld['cantidad'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][unidad]" value="{{ $registroOld['unidad'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][id_presentacion_temporal]" value="{{ $registroOld['id_presentacion_temporal'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][presentacion_texto]" value="{{ $registroOld['presentacion_texto'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][estado]" value="{{ $registroOld['estado'] ?? 'ACTIVO' }}">
                                </td>
                                <td>{{ $registroOld['fecha_vigencia'] ?? 'Sin fecha' }}</td>
                                <td>{{ $registroOld['cantidad'] ?? '-' }}</td>
                                <td>{{ $registroOld['unidad'] ?? '-' }}</td>
                                <td>{{ $registroOld['presentacion_texto'] ?? 'Sin presentacion' }}</td>
                                <td><span class="producto-pill">{{ $registroOld['estado'] ?? 'ACTIVO' }}</span></td>
                                <td>
                                    <button type="button" class="producto-btn producto-btn-danger" onclick="quitarFilaProducto(this)">
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                        <span>Quitar</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="sinRegistrosProducto">
                                <td colspan="9">
                                    <div class="producto-empty">
                                        Todavia no agregaste registros. Primero agrega una presentacion y luego relaciona aqui su registro.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-600">
                El certificado se asociara al registro desde el modulo de certificados usando la tabla
                certificados_registros.
            </div>
        </div>
    </section>
</div>
