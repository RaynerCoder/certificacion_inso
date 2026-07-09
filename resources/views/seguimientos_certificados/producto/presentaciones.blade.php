{{-- Paso 3: registros y presentaciones del producto. --}}
<div class="producto-step" data-producto-step="2">
    <section class="producto-section">
        <div class="producto-step-compose">
            <div class="producto-form-panel">
                {{-- Primero se registran presentaciones; luego los registros apuntan a una de ellas. --}}
                <div class="producto-inline-head is-amber">
                    <span>
                        <i class="fa-solid fa-box-open"></i>
                    </span>
                    <strong>Datos de la presentacion</strong>
                </div>

                <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-12">
                    <div class="md:col-span-3 xl:col-span-2">
                        <label class="producto-field-label" for="form_presentacion_cantidad">Cantidad</label>
                        <input class="producto-input" id="form_presentacion_cantidad" type="number" min="0"
                            step="1" placeholder="Ej: 1">
                    </div>

                    <div class="md:col-span-5 xl:col-span-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label class="producto-field-label !mb-0" for="form_presentacion_unidad">Unidad</label>
                            <button type="button" class="text-xs font-bold text-teal-700 hover:text-teal-900"
                                onclick="abrirModalUnidadProducto('form_presentacion_unidad')">
                                + Nueva unidad
                            </button>
                        </div>
                        <select class="producto-select producto-select-search" id="form_presentacion_unidad" data-producto-buscador="1">
                            <option value="">Seleccione unidad</option>
                            @if (str_starts_with((string) old('form_unidad_temporal_id'), 'TEMP-') && old('form_unidad_temporal_nombre'))
                                <option value="{{ old('form_unidad_temporal_id') }}" data-temporal="1"
                                    data-texto="{{ trim(old('form_unidad_temporal_nombre') . (old('form_unidad_temporal_abreviatura') ? ' (' . old('form_unidad_temporal_abreviatura') . ')' : '')) }}">
                                    {{ old('form_unidad_temporal_nombre') }}{{ old('form_unidad_temporal_abreviatura') ? ' (' . old('form_unidad_temporal_abreviatura') . ')' : '' }}
                                </option>
                            @endif
                            @foreach ($catalogosUnidades as $catalogoUnidad)
                                <option value="{{ $catalogoUnidad->id }}"
                                    data-texto="{{ trim($catalogoUnidad->nombre . ($catalogoUnidad->abreviatura ? ' (' . $catalogoUnidad->abreviatura . ')' : '')) }}">
                                    {{ $catalogoUnidad->nombre }}{{ $catalogoUnidad->abreviatura ? ' (' . $catalogoUnidad->abreviatura . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-4 xl:col-span-2">
                        <label class="producto-field-label" for="form_presentacion_estado">Estado</label>
                        <select class="producto-select" id="form_presentacion_estado">
                            <option value="ACTIVO">Activo</option>
                            <option value="INACTIVO">Inactivo</option>
                        </select>
                    </div>

                    <div class="md:col-span-12 xl:col-span-5">
                        <label class="producto-field-label" for="form_presentacion_etiqueta">Etiqueta PDF</label>

                        {{-- El input real queda oculto; estos botones pequeños controlan seleccionar, ver y quitar el PDF. --}}
                        <div id="presentacionEtiquetaWrapper" class="producto-upload-card producto-upload-card-compact">
                            <input class="producto-upload-input" id="form_presentacion_etiqueta" type="file"
                                accept="application/pdf,.pdf" onchange="actualizarVistaPreviaEtiquetaProducto()">

                            <span class="producto-upload-icon">
                                <i class="fa-solid fa-file-pdf"></i>
                            </span>

                            <span id="presentacionEtiquetaNombre" class="producto-upload-name">
                                Sin PDF seleccionado.
                            </span>

                            <div class="producto-upload-actions">
                                <button type="button" class="producto-upload-button is-select"
                                    onclick="document.getElementById('form_presentacion_etiqueta')?.click()">
                                    <i class="fa-solid fa-upload"></i>
                                    Seleccionar
                                </button>

                                <button type="button" id="btnVerEtiquetaPdf" class="producto-upload-button is-view"
                                    onclick="abrirModalEtiquetaProducto()" disabled>
                                    <i class="fa-solid fa-eye"></i>
                                    Ver
                                </button>

                                <button type="button" id="btnQuitarEtiquetaPdf" class="producto-upload-button is-remove"
                                    onclick="limpiarEtiquetaPresentacionProducto()" disabled>
                                    <i class="fa-solid fa-xmark"></i>
                                    Quitar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-8 xl:col-span-9">
                        <label class="producto-field-label" for="form_presentacion_descripcion">Descripcion</label>
                        <textarea class="producto-textarea producto-textarea-compact" id="form_presentacion_descripcion"
                            placeholder="Ej: Envase plastico con tapa de seguridad."></textarea>
                    </div>

                    <div class="md:col-span-4 xl:col-span-3 flex justify-end">
                        <button type="button" class="producto-btn producto-btn-orange producto-btn-inline" onclick="agregarPresentacionProducto()">
                            <i class="fa-solid fa-plus"></i>
                            Agregar presentacion
                        </button>
                    </div>
                </div>
            </div>

            <div class="producto-table-wrap overflow-x-auto rounded-lg border border-slate-200">
                <table class="producto-table producto-table-presentaciones">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Cantidad presentacion</th>
                            <th>Unidad presentacion</th>
                            <th>Descripcion presentacion</th>
                            <th>Etiqueta PDF</th>
                            <th>Estado</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPresentacionesProducto">
                        @forelse ($presentacionesOld as $indice => $presentacionOld)
                            @php
                                $presentacionTexto = trim(
                                    ($presentacionOld['cantidad'] ?? '') . ' ' . $nombreCatalogoUnidad($presentacionOld['id_catalogo_unidad'] ?? null)
                                );
                                $presentacionTexto = $presentacionTexto ?: 'Presentacion #' . ($indice + 1);
                                $presentacionTexto = $presentacionTexto . (!empty($presentacionOld['descripcion']) ? ' - ' . $presentacionOld['descripcion'] : '');
                            @endphp
                            <tr data-tipo-fila="presentacion" data-presentacion-indice="{{ $indice }}"
                                data-presentacion-texto="{{ $presentacionTexto }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong class="presentacion-producto-nombre">
                                        {{ old('form_nombre_comercial', 'Sin nombre comercial') }}
                                    </strong>
                                    <div class="text-xs text-slate-500 presentacion-producto-codigo">
                                        {{ old('form_codigo', 'Sin codigo') }}
                                    </div>
                                </td>
                                <td>
                                    {{ $presentacionOld['cantidad'] ?? '-' }}
                                    <input type="hidden" name="presentaciones[{{ $indice }}][cantidad]"
                                        value="{{ $presentacionOld['cantidad'] ?? '' }}">
                                    <input type="hidden" name="presentaciones[{{ $indice }}][id_catalogo_unidad]"
                                        value="{{ $presentacionOld['id_catalogo_unidad'] ?? '' }}">
                                    <input type="hidden" name="presentaciones[{{ $indice }}][descripcion]"
                                        value="{{ $presentacionOld['descripcion'] ?? '' }}">
                                    <input type="hidden" name="presentaciones[{{ $indice }}][estado]"
                                        value="{{ $presentacionOld['estado'] ?? 'ACTIVO' }}">
                                </td>
                                <td>{{ $nombreCatalogoUnidad($presentacionOld['id_catalogo_unidad'] ?? null) }}</td>
                                <td>{{ $presentacionOld['descripcion'] ?? 'Sin descripcion' }}</td>
                                <td>
                                    <div class="producto-table-file-input">
                                        <input class="producto-upload-input"
                                            id="form_presentacion_etiqueta_old_{{ $indice }}" type="file"
                                            name="presentaciones[{{ $indice }}][url_etiqueta]"
                                            accept="application/pdf,.pdf"
                                            onchange="actualizarEtiquetaOldProducto(this)">

                                        <button type="button" class="producto-upload-button is-select"
                                            onclick="document.getElementById('form_presentacion_etiqueta_old_{{ $indice }}')?.click()">
                                            <i class="fa-solid fa-upload"></i>
                                            Seleccionar
                                        </button>

                                        <button type="button" class="producto-upload-button is-view"
                                            data-etiqueta-old-ver disabled>
                                            <i class="fa-solid fa-eye"></i>
                                            Ver
                                        </button>

                                        <button type="button" class="producto-upload-button is-remove"
                                            onclick="limpiarEtiquetaOldProducto('form_presentacion_etiqueta_old_{{ $indice }}')" disabled
                                            data-etiqueta-old-quitar>
                                            <i class="fa-solid fa-xmark"></i>
                                            Quitar
                                        </button>

                                        <span class="producto-table-file-name">Debe volver a seleccionar PDF</span>
                                    </div>
                                </td>
                                <td><span class="producto-pill">{{ $presentacionOld['estado'] ?? 'ACTIVO' }}</span></td>
                                <td>
                                    <button type="button" class="producto-btn producto-btn-danger"
                                        onclick="quitarPresentacionProducto(this)">
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                        <span>Quitar</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="sinPresentacionesProducto">
                                <td colspan="8">
                                    <div class="producto-empty">
                                        Todavia no agregaste presentaciones.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="producto-form-panel producto-form-panel-separated">
                {{-- Los registros se agregan despues de crear al menos una presentacion. --}}
                <div class="producto-inline-head is-teal">
                    <span>
                        <i class="fa-solid fa-clipboard-list"></i>
                    </span>
                    <strong>Datos del registro</strong>
                </div>

                <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-12">
                    <div class="md:col-span-7 xl:col-span-5">
                        <label class="producto-field-label" for="form_registro_presentacion">
                            Presentacion asociada
                        </label>
                        <select class="producto-select" id="form_registro_presentacion">
                            <option value="">Seleccione una presentacion</option>
                            @foreach ($presentacionesOld as $indice => $presentacionOld)
                                @php
                                    $presentacionTexto = trim(
                                       ($presentacionOld['cantidad'] ?? '') . ' ' . $nombreCatalogoUnidad($presentacionOld['id_catalogo_unidad'] ?? null)
                                    );
                                    $presentacionTexto = $presentacionTexto ?: 'Presentacion #' . ($indice + 1);
                                    $presentacionTexto = $presentacionTexto . (!empty($presentacionOld['descripcion']) ? ' - ' . $presentacionOld['descripcion'] : '');
                                @endphp
                                <option value="{{ $indice }}" data-texto="{{ $presentacionTexto }}">
                                    {{ $presentacionTexto }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-5 xl:col-span-4">
                        <label class="producto-field-label" for="form_registro_codigo_autorizacion">
                            Codigo de autorizacion
                        </label>
                        <input class="producto-input" id="form_registro_codigo_autorizacion" type="text"
                            placeholder="Ej: SENASAG-RS-001">
                    </div>

                    <div class="md:col-span-4 xl:col-span-3">
                        <label class="producto-field-label" for="form_registro_fecha_vigencia">
                            Fecha vigencia
                        </label>
                        <input class="producto-input" id="form_registro_fecha_vigencia" type="date">
                    </div>

                    <div class="md:col-span-3 xl:col-span-2">
                        <label class="producto-field-label" for="form_registro_estado">Estado</label>
                        <select class="producto-select" id="form_registro_estado">
                            <option value="ACTIVO">Activo</option>
                            <option value="INACTIVO">Inactivo</option>
                        </select>
                    </div>

                    <div class="md:col-span-3 xl:col-span-2">
                        <label class="producto-field-label" for="form_registro_cantidad">Cantidad</label>
                        <input class="producto-input" id="form_registro_cantidad" type="number" min="0"
                            step="1" placeholder="120">
                    </div>

                    <div class="md:col-span-6 xl:col-span-3">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label class="producto-field-label !mb-0" for="form_registro_unidad">Unidad</label>
                            <button type="button" class="text-xs font-bold text-teal-700 hover:text-teal-900"
                                onclick="abrirModalUnidadProducto('form_registro_unidad')">
                                + Nueva unidad
                            </button>
                        </div>
                        <select class="producto-select producto-select-search" id="form_registro_unidad" data-producto-buscador="1">
                            <option value="">Seleccione unidad</option>
                            @if (str_starts_with((string) old('form_unidad_temporal_id'), 'TEMP-') && old('form_unidad_temporal_nombre'))
                                <option value="{{ old('form_unidad_temporal_id') }}" data-temporal="1"
                                    data-texto="{{ trim(old('form_unidad_temporal_nombre') . (old('form_unidad_temporal_abreviatura') ? ' (' . old('form_unidad_temporal_abreviatura') . ')' : '')) }}">
                                    {{ old('form_unidad_temporal_nombre') }}{{ old('form_unidad_temporal_abreviatura') ? ' (' . old('form_unidad_temporal_abreviatura') . ')' : '' }}
                                </option>
                            @endif
                            @foreach ($catalogosUnidades as $catalogoUnidad)
                                <option value="{{ $catalogoUnidad->id }}"
                                    data-texto="{{ trim($catalogoUnidad->nombre . ($catalogoUnidad->abreviatura ? ' (' . $catalogoUnidad->abreviatura . ')' : '')) }}">
                                    {{ $catalogoUnidad->nombre }}{{ $catalogoUnidad->abreviatura ? ' (' . $catalogoUnidad->abreviatura . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-12 xl:col-span-5 flex justify-end">
                        <button type="button" class="producto-btn producto-btn-orange producto-btn-inline" onclick="agregarRegistroProducto()">
                            <i class="fa-solid fa-link"></i>
                            Agregar registro
                        </button>
                    </div>
                </div>
            </div>

            <div class="producto-table-wrap overflow-x-auto rounded-lg border border-slate-200">
                <table class="producto-table producto-table-registros-presentaciones">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Codigo registro</th>
                            <th>Fecha vigencia</th>
                            <th>Cantidad registro</th>
                            <th>Unidad registro</th>
                            <th>Presentacion asociada</th>
                            <th>Estado</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody id="tablaRegistrosPresentacionesProducto">
                        @forelse ($registrosOld as $indice => $registroOld)
                            @php
                                $indicePresentacion = $registroOld['id_presentacion_temporal'] ?? $indice;
                                $presentacionOld = $presentacionesOld[$indicePresentacion] ?? null;
                                $presentacionTexto = $registroOld['presentacion_texto'] ?? trim(
                                    ($presentacionOld['cantidad'] ?? '') . ' ' . $nombreCatalogoUnidad($presentacionOld['id_catalogo_unidad'] ?? null)
                                );
                                $presentacionTexto = $presentacionTexto ?: 'Presentacion #' . ((int) $indicePresentacion + 1);
                            @endphp
                            <tr data-tipo-fila="registro" data-presentacion-indice="{{ $indicePresentacion }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong class="presentacion-producto-nombre">
                                        {{ old('form_nombre_comercial', 'Sin nombre comercial') }}
                                    </strong>
                                    <div class="text-xs text-slate-500 presentacion-producto-codigo">
                                        {{ old('form_codigo', 'Sin codigo') }}
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $registroOld['codigo_autorizacion'] ?? 'Sin codigo' }}</strong>
                                    <input type="hidden" name="registros[{{ $indice }}][codigo_autorizacion]"
                                        value="{{ $registroOld['codigo_autorizacion'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][fecha_vigencia]"
                                        value="{{ $registroOld['fecha_vigencia'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][cantidad]"
                                        value="{{ $registroOld['cantidad'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][id_catalogo_unidad]"
                                        value="{{ $registroOld['id_catalogo_unidad'] ?? '' }}">
                                    <input type="hidden" name="registros[{{ $indice }}][id_presentacion_temporal]"
                                        value="{{ $indicePresentacion }}">
                                    <input type="hidden" name="registros[{{ $indice }}][presentacion_texto]"
                                        value="{{ $presentacionTexto }}">
                                    <input type="hidden" name="registros[{{ $indice }}][estado]"
                                        value="{{ $registroOld['estado'] ?? 'ACTIVO' }}">
                                </td>
                                <td>{{ $registroOld['fecha_vigencia'] ?? 'Sin fecha' }}</td>
                                <td>{{ $registroOld['cantidad'] ?? '-' }}</td>
                                <td>{{ $nombreCatalogoUnidad($registroOld['id_catalogo_unidad'] ?? null) }}</td>
                                <td>{{ $presentacionTexto }}</td>
                                <td><span class="producto-pill">{{ $registroOld['estado'] ?? 'ACTIVO' }}</span></td>
                                <td>
                                    <button type="button" class="producto-btn producto-btn-danger"
                                        onclick="quitarFilaProducto(this)">
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                        <span>Quitar</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="sinRegistrosPresentacionesProducto">
                                <td colspan="9">
                                    <div class="producto-empty">
                                        Todavia no agregaste registros.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
