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

    $ingredientesCatalogo = collect(
        $ingredientes ?? \App\Models\Ingrediente::query()->where('estado', 'ACTIVO')->orderBy('id')->get()
    )
        // Los registros antiguos con el mismo nombre se muestran como una sola opción.
        ->unique(fn($ingrediente) => mb_strtolower(trim((string) $ingrediente->nombre)))
        ->sortBy(fn($ingrediente) => mb_strtolower(trim((string) $ingrediente->nombre)))
        ->values();

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

{{-- Bloquea el formulario durante el guardado para evitar registros enviados varias veces. --}}
<div id="productoGuardandoOverlay"
    class="fixed inset-0 z-[20000] hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]"
    role="dialog" aria-modal="true" aria-labelledby="productoGuardandoTitulo" aria-hidden="true">
    <div class="flex max-w-sm items-center gap-4 rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-2xl">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-teal-50 text-lg text-teal-700">
            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
        </span>
        <div>
            <h3 id="productoGuardandoTitulo" class="text-base font-black text-slate-900">Registrando producto</h3>
            <p class="mt-1 text-sm font-medium text-slate-600">Espere mientras guardamos toda la información.</p>
        </div>
    </div>
</div>

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
                <label class="producto-field-label" for="modal_clasificacion_toxicologica_descripcion">Descripción</label>
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

            <div class="producto-image-modal-actions">
                <div class="producto-image-zoom-tools" role="group" aria-label="Zoom de la etiqueta">
                    <button type="button" onclick="cambiarZoomEtiquetaProducto(-0.25)"
                        title="Alejar imagen" aria-label="Alejar imagen">
                        <i class="fa-solid fa-magnifying-glass-minus"></i>
                    </button>
                    <output id="etiquetaImagenZoomValor" aria-live="polite">100%</output>
                    <button type="button" onclick="cambiarZoomEtiquetaProducto(0.25)"
                        title="Acercar imagen" aria-label="Acercar imagen">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </button>
                    <button type="button" onclick="restablecerZoomEtiquetaProducto()"
                        title="Restablecer tamaño" aria-label="Restablecer tamaño">
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
    // Guarda el paso actual del wizard para mostrar una sola seccion a la vez.
    let productoPasoActual = 0;

    // Contadores usados para crear nombres de arrays dinamicos sin repetir indices.
    let productoIngredienteIndice = {{ $siguienteIngrediente }};
    let productoPresentacionIndice = {{ $siguientePresentacion }};
    let productoRegistroIndice = {{ $siguienteRegistro }};
    let productoRegistroEditandoId = null;
    let productoEtiquetaPreviewUrl = null;
    let productoEtiquetaOrigenActual = null;
    let productoEtiquetaZoom = 1;
    let productoEtiquetaTamanoBase = { ancho: 0, alto: 0 };
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

    // Mantiene sincronizados el select real y el buscador visible del catálogo.
    function sincronizarBuscadorProducto(select, valorSeleccionado = null) {
        if (!select) {
            return;
        }

        if (valorSeleccionado !== null) {
            select.value = valorSeleccionado;
        }

        actualizarBuscadorCatalogoProducto(select);
    }

    // Actualiza un selector de unidad sin modificar el otro campo del formulario.
    function establecerUnidadProducto(idSelect, valor = '') {
        const select = document.getElementById(idSelect);

        if (!select) {
            return;
        }

        sincronizarBuscadorProducto(select, String(valor ?? ''));
    }

    // Cierra uno o todos los buscadores de catálogo sin alterar la opción seleccionada.
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

    // Dibuja las opciones del catálogo que coinciden con el texto escrito.
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

            return `
                <button type="button" class="producto-ingredient-option ${seleccionado ? 'is-selected' : ''}"
                    data-producto-search-option="${escaparHtmlProducto(opcion.value)}" role="option"
                    aria-selected="${seleccionado ? 'true' : 'false'}">
                    <span class="producto-ingredient-option-copy">
                        <strong>${escaparHtmlProducto(opcion.textContent.trim())}</strong>
                    </span>
                    ${seleccionado ? '<i class="fa-solid fa-check producto-ingredient-option-check" aria-hidden="true"></i>' : ''}
                </button>`;
        }).join('');

        lista.querySelectorAll('[data-producto-search-option]').forEach((boton) => {
            boton.addEventListener('click', () => {
                select.value = boton.dataset.productoSearchOption || '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
                actualizarBuscadorCatalogoProducto(select);
                cerrarBuscadoresCatalogoProducto();
            });
        });
    }

    // Refleja en el control visible el texto que realmente conserva el select.
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
            etiqueta.textContent = select.value ? opcion?.textContent?.trim() || textoVacio : textoVacio;
        }

        if (botonLimpiar) {
            botonLimpiar.hidden = !select.value;
        }

        contenedor.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
        renderizarOpcionesBuscadorProducto(contenedor);
    }

    // Convierte cualquier select marcado en el mismo buscador compacto usado por Ingredientes.
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
                        <strong data-producto-search-label></strong>
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
                    requestAnimationFrame(() => buscador.focus());
                }
            });

            buscador?.addEventListener('input', () => renderizarOpcionesBuscadorProducto(contenedor));
            botonLimpiar?.addEventListener('click', (event) => {
                event.stopPropagation();
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
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

    function textoUnidadCatalogoProducto(idUnidad) {
        if (!idUnidad) {
            return '';
        }

        const opcion =
            document.querySelector(`#form_presentacion_unidad option[value="${CSS.escape(String(idUnidad))}"]`) ||
            document.querySelector(`#form_registro_unidad option[value="${CSS.escape(String(idUnidad))}"]`);

        return opcion?.dataset.texto || opcion?.textContent?.trim() || '';
    }

    // En la revisión identifica una unidad temporal sin usar términos ambiguos como "origen".
    function textoUnidadRevisionProducto(idUnidad) {
        const texto = textoUnidadCatalogoProducto(idUnidad);

        return String(idUnidad || '').startsWith('TEMP-') ?
            `Nueva unidad: ${texto || 'Sin nombre'} (se registrará activa)` :
            texto;
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

        if (campo.matches('[data-producto-buscador]')) {
            return document.querySelector(
                `[data-producto-search-select="${CSS.escape(campo.id)}"]`
            ) || campo;
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

        const errorDentroDelControl = objetivo.matches('td, tr');
        let error = errorDentroDelControl
            ? objetivo.querySelector(':scope > .producto-field-error')
            : objetivo.nextElementSibling;

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

    // Limpia el error visual apenas el usuario corrige o cambia el campo.
    function limpiarErrorProducto(idCampo) {
        const campo = typeof idCampo === 'string' ? document.getElementById(idCampo) : idCampo;
        const objetivo = objetivoErrorProducto(campo);

        if (!objetivo) {
            return;
        }

        campo?.classList.remove('is-invalid');
        objetivo.classList.remove('is-invalid');

        const error = objetivo.matches('td, tr')
            ? objetivo.querySelector(':scope > .producto-field-error')
            : objetivo.nextElementSibling;
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

        imagen.onload = prepararZoomEtiquetaProducto;
        imagen.src = url;
        document.getElementById('etiquetaImagenModalNombre').textContent = nombre || 'Imagen de etiqueta';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');

        if (imagen.complete) {
            prepararZoomEtiquetaProducto();
        }
    }

    // Conserva como 100% el tamaño que cabe completo dentro de la vista previa.
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

    function cambiarZoomEtiquetaProducto(cambio) {
        productoEtiquetaZoom = Math.min(3, Math.max(0.5, productoEtiquetaZoom + cambio));
        aplicarZoomEtiquetaProducto();
    }

    function restablecerZoomEtiquetaProducto() {
        productoEtiquetaZoom = 1;
        aplicarZoomEtiquetaProducto();
    }

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

    // Cierra la vista previa sin borrar la imagen seleccionada.
    function cerrarModalEtiquetaProducto() {
        const modal = document.getElementById('modalEtiquetaImagenProducto');
        modal?.classList.remove('is-open');
        modal?.setAttribute('aria-hidden', 'true');
        restablecerZoomEtiquetaProducto();
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
        const presentacionValor = `${cantidadPresentacion.value} ${textoUnidadPresentacion}`.trim();
        const presentacionDescripcion = descripcion.value.trim();
        const presentacionTexto = `${presentacionValor}${presentacionDescripcion ? ' - ' + presentacionDescripcion : ''}`;
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
                    <div class="producto-table-presentacion">
                        <strong>${escaparHtmlProducto(presentacionValor)}</strong>
                        ${presentacionDescripcion ? `<small class="producto-table-descripcion">${escaparHtmlProducto(presentacionDescripcion)}</small>` : ''}
                    </div>
                </td>
                <td><span class="producto-pill">${escaparHtmlProducto(estadoRegistro.value)}</span></td>
                <td>
                    <div class="producto-row-actions">
                        <button type="button" class="producto-action-icon is-edit"
                            title="Editar presentación y registro" aria-label="Editar presentación y registro"
                            onclick="editarFilaRegistroPresentacionProducto(this)">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="producto-action-icon is-delete"
                            title="Eliminar presentación y registro" aria-label="Eliminar presentación y registro"
                            onclick="quitarFilaProducto(this)">
                            <i class="fa-solid fa-trash-can"></i>
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
                        <strong>${escaparHtmlProducto(presentacionValor)}</strong>
                        ${presentacionDescripcion ? `<small class="producto-table-descripcion">${escaparHtmlProducto(presentacionDescripcion)}</small>` : ''}
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
                            aria-label="Ver etiqueta"
                            onclick="abrirModalEtiquetaProducto(${indicePresentacion})">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button type="button" class="producto-action-icon is-edit"
                            title="Editar presentación y registro" aria-label="Editar presentación y registro"
                            onclick="editarFilaRegistroPresentacionProducto(this)">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="producto-action-icon is-delete"
                            title="Eliminar presentación y registro" aria-label="Eliminar presentación y registro"
                            onclick="quitarFilaProducto(this)">
                            <i class="fa-solid fa-trash-can"></i>
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

        if (!select?.value) {
            return 'No registrado';
        }

        const texto = select?.options[select.selectedIndex]?.text?.trim();

        return texto || 'No registrado';
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

    // Destaca los catálogos que todavía no existen y que se crearán junto con el producto.
    // Cada bloque presenta todos sus campos una sola vez para no mezclar datos nuevos con los ya registrados.
    function bloqueCatalogoNuevoProducto(titulo, campos) {
        const filas = (Array.isArray(campos) ? campos : []).map((campo) => `
                <div>
                    <dt>${escaparHtmlProducto(campo.etiqueta)}</dt>
                    <dd>${escaparHtmlProducto(textoResumenProducto(campo.valor)).replaceAll('\n', '<br>')}</dd>
                </div>
            `).join('');

        return `
                <article class="producto-review-new-catalog">
                    <header>
                        <div>
                            <span class="producto-review-new-badge">Nuevo</span>
                            <h5>${escaparHtmlProducto(titulo)}</h5>
                        </div>
                        <p>Se registrará al guardar el producto.</p>
                    </header>
                    <dl>${filas}</dl>
                </article>
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

    // Localiza la imagen real de una presentación nueva, recuperada o tomada del catálogo.
    function datosEtiquetaResumenProducto(indice, presentacion = {}) {
        const input = document.querySelector(`input[name="presentaciones[${indice}][url_etiqueta]"]`);
        const datosOld = input?.id ? productoEtiquetaOldUrls[input.id] : null;
        const opcionCatalogo = presentacion.id_presentacion_origen ?
            document.querySelector(`#form_presentacion_catalogo option[value="${presentacion.id_presentacion_origen}"]`) :
            null;
        const datos = productoEtiquetaUrls[indice] || datosOld;
        const url = datos?.url || input?.dataset.urlInicial || opcionCatalogo?.dataset.etiquetaUrl || '';
        const nombre = input?.files?.[0]?.name || datos?.nombre || opcionCatalogo?.dataset.etiquetaNombre ||
            (url ? 'Imagen registrada' : 'Sin imagen');

        return { url, nombre };
    }

    function abrirEtiquetaResumenProducto(indice) {
        const presentaciones = gruposPorIndiceResumenProducto('presentaciones');
        const etiqueta = datosEtiquetaResumenProducto(indice, presentaciones[indice]);

        if (!etiqueta.url) {
            notificarProducto('info', 'Sin imagen', 'Esta presentación no tiene una etiqueta disponible.');
            return;
        }

        mostrarEtiquetaEnModalProducto(etiqueta.url, etiqueta.nombre);
    }

    // Presenta cada formato junto a sus registros para que la relación sea fácil de comprobar.
    function resumenPresentacionesRegistrosProducto(presentaciones, registros) {
        const listaPresentaciones = Object.entries(presentaciones);

        if (!listaPresentaciones.length) {
            return '<div class="producto-review-empty">No se agregaron presentaciones ni registros.</div>';
        }

        return `<div class="producto-review-presentations">
            ${listaPresentaciones.map(([indice, presentacion], posicion) => {
                const etiqueta = datosEtiquetaResumenProducto(indice, presentacion);
                const registrosAsociados = Object.entries(registros)
                    .filter(([, registro]) => String(registro.id_presentacion_temporal) === String(indice));
                const vistaEtiqueta = etiqueta.url ? `
                    <button type="button" class="producto-review-image" onclick="abrirEtiquetaResumenProducto(${Number(indice)})"
                        title="Ampliar etiqueta" aria-label="Ampliar etiqueta de la presentación ${posicion + 1}">
                        <img src="${escaparHtmlProducto(etiqueta.url)}" alt="Etiqueta de la presentación ${posicion + 1}">
                        <span><i class="fa-solid fa-magnifying-glass-plus"></i></span>
                    </button>` : `
                    <div class="producto-review-image is-empty">
                        <i class="fa-regular fa-image"></i>
                        <span>Sin imagen</span>
                    </div>`;
                const listaRegistros = registrosAsociados.length ? registrosAsociados.map(([, registro], registroPosicion) => `
                    <div class="producto-review-register-row">
                        <div class="producto-review-register-head">
                            <strong>Registro ${registroPosicion + 1}</strong>
                            <span class="producto-review-status ${registro.estado === 'INACTIVO' ? 'is-inactive' : ''}">
                                ${escaparHtmlProducto(registro.estado || 'ACTIVO')}
                            </span>
                        </div>
                        <dl>
                            <div><dt>Código de autorización</dt><dd>${escaparHtmlProducto(textoResumenProducto(registro.codigo_autorizacion))}</dd></div>
                            <div><dt>Nro. de lote</dt><dd>${escaparHtmlProducto(textoResumenProducto(registro.numero_lote))}</dd></div>
                            <div><dt>Fecha de vigencia</dt><dd>${escaparHtmlProducto(textoResumenProducto(registro.fecha_vigencia))}</dd></div>
                            <div><dt>Cantidad registrada</dt><dd>${escaparHtmlProducto(textoResumenProducto(registro.cantidad))}</dd></div>
                            <div><dt>Unidad del registro</dt><dd>${escaparHtmlProducto(textoResumenProducto(textoUnidadRevisionProducto(registro.id_catalogo_unidad)))}</dd></div>
                        </dl>
                    </div>
                `).join('') : '<div class="producto-review-empty">Esta presentación todavía no tiene registros asociados.</div>';

                return `
                    <article class="producto-review-presentation">
                        <header class="producto-review-presentation-head">
                            <div>
                                <span class="producto-review-eyebrow">Presentación ${posicion + 1}</span>
                                <h5>Datos de la presentación</h5>
                            </div>
                            <span class="producto-review-status ${presentacion.estado === 'INACTIVO' ? 'is-inactive' : ''}">
                                ${escaparHtmlProducto(presentacion.estado || 'ACTIVO')}
                            </span>
                        </header>

                        <div class="producto-review-presentation-main">
                            <div class="producto-review-visual">
                                <span class="producto-review-block-label">Vista previa de la etiqueta</span>
                                ${vistaEtiqueta}
                            </div>

                            <div class="producto-review-presentation-content">
                                <dl class="producto-review-presentation-data">
                                    <div><dt>Cantidad de la presentación</dt><dd>${escaparHtmlProducto(textoResumenProducto(presentacion.cantidad))}</dd></div>
                                    <div><dt>Unidad de la presentación</dt><dd>${escaparHtmlProducto(textoResumenProducto(textoUnidadRevisionProducto(presentacion.id_catalogo_unidad)))}</dd></div>
                                    <div><dt>Nombre del archivo de etiqueta</dt><dd>${escaparHtmlProducto(etiqueta.nombre)}</dd></div>
                                </dl>

                                <div class="producto-review-description">
                                    <span>Descripción de la presentación</span>
                                    <p>${escaparHtmlProducto(textoResumenProducto(presentacion.descripcion))}</p>
                                </div>
                            </div>
                        </div>

                        <div class="producto-review-registers">
                            <div class="producto-review-registers-head">
                                <div>
                                    <h6>Registros vinculados a esta presentación</h6>
                                    <p>Datos de autorización, lote, vigencia y cantidad registrada.</p>
                                </div>
                                <span>${registrosAsociados.length}</span>
                            </div>
                            <div class="producto-review-register-list">
                                ${listaRegistros}
                            </div>
                        </div>
                    </article>`;
            }).join('')}
        </div>`;
    }

    // Arma un resumen claro con los mismos datos que se enviarán al guardar el producto.
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
            datosProducto.push(bloqueCatalogoNuevoProducto('Fabricante nuevo', [
                {
                    etiqueta: 'Razón social',
                    valor: valorResumenProducto('#form_fabricante_temporal_razon_social'),
                },
                {
                    etiqueta: 'Nombre del fabricante',
                    valor: valorResumenProducto('#form_fabricante_temporal_nombre'),
                },
                {
                    etiqueta: 'Descripción',
                    valor: valorResumenProducto('#form_fabricante_temporal_descripcion'),
                },
                {
                    etiqueta: 'Estado al registrar',
                    valor: 'Activo',
                },
            ]));
        } else {
            datosProducto.push(itemResumenProducto(
                fabricanteRazonSocial ? 'Fabricante (razón social)' : 'Fabricante',
                fabricanteVisible
            ));
        }

        if (clasificacionQuimicaNueva) {
            datosProducto.push(bloqueCatalogoNuevoProducto('Clasificación química nueva', [
                {
                    etiqueta: 'Nombre de la clasificación',
                    valor: valorResumenProducto('#form_clasificacion_quimica_temporal_nombre'),
                },
                {
                    etiqueta: 'Descripción',
                    valor: valorResumenProducto('#form_clasificacion_quimica_temporal_descripcion'),
                },
                {
                    etiqueta: 'Estado al registrar',
                    valor: 'Activo',
                },
            ]));
        } else {
            datosProducto.push(itemResumenProducto(
                'Clasificación química',
                textoSelectProducto('form_id_clasificacion_quimica')
            ));
        }

        if (clasificacionToxicologicaNueva) {
            datosProducto.push(bloqueCatalogoNuevoProducto('Clasificación toxicológica nueva', [
                {
                    etiqueta: 'Código',
                    valor: valorResumenProducto('#form_clasificacion_toxicologica_temporal_codigo'),
                },
                {
                    etiqueta: 'Descripción',
                    valor: valorResumenProducto('#form_clasificacion_toxicologica_temporal_descripcion'),
                },
                {
                    etiqueta: 'Estado al registrar',
                    valor: 'Activo',
                },
            ]));
        } else {
            datosProducto.push(itemResumenProducto(
                'Clasificación toxicológica',
                textoSelectProducto('form_id_clasificacion_toxicologica')
            ));
        }

        datosProducto.push(itemResumenProducto('Estado del producto', textoSelectProducto('form_estado')));

        const filasIngredientes = Object.entries(ingredientes).map(([indice, ingrediente]) => {
            const esNuevo = String(ingrediente.id_ingrediente || '').startsWith('TEMP-');
            const nombre = ingrediente.nombre || textoResumenProducto(ingrediente.id_ingrediente);

            return [
                Number(indice) + 1,
                esNuevo ? `Nuevo ingrediente: ${nombre}` : nombre,
                ingrediente.porcentaje !== undefined && ingrediente.porcentaje !== '' ?
                `${formatearPorcentajeIngrediente(ingrediente.porcentaje)}%` : '',
                ingrediente.estado || 'ACTIVO',
            ];
        });

        const nombreProducto = textoResumenProducto(valorResumenProducto('#form_nombre_comercial'));
        const codigoProducto = textoResumenProducto(valorResumenProducto('#form_codigo'));
        const resumenGeneral = `
            <div class="producto-review-overview">
                <div class="producto-review-overview-identity">
                    <span>Producto listo para revisión</span>
                    <dl class="producto-review-product-data">
                        <div>
                            <dt>Nombre comercial del producto</dt>
                            <dd>${escaparHtmlProducto(nombreProducto)}</dd>
                        </div>
                        <div>
                            <dt>Código del producto</dt>
                            <dd>${escaparHtmlProducto(codigoProducto)}</dd>
                        </div>
                    </dl>
                </div>
                <dl>
                    <div><dt>Ingredientes</dt><dd>${Object.keys(ingredientes).length}</dd></div>
                    <div><dt>Presentaciones</dt><dd>${Object.keys(presentaciones).length}</dd></div>
                    <div><dt>Registros</dt><dd>${Object.keys(registros).length}</dd></div>
                </dl>
            </div>`;

        destino.innerHTML = resumenGeneral + [
            grupoResumenProducto(
                'Datos principales del producto',
                'Información que identifica y clasifica el producto.',
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
                'Presentaciones y registros',
                'Cada etiqueta aparece junto a la presentación y los registros que le pertenecen.',
                resumenPresentacionesRegistrosProducto(presentaciones, registros)
            ),
        ].join('');
    }

    // Guarda el avance temporal en el navegador sin enviar el formulario ni recargar la pagina.
    function guardarAvanceProducto() {
        const formulario = document.getElementById('formProductoWizard');
        const datos = {};
        let tieneArchivosTemporales = false;

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

    // Después de una validación vuelve al paso que contiene el primer dato por corregir.
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

    // Convierte presentaciones.0.cantidad al nombre HTML presentaciones[0][cantidad].
    function nombreHtmlCampoProducto(campo) {
        const partes = String(campo || '').split('.');
        return partes.slice(1).reduce((nombre, parte) => `${nombre}[${parte}]`, partes[0] || '');
    }

    // Muestra los errores recibidos sin recargar la página ni limpiar campos o archivos.
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
        resumen?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Muestra una espera que cubre el formulario mientras el servidor registra el producto.
    function mostrarEsperaGuardadoProducto(mostrar) {
        const overlay = document.getElementById('productoGuardandoOverlay');

        if (!overlay) {
            return;
        }

        overlay.classList.toggle('hidden', !mostrar);
        overlay.classList.toggle('flex', mostrar);
        overlay.setAttribute('aria-hidden', mostrar ? 'false' : 'true');
    }

    // Guarda sin recargar; una respuesta 422 deja intacto todo el formulario para corregir solo el error.
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
            const respuesta = await fetch(formulario.action, {
                method: formulario.method || 'POST',
                body: new FormData(formulario),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const resultado = await respuesta.json().catch(() => ({}));

            if (respuesta.status === 422) {
                mostrarErroresGuardadoProducto(resultado.errors || {});
                return;
            }

            if (!respuesta.ok) {
                throw new Error(resultado.message || 'No fue posible guardar el producto.');
            }

            if (productoEmbebidoTramite && resultado.producto) {
                window.parent?.postMessage({
                    tipo: 'producto-registrado-tramite',
                    producto: resultado.producto,
                }, window.location.origin);
                notificarProducto('success', 'Producto registrado', resultado.message);
                return;
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
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('formProductoWizard')?.addEventListener('submit', enviarProductoSinPerderDatos);

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
                select.dispatchEvent(new Event('change', { bubbles: true }));
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
@if (!$productoEmbebido)
    </x-admin-layout>
@endif
