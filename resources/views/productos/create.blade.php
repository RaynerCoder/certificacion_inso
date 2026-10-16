@php
    /*
     * ================================================================
     * DATOS DE CONTEXTO DE LA VISTA
     * ================================================================
     *
     * $productoEnEdicion:
     *   Contiene el producto enviado por el controlador cuando se está editando.
     *   Si se registra un producto nuevo, su valor será null.
     *
     * $modoEdicionProducto:
     *   Permite usar la misma vista para registrar y editar.
     */
    $productoEnEdicion = $producto ?? null;
    $modoEdicionProducto = filled($productoEnEdicion);

    /*
     * DATOS DEL FLUJO DE RETORNO
     *
     * Estos valores indican desde qué pantalla se abrió el formulario
     * y a qué pantalla debe volver el usuario después de guardar o cancelar.
     */
    $productoCertificadoOrigen = old('form_id_certificado', request('form_id_certificado'));
    $productoBandejaOrigen = old('form_bandeja', request('bandeja', 'recibidas'));
    $productoRetornoSolicitado = old('form_retorno', request('return_to'));
    $productoRetornoSeguro = $modoEdicionProducto
        ? route('productos_show', $productoEnEdicion)
        : route('productos_index');

    if (
        filled($productoRetornoSolicitado) &&
        \Illuminate\Support\Str::startsWith($productoRetornoSolicitado, [url('/'), '/'])
    ) {
        $productoRetornoSeguro = $productoRetornoSolicitado;
    }
@endphp

<x-admin-layout title="Productos | Certificador" :breadcrumbs="[
        [
            'name' => 'Menu',
            'href' => route('admin_dashboard'),
        ],
        [
            'name' => 'Productos',
            'href' => route('productos_index'),
        ],
        [
            'name' => $modoEdicionProducto ? 'Editar Producto' : 'Registrar Producto',
        ],
    ]">

@php
    /*
     * ================================================================
     * DATOS RECIBIDOS DESDE EL CONTROLADOR
     * ================================================================
     *
     * El controlador consulta estos catálogos y los envía a la vista.
     * Aquí NO se vuelve a consultar la base de datos.
     *
     * Las variables con sufijo "Catalogo" se usan únicamente para
     * mostrar opciones en selects, buscadores y tablas del formulario.
     */
    $clasificacionesToxicologicasCatalogo = $clasificacionesToxicologicas;
    $fabricantesCatalogo = $fabricantes;
    $territoriosCatalogo = $territorios;
    $importadoresCatalogo = $importadores;
    $ingredientesCatalogo = $ingredientes;
    $clasificacionesQuimicasCatalogo = $clasificacionesQuimicas;

    // Del catálogo general de medidas se conservan solo las unidades de medida.
    $catalogosUnidades = $catalogosMedidas->where('tipo', 'unidad de medida')->values();

    $nombreCatalogoUnidad = function ($idCatalogoUnidad) use ($catalogosMedidas) {
        $catalogo = $catalogosMedidas->firstWhere('id', (int) $idCatalogoUnidad);

        if (!$catalogo) {
            return 'Sin unidad';
        }

        return trim($catalogo->nombre . ($catalogo->abreviatura ? ' (' . $catalogo->abreviatura . ')' : ''));
    };

    /*
     * Devuelve el nombre visible de una persona.
     * Una persona puede ser natural o empresa; por eso se revisan ambas relaciones.
     */
    $nombrePersonaProducto = function ($persona) {
        if ($persona?->empresa) {
            return $persona->empresa->razon_social;
        }

        $nombreNatural = trim(
            implode(
                ' ',
                array_filter([
                    $persona?->natural?->nombres,
                    $persona?->natural?->apellido_paterno,
                    $persona?->natural?->apellido_materno,
                ]),
            ),
        );

        return $nombreNatural ?: 'Persona #' . $persona?->id;
    };

    /*
     * ================================================================
     * DATOS RECUPERADOS DESPUÉS DE UNA VALIDACIÓN
     * ================================================================
     *
     * old(...) recupera lo que el usuario había escrito cuando Laravel
     * devuelve el formulario por un error de validación.
     *
     * Si no existen datos old(), se utilizan los datos iniciales enviados
     * por el controlador (por ejemplo, al editar un producto).
     */
    $claseErrorProducto = fn(string $campo) => $errors->has($campo) ? ' is-invalid' : '';
    $ingredientesOld = old('ingredientes_productos', $ingredientesIniciales ?? []);
    $presentacionesOld = old('presentaciones', $presentacionesIniciales ?? []);
    $registrosOld = old('registros', $registrosIniciales ?? []);

    // Después de un error pueden quedar índices intermedios vacíos; se continúa desde el índice mayor para no reemplazar filas.
    $siguienteIndiceProducto = function (array $filas): int {
        if ($filas === []) {
            return 0;
        }

        return max(array_map('intval', array_keys($filas))) + 1;
    };

    $siguienteIngrediente = $siguienteIndiceProducto($ingredientesOld);
    $siguientePresentacion = $siguienteIndiceProducto($presentacionesOld);
    $siguienteRegistro = $siguienteIndiceProducto($registrosOld);

    // Mantiene los mismos nombres de campos en registro y edición.
    $valorProducto = function (string $campo, $predeterminado = null) use ($productoEnEdicion) {
        $valores = [
            'form_id_importador_persona' => $productoEnEdicion?->id_importador_persona,
            'form_id_fabricante' => $productoEnEdicion?->id_fabricante,
            'form_estado' => $productoEnEdicion?->estado,
            'form_codigo' => $productoEnEdicion?->codigo,
            'form_nombre_comercial' => $productoEnEdicion?->nombre_comercial,
            'form_id_territorio_pais' => $productoEnEdicion?->id_territorio_pais,
            'form_tipo_producto' => $productoEnEdicion?->tipo_producto,
            'form_nombre_tecnico' => $productoEnEdicion?->nombre_tecnico,
            'form_id_clasificacion_quimica' => $productoEnEdicion?->id_clasificacion_quimica,
            'form_id_clasificacion_toxicologica' => $productoEnEdicion?->id_clasificacion_toxicologica,
        ];

        return old($campo, $valores[$campo] ?? $predeterminado);
    };
@endphp

@include('productos.create.estilos')

@if (session('success'))
    <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
        {{ session('success') }}
    </div>
@endif

