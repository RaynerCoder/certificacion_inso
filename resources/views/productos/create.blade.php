@php
    // Modo embebido: permite usar solo el formulario dentro de otro flujo, sin cargar el layout completo.
    $productoEmbebido = $productoEmbebido ?? request()->boolean('embebido');
    $productoEnEdicion = $producto ?? null;
    $modoEdicionProducto = filled($productoEnEdicion);

    /*
     * Origen del formulario:
     * - Desde Productos: vuelve al listado.
     * - Desde un trámite: vuelve al detalle del trámite que abrió este formulario.
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

@if (!$productoEmbebido)
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
@endif

@php
    /*
     * Catalogos para armar la vista.
     * Cuando se implemente el guardado se pueden mover estas consultas al controlador.
     */
    $clasificacionesToxicologicasCatalogo =
        $clasificacionesToxicologicas ??
        \App\Models\ClasificacionToxicologica::query()->where('estado', 'ACTIVO')->orderBy('codigo')->get();

    $fabricantesCatalogo =
        $fabricantes ??
        \App\Models\Fabricante::query()
            ->where('estado', 'ACTIVO')
            ->orderByRaw('CASE WHEN razon_social IS NULL OR razon_social = ? THEN 1 ELSE 0 END', [''])
            ->orderBy('razon_social')
            ->orderBy('nombre')
            ->get();

    /*
     * Para productos se guarda id_territorio_pais, por eso este catalogo
     * muestra solo territorios cuyo ambito es Pais.
     */
    $territoriosCatalogo = isset($territorios)
        ? collect($territorios)->where('id_ambito', 1)->sortBy('nombre')->values()
        : \App\Models\Territorio::query()->where('estado', 'ACTIVO')->where('id_ambito', 1)->orderBy('nombre')->get();

    $importadoresCatalogo =
        $importadores ??
        \App\Models\Persona::with(['natural', 'empresa'])
            ->where('estado', 'ACTIVO')
            ->orderBy('id')
            ->get();

    $ingredientesCatalogo =
        $ingredientes ?? \App\Models\Ingrediente::query()->where('estado', 'ACTIVO')->orderBy('nombre')->get();

    $clasificacionesQuimicasCatalogo =
        $clasificacionesQuimicas ??
        \App\Models\ClasificacionQuimica::query()->where('estado', 'ACTIVO')->orderBy('nombre')->get();

    $catalogosMedidas =
        $catalogosMedidas ??
        \App\Models\CatalogoMedida::query()->where('estado', 'ACTIVO')->orderBy('tipo')->orderBy('nombre')->get();

    $catalogosUnidades = $catalogosMedidas->where('tipo', 'unidad de medida')->values();

    $nombreCatalogoUnidad = function ($idCatalogoUnidad) use ($catalogosMedidas) {
        $catalogo = $catalogosMedidas->firstWhere('id', (int) $idCatalogoUnidad);

        if (!$catalogo) {
            return 'Sin unidad';
        }

        return trim($catalogo->nombre . ($catalogo->abreviatura ? ' (' . $catalogo->abreviatura . ')' : ''));
    };

    /*
     * Catalogo usado solo como apoyo visual para reutilizar presentaciones ya registradas.
     * La vista las filtra por importador y por el codigo/nombre del producto actual.
     */
    $presentacionesCatalogo =
        $presentacionesCatalogo ??
        \App\Models\Presentacion::with(['producto', 'catalogoUnidad'])
            ->where('estado', 'ACTIVO')
            ->whereHas('producto', fn($consulta) => $consulta->where('estado', 'ACTIVO'))
            ->orderByDesc('id')
            ->get();

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

    // Atajos de vista: pintan errores de Laravel sin repetir logica en cada campo.
    $claseErrorProducto = fn(string $campo) => $errors->has($campo) ? ' is-invalid' : '';
    $ingredientesOld = old('ingredientes_productos', $ingredientesIniciales ?? []);
    $presentacionesOld = old('presentaciones', $presentacionesIniciales ?? []);
    $registrosOld = old('registros', $registrosIniciales ?? []);

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

{{-- En tramite se usa el formulario preparado en seguimientos/producto. --}}
@include($productoEmbebido ? 'seguimientos_certificados.producto.estilos' : 'productos.create.estilos')

@if ($productoEmbebido)
    <style>
        /* Ajuste visual solo para el formulario cargado dentro de Nuevo Trámite. */
        body {
            margin: 0;
            background: #ffffff;
        }

        .producto-wizard {
            gap: 12px;
        }
    </style>
@endif

@if (session('success'))
    {{-- Mensaje de guardado: en modo embebido confirma que el producto ya fue creado. --}}
    <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
        {{ session('success') }}
    </div>
@endif

@if ($productoEmbebido && session('producto_creado_tramite'))
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

    <script>
        /*
         * Avisa al formulario de tramite que el producto ya fue guardado.
         * El parent solo usa estos datos para mostrar un resumen visual al usuario.
         */
        window.parent?.postMessage({
            tipo: 'producto-registrado-tramite',
            producto: @json(session('producto_creado_tramite')),
        }, window.location.origin);
    </script>
@endif

<form id="formProductoWizard"
    action="{{ $modoEdicionProducto ? route('productos_update', $productoEnEdicion) : route('productos_store', $productoEmbebido ? ['embebido' => 1] : []) }}"
    method="POST" enctype="multipart/form-data" class="producto-wizard" autocomplete="off"
    @if ($productoEmbebido) target="productoSubmitFrame" @endif>
    @csrf
    @if ($modoEdicionProducto)
        @method('PUT')
    @endif

    {{-- Datos temporales de catalogos creados desde modales; el controlador los usa al guardar el producto. --}}
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

    {{-- Unidad temporal de PRESENTACIÓN --}}
    <input type="hidden" id="form_presentacion_unidad_temporal_id" name="form_presentacion_unidad_temporal_id"
        value="{{ old('form_presentacion_unidad_temporal_id') }}">

    <input type="hidden" id="form_presentacion_unidad_temporal_nombre" name="form_presentacion_unidad_temporal_nombre"
        value="{{ old('form_presentacion_unidad_temporal_nombre') }}">

    <input type="hidden" id="form_presentacion_unidad_temporal_abreviatura"
        name="form_presentacion_unidad_temporal_abreviatura"
        value="{{ old('form_presentacion_unidad_temporal_abreviatura') }}">

    {{-- Unidad temporal de REGISTRO --}}
    <input type="hidden" id="form_registro_unidad_temporal_id" name="form_registro_unidad_temporal_id"
        value="{{ old('form_registro_unidad_temporal_id') }}">

    <input type="hidden" id="form_registro_unidad_temporal_nombre" name="form_registro_unidad_temporal_nombre"
        value="{{ old('form_registro_unidad_temporal_nombre') }}">

    <input type="hidden" id="form_registro_unidad_temporal_abreviatura"
        name="form_registro_unidad_temporal_abreviatura"
        value="{{ old('form_registro_unidad_temporal_abreviatura') }}">

    {{-- Origen del flujo: permite volver a la pantalla que abrió Producto y relacionarlo al trámite. --}}
    <input type="hidden" name="form_id_certificado" value="{{ $productoCertificadoOrigen }}">
    <input type="hidden" name="form_bandeja" value="{{ $productoBandejaOrigen }}">
    <input type="hidden" name="form_retorno" value="{{ $productoRetornoSeguro }}">

    @unless ($productoEmbebido)
        {{-- Encabezado principal: se oculta cuando Producto se carga dentro del trámite. --}}
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

        {{-- Burbujas horizontales: en modo embebido las burbujas las maneja el trámite. --}}
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
                    <span>Registro y presentación</span>
                </button>
                <button type="button" class="producto-burbuja" data-producto-ir="3">
                    <span class="producto-circulo">4</span>
                    <span>Revisión</span>
                </button>
            </div>
        </div>
    @endunless

    @if ($errors->any())
        {{-- Resumen de errores del servidor: aparece despues del submit y conserva old() en los campos. --}}
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="text-sm font-black">No se pudo guardar. Revise estos datos:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                @include(
                    $productoEmbebido
                        ? 'seguimientos_certificados.producto.producto'
                        : 'productos.create.producto')

                {{-- Paso 2: tabla pivote ingredientes_productos. --}}
                @include(
                    $productoEmbebido
                        ? 'seguimientos_certificados.producto.ingredientes'
                        : 'productos.create.ingredientes')

                {{-- Paso 3: presentaciones del producto. --}}
                @include(
                    $productoEmbebido
                        ? 'seguimientos_certificados.producto.presentaciones'
                        : 'productos.create.presentaciones')

                {{-- Los registros se agregan dentro del paso de presentaciones para mantener la relacion. --}}
                {{-- @include('productos.create.registros') --}}

                {{-- Paso 4: resumen antes de guardar producto. --}}
                @include(
                    $productoEmbebido
                        ? 'seguimientos_certificados.producto.revision'
                        : 'productos.create.revision')

            </div>

            {{-- Botones inferiores del wizard. --}}
            <div class="producto-action-bar">
                <div></div>

                <div class="producto-action-buttons">
                    @if (!$productoEmbebido)
                        {{-- Sale del wizard sin enviar datos al controlador. --}}
                        <a href="{{ $productoRetornoSeguro }}" class="producto-btn producto-btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i>
                            Salir sin guardar
                        </a>
                    @endif

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

