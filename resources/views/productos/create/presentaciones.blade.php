{{-- Paso 3: captura el registro y la presentacion que se guardaran relacionados. --}}
<div class="producto-step" data-producto-step="2">
    <section class="producto-section">
        <div class="producto-registro-presentacion">
            <div class="producto-rp-layout">
                <div class="producto-rp-formularios">
                    {{-- El registro se completa primero porque identifica la autorizacion del producto. --}}
                    <section class="producto-rp-card" aria-labelledby="tituloDatosRegistro">
                        <div class="producto-rp-card-head">
                            <span class="producto-rp-card-number">1</span>
                            <div>
                                <h3 id="tituloDatosRegistro">Datos del registro</h3>
                                <p>Información de autorización, lote y vigencia.</p>
                            </div>
                        </div>

                        <div class="producto-registro-grid producto-registro-grid-autorizacion">
                            <div class="producto-campo-codigo">
                                <label class="producto-field-label" for="form_registro_codigo_autorizacion">
                                    Código de autorización
                                </label>
                                <input class="producto-input" id="form_registro_codigo_autorizacion" type="text"
                                    placeholder="Ej: INSO-RP-001">
                            </div>

                            <div class="producto-campo-lote">
                                <label class="producto-field-label" for="form_registro_numero_lote">Nro. de lote</label>
                                <input class="producto-input" id="form_registro_numero_lote" type="text"
                                    maxlength="150" placeholder="Ej: LOTE-2026-001">
                            </div>

                            <div class="producto-campo-fecha">
                                <label class="producto-field-label" for="form_registro_fecha_vigencia">
                                    Fecha de vigencia
                                </label>
                                <input class="producto-input" id="form_registro_fecha_vigencia" type="date">
                            </div>

                            <div class="producto-campo-cantidad">
                                <label class="producto-field-label" for="form_registro_cantidad">Cantidad total</label>
                                <input class="producto-input" id="form_registro_cantidad" type="number" min="0"
                                    step="1" placeholder="Ej: 120">
                            </div>

                            <div class="producto-campo-unidad">
                                <div class="producto-field-label-row">
                                    <label class="producto-field-label" for="form_registro_unidad">Unidad</label>
                                    <button type="button" class="producto-field-link"
                                        onclick="abrirModalUnidadProducto('form_registro_unidad')">
                                        + Nueva unidad
                                    </button>
                                </div>
                                <select class="producto-select producto-select-search" id="form_registro_unidad"
                                    data-producto-buscador="1">
                                    <option value="">Seleccione unidad</option>
                                    @if (str_starts_with((string) old('form_registro_unidad_temporal_id'), 'TEMP-') && old('form_registro_unidad_temporal_nombre'))
                                        <option value="{{ old('form_registro_unidad_temporal_id') }}" data-temporal="1"
                                            data-texto="{{ trim(old('form_registro_unidad_temporal_nombre') . (old('form_registro_unidad_temporal_abreviatura') ? ' (' . old('form_registro_unidad_temporal_abreviatura') . ')' : '')) }}">
                                            {{ old('form_registro_unidad_temporal_nombre') }}{{ old('form_registro_unidad_temporal_abreviatura') ? ' (' . old('form_registro_unidad_temporal_abreviatura') . ')' : '' }}
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
                        </div>
                    </section>

                    {{-- Una presentacion puede ser nueva o reutilizar una ya registrada para el mismo producto. --}}
                    <section class="producto-rp-card" aria-labelledby="tituloDatosPresentacion">
                        <div class="producto-rp-card-head">
                            <span class="producto-rp-card-number">2</span>
                            <div>
                                <h3 id="tituloDatosPresentacion">Datos de la presentación</h3>
                                <p>Defina el envase o formato comercial.</p>
                            </div>
                        </div>

                        <div class="producto-registro-grid producto-registro-grid-presentacion">
                            <div class="producto-campo-catalogo">
                                <div class="producto-field-label-row">
                                    <label class="producto-field-label" for="form_presentacion_catalogo">
                                        Seleccionar o crear presentación
                                    </label>
                                    <button type="button" class="producto-field-link"
                                        onclick="prepararNuevaPresentacionProducto()">
                                        + Nueva presentación
                                    </button>
                                </div>

                                <select class="producto-select" id="form_presentacion_catalogo">
                                    <option value="">Buscar o seleccionar una presentación existente</option>
                                    @foreach ($presentacionesCatalogo as $presentacionCatalogo)
                                        @php
                                            $productoCatalogo = $presentacionCatalogo->producto;
                                            $unidadCatalogo = $presentacionCatalogo->catalogoUnidad;
                                            $textoUnidadCatalogo = $unidadCatalogo
                                                ? trim($unidadCatalogo->nombre . ($unidadCatalogo->abreviatura ? ' (' . $unidadCatalogo->abreviatura . ')' : ''))
                                                : 'Sin unidad';
                                            $textoPresentacionCatalogo = trim(($presentacionCatalogo->cantidad ?? '') . ' ' . $textoUnidadCatalogo);
                                            $textoPresentacionCatalogo = $textoPresentacionCatalogo ?: 'Presentación #' . $presentacionCatalogo->id;
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
                                <input type="hidden" id="form_presentacion_origen_id" value="">
                            </div>

                            <div class="producto-campo-cantidad">
                                <label class="producto-field-label" for="form_presentacion_cantidad">Cantidad</label>
                                <input class="producto-input" id="form_presentacion_cantidad" type="number" min="0"
                                    step="1" placeholder="Ej: 1">
                            </div>

                            <div class="producto-campo-unidad">
                                <div class="producto-field-label-row">
                                    <label class="producto-field-label" for="form_presentacion_unidad">Unidad</label>
                                    <button type="button" class="producto-field-link"
                                        onclick="abrirModalUnidadProducto('form_presentacion_unidad')">
                                        + Nueva unidad
                                    </button>
                                </div>
                                <select class="producto-select producto-select-search" id="form_presentacion_unidad"
                                    data-producto-buscador="1">
                                    <option value="">Seleccione unidad</option>
                                    @if (str_starts_with((string) old('form_presentacion_unidad_temporal_id'), 'TEMP-') && old('form_presentacion_unidad_temporal_nombre'))
                                        <option value="{{ old('form_presentacion_unidad_temporal_id') }}" data-temporal="1"
                                            data-texto="{{ trim(old('form_presentacion_unidad_temporal_nombre') . (old('form_presentacion_unidad_temporal_abreviatura') ? ' (' . old('form_presentacion_unidad_temporal_abreviatura') . ')' : '')) }}">
                                            {{ old('form_presentacion_unidad_temporal_nombre') }}{{ old('form_presentacion_unidad_temporal_abreviatura') ? ' (' . old('form_presentacion_unidad_temporal_abreviatura') . ')' : '' }}
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

                            <div class="producto-campo-descripcion">
                                <label class="producto-field-label" for="form_presentacion_descripcion">Descripción</label>
                                <textarea class="producto-textarea producto-textarea-compact" id="form_presentacion_descripcion"
                                    maxlength="250" placeholder="Ej: Envase plástico con tapa de seguridad."></textarea>
                            </div>

                            <div class="producto-campo-acciones">
                                <button type="button" class="producto-btn producto-btn-primary producto-btn-inline"
                                    onclick="agregarPresentacionRegistroProducto()">
                                    <i class="fa-solid fa-plus"></i>
                                    Agregar presentación y registro
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- La etiqueta queda visible mientras se completan los datos de ambos bloques. --}}
                <aside class="producto-rp-card producto-rp-preview" aria-labelledby="tituloVistaEtiqueta">
                    <div class="producto-rp-card-head">
                        <span class="producto-rp-card-icon"><i class="fa-regular fa-image"></i></span>
                        <div>
                            <h3 id="tituloVistaEtiqueta">Vista previa de la etiqueta</h3>
                            <p>Compruebe la imagen antes de agregar la presentación.</p>
                        </div>
                    </div>

                    <div class="producto-etiqueta-workspace">
                        <button type="button" id="presentacionEtiquetaPreview"
                            class="producto-etiqueta-preview is-empty"
                            onclick="abrirModalEtiquetaProducto()" disabled>
                            <img id="presentacionEtiquetaImagen" alt="Vista previa de la etiqueta" hidden>
                            <span id="presentacionEtiquetaPlaceholder">
                                <i class="fa-regular fa-image"></i>
                                <strong>Sin imagen seleccionada</strong>
                                <small>Seleccione una etiqueta para verla aquí.</small>
                            </span>
                        </button>

                        <div class="producto-etiqueta-sidebar">
                            <div id="presentacionEtiquetaWrapper" class="producto-etiqueta-controls">
                                <input class="producto-upload-input" id="form_presentacion_etiqueta" type="file"
                                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                    onchange="actualizarVistaPreviaEtiquetaProducto()">

                                <button type="button" id="btnSeleccionarEtiquetaImagen"
                                    class="producto-upload-button is-select"
                                    onclick="document.getElementById('form_presentacion_etiqueta')?.click()">
                                    <i class="fa-solid fa-upload"></i>
                                    Seleccionar
                                </button>
                                <button type="button" id="btnCambiarEtiquetaImagen"
                                    class="producto-upload-button is-select"
                                    onclick="document.getElementById('form_presentacion_etiqueta')?.click()" disabled>
                                    <i class="fa-solid fa-rotate"></i>
                                    Cambiar
                                </button>
                                <button type="button" id="btnVerEtiquetaImagen"
                                    class="producto-upload-button is-view" onclick="abrirModalEtiquetaProducto()" disabled>
                                    <i class="fa-solid fa-eye"></i>
                                    Ver
                                </button>
                                <button type="button" id="btnQuitarEtiquetaImagen"
                                    class="producto-upload-button is-remove"
                                    onclick="limpiarEtiquetaPresentacionProducto()" disabled>
                                    <i class="fa-solid fa-trash-can"></i>
                                    Quitar
                                </button>
                            </div>

                            <div class="producto-etiqueta-info">
                                <i class="fa-solid fa-circle-info"></i>
                                <div>
                                    <span id="presentacionEtiquetaNombre">Sin imagen seleccionada.</span>
                                    <small>Formatos permitidos: JPG, PNG y WEBP.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <section class="producto-rp-card producto-rp-listado" aria-labelledby="tituloListadoPresentaciones">
                <div class="producto-rp-listado-head">
                    <div>
                        <h3 id="tituloListadoPresentaciones">Presentaciones y registros agregados</h3>
                        <p><span id="contadorRegistrosPresentacionesProducto">{{ count($registrosOld) }}</span> registro(s) en esta solicitud.</p>
                    </div>

                    <div class="producto-rp-listado-tools">
                        <label class="producto-table-filter">
                            <i class="fa-solid fa-filter"></i>
                            <select id="filtrarEstadoRegistrosPresentacionesProducto" aria-label="Filtrar por estado">
                                <option value="">Todos los estados</option>
                                <option value="ACTIVO">Activos</option>
                                <option value="INACTIVO">Inactivos</option>
                            </select>
                        </label>

                        <label class="producto-table-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input id="buscarRegistrosPresentacionesProducto" type="search"
                                placeholder="Buscar registro..." aria-label="Buscar en registros agregados">
                        </label>
                    </div>
                </div>

                {{-- Los campos ocultos contienen las presentaciones reales que recibirá el controlador. --}}
                <div id="presentacionesOcultasProducto" class="hidden"></div>

                <div class="producto-table-wrap overflow-x-auto">
                    <table class="producto-table producto-table-registros-presentaciones">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Código</th>
                                <th>Lote</th>
                                <th>Vigencia</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Presentación</th>
                                <th>Etiqueta</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaRegistrosPresentacionesProducto">
                            @forelse ($registrosOld as $indice => $registroOld)
                                @php
                                    $indicePresentacion = $registroOld['id_presentacion_temporal'] ?? $indice;
                                    $presentacionOld = $presentacionesOld[$indicePresentacion] ?? [];
                                    $textoUnidadPresentacion = $nombreCatalogoUnidad($presentacionOld['id_catalogo_unidad'] ?? null);
                                    $textoUnidadRegistro = $nombreCatalogoUnidad($registroOld['id_catalogo_unidad'] ?? null);
                                    $presentacionTexto = $registroOld['presentacion_texto'] ?? '';

                                    if (!$presentacionTexto) {
                                        $presentacionTexto = trim(
                                            ($presentacionOld['cantidad'] ?? '') . ' ' . $textoUnidadPresentacion
                                        );
                                        $presentacionTexto = $presentacionTexto ?: 'Presentación #' . ((int) $indicePresentacion + 1);
                                        $presentacionTexto .= !empty($presentacionOld['descripcion'])
                                            ? ' - ' . $presentacionOld['descripcion']
                                            : '';
                                    }
                                    $presentacionOrigenOld = !empty($presentacionOld['id_presentacion_origen'])
                                        ? $presentacionesCatalogo->firstWhere('id', $presentacionOld['id_presentacion_origen'])
                                        : null;
                                    $etiquetaOldUrl = $presentacionOrigenOld?->url_etiqueta
                                        ? \Illuminate\Support\Facades\Storage::url($presentacionOrigenOld->url_etiqueta)
                                        : '';
                                    $estadoRegistroOld = $registroOld['estado'] ?? 'ACTIVO';
                                @endphp
                                <tr data-tipo-fila="registro" data-presentacion-indice="{{ $indicePresentacion }}"
                                    data-estado-registro="{{ $estadoRegistroOld }}">
                                    <td data-label="Registro">{{ $loop->iteration }}</td>
                                    <td data-label="Código"><strong>{{ $registroOld['codigo_autorizacion'] ?? 'Sin código' }}</strong></td>
                                    <td data-label="Lote">{{ $registroOld['numero_lote'] ?? 'Sin lote' }}</td>
                                    <td data-label="Vigencia">{{ $registroOld['fecha_vigencia'] ?? 'Sin fecha' }}</td>
                                    <td data-label="Cantidad">{{ $registroOld['cantidad'] ?? '-' }}</td>
                                    <td data-label="Unidad">{{ $textoUnidadRegistro }}</td>
                                    <td data-label="Presentación">
                                        <div class="producto-table-presentacion">
                                            <strong>{{ $presentacionTexto }}</strong>
                                            <small>{{ $presentacionOld['estado'] ?? 'ACTIVO' }}</small>
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
                                    <td class="producto-table-etiqueta-cell" data-label="Etiqueta">
                                        <input class="producto-upload-input"
                                            id="form_presentacion_etiqueta_old_{{ $indicePresentacion }}" type="file"
                                            name="presentaciones[{{ $indicePresentacion }}][url_etiqueta]"
                                            accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                            data-url-inicial="{{ $etiquetaOldUrl }}"
                                            onchange="actualizarEtiquetaOldProducto(this)">

                                        <button type="button"
                                            class="producto-table-image-preview {{ $etiquetaOldUrl ? '' : 'is-empty' }}"
                                            data-etiqueta-old-preview
                                            onclick="abrirModalEtiquetaOldProducto('form_presentacion_etiqueta_old_{{ $indicePresentacion }}')"
                                            @disabled(!$etiquetaOldUrl)>
                                            <img src="{{ $etiquetaOldUrl }}" alt="Etiqueta de la presentación"
                                                data-etiqueta-old-imagen @hidden(!$etiquetaOldUrl)>
                                            <span data-etiqueta-old-placeholder @hidden($etiquetaOldUrl)>Sin imagen</span>
                                        </button>

                                        <span class="producto-table-file-name">
                                            {{ $etiquetaOldUrl ? 'Imagen registrada' : 'Seleccione una imagen' }}
                                        </span>
                                    </td>
                                    <td data-label="Estado">
                                        <span class="producto-table-status {{ $estadoRegistroOld === 'ACTIVO' ? '' : 'is-inactive' }}">
                                            {{ $estadoRegistroOld }}
                                        </span>
                                    </td>
                                    <td data-label="Acciones">
                                        <div class="producto-row-actions">
                                            <button type="button" class="producto-action-icon is-view"
                                                title="Ver etiqueta"
                                                onclick="abrirModalEtiquetaOldProducto('form_presentacion_etiqueta_old_{{ $indicePresentacion }}')"
                                                @disabled(!$etiquetaOldUrl)>
                                                <i class="fa-solid fa-eye"></i><span>Ver</span>
                                            </button>
                                            <button type="button" class="producto-action-icon is-edit"
                                                title="Editar presentación y registro"
                                                onclick="editarFilaRegistroPresentacionProducto(this)">
                                                <i class="fa-solid fa-pen"></i><span>Editar</span>
                                            </button>
                                            <button type="button" class="producto-action-icon is-delete"
                                                title="{{ !empty($registroOld['bloqueado_eliminacion']) ? 'Este registro ya está relacionado con un certificado' : 'Eliminar presentación y registro' }}"
                                                onclick="quitarFilaProducto(this)"
                                                @disabled(!empty($registroOld['bloqueado_eliminacion']))>
                                                <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                            </button>
                                        </div>
                                        <input type="hidden" name="registros[{{ $indice }}][id_registro]"
                                            value="{{ $registroOld['id_registro'] ?? '' }}">
                                        <input type="hidden" name="registros[{{ $indice }}][codigo_autorizacion]"
                                            value="{{ $registroOld['codigo_autorizacion'] ?? '' }}">
                                        <input type="hidden" name="registros[{{ $indice }}][numero_lote]"
                                            value="{{ $registroOld['numero_lote'] ?? '' }}">
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
                                            value="{{ $estadoRegistroOld }}">
                                    </td>
                                </tr>
                            @empty
                                <tr id="sinRegistrosPresentacionesProducto">
                                    <td colspan="10">
                                        <div class="producto-empty">
                                            Todavía no agregó presentaciones y registros.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
</div>