<form id="formProductoWizard"
    action="{{ $modoEdicionProducto ? route('productos_update', $productoEnEdicion) : route('productos_store') }}"
    method="POST" enctype="multipart/form-data" class="producto-wizard" autocomplete="off">
    @csrf
    @if ($modoEdicionProducto)
        @method('PUT')
    @endif

    {{--
        ================================================================
        DATOS GENERADOS DESDE LOS MODALES
        ================================================================

        Estos campos ocultos NO vienen del controlador.

        Se llenan en JavaScript cuando el usuario crea temporalmente:
        - un fabricante,
        - una clasificación toxicológica,
        - una clasificación química,
        - o una unidad de medida.

        Al enviar el formulario, el controlador recibe estos valores y
        decide si corresponde registrar el nuevo dato en la base de datos.
    --}}
    <input type="hidden" id="form_fabricante_temporal_nombre" name="form_fabricante_temporal_nombre"
        value="{{ old('form_fabricante_temporal_nombre') }}">
    <input type="hidden" id="form_fabricante_temporal_razon_social" name="form_fabricante_temporal_razon_social"
        value="{{ old('form_fabricante_temporal_razon_social') }}">
    <input type="hidden" id="form_fabricante_temporal_descripcion" name="form_fabricante_temporal_descripcion"
        value="{{ old('form_fabricante_temporal_descripcion') }}">
    <input type="hidden" id="form_clasificacion_toxicologica_temporal_codigo"
        name="form_clasificacion_toxicologica_temporal_codigo"
        value="{{ old('form_clasificacion_toxicologica_temporal_codigo') }}">
    <input type="hidden" id="form_clasificacion_toxicologica_temporal_descripcion"
        name="form_clasificacion_toxicologica_temporal_descripcion"
        value="{{ old('form_clasificacion_toxicologica_temporal_descripcion') }}">
    <input type="hidden" id="form_clasificacion_quimica_temporal_nombre"
        name="form_clasificacion_quimica_temporal_nombre"
        value="{{ old('form_clasificacion_quimica_temporal_nombre') }}">
    <input type="hidden" id="form_clasificacion_quimica_temporal_descripcion"
        name="form_clasificacion_quimica_temporal_descripcion"
        value="{{ old('form_clasificacion_quimica_temporal_descripcion') }}">

    {{-- Datos de una nueva unidad creada desde el modal para la PRESENTACIÓN. --}}
    <input type="hidden" id="form_presentacion_unidad_temporal_id" name="form_presentacion_unidad_temporal_id"
        value="{{ old('form_presentacion_unidad_temporal_id') }}">

    <input type="hidden" id="form_presentacion_unidad_temporal_nombre" name="form_presentacion_unidad_temporal_nombre"
        value="{{ old('form_presentacion_unidad_temporal_nombre') }}">

    <input type="hidden" id="form_presentacion_unidad_temporal_abreviatura"
        name="form_presentacion_unidad_temporal_abreviatura"
        value="{{ old('form_presentacion_unidad_temporal_abreviatura') }}">

    {{-- Datos de una nueva unidad creada desde el modal para el REGISTRO. --}}
    <input type="hidden" id="form_registro_unidad_temporal_id" name="form_registro_unidad_temporal_id"
        value="{{ old('form_registro_unidad_temporal_id') }}">

    <input type="hidden" id="form_registro_unidad_temporal_nombre" name="form_registro_unidad_temporal_nombre"
        value="{{ old('form_registro_unidad_temporal_nombre') }}">

    <input type="hidden" id="form_registro_unidad_temporal_abreviatura"
        name="form_registro_unidad_temporal_abreviatura"
        value="{{ old('form_registro_unidad_temporal_abreviatura') }}">

    {{--
        DATOS DE ORIGEN DEL FLUJO
        Se envían al controlador para conservar la relación con el trámite
        y saber a qué pantalla debe regresar el usuario después de guardar.
    --}}
    <input type="hidden" name="form_id_certificado" value="{{ $productoCertificadoOrigen }}">
    <input type="hidden" name="form_bandeja" value="{{ $productoBandejaOrigen }}">
    <input type="hidden" name="form_retorno" value="{{ $productoRetornoSeguro }}">

    @if ($tramiteOrigen ?? null)
        <div class="mx-6 mt-5 rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-900">
            <strong>Registro para el trámite {{ $tramiteOrigen->codigo }}.</strong>
            @if ($tramiteOrigen->tipoCertificado?->modo_emision === 'POR_LOTE')
                Cada fila de registro debe tener un número de lote: cada lote podrá emitir su propio certificado.
            @else
                Este tipo emite un único certificado para todo el trámite; los registros se conservarán como respaldo.
            @endif
        </div>
    @endif

    <div class="producto-header">
        <div class="px-6 py-6">
            <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                        {{ $modoEdicionProducto ? 'Editar producto' : 'Registrar nuevo producto' }}
                    </h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        {{ $modoEdicionProducto
                            ? 'Actualice los datos principales y revise sus ingredientes, presentaciones y registros.'
                            : 'Complete el producto, sus ingredientes y presentaciones. Luego puede preparar la autorización.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="producto-stepper-card">
        <div class="producto-stepper" id="productoStepper">
            <button type="button" class="producto-burbuja" data-producto-ir="0">
                <span class="producto-circulo">1</span>
                <span>Producto</span>
            </button>
            <button type="button" class="producto-burbuja" data-producto-ir="1">
                <span class="producto-circulo">2</span>
                <span>Ingredientes</span>
            </button>
            <button type="button" class="producto-burbuja" data-producto-ir="2">
                <span class="producto-circulo">3</span>
                <span>Presentaciones y registros</span>
            </button>
            <button type="button" class="producto-burbuja" data-producto-ir="3">
                <span class="producto-circulo">4</span>
                <span>Revisión</span>
            </button>
        </div>
    </div>

    {{-- Este bloque también recibe errores sin recargar la página para conservar todo lo ingresado. --}}
    <div id="productoResumenErrores"
        class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 {{ $errors->any() ? '' : 'hidden' }}">
        <p class="text-sm font-black">No se pudo guardar. Revise estos datos:</p>
        <ul id="productoListaErrores" class="mt-2 list-disc space-y-1 pl-5 text-sm font-semibold">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>

    <div class="producto-layout">
        <section class="producto-card">
            {{-- Encabezado dinamico: muestra el numero de color, titulo del paso y conserva el indicador Paso X de 5. --}}
            <div id="productoEncabezadoPaso" class="producto-form-head">
                <div class="producto-form-head-left">
                    <div class="producto-form-icon">
                        <span id="productoNumeroPaso">1</span>
                    </div>
                    <div>
                        <h2 id="productoTituloPaso" class="producto-form-title">Datos principales del producto</h2>
                        <p id="productoSubtituloPaso" class="producto-form-subtitle">
                            Identifique el producto, importador, fabricante y tipo.
                        </p>
                    </div>
                </div>

                <span id="productoEtiquetaPaso" class="producto-pill">Paso 1 de 4</span>
            </div>

            <div class="producto-body">
                {{-- Paso 1: datos principales de productos. --}}
                @include('productos.create.producto')

                {{-- Paso 2: tabla pivote ingredientes_productos. --}}
                @include('productos.create.ingredientes')

                {{-- Paso 3: presentaciones del producto. --}}
                @include('productos.create.presentaciones')

                {{-- Los registros se agregan dentro del paso de presentaciones para mantener la relacion. --}}
                {{-- @include('productos.create.registros') --}}

                {{-- Paso 4: resumen antes de guardar producto. --}}
                @include('productos.create.revision')

            </div>

            {{-- Botones inferiores del wizard. --}}
            <div class="producto-action-bar">
                <div></div>

                <div class="producto-action-buttons">
                    {{-- Sale del wizard sin enviar datos al controlador. --}}
                    <a href="{{ $productoRetornoSeguro }}" class="producto-btn producto-btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i>
                        Salir sin guardar
                    </a>

                    <button type="button" id="btnProductoAnterior" class="producto-btn producto-btn-secondary"
                        onclick="cambiarPasoProducto(-1)">
                        <i class="fa-solid fa-chevron-left"></i>
                        Anterior
                    </button>

                    {{-- Guarda el avance del formulario sin cambiar de paso. --}}
                    <button type="button" id="btnProductoGuardarAvance" class="producto-btn producto-btn-light"
                        onclick="guardarAvanceProducto()">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar avance
                    </button>

                    <button type="button" id="btnProductoSiguiente" class="producto-btn producto-btn-primary"
                        onclick="cambiarPasoProducto(1)">
                        Siguiente
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

    </div>
</form>

{{-- Bloquea el formulario durante el guardado para evitar registros enviados varias veces. --}}
<div id="productoGuardandoOverlay"
    class="fixed inset-0 z-[20000] hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]"
    role="dialog" aria-modal="true" aria-labelledby="productoGuardandoTitulo" aria-hidden="true">
    <div class="flex max-w-sm items-center gap-4 rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-2xl">
        <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-teal-50 text-lg text-teal-700">
            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
        </span>
        <div>
            <h3 id="productoGuardandoTitulo" class="text-base font-black text-slate-900">Registrando producto</h3>
            <p class="mt-1 text-sm font-medium text-slate-600">Espere mientras guardamos toda la información.</p>
        </div>
    </div>
</div>

{{-- MODAL: captura temporalmente los datos de un nuevo fabricante.
         Los datos se guardan en campos ocultos y recién se envían al controlador
         cuando se guarda el producto completo. --}}
<div class="producto-modal" id="modalFabricanteProducto">
    <div class="producto-modal-card is-compact">
        <div class="producto-modal-head">
            <div class="producto-modal-title-row">
                <span class="producto-modal-icon">
                    <i class="fa-solid fa-industry"></i>
                </span>
                <div>
                    <h3 class="producto-modal-title">Nuevo fabricante</h3>
                    <p class="producto-modal-subtitle">Agregue un fabricante temporal para seleccionarlo en el
                        producto.</p>
                </div>
            </div>
        </div>

        <div class="producto-modal-body">
            <div>
                <label class="producto-field-label" for="modal_fabricante_razon_social">Razón social</label>
                <input class="producto-input" id="modal_fabricante_razon_social" type="text"
                    placeholder="Ej: Agroquímica Andina S.R.L.">
            </div>

            <div>
                <label class="producto-field-label" for="modal_fabricante_nombre">Nombre del fabricante</label>
                <input class="producto-input" id="modal_fabricante_nombre" type="text"
                    placeholder="Ej: Agroquímica Andina">
            </div>

            <div>
                <label class="producto-field-label" for="modal_fabricante_descripcion">Descripción</label>
                <textarea class="producto-textarea" id="modal_fabricante_descripcion" placeholder="Detalle corto del fabricante"></textarea>
            </div>
        </div>

        <div class="producto-modal-actions">
            <button type="button" class="producto-btn producto-btn-secondary"
                onclick="cerrarModalProducto('modalFabricanteProducto')">Cancelar</button>
            <button type="button" class="producto-btn producto-btn-primary"
                onclick="agregarOpcionTemporalProducto('modalFabricanteProducto', 'form_id_fabricante', 'modal_fabricante_nombre')">
                Agregar a la lista
            </button>
        </div>
    </div>
</div>

{{-- MODAL: captura temporalmente una nueva clasificación toxicológica.
         No guarda directamente en base de datos; prepara los datos para el controlador. --}}
<div class="producto-modal" id="modalClasificacionToxicologica">
    <div class="producto-modal-card is-compact">
        <div class="producto-modal-head">
            <div class="producto-modal-title-row">
                <span class="producto-modal-icon">
                    <i class="fa-solid fa-tags"></i>
                </span>
                <div>
                    <h3 class="producto-modal-title">Nueva clasificación toxicológica</h3>
                    <p class="producto-modal-subtitle">Agregue una opción temporal si no aparece en el catálogo.</p>
                </div>
            </div>
        </div>

        <div class="producto-modal-body">
            <div>
                <label class="producto-field-label" for="modal_clasificacion_toxicologica_codigo">Código</label>
                <input class="producto-input" id="modal_clasificacion_toxicologica_codigo" type="text"
                    placeholder="Ej: CLASE II">
            </div>

            <div>
                <label class="producto-field-label"
                    for="modal_clasificacion_toxicologica_descripcion">Descripción</label>
                <input class="producto-input" id="modal_clasificacion_toxicologica_descripcion" type="text"
                    placeholder="Ej: Moderadamente peligroso">
            </div>
        </div>

        <div class="producto-modal-actions">
            <button type="button" class="producto-btn producto-btn-secondary"
                onclick="cerrarModalProducto('modalClasificacionToxicologica')">Cancelar</button>
            <button type="button" class="producto-btn producto-btn-primary"
                onclick="agregarOpcionTemporalProducto('modalClasificacionToxicologica', 'form_id_clasificacion_toxicologica', 'modal_clasificacion_toxicologica_codigo')">
                Agregar a la lista
            </button>
        </div>
    </div>
</div>

{{-- MODAL: captura temporalmente una nueva clasificación química.
         No guarda directamente en base de datos; prepara los datos para el controlador. --}}
<div class="producto-modal" id="modalClasificacionQuimica">
    <div class="producto-modal-card is-compact">
        <div class="producto-modal-head">
            <div class="producto-modal-title-row">
                <span class="producto-modal-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </span>
                <div>
                    <h3 class="producto-modal-title">Nueva clasificación química</h3>
                    <p class="producto-modal-subtitle">Agregue una clasificación química si no aparece en la lista.</p>
                </div>
            </div>
        </div>

        <div class="producto-modal-body">
            <div>
                <label class="producto-field-label" for="modal_clasificacion_quimica_nombre">Nombre de la
                    clasificación química</label>
                <input class="producto-input" id="modal_clasificacion_quimica_nombre" type="text"
                    placeholder="Ej: Insecticida">
            </div>

            <div>
                <label class="producto-field-label" for="modal_clasificacion_quimica_descripcion">Descripción</label>
                <textarea class="producto-textarea" id="modal_clasificacion_quimica_descripcion"
                    placeholder="Detalle corto de la clasificación química"></textarea>
            </div>
        </div>

        <div class="producto-modal-actions">
            <button type="button" class="producto-btn producto-btn-secondary"
                onclick="cerrarModalProducto('modalClasificacionQuimica')">Cancelar</button>
            <button type="button" class="producto-btn producto-btn-primary"
                onclick="agregarOpcionTemporalProducto('modalClasificacionQuimica', 'form_id_clasificacion_quimica', 'modal_clasificacion_quimica_nombre')">
                Agregar a la lista
            </button>
        </div>
    </div>
</div>

{{-- MODAL: permite crear temporalmente una unidad de medida para presentación o registro.
         La unidad se envía al controlador junto con el producto y se registra al guardar. --}}
<div class="producto-modal" id="modalUnidadProducto">
    <div class="producto-modal-card is-compact">

        <div class="producto-modal-head">
            <div class="producto-modal-title-row">
                <span class="producto-modal-icon">
                    <i class="fa-solid fa-ruler-combined"></i>
                </span>

                <div>
                    <h3 class="producto-modal-title">Nueva unidad</h3>
                    <p class="producto-modal-subtitle">
                        Agregue una unidad de medida si no aparece en la lista.
                    </p>
                </div>
            </div>
        </div>

        <div class="producto-modal-body">

            <div>
                <label class="producto-field-label" for="modal_unidad_nombre">
                    Nombre de la unidad
                </label>

                <input class="producto-input" id="modal_unidad_nombre" type="text" placeholder="Ej: Mililitro">
            </div>

            <div>
                <label class="producto-field-label" for="modal_unidad_abreviatura">
                    Abreviatura
                </label>

                <input class="producto-input" id="modal_unidad_abreviatura" type="text" placeholder="Ej: ml">
            </div>

        </div>

        <div class="producto-modal-actions">

            <button type="button" class="producto-btn producto-btn-secondary"
                onclick="cerrarModalProducto('modalUnidadProducto')">
                Cancelar
            </button>

            <button type="button" class="producto-btn producto-btn-primary"
                onclick="agregarUnidadTemporalProducto()">

                <i class="fa-solid fa-plus"></i>
                Agregar unidad
            </button>

        </div>

    </div>
</div>


{{-- MODAL: permite agregar temporalmente un ingrediente que no existe en el catálogo.
         La opción queda disponible en el formulario y se envía al controlador al guardar. --}}
<div class="producto-modal" id="modalIngredienteProducto">
    <div class="producto-modal-card is-compact">
        <div class="producto-modal-head">
            <div class="producto-modal-title-row">
                <span class="producto-modal-icon">
                    <i class="fa-solid fa-flask"></i>
                </span>
                <div>
                    <h3 class="producto-modal-title">Nuevo ingrediente</h3>
                    <p class="producto-modal-subtitle">Agregue un ingrediente temporal para usarlo en el producto.</p>
                </div>
            </div>
        </div>

        <div class="producto-modal-body">
            <div>
                <label class="producto-field-label" for="modal_ingrediente_nombre">Nombre del ingrediente</label>
                <input class="producto-input" id="modal_ingrediente_nombre" type="text"
                    placeholder="Ej: Glifosato">
            </div>
        </div>

        <div class="producto-modal-actions">
            <button type="button" class="producto-btn producto-btn-secondary"
                onclick="cerrarModalProducto('modalIngredienteProducto')">Cancelar</button>
            <button type="button" class="producto-btn producto-btn-primary"
                onclick="agregarOpcionTemporalProducto('modalIngredienteProducto', 'form_ingrediente_select', 'modal_ingrediente_nombre')">
                Agregar a la lista
            </button>
        </div>
    </div>
</div>


{{-- MODAL: permite escribir un nuevo tipo de envase.
         El valor se agrega al selector de la presentación y se enviará dentro
         de presentaciones[...] cuando se guarde el producto. --}}
<div class="producto-modal" id="modalTipoEnvaseProducto">
    <div class="producto-modal-card is-compact">

        <div class="producto-modal-head">
            <div class="producto-modal-title-row">
                <span class="producto-modal-icon">
                    <i class="fa-solid fa-box-open"></i>
                </span>

                <div>
                    <h3 class="producto-modal-title">Nuevo tipo de envase</h3>
                    <p class="producto-modal-subtitle">
                        Escriba el tipo de envase que utilizará esta presentación.
                    </p>
                </div>
            </div>
        </div>

        <div class="producto-modal-body">
            <div>
                <label class="producto-field-label" for="modal_tipo_envase_nombre">
                    Tipo de envase
                </label>

                <input class="producto-input" id="modal_tipo_envase_nombre" type="text" maxlength="150"
                    placeholder="Ej: Frasco, Bidón, Aerosol o Bolsa">
            </div>
        </div>

        <div class="producto-modal-actions">
            <button type="button" class="producto-btn producto-btn-secondary"
                onclick="cerrarModalProducto('modalTipoEnvaseProducto')">
                Cancelar
            </button>

            <button type="button" class="producto-btn producto-btn-primary"
                onclick="agregarTipoEnvaseTemporalProducto()">
                <i class="fa-solid fa-plus"></i>
                Agregar tipo de envase
            </button>
        </div>
    </div>
</div>


{{-- La etiqueta se revisa en grande sin salir del formulario. --}}
<div class="producto-modal" id="modalEtiquetaImagenProducto" aria-hidden="true">
    <div class="producto-modal-card is-image" role="dialog" aria-modal="true"
        aria-labelledby="tituloModalEtiquetaImagen">
        <div class="producto-modal-head">
            <div class="producto-modal-title-row">
                <span class="producto-modal-icon">
                    <i class="fa-regular fa-image"></i>
                </span>
                <div>
                    <h3 id="tituloModalEtiquetaImagen" class="producto-modal-title">Vista previa de la etiqueta</h3>
                    <p id="etiquetaImagenModalNombre" class="producto-modal-subtitle">Sin imagen seleccionada.</p>
                </div>
            </div>

            <div class="producto-image-modal-actions">
                <div class="producto-image-zoom-tools" role="group" aria-label="Zoom de la etiqueta">
                    <button type="button" onclick="cambiarZoomEtiquetaProducto(-0.25)" title="Alejar imagen"
                        aria-label="Alejar imagen">
                        <i class="fa-solid fa-magnifying-glass-minus"></i>
                    </button>
                    <output id="etiquetaImagenZoomValor" aria-live="polite">100%</output>
                    <button type="button" onclick="cambiarZoomEtiquetaProducto(0.25)" title="Acercar imagen"
                        aria-label="Acercar imagen">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </button>
                    <button type="button" onclick="restablecerZoomEtiquetaProducto()" title="Restablecer tamaño"
                        aria-label="Restablecer tamaño">
                        <i class="fa-solid fa-compress"></i>
                    </button>
                </div>

                <button type="button" class="producto-modal-close" onclick="cerrarModalEtiquetaProducto()"
                    title="Cerrar vista previa" aria-label="Cerrar vista previa">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="producto-image-preview-viewport" id="etiquetaImagenViewport">
            <div class="producto-image-preview-stage" id="etiquetaImagenStage">
                <img id="etiquetaImagenPreview" class="producto-image-preview-modal"
                    alt="Vista previa de la etiqueta" draggable="false">
            </div>
        </div>
    </div>
</div>

<script>
    // MANTIENE EL ESTADO DEL FORMULARIO
    // conserva índices y valores temporales mientras la página permanece abierta.
    let productoPasoActual = 0;

    let productoIngredienteIndice = {{ $siguienteIngrediente }};
    let productoPresentacionIndice = {{ $siguientePresentacion }};
    let productoRegistroIndice = {{ $siguienteRegistro }};

    let productoRegistroEditandoId = null;

    let productoEtiquetaPreviewUrl = null;
    let productoEtiquetaOrigenActual = null;
    let productoEtiquetaZoom = 1;
    let productoEtiquetaTamanoBase = {
        ancho: 0,
        alto: 0
    };
    const productoEtiquetaUrls = {};
    const productoEtiquetaOldUrls = {};
    // ERRORES RECIBIDOS DESDE LARAVEL
    // contiene los mensajes generados por la validación del controlador en una carga anterior.
    const productoErroresLaravel = @json($errors->messages());

    // indica si el formulario está modificando un producto existente.
    const productoModoEdicion = @json($modoEdicionProducto);
    // NAVEGACIÓN DEL FORMULARIO
    // controla los pasos del wizard. No registra ni modifica datos en la base de datos.
    // ========================================================================

    const productoTitulos = [
        ['Datos principales del producto', 'Identifique el producto, importador, fabricante y tipo.'],
        ['Ingredientes', 'Agregue los ingredientes y sus porcentajes.'],
        ['Presentaciones y registros', 'Agregue los formatos comerciales y relacione sus registros.'],
        ['Revisión', ''],
    ];

    const productoColoresPaso = ['is-blue', 'is-teal', 'is-amber', 'is-teal'];

    // MUESTRA UN PASO DEL FORMULARIO
    // muestra el paso indicado y actualiza burbujas, encabezado, botones y resumen.
    // indice: 0=Producto, 1=Ingredientes, 2=Presentaciones y registros, 3=Revisión.
    // Ejemplo: mostrarPasoProducto(2) abre el paso Presentaciones y registros.
    function mostrarPasoProducto(indice) {
        const pasos = document.querySelectorAll('[data-producto-step]');
        const burbujas = document.querySelectorAll('[data-producto-ir]');
        const ultimoPaso = productoTitulos.length - 1;

        // limita el índice para impedir pasos menores a 0 o mayores al último disponible.
        productoPasoActual = Math.max(0, Math.min(indice, ultimoPaso));

        pasos.forEach((paso) => {
            paso.classList.toggle('is-active', Number(paso.dataset.productoStep) === productoPasoActual);
        });

        burbujas.forEach((burbuja, posicion) => {
            burbuja.classList.toggle('is-active', posicion === productoPasoActual);
            burbuja.classList.toggle('is-completed', posicion < productoPasoActual);
        });

        const encabezadoPaso = document.getElementById('productoEncabezadoPaso');

        encabezadoPaso.classList.remove('is-blue', 'is-teal', 'is-amber');
        encabezadoPaso.classList.add(productoColoresPaso[productoPasoActual]);

        document.getElementById('productoNumeroPaso').textContent = productoPasoActual + 1;
        document.getElementById('productoTituloPaso').textContent = productoTitulos[productoPasoActual][0];
        const subtituloPaso = document.getElementById('productoSubtituloPaso');
        subtituloPaso.textContent = productoTitulos[productoPasoActual][1];
        subtituloPaso.hidden = !productoTitulos[productoPasoActual][1];
        document.getElementById('productoEtiquetaPaso').textContent =
            `Paso ${productoPasoActual + 1} de ${pasos.length}`;

        document.getElementById('btnProductoAnterior').classList.toggle('hidden', productoPasoActual === 0);

        const botonSiguiente = document.getElementById('btnProductoSiguiente');
        if (productoPasoActual === ultimoPaso) {
            botonSiguiente.innerHTML = productoModoEdicion ?
                '<i class="fa-solid fa-floppy-disk"></i> Actualizar producto' :
                '<i class="fa-solid fa-floppy-disk"></i> Guardar producto';
        } else if (productoPasoActual === 2) {
            botonSiguiente.innerHTML = 'Continuar a revisión <i class="fa-solid fa-chevron-right"></i>';
        } else {
            botonSiguiente.innerHTML = 'Siguiente <i class="fa-solid fa-chevron-right"></i>';
        }

        actualizarResumenProducto();

    }

    // CAMBIA AL PASO ANTERIOR O SIGUIENTE
    // recibe 1 para avanzar o -1 para retroceder.
    // si el usuario avanza desde el último paso, envía el formulario para guardar.
    // Ejemplo: cambiarPasoProducto(1) avanza; cambiarPasoProducto(-1) retrocede.
    function cambiarPasoProducto(direccion) {
        const ultimoPaso = productoTitulos.length - 1;

        if (direccion > 0 && productoPasoActual === ultimoPaso) {
            document.getElementById('formProductoWizard')?.requestSubmit();
            return;
        }

        // La navegacion queda libre; Laravel validara todo recien al presionar Guardar producto.
        mostrarPasoProducto(productoPasoActual + direccion);
    }


    // ESCAPA TEXTO PARA HTML
    // evita que un texto ingresado por el usuario se interprete como etiquetas HTML.
    function escaparHtmlProducto(valor) {
        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    // NORMALIZA TEXTO PARA BUSCAR Y COMPARAR
    // convierte a minúsculas, elimina espacios al inicio/final y reduce espacios repetidos.
    // Ejemplo: '  Frasco   Plástico ' se compara como 'frasco plástico'.
    function normalizarTextoProducto(valor) {
        // ?? usa cadena vacía cuando valor es null/undefined; luego normaliza espacios.
        return String(valor ?? '').trim().toLowerCase().replace(/\s+/g, ' ');
    }

    // SINCRONIZA UN SELECT CON SU BUSCADOR VISUAL
    // select es el <select> real que contiene el dato enviado al controlador.
    // valorSeleccionado es el valor que se desea seleccionar; null conserva la selección actual.
    function sincronizarBuscadorProducto(select, valorSeleccionado = null) {
        if (!select) {
            return;
        }

        if (valorSeleccionado !== null) {
            select.value = valorSeleccionado;
        }

        actualizarBuscadorCatalogoProducto(select);
    }

    // ASIGNA UNA UNIDAD AL SELECTOR INDICADO
    // idSelect identifica si se modifica la unidad de presentación o la unidad de registro.
    // valor corresponde al id o valor de la unidad que debe quedar seleccionada.
    function establecerUnidadProducto(idSelect, valor = '') {
        const select = document.getElementById(idSelect);

        if (!select) {
            return;
        }

        sincronizarBuscadorProducto(select, String(valor ?? ''));
    }

    // CIERRA LOS BUSCADORES DE CATÁLOGO
    // excepto permite dejar abierto un buscador específico; null cierra todos.
    function cerrarBuscadoresCatalogoProducto(excepto = null) {
        document.querySelectorAll('[data-producto-search-select]').forEach((contenedor) => {
            if (contenedor === excepto) {
                return;
            }

            contenedor.classList.remove('is-open');
            contenedor.querySelector('[data-producto-search-trigger]')?.setAttribute('aria-expanded', 'false');

            const buscador = contenedor.querySelector('[data-producto-search-input]');
            if (buscador) {
                buscador.value = '';
            }
        });
    }

    // MUESTRA LAS OPCIONES QUE COINCIDEN CON LA BÚSQUEDA
    // lee el texto escrito en el buscador y dibuja únicamente las opciones coincidentes.
    // al seleccionar una opción actualiza el <select> real que se enviará al controlador.
    function renderizarOpcionesBuscadorProducto(contenedor) {
        const select = document.getElementById(contenedor?.dataset.productoSearchSelect || '');
        const lista = contenedor?.querySelector('[data-producto-search-options]');
        const buscador = contenedor?.querySelector('[data-producto-search-input]');

        if (!select || !lista) {
            return;
        }

        const consulta = normalizarTextoProducto(buscador?.value || '');
        const opciones = Array.from(select.options).filter((opcion) =>
            opcion.value && normalizarTextoProducto(opcion.textContent).includes(consulta)
        );

        if (!opciones.length) {
            lista.innerHTML = '<div class="producto-ingredient-empty">No se encontraron resultados.</div>';
            return;
        }

        lista.innerHTML = opciones.map((opcion) => {
            const seleccionado = String(select.value) === String(opcion.value);

            const detalle = opcion.dataset.productoDetalle?.trim() || '';

            return `
                <button type="button" class="producto-ingredient-option ${seleccionado ? 'is-selected' : ''}"
                    data-producto-search-option="${escaparHtmlProducto(opcion.value)}" role="option"
                    aria-selected="${seleccionado ? 'true' : 'false'}">
                    <span class="producto-ingredient-option-copy">
                        <strong>${escaparHtmlProducto(opcion.textContent.trim())}</strong>
                        ${detalle ? `<small class="producto-ingredient-option-detail">${escaparHtmlProducto(detalle)}</small>` : ''}
                    </span>
                    ${seleccionado ? '<i class="fa-solid fa-check producto-ingredient-option-check" aria-hidden="true"></i>' : ''}
                </button>`;
        }).join('');

        lista.querySelectorAll('[data-producto-search-option]').forEach((boton) => {
            boton.addEventListener('click', () => {
                select.value = boton.dataset.productoSearchOption || '';
                // dispara manualmente change para ejecutar la misma lógica que una selección hecha por el usuario.
                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                actualizarBuscadorCatalogoProducto(select);
                cerrarBuscadoresCatalogoProducto();
            });
        });
    }

    // ACTUALIZA EL TEXTO VISIBLE DE UN BUSCADOR DE CATÁLOGO
    // refleja en el control visual la opción que actualmente está seleccionada en el <select> real.
    function actualizarBuscadorCatalogoProducto(select) {
        const contenedor = document.querySelector(
            `[data-producto-search-select="${CSS.escape(select?.id || '')}"]`
        );

        if (!select || !contenedor) {
            return;
        }

        const etiqueta = contenedor.querySelector('[data-producto-search-label]');
        const botonLimpiar = contenedor.querySelector('[data-producto-search-clear]');
        const opcion = select.options[select.selectedIndex];
        const textoVacio = select.options[0]?.textContent?.trim() || 'Seleccione una opción';

        if (etiqueta) {
            const nombre = select.value ? opcion?.textContent?.trim() || textoVacio : textoVacio;
            const detalle = select.value ? opcion?.dataset.productoDetalle?.trim() || '' : '';

            etiqueta.innerHTML = detalle ?
                `<strong>${escaparHtmlProducto(nombre)}</strong><small class="producto-search-selected-detail">${escaparHtmlProducto(detalle)}</small>` :
                `<strong>${escaparHtmlProducto(nombre)}</strong>`;
        }

        if (botonLimpiar) {
            botonLimpiar.hidden = !select.value;
        }

        contenedor.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
        renderizarOpcionesBuscadorProducto(contenedor);
    }

    // CREA LOS BUSCADORES VISUALES DE LOS SELECTS
    // transforma cada select marcado con data-producto-buscador en un control buscable.
    // el select original no se elimina porque su valor es el que se envía al controlador.
    function inicializarBuscadoresProducto() {
        document.querySelectorAll('[data-producto-buscador]').forEach((select) => {
            if (document.querySelector(`[data-producto-search-select="${CSS.escape(select.id)}"]`)) {
                return;
            }

            const idDesplegable = `${select.id}_buscador`;
            const placeholder = select.dataset.productoPlaceholder || 'Buscar...';
            const contenedor = document.createElement('div');

            contenedor.className = 'producto-ingredient-select producto-catalog-search';
            contenedor.dataset.productoSearchSelect = select.id;
            contenedor.innerHTML = `
                <button type="button" class="producto-ingredient-trigger" data-producto-search-trigger
                    aria-expanded="false" aria-controls="${escaparHtmlProducto(idDesplegable)}">
                    <span class="producto-ingredient-trigger-text">
                        <span data-producto-search-label></span>
                    </span>
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>
                <button type="button" class="producto-search-clear" data-producto-search-clear hidden
                    title="Quitar selección" aria-label="Quitar selección">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
                <div class="producto-ingredient-dropdown" id="${escaparHtmlProducto(idDesplegable)}">
                    <div class="producto-ingredient-search">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" placeholder="${escaparHtmlProducto(placeholder)}" autocomplete="off"
                            data-producto-search-input aria-label="${escaparHtmlProducto(placeholder)}">
                        <button type="button" class="producto-search-close" data-producto-search-close
                            title="Cerrar selector" aria-label="Cerrar selector">
                            <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="producto-ingredient-options" data-producto-search-options role="listbox"></div>
                </div>`;

            select.insertAdjacentElement('afterend', contenedor);
            select.classList.add('producto-search-native-select');

            const disparador = contenedor.querySelector('[data-producto-search-trigger]');
            const buscador = contenedor.querySelector('[data-producto-search-input]');
            const botonCerrar = contenedor.querySelector('[data-producto-search-close]');
            const botonLimpiar = contenedor.querySelector('[data-producto-search-clear]');

            disparador?.addEventListener('click', () => {
                const abrir = !contenedor.classList.contains('is-open');
                cerrarBuscadoresCatalogoProducto(contenedor);
                cerrarSelectorIngredienteProducto();
                contenedor.classList.toggle('is-open', abrir);
                disparador.setAttribute('aria-expanded', abrir ? 'true' : 'false');

                if (abrir) {
                    buscador.value = '';
                    renderizarOpcionesBuscadorProducto(contenedor);
                    // espera a que el desplegable sea visible antes de colocar el cursor en el buscador.
                    requestAnimationFrame(() => buscador.focus());
                }
            });

            buscador?.addEventListener('input', () => renderizarOpcionesBuscadorProducto(contenedor));
            botonLimpiar?.addEventListener('click', (event) => {
                event.stopPropagation();
                select.value = '';
                // dispara manualmente change para ejecutar la misma lógica que una selección hecha por el usuario.
                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                actualizarBuscadorCatalogoProducto(select);
                cerrarBuscadoresCatalogoProducto();
            });
            botonCerrar?.addEventListener('click', (event) => {
                event.stopPropagation();
                cerrarBuscadoresCatalogoProducto();
                disparador?.focus();
            });
            buscador?.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    cerrarBuscadoresCatalogoProducto();
                    disparador?.focus();
                }
            });
            select.addEventListener('change', () => actualizarBuscadorCatalogoProducto(select));
            actualizarBuscadorCatalogoProducto(select);
        });
    }

    // guarda temporalmente qué selector de unidad abrió el modal.
    // sus valores esperados son form_presentacion_unidad o form_registro_unidad.
    let unidadSelectDestinoProducto = null;
    // ABRE EL MODAL PARA CREAR UNA UNIDAD TEMPORAL
    // idSelectDestino indica qué selector recibirá la nueva unidad.
    // Ejemplo: puede ser form_presentacion_unidad o form_registro_unidad.
    function abrirModalUnidadProducto(idSelectDestino) {

        unidadSelectDestinoProducto = idSelectDestino;

        abrirModalProducto('modalUnidadProducto');
    }
    // AGREGA UNA UNIDAD CREADA DESDE EL MODAL
    // valida nombre y abreviatura, evita duplicados y agrega la unidad al selector correspondiente.
    // también llena campos ocultos para que el controlador pueda registrar la unidad al guardar el producto.
    function agregarUnidadTemporalProducto() {

        const nombreInput =
            document.getElementById('modal_unidad_nombre');

        const abreviaturaInput =
            document.getElementById('modal_unidad_abreviatura');

        const nombre = nombreInput?.value.trim() || '';
        const abreviatura = abreviaturaInput?.value.trim() || '';

        if (!nombre) {
            marcarErrorProducto(
                nombreInput,
                'Ingrese el nombre de la unidad.'
            );
            return;
        }

        if (!unidadSelectDestinoProducto) {
            return;
        }

        const normalizarUnidad = (valor) => String(valor || '').trim().toLocaleUpperCase('es');
        const opcionesUnidad = Array.from(document.querySelectorAll(
            '#form_presentacion_unidad option[value], #form_registro_unidad option[value]'
        )).filter((opcion) => opcion.value);
        const unidadRepetida = opcionesUnidad.some((opcion) =>
            normalizarUnidad(opcion.dataset.nombre) === normalizarUnidad(nombre)
        );

        if (unidadRepetida) {
            marcarErrorProducto(nombreInput, 'Ya existe una unidad con ese nombre. Selecciónela de la lista.');
            return;
        }

        // TEMP-* identifica una opción que todavía no existe en base de datos.
        const valorTemporal = `TEMP-${Date.now()}`;

        const textoVisible = abreviatura ?
            `${nombre} (${abreviatura})` :
            nombre;

        const selectDestino = document.getElementById(unidadSelectDestinoProducto);

        if (!selectDestino) {
            return;
        }

        // La unidad temporal pertenece únicamente al campo que abrió el modal.
        // El otro selector conserva tanto su catálogo como su valor seleccionado.
        selectDestino.querySelectorAll('option[data-temporal="1"]').forEach((opcion) => opcion.remove());

        const opcion = new Option(textoVisible, valorTemporal, false, false);
        opcion.dataset.temporal = '1';
        opcion.dataset.texto = textoVisible;
        opcion.dataset.nombre = nombre;
        opcion.dataset.abreviatura = abreviatura;

        selectDestino.add(opcion);
        sincronizarBuscadorProducto(selectDestino, valorTemporal);

        /*
         * Guardamos los datos temporales
         * solamente para el campo correspondiente.
         */
        const prefijo =
            unidadSelectDestinoProducto === 'form_registro_unidad' ?
            'form_registro_unidad_temporal' :
            'form_presentacion_unidad_temporal';

        const campoId =
            document.getElementById(`${prefijo}_id`);

        const campoNombre =
            document.getElementById(`${prefijo}_nombre`);

        const campoAbreviatura =
            document.getElementById(`${prefijo}_abreviatura`);

        if (campoId) {
            campoId.value = valorTemporal;
        }

        if (campoNombre) {
            campoNombre.value = nombre;
        }

        if (campoAbreviatura) {
            campoAbreviatura.value = abreviatura;
        }

        limpiarErrorProducto(nombreInput);

        cerrarModalProducto('modalUnidadProducto');

        actualizarResumenProducto();

        unidadSelectDestinoProducto = null;
    }


    // ABRE EL MODAL PARA CREAR UN TIPO DE ENVASE
    // solo abre el modal y coloca el cursor en el campo de nombre.
    function abrirModalTipoEnvaseProducto() {
        const selectorTipoEnvase = document.getElementById('form_presentacion_tipo_envase');

        if (!selectorTipoEnvase) {
            return;
        }

        abrirModalProducto('modalTipoEnvaseProducto');

        requestAnimationFrame(() => {
            document.getElementById('modal_tipo_envase_nombre')?.focus();
        });
    }


    // AGREGA EL TIPO DE ENVASE ESCRITO EN EL MODAL
    // valida el texto, evita duplicados y lo agrega al selector de la presentación.
    // el valor se enviará dentro de presentaciones[...] al guardar el producto.
    function agregarTipoEnvaseTemporalProducto() {
        const campoNombre = document.getElementById('modal_tipo_envase_nombre');
        const selectorTipoEnvase = document.getElementById('form_presentacion_tipo_envase');
        const tipoEnvase = campoNombre?.value.trim().replace(/\s+/g, ' ') || '';

        if (!campoNombre || !selectorTipoEnvase) {
            return;
        }

        if (!tipoEnvase) {
            marcarErrorProducto(campoNombre, 'Ingrese el tipo de envase.');
            return;
        }

        const tipoEnvaseYaExiste = Array.from(selectorTipoEnvase.options)
            .filter((opcion) => opcion.value)
            .some((opcion) =>
                normalizarTextoProducto(opcion.value) === normalizarTextoProducto(tipoEnvase)
            );

        if (tipoEnvaseYaExiste) {
            marcarErrorProducto(
                campoNombre,
                'Ese tipo de envase ya existe. Selecciónelo de la lista.'
            );
            return;
        }

        // crea y deja seleccionada una nueva opción usando el mismo texto como value y etiqueta visible.
        const opcionNueva = new Option(tipoEnvase, tipoEnvase, true, true);
        selectorTipoEnvase.add(opcionNueva);

        // Actualiza el buscador visual que acompaña al select.
        sincronizarBuscadorProducto(selectorTipoEnvase, tipoEnvase);

        limpiarErrorProducto(campoNombre);
        cerrarModalProducto('modalTipoEnvaseProducto');
    }
    // OBTIENE EL TEXTO VISIBLE DE UNA UNIDAD
    // recibe el id o valor de una unidad y devuelve el nombre mostrado en sus selectores.
    function textoUnidadCatalogoProducto(idUnidad) {
        if (!idUnidad) {
            return '';
        }

        const opcion =
            document.querySelector(`#form_presentacion_unidad option[value="${CSS.escape(String(idUnidad))}"]`) ||
            document.querySelector(`#form_registro_unidad option[value="${CSS.escape(String(idUnidad))}"]`);

        return opcion?.dataset.texto || opcion?.textContent?.trim() || '';
    }

    // PREPARA EL TEXTO DE UNA UNIDAD PARA LA REVISIÓN
    function textoUnidadRevisionProducto(idUnidad) {
        return textoUnidadCatalogoProducto(idUnidad);
    }

    // MUESTRA UN MENSAJE AL USUARIO
    // recibe icono, título y mensaje; usa SweetAlert y deja alert() como respaldo.
    function notificarProducto(icono, titulo, mensaje) {
        if (window.Swal) {
            Swal.fire({
                icon: icono,
                title: titulo,
                text: mensaje,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#0d9488',
            });
            return;
        }

        alert(`${titulo}: ${mensaje}`);
    }

    // Devuelve el elemento visible que debe marcarse cuando un input oculto tiene un error.
    // OBTIENE EL CONTROL VISIBLE QUE DEBE MOSTRAR UN ERROR
    // algunos selects e inputs reales están ocultos porque tienen una interfaz personalizada.
    // esta función devuelve el elemento visible que debe resaltarse.

    function objetivoErrorProducto(campo) {
        if (!campo) {
            return null;
        }

        if (campo.classList.contains('producto-upload-input')) {
            return campo.closest('.producto-table-etiqueta-cell') || document.getElementById(
                'presentacionEtiquetaPreview');
        }

        if (campo.id === 'form_ingrediente_select') {
            return document.querySelector('[data-ingrediente-combobox]') || campo;
        }

        if (campo.matches('[data-producto-buscador]')) {
            return document.querySelector(
                `[data-producto-search-select="${CSS.escape(campo.id)}"]`
            ) || campo;
        }

        return campo;
    }

    // MARCA UN CAMPO COMO INVÁLIDO
    // recibe el id o elemento del campo y el mensaje que debe mostrarse debajo.
    // devuelve false para poder usarse directamente dentro de una validación.
    function marcarErrorProducto(idCampo, mensaje) {
        // permite llamar la función enviando un id de campo o directamente el elemento HTML.
        const campo = typeof idCampo === 'string' ? document.getElementById(idCampo) : idCampo;
        const objetivo = objetivoErrorProducto(campo);

        if (!objetivo) {
            return false;
        }

        objetivo.classList.add('is-invalid');

        const errorDentroDelControl = objetivo.matches('td, tr');
        let error = errorDentroDelControl ?
            objetivo.querySelector(':scope > .producto-field-error') :
            objetivo.nextElementSibling;

        if (!error || !error.classList.contains('producto-field-error')) {
            error = document.createElement('p');
            error.className = 'producto-field-error';

            if (errorDentroDelControl) {
                objetivo.appendChild(error);
            } else {
                objetivo.insertAdjacentElement('afterend', error);
            }
        }

        error.textContent = mensaje;
        return false;
    }

    // LIMPIA EL ERROR VISUAL DE UN CAMPO
    // elimina la clase de error y el mensaje asociado cuando el usuario corrige el dato.
    function limpiarErrorProducto(idCampo) {
        // permite llamar la función enviando un id de campo o directamente el elemento HTML.
        const campo = typeof idCampo === 'string' ? document.getElementById(idCampo) : idCampo;
        const objetivo = objetivoErrorProducto(campo);

        if (!objetivo) {
            return;
        }

        campo?.classList.remove('is-invalid');
        objetivo.classList.remove('is-invalid');

        const error = objetivo.matches('td, tr') ?
            objetivo.querySelector(':scope > .producto-field-error') :
            objetivo.nextElementSibling;
        if (error?.classList.contains('producto-field-error')) {
            error.remove();
        }
    }

    // VALIDA QUE UN CAMPO OBLIGATORIO TENGA VALOR
    // recibe el id del campo y el mensaje de error.
    // devuelve true si tiene valor y false si está vacío.
    function validarRequeridoProducto(idCampo, mensaje) {
        const campo = document.getElementById(idCampo);
        if (campo?.value?.trim()) {
            limpiarErrorProducto(campo);
            return true;
        }

        return marcarErrorProducto(campo, mensaje);
    }

    // Convierte la coma decimal al formato que utiliza la base de datos.
    function normalizarNumeroDecimalProducto(valor) {
        return String(valor ?? '').trim().replace(',', '.');
    }

    // VALIDA UN CAMPO NUMÉRICO
    // permite definir un mínimo y un máximo; null significa que ese límite no se aplica.
    // devuelve true cuando el número cumple las condiciones.
    function validarNumeroProducto(idCampo, mensaje, minimo = null, maximo = null) {
        const campo = document.getElementById(idCampo);
        const valorNormalizado = normalizarNumeroDecimalProducto(campo?.value);
        const numero = Number(valorNormalizado);

        if (!valorNormalizado || !/^\d+(?:\.\d+)?$/.test(valorNormalizado) || Number.isNaN(numero)) {
            return marcarErrorProducto(campo, mensaje);
        }

        if (campo.validity && !campo.validity.valid) {
            return marcarErrorProducto(campo, 'Ingrese un numero valido para este campo.');
        }

        if (minimo !== null && numero < minimo) {
            return marcarErrorProducto(campo, `Debe ser mayor o igual a ${minimo}.`);
        }

        if (maximo !== null && numero > maximo) {
            return marcarErrorProducto(campo, `Debe ser menor o igual a ${maximo}.`);
        }

        limpiarErrorProducto(campo);
        return true;
    }

    // ACTIVA LA LIMPIEZA AUTOMÁTICA DE ERRORES
    // agrega eventos al campo para retirar el error cuando el usuario escribe o cambia su valor.
    function prepararLimpiezaErrorProducto(campo) {
        if (!campo) {
            return;
        }

        campo.addEventListener('input', () => limpiarErrorProducto(campo));
        campo.addEventListener('change', () => limpiarErrorProducto(campo));
        campo.addEventListener('invalid', (event) => {
            event.preventDefault();
            marcarErrorProducto(campo, campo.validationMessage || 'Revise este campo.');
        });
    }

    // VALIDA LOS CAMPOS DEL PASO INDICADO
    // indicePaso identifica el paso que se revisará antes de continuar.
    // actualmente el paso 0 valida los datos principales del producto.
    function validarPasoProducto(indicePaso) {
        if (indicePaso !== 0) {
            return true;
        }

        const reglas = [
            ['form_codigo', 'Ingrese el codigo del producto.'],
            ['form_nombre_comercial', 'Ingrese el nombre comercial.'],
            ['form_tipo_producto', 'Ingrese el tipo de producto.'],
            ['form_id_importador_persona', 'Seleccione el importador.'],
            ['form_id_territorio_pais', 'Seleccione el pais.'],
            ['form_id_fabricante', 'Seleccione el fabricante.'],
            ['form_id_clasificacion_toxicologica', 'Seleccione la clasificación toxicológica.'],
        ];

        const resultados = reglas.map(([id, mensaje]) => validarRequeridoProducto(id, mensaje));
        const valido = resultados.every(Boolean);

        if (!valido) {
            document.querySelector('.is-invalid')?.focus?.();
        }

        return valido;
    }



    // ACTUALIZA LA VISTA DE LA ETIQUETA
    // muestra el archivo seleccionado y sus acciones disponibles.
    function actualizarControlEtiquetaActual(nombreArchivo, tieneImagen, urlImagen = '') {
        const nombre = document.getElementById('presentacionEtiquetaNombre');
        const botonVista = document.getElementById('presentacionEtiquetaPreview');
        const botonCambiar = document.getElementById('btnCambiarEtiquetaImagen');
        const botonVer = document.getElementById('btnVerEtiquetaImagen');
        const botonQuitar = document.getElementById('btnQuitarEtiquetaImagen');
        const imagen = document.getElementById('presentacionEtiquetaImagen');
        const placeholder = document.getElementById('presentacionEtiquetaPlaceholder');

        if (nombre) {
            nombre.textContent = nombreArchivo;
        }

        if (botonVista) {
            botonVista.classList.toggle('is-empty', !tieneImagen);
            botonVista.disabled = !tieneImagen;
        }

        if (botonCambiar) {
            botonCambiar.disabled = !tieneImagen;
        }

        if (botonVer) {
            botonVer.disabled = !tieneImagen;
        }

        if (botonQuitar) {
            botonQuitar.disabled = !tieneImagen;
        }

        if (imagen) {
            imagen.src = tieneImagen ? urlImagen : '';
            imagen.hidden = !tieneImagen;
        }

        if (placeholder) {
            placeholder.hidden = tieneImagen;
        }
    }

    // REINICIA EL CONTROL DE ETIQUETA
    // devuelve el selector y la vista previa de etiqueta a su estado inicial.
    function reiniciarControlEtiquetaActual() {
        actualizarControlEtiquetaActual('Sin imagen seleccionada.', false);
    }

    // QUITA LA ETIQUETA SELECCIONADA DE LA PRESENTACIÓN
    // limpia el archivo, la vista previa y las referencias temporales utilizadas por el navegador.
    function limpiarEtiquetaPresentacionProducto() {
        const etiqueta = document.getElementById('form_presentacion_etiqueta');
        const origen = document.getElementById('form_presentacion_origen_id');
        const catalogo = document.getElementById('form_presentacion_catalogo');

        if (etiqueta) {
            etiqueta.value = '';
        }

        if (origen) {
            origen.value = '';
        }

        if (catalogo) {
            catalogo.value = '';
        }

        bloquearCamposPresentacionProducto(false);
        if (productoEtiquetaPreviewUrl) {
            // libera de memoria la URL temporal creada anteriormente por el navegador.
            URL.revokeObjectURL(productoEtiquetaPreviewUrl);
            productoEtiquetaPreviewUrl = null;
        }

        productoEtiquetaOrigenActual = null;
        limpiarErrorProducto(etiqueta);
        reiniciarControlEtiquetaActual();
    }
    // VERIFICA SI EL ARCHIVO DE ETIQUETA ES UNA IMAGEN
    // devuelve true únicamente cuando el archivo seleccionado tiene un tipo MIME de imagen.
    function esImagenEtiquetaProducto(archivo) {
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        return tiposPermitidos.includes(archivo?.type) || /\.(jpe?g|png|webp)$/i.test(archivo?.name || '');
    }

    // ACTUALIZA LA VISTA PREVIA DE LA ETIQUETA
    // toma el archivo seleccionado, valida que sea imagen y crea una URL temporal para mostrarlo.
    function actualizarVistaPreviaEtiquetaProducto() {
        const etiqueta = document.getElementById('form_presentacion_etiqueta');
        const archivo = etiqueta?.files?.[0] ?? null;

        limpiarErrorProducto(etiqueta);

        if (productoEtiquetaPreviewUrl) {
            // libera de memoria la URL temporal creada anteriormente por el navegador.
            URL.revokeObjectURL(productoEtiquetaPreviewUrl);
            productoEtiquetaPreviewUrl = null;
        }

        if (!archivo) {
            reiniciarControlEtiquetaActual();
            return;
        }

        if (!esImagenEtiquetaProducto(archivo)) {
            etiqueta.value = '';
            actualizarControlEtiquetaActual('Seleccione una imagen JPG, PNG o WEBP.', false);
            marcarErrorProducto(etiqueta, 'Seleccione una imagen JPG, PNG o WEBP.');
            return;
        }

        productoEtiquetaPreviewUrl = URL.createObjectURL(archivo);
        actualizarControlEtiquetaActual(archivo.name, true, productoEtiquetaPreviewUrl);
    }

    // FILTRA LAS PRESENTACIONES DISPONIBLES
    // muestra únicamente las presentaciones del catálogo que corresponden a los datos actuales del producto.

    function filtrarPresentacionesCatalogoProducto() {
        const catalogo = document.getElementById('form_presentacion_catalogo');
        const importadorId = document.getElementById('form_id_importador_persona')?.value || '';
        const codigoProducto = normalizarTextoProducto(document.getElementById('form_codigo')?.value);
        const nombreProducto = normalizarTextoProducto(document.getElementById('form_nombre_comercial')?.value);

        if (!catalogo) {
            return;
        }

        Array.from(catalogo.options).forEach((opcion) => {
            if (!opcion.value) {
                opcion.hidden = false;
                opcion.disabled = false;
                return;
            }

            const mismoImportador = opcion.dataset.importadorId === importadorId;
            const codigoCatalogo = normalizarTextoProducto(opcion.dataset.productoCodigo);
            const nombreCatalogo = normalizarTextoProducto(opcion.dataset.productoNombre);
            const mismoProducto = codigoProducto ?
                codigoCatalogo === codigoProducto :
                nombreProducto && nombreCatalogo === nombreProducto;
            const visible = mismoImportador && Boolean(mismoProducto);

            opcion.hidden = !visible;
            opcion.disabled = !visible;
        });

        if (catalogo.value && catalogo.options[catalogo.selectedIndex]?.disabled) {
            limpiarEtiquetaPresentacionProducto();
        }
    }

    // CARGA UNA PRESENTACIÓN EXISTENTE EN EL FORMULARIO
    // al seleccionar una presentación del catálogo copia sus datos en los campos form_presentacion_*.
    function autocompletarPresentacionProducto() {
        const catalogo = document.getElementById('form_presentacion_catalogo');
        const opcion = catalogo?.options[catalogo.selectedIndex];
        const origen = document.getElementById('form_presentacion_origen_id');
        const etiqueta = document.getElementById('form_presentacion_etiqueta');
        const esTemporal = opcion?.dataset.temporalPresentacion === '1';

        if (!opcion?.value) {
            if (origen) {
                origen.value = '';
            }
            productoEtiquetaOrigenActual = null;
            bloquearCamposPresentacionProducto(false);
            reiniciarControlEtiquetaActual();
            return;
        }

        if (origen) {
            origen.value = esTemporal ? '' : opcion.value;
        }

        if (etiqueta) {
            etiqueta.value = '';
        }

        if (productoEtiquetaPreviewUrl) {
            // libera de memoria la URL temporal creada anteriormente por el navegador.
            URL.revokeObjectURL(productoEtiquetaPreviewUrl);
            productoEtiquetaPreviewUrl = null;
        }

        document.getElementById('form_presentacion_cantidad').value = opcion.dataset.cantidad || '';
        establecerUnidadProducto('form_presentacion_unidad', opcion.dataset.unidadId || '');

        // Obtiene el tipo de envase guardado en la presentación seleccionada.
        // Ejemplo: "Frasco", "Bidón" o "Aerosol".
        const tipoEnvaseGuardado = opcion.dataset.tipoEnvase || '';
        const selectorTipoEnvase = document.getElementById(
            'form_presentacion_tipo_envase'
        );

        if (selectorTipoEnvase) {
            // Una presentación antigua puede tener un tipo de envase que aún no aparece
            // en el selector. Se agrega solo a esta pantalla para poder mostrarlo.
            const tipoEnvaseExiste = Array.from(selectorTipoEnvase.options).some((opcionEnvase) => opcionEnvase
                .value === tipoEnvaseGuardado);

            if (tipoEnvaseGuardado && !tipoEnvaseExiste) {
                const nuevaOpcion = new Option(
                    tipoEnvaseGuardado,
                    tipoEnvaseGuardado
                );
                selectorTipoEnvase.add(nuevaOpcion);
            }

            // Selecciona el envase correspondiente y actualiza el buscador visual.
            sincronizarBuscadorProducto(selectorTipoEnvase, tipoEnvaseGuardado);
        }

        document.getElementById('form_presentacion_descripcion').value = opcion.dataset.descripcion || '';

        const estadoPresentacion = document.getElementById('form_presentacion_estado');
        if (estadoPresentacion) {
            estadoPresentacion.value = opcion.dataset.estado || 'ACTIVO';
        }

        productoEtiquetaOrigenActual = opcion.dataset.etiquetaUrl ? {
            id: esTemporal ? opcion.dataset.temporalIndice : opcion.value,
            url: opcion.dataset.etiquetaUrl,
            nombre: opcion.dataset.etiquetaNombre || 'Etiqueta registrada',
        } : null;

        actualizarControlEtiquetaActual(
            productoEtiquetaOrigenActual ? productoEtiquetaOrigenActual.nombre :
            'Presentación sin imagen registrada.',
            Boolean(productoEtiquetaOrigenActual),
            productoEtiquetaOrigenActual?.url || ''
        );

        // Si se selecciona una presentacion ya registrada o temporal, solo deben editarse los datos del registro.
        bloquearCamposPresentacionProducto(true);
    }

    // BLOQUEA O HABILITA LOS CAMPOS DE PRESENTACIÓN
    // bloquear=true impide modificar una presentación existente; false permite editar una nueva.
    function bloquearCamposPresentacionProducto(bloquear) {
        ['form_presentacion_cantidad', 'form_presentacion_unidad', 'form_presentacion_descripcion'].forEach((id) => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.readOnly = bloquear;
            }
        });

        const estado = document.getElementById('form_presentacion_estado');
        if (estado) {
            estado.disabled = bloquear;
        }

        const inputEtiqueta = document.getElementById('form_presentacion_etiqueta');
        if (inputEtiqueta) {
            inputEtiqueta.disabled = bloquear;
        }

        const tieneImagen = Boolean(
            productoEtiquetaPreviewUrl ||
            productoEtiquetaOrigenActual?.url ||
            inputEtiqueta?.files?.[0]
        );
        const botonSeleccionar = document.getElementById('btnSeleccionarEtiquetaImagen');
        const botonCambiar = document.getElementById('btnCambiarEtiquetaImagen');
        const botonQuitar = document.getElementById('btnQuitarEtiquetaImagen');

        if (botonSeleccionar) {
            botonSeleccionar.disabled = bloquear;
        }
        if (botonCambiar) {
            botonCambiar.disabled = bloquear || !tieneImagen;
        }
        if (botonQuitar) {
            botonQuitar.disabled = bloquear || !tieneImagen;
        }
    }

    // PREPARA LOS CAMPOS PARA UNA NUEVA PRESENTACIÓN
    // limpia la presentación anterior y deja los controles listos para ingresar nuevos datos.
    function prepararNuevaPresentacionProducto() {
        const catalogo = document.getElementById('form_presentacion_catalogo');

        if (catalogo) {
            catalogo.value = '';
        }

        // Limpia los datos de la presentación anterior para iniciar una nueva.
        document.getElementById('form_presentacion_cantidad').value = '';

        const selectorTipoEnvase = document.getElementById('form_presentacion_tipo_envase');

        if (selectorTipoEnvase) {
            // Quita el envase anterior y actualiza el buscador visual.
            sincronizarBuscadorProducto(selectorTipoEnvase, '');
        }

        establecerUnidadProducto('form_presentacion_unidad');

        document.getElementById('form_presentacion_descripcion').value = '';

        const estadoPresentacion = document.getElementById('form_presentacion_estado');
        if (estadoPresentacion) {
            estadoPresentacion.value = 'ACTIVO';
        }
        limpiarEtiquetaPresentacionProducto();
        document.getElementById('form_presentacion_cantidad')?.focus();
    }

    // AGREGA UNA PRESENTACIÓN TEMPORAL AL SELECTOR
    // indicePresentacion identifica la posición usada en presentaciones[...].
    // datos contiene los valores visibles de la presentación recién creada.
    function registrarOpcionTemporalPresentacionProducto(indicePresentacion, datos) {
        const catalogo = document.getElementById('form_presentacion_catalogo');

        if (!catalogo || catalogo.querySelector(`option[value="TEMP-${indicePresentacion}"]`)) {
            return;
        }

        const opcion = document.createElement('option');
        opcion.value = `TEMP-${indicePresentacion}`;
        opcion.textContent = `Ya agregada: ${datos.texto}`;
        opcion.dataset.temporalPresentacion = '1';
        opcion.dataset.temporalIndice = String(indicePresentacion);
        opcion.dataset.importadorId = document.getElementById('form_id_importador_persona')?.value || '';
        opcion.dataset.productoCodigo = document.getElementById('form_codigo')?.value || '';
        opcion.dataset.productoNombre = document.getElementById('form_nombre_comercial')?.value || '';
        opcion.dataset.cantidad = datos.cantidad || '';
        opcion.dataset.unidadId = datos.id_catalogo_unidad || '';
        opcion.dataset.unidadTexto = datos.unidad_texto || '';
        opcion.dataset.tipoEnvase = datos.tipo_envase || '';
        opcion.dataset.descripcion = datos.descripcion || '';
        opcion.dataset.estado = datos.estado || 'ACTIVO';
        opcion.dataset.etiquetaUrl = datos.etiquetaUrl || '';
        opcion.dataset.etiquetaNombre = datos.etiquetaNombre || 'Imagen de etiqueta';

        catalogo.appendChild(opcion);
    }

    // BUSCA SI UNA PRESENTACIÓN TEMPORAL YA EXISTE
    // compara origen, cantidad, unidad, descripción y tipo de envase.
    // devuelve el índice existente o null cuando no encuentra coincidencia.
    function buscarIndicePresentacionTemporalProducto(origenId, cantidad, idCatalogoUnidad, descripcion, tipoEnvase) {
        if (origenId) {
            const origenExistente = document.querySelector(
                `#presentacionesOcultasProducto input[name$="[id_presentacion_origen]"][value="${CSS.escape(origenId)}"]`
            );

            if (origenExistente) {
                return origenExistente.name.match(/presentaciones\[(\d+)]/)?.[1] ?? null;
            }
        }

        const claveNueva = [
            normalizarTextoProducto(cantidad),
            normalizarTextoProducto(idCatalogoUnidad),
            normalizarTextoProducto(descripcion),
            normalizarTextoProducto(tipoEnvase),
        ].join('|');

        for (const bloque of document.querySelectorAll('#presentacionesOcultasProducto [data-presentacion-temporal]')) {
            const indice = bloque.dataset.presentacionTemporal;
            const claveActual = [
                normalizarTextoProducto(bloque.querySelector(`input[name="presentaciones[${indice}][cantidad]"]`)
                    ?.value),
                normalizarTextoProducto(bloque.querySelector(
                    `input[name="presentaciones[${indice}][id_catalogo_unidad]"]`)?.value),
                normalizarTextoProducto(bloque.querySelector(`input[name="presentaciones[${indice}][descripcion]"]`)
                    ?.value),
                normalizarTextoProducto(bloque.querySelector(`input[name="presentaciones[${indice}][tipo_envase]"]`)
                    ?.value),
            ].join('|');

            if (claveActual === claveNueva) {
                return indice;
            }
        }

        return null;
    }

    // CREA LOS INPUTS QUE SE ENVIARÁN COMO PRESENTACIÓN
    // genera los campos hidden presentaciones[indice][...] que posteriormente recibe el controlador.
    function crearCamposOcultosPresentacionProducto(indicePresentacion, datos) {
        const contenedor = document.getElementById('presentacionesOcultasProducto');

        if (!contenedor || document.getElementById(`presentacion_oculta_${indicePresentacion}`)) {
            return;
        }

        const bloque = document.createElement('div');
        bloque.id = `presentacion_oculta_${indicePresentacion}`;
        bloque.dataset.presentacionTemporal = String(indicePresentacion);
        bloque.innerHTML = `
                <input type="hidden" name="presentaciones[${indicePresentacion}][cantidad]" value="${escaparHtmlProducto(datos.cantidad)}">
                <input type="hidden" name="presentaciones[${indicePresentacion}][id_catalogo_unidad]" value="${escaparHtmlProducto(datos.id_catalogo_unidad)}">
                <input type="hidden" name="presentaciones[${indicePresentacion}][descripcion]" value="${escaparHtmlProducto(datos.descripcion)}">
                <input type="hidden" name="presentaciones[${indicePresentacion}][estado]" value="${escaparHtmlProducto(datos.estado)}">
                <input type="hidden" name="presentaciones[${indicePresentacion}][id_presentacion_origen]" value="${escaparHtmlProducto(datos.origenId)}">
                <input type="hidden" name="presentaciones[${indicePresentacion}][tipo_envase]" value="${escaparHtmlProducto(datos.tipo_envase)}">
                <span class="presentacion-etiqueta-input"></span>
            `;

        contenedor.appendChild(bloque);
    }

    // VERIFICA SI UN REGISTRO YA FUE AGREGADO
    // compara los datos principales del registro dentro de la presentación indicada.
    // devuelve true cuando encuentra una fila equivalente.
    function existeRegistroDuplicadoProducto(indicePresentacion, codigo, numeroLote, fechaVigencia, cantidad,
        idCatalogoUnidad) {
        const claveNueva = [
            String(indicePresentacion),
            normalizarTextoProducto(codigo),
            normalizarTextoProducto(numeroLote),
            normalizarTextoProducto(fechaVigencia),
            normalizarTextoProducto(cantidad),
            normalizarTextoProducto(idCatalogoUnidad),
        ].join('|');

        return Array.from(document.querySelectorAll(
                '#tablaRegistrosPresentacionesProducto tr[data-presentacion-indice]'))
            .some((fila) => {
                const claveFila = [
                    fila.dataset.presentacionIndice,
                    normalizarTextoProducto(fila.querySelector('input[name$="[codigo_autorizacion]"]')?.value),
                    normalizarTextoProducto(fila.querySelector('input[name$="[numero_lote]"]')?.value),
                    normalizarTextoProducto(fila.querySelector('input[name$="[fecha_vigencia]"]')?.value),
                    normalizarTextoProducto(fila.querySelector('input[name$="[cantidad]"][name^="registros"]')
                        ?.value),
                    normalizarTextoProducto(fila.querySelector(
                        'input[name$="[id_catalogo_unidad]"][name^="registros"]')?.value),
                ].join('|');

                return claveFila === claveNueva;
            });
    }

    // RECUPERA UNA ETIQUETA DESPUÉS DE UNA VALIDACIÓN O EDICIÓN
    // permite volver a mostrar una etiqueta que ya estaba registrada sin obligar a seleccionarla nuevamente.
    function actualizarEtiquetaOldProducto(input) {
        const archivo = input?.files?.[0] ?? null;
        const celda = input?.closest('.producto-table-etiqueta-cell');
        const nombre = celda?.querySelector('.producto-table-file-name');
        const vistaPrevia = celda?.querySelector('[data-etiqueta-old-preview]');
        const imagen = celda?.querySelector('[data-etiqueta-old-imagen]');
        const placeholder = celda?.querySelector('[data-etiqueta-old-placeholder]');
        const urlInicial = input?.dataset.urlInicial || '';

        limpiarErrorProducto(input);

        if (productoEtiquetaOldUrls[input.id]?.esTemporal && productoEtiquetaOldUrls[input.id]?.url) {
            URL.revokeObjectURL(productoEtiquetaOldUrls[input.id].url);
        }
        delete productoEtiquetaOldUrls[input.id];

        if (!archivo) {
            const nombreInicial = urlInicial ? 'Imagen registrada' : 'Seleccione nuevamente la imagen';

            if (nombre) {
                nombre.textContent = nombreInicial;
            }
            if (vistaPrevia) {
                vistaPrevia.disabled = !urlInicial;
                vistaPrevia.classList.toggle('is-empty', !urlInicial);
            }
            if (imagen) {
                imagen.src = urlInicial;
                imagen.hidden = !urlInicial;
            }
            if (placeholder) {
                placeholder.hidden = Boolean(urlInicial);
            }
            if (urlInicial) {
                productoEtiquetaOldUrls[input.id] = {
                    url: urlInicial,
                    nombre: nombreInicial,
                    esTemporal: false
                };
            }
            return;
        }

        if (!esImagenEtiquetaProducto(archivo)) {
            input.value = '';
            actualizarEtiquetaOldProducto(input);
            marcarErrorProducto(input, 'Seleccione una imagen JPG, PNG o WEBP.');
            return;
        }

        productoEtiquetaOldUrls[input.id] = {
            url: URL.createObjectURL(archivo),
            nombre: archivo.name,
            esTemporal: true,
        };

        if (nombre) {
            nombre.textContent = archivo.name;
        }

        if (vistaPrevia) {
            vistaPrevia.disabled = false;
            vistaPrevia.classList.remove('is-empty');
        }
        if (imagen) {
            imagen.src = productoEtiquetaOldUrls[input.id].url;
            imagen.hidden = false;
        }
        if (placeholder) {
            placeholder.hidden = true;
        }
    }

    // ABRE UNA ETIQUETA RECUPERADA EN EL VISOR
    // idInput identifica el input que contiene la URL inicial de la etiqueta registrada.
    function abrirModalEtiquetaOldProducto(idInput) {
        const input = document.getElementById(idInput);
        const datos = productoEtiquetaOldUrls[idInput] || (input?.dataset.urlInicial ? {
            url: input.dataset.urlInicial,
            nombre: 'Imagen registrada',
        } : null);

        if (!datos?.url) {
            notificarProducto('info', 'Sin imagen', 'Seleccione una imagen de la etiqueta para verla.');
            return;
        }

        mostrarEtiquetaEnModalProducto(datos.url, datos.nombre);
    }

    // ABRE LA ETIQUETA DE UNA PRESENTACIÓN EN EL VISOR
    // La fila es la fuente principal; el mapa temporal queda como respaldo.
    function abrirModalEtiquetaProducto(indice = null, elemento = null) {
        const fila = elemento?.closest('tr');
        const imagenFila = fila?.querySelector('.producto-table-image-preview img');
        const datosFila = indice !== null ? productoEtiquetaUrls[indice] : null;
        const url = fila?.dataset.etiquetaUrl ||
            imagenFila?.getAttribute('src') ||
            datosFila?.url ||
            productoEtiquetaPreviewUrl ||
            productoEtiquetaOrigenActual?.url;
        const nombre = fila?.dataset.etiquetaNombre ||
            datosFila?.nombre ||
            productoEtiquetaOrigenActual?.nombre ||
            document.getElementById('presentacionEtiquetaNombre')?.textContent ||
            'Imagen de etiqueta';

        if (!url) {
            notificarProducto('info', 'Sin imagen', 'Seleccione una imagen de la etiqueta para verla.');
            return;
        }

        mostrarEtiquetaEnModalProducto(url, nombre);
    }
    // MUESTRA UNA IMAGEN DENTRO DEL MODAL DE ETIQUETA
    // url indica la imagen y nombre es el texto mostrado en el encabezado.
    function mostrarEtiquetaEnModalProducto(url, nombre) {
        const imagen = document.getElementById('etiquetaImagenPreview');
        const modal = document.getElementById('modalEtiquetaImagenProducto');

        if (!imagen || !modal) {
            return;
        }

        imagen.onload = prepararZoomEtiquetaProducto;
        imagen.src = url;
        document.getElementById('etiquetaImagenModalNombre').textContent = nombre || 'Imagen de etiqueta';
        // is-open es la clase CSS utilizada por esta vista para hacer visible el modal.
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');

        if (imagen.complete) {
            prepararZoomEtiquetaProducto();
        }
    }

    // PREPARA EL TAMAÑO BASE PARA EL ZOOM
    // calcula el tamaño inicial de la imagen una vez que termina de cargar.
    function prepararZoomEtiquetaProducto() {
        const imagen = document.getElementById('etiquetaImagenPreview');
        const escenario = document.getElementById('etiquetaImagenStage');
        const visor = document.getElementById('etiquetaImagenViewport');

        if (!imagen || !escenario || !visor) {
            return;
        }

        productoEtiquetaZoom = 1;
        imagen.style.width = '';
        imagen.style.height = '';
        imagen.style.transform = '';
        imagen.style.maxWidth = '';
        imagen.style.maxHeight = '';
        escenario.style.width = '';
        escenario.style.height = '';

        requestAnimationFrame(() => {
            const anchoDisponible = Math.max(1, visor.clientWidth - 36);
            const altoDisponible = Math.max(1, visor.clientHeight - 36);
            const anchoNatural = Math.max(1, imagen.naturalWidth);
            const altoNatural = Math.max(1, imagen.naturalHeight);
            const escalaInicial = Math.min(1, anchoDisponible / anchoNatural, altoDisponible / altoNatural);
            productoEtiquetaTamanoBase = {
                ancho: anchoNatural * escalaInicial,
                alto: altoNatural * escalaInicial,
            };
            aplicarZoomEtiquetaProducto();
        });
    }
    // AUMENTA O DISMINUYE EL ZOOM DE LA ETIQUETA
    // cambio se suma al zoom actual; por ejemplo 0.25 aumenta y -0.25 disminuye.
    function cambiarZoomEtiquetaProducto(cambio) {
        productoEtiquetaZoom = Math.min(3, Math.max(0.5, productoEtiquetaZoom + cambio));
        aplicarZoomEtiquetaProducto();
    }
    // RESTABLECE EL ZOOM DE LA ETIQUETA
    // vuelve la imagen al nivel de zoom 1, equivalente al 100%.
    function restablecerZoomEtiquetaProducto() {
        productoEtiquetaZoom = 1;
        aplicarZoomEtiquetaProducto();
    }
    // APLICA EL NIVEL DE ZOOM A LA IMAGEN
    // actualiza el tamaño visible de la etiqueta y el porcentaje mostrado en pantalla.
    function aplicarZoomEtiquetaProducto() {
        const imagen = document.getElementById('etiquetaImagenPreview');
        const escenario = document.getElementById('etiquetaImagenStage');
        const valor = document.getElementById('etiquetaImagenZoomValor');

        if (!imagen || !escenario || !productoEtiquetaTamanoBase.ancho) {
            return;
        }

        const ancho = productoEtiquetaTamanoBase.ancho * productoEtiquetaZoom;
        const alto = productoEtiquetaTamanoBase.alto * productoEtiquetaZoom;
        escenario.style.width = `${ancho}px`;
        escenario.style.height = `${alto}px`;
        imagen.style.width = `${productoEtiquetaTamanoBase.ancho}px`;
        imagen.style.height = `${productoEtiquetaTamanoBase.alto}px`;
        imagen.style.maxWidth = 'none';
        imagen.style.maxHeight = 'none';
        imagen.style.transform = `scale(${productoEtiquetaZoom})`;

        if (valor) {
            valor.textContent = `${Math.round(productoEtiquetaZoom * 100)}%`;
        }

        // Al ampliar se conserva el centro visible para recorrer la etiqueta en cualquier dirección.
        requestAnimationFrame(() => {
            const visor = document.getElementById('etiquetaImagenViewport');
            if (!visor) return;
            visor.scrollLeft = Math.max(0, (ancho - visor.clientWidth) / 2);
            visor.scrollTop = Math.max(0, (alto - visor.clientHeight) / 2);
        });
    }

    // CIERRA EL VISOR DE ETIQUETA
    // oculta el modal y restablece el zoom para la próxima apertura.
    function cerrarModalEtiquetaProducto() {
        const modal = document.getElementById('modalEtiquetaImagenProducto');
        modal?.classList.remove('is-open');
        modal?.setAttribute('aria-hidden', 'true');
        restablecerZoomEtiquetaProducto();
    }

    // AGREGA UN INGREDIENTE AL PRODUCTO
    // toma los datos form_ingrediente_*, los valida y crea una fila con ingredientes_productos[indice][...].
    // esos inputs son los que finalmente recibirá el controlador.


    function agregarIngredienteProducto() {
        const select = document.getElementById('form_ingrediente_select');
        const porcentaje = document.getElementById('form_ingrediente_porcentaje');
        const estado = document.getElementById('form_ingrediente_estado');
        const ingredienteId = select.value;
        const opcionIngrediente = select.options[select.selectedIndex];
        const ingredienteNombre = opcionIngrediente?.dataset.nombre || opcionIngrediente?.text || 'Sin nombre';
        const ingredienteValido = validarRequeridoProducto('form_ingrediente_select', 'Seleccione un ingrediente.');
        const porcentajeValido = validarNumeroProducto('form_ingrediente_porcentaje', 'Ingrese un porcentaje valido.',
            0, 100);

        if (!ingredienteValido || !porcentajeValido) {
            return;
        }

        const ingredienteRepetido = Array.from(document.querySelectorAll(
            '#tablaIngredientesProducto input[name*="[id_ingrediente]"]'
        )).some((input) => input.value === ingredienteId);

        if (ingredienteRepetido) {
            marcarErrorProducto(select, 'Este ingrediente ya fue agregado.');
            return;
        }

        document.getElementById('sinIngredientesProducto')?.remove();

        const fila = document.createElement('tr');
        fila.innerHTML = `
                <td class="producto-ingredient-index">${productoIngredienteIndice + 1}</td>
                <td class="producto-ingredient-name">
                    ${escaparHtmlProducto(ingredienteNombre)}
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][id_ingrediente]" value="${ingredienteId}">
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][nombre]" value="${escaparHtmlProducto(ingredienteNombre)}">
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][porcentaje]" value="${porcentaje.value}">
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][estado]" value="${estado.value}">
                </td>
                <td class="producto-ingredient-percentage">${formatearPorcentajeIngrediente(porcentaje.value)}%</td>
                <td><span class="producto-pill">${estado.value}</span></td>
                <td>
                    <button type="button" class="producto-btn producto-btn-danger" onclick="quitarFilaProducto(this)">
                        <i class="fa-solid fa-xmark text-[10px]"></i>
                        <span>Quitar</span>
                    </button>
                </td>
            `;

        document.getElementById('tablaIngredientesProducto').appendChild(fila);
        productoIngredienteIndice++;
        porcentaje.value = '';
        select.value = '';
        actualizarDetalleIngredienteProducto();
        limpiarErrorProducto(select);
        limpiarErrorProducto(porcentaje);
        actualizarTotalPorcentajeIngredientesProducto();
        actualizarResumenProducto();
    }


    // FORMATEA EL PORCENTAJE DE UN INGREDIENTE
    // evita decimales innecesarios y conserva hasta cinco decimales cuando existen.
    function formatearPorcentajeIngrediente(valor) {
        const porcentaje = Number(valor);

        if (!Number.isFinite(porcentaje)) {
            return '0';
        }

        return porcentaje.toLocaleString('es-BO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 5,
        });
    }

    // CALCULA EL PORCENTAJE TOTAL DE INGREDIENTES
    // suma todas las filas, actualiza la barra visual y muestra aviso cuando el total supera 100%.
    function actualizarTotalPorcentajeIngredientesProducto() {
        const total = Array.from(document.querySelectorAll(
            '#tablaIngredientesProducto input[name*="[porcentaje]"]'
        )).reduce((suma, input) => suma + (Number(input.value) || 0), 0);

        const totalTexto = document.getElementById('totalPorcentajeIngredientesProducto');
        const alerta = document.getElementById('alertaPorcentajeIngredientesProducto');
        const barra = document.getElementById('barraPorcentajeIngredientesProducto');

        if (totalTexto) {
            totalTexto.textContent = `${formatearPorcentajeIngrediente(total)}%`;
        }

        if (alerta) {
            alerta.classList.toggle('hidden', total <= 100);
        }

        if (barra) {
            barra.style.width = `${Math.min(total, 100)}%`;
            barra.classList.toggle('is-over-limit', total > 100);
        }
    }

    // AGREGA UNA PRESENTACIÓN Y UN REGISTRO
    // valida los datos y agrega una fila para enviar al controlador al guardar el producto.
    function agregarPresentacionRegistroProducto() {
        const datos = obtenerDatosRegistroPresentacionProducto();

        if (!validarRegistroPresentacionProducto(datos)) {
            return;
        }

        const datosPreparados = prepararRegistroPresentacionProducto(datos);

        if (existeRegistroDuplicadoProducto(
                datosPreparados.indicePresentacion,
                datosPreparados.registro.codigoAutorizacion,
                datosPreparados.registro.numeroLote,
                datosPreparados.registro.fechaVigencia,
                datosPreparados.registro.cantidad,
                datosPreparados.registro.unidadId
            )) {
            marcarErrorProducto(
                datosPreparados.campos.codigoAutorizacion,
                'Este registro ya fue agregado para la presentación seleccionada.'
            );

            return;
        }

        document.getElementById('sinRegistrosPresentacionesProducto')?.remove();

        if (!datosPreparados.reutilizaPresentacion) {
            guardarPresentacionNuevaProducto(datosPreparados);
        }

        const fila = crearFilaRegistroProducto(datosPreparados);

        document
            .getElementById('tablaRegistrosPresentacionesProducto')
            .appendChild(fila);

        guardarEtiquetaPresentacionProducto(datosPreparados);

        limpiarFormularioRegistroPresentacionProducto(datosPreparados);

        filtrarRegistrosPresentacionesProducto();
        actualizarResumenProducto();
    }

    // OBTIENE LOS DATOS DEL FORMULARIO
    // reúne los valores de presentación, registro e imagen sin cambiar el formulario.
    function obtenerDatosRegistroPresentacionProducto() {
        const campos = {
            tipoEnvase: document.getElementById('form_presentacion_tipo_envase'),
            cantidadPresentacion: document.getElementById('form_presentacion_cantidad'),
            unidadPresentacion: document.getElementById('form_presentacion_unidad'),
            descripcionPresentacion: document.getElementById('form_presentacion_descripcion'),
            estadoPresentacion: document.getElementById('form_presentacion_estado'),
            origenPresentacion: document.getElementById('form_presentacion_origen_id'),
            catalogoPresentacion: document.getElementById('form_presentacion_catalogo'),
            etiqueta: document.getElementById('form_presentacion_etiqueta'),

            codigoAutorizacion: document.getElementById('form_registro_codigo_autorizacion'),
            numeroLote: document.getElementById('form_registro_numero_lote'),
            fechaVigencia: document.getElementById('form_registro_fecha_vigencia'),
            cantidadRegistro: document.getElementById('form_registro_cantidad'),
            unidadRegistro: document.getElementById('form_registro_unidad'),
            estadoRegistro: document.getElementById('form_registro_estado'),
        };

        const opcionSeleccionada =
            campos.catalogoPresentacion?.options[
                campos.catalogoPresentacion.selectedIndex
            ];

        return {
            campos,
            presentacion: {
                cantidad: normalizarNumeroDecimalProducto(campos.cantidadPresentacion.value),
                unidadId: campos.unidadPresentacion.value,
                tipoEnvase: campos.tipoEnvase?.value.trim() || '',
                descripcion: campos.descripcionPresentacion.value.trim(),
                estado: campos.estadoPresentacion?.value || 'ACTIVO',
                origenId: campos.origenPresentacion?.value || '',
            },

            registro: {
                codigoAutorizacion: campos.codigoAutorizacion.value.trim(),
                numeroLote: campos.numeroLote.value.trim(),
                fechaVigencia: campos.fechaVigencia.value,
                cantidad: normalizarNumeroDecimalProducto(campos.cantidadRegistro.value),
                unidadId: campos.unidadRegistro.value,
                estado: campos.estadoRegistro?.value || 'ACTIVO',
                idRegistro: productoRegistroEditandoId || '',
            },

            archivoEtiqueta: campos.etiqueta?.files?.[0] ?? null,

            indiceTemporalSeleccionado: opcionSeleccionada?.dataset.temporalPresentacion === '1' ?
                opcionSeleccionada.dataset.temporalIndice :
                null,
        };
    }

    // VALIDA LA PRESENTACIÓN Y EL REGISTRO
    // verifica los campos obligatorios y el formato de la imagen cuando existe.
    function validarRegistroPresentacionProducto(datos) {
        const cantidadPresentacionValida = validarNumeroProducto(
            'form_presentacion_cantidad',
            'Ingrese la cantidad de la presentación.',
            0.00001
        );

        const unidadPresentacionValida = validarRequeridoProducto(
            'form_presentacion_unidad',
            'Seleccione la unidad de la presentación.'
        );

        if (!cantidadPresentacionValida || !unidadPresentacionValida) {
            return false;
        }

        if (
            datos.archivoEtiqueta &&
            !esImagenEtiquetaProducto(datos.archivoEtiqueta)
        ) {
            marcarErrorProducto(
                datos.campos.etiqueta,
                'Seleccione una imagen JPG, PNG o WEBP.'
            );

            return false;
        }

        return true;
    }

    // PREPARA LOS DATOS DEL REGISTRO
    // determina índices, textos visibles y datos de la etiqueta.
    function prepararRegistroPresentacionProducto(datos) {
        const indiceExistente =
            datos.indiceTemporalSeleccionado ??
            buscarIndicePresentacionTemporalProducto(
                datos.presentacion.origenId,
                datos.presentacion.cantidad,
                datos.presentacion.unidadId,
                datos.presentacion.descripcion,
                datos.presentacion.tipoEnvase
            );

        const reutilizaPresentacion =
            indiceExistente !== null && indiceExistente !== undefined;

        const indicePresentacion = reutilizaPresentacion ?
            Number(indiceExistente) :
            productoPresentacionIndice;

        const textoUnidadPresentacion = textoUnidadCatalogoProducto(
            datos.presentacion.unidadId
        );

        const textoUnidadRegistro = textoUnidadCatalogoProducto(
            datos.registro.unidadId
        );

        const valorPresentacion = (
            `${datos.presentacion.cantidad} ${textoUnidadPresentacion}`
        ).trim();

        const textoPresentacion = [
                valorPresentacion,
                datos.presentacion.tipoEnvase,
                datos.presentacion.descripcion,
            ]
            .filter(Boolean)
            .join(' — ');

        const etiquetaUrl = datos.archivoEtiqueta ?
            productoEtiquetaPreviewUrl || URL.createObjectURL(datos.archivoEtiqueta) :
            productoEtiquetaOrigenActual?.url ||
            productoEtiquetaUrls[indicePresentacion]?.url ||
            '';

        const etiquetaNombre = datos.archivoEtiqueta ?
            datos.archivoEtiqueta.name :
            productoEtiquetaOrigenActual?.nombre ||
            productoEtiquetaUrls[indicePresentacion]?.nombre ||
            '';

        return {
            ...datos,
            reutilizaPresentacion,
            indicePresentacion,
            indiceRegistro: productoRegistroIndice,
            textoUnidadPresentacion,
            textoUnidadRegistro,
            valorPresentacion,
            textoPresentacion,
            etiquetaUrl,
            etiquetaNombre,
        };
    }

    // GUARDA UNA PRESENTACIÓN NUEVA
    // crea sus campos ocultos y la agrega al selector de presentaciones disponibles.
    function guardarPresentacionNuevaProducto(datos) {
        productoEtiquetaUrls[datos.indicePresentacion] = {
            url: datos.etiquetaUrl,
            nombre: datos.etiquetaNombre,
        };

        crearCamposOcultosPresentacionProducto(datos.indicePresentacion, {
            cantidad: datos.presentacion.cantidad,
            id_catalogo_unidad: datos.presentacion.unidadId,
            unidad_texto: datos.textoUnidadPresentacion,
            descripcion: datos.presentacion.descripcion,
            tipo_envase: datos.presentacion.tipoEnvase,
            estado: datos.presentacion.estado,
            origenId: datos.presentacion.origenId,
        });

        registrarOpcionTemporalPresentacionProducto(datos.indicePresentacion, {
            texto: datos.textoPresentacion,
            cantidad: datos.presentacion.cantidad,
            id_catalogo_unidad: datos.presentacion.unidadId,
            unidad_texto: datos.textoUnidadPresentacion,
            descripcion: datos.presentacion.descripcion,
            tipo_envase: datos.presentacion.tipoEnvase,
            estado: datos.presentacion.estado,
            etiquetaUrl: datos.etiquetaUrl,
            etiquetaNombre: datos.etiquetaNombre,
        });
    }

    // CREA LA FILA DEL REGISTRO
    // muestra una sola estructura de tabla para todos los registros.
    function crearFilaRegistroProducto(datos) {
        const fila = document.createElement('tr');
        const tieneEtiqueta = Boolean(datos.etiquetaUrl);
        const nombreProducto = document.getElementById('form_nombre_comercial')?.value ||
            'Sin nombre comercial';
        const codigoProducto = document.getElementById('form_codigo')?.value || 'Sin código';

        fila.dataset.tipoFila = 'registro';
        fila.dataset.presentacionIndice = datos.indicePresentacion;
        fila.dataset.presentacionTexto = datos.textoPresentacion;
        fila.dataset.etiquetaUrl = datos.etiquetaUrl || '';
        fila.dataset.etiquetaNombre = datos.etiquetaNombre || 'Imagen de etiqueta';

        fila.innerHTML = `
        <td data-label="Registro">${datos.indiceRegistro + 1}</td>
        <td data-label="Producto asociado">
            <div class="producto-table-producto">
                <strong class="presentacion-producto-nombre">
                    ${escaparHtmlProducto(nombreProducto)}
                </strong>
                <small class="presentacion-producto-codigo">
                    ${escaparHtmlProducto(codigoProducto)}
                </small>
            </div>
        </td>
        <td data-label="Nombre de la presentación">
            <div class="producto-table-presentacion">
                <strong>
                    ${escaparHtmlProducto(datos.valorPresentacion)}
                </strong>

                ${datos.presentacion.descripcion ? `
                    <small class="producto-table-descripcion">
                        ${escaparHtmlProducto(datos.presentacion.descripcion)}
                    </small>
                ` : ''}
            </div>
        </td>

        <td data-label="Tipo de envase">
            ${escaparHtmlProducto(datos.presentacion.tipoEnvase || 'Sin especificar')}
        </td>

        <td class="producto-table-etiqueta-cell" data-label="Etiqueta">
            ${tieneEtiqueta ? `
                <button type="button" class="producto-table-image-preview"
                    onclick="abrirModalEtiquetaProducto(${datos.indicePresentacion}, this)">
                    <img src="${escaparHtmlProducto(datos.etiquetaUrl)}"
                        alt="Etiqueta de la presentación">
                </button>

                <span class="producto-table-file-name">
                    ${escaparHtmlProducto(datos.etiquetaNombre)}
                </span>
            ` : `
                <span class="producto-table-file-name">Sin imagen</span>
            `}
        </td>

        <td data-label="Acciones">
            <div class="producto-row-actions">
                ${tieneEtiqueta ? `
                    <button type="button" class="producto-action-icon is-view"
                        title="Ver etiqueta"
                        onclick="abrirModalEtiquetaProducto(${datos.indicePresentacion}, this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                ` : ''}

                <button type="button" class="producto-action-icon is-edit"
                    title="Editar presentación y registro"
                    onclick="editarFilaRegistroPresentacionProducto(this)">
                    <i class="fa-solid fa-pen"></i>
                </button>

                <button type="button" class="producto-action-icon is-delete"
                    title="Eliminar presentación y registro"
                    onclick="quitarFilaProducto(this)">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][id_registro]"
                value="${escaparHtmlProducto(datos.registro.idRegistro)}">

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][codigo_autorizacion]"
                value="${escaparHtmlProducto(datos.registro.codigoAutorizacion)}">

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][numero_lote]"
                value="${escaparHtmlProducto(datos.registro.numeroLote)}">

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][fecha_vigencia]"
                value="${escaparHtmlProducto(datos.registro.fechaVigencia)}">

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][cantidad]"
                value="${escaparHtmlProducto(datos.registro.cantidad)}">

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][id_catalogo_unidad]"
                value="${escaparHtmlProducto(datos.registro.unidadId)}">

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][id_presentacion_temporal]"
                value="${escaparHtmlProducto(datos.indicePresentacion)}">

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][presentacion_texto]"
                value="${escaparHtmlProducto(datos.textoPresentacion)}">

            <input type="hidden"
                name="registros[${datos.indiceRegistro}][estado]"
                value="${escaparHtmlProducto(datos.registro.estado)}">
        </td>
    `;

        return fila;
    }

    // GUARDA LA ETIQUETA DE LA PRESENTACIÓN
    // mueve la imagen al bloque oculto para enviarla una sola vez al controlador.
    function guardarEtiquetaPresentacionProducto(datos) {
        const etiqueta = datos.campos.etiqueta;

        if (!etiqueta) {
            return;
        }

        if (!datos.archivoEtiqueta || datos.reutilizaPresentacion) {
            etiqueta.value = '';
            return;
        }

        etiqueta.name =
            `presentaciones[${datos.indicePresentacion}][url_etiqueta]`;

        etiqueta.id =
            `form_presentacion_etiqueta_${datos.indicePresentacion}`;

        etiqueta.classList.add('hidden');
        etiqueta.removeAttribute('onchange');

        document.querySelector(
            `#presentacion_oculta_${datos.indicePresentacion} .presentacion-etiqueta-input`
        )?.appendChild(etiqueta);

        const nuevoInputEtiqueta = document.createElement('input');

        nuevoInputEtiqueta.className = 'producto-upload-input';
        nuevoInputEtiqueta.id = 'form_presentacion_etiqueta';
        nuevoInputEtiqueta.type = 'file';
        nuevoInputEtiqueta.accept =
            'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp';

        nuevoInputEtiqueta.addEventListener(
            'change',
            actualizarVistaPreviaEtiquetaProducto
        );

        prepararLimpiezaErrorProducto(nuevoInputEtiqueta);

        document
            .getElementById('presentacionEtiquetaWrapper')
            ?.prepend(nuevoInputEtiqueta);
    }

    // LIMPIA EL FORMULARIO
    // restablece los campos para agregar el siguiente registro.
    function limpiarFormularioRegistroPresentacionProducto(datos) {
        productoEtiquetaPreviewUrl = null;
        productoEtiquetaOrigenActual = null;

        if (!datos.reutilizaPresentacion) {
            productoPresentacionIndice++;
        }

        productoRegistroIndice++;
        productoRegistroEditandoId = null;

        const {
            campos
        } = datos;

        campos.cantidadPresentacion.value = '';
        campos.descripcionPresentacion.value = '';
        campos.codigoAutorizacion.value = '';
        campos.numeroLote.value = '';
        campos.fechaVigencia.value = '';
        campos.cantidadRegistro.value = '';

        if (campos.origenPresentacion) {
            campos.origenPresentacion.value = '';
        }

        if (campos.catalogoPresentacion) {
            campos.catalogoPresentacion.value = '';
        }

        if (campos.estadoPresentacion) {
            campos.estadoPresentacion.value = 'ACTIVO';
        }

        if (campos.estadoRegistro) {
            campos.estadoRegistro.value = 'ACTIVO';
        }

        sincronizarBuscadorProducto(campos.tipoEnvase, '');

        establecerUnidadProducto('form_presentacion_unidad');
        establecerUnidadProducto('form_registro_unidad');

        bloquearCamposPresentacionProducto(false);

        limpiarErrorProducto(campos.cantidadPresentacion);
        limpiarErrorProducto(campos.unidadPresentacion);
        limpiarErrorProducto(campos.cantidadRegistro);
        limpiarErrorProducto(campos.unidadRegistro);
        limpiarErrorProducto(campos.etiqueta);

        reiniciarControlEtiquetaActual();
    }




    // CARGA UNA FILA PARA EDITARLA
    // recibe el botón Editar de la fila, recupera sus datos y los devuelve a los campos form_presentacion_* y form_registro_*.
    // después retira temporalmente la fila para que se vuelva a agregar con los cambios.
    function editarFilaRegistroPresentacionProducto(boton) {
        const fila = boton?.closest('tr');

        if (!fila) {
            return;
        }

        const indicePresentacion = fila.dataset.presentacionIndice;
        const presentacionOculta = document.getElementById(`presentacion_oculta_${indicePresentacion}`);
        // busca primero el dato dentro de la fila visible; si no está, lo busca en los inputs ocultos de la presentación.
        const valorCampo = (selector) =>
            fila.querySelector(selector)?.value ||
            presentacionOculta?.querySelector(selector)?.value ||
            '';

        document.getElementById('form_presentacion_cantidad').value = valorCampo(
            `input[name="presentaciones[${indicePresentacion}][cantidad]"]`);

        establecerUnidadProducto(
            'form_presentacion_unidad',
            valorCampo(`input[name="presentaciones[${indicePresentacion}][id_catalogo_unidad]"]`)
        );

        // Recupera el envase que pertenece a esta presentación.
        // Ejemplo: si la fila guardó “Frasco”, el selector vuelve a marcar “Frasco”.
        const selectorTipoEnvase = document.getElementById('form_presentacion_tipo_envase');

        const tipoEnvaseGuardado = valorCampo(`input[name="presentaciones[${indicePresentacion}][tipo_envase]"]`);

        if (selectorTipoEnvase) {
            const existeTipoEnvase = Array.from(selectorTipoEnvase.options).some(
                (opcion) => opcion.value === tipoEnvaseGuardado
            );

            if (tipoEnvaseGuardado && !existeTipoEnvase) {
                selectorTipoEnvase.add(new Option(tipoEnvaseGuardado, tipoEnvaseGuardado));
            }

            sincronizarBuscadorProducto(selectorTipoEnvase, tipoEnvaseGuardado);
        }

        document.getElementById('form_presentacion_descripcion').value = valorCampo(
            `input[name="presentaciones[${indicePresentacion}][descripcion]"]`);

        const estadoPresentacion = document.getElementById('form_presentacion_estado');
        if (estadoPresentacion) {
            estadoPresentacion.value = valorCampo(
                `input[name="presentaciones[${indicePresentacion}][estado]"]`) || 'ACTIVO';
        }

        document.getElementById('form_presentacion_origen_id').value = valorCampo(
            `input[name="presentaciones[${indicePresentacion}][id_presentacion_origen]"]`);

        document.getElementById('form_registro_codigo_autorizacion').value = valorCampo(
            'input[name$="[codigo_autorizacion]"]');

        document.getElementById('form_registro_numero_lote').value = valorCampo('input[name$="[numero_lote]"]');
        document.getElementById('form_registro_fecha_vigencia').value = valorCampo('input[name$="[fecha_vigencia]"]');
        document.getElementById('form_registro_cantidad').value = valorCampo(
            'input[name$="[cantidad]"][name^="registros"]');

        establecerUnidadProducto(
            'form_registro_unidad',
            valorCampo('input[name$="[id_catalogo_unidad]"][name^="registros"]')
        );

        const estadoRegistro = document.getElementById('form_registro_estado');
        if (estadoRegistro) {
            estadoRegistro.value = valorCampo(
                'input[name$="[estado]"][name^="registros"]') || 'ACTIVO';
        }

        productoRegistroEditandoId = valorCampo('input[name$="[id_registro]"][name^="registros"]') || null;

        // Conserva la etiqueta mientras la fila se retira para editarla.
        const idPresentacionOrigen = document.getElementById('form_presentacion_origen_id').value;
        const inputEtiquetaRegistrada = fila.querySelector('input[type="file"][data-url-inicial]');
        const inputEtiquetaTemporal = presentacionOculta?.querySelector('input[type="file"]');
        const inputEtiquetaConArchivo = inputEtiquetaTemporal?.files?.[0] ?
            inputEtiquetaTemporal :
            (inputEtiquetaRegistrada?.files?.[0] ? inputEtiquetaRegistrada : null);
        const datosEtiquetaTemporal = productoEtiquetaUrls[indicePresentacion] || null;
        const urlEtiquetaRegistrada = inputEtiquetaRegistrada?.dataset.urlInicial ||
            fila.dataset.etiquetaUrl ||
            datosEtiquetaTemporal?.url ||
            fila.querySelector('.producto-table-image-preview img')?.getAttribute('src') ||
            '';
        const nombreEtiquetaRegistrada = fila.dataset.etiquetaNombre ||
            datosEtiquetaTemporal?.nombre ||
            'Imagen registrada';

        if (inputEtiquetaConArchivo) {
            const idAnterior = inputEtiquetaConArchivo.id;
            const datosAnteriores = productoEtiquetaOldUrls[idAnterior];

            if (datosAnteriores?.esTemporal && datosAnteriores.url) {
                URL.revokeObjectURL(datosAnteriores.url);
            }
            delete productoEtiquetaOldUrls[idAnterior];

            document.getElementById('form_presentacion_etiqueta')?.remove();
            inputEtiquetaConArchivo.id = 'form_presentacion_etiqueta';
            inputEtiquetaConArchivo.removeAttribute('name');
            inputEtiquetaConArchivo.removeAttribute('onchange');
            inputEtiquetaConArchivo.classList.remove('hidden');
            inputEtiquetaConArchivo.addEventListener('change', actualizarVistaPreviaEtiquetaProducto);
            prepararLimpiezaErrorProducto(inputEtiquetaConArchivo);
            document.getElementById('presentacionEtiquetaWrapper')?.prepend(inputEtiquetaConArchivo);
        } else if (urlEtiquetaRegistrada) {
            productoEtiquetaOrigenActual = {
                id: idPresentacionOrigen || indicePresentacion,
                url: urlEtiquetaRegistrada,
                nombre: nombreEtiquetaRegistrada,
            };
        }

        quitarFilaProducto(boton);

        if (inputEtiquetaConArchivo) {
            productoEtiquetaOrigenActual = null;
            productoEtiquetaPreviewUrl = null;
            actualizarVistaPreviaEtiquetaProducto();
        } else if (productoEtiquetaOrigenActual) {
            actualizarControlEtiquetaActual(
                productoEtiquetaOrigenActual.nombre,
                true,
                productoEtiquetaOrigenActual.url
            );
        } else {
            reiniciarControlEtiquetaActual();
        }

        notificarProducto(
            'info',
            'Fila cargada',
            urlEtiquetaRegistrada ?
            'Revise los datos y vuelva a agregar la fila para guardar los cambios.' :
            'Revise los datos y seleccione nuevamente la imagen antes de agregar la fila corregida.'
        );
    }

    // ELIMINA UNA FILA DINÁMICA
    // recibe el botón Quitar, elimina su fila y limpia datos auxiliares que ya no se utilizan.
    // también actualiza numeración, filtros, porcentajes y resumen.
    function quitarFilaProducto(boton) {
        const fila = boton.closest('tr');
        const indicePresentacion = fila?.dataset.presentacionIndice;

        fila?.remove();

        // comprueba si otra fila de registro todavía utiliza la misma presentación.
        const presentacionSigueEnUso = indicePresentacion !== undefined &&
            document.querySelector(
                `#tablaRegistrosPresentacionesProducto tr[data-presentacion-indice="${indicePresentacion}"]`);

        if (indicePresentacion !== undefined && !presentacionSigueEnUso) {
            document.getElementById(`presentacion_oculta_${indicePresentacion}`)?.remove();
            document.querySelector(`#form_presentacion_catalogo option[value="TEMP-${indicePresentacion}"]`)?.remove();

            if (productoEtiquetaUrls[indicePresentacion]) {
                URL.revokeObjectURL(productoEtiquetaUrls[indicePresentacion].url);
                delete productoEtiquetaUrls[indicePresentacion];
            }
        }

        asegurarFilaVaciaRegistrosPresentacionesProducto();
        reenumerarFilasProducto('tablaRegistrosPresentacionesProducto', 'sinRegistrosPresentacionesProducto');
        actualizarTotalPorcentajeIngredientesProducto();
        filtrarRegistrosPresentacionesProducto();
        actualizarResumenProducto();
    }

    // FILTRA VISUALMENTE PRESENTACIONES Y REGISTROS
    // aplica el texto de búsqueda sin eliminar datos del formulario.
    function filtrarRegistrosPresentacionesProducto() {
        const tabla = document.getElementById('tablaRegistrosPresentacionesProducto');
        const buscador = document.getElementById('buscarRegistrosPresentacionesProducto');
        const contador = document.getElementById('contadorRegistrosPresentacionesProducto');

        if (!tabla) {
            return;
        }

        const textoBuscado = normalizarTextoProducto(buscador?.value || '');
        const filas = Array.from(tabla.querySelectorAll('tr:not(#sinRegistrosPresentacionesProducto)'));
        let filasVisibles = 0;

        filas.forEach((fila) => {
            const coincideTexto = !textoBuscado || normalizarTextoProducto(fila.textContent).includes(
                textoBuscado);

            fila.hidden = !coincideTexto;
            filasVisibles += coincideTexto ? 1 : 0;
        });

        if (contador) {
            contador.textContent = String(filasVisibles);
        }
    }

    // CONTROLA EL MENSAJE DE TABLA VACÍA
    // muestra la fila 'sin registros' únicamente cuando no existen filas agregadas.
    function asegurarFilaVaciaRegistrosPresentacionesProducto() {
        const cuerpoTabla = document.getElementById('tablaRegistrosPresentacionesProducto');

        if (!cuerpoTabla || cuerpoTabla.querySelector('tr:not(#sinRegistrosPresentacionesProducto)')) {
            return;
        }

        const columnas = 6;
        cuerpoTabla.innerHTML = `
                <tr id="sinRegistrosPresentacionesProducto">
                    <td colspan="${columnas}">
                        <div class="producto-empty">
                            Todavía no agregó presentaciones.
                        </div>
                    </td>
                </tr>
            `;
    }

    // RENUMERA LAS FILAS VISIBLES DE UNA TABLA
    // idTabla indica qué tabla recorrer e idFilaVacia identifica la fila informativa que debe ignorarse.
    function reenumerarFilasProducto(idTabla, idFilaVacia) {
        document.querySelectorAll(`#${idTabla} tr:not(#${idFilaVacia})`).forEach((fila, indice) => {
            if (fila.children[0]) {
                fila.children[0].textContent = indice + 1;
            }
        });
    }

    // ABRE UN MODAL AUXILIAR
    // idModal es el id HTML del modal que debe mostrarse.


    // ABRE UN MODAL DEL FORMULARIO
    // muestra el modal indicado y limpia sus campos antes de usarlo.
    function abrirModalProducto(idModal) {
        const modal = document.getElementById(idModal);

        if (!modal) {
            return;
        }

        limpiarModalProducto(modal);
        // is-open es la clase CSS utilizada por esta vista para hacer visible el modal.
        modal.classList.add('is-open');
    }

    // LIMPIA LOS CAMPOS DE UN MODAL
    // elimina valores y errores para que el modal pueda utilizarse nuevamente.
    function limpiarModalProducto(modal) {
        if (!modal) {
            return;
        }

        modal.querySelectorAll('input, textarea, select').forEach((campo) => {
            if (campo.type === 'checkbox' || campo.type === 'radio') {
                campo.checked = false;
                return;
            }

            campo.value = '';
        });


    }

    // CIERRA Y LIMPIA UN MODAL AUXILIAR
    // idModal identifica el modal que debe cerrarse.
    function cerrarModalProducto(idModal) {
        const modal = document.getElementById(idModal);

        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        limpiarModalProducto(modal);
    }

    // AGREGA UNA OPCIÓN TEMPORAL DESDE UN MODAL
    // idModal identifica qué catálogo se está creando, idSelect indica dónde se agregará e idInput contiene el nombre principal.
    // según el modal también recoge otros datos y los coloca en campos ocultos para enviarlos al controlador.
    function agregarOpcionTemporalProducto(idModal, idSelect, idInput) {
        const input = document.getElementById(idInput);
        const select = document.getElementById(idSelect);
        const textoBase = input?.value.trim() || '';

        if (!textoBase || !select) {
            const mensaje = idSelect === 'form_id_clasificacion_toxicologica' ?
                'Ingrese el código para agregarlo.' :
                'Ingrese un nombre para agregarlo.';
            marcarErrorProducto(input, mensaje);
            return;
        }

        // TEMP-* identifica una opción que todavía no existe en base de datos.
        const valorTemporal = `TEMP-${Date.now()}`;
        let textoVisible = textoBase;
        let textoUnidad = textoBase;

        if (idSelect === 'form_id_fabricante') {
            const razonSocialInput = document.getElementById('modal_fabricante_razon_social');
            const razonSocial = razonSocialInput?.value.trim() || '';
            const normalizar = (valor) => String(valor || '').trim().toLocaleUpperCase('es');
            const opcionesRegistradas = Array.from(select.options).filter((opcion) => opcion.dataset.temporal !== '1');
            const nombreRepetido = opcionesRegistradas.some((opcion) =>
                normalizar(opcion.dataset.nombre) === normalizar(textoBase)
            );
            const razonSocialRepetida = razonSocial !== '' && opcionesRegistradas.some((opcion) =>
                normalizar(opcion.dataset.razonSocial) === normalizar(razonSocial)
            );

            if (nombreRepetido) {
                marcarErrorProducto(input, 'Ya existe un fabricante con el mismo nombre. Selecciónelo del listado.');
                return;
            }

            if (razonSocialRepetida) {
                marcarErrorProducto(razonSocialInput,
                    'Ya existe un fabricante con la misma razón social. Selecciónelo del listado.');
                return;
            }

            document.getElementById('form_fabricante_temporal_nombre').value = textoBase;
            document.getElementById('form_fabricante_temporal_razon_social').value = razonSocial;
            document.getElementById('form_fabricante_temporal_descripcion').value =
                document.getElementById('modal_fabricante_descripcion')?.value.trim() || '';
            // En el selector se identifica al fabricante por su razón social.
            textoVisible = razonSocial || textoBase;
        }

        if (idSelect === 'form_id_clasificacion_toxicologica') {
            const descripcion = document.getElementById('modal_clasificacion_toxicologica_descripcion')?.value.trim() ||
                '';
            const normalizar = (valor) => String(valor || '').trim().toLocaleUpperCase('es');
            const descripcionInput = document.getElementById('modal_clasificacion_toxicologica_descripcion');

            if (!descripcion) {
                marcarErrorProducto(descripcionInput, 'Ingrese la descripción de la clasificación toxicológica.');
                return;
            }

            const combinacionRepetida = Array.from(select.options).some((opcion) =>
                opcion.value &&
                normalizar(opcion.dataset.codigo) === normalizar(textoBase) &&
                normalizar(opcion.dataset.descripcion) === normalizar(descripcion)
            );

            if (combinacionRepetida) {
                marcarErrorProducto(
                    descripcionInput,
                    'Ya existe una clasificación toxicológica con el mismo código y descripción. Selecciónela del listado.'
                );
                return;
            }

            document.getElementById('form_clasificacion_toxicologica_temporal_codigo').value = textoBase;
            document.getElementById('form_clasificacion_toxicologica_temporal_descripcion').value = descripcion;
            textoVisible = descripcion ? `${textoBase} - ${descripcion}` : textoBase;
        }

        if (idSelect === 'form_id_clasificacion_quimica') {
            const normalizar = (valor) => String(valor || '').trim().toLocaleUpperCase('es');
            const nombreRepetido = Array.from(select.options).some((opcion) =>
                opcion.value && normalizar(opcion.dataset.nombre || opcion.textContent) === normalizar(textoBase)
            );

            if (nombreRepetido) {
                marcarErrorProducto(
                    input,
                    'Ya existe una clasificación química con ese nombre. Selecciónela del listado.'
                );
                return;
            }

            document.getElementById('form_clasificacion_quimica_temporal_nombre').value = textoBase;
            document.getElementById('form_clasificacion_quimica_temporal_descripcion').value =
                document.getElementById('modal_clasificacion_quimica_descripcion')?.value.trim() || '';
        }

        if (idSelect === 'form_ingrediente_select') {
            const normalizar = (valor) => String(valor || '').trim().toLocaleUpperCase('es');
            const nombreRepetido = Array.from(select.options).some((opcion) =>
                opcion.value && opcion.dataset.temporal !== '1' &&
                normalizar(opcion.dataset.nombre || opcion.textContent) === normalizar(textoBase)
            );

            if (nombreRepetido) {
                marcarErrorProducto(input, 'Ya existe un ingrediente con ese nombre. Selecciónelo del listado.');
                return;
            }
        }

        if (idSelect === 'form_presentacion_unidad' || idSelect === 'form_registro_unidad') {
            const abreviatura =
                document.getElementById('modal_unidad_abreviatura')?.value.trim() || '';

            textoVisible = abreviatura ?
                `${textoBase} (${abreviatura})` :
                textoBase;

            textoUnidad = textoVisible;

            // Determina qué grupo temporal corresponde.
            const prefijoTemporal = idSelect === 'form_presentacion_unidad' ?
                'form_presentacion_unidad_temporal' :
                'form_registro_unidad_temporal';

            // Guarda SOLO los datos temporales del select que abrió el modal.
            document.getElementById(`${prefijoTemporal}_id`).value = valorTemporal;
            document.getElementById(`${prefijoTemporal}_nombre`).value = textoBase;
            document.getElementById(`${prefijoTemporal}_abreviatura`).value = abreviatura;

            // Trabaja SOLO con el select que abrió "+ Nueva unidad".
            const unidadSelect = document.getElementById(idSelect);

            if (!unidadSelect) {
                return;
            }

            // Elimina solamente la opción temporal de ESTE select.
            unidadSelect
                .querySelectorAll('option[data-temporal="1"]')
                .forEach((opcionTemporal) => opcionTemporal.remove());

            // Crea la nueva unidad únicamente en ESTE select.
            const opcionUnidad = new Option(
                textoVisible,
                valorTemporal,
                true,
                true
            );

            opcionUnidad.dataset.temporal = '1';
            opcionUnidad.dataset.texto = textoUnidad;

            unidadSelect.add(opcionUnidad);

            // Autoselecciona únicamente el select correspondiente.
            sincronizarBuscadorProducto(
                unidadSelect,
                valorTemporal
            );

            limpiarErrorProducto(unidadSelect);
            limpiarErrorProducto(input);

            cerrarModalProducto(idModal);
            actualizarResumenProducto();

            return;
        }

        select.querySelectorAll('option[data-temporal="1"]').forEach((opcionTemporal) => opcionTemporal.remove());

        const opcion = new Option(textoVisible, valorTemporal, true, true);
        opcion.dataset.temporal = '1';

        if (idSelect === 'form_id_clasificacion_quimica') {
            opcion.dataset.nombre = textoBase;
        }

        if (idSelect === 'form_id_fabricante') {
            opcion.dataset.nombre = textoBase;
            opcion.dataset.razonSocial = document.getElementById('modal_fabricante_razon_social')?.value.trim() || '';
        }

        if (idSelect === 'form_id_clasificacion_toxicologica') {
            opcion.dataset.codigo = textoBase;
            opcion.dataset.descripcion = document.getElementById('modal_clasificacion_toxicologica_descripcion')?.value
                .trim() || '';
        }

        if (idSelect === 'form_ingrediente_select') {
            opcion.dataset.nombre = textoBase;
            opcion.dataset.composicion = '';
        }

        select.add(opcion);
        sincronizarBuscadorProducto(select, valorTemporal);
        limpiarErrorProducto(select);
        limpiarErrorProducto(input);
        actualizarDetalleIngredienteProducto();
        cerrarModalProducto(idModal);
        actualizarResumenProducto();
    }

    // ACTUALIZA LOS RESÚMENES DEL FORMULARIO
    // lee los valores actuales de los campos y actualiza la información visible sin modificar los datos.


    // ACTUALIZA EL RESUMEN DEL FORMULARIO
    // muestra los datos actuales sin modificar los valores ingresados.
    function actualizarResumenProducto() {
        const nombre = document.getElementById('form_nombre_comercial')?.value || 'Sin nombre comercial';
        const codigo = document.getElementById('form_codigo')?.value || 'Sin codigo';
        const totalIngredientes = document.querySelectorAll(
            '#tablaIngredientesProducto tr:not(#sinIngredientesProducto)').length;
        const totalPresentaciones = indicesPresentacionesResumenProducto().length;
        const totalRegistros = document.querySelectorAll(
            '#tablaRegistrosPresentacionesProducto tr:not(#sinRegistrosPresentacionesProducto)').length;
        const productoCompleto = document.getElementById('form_nombre_comercial')?.value &&
            document.getElementById('form_tipo_producto')?.value &&
            document.getElementById('form_id_fabricante')?.value &&
            document.getElementById('form_id_clasificacion_toxicologica')?.value;

        ponerTextoProducto('resumenNombreProducto', nombre);
        ponerTextoProducto('resumenCodigoProducto', codigo);
        ponerTextoProducto('resumenIngredientes', totalIngredientes);
        ponerTextoProducto('resumenPresentacionesProducto', totalPresentaciones);
        ponerTextoProducto('resumenRegistrosProducto', totalRegistros);
        ponerTextoProducto('revisionProductoImportador', textoSelectProducto('form_id_importador_persona'));
        ponerTextoProducto('revisionProductoPais', textoSelectProducto('form_id_territorio_pais'));
        ponerTextoProducto('revisionProductoFabricante', textoSelectProducto('form_id_fabricante'));
        ponerTextoProducto('revisionProductoTipo', textoSelectProducto('form_id_clasificacion_toxicologica'));

        // Las presentaciones ya agregadas mantienen visible el producto actualizado.
        document.querySelectorAll('.presentacion-producto-nombre').forEach((elemento) => {
            elemento.textContent = nombre;
        });

        document.querySelectorAll('.presentacion-producto-codigo').forEach((elemento) => {
            elemento.textContent = codigo;
        });

        ponerTextoProducto('estadoProductoPrincipal', productoCompleto ? 'Completo' : 'Pendiente');
        ponerTextoProducto('estadoProductoIngredientes', totalIngredientes);
        ponerTextoProducto('estadoProductoPresentaciones', totalPresentaciones);
        ponerTextoProducto('estadoProductoRegistros', totalRegistros);

        const avance = ((productoPasoActual + 1) / document.querySelectorAll('[data-producto-step]').length) * 100;
        const barraProgreso = document.getElementById('productoProgresoBarra');

        if (barraProgreso) {
            barraProgreso.style.width = `${avance}%`;
        }
        actualizarRevisionFinalProducto();
    }

    // COLOCA TEXTO EN UN ELEMENTO DEL RESUMEN
    // idElemento identifica el destino y valor es el texto que debe mostrarse.
    function ponerTextoProducto(idElemento, valor) {
        const elemento = document.getElementById(idElemento);

        if (elemento) {
            elemento.textContent = valor;
        }
    }

    // OBTIENE EL TEXTO DE LA OPCIÓN SELECCIONADA
    // recibe el id de un select y devuelve el texto visible de su opción actual.
    function textoSelectProducto(idSelect) {
        const select = document.getElementById(idSelect);

        if (!select?.value) {
            return '-';
        }

        const texto = select?.options[select.selectedIndex]?.text?.trim();

        return texto || '-';
    }

    // CIERRA EL BUSCADOR DE INGREDIENTES
    // oculta el desplegable y limpia el texto escrito en su búsqueda.
    function cerrarSelectorIngredienteProducto() {
        const contenedor = document.querySelector('[data-ingrediente-combobox]');
        const disparador = document.querySelector('[data-ingrediente-trigger]');
        const buscador = document.querySelector('[data-ingrediente-search]');

        contenedor?.classList.remove('is-open');
        disparador?.setAttribute('aria-expanded', 'false');

        if (buscador) {
            buscador.value = '';
            renderizarOpcionesIngredienteProducto();
        }
    }

    // MUESTRA LOS INGREDIENTES QUE COINCIDEN CON LA BÚSQUEDA
    // filtra el catálogo recibido desde el controlador y actualiza el select form_ingrediente_select al elegir una opción.
    function renderizarOpcionesIngredienteProducto() {
        const select = document.getElementById('form_ingrediente_select');
        const lista = document.getElementById('form_ingrediente_options');
        const buscador = document.querySelector('[data-ingrediente-search]');

        if (!select || !lista) {
            return;
        }

        const consulta = normalizarTextoProducto(buscador?.value || '');
        const opciones = Array.from(select.options).filter((opcion) => {
            if (!opcion.value) {
                return false;
            }

            const nombre = opcion.dataset.nombre || opcion.text.trim();
            return normalizarTextoProducto(nombre).includes(consulta);
        });

        if (!opciones.length) {
            lista.innerHTML = '<div class="producto-ingredient-empty">No se encontraron ingredientes.</div>';
            return;
        }

        lista.innerHTML = opciones.map((opcion) => {
            const nombre = opcion.dataset.nombre || opcion.text.trim();
            const seleccionado = select.value === opcion.value && opcion.value !== '';

            return `
                    <button type="button"
                        class="producto-ingredient-option ${seleccionado ? 'is-selected' : ''}"
                        data-ingrediente-option="${escaparHtmlProducto(opcion.value)}"
                        role="option"
                        aria-selected="${seleccionado ? 'true' : 'false'}">
                        <span class="producto-ingredient-option-copy">
                            <strong>${escaparHtmlProducto(nombre)}</strong>
                        </span>
                        ${seleccionado ? '<i class="fa-solid fa-check producto-ingredient-option-check" aria-hidden="true"></i>' : ''}
                    </button>
                `;
        }).join('');

        lista.querySelectorAll('[data-ingrediente-option]').forEach((boton) => {
            boton.addEventListener('click', () => {
                select.value = boton.dataset.ingredienteOption || '';
                // dispara manualmente change para ejecutar la misma lógica que una selección hecha por el usuario.
                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                cerrarSelectorIngredienteProducto();
            });
        });
    }

    // ACTUALIZA EL DETALLE DEL INGREDIENTE SELECCIONADO
    // mantiene sincronizado el select real, el buscador visible y la información complementaria.
    function actualizarDetalleIngredienteProducto() {
        const select = document.getElementById('form_ingrediente_select');
        const etiqueta = document.querySelector('[data-ingrediente-label]');
        const opcion = select?.options[select.selectedIndex];
        const nombre = opcion?.dataset.nombre || opcion?.text?.trim() || 'Seleccione un ingrediente';
        const botonLimpiar = document.querySelector('[data-ingrediente-clear]');

        if (!etiqueta) {
            return;
        }

        etiqueta.textContent = select?.value ? nombre : 'Seleccione un ingrediente';
        if (botonLimpiar) {
            botonLimpiar.hidden = !select?.value;
        }
        renderizarOpcionesIngredienteProducto();
    }

    // LIMPIA DATOS TEMPORALES CUANDO SE ELIGE UNA OPCIÓN EXISTENTE
    // idSelect identifica el catálogo cuyo valor cambió.
    // evita enviar al controlador datos temporales que ya no corresponden a la selección actual.
    function limpiarCatalogoTemporalProducto(idSelect) {
        const select = document.getElementById(idSelect);
        const opcion = select?.options[select.selectedIndex];

        if (opcion?.dataset.temporal === '1') {
            return;
        }

        if (idSelect === 'form_id_fabricante') {
            document.getElementById('form_fabricante_temporal_nombre').value = '';
            document.getElementById('form_fabricante_temporal_razon_social').value = '';
            document.getElementById('form_fabricante_temporal_descripcion').value = '';
        }

        if (idSelect === 'form_id_clasificacion_toxicologica') {
            document.getElementById('form_clasificacion_toxicologica_temporal_descripcion').value = '';
            document.getElementById('form_clasificacion_toxicologica_temporal_codigo').value = '';
        }
    }

    // OBTIENE EL VALOR ACTUAL DE UN CAMPO PARA EL RESUMEN
    // selector es un selector CSS; devuelve texto vacío cuando el elemento no existe.

    function valorResumenProducto(selector) {
        const campo = document.querySelector(selector);
        return campo ? String(campo.value || '').trim() : '';
    }

    // PREPARA UN VALOR PARA MOSTRARLO EN LA REVISIÓN
    // usa un guion cuando el valor recibido está vacío.
    function textoResumenProducto(valor) {
        return valor && String(valor).trim() !== '' ? valor : '-';
    }

    // CREA UN ELEMENTO DEL RESUMEN FINAL
    // titulo identifica el dato, valor es su contenido y anchoCompleto indica si ocupa toda la fila.
    // devuelve HTML; no modifica datos del formulario.
    function itemResumenProducto(titulo, valor, anchoCompleto = false) {
        const textoSeguro = escaparHtmlProducto(textoResumenProducto(valor)).replaceAll('\n', '<br>');

        return `
                <div class="producto-review-row ${anchoCompleto ? 'is-wide' : ''}">
                    <dt>${escaparHtmlProducto(titulo)}</dt>
                    <dd>${textoSeguro}</dd>
                </div>
            `;
    }

    // CREA UNA SECCIÓN DEL RESUMEN FINAL
    // agrupa el título y su contenido en un bloque visual.
    function grupoResumenProducto(titulo, contenidoHtml, numero) {
        const contenidoSeguro = Array.isArray(contenidoHtml) ? contenidoHtml.join('') : (contenidoHtml || '');

        return `
                <section class="producto-review-section">
                    <div class="producto-review-section-head">
                        <div class="producto-review-section-heading">
                            <span class="producto-review-section-number">${escaparHtmlProducto(numero)}</span>
                            <h4>${escaparHtmlProducto(titulo)}</h4>
                        </div>
                    </div>
                    <div class="producto-review-list">
                        ${contenidoSeguro}
                    </div>
                </section>
            `;
    }

    // CREA UNA TABLA PARA LA REVISIÓN FINAL
    // recibe títulos de columnas, filas de datos y el mensaje que se muestra cuando no existen filas.
    function tablaResumenProducto(columnas, filas, mensajeVacio) {
        const columnasSeguras = Array.isArray(columnas) ? columnas : [];
        const filasSeguras = Array.isArray(filas) ? filas : [];

        if (!columnasSeguras.length) {
            return '';
        }

        const encabezado = columnasSeguras
            .map((columna) => `<th>${escaparHtmlProducto(columna)}</th>`)
            .join('');
        const cuerpo = filasSeguras.length ?
            filasSeguras.map((fila) => `
                    <tr>
                        ${(Array.isArray(fila) ? fila : []).map((valor) => `<td>${escaparHtmlProducto(textoResumenProducto(valor)).replaceAll('\n', '<br>')}</td>`).join('')}
                    </tr>
                `).join('') :
            `
                    <tr>
                        <td colspan="${columnasSeguras.length}">
                            <div class="producto-review-empty">${escaparHtmlProducto(mensajeVacio)}</div>
                        </td>
                    </tr>
                `;

        return `
                <div class="producto-review-table-block">
                    <div class="producto-review-table-wrap">
                        <table class="producto-review-table">
                            <thead>
                                <tr>${encabezado}</tr>
                            </thead>
                            <tbody>${cuerpo}</tbody>
                        </table>
                    </div>
                </div>
            `;
    }

    // AGRUPA INPUTS DINÁMICOS POR ÍNDICE
    // prefijo puede ser ingredientes_productos, presentaciones o registros.
    // convierte nombres como presentaciones[0][cantidad] en un objeto organizado por índice.
    function gruposPorIndiceResumenProducto(prefijo) {
        const patron = new RegExp(`^${prefijo}\\[(\\d+)\\]\\[([^\\]]+)\\]$`);
        // cada clave será el índice de la fila y su valor contendrá los campos de ese índice.
        const grupos = {};

        document.querySelectorAll(`input[name^="${prefijo}["]`).forEach((input) => {
            const coincidencia = input.name.match(patron);
            if (!coincidencia) {
                return;
            }

            const indice = coincidencia[1];
            const campo = coincidencia[2];
            grupos[indice] = grupos[indice] || {};

            if (input.type === 'file') {
                grupos[indice][campo] = input.files?.[0]?.name || grupos[indice][campo] || '';
                return;
            }

            grupos[indice][campo] = input.value;
        });

        return grupos;
    }

    // OBTIENE LOS ÍNDICES DE LAS PRESENTACIONES ACTUALES
    // devuelve los índices existentes para poder relacionarlos con registros y etiquetas.
    function indicesPresentacionesResumenProducto() {
        return Object.keys(gruposPorIndiceResumenProducto('presentaciones'));
    }

    // OBTIENE LOS DATOS DE ETIQUETA PARA LA REVISIÓN
    // recibe el índice y datos de una presentación, y devuelve la URL/nombre disponible para mostrar.
    function datosEtiquetaResumenProducto(indice, presentacion = {}) {
        const input = document.querySelector(`input[name="presentaciones[${indice}][url_etiqueta]"]`);
        const datosOld = input?.id ? productoEtiquetaOldUrls[input.id] : null;
        const opcionCatalogo = presentacion.id_presentacion_origen ?
            document.querySelector(
                `#form_presentacion_catalogo option[value="${presentacion.id_presentacion_origen}"]`) :
            null;
        const datos = productoEtiquetaUrls[indice] || datosOld;
        const url = datos?.url || input?.dataset.urlInicial || opcionCatalogo?.dataset.etiquetaUrl || '';
        const nombre = input?.files?.[0]?.name || datos?.nombre || opcionCatalogo?.dataset.etiquetaNombre ||
            (url ? 'Imagen registrada' : '-');

        return {
            url,
            nombre
        };
    }
    // ABRE UNA ETIQUETA DESDE LA REVISIÓN FINAL
    // indice identifica la presentación cuya etiqueta debe mostrarse.
    function abrirEtiquetaResumenProducto(indice) {
        const presentaciones = gruposPorIndiceResumenProducto('presentaciones');
        const etiqueta = datosEtiquetaResumenProducto(indice, presentaciones[indice]);

        if (!etiqueta.url) {
            notificarProducto('info', 'Sin imagen', 'Esta presentación no tiene una etiqueta disponible.');
            return;
        }

        mostrarEtiquetaEnModalProducto(etiqueta.url, etiqueta.nombre);
    }

    // FORMATEA FECHAS PARA LA REVISIÓN
    // convierte una fecha YYYY-MM-DD a DD/MM/YYYY sin alterar el valor que se enviará al servidor.
    function formatearFechaRevisionProducto(valor) {
        const coincidencia = String(valor || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);

        return coincidencia ? `${coincidencia[3]}/${coincidencia[2]}/${coincidencia[1]}` :
            textoResumenProducto(valor);
    }

    // CREA EL RESUMEN DE PRESENTACIONES Y REGISTROS
    // relaciona cada presentación con sus registros y genera el HTML mostrado en el paso Revisión.
    function resumenPresentacionesRegistrosProducto(presentaciones, registros) {
        const listaPresentaciones = Object.entries(presentaciones);

        if (!listaPresentaciones.length) {
            return '<div class="producto-review-empty">-</div>';
        }

        return `<div class="producto-review-presentations">
            ${listaPresentaciones.map(([indice, presentacion], posicion) => {
                const etiqueta = datosEtiquetaResumenProducto(indice, presentacion);
                const unidadPresentacion = textoUnidadRevisionProducto(presentacion.id_catalogo_unidad);
                const registrosAsociados = Object.entries(registros)
                    .filter(([, registro]) => String(registro.id_presentacion_temporal) === String(indice));
                const vistaEtiqueta = etiqueta.url ? `
                    <button type="button" class="producto-review-image" onclick="abrirEtiquetaResumenProducto(${Number(indice)})"
                        title="Ampliar etiqueta" aria-label="Ampliar etiqueta de la presentación ${posicion + 1}">
                        <img src="${escaparHtmlProducto(etiqueta.url)}" alt="Etiqueta de la presentación ${posicion + 1}">
                        <span><i class="fa-solid fa-magnifying-glass-plus"></i></span>
                    </button>
                    <button type="button" class="producto-review-image-action"
                        onclick="abrirEtiquetaResumenProducto(${Number(indice)})">
                        <i class="fa-regular fa-eye"></i> Ver etiqueta
                    </button>` : `
                    <div class="producto-review-image is-empty">
                        <i class="fa-regular fa-image"></i>
                        <span>-</span>
                    </div>`;
                const filasRegistros = registrosAsociados.map(([, registro]) => [
                    registro.codigo_autorizacion,
                    registro.numero_lote,
                    formatearFechaRevisionProducto(registro.fecha_vigencia),
                    registro.cantidad,
                    textoUnidadRevisionProducto(registro.id_catalogo_unidad),
                ]);
                const listaRegistros = tablaResumenProducto(
                    ['Código de autorización', 'Número de lote', 'Fecha de vigencia', 'Cantidad', 'Unidad'],
                    filasRegistros,
                    '-'
                );

                return `
                    <article class="producto-review-presentation">
                        <header class="producto-review-presentation-head">
                            <h5>Presentación ${posicion + 1}</h5>
                        </header>

                        <div class="producto-review-presentation-body">
                            <div class="producto-review-visual">
                                ${vistaEtiqueta}
                                <span class="producto-review-file-name">${escaparHtmlProducto(etiqueta.nombre)}</span>
                            </div>

                            <div class="producto-review-presentation-content">
                                <dl class="producto-review-presentation-data">
                                    <div><dt>Tipo de envase</dt><dd>${escaparHtmlProducto(textoResumenProducto(presentacion.tipo_envase))}</dd></div>
                                    <div><dt>Cantidad</dt><dd>${escaparHtmlProducto(textoResumenProducto(presentacion.cantidad))}</dd></div>
                                    <div><dt>Unidad</dt><dd>${escaparHtmlProducto(textoResumenProducto(unidadPresentacion))}</dd></div>
                                    <div><dt>Descripción</dt><dd>${escaparHtmlProducto(textoResumenProducto(presentacion.descripcion))}</dd></div>
                                </dl>
                            </div>

                            <div class="producto-review-registers">
                                <div class="producto-review-registers-head">
                                    <h6>Registros</h6>
                                    <span>${registrosAsociados.length} ${registrosAsociados.length === 1 ? 'registro' : 'registros'}</span>
                                </div>
                                ${listaRegistros}
                            </div>
                        </div>
                    </article>`;
            }).join('')}
        </div>`;
    }

    // ACTUALIZA TODO EL PASO DE REVISIÓN
    // reúne datos principales, catálogos temporales, ingredientes, presentaciones, registros y etiquetas.
    // solo muestra información; no guarda ni modifica la base de datos.
    function actualizarRevisionFinalProducto() {
        const destino = document.getElementById('resumenProductoWizard');

        if (!destino) {
            return;
        }

        const fabricanteSelect = document.getElementById('form_id_fabricante');
        const fabricanteOpcion = fabricanteSelect?.selectedOptions?.[0];
        const fabricanteRazonSocial = fabricanteOpcion?.dataset.razonSocial?.trim() || '';
        const fabricanteNombre = fabricanteOpcion?.dataset.nombre?.trim() || '';
        const fabricanteVisible = fabricanteRazonSocial || fabricanteNombre || textoSelectProducto(
            'form_id_fabricante');
        const fabricanteNuevo = fabricanteOpcion?.dataset.temporal === '1';
        const clasificacionQuimicaSelect = document.getElementById('form_id_clasificacion_quimica');
        const clasificacionQuimicaOpcion = clasificacionQuimicaSelect?.selectedOptions?.[0];
        const clasificacionQuimicaNueva = clasificacionQuimicaOpcion?.dataset.temporal === '1';
        const clasificacionToxicologicaSelect = document.getElementById('form_id_clasificacion_toxicologica');
        const clasificacionToxicologicaOpcion = clasificacionToxicologicaSelect?.selectedOptions?.[0];
        const clasificacionToxicologicaNueva = clasificacionToxicologicaOpcion?.dataset.temporal === '1';
        const ingredientes = gruposPorIndiceResumenProducto('ingredientes_productos');
        const presentaciones = gruposPorIndiceResumenProducto('presentaciones');
        const registros = gruposPorIndiceResumenProducto('registros');

        const datosProducto = [
            itemResumenProducto('Código', valorResumenProducto('#form_codigo')),
            itemResumenProducto('Nombre comercial', valorResumenProducto('#form_nombre_comercial')),
            itemResumenProducto('Tipo de producto', textoSelectProducto('form_tipo_producto')),
            itemResumenProducto('Nombre técnico', valorResumenProducto('#form_nombre_tecnico')),
            itemResumenProducto('Importador', textoSelectProducto('form_id_importador_persona')),
            itemResumenProducto('País', textoSelectProducto('form_id_territorio_pais')),
        ];

        if (fabricanteNuevo) {
            const razonSocialTemporal = valorResumenProducto('#form_fabricante_temporal_razon_social');
            const nombreTemporal = valorResumenProducto('#form_fabricante_temporal_nombre');

            datosProducto.push(itemResumenProducto('Fabricante', razonSocialTemporal || nombreTemporal));

            if (
                nombreTemporal &&
                normalizarTextoProducto(nombreTemporal) !== normalizarTextoProducto(razonSocialTemporal)
            ) {
                datosProducto.push(itemResumenProducto('Nombre del fabricante', nombreTemporal));
            }

            datosProducto.push(itemResumenProducto(
                'Descripción del fabricante',
                valorResumenProducto('#form_fabricante_temporal_descripcion')
            ));
        } else {
            datosProducto.push(itemResumenProducto(
                'Fabricante',
                fabricanteVisible
            ));
        }

        if (clasificacionQuimicaNueva) {
            datosProducto.push(itemResumenProducto(
                'Clasificación química',
                valorResumenProducto('#form_clasificacion_quimica_temporal_nombre')
            ));
            datosProducto.push(itemResumenProducto(
                'Descripción química',
                valorResumenProducto('#form_clasificacion_quimica_temporal_descripcion')
            ));
        } else {
            datosProducto.push(itemResumenProducto(
                'Clasificación química',
                textoSelectProducto('form_id_clasificacion_quimica')
            ));
        }

        if (clasificacionToxicologicaNueva) {
            datosProducto.push(itemResumenProducto(
                'Clasificación toxicológica',
                valorResumenProducto('#form_clasificacion_toxicologica_temporal_codigo')
            ));
            datosProducto.push(itemResumenProducto(
                'Descripción toxicológica',
                valorResumenProducto('#form_clasificacion_toxicologica_temporal_descripcion')
            ));
        } else {
            datosProducto.push(itemResumenProducto(
                'Clasificación toxicológica',
                textoSelectProducto('form_id_clasificacion_toxicologica')
            ));
        }

        const filasIngredientes = Object.entries(ingredientes).map(([indice, ingrediente]) => {
            const nombre = ingrediente.nombre || textoResumenProducto(ingrediente.id_ingrediente);

            return [
                Number(indice) + 1,
                nombre,
                ingrediente.porcentaje !== undefined && ingrediente.porcentaje !== '' ?
                `${formatearPorcentajeIngrediente(ingrediente.porcentaje)}%` : '',
            ];
        });

        destino.innerHTML = [
            grupoResumenProducto(
                'Datos generales',
                datosProducto.join(''),
                '1'
            ),
            grupoResumenProducto(
                `Ingredientes (${Object.keys(ingredientes).length})`,
                tablaResumenProducto(
                    ['#', 'Ingrediente', 'Porcentaje'],
                    filasIngredientes,
                    '-'
                ),
                '2'
            ),
            grupoResumenProducto(
                'Presentaciones y registros vinculados',
                resumenPresentacionesRegistrosProducto(presentaciones, registros),
                '3'
            ),
        ].join('');
    }

    // GUARDA TEMPORALMENTE EL AVANCE EN EL NAVEGADOR
    // guarda campos de texto en localStorage sin enviarlos al controlador.
    // los archivos no pueden guardarse en localStorage y permanecen solo mientras la página siga abierta.


    // GUARDA EL AVANCE EN EL NAVEGADOR
    // conserva temporalmente los campos de texto sin enviarlos al controlador.
    function guardarAvanceProducto() {
        const formulario = document.getElementById('formProductoWizard');
        const datos = {};
        let tieneArchivosTemporales = false;

        // FormData recorre exactamente los campos que normalmente se enviarían al controlador.
        new FormData(formulario).forEach((valor, clave) => {
            // El navegador no puede guardar archivos en localStorage; la imagen permanece en el formulario abierto.
            if (valor instanceof File) {
                if (valor.name) {
                    tieneArchivosTemporales = true;
                    datos[`${clave}_nombre_temporal`] = valor.name;
                }

                return;
            }

            if (datos[clave]) {
                datos[clave] = Array.isArray(datos[clave]) ? [...datos[clave], valor] : [datos[clave], valor];
                return;
            }

            datos[clave] = valor;
        });

        // localStorage guarda el avance solo en este navegador; no registra nada en la base de datos.
        localStorage.setItem('producto.formulario.avance', JSON.stringify({
            paso: productoPasoActual,
            datos,
            guardado_en: new Date().toISOString(),
        }));

        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Avance guardado',
                text: tieneArchivosTemporales ?
                    'Los datos se guardaron. Las imágenes permanecen en esta página hasta guardar el producto; no recargue la vista.' :
                    'El avance se guardo temporalmente en este navegador.',
                timer: 1400,
                showConfirmButton: false,
            });
            return;
        }

        notificarProducto(
            'success',
            'Avance guardado',
            tieneArchivosTemporales ?
            'Las imágenes permanecen en esta página hasta guardar el producto; no recargue la vista.' :
            'El avance se guardo temporalmente en este navegador.'
        );
    }

    // IDENTIFICA EL PASO QUE CONTIENE EL PRIMER ERROR
    // recibe los errores devueltos por Laravel y devuelve 0, 1 o 2 según el campo que debe corregirse.
    function pasoConPrimerErrorProducto(errores) {
        const primerCampo = Object.keys(errores || {})[0] || '';

        if (primerCampo.startsWith('ingredientes_productos')) {
            return 1;
        }

        if (primerCampo.startsWith('presentaciones') || primerCampo.startsWith('registros')) {
            return 2;
        }

        return 0;
    }

    // CONVIERTE EL NOMBRE DE ERROR DE LARAVEL AL NAME DEL HTML
    // Ejemplo: presentaciones.0.cantidad se convierte en presentaciones[0][cantidad].
    function nombreHtmlCampoProducto(campo) {
        const partes = String(campo || '').split('.');
        return partes.slice(1).reduce((nombre, parte) => `${nombre}[${parte}]`, partes[0] || '');
    }

    // MUESTRA LOS ERRORES DEVUELTOS POR EL CONTROLADOR
    // recibe el objeto errors de una respuesta HTTP 422, marca los campos y abre el paso correspondiente.
    // no borra los datos que el usuario ya había ingresado.
    function mostrarErroresGuardadoProducto(errores) {
        const resumen = document.getElementById('productoResumenErrores');
        const lista = document.getElementById('productoListaErrores');
        const entradas = Object.entries(errores || {});

        document.querySelectorAll('.producto-field-error').forEach((error) => error.remove());
        document.querySelectorAll('.is-invalid').forEach((campo) => campo.classList.remove('is-invalid'));

        if (resumen && lista) {
            lista.innerHTML = entradas
                .flatMap(([, mensajes]) => mensajes)
                .map((mensaje) => `<li>${escaparHtmlProducto(mensaje)}</li>`)
                .join('');
            resumen.classList.toggle('hidden', entradas.length === 0);
        }

        entradas.forEach(([campo, mensajes]) => {
            const nombreCampo = nombreHtmlCampoProducto(campo);
            const elemento = document.getElementById(campo) ||
                document.querySelector(`[name="${CSS.escape(nombreCampo)}"]`);

            if (elemento) {
                marcarErrorProducto(elemento, mensajes[0]);
            }
        });

        mostrarPasoProducto(pasoConPrimerErrorProducto(errores));
        resumen?.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    // MUESTRA U OCULTA LA CAPA DE ESPERA
    // mostrar=true bloquea la interfaz mientras se guarda; false vuelve a habilitarla.
    function mostrarEsperaGuardadoProducto(mostrar) {
        const overlay = document.getElementById('productoGuardandoOverlay');

        if (!overlay) {
            return;
        }

        overlay.classList.toggle('hidden', !mostrar);
        overlay.classList.toggle('flex', mostrar);
        overlay.setAttribute('aria-hidden', mostrar ? 'false' : 'true');
    }

    // ENVÍA EL FORMULARIO AL CONTROLADOR SIN RECARGAR LA PÁGINA
    // usa FormData y fetch para enviar todos los campos, incluidos archivos.
    // si Laravel responde 422 muestra los errores; si guarda correctamente redirige o avisa al trámite padre.
    async function enviarProductoSinPerderDatos(event) {
        event.preventDefault();

        const formulario = event.currentTarget;
        const boton = document.getElementById('btnProductoSiguiente');
        const contenidoBoton = boton?.innerHTML;
        let mantenerEsperaHastaNavegar = false;

        if (formulario.dataset.enviando === '1') {
            return;
        }

        formulario.dataset.enviando = '1';
        mostrarEsperaGuardadoProducto(true);
        if (boton) {
            boton.disabled = true;
            boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
        }

        try {
            // envía el formulario a la misma ruta definida en action, pero sin recargar la página.
            const respuesta = await fetch(formulario.action, {
                method: formulario.method || 'POST',
                // incluye inputs normales, arrays dinámicos y archivos seleccionados.
                body: new FormData(formulario),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            // intenta leer la respuesta JSON; si no existe JSON utiliza un objeto vacío para evitar otro error.
            const resultado = await respuesta.json().catch(() => ({}));

            if (respuesta.status === 422) {
                mostrarErroresGuardadoProducto(resultado.errors || {});
                return;
            }

            if (!respuesta.ok) {
                throw new Error(resultado.message || 'No fue posible guardar el producto.');
            }

            if (!resultado.redirect) {
                throw new Error('No se recibió la dirección para continuar.');
            }

            mantenerEsperaHastaNavegar = true;
            window.location.assign(resultado.redirect);
        } catch (error) {
            notificarProducto('error', 'No se pudo guardar', error.message);
        } finally {
            if (!mantenerEsperaHastaNavegar) {
                formulario.dataset.enviando = '0';
                mostrarEsperaGuardadoProducto(false);

                if (boton && document.body.contains(boton)) {
                    boton.disabled = false;
                    boton.innerHTML = contenidoBoton;
                }
            }
        }
    }

    // Inicializa eventos del wizard cuando la vista termina de cargar.

    // INICIALIZA EL FORMULARIO
    // registra los eventos cuando la página termina de cargar.
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('formProductoWizard')?.addEventListener('submit', enviarProductoSinPerderDatos);

        document.querySelectorAll('[data-producto-ir]').forEach((boton) => {
            boton.addEventListener('click', () => {
                const pasoDestino = Number(boton.dataset.productoIr);
                mostrarPasoProducto(pasoDestino);
            });
        });

        ['form_nombre_comercial', 'form_tipo_producto', 'form_codigo', 'form_id_importador_persona',
            'form_id_fabricante', 'form_id_territorio_pais', 'form_id_clasificacion_quimica',
            'form_id_clasificacion_toxicologica'
        ].forEach((
            id) => {
            document.getElementById(id)?.addEventListener('input', () => {
                filtrarPresentacionesCatalogoProducto();
                actualizarResumenProducto();
            });
            document.getElementById(id)?.addEventListener('change', () => {
                limpiarCatalogoTemporalProducto(id);
                filtrarPresentacionesCatalogoProducto();
                actualizarResumenProducto();
            });
        });

        document.querySelector('[data-ingrediente-trigger]')?.addEventListener('click', () => {
            const contenedor = document.querySelector('[data-ingrediente-combobox]');
            const estaAbierto = contenedor?.classList.toggle('is-open');
            const buscador = document.querySelector('[data-ingrediente-search]');

            document.querySelector('[data-ingrediente-trigger]')?.setAttribute(
                'aria-expanded',
                estaAbierto ? 'true' : 'false'
            );

            if (estaAbierto) {
                renderizarOpcionesIngredienteProducto();
                requestAnimationFrame(() => buscador?.focus());
            }
        });

        document.querySelector('[data-ingrediente-search]')?.addEventListener('input', () => {
            renderizarOpcionesIngredienteProducto();
        });

        document.querySelector('[data-ingrediente-search]')?.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            cerrarSelectorIngredienteProducto();
            document.querySelector('[data-ingrediente-trigger]')?.focus();
        });

        document.querySelector('[data-ingrediente-close]')?.addEventListener('click', (event) => {
            event.stopPropagation();
            cerrarSelectorIngredienteProducto();
            document.querySelector('[data-ingrediente-trigger]')?.focus();
        });

        document.querySelector('[data-ingrediente-clear]')?.addEventListener('click', (event) => {
            event.stopPropagation();
            const select = document.getElementById('form_ingrediente_select');

            if (select) {
                select.value = '';
                // dispara manualmente change para ejecutar la misma lógica que una selección hecha por el usuario.
                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            cerrarSelectorIngredienteProducto();
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-ingrediente-combobox]')) {
                cerrarSelectorIngredienteProducto();
            }

            if (!event.target.closest('[data-producto-search-select]')) {
                cerrarBuscadoresCatalogoProducto();
            }
        });

        inicializarBuscadoresProducto();

        const selectorPresentacionRegistro = document.querySelector('[data-producto-selector-registro]');
        const camposRegistro = document.querySelector('.producto-registro-grid-autorizacion');

        if (selectorPresentacionRegistro && camposRegistro) {
            camposRegistro.prepend(selectorPresentacionRegistro);
        }

        document.getElementById('form_ingrediente_select')?.addEventListener('change',
            actualizarDetalleIngredienteProducto);
        actualizarDetalleIngredienteProducto();

        actualizarTotalPorcentajeIngredientesProducto();

        document.getElementById('form_presentacion_catalogo')?.addEventListener('change',
            autocompletarPresentacionProducto);
        filtrarPresentacionesCatalogoProducto();

        document.getElementById('buscarRegistrosPresentacionesProducto')?.addEventListener(
            'input',
            filtrarRegistrosPresentacionesProducto
        );
        filtrarRegistrosPresentacionesProducto();

        // Todos los controles del wizard limpian su error apenas el usuario corrige el dato.
        document.querySelectorAll(
                '.producto-input, .producto-select, .producto-textarea, .producto-upload-input')
            .forEach((campo) => prepararLimpiezaErrorProducto(campo));

        const tieneErroresServidor = Object.keys(productoErroresLaravel).length > 0;
        mostrarPasoProducto(tieneErroresServidor ? pasoConPrimerErrorProducto(productoErroresLaravel) : 0);

        // Errores devueltos por Laravel despues del submit: se pintan en el campo sin perder old().
        Object.entries(productoErroresLaravel).forEach(([campo, mensajes]) => {
            const idCampo = campo.replaceAll('.', '_');
            const elemento = document.getElementById(campo) || document.getElementById(idCampo);

            if (elemento) {
                marcarErrorProducto(elemento, mensajes[0]);
            }
        });
    });

</script>
</x-admin-layout>