@if ($productoEmbebido)
    {{-- El producto se guarda en un iframe oculto para no sacar al usuario del trámite actual. --}}
    <iframe name="productoSubmitFrame" class="hidden" title="Guardado de producto"></iframe>
@endif

{{-- Modal visual para fabricante. No guarda en base de datos todavia; solo permite dejar preparada la vista. --}}
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

{{-- Permite agregar una clasificación toxicológica sin salir del formulario. --}}
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
                <label class="producto-field-label" for="modal_clasificacion_toxicologica_descripcion">Descripción
                    <span class="font-normal text-slate-400">(opcional)</span></label>
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

{{-- Permite agregar una clasificación química sin salir del formulario. --}}
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

{{-- Modal visual para unidad de medida. --}}
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

{{-- Modal visual para ingrediente. --}}
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

            <button type="button" class="producto-modal-close" onclick="cerrarModalEtiquetaProducto()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <img id="etiquetaImagenPreview" class="producto-image-preview-modal"
            alt="Vista previa de la etiqueta">
    </div>
</div>

<script>
    // Guarda el paso actual del wizard para mostrar una sola seccion a la vez.
    let productoPasoActual = 0;

    // Contadores usados para crear nombres de arrays dinamicos sin repetir indices.
    let productoIngredienteIndice = {{ count($ingredientesOld) }};
    let productoPresentacionIndice = {{ count($presentacionesOld) }};
    let productoRegistroIndice = {{ count($registrosOld) }};
    let productoRegistroEditandoId = null;
    let productoEtiquetaPreviewUrl = null;
    let productoEtiquetaOrigenActual = null;
    const productoEtiquetaUrls = {};
    const productoEtiquetaOldUrls = {};
    const productoErroresLaravel = @json($errors->messages());
    const productoEmbebidoTramite = @json($productoEmbebido);
    const productoModoEdicion = @json($modoEdicionProducto);

    // Entradas del formulario separadas por modulo para evitar mezclar ids entre producto, ingredientes y registro.
    const productoCamposFormulario = {
        producto: {
            codigo: 'form_codigo',
            nombreComercial: 'form_nombre_comercial',
            fabricante: 'form_id_fabricante',
            clasificacionToxicologica: 'form_id_clasificacion_toxicologica',
        },
        ingrediente: {
            select: 'form_ingrediente_select',
            porcentaje: 'form_ingrediente_porcentaje',
            estado: 'form_ingrediente_estado',
        },
        presentacion: {
            cantidad: 'form_presentacion_cantidad',
            unidad: 'form_presentacion_unidad',
            etiqueta: 'form_presentacion_etiqueta',
            descripcion: 'form_presentacion_descripcion',
            estado: 'form_presentacion_estado',
        },
        registro: {
            codigoAutorizacion: 'form_registro_codigo_autorizacion',
            numeroLote: 'form_registro_numero_lote',
            fechaVigencia: 'form_registro_fecha_vigencia',
            cantidad: 'form_registro_cantidad',
            unidad: 'form_registro_unidad',
            estado: 'form_registro_estado',
        },
    };

    const productoTitulos = [
        ['Datos principales del producto', 'Identifique el producto, importador, fabricante y tipo.'],
        ['Ingredientes', 'Agregue los ingredientes y sus porcentajes.'],
        ['Registro y presentación', 'Registre la autorización y el formato comercial del producto.'],
        ['Revisión', 'Revise la información antes de guardar el producto.'],
    ];

    const productoColoresPaso = ['is-blue', 'is-teal', 'is-amber', 'is-teal'];

    // Muestra el paso solicitado y actualiza burbujas, botones y resumen lateral.
    function mostrarPasoProducto(indice) {
        const pasos = document.querySelectorAll('[data-producto-step]');
        const burbujas = document.querySelectorAll('[data-producto-ir]');
        const ultimoPaso = productoTitulos.length - 1;

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
        document.getElementById('productoSubtituloPaso').textContent = productoTitulos[productoPasoActual][1];
        document.getElementById('productoEtiquetaPaso').textContent =
            `Paso ${productoPasoActual + 1} de ${pasos.length}`;

        document.getElementById('btnProductoAnterior').classList.toggle('hidden', productoPasoActual === 0);

        const botonSiguiente = document.getElementById('btnProductoSiguiente');
        if (productoPasoActual === ultimoPaso) {
            botonSiguiente.innerHTML = productoModoEdicion
                ? '<i class="fa-solid fa-floppy-disk"></i> Actualizar producto'
                : '<i class="fa-solid fa-floppy-disk"></i> Guardar producto';
        } else if (productoPasoActual === 2) {
            botonSiguiente.innerHTML = 'Continuar a revisión <i class="fa-solid fa-chevron-right"></i>';
        } else {
            botonSiguiente.innerHTML = 'Siguiente <i class="fa-solid fa-chevron-right"></i>';
        }

        actualizarResumenProducto();

        // Si el formulario esta dentro de un tramite, sincroniza la burbuja superior del padre.
        if (productoEmbebidoTramite) {
            window.parent?.postMessage({
                tipo: 'producto-paso-tramite',
                paso: productoPasoActual,
            }, window.location.origin);
        }
    }

    // Avanza o retrocede segun el valor enviado por los botones inferiores.
    function cambiarPasoProducto(direccion) {
        const ultimoPaso = productoTitulos.length - 1;

        if (direccion > 0 && productoPasoActual === ultimoPaso) {
            document.getElementById('formProductoWizard')?.requestSubmit();
            return;
        }

        // La navegacion queda libre; Laravel validara todo recien al presionar Guardar producto.
        mostrarPasoProducto(productoPasoActual + direccion);
    }

    // Evita que valores escritos por el usuario rompan la tabla dinamica del wizard.
    function escaparHtmlProducto(valor) {
        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    // Normaliza texto para comparar codigo/nombre de producto sin depender de mayusculas o espacios dobles.
    function normalizarTextoProducto(valor) {
        return String(valor ?? '').trim().toLowerCase().replace(/\s+/g, ' ');
    }

    function sincronizarTomSelectProducto(select, valorSeleccionado = null) {
        if (!select?.tomselect) {
            if (valorSeleccionado !== null) {
                select.value = valorSeleccionado;
            }
            return;
        }

        select.tomselect.sync();
        if (valorSeleccionado !== null) {
            select.tomselect.setValue(valorSeleccionado, true);
        }
    }

    // Actualiza un selector de unidad sin modificar el otro campo del formulario.
    function establecerUnidadProducto(idSelect, valor = '') {
        const select = document.getElementById(idSelect);

        if (!select) {
            return;
        }

        sincronizarTomSelectProducto(select, String(valor ?? ''));
    }

    function inicializarBuscadoresProducto() {
        if (!window.TomSelect) {
            return;
        }

        document.querySelectorAll('[data-producto-buscador]').forEach((select) => {
            if (select.tomselect) {
                return;
            }

            new TomSelect(select, {
                create: false,
                allowEmptyOption: true,
                maxOptions: 500,
                dropdownParent: 'body',
                searchField: ['text'],
                render: {
                    option: (data, escape) =>
                        `<div class="producto-ts-option">${escape(data.text || '')}</div>`,
                    item: (data, escape) => `<div>${escape(data.text || '')}</div>`,
                },
            });
        });
    }

    let unidadSelectDestinoProducto = null;

    function abrirModalUnidadProducto(idSelectDestino) {

        unidadSelectDestinoProducto = idSelectDestino;

        abrirModalProducto('modalUnidadProducto');
    }


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
        sincronizarTomSelectProducto(selectDestino, valorTemporal);

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

    function textoUnidadCatalogoProducto(idUnidad) {
        if (!idUnidad) {
            return '';
        }

        const opcion =
            document.querySelector(`#form_presentacion_unidad option[value="${CSS.escape(String(idUnidad))}"]`) ||
            document.querySelector(`#form_registro_unidad option[value="${CSS.escape(String(idUnidad))}"]`);

        return opcion?.dataset.texto || opcion?.textContent?.trim() || '';
    }

    // Muestra avisos del wizard con SweetAlert y deja alert como respaldo si la libreria no carga.
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

        return campo;
    }

    // Marca un campo como invalido y muestra un mensaje corto debajo del control.
    function marcarErrorProducto(idCampo, mensaje) {
        const campo = typeof idCampo === 'string' ? document.getElementById(idCampo) : idCampo;
        const objetivo = objetivoErrorProducto(campo);

        if (!objetivo) {
            return false;
        }

        objetivo.classList.add('is-invalid');

        let error = objetivo.nextElementSibling;
        if (!error || !error.classList.contains('producto-field-error')) {
            error = document.createElement('p');
            error.className = 'producto-field-error';
            objetivo.insertAdjacentElement('afterend', error);
        }

        error.textContent = mensaje;
        return false;
    }

    // Limpia el error visual apenas el usuario corrige o cambia el campo.
    function limpiarErrorProducto(idCampo) {
        const campo = typeof idCampo === 'string' ? document.getElementById(idCampo) : idCampo;
        const objetivo = objetivoErrorProducto(campo);

        if (!objetivo) {
            return;
        }

        objetivo.classList.remove('is-invalid');

        const error = objetivo.nextElementSibling;
        if (error?.classList.contains('producto-field-error')) {
            error.remove();
        }
    }

    // Valida requerido para campos normales y select.
    function validarRequeridoProducto(idCampo, mensaje) {
        const campo = document.getElementById(idCampo);
        if (campo?.value?.trim()) {
            limpiarErrorProducto(campo);
            return true;
        }

        return marcarErrorProducto(campo, mensaje);
    }

    // Valida numeros para evitar porcentajes/cantidades vacias, negativas o fuera de rango.
    function validarNumeroProducto(idCampo, mensaje, minimo = null, maximo = null) {
        const campo = document.getElementById(idCampo);
        const numero = Number(campo?.value);

        if (!campo?.value || Number.isNaN(numero)) {
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

    // Conecta un campo al limpiador de errores para que el aviso desaparezca al corregirlo.
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

    // Evita avanzar cuando el paso actual tiene datos principales incompletos o mal escritos.
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

    // Mantiene sincronizados el nombre, los botones y la miniatura de la etiqueta.
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

    // Deja el control listo para una nueva presentacion sin tocar las filas ya agregadas.
    function reiniciarControlEtiquetaActual() {
        actualizarControlEtiquetaActual('Sin imagen seleccionada.', false);
    }

    // Quita la imagen seleccionada antes de agregar la presentacion a la tabla.
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
            URL.revokeObjectURL(productoEtiquetaPreviewUrl);
            productoEtiquetaPreviewUrl = null;
        }

        productoEtiquetaOrigenActual = null;
        limpiarErrorProducto(etiqueta);
        reiniciarControlEtiquetaActual();
    }

    function esImagenEtiquetaProducto(archivo) {
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        return tiposPermitidos.includes(archivo?.type) || /\.(jpe?g|png|webp)$/i.test(archivo?.name || '');
    }

    // Prepara una vista previa local de la etiqueta antes de agregarla a la tabla.
    function actualizarVistaPreviaEtiquetaProducto() {
        const etiqueta = document.getElementById('form_presentacion_etiqueta');
        const archivo = etiqueta?.files?.[0] ?? null;

        limpiarErrorProducto(etiqueta);

        if (productoEtiquetaPreviewUrl) {
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

    // Muestra solo presentaciones del importador seleccionado y del producto que se esta registrando.
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

    // Al elegir una presentacion registrada, autocompleta los campos que pertenecen a presentaciones.
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
            URL.revokeObjectURL(productoEtiquetaPreviewUrl);
            productoEtiquetaPreviewUrl = null;
        }

        document.getElementById('form_presentacion_cantidad').value = opcion.dataset.cantidad || '';
        establecerUnidadProducto('form_presentacion_unidad', opcion.dataset.unidadId || '');
        document.getElementById('form_presentacion_descripcion').value = opcion.dataset.descripcion || '';
        document.getElementById('form_presentacion_estado').value = opcion.dataset.estado || 'ACTIVO';

        productoEtiquetaOrigenActual = opcion.dataset.etiquetaUrl ? {
                id: esTemporal ? opcion.dataset.temporalIndice : opcion.value,
                url: opcion.dataset.etiquetaUrl,
                nombre: opcion.dataset.etiquetaNombre || 'Etiqueta registrada',
            } :
            null;

        actualizarControlEtiquetaActual(
            productoEtiquetaOrigenActual ? productoEtiquetaOrigenActual.nombre : 'Presentación sin imagen registrada.',
            Boolean(productoEtiquetaOrigenActual),
            productoEtiquetaOrigenActual?.url || ''
        );

        // Si se selecciona una presentacion ya registrada o temporal, solo deben editarse los datos del registro.
        bloquearCamposPresentacionProducto(true);
    }

    // Bloquea o libera los campos propios de presentacion; el registro siempre queda editable.
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

    // Limpia solamente los datos de presentación para comenzar una nueva sin salir del paso actual.
    function prepararNuevaPresentacionProducto() {
        const catalogo = document.getElementById('form_presentacion_catalogo');

        if (catalogo) {
            catalogo.value = '';
        }

        document.getElementById('form_presentacion_cantidad').value = '';
        establecerUnidadProducto('form_presentacion_unidad');
        document.getElementById('form_presentacion_descripcion').value = '';
        document.getElementById('form_presentacion_estado').value = 'ACTIVO';
        limpiarEtiquetaPresentacionProducto();
        document.getElementById('form_presentacion_cantidad')?.focus();
    }

    // Crea una opcion temporal para reutilizar una presentacion agregada sin volver a guardarla duplicada.
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
        opcion.dataset.descripcion = datos.descripcion || '';
        opcion.dataset.estado = datos.estado || 'ACTIVO';
        opcion.dataset.etiquetaUrl = datos.etiquetaUrl || '';
        opcion.dataset.etiquetaNombre = datos.etiquetaNombre || 'Imagen de etiqueta';

        catalogo.appendChild(opcion);
    }

    // Busca si la presentacion ya existe en el formulario para que el nuevo registro apunte a esa misma fila logica.
    function buscarIndicePresentacionTemporalProducto(origenId, cantidad, idCatalogoUnidad, descripcion) {
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
            ].join('|');

            if (claveActual === claveNueva) {
                return indice;
            }
        }

        return null;
    }

    // Guarda los campos reales de presentacion una sola vez aunque se agreguen varios registros.
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
                <span class="presentacion-etiqueta-input"></span>
            `;

        contenedor.appendChild(bloque);
    }

    // Evita agregar dos veces el mismo registro para una misma presentacion.
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

    // Mantiene sincronizados el archivo, los botones y la miniatura de una fila recuperada por Laravel.
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
                productoEtiquetaOldUrls[input.id] = { url: urlInicial, nombre: nombreInicial, esTemporal: false };
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

    // Abre la imagen de una fila recuperada por Laravel.
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

    // Abre la imagen actual o la que pertenece a una fila ya agregada.
    function abrirModalEtiquetaProducto(indice = null) {
        const datosFila = indice !== null ? productoEtiquetaUrls[indice] : null;
        const url = datosFila?.url || productoEtiquetaPreviewUrl || productoEtiquetaOrigenActual?.url;
        const nombre = datosFila?.nombre ||
            productoEtiquetaOrigenActual?.nombre ||
            document.getElementById('presentacionEtiquetaNombre')?.textContent ||
            'Imagen de etiqueta';

        if (!url) {
            notificarProducto('info', 'Sin imagen', 'Seleccione una imagen de la etiqueta para verla.');
            return;
        }

        mostrarEtiquetaEnModalProducto(url, nombre);
    }

    function mostrarEtiquetaEnModalProducto(url, nombre) {
        const imagen = document.getElementById('etiquetaImagenPreview');
        const modal = document.getElementById('modalEtiquetaImagenProducto');

        if (!imagen || !modal) {
            return;
        }

        imagen.src = url;
        document.getElementById('etiquetaImagenModalNombre').textContent = nombre || 'Imagen de etiqueta';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    // Cierra la vista previa sin borrar la imagen seleccionada.
    function cerrarModalEtiquetaProducto() {
        const modal = document.getElementById('modalEtiquetaImagenProducto');
        modal?.classList.remove('is-open');
        modal?.setAttribute('aria-hidden', 'true');
    }

    // Agrega un ingrediente a la tabla y crea inputs hidden para enviarlo al controlador.
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


    // Mantiene una presentación uniforme para porcentajes enteros y decimales.
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

    // Muestra el total de porcentajes agregados y avisa cuando supera 100%.
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
    // Agrega una presentacion y su registro en una sola accion visual.
    // Internamente se envian dos arrays porque la base de datos guarda presentaciones y registros por separado.
    function agregarPresentacionRegistroProducto() {
        const cantidadPresentacion = document.getElementById('form_presentacion_cantidad');
        const unidadPresentacion = document.getElementById('form_presentacion_unidad');
        const etiqueta = document.getElementById('form_presentacion_etiqueta');
        const descripcion = document.getElementById('form_presentacion_descripcion');
        const estadoPresentacion = document.getElementById('form_presentacion_estado');
        const origenPresentacion = document.getElementById('form_presentacion_origen_id');
        const codigo = document.getElementById('form_registro_codigo_autorizacion');
        const numeroLote = document.getElementById('form_registro_numero_lote');
        const fechaVigencia = document.getElementById('form_registro_fecha_vigencia');
        const cantidadRegistro = document.getElementById('form_registro_cantidad');
        const unidadRegistro = document.getElementById('form_registro_unidad');
        const estadoRegistro = document.getElementById('form_registro_estado');
        const catalogoPresentacion = document.getElementById('form_presentacion_catalogo');
        const opcionPresentacion = catalogoPresentacion?.options[catalogoPresentacion.selectedIndex];
        const indiceTemporalSeleccionado = opcionPresentacion?.dataset.temporalPresentacion === '1' ?
            opcionPresentacion.dataset.temporalIndice :
            null;
        const archivoEtiqueta = etiqueta?.files?.[0] ?? null;
        const origenId = origenPresentacion?.value || '';
        const indiceExistente = indiceTemporalSeleccionado ?? buscarIndicePresentacionTemporalProducto(
            origenId,
            cantidadPresentacion.value,
            unidadPresentacion.value,
            descripcion.value
        );
        const reutilizaPresentacion = indiceExistente !== null && indiceExistente !== undefined;
        const tieneEtiquetaOrigen = Boolean(origenId && productoEtiquetaOrigenActual?.id === origenId &&
            productoEtiquetaOrigenActual?.url);
        const tieneEtiquetaTemporal = Boolean(reutilizaPresentacion && productoEtiquetaUrls[indiceExistente]?.url);
        const productoNombre = document.getElementById('form_nombre_comercial')?.value || 'Sin nombre comercial';
        const productoCodigo = document.getElementById('form_codigo')?.value || 'Sin codigo';

        const cantidadValida = validarNumeroProducto('form_presentacion_cantidad', 'Ingrese una cantidad valida.', 1);
        const unidadValida = validarRequeridoProducto('form_presentacion_unidad', 'Ingrese la unidad.');
        if (!cantidadValida || !unidadValida) {
            return;
        }

        if (!archivoEtiqueta && !tieneEtiquetaOrigen && !tieneEtiquetaTemporal) {
            marcarErrorProducto(etiqueta, 'Seleccione la imagen de la etiqueta.');
            return;
        }

        if (archivoEtiqueta && !esImagenEtiquetaProducto(archivoEtiqueta)) {
            marcarErrorProducto(etiqueta, 'Seleccione una imagen JPG, PNG o WEBP.');
            return;
        }

        document.getElementById('sinRegistrosPresentacionesProducto')?.remove();

        const indicePresentacion = reutilizaPresentacion ? Number(indiceExistente) : productoPresentacionIndice;
        const indiceRegistro = productoRegistroIndice;
        const idRegistroEditando = productoRegistroEditandoId || '';
        const textoUnidadPresentacion = textoUnidadCatalogoProducto(unidadPresentacion.value);
        const textoUnidadRegistro = textoUnidadCatalogoProducto(unidadRegistro.value);
        const presentacionTexto =
            `${cantidadPresentacion.value} ${textoUnidadPresentacion}${descripcion.value ? ' - ' + descripcion.value : ''}`;
        const etiquetaUrl = archivoEtiqueta ?
            (productoEtiquetaPreviewUrl || URL.createObjectURL(archivoEtiqueta)) :
            (productoEtiquetaOrigenActual?.url || productoEtiquetaUrls[indicePresentacion]?.url);
        const etiquetaNombre = archivoEtiqueta ?
            archivoEtiqueta.name :
            (productoEtiquetaOrigenActual?.nombre || productoEtiquetaUrls[indicePresentacion]?.nombre ||
                'Imagen de etiqueta');
        if (existeRegistroDuplicadoProducto(
                indicePresentacion,
                codigo.value,
                numeroLote.value,
                fechaVigencia.value,
                cantidadRegistro.value,
                unidadRegistro.value
            )) {
            marcarErrorProducto(codigo, 'Este registro ya fue agregado para la presentacion seleccionada.');
            return;
        }

        if (!reutilizaPresentacion) {
            productoEtiquetaUrls[indicePresentacion] = {
                url: etiquetaUrl,
                nombre: etiquetaNombre,
            };

            crearCamposOcultosPresentacionProducto(indicePresentacion, {
                cantidad: cantidadPresentacion.value,
                id_catalogo_unidad: unidadPresentacion.value,
                unidad_texto: textoUnidadPresentacion,
                descripcion: descripcion.value,
                estado: estadoPresentacion.value,
                origenId,
            });
        }

        const fila = document.createElement('tr');
        fila.dataset.tipoFila = 'registro';
        fila.dataset.presentacionIndice = indicePresentacion;
        fila.dataset.presentacionTexto = presentacionTexto;

        /* El formulario embebido conserva su tabla resumida; el módulo principal usa la tabla compacta. */
        if (productoEmbebidoTramite) {
            fila.innerHTML = `
                <td>${indiceRegistro + 1}</td>
                <td>
                    <strong class="presentacion-producto-nombre">${escaparHtmlProducto(productoNombre)}</strong>
                    <small class="presentacion-producto-codigo">${escaparHtmlProducto(productoCodigo)}</small>
                </td>
                <td>
                    <strong>${escaparHtmlProducto(codigo.value || 'Sin código')}</strong>
                    <small>${escaparHtmlProducto(numeroLote.value || 'Sin lote')}</small>
                </td>
                <td>${escaparHtmlProducto(fechaVigencia.value || 'Sin fecha')}</td>
                <td>${escaparHtmlProducto(cantidadRegistro.value || '-')}</td>
                <td>${escaparHtmlProducto(textoUnidadRegistro || 'Sin unidad')}</td>
                <td>
                    ${escaparHtmlProducto(presentacionTexto)}
                </td>
                <td><span class="producto-pill">${escaparHtmlProducto(estadoRegistro.value)}</span></td>
                <td>
                    <div class="producto-row-actions">
                        <button type="button" class="producto-action-icon is-edit"
                            title="Editar presentación y registro" onclick="editarFilaRegistroPresentacionProducto(this)">
                            <span>Editar</span>
                        </button>
                        <button type="button" class="producto-action-icon is-delete"
                            title="Eliminar presentación y registro" onclick="quitarFilaProducto(this)">
                            <span>Quitar</span>
                        </button>
                    </div>
                    <span class="presentacion-etiqueta-input hidden"></span>
                    <input type="hidden" name="registros[${indiceRegistro}][id_registro]" value="${escaparHtmlProducto(idRegistroEditando)}">
                    <input type="hidden" name="registros[${indiceRegistro}][codigo_autorizacion]" value="${escaparHtmlProducto(codigo.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][numero_lote]" value="${escaparHtmlProducto(numeroLote.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][fecha_vigencia]" value="${escaparHtmlProducto(fechaVigencia.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][cantidad]" value="${escaparHtmlProducto(cantidadRegistro.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][id_catalogo_unidad]" value="${escaparHtmlProducto(unidadRegistro.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][id_presentacion_temporal]" value="${escaparHtmlProducto(indicePresentacion)}">
                    <input type="hidden" name="registros[${indiceRegistro}][presentacion_texto]" value="${escaparHtmlProducto(presentacionTexto)}">
                    <input type="hidden" name="registros[${indiceRegistro}][estado]" value="${escaparHtmlProducto(estadoRegistro.value)}">
                </td>
            `;
        } else {
            const claseEstado = estadoRegistro.value === 'ACTIVO' ? '' : 'is-inactive';
            fila.dataset.estadoRegistro = estadoRegistro.value;
            fila.innerHTML = `
                <td data-label="Registro">${indiceRegistro + 1}</td>
                <td data-label="Código"><strong>${escaparHtmlProducto(codigo.value || 'Sin código')}</strong></td>
                <td data-label="Lote">${escaparHtmlProducto(numeroLote.value || 'Sin lote')}</td>
                <td data-label="Vigencia">${escaparHtmlProducto(fechaVigencia.value || 'Sin fecha')}</td>
                <td data-label="Cantidad">${escaparHtmlProducto(cantidadRegistro.value || '-')}</td>
                <td data-label="Unidad">${escaparHtmlProducto(textoUnidadRegistro || 'Sin unidad')}</td>
                <td data-label="Presentación">
                    <div class="producto-table-presentacion">
                        <strong>${escaparHtmlProducto(presentacionTexto)}</strong>
                        <small>${escaparHtmlProducto(estadoPresentacion.value)}</small>
                    </div>
                </td>
                <td class="producto-table-etiqueta-cell" data-label="Etiqueta">
                    <button type="button" class="producto-table-image-preview"
                        onclick="abrirModalEtiquetaProducto(${indicePresentacion})">
                        <img src="${escaparHtmlProducto(etiquetaUrl)}" alt="Etiqueta de la presentación">
                        <span hidden>Sin imagen</span>
                    </button>
                    <span class="producto-table-file-name">${escaparHtmlProducto(etiquetaNombre)}</span>
                    <span class="presentacion-etiqueta-input hidden"></span>
                </td>
                <td data-label="Estado">
                    <span class="producto-table-status ${claseEstado}">${escaparHtmlProducto(estadoRegistro.value)}</span>
                </td>
                <td data-label="Acciones">
                    <div class="producto-row-actions">
                        <button type="button" class="producto-action-icon is-view" title="Ver etiqueta"
                            onclick="abrirModalEtiquetaProducto(${indicePresentacion})">
                            <i class="fa-solid fa-eye"></i><span>Ver</span>
                        </button>
                        <button type="button" class="producto-action-icon is-edit"
                            title="Editar presentación y registro" onclick="editarFilaRegistroPresentacionProducto(this)">
                            <i class="fa-solid fa-pen"></i><span>Editar</span>
                        </button>
                        <button type="button" class="producto-action-icon is-delete"
                            title="Eliminar presentación y registro" onclick="quitarFilaProducto(this)">
                            <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                        </button>
                    </div>
                    <input type="hidden" name="registros[${indiceRegistro}][id_registro]" value="${escaparHtmlProducto(idRegistroEditando)}">
                    <input type="hidden" name="registros[${indiceRegistro}][codigo_autorizacion]" value="${escaparHtmlProducto(codigo.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][numero_lote]" value="${escaparHtmlProducto(numeroLote.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][fecha_vigencia]" value="${escaparHtmlProducto(fechaVigencia.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][cantidad]" value="${escaparHtmlProducto(cantidadRegistro.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][id_catalogo_unidad]" value="${escaparHtmlProducto(unidadRegistro.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][id_presentacion_temporal]" value="${escaparHtmlProducto(indicePresentacion)}">
                    <input type="hidden" name="registros[${indiceRegistro}][presentacion_texto]" value="${escaparHtmlProducto(presentacionTexto)}">
                    <input type="hidden" name="registros[${indiceRegistro}][estado]" value="${escaparHtmlProducto(estadoRegistro.value)}">
                </td>
            `;
        }

        document.getElementById('tablaRegistrosPresentacionesProducto').appendChild(fila);
        filtrarRegistrosPresentacionesProducto();

        if (archivoEtiqueta && !reutilizaPresentacion) {
            // Mueve el input file real al bloque oculto para enviarlo una sola vez aunque existan varios registros.
            etiqueta.name = `presentaciones[${indicePresentacion}][url_etiqueta]`;
            etiqueta.id = `form_presentacion_etiqueta_${indicePresentacion}`;
            etiqueta.classList.add('hidden');
            etiqueta.removeAttribute('onchange');
            document.querySelector(`#presentacion_oculta_${indicePresentacion} .presentacion-etiqueta-input`)
                ?.appendChild(etiqueta);

            // Crea otro input file limpio para registrar una siguiente presentacion.
            const nuevoInputEtiqueta = document.createElement('input');
            nuevoInputEtiqueta.className = 'producto-upload-input';
            nuevoInputEtiqueta.id = 'form_presentacion_etiqueta';
            nuevoInputEtiqueta.type = 'file';
            nuevoInputEtiqueta.accept = 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp';
            nuevoInputEtiqueta.addEventListener('change', actualizarVistaPreviaEtiquetaProducto);
            prepararLimpiezaErrorProducto(nuevoInputEtiqueta);
            document.getElementById('presentacionEtiquetaWrapper').prepend(nuevoInputEtiqueta);
        } else if (etiqueta) {
            etiqueta.value = '';
        }

        if (!reutilizaPresentacion) {
            registrarOpcionTemporalPresentacionProducto(indicePresentacion, {
                texto: presentacionTexto,
                cantidad: cantidadPresentacion.value,
                id_catalogo_unidad: unidadPresentacion.value,
                unidad_texto: textoUnidadPresentacion,
                descripcion: descripcion.value,
                estado: estadoPresentacion.value,
                etiquetaUrl,
                etiquetaNombre,
            });
        }

        productoEtiquetaPreviewUrl = null;
        productoEtiquetaOrigenActual = null;
        if (!reutilizaPresentacion) {
            productoPresentacionIndice++;
        }
        productoRegistroIndice++;
        productoRegistroEditandoId = null;
        cantidadPresentacion.value = '';
        establecerUnidadProducto('form_presentacion_unidad');
        descripcion.value = '';
        if (origenPresentacion) {
            origenPresentacion.value = '';
        }
        if (catalogoPresentacion) {
            catalogoPresentacion.value = '';
        }
        bloquearCamposPresentacionProducto(false);
        codigo.value = '';
        numeroLote.value = '';
        fechaVigencia.value = '';
        cantidadRegistro.value = '';
        establecerUnidadProducto('form_registro_unidad');
        limpiarErrorProducto(cantidadPresentacion);
        limpiarErrorProducto(unidadPresentacion);
        limpiarErrorProducto(etiqueta);
        limpiarErrorProducto(codigo);
        reiniciarControlEtiquetaActual();
        actualizarResumenProducto();
    }

    // Carga los datos de una fila al formulario para corregirlos y volver a agregarlos.
    function editarFilaRegistroPresentacionProducto(boton) {
        const fila = boton?.closest('tr');

        if (!fila) {
            return;
        }

        const indicePresentacion = fila.dataset.presentacionIndice;
        const presentacionOculta = document.getElementById(`presentacion_oculta_${indicePresentacion}`);
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
        document.getElementById('form_presentacion_descripcion').value = valorCampo(
            `input[name="presentaciones[${indicePresentacion}][descripcion]"]`);
        document.getElementById('form_presentacion_estado').value = valorCampo(
            `input[name="presentaciones[${indicePresentacion}][estado]"]`) || 'ACTIVO';
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
        document.getElementById('form_registro_estado').value = valorCampo(
            'input[name$="[estado]"][name^="registros"]') || 'ACTIVO';

        productoRegistroEditandoId = valorCampo('input[name$="[id_registro]"][name^="registros"]') || null;

        // Una etiqueta ya guardada puede reutilizarse al editar la fila sin pedir el archivo otra vez.
        const idPresentacionOrigen = document.getElementById('form_presentacion_origen_id').value;
        const inputEtiquetaRegistrada = fila.querySelector('input[type="file"][data-url-inicial]');
        const urlEtiquetaRegistrada = inputEtiquetaRegistrada?.dataset.urlInicial || '';

        if (idPresentacionOrigen && urlEtiquetaRegistrada) {
            productoEtiquetaOrigenActual = {
                id: idPresentacionOrigen,
                url: urlEtiquetaRegistrada,
                nombre: 'Imagen registrada',
            };
        }

        quitarFilaProducto(boton);
        if (!productoEtiquetaOrigenActual) {
            reiniciarControlEtiquetaActual();
        }

        notificarProducto(
            'info',
            'Fila cargada',
            urlEtiquetaRegistrada
                ? 'Revise los datos y vuelva a agregar la fila para guardar los cambios.'
                : 'Revise los datos y seleccione nuevamente la imagen antes de agregar la fila corregida.'
        );
    }

    // Elimina una fila dinamica y recalcula los contadores visibles.
    function quitarFilaProducto(boton) {
        const fila = boton.closest('tr');
        const indicePresentacion = fila?.dataset.presentacionIndice;

        fila?.remove();

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

    // Aplica la búsqueda y el filtro de estado sin modificar los datos que se enviarán al servidor.
    function filtrarRegistrosPresentacionesProducto() {
        const tabla = document.getElementById('tablaRegistrosPresentacionesProducto');
        const buscador = document.getElementById('buscarRegistrosPresentacionesProducto');
        const filtroEstado = document.getElementById('filtrarEstadoRegistrosPresentacionesProducto');
        const contador = document.getElementById('contadorRegistrosPresentacionesProducto');

        if (!tabla) {
            return;
        }

        const textoBuscado = normalizarTextoProducto(buscador?.value || '');
        const estadoBuscado = filtroEstado?.value || '';
        const filas = Array.from(tabla.querySelectorAll('tr:not(#sinRegistrosPresentacionesProducto)'));
        let filasVisibles = 0;

        filas.forEach((fila) => {
            const coincideTexto = !textoBuscado || normalizarTextoProducto(fila.textContent).includes(textoBuscado);
            const coincideEstado = !estadoBuscado || fila.dataset.estadoRegistro === estadoBuscado;
            const visible = coincideTexto && coincideEstado;

            fila.hidden = !visible;
            filasVisibles += visible ? 1 : 0;
        });

        if (contador) {
            contador.textContent = String(filasVisibles);
        }
    }

    // Restaura el aviso de tabla vacia cuando el usuario quita todos los registros agregados.
    function asegurarFilaVaciaRegistrosPresentacionesProducto() {
        const cuerpoTabla = document.getElementById('tablaRegistrosPresentacionesProducto');

        if (!cuerpoTabla || cuerpoTabla.querySelector('tr:not(#sinRegistrosPresentacionesProducto)')) {
            return;
        }

        const columnas = productoEmbebidoTramite ? 9 : 10;
        cuerpoTabla.innerHTML = `
                <tr id="sinRegistrosPresentacionesProducto">
                    <td colspan="${columnas}">
                        <div class="producto-empty">
                            Todavía no agregó presentaciones y registros.
                        </div>
                    </td>
                </tr>
            `;
    }

    // Mantiene la numeracion visual ordenada despues de quitar filas.
    function reenumerarFilasProducto(idTabla, idFilaVacia) {
        document.querySelectorAll(`#${idTabla} tr:not(#${idFilaVacia})`).forEach((fila, indice) => {
            if (fila.children[0]) {
                fila.children[0].textContent = indice + 1;
            }
        });
    }

    // Abre un modal visual para agregar catalogos sin salir del formulario.
    // Tambien limpia sus campos para iniciar siempre un registro nuevo.
    function abrirModalProducto(idModal) {
        const modal = document.getElementById(idModal);

        if (!modal) {
            return;
        }

        limpiarModalProducto(modal);
        modal.classList.add('is-open');
    }

    // Limpia los campos internos del modal para que al abrirlo otra vez no queden datos anteriores.
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

    // Cierra un modal visual y limpia sus campos para un nuevo registro.
    function cerrarModalProducto(idModal) {
        const modal = document.getElementById(idModal);

        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        limpiarModalProducto(modal);
    }

    // Agrega una opcion temporal al select para no cortar el flujo de la vista.
    // Si ya habia una opcion temporal en ese select, se reemplaza para evitar varios registros nuevos.
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
            const opcionMismoCodigo = Array.from(select.options).find((opcion) =>
                opcion.dataset.temporal !== '1' &&
                normalizar(opcion.dataset.codigo) === normalizar(textoBase)
            );

            if (opcionMismoCodigo) {
                const mismaDescripcion = normalizar(opcionMismoCodigo.dataset.descripcion) === normalizar(descripcion);
                const mensaje = mismaDescripcion ?
                    'Ya existe una clasificación toxicológica con el mismo código y descripción. Selecciónela del listado.' :
                    'El código ya está registrado. Seleccione la clasificación toxicológica existente.';

                marcarErrorProducto(input, mensaje);
                return;
            }

            document.getElementById('form_clasificacion_toxicologica_temporal_codigo').value = textoBase;
            document.getElementById('form_clasificacion_toxicologica_temporal_descripcion').value = descripcion;
            textoVisible = descripcion ? `${textoBase} - ${descripcion}` : textoBase;
        }

        if (idSelect === 'form_id_clasificacion_quimica') {
            document.getElementById('form_clasificacion_quimica_temporal_nombre').value = textoBase;
            document.getElementById('form_clasificacion_quimica_temporal_descripcion').value =
                document.getElementById('modal_clasificacion_quimica_descripcion')?.value.trim() || '';
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
            sincronizarTomSelectProducto(
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
        sincronizarTomSelectProducto(select, valorTemporal);
        limpiarErrorProducto(select);
        limpiarErrorProducto(input);
        actualizarDetalleIngredienteProducto();
        cerrarModalProducto(idModal);
        actualizarResumenProducto();
    }
    // Actualiza el panel lateral y el resumen del paso de revisión.
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

    // Escribe texto solo si el elemento existe; asi el paso nuevo no depende de IDs antiguos.
    function ponerTextoProducto(idElemento, valor) {
        const elemento = document.getElementById(idElemento);

        if (elemento) {
            elemento.textContent = valor;
        }
    }

    // Devuelve el texto seleccionado de un select para mostrarlo en revision.
    function textoSelectProducto(idSelect) {
        const select = document.getElementById(idSelect);
        const texto = select?.options[select.selectedIndex]?.text?.trim();

        return texto || 'Sin seleccionar';
    }

    // Cierra el selector visual de ingredientes cuando el usuario elige o hace clic fuera.
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

    // El selector muestra y busca únicamente por el nombre del ingrediente.
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
                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                cerrarSelectorIngredienteProducto();
            });
        });
    }

    // Sincroniza el selector visual con el select real que se envia al controlador.
    function actualizarDetalleIngredienteProducto() {
        const select = document.getElementById('form_ingrediente_select');
        const etiqueta = document.querySelector('[data-ingrediente-label]');
        const opcion = select?.options[select.selectedIndex];
        const nombre = opcion?.dataset.nombre || opcion?.text?.trim() || 'Seleccione un ingrediente';

        if (!etiqueta) {
            return;
        }

        etiqueta.textContent = select?.value ? nombre : 'Seleccione un ingrediente';
        renderizarOpcionesIngredienteProducto();
    }

    // Limpia datos temporales si el usuario cambia del catalogo nuevo a uno existente.
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

    // Obtiene el value de un campo por selector CSS para armar el resumen final.
    function valorResumenProducto(selector) {
        const campo = document.querySelector(selector);
        return campo ? String(campo.value || '').trim() : '';
    }

    // Muestra "No registrado" cuando el dato aun no fue llenado.
    function textoResumenProducto(valor) {
        return valor && String(valor).trim() !== '' ? valor : 'No registrado';
    }

    // Crea una fila campo/valor igual al formato usado en revision de persona/empresa.
    function itemResumenProducto(titulo, valor, anchoCompleto = false) {
        const textoSeguro = escaparHtmlProducto(textoResumenProducto(valor)).replaceAll('\n', '<br>');

        return `
                <div class="producto-review-row ${anchoCompleto ? 'is-wide' : ''}">
                    <dt>${escaparHtmlProducto(titulo)}</dt>
                    <dd>${textoSeguro}</dd>
                </div>
            `;
    }

    // Agrupa una seccion del resumen final con un titulo y su contenido.
    // Acepta HTML como texto o como arreglo para evitar errores si se reutiliza desde otro bloque.
    function grupoResumenProducto(titulo, descripcion, contenidoHtml) {
        const contenidoSeguro = Array.isArray(contenidoHtml) ? contenidoHtml.join('') : (contenidoHtml || '');

        return `
                <section class="producto-review-section">
                    <div class="producto-review-section-head">
                        <span class="producto-review-section-dot"></span>
                        <div>
                            <h4>${escaparHtmlProducto(titulo)}</h4>
                            <p>${escaparHtmlProducto(descripcion)}</p>
                        </div>
                    </div>
                    <div class="producto-review-list">
                        ${contenidoSeguro}
                    </div>
                </section>
            `;
    }

    // Construye una tabla de revision para listas dinamicas como ingredientes, presentaciones y registros.
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

    // Agrupa inputs del tipo presentaciones[0][cantidad] o registros[0][id_catalogo_unidad] por indice.
    function gruposPorIndiceResumenProducto(prefijo) {
        const patron = new RegExp(`^${prefijo}\\[(\\d+)\\]\\[([^\\]]+)\\]$`);
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

    // Devuelve indices unicos de presentaciones para contar la relacion real, no las filas de registros.
    function indicesPresentacionesResumenProducto() {
        return Object.keys(gruposPorIndiceResumenProducto('presentaciones'));
    }

    // Lee el nombre del PDF de una presentacion, sea nuevo, temporal o copiado de catalogo.
    function etiquetaResumenPresentacionProducto(indice, presentacion) {
        const archivo = document.querySelector(`input[name="presentaciones[${indice}][url_etiqueta]"]`)?.files?.[0];

        if (archivo?.name) {
            return archivo.name;
        }

        if (productoEtiquetaUrls[indice]?.nombre) {
            return productoEtiquetaUrls[indice].nombre;
        }

        if (presentacion.id_presentacion_origen) {
            return `Etiqueta registrada de presentacion #${presentacion.id_presentacion_origen}`;
        }

        return 'Pendiente de seleccionar PDF';
    }

    // Une cantidad, unidad y descripcion para identificar a que presentacion apunta un registro.
    function textoPresentacionResumenProducto(indice, presentaciones) {
        const presentacion = presentaciones[indice] || {};
        const base = [presentacion.cantidad, textoUnidadCatalogoProducto(presentacion.id_catalogo_unidad)].filter(
            Boolean).join(' ');
        const descripcion = presentacion.descripcion ? ` - ${presentacion.descripcion}` : '';

        return base || descripcion ? `${base}${descripcion}` : `Presentacion #${indice}`;
    }

    // Actualiza el paso Revision para confirmar antes de guardar producto.
    function actualizarRevisionFinalProducto() {
        const destino = document.getElementById('resumenProductoWizard');

        if (!destino) {
            return;
        }

        const fabricanteNuevo = document.getElementById('form_id_fabricante')?.selectedOptions?.[0]?.dataset
            .temporal === '1';
        const clasificacionToxicologicaNueva = document.getElementById('form_id_clasificacion_toxicologica')
            ?.selectedOptions?.[0]?.dataset.temporal === '1';
        const ingredientes = gruposPorIndiceResumenProducto('ingredientes_productos');
        const presentaciones = gruposPorIndiceResumenProducto('presentaciones');
        const registros = gruposPorIndiceResumenProducto('registros');

        const datosProducto = [
            itemResumenProducto('Codigo', valorResumenProducto('#form_codigo')),
            itemResumenProducto('Nombre comercial', valorResumenProducto('#form_nombre_comercial')),
            itemResumenProducto('Tipo de producto', valorResumenProducto('#form_tipo_producto')),
            itemResumenProducto('Nombre técnico', valorResumenProducto('#form_nombre_tecnico')),
            itemResumenProducto('Clasificación química', textoSelectProducto('form_id_clasificacion_quimica')),
            itemResumenProducto('Importador', textoSelectProducto('form_id_importador_persona')),
            itemResumenProducto('Pais / territorio', textoSelectProducto('form_id_territorio_pais')),
            itemResumenProducto('Fabricante', textoSelectProducto('form_id_fabricante')),
            itemResumenProducto('Clasificación toxicológica', textoSelectProducto(
                'form_id_clasificacion_toxicologica')),
            itemResumenProducto('Estado del producto', valorResumenProducto('#form_estado')),
        ];

        if (fabricanteNuevo) {
            datosProducto.push(itemResumenProducto('Fabricante nuevo - nombre', valorResumenProducto(
                '#form_fabricante_temporal_nombre')));
            datosProducto.push(itemResumenProducto('Fabricante nuevo - razon social', valorResumenProducto(
                '#form_fabricante_temporal_razon_social')));
            datosProducto.push(itemResumenProducto('Fabricante nuevo - descripcion', valorResumenProducto(
                '#form_fabricante_temporal_descripcion'), true));
        }

        if (clasificacionToxicologicaNueva) {
            datosProducto.push(itemResumenProducto('Clasificación toxicológica nueva - código', valorResumenProducto(
                '#form_clasificacion_toxicologica_temporal_codigo')));
            const descripcionToxicologica = valorResumenProducto(
                '#form_clasificacion_toxicologica_temporal_descripcion');
            if (descripcionToxicologica && descripcionToxicologica !== 'Sin dato') {
                datosProducto.push(itemResumenProducto('Clasificación toxicológica nueva - descripción',
                    descripcionToxicologica));
            }
        }

        const filasIngredientes = Object.entries(ingredientes).map(([indice, ingrediente]) => [
            Number(indice) + 1,
            ingrediente.nombre || textoResumenProducto(ingrediente.id_ingrediente),
            ingrediente.porcentaje !== undefined && ingrediente.porcentaje !== '' ?
            `${formatearPorcentajeIngrediente(ingrediente.porcentaje)}%` : '',
            ingrediente.estado || 'ACTIVO',
        ]);

        const filasPresentaciones = Object.entries(presentaciones).map(([indice, presentacion]) => [
            Number(indice) + 1,
            presentacion.cantidad,
            textoUnidadCatalogoProducto(presentacion.id_catalogo_unidad),
            presentacion.descripcion,
            etiquetaResumenPresentacionProducto(indice, presentacion),
            presentacion.id_presentacion_origen ?
            `Reutiliza presentacion #${presentacion.id_presentacion_origen}` : 'Nueva presentacion',
            presentacion.estado || 'ACTIVO',
        ]);

        const filasRegistros = Object.entries(registros).map(([indice, registro]) => [
            Number(indice) + 1,
            registro.codigo_autorizacion,
            registro.numero_lote,
            registro.fecha_vigencia,
            registro.cantidad,
            textoUnidadCatalogoProducto(registro.id_catalogo_unidad),
            textoPresentacionResumenProducto(registro.id_presentacion_temporal, presentaciones),
            registro.estado || 'ACTIVO',
        ]);

        destino.innerHTML = [
            grupoResumenProducto(
                'Datos principales del producto',
                'Informacion general que identifica el producto que se va a registrar.',
                datosProducto.join('')
            ),
            grupoResumenProducto(
                'Ingredientes del producto',
                'Porcentaje y estado de cada ingrediente agregado.',
                tablaResumenProducto(
                    ['#', 'Ingrediente', 'Porcentaje', 'Estado'],
                    filasIngredientes,
                    'No se agregaron ingredientes.'
                )
            ),
            grupoResumenProducto(
                'Presentaciones del producto',
                'Formatos, cantidades, unidades, imagen de etiqueta y estado de cada presentacion.',
                tablaResumenProducto(
                    ['#', 'Cantidad', 'Unidad', 'Descripcion', 'Imagen de etiqueta', 'Origen', 'Estado'],
                    filasPresentaciones,
                    'No se agregaron presentaciones.'
                )
            ),
            grupoResumenProducto(
                'Registros del producto',
                'Codigos, lotes, vigencias, cantidades y unidades declaradas para cada presentacion.',
                tablaResumenProducto(
                    ['#', 'Codigo autorizacion', 'Nro. de lote', 'Fecha vigencia', 'Cantidad', 'Unidad',
                        'Presentacion seleccionada', 'Estado'
                    ],
                    filasRegistros,
                    'No se agregaron registros.'
                )
            ),
        ].join('');
    }

    // Guarda el avance temporal en el navegador sin enviar el formulario ni recargar la pagina.
    function guardarAvanceProducto() {
        const formulario = document.getElementById('formProductoWizard');
        const datos = {};
        let tieneArchivosTemporales = false;

        new FormData(formulario).forEach((valor, clave) => {
            // localStorage no conserva archivos reales; el PDF queda vivo en el input mientras no se recargue.
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
                    'Los datos se guardaron. Los PDF se mantienen en esta pagina hasta guardar el producto; no recargue la vista.' :
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
            'Los PDF se mantienen en esta pagina hasta guardar el producto; no recargue la vista.' :
            'El avance se guardo temporalmente en este navegador.'
        );
    }

    // Inicializa eventos del wizard cuando la vista termina de cargar.
    document.addEventListener('DOMContentLoaded', () => {
        // Permite que las burbujas superiores del tramite cambien el paso del producto embebido.
        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin || event.data?.tipo !== 'producto-ir-paso') {
                return;
            }

            mostrarPasoProducto(Number(event.data.paso));
        });

        document.querySelectorAll('[data-producto-ir]').forEach((boton) => {
            boton.addEventListener('click', () => {
                const pasoDestino = Number(boton.dataset.productoIr);
                mostrarPasoProducto(pasoDestino);
            });
        });

        ['form_nombre_comercial', 'form_tipo_producto', 'form_codigo', 'form_id_importador_persona',
            'form_id_fabricante', 'form_id_clasificacion_toxicologica'
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

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-ingrediente-combobox]')) {
                cerrarSelectorIngredienteProducto();
            }
        });

        inicializarBuscadoresProducto();

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
        document.getElementById('filtrarEstadoRegistrosPresentacionesProducto')?.addEventListener(
            'change',
            filtrarRegistrosPresentacionesProducto
        );
        filtrarRegistrosPresentacionesProducto();

        // Todos los controles del wizard limpian su error apenas el usuario corrige el dato.
        document.querySelectorAll(
                '.producto-input, .producto-select, .producto-textarea, .producto-upload-input')
            .forEach((campo) => prepararLimpiezaErrorProducto(campo));

        mostrarPasoProducto(0);

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
@if (!$productoEmbebido)
    </x-admin-layout>
@endif
