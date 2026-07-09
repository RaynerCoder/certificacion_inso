{{-- Paso 3: presentacion y registro del producto. --}}
<div class="producto-step" data-producto-step="2">
    <section class="producto-section">
        <div class="producto-step-compose">
            <div class="producto-form-panel">
                {{-- Bloque 1: datos que se guardaran en presentaciones. --}}
                <div class="producto-inline-head is-amber">
                    <span>
                        <i class="fa-solid fa-box-open"></i>
                    </span>
                    <strong>Datos de la presentacion</strong>
                </div>

                <div class="producto-registro-grid producto-registro-grid-presentacion">
                    <div class="md:col-span-12">
                        <label class="producto-field-label" for="form_presentacion_catalogo">
                            Presentacion registrada para este importador y producto
                        </label>
                        <select class="producto-select" id="form_presentacion_catalogo">
                            <option value="">Crear nueva presentacion o seleccionar una existente</option>
                            @foreach ($presentacionesCatalogo as $presentacionCatalogo)
                                @php
                                    $productoCatalogo = $presentacionCatalogo->producto;
                                    $unidadCatalogo = $presentacionCatalogo->catalogoUnidad;
                                    $textoUnidadCatalogo = $unidadCatalogo
                                        ? trim($unidadCatalogo->nombre . ($unidadCatalogo->abreviatura ? ' (' . $unidadCatalogo->abreviatura . ')' : ''))
                                        : 'Sin unidad';
                                    $textoPresentacionCatalogo = trim(($presentacionCatalogo->cantidad ?? '') . ' ' . $textoUnidadCatalogo);
                                    $textoPresentacionCatalogo = $textoPresentacionCatalogo ?: 'Presentacion #' . $presentacionCatalogo->id;
                                    $textoPresentacionCatalogo .= $presentacionCatalogo->descripcion ? ' - ' . $presentacionCatalogo->descripcion : '';
                                    $urlEtiquetaCatalogo = $presentacionCatalogo->url_etiqueta
                                        ? \Illuminate\Support\Facades\Storage::url($presentacionCatalogo->url_etiqueta)
                                        : '';
                                @endphp
                                <option value="{{ $presentacionCatalogo->id }}"
                                    data-importador-id="{{ $productoCatalogo?->id_importador_persona }}"
                                    data-producto-codigo="{{ $productoCatalogo?->codigo }}"
                                    data-producto-nombre="{{ $productoCatalogo?->nombre_comercial }}"
                                    data-cantidad="{{ $presentacionCatalogo->cantidad }}"
                                    data-unidad-id="{{ $presentacionCatalogo->id_catalogo_unidad }}"
                                    data-unidad-texto="{{ $textoUnidadCatalogo }}"
                                    data-descripcion="{{ $presentacionCatalogo->descripcion }}"
                                    data-estado="{{ $presentacionCatalogo->estado }}"
                                    data-etiqueta-url="{{ $urlEtiquetaCatalogo }}"
                                    data-etiqueta-nombre="Etiqueta registrada - {{ $textoPresentacionCatalogo }}">
                                    {{ $textoPresentacionCatalogo }}
                                </option>
                            @endforeach
                        </select>
                        {{-- <p class="producto-field-help">
                            Solo se muestran presentaciones del importador seleccionado y del codigo o nombre comercial del producto actual.
                        </p> --}}
                        <input type="hidden" id="form_presentacion_origen_id" value="">
                    </div>

                    <div class="producto-campo-cantidad">
                        <label class="producto-field-label" for="form_presentacion_cantidad">Cantidad</label>
                        <input class="producto-input" id="form_presentacion_cantidad" type="number" min="0"
                            step="1" placeholder="Ej: 1">
                    </div>

                    <div class="producto-campo-unidad">
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

                    <div class="producto-campo-estado">
                        <label class="producto-field-label" for="form_presentacion_estado">Estado</label>
                        <select class="producto-select" id="form_presentacion_estado">
                            <option value="ACTIVO">Activo</option>
                            <option value="INACTIVO">Inactivo</option>
                        </select>
                    </div>

                    <div class="producto-campo-etiqueta">
                        <label class="producto-field-label" for="form_presentacion_etiqueta">Etiqueta PDF</label>

                        {{-- El input real queda oculto; estos botones pequenos controlan seleccionar, ver y quitar el PDF. --}}
                        <div id="presentacionEtiquetaWrapper" class="producto-upload-card producto-upload-card-compact">
                            <input class="producto-upload-input" id="form_presentacion_etiqueta" type="file"
                                accept="application/pdf,.pdf" onchange="actualizarVistaPreviaEtiquetaProducto()">

                            <span class="producto-upload-icon">
                                <i class="fa-regular fa-file-pdf"></i>
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

                    <div class="producto-campo-descripcion">
                        <label class="producto-field-label" for="form_presentacion_descripcion">Descripcion</label>
                        <textarea class="producto-textarea producto-textarea-compact" id="form_presentacion_descripcion"
                            placeholder="Ej: Envase plastico con tapa de seguridad."></textarea>
                    </div>
                </div>
            </div>

            <div class="producto-form-panel producto-form-panel-separated">
                {{-- Bloque 2: datos que se guardaran en registros y apuntaran a la presentacion anterior. --}}
                <div class="producto-inline-head is-teal">
                    <span>
                        <i class="fa-solid fa-clipboard-list"></i>
                    </span>
                    <strong>Datos del registro</strong>
                </div>

                <div class="producto-registro-grid producto-registro-grid-autorizacion">
                    <div class="producto-campo-codigo">
                        <label class="producto-field-label" for="form_registro_codigo_autorizacion">
                            Codigo de autorizacion
                        </label>
                        <input class="producto-input" id="form_registro_codigo_autorizacion" type="text"
                            placeholder="Ej: INSO-RP-001">
                    </div>

                    <div class="producto-campo-fecha">
                        <label class="producto-field-label" for="form_registro_fecha_vigencia">
                            Fecha vigencia
                        </label>
                        <input class="producto-input" id="form_registro_fecha_vigencia" type="date">
                    </div>

                    <div class="producto-campo-cantidad">
                        <label class="producto-field-label" for="form_registro_cantidad">Cantidad</label>
                        <input class="producto-input" id="form_registro_cantidad" type="number" min="0"
                            step="1" placeholder="120">
                    </div>

                    <div class="producto-campo-unidad">
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

                    <div class="producto-campo-estado">
                        <label class="producto-field-label" for="form_registro_estado">Estado</label>
                        <select class="producto-select" id="form_registro_estado">
                            <option value="ACTIVO">Activo</option>
                            <option value="INACTIVO">Inactivo</option>
                        </select>
                    </div>

                    <div class="producto-campo-acciones">
                        <button type="button" class="producto-btn producto-btn-orange producto-btn-inline"
                            onclick="agregarPresentacionRegistroProducto()">
                            <i class="fa-solid fa-link"></i>
                            Agregar presentacion y registro
                        </button>
                    </div>
                </div>
            </div>

            <div class="producto-table-wrap overflow-x-auto rounded-lg border border-slate-200">
                {{-- Presentaciones temporales reales que se enviaran al controlador.
                    Se separan de la tabla para que varios registros puedan reutilizar la misma presentacion sin duplicarla. --}}
                <div id="presentacionesOcultasProducto" class="hidden"></div>

                <table class="producto-table producto-table-registros-presentaciones">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>
                                <span class="producto-table-head-icon">
                                    <i class="fa-solid fa-cube"></i>
                                    Datos de presentacion
                                </span>
                            </th>
                            <th>
                                <span class="producto-table-head-icon">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Datos de registro
                                </span>
                            </th>
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
                                $presentacionTexto .= !empty($presentacionOld['descripcion']) ? ' - ' . $presentacionOld['descripcion'] : '';
                                $etiquetaOldTexto = !empty($presentacionOld['id_presentacion_origen'])
                                    ? 'Etiqueta registrada'
                                    : 'Debe volver a seleccionar PDF';
                            @endphp
                            <tr data-tipo-fila="registro" data-presentacion-indice="{{ $indicePresentacion }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="producto-table-product">
                                        <div class="producto-table-product-title">
                                            <strong class="presentacion-producto-nombre">
                                                {{ old('form_nombre_comercial', 'Sin nombre comercial') }}
                                            </strong>
                                            <span class="producto-table-status">ACTIVO</span>
                                        </div>

                                        <div class="producto-table-product-line">
                                            <i class="fa-solid fa-flask"></i>
                                            <span>Tipo de producto</span>
                                            <strong class="presentacion-producto-tipo">
                                                {{ old('form_tipo_producto_temporal_descripcion') ?: optional(collect($tiposProductosCatalogo)->firstWhere('id', old('form_id_tipo_producto')))->descripcion ?: 'Sin tipo' }}
                                            </strong>
                                        </div>

                                        <div class="producto-table-product-line">
                                            <i class="fa-solid fa-tag"></i>
                                            <span>Codigo producto</span>
                                            <strong class="presentacion-producto-codigo">
                                                {{ old('form_codigo', 'Sin codigo') }}
                                            </strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="producto-table-detail">
                                        <div>
                                            <span>Cantidad</span>
                                            <strong>{{ $presentacionOld['cantidad'] ?? '-' }}</strong>
                                        </div>
                                        <div>
                                            <span>Unidad</span>
                                            <strong>{{ $nombreCatalogoUnidad($presentacionOld['id_catalogo_unidad'] ?? null) }}</strong>
                                        </div>
                                        <div class="is-wide">
                                            <span>Descripcion</span>
                                            <strong>{{ $presentacionOld['descripcion'] ?? 'Sin descripcion' }}</strong>
                                        </div>
                                        <div class="is-wide">
                                            <span>Origen</span>
                                            <strong>
                                                {{ !empty($presentacionOld['id_presentacion_origen']) ? 'Copiada de presentacion #' . $presentacionOld['id_presentacion_origen'] : 'Nueva presentacion' }}
                                            </strong>
                                        </div>
                                        <div class="is-wide">
                                            <span>Etiqueta PDF</span>
                                            <div class="producto-table-file-input">
                                                <input class="producto-upload-input"
                                                    id="form_presentacion_etiqueta_old_{{ $indicePresentacion }}" type="file"
                                                    name="presentaciones[{{ $indicePresentacion }}][url_etiqueta]"
                                                    accept="application/pdf,.pdf"
                                                    onchange="actualizarEtiquetaOldProducto(this)">

                                                <button type="button" class="producto-upload-button is-select"
                                                    onclick="document.getElementById('form_presentacion_etiqueta_old_{{ $indicePresentacion }}')?.click()">
                                                    <i class="fa-solid fa-upload"></i>
                                                    Seleccionar
                                                </button>

                                                <span class="producto-table-file-icon">
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                </span>

                                                <span class="producto-table-file-name">{{ $etiquetaOldTexto }}</span>

                                                <button type="button" class="producto-upload-button is-view"
                                                    data-etiqueta-old-ver disabled>
                                                    <i class="fa-solid fa-eye"></i>
                                                    Ver
                                                </button>

                                                <button type="button" class="producto-upload-button is-remove"
                                                    onclick="limpiarEtiquetaOldProducto('form_presentacion_etiqueta_old_{{ $indicePresentacion }}')" disabled
                                                    data-etiqueta-old-quitar>
                                                    <i class="fa-solid fa-trash-can"></i>
                                                    Quitar
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <span>Estado presentacion</span>
                                            <strong class="producto-table-status">{{ $presentacionOld['estado'] ?? 'ACTIVO' }}</strong>
                                        </div>
                                    </div>
                                    <input type="hidden" name="presentaciones[{{ $indicePresentacion }}][cantidad]"
                                        value="{{ $presentacionOld['cantidad'] ?? '' }}">
                                    <input type="hidden" name="presentaciones[{{ $indicePresentacion }}][id_catalogo_unidad]"
                                        value="{{ $presentacionOld['id_catalogo_unidad'] ?? '' }}">
                                    <input type="hidden" name="presentaciones[{{ $indicePresentacion }}][descripcion]"
                                        value="{{ $presentacionOld['descripcion'] ?? '' }}">
                                    <input type="hidden" name="presentaciones[{{ $indicePresentacion }}][estado]"
                                        value="{{ $presentacionOld['estado'] ?? 'ACTIVO' }}">
                                    <input type="hidden" name="presentaciones[{{ $indicePresentacion }}][id_presentacion_origen]"
                                        value="{{ $presentacionOld['id_presentacion_origen'] ?? '' }}">
                                </td>
                                <td>
                                    <div class="producto-table-detail">
                                        <div class="is-wide">
                                            <span>Codigo autorizacion</span>
                                            <strong>{{ $registroOld['codigo_autorizacion'] ?? 'Sin codigo' }}</strong>
                                        </div>
                                        <div>
                                            <span>Vigencia</span>
                                            <strong>{{ $registroOld['fecha_vigencia'] ?? 'Sin fecha' }}</strong>
                                        </div>
                                        <div>
                                            <span>Cantidad</span>
                                            <strong>{{ $registroOld['cantidad'] ?? '-' }}</strong>
                                        </div>
                                        <div>
                                            <span>Unidad</span>
                                            <strong>{{ $nombreCatalogoUnidad($registroOld['id_catalogo_unidad'] ?? null) }}</strong>
                                        </div>
                                        <div class="is-wide">
                                            <span>Relacion</span>
                                            <strong>Usa la presentacion de esta fila</strong>
                                        </div>
                                        <div>
                                            <span>Estado registro</span>
                                            <strong class="producto-table-status">{{ $registroOld['estado'] ?? 'ACTIVO' }}</strong>
                                        </div>
                                    </div>
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
                                <td>
                                    <div class="producto-row-actions">
                                        <button type="button" class="producto-action-icon is-edit"
                                            title="Editar presentacion y registro"
                                            onclick="editarFilaRegistroPresentacionProducto(this)">
                                            <span>Editar</span>
                                        </button>

                                        <button type="button" class="producto-action-icon is-delete"
                                            title="Eliminar presentacion y registro"
                                            onclick="quitarFilaProducto(this)">
                                            <span>Quitar</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="sinRegistrosPresentacionesProducto">
                                <td colspan="5">
                                    <div class="producto-empty">
                                        Todavia no agregaste presentaciones y registros.
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
