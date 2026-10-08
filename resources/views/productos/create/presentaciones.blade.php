{{--
    ============================================================================
    PASO 3 - REGISTRO Y PRESENTACIÓN
    ============================================================================

--}}

<div class="producto-step" data-producto-step="2">

    <section class="producto-section">
        <div class="producto-registro-presentacion">
            <div class="producto-rp-layout">
                <div class="producto-rp-formularios">

                    {{--
                        ============================================================================
                        1. DATOS DEL REGISTRO
                        ============================================================================
                        Campos ingresados o seleccionados por el usuario.
                    --}}

                    <section class="producto-rp-card" aria-labelledby="tituloDatosRegistro">
                        <div class="producto-rp-card-head">
                            <span class="producto-rp-card-number">1</span>
                            <div>
                                <h3 id="tituloDatosRegistro">Datos del registro</h3>
                                <p>Información de autorización, lote y vigencia.</p>
                            </div>
                        </div>
                        <div class="producto-registro-grid producto-registro-grid-autorizacion">
                            {{-- CÓDIGO DE AUTORIZACIÓN: dato ingresado por el usuario. --}}
                            <div class="producto-campo-codigo">
                                <label class="producto-field-label" for="form_registro_codigo_autorizacion">
                                    Código de autorización
                                </label>
                                <input class="producto-input" id="form_registro_codigo_autorizacion" type="text"
                                    placeholder="Ej: INSO-RP-001">
                            </div>
                            {{-- NÚMERO DE LOTE: dato ingresado por el usuario. --}}
                            <div class="producto-campo-lote">
                                <label class="producto-field-label" for="form_registro_numero_lote">Nro. de lote</label>
                                <input class="producto-input" id="form_registro_numero_lote" type="text"
                                    maxlength="150" placeholder="Ej: LOTE-2026-001">
                            </div>
                            {{-- FECHA DE VIGENCIA: fecha ingresada por el usuario. --}}
                            <div class="producto-campo-fecha">
                                <label class="producto-field-label" for="form_registro_fecha_vigencia">
                                    Fecha de vigencia
                                </label>
                                <input class="producto-input" id="form_registro_fecha_vigencia" type="date">
                            </div>
                            {{-- CANTIDAD: valor numérico ingresado por el usuario. --}}
                            <div class="producto-campo-cantidad">
                                <label class="producto-field-label" for="form_registro_cantidad">Cantidad total</label>
                                <input class="producto-input" id="form_registro_cantidad" type="number" min="0"
                                    step="1" placeholder="Ej: 120">
                            </div>

                            {{-- UNIDAD DEL REGISTRO
                                Las unidades existentes llegan desde el controlador.
                            También puede agregarse una unidad temporal desde el modal. --}}
                            <div class="producto-campo-unidad">
                                <div class="producto-field-label-row">
                                    <label class="producto-field-label" for="form_registro_unidad">Unidad</label>
                                    <button type="button" class="producto-field-link"
                                        onclick="abrirModalUnidadProducto('form_registro_unidad')">
                                        + Nueva unidad
                                    </button>
                                </div>
                                <select class="producto-select producto-select-search" id="form_registro_unidad"
                                    data-producto-buscador="1" data-producto-placeholder="Buscar unidad">
                                    <option value="">Seleccione unidad</option>
                                    @if (str_starts_with((string) old('form_registro_unidad_temporal_id'), 'TEMP-') &&
                                            old('form_registro_unidad_temporal_nombre'))
                                        <option value="{{ old('form_registro_unidad_temporal_id') }}" data-temporal="1"
                                            data-nombre="{{ old('form_registro_unidad_temporal_nombre') }}"
                                            data-abreviatura="{{ old('form_registro_unidad_temporal_abreviatura') }}"
                                            data-texto="{{ trim(old('form_registro_unidad_temporal_nombre') . (old('form_registro_unidad_temporal_abreviatura') ? ' (' . old('form_registro_unidad_temporal_abreviatura') . ')' : '')) }}">
                                            {{ old('form_registro_unidad_temporal_nombre') }}{{ old('form_registro_unidad_temporal_abreviatura') ? ' (' . old('form_registro_unidad_temporal_abreviatura') . ')' : '' }}
                                        </option>
                                    @endif

                                    {{-- Unidades existentes recibidas desde el controlador. --}}
                                    @foreach ($catalogosUnidades as $catalogoUnidad)
                                        <option value="{{ $catalogoUnidad->id }}"
                                            data-nombre="{{ $catalogoUnidad->nombre }}"
                                            data-abreviatura="{{ $catalogoUnidad->abreviatura }}"
                                            data-texto="{{ trim($catalogoUnidad->nombre . ($catalogoUnidad->abreviatura ? ' (' . $catalogoUnidad->abreviatura . ')' : '')) }}">
                                            {{ $catalogoUnidad->nombre }}{{ $catalogoUnidad->abreviatura ? ' (' . $catalogoUnidad->abreviatura . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- ESTADO DEL REGISTRO
                            El valor seleccionado se enviará dentro de registros[indice][estado]. --}}
                            <div class="producto-campo-estado">
                                <label class="producto-field-label" for="form_registro_estado">Estado</label>
                                <select class="producto-select" id="form_registro_estado">
                                    <option value="ACTIVO">Activo</option>
                                    <option value="INACTIVO">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    {{--
                        ============================================================================
                        2. DATOS DE LA PRESENTACIÓN
                        ============================================================================
                        El usuario puede:
                        - seleccionar una presentación ya registrada; o
                        - crear una nueva presentación.
                        Si selecciona una existente, JavaScript usa los atributos data-* de la opción
                        para completar los campos form_presentacion_*.
                    --}}

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
                                        Presentación del producto
                                    </label>
                                    <button type="button" class="producto-field-link"
                                        onclick="prepararNuevaPresentacionProducto()">
                                        Crear presentación
                                    </button>
                                </div>

                                {{-- CATÁLOGO DE PRESENTACIONES
                                    $presentacionesCatalogo llega desde el controlador.
                                Los atributos data-* permiten autocompletar los campos sin otra consulta al servidor. --}}
                                <select class="producto-select" id="form_presentacion_catalogo">
                                    <option value="">Seleccione una presentación registrada o cree otra</option>
                                    @foreach ($presentacionesCatalogo as $presentacionCatalogo)
                                        @php
                                            $productoCatalogo = $presentacionCatalogo->producto;
                                            // Unidad relacionada con la presentación.
                                            $unidadCatalogo = $presentacionCatalogo->catalogoUnidad;
                                            // Texto que verá el usuario. Ejemplo: "Mililitro (ml)".
                                            $textoUnidadCatalogo = $unidadCatalogo
                                                ? trim(
                                                    $unidadCatalogo->nombre .
                                                        ($unidadCatalogo->abreviatura
                                                            ? ' (' . $unidadCatalogo->abreviatura . ')'
                                                            : ''),
                                                )
                                                : 'Sin unidad';
                                            // Construye el texto principal de la presentación. Ejemplo: "500 Mililitro (ml)".
                                            $textoPresentacionCatalogo = trim(
                                                ($presentacionCatalogo->cantidad ?? '') . ' ' . $textoUnidadCatalogo,
                                            );
                                            $textoPresentacionCatalogo =
                                                $textoPresentacionCatalogo ?:
                                                'Presentación #' . $presentacionCatalogo->id;
                                            $textoPresentacionCatalogo .= $presentacionCatalogo->descripcion
                                                ? ' - ' . $presentacionCatalogo->descripcion
                                                : '';
                                            // Genera la URL para mostrar una etiqueta ya registrada, si existe.
                                            $urlEtiquetaCatalogo = $presentacionCatalogo->url_etiqueta
                                                ? route('productos_presentaciones_etiqueta', $presentacionCatalogo)
                                                : '';
                                        @endphp
                                        <option value="{{ $presentacionCatalogo->id }}"
                                            data-importador-id="{{ $productoCatalogo?->id_importador_persona }}"
                                            data-producto-codigo="{{ $productoCatalogo?->codigo }}"
                                            data-producto-nombre="{{ $productoCatalogo?->nombre_comercial }}"
                                            data-tipo-envase="{{ $presentacionCatalogo->tipo_envase }}"
                                            data-cantidad="{{ $presentacionCatalogo->cantidad }}"
                                            data-unidad-id="{{ $presentacionCatalogo->id_catalogo_unidad }}"
                                            data-unidad-texto="{{ $textoUnidadCatalogo }}"
                                            data-descripcion="{{ $presentacionCatalogo->descripcion }}"
                                            data-estado="{{ $presentacionCatalogo->estado }}"
                                            data-etiqueta-url="{{ $urlEtiquetaCatalogo }}"
                                            data-etiqueta-nombre="{{ $presentacionCatalogo->url_etiqueta ? basename($presentacionCatalogo->url_etiqueta) : 'Sin archivo' }}">
                                            {{ $textoPresentacionCatalogo }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- Guarda el id de la presentación existente que se está reutilizando.
                                Vacío significa que se está creando una presentación nueva. --}}
                                <input type="hidden" id="form_presentacion_origen_id" value="">
                            </div>

                            {{-- TIPO DE ENVASE
                                $tiposEnvases llega desde el controlador.
                            El usuario también puede agregar una opción temporal desde el modal. --}}
                            <div class="producto-campo-tipo-envase">
                                <div class="producto-field-label-row">
                                    <label class="producto-field-label" for="form_presentacion_tipo_envase">
                                        Tipo de presentación o envase
                                    </label>
                                    <button type="button" class="producto-field-link"
                                        onclick="abrirModalTipoEnvaseProducto()">
                                        + Nuevo tipo de envase
                                    </button>
                                </div>
                                <select class="producto-select producto-select-search"
                                    id="form_presentacion_tipo_envase" data-producto-buscador="1"
                                    data-producto-placeholder="Buscar tipo de envase">
                                    <option value="">Seleccione tipo de envase</option>

                                    {{-- Tipos de envase recibidos desde el controlador. --}}
                                    @foreach ($tiposEnvases ?? [] as $tipoEnvase)
                                        <option value="{{ $tipoEnvase }}">
                                            {{ $tipoEnvase }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="producto-campo-cantidad">
                                <label class="producto-field-label" for="form_presentacion_cantidad">Cantidad</label>
                                <input class="producto-input" id="form_presentacion_cantidad" type="number"
                                    min="0" step="1" placeholder="Ej: 1">
                            </div>

                            {{-- UNIDAD DE LA PRESENTACIÓN
                            Puede seleccionarse una unidad existente o crear una temporal desde el modal. --}}
                            <div class="producto-campo-unidad">
                                <div class="producto-field-label-row">
                                    <label class="producto-field-label" for="form_presentacion_unidad">Unidad</label>
                                    <button type="button" class="producto-field-link"
                                        onclick="abrirModalUnidadProducto('form_presentacion_unidad')">
                                        + Nueva unidad
                                    </button>
                                </div>
                                <select class="producto-select producto-select-search" id="form_presentacion_unidad"
                                    data-producto-buscador="1" data-producto-placeholder="Buscar unidad">
                                    <option value="">Seleccione unidad</option>
                                    @if (str_starts_with((string) old('form_presentacion_unidad_temporal_id'), 'TEMP-') &&
                                            old('form_presentacion_unidad_temporal_nombre'))
                                        <option value="{{ old('form_presentacion_unidad_temporal_id') }}"
                                            data-temporal="1"
                                            data-nombre="{{ old('form_presentacion_unidad_temporal_nombre') }}"
                                            data-abreviatura="{{ old('form_presentacion_unidad_temporal_abreviatura') }}"
                                            data-texto="{{ trim(old('form_presentacion_unidad_temporal_nombre') . (old('form_presentacion_unidad_temporal_abreviatura') ? ' (' . old('form_presentacion_unidad_temporal_abreviatura') . ')' : '')) }}">
                                            {{ old('form_presentacion_unidad_temporal_nombre') }}{{ old('form_presentacion_unidad_temporal_abreviatura') ? ' (' . old('form_presentacion_unidad_temporal_abreviatura') . ')' : '' }}
                                        </option>
                                    @endif
                                    @foreach ($catalogosUnidades as $catalogoUnidad)
                                        <option value="{{ $catalogoUnidad->id }}"
                                            data-nombre="{{ $catalogoUnidad->nombre }}"
                                            data-abreviatura="{{ $catalogoUnidad->abreviatura }}"
                                            data-texto="{{ trim($catalogoUnidad->nombre . ($catalogoUnidad->abreviatura ? ' (' . $catalogoUnidad->abreviatura . ')' : '')) }}">
                                            {{ $catalogoUnidad->nombre }}{{ $catalogoUnidad->abreviatura ? ' (' . $catalogoUnidad->abreviatura . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{--
                                ESTADO DE LA PRESENTACIÓN DESHABILITADO EN LA INTERFAZ.
                                Si JavaScript sigue usando form_presentacion_estado, debe existir un valor
                                alternativo (por ejemplo ACTIVO) para evitar errores.
                                <div class="producto-campo-estado">*
                                *&#xA0;                               <label class="producto-field-label" for="form_presentacion_estado">Estado</label>*
                                *&#xA0;                               <select class="producto-select" id="form_presentacion_estado">*
                                *&#xA0;                                   <option value="ACTIVO">Activo</option>*
                                *&#xA0;                                   <option value="INACTIVO">Inactivo</option>*
                                *&#xA0;                               </select>*
                            *&#xA0;                           </div> --}}
                            {{-- DESCRIPCIÓN DE LA PRESENTACIÓN: dato opcional ingresado por el usuario. --}}
                            <div class="producto-campo-descripcion">
                                <label class="producto-field-label"
                                    for="form_presentacion_descripcion">Descripción</label>
                                <textarea class="producto-textarea producto-textarea-compact" id="form_presentacion_descripcion" maxlength="250"
                                    placeholder="Ej: Envase plástico con tapa de seguridad."></textarea>
                            </div>

                            {{-- ACCIÓN PRINCIPAL DEL PASO
                                        Toma los campos form_registro_* y form_presentacion_* y crea las filas
                                    dinámicas que serán enviadas al controlador. --}}
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

                {{--
                            ============================================================================
                            3. ETIQUETA DE LA PRESENTACIÓN
                            ============================================================================
                            El usuario selecciona una imagen local.
                            JavaScript genera una vista previa en el navegador.
                            El archivo no se guarda todavía.
                            Se enviará al controlador cuando se guarde el producto completo.
                        --}}

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
                            class="producto-etiqueta-preview is-empty" onclick="abrirModalEtiquetaProducto()"
                            disabled>
                            <img id="presentacionEtiquetaImagen" alt="Vista previa de la etiqueta" hidden>
                            <span id="presentacionEtiquetaPlaceholder">
                                <i class="fa-regular fa-image"></i>
                                <strong>Sin imagen seleccionada</strong>
                                <small>Seleccione una etiqueta para verla aquí.</small>
                            </span>
                        </button>
                        <div class="producto-etiqueta-sidebar">
                            <div id="presentacionEtiquetaWrapper" class="producto-etiqueta-controls">

                                {{-- Archivo seleccionado por el usuario para la presentación que está preparando. --}}
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
                                    class="producto-upload-button is-view" onclick="abrirModalEtiquetaProducto()"
                                    disabled>
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

            {{--
                        ============================================================================
                        4. PRESENTACIONES Y REGISTROS AGREGADOS
                        ============================================================================
                        Esta tabla muestra los datos que el usuario ya agregó en este paso.
                        Cada fila contiene inputs hidden con registros[indice][...].
                        Las presentaciones se conservan mediante presentaciones[indice][...].
                        Esos inputs, y no los campos form_* de captura, son los que finalmente
                        recibe el controlador al guardar el producto.
                    --}}
            <section class="producto-rp-card producto-rp-listado" aria-labelledby="tituloListadoPresentaciones">
                <div class="producto-rp-listado-head">
                    <div>
                        <h3 id="tituloListadoPresentaciones">Presentaciones y registros agregados</h3>
                        <p><span id="contadorRegistrosPresentacionesProducto">{{ count($registrosOld) }}</span>
                            registro(s) en esta solicitud.</p>
                    </div>

                    {{-- Herramientas visuales: solo filtran las filas mostradas.
                                No cambian ni eliminan datos que se enviarán al controlador. --}}
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

                {{--
                                CONTENEDOR DE PRESENTACIONES DINÁMICAS
                                JavaScript agrega aquí inputs con nombres presentaciones[indice][campo].
                                El controlador recibe esos inputs al guardar el formulario.
                            --}}

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
                                    // Relaciona este registro con su presentación.
                                    $indicePresentacion = $registroOld['id_presentacion_temporal'] ?? $indice;
                                    // Datos de la presentación asociada a este registro.
                                    $presentacionOld = $presentacionesOld[$indicePresentacion] ?? [];
                                    $textoUnidadPresentacion = $nombreCatalogoUnidad(
                                        $presentacionOld['id_catalogo_unidad'] ?? null,
                                    );
                                    $textoUnidadRegistro = $nombreCatalogoUnidad(
                                        $registroOld['id_catalogo_unidad'] ?? null,
                                    );
                                    $presentacionValor = trim(
                                        ($presentacionOld['cantidad'] ?? '') . ' ' . $textoUnidadPresentacion,
                                    );
                                    $presentacionValor =
                                        $presentacionValor ?:
                                        $registroOld['presentacion_texto'] ??
                                            'Presentación #' . ((int) $indicePresentacion + 1);
                                    $presentacionDescripcion = $presentacionOld['descripcion'] ?? '';
                                    $presentacionTexto = trim(
                                        $presentacionValor .
                                            ($presentacionDescripcion ? ' - ' . $presentacionDescripcion : ''),
                                    );
                                    // Si la presentación reutiliza una ya registrada, obtiene ese registro original.
                                    $presentacionOrigenOld = !empty($presentacionOld['id_presentacion_origen'])
                                        ? $presentacionesCatalogo->firstWhere(
                                            'id',
                                            $presentacionOld['id_presentacion_origen'],
                                        )
                                        : null;
                                    // URL de la etiqueta ya guardada para poder mostrarla nuevamente.
                                    $etiquetaOldUrl = $presentacionOrigenOld?->url_etiqueta
                                        ? route('productos_presentaciones_etiqueta', $presentacionOrigenOld)
                                        : '';
                                    // Estado recuperado; ACTIVO se usa como valor por defecto.
                                    $estadoRegistroOld = $registroOld['estado'] ?? 'ACTIVO';
                                @endphp
                                <tr data-tipo-fila="registro" data-presentacion-indice="{{ $indicePresentacion }}"
                                    data-estado-registro="{{ $estadoRegistroOld }}">
                                    <td data-label="Registro">{{ $loop->iteration }}</td>
                                    <td data-label="Código">
                                        <strong>{{ $registroOld['codigo_autorizacion'] ?? 'Sin código' }}</strong>
                                    </td>
                                    <td data-label="Lote">{{ $registroOld['numero_lote'] ?? 'Sin lote' }}</td>
                                    <td data-label="Vigencia">{{ $registroOld['fecha_vigencia'] ?? 'Sin fecha' }}</td>
                                    <td data-label="Cantidad">{{ $registroOld['cantidad'] ?? '-' }}</td>
                                    <td data-label="Unidad">{{ $textoUnidadRegistro }}</td>
                                    <td data-label="Presentación">
                                        <div class="producto-table-presentacion">
                                            <strong>{{ $presentacionValor }}</strong>
                                            @if ($presentacionDescripcion)
                                                <small
                                                    class="producto-table-descripcion">{{ $presentacionDescripcion }}</small>
                                            @endif
                                        </div>
                                        <input type="hidden"
                                            name="presentaciones[{{ $indicePresentacion }}][cantidad]"
                                            value="{{ $presentacionOld['cantidad'] ?? '' }}">
                                        <input type="hidden"
                                            name="presentaciones[{{ $indicePresentacion }}][id_catalogo_unidad]"
                                            value="{{ $presentacionOld['id_catalogo_unidad'] ?? '' }}">
                                        <input type="hidden"
                                            name="presentaciones[{{ $indicePresentacion }}][descripcion]"
                                            value="{{ $presentacionOld['descripcion'] ?? '' }}">
                                        <input type="hidden"
                                            name="presentaciones[{{ $indicePresentacion }}][estado]"
                                            value="{{ $presentacionOld['estado'] ?? 'ACTIVO' }}">
                                        <input type="hidden"
                                            name="presentaciones[{{ $indicePresentacion }}][id_presentacion_origen]"
                                            value="{{ $presentacionOld['id_presentacion_origen'] ?? '' }}">
                                    </td>
                                    <td class="producto-table-etiqueta-cell" data-label="Etiqueta">
                                        <input class="producto-upload-input"
                                            id="form_presentacion_etiqueta_old_{{ $indicePresentacion }}"
                                            type="file"
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
                                        <span
                                            class="producto-table-status {{ $estadoRegistroOld === 'ACTIVO' ? '' : 'is-inactive' }}">
                                            {{ $estadoRegistroOld }}
                                        </span>
                                    </td>
                                    <td data-label="Acciones">
                                        <div class="producto-row-actions">
                                            <button type="button" class="producto-action-icon is-view"
                                                title="Ver etiqueta" aria-label="Ver etiqueta"
                                                onclick="abrirModalEtiquetaOldProducto('form_presentacion_etiqueta_old_{{ $indicePresentacion }}')"
                                                @disabled(!$etiquetaOldUrl)>
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <button type="button" class="producto-action-icon is-edit"
                                                title="Editar presentación y registro"
                                                aria-label="Editar presentación y registro"
                                                onclick="editarFilaRegistroPresentacionProducto(this)">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="producto-action-icon is-delete"
                                                title="{{ !empty($registroOld['bloqueado_eliminacion']) ? 'Este registro ya está relacionado con un certificado' : 'Eliminar presentación y registro' }}"
                                                aria-label="Eliminar presentación y registro"
                                                onclick="quitarFilaProducto(this)" @disabled(!empty($registroOld['bloqueado_eliminacion']))>
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>

                                        {{-- DATOS DEL REGISTRO QUE SE ENVIARÁN AL CONTROLADOR --}}
                                        <input type="hidden" name="registros[{{ $indice }}][id_registro]"
                                            value="{{ $registroOld['id_registro'] ?? '' }}">
                                        <input type="hidden"
                                            name="registros[{{ $indice }}][codigo_autorizacion]"
                                            value="{{ $registroOld['codigo_autorizacion'] ?? '' }}">
                                        <input type="hidden" name="registros[{{ $indice }}][numero_lote]"
                                            value="{{ $registroOld['numero_lote'] ?? '' }}">
                                        <input type="hidden" name="registros[{{ $indice }}][fecha_vigencia]"
                                            value="{{ $registroOld['fecha_vigencia'] ?? '' }}">
                                        <input type="hidden" name="registros[{{ $indice }}][cantidad]"
                                            value="{{ $registroOld['cantidad'] ?? '' }}">
                                        <input type="hidden"
                                            name="registros[{{ $indice }}][id_catalogo_unidad]"
                                            value="{{ $registroOld['id_catalogo_unidad'] ?? '' }}">
                                        <input type="hidden"
                                            name="registros[{{ $indice }}][id_presentacion_temporal]"
                                            value="{{ $indicePresentacion }}">
                                        <input type="hidden"
                                            name="registros[{{ $indice }}][presentacion_texto]"
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
