@php
    // Modo embebido: permite usar solo el formulario dentro de otro flujo, sin cargar el layout completo.
    $productoEmbebido = $productoEmbebido ?? request()->boolean('embebido');

    /*
     * Origen del formulario:
     * - Desde Productos: vuelve al listado.
     * - Desde un trámite: vuelve al detalle del trámite que abrió este formulario.
     */
    $productoCertificadoOrigen = old('form_id_certificado', request('form_id_certificado'));
    $productoBandejaOrigen = old('form_bandeja', request('bandeja', 'recibidas'));
    $productoRetornoSolicitado = old('form_retorno', request('return_to'));
    $productoRetornoSeguro = route('productos_index');

    if (filled($productoRetornoSolicitado) && \Illuminate\Support\Str::startsWith($productoRetornoSolicitado, [url('/'), '/'])) {
        $productoRetornoSeguro = $productoRetornoSolicitado;
    }
@endphp

@if (! $productoEmbebido)
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
        'name' => 'Registrar Producto',
    ],
]">
@endif

    @php
        /*
         * Catalogos para armar la vista.
         * Cuando se implemente el guardado se pueden mover estas consultas al controlador.
         */
        $tiposProductosCatalogo =
            $tiposProductos ??
            \App\Models\TipoProducto::query()->where('estado', 'ACTIVO')->orderBy('descripcion')->get();

        $fabricantesCatalogo =
            $fabricantes ?? \App\Models\Fabricante::query()->where('estado', 'ACTIVO')->orderBy('nombre')->get();

        /*
         * Para productos se guarda id_territorio_pais, por eso este catalogo
         * muestra solo territorios cuyo ambito es Pais.
         */
        $territoriosCatalogo = isset($territorios)
            ? collect($territorios)->where('id_ambito', 1)->sortBy('nombre')->values()
            : \App\Models\Territorio::query()
                ->where('estado', 'ACTIVO')
                ->where('id_ambito', 1)
                ->orderBy('nombre')
                ->get();

        $importadoresCatalogo =
            $importadores ??
            \App\Models\Persona::with(['natural', 'empresa'])
                ->where('estado', 'ACTIVO')
                ->orderBy('id')
                ->get();

        $ingredientesCatalogo =
            $ingredientes ?? \App\Models\Ingrediente::query()->where('estado', 'ACTIVO')->orderBy('nombre')->get();

        /*
         * Catalogo usado solo como apoyo visual para reutilizar presentaciones ya registradas.
         * La vista las filtra por importador y por el codigo/nombre del producto actual.
         */
        $presentacionesCatalogo =
            $presentacionesCatalogo ??
            \App\Models\Presentacion::with('producto')
                ->where('estado', 'ACTIVO')
                ->whereHas('producto', fn ($consulta) => $consulta->where('estado', 'ACTIVO'))
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
        $claseErrorProducto = fn (string $campo) => $errors->has($campo) ? ' is-invalid' : '';
        $ingredientesOld = old('ingredientes_productos', []);
        $presentacionesOld = old('presentaciones', []);
        $registrosOld = old('registros', []);
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
        action="{{ route('productos_store', $productoEmbebido ? ['embebido' => 1] : []) }}" method="POST" enctype="multipart/form-data"
        class="producto-wizard" autocomplete="off" @if ($productoEmbebido) target="productoSubmitFrame" @endif>
        @csrf

        {{-- Datos temporales de catalogos creados desde modales; el controlador los usa al guardar el producto. --}}
        <input type="hidden" id="form_fabricante_temporal_nombre" name="form_fabricante_temporal_nombre"
            value="{{ old('form_fabricante_temporal_nombre') }}">
        <input type="hidden" id="form_fabricante_temporal_razon_social" name="form_fabricante_temporal_razon_social"
            value="{{ old('form_fabricante_temporal_razon_social') }}">
        <input type="hidden" id="form_fabricante_temporal_descripcion" name="form_fabricante_temporal_descripcion"
            value="{{ old('form_fabricante_temporal_descripcion') }}">
        <input type="hidden" id="form_tipo_producto_temporal_descripcion" name="form_tipo_producto_temporal_descripcion"
            value="{{ old('form_tipo_producto_temporal_descripcion') }}">
        <input type="hidden" id="form_tipo_producto_temporal_codigo" name="form_tipo_producto_temporal_codigo"
            value="{{ old('form_tipo_producto_temporal_codigo') }}">
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
                                Registrar nuevo producto
                            </h1>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                                Complete el producto, sus ingredientes y presentaciones. Luego puede preparar la
                                autorización.
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
                        <span>Presentaciones</span>
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
                    @include($productoEmbebido ? 'seguimientos_certificados.producto.producto' : 'productos.create.producto')

                    {{-- Paso 2: tabla pivote ingredientes_productos. --}}
                    @include($productoEmbebido ? 'seguimientos_certificados.producto.ingredientes' : 'productos.create.ingredientes')

                    {{-- Paso 3: presentaciones del producto. --}}
                    @include($productoEmbebido ? 'seguimientos_certificados.producto.presentaciones' : 'productos.create.presentaciones')

                    {{-- Los registros se agregan dentro del paso de presentaciones para mantener la relacion. --}}
                    {{-- @include('productos.create.registros') --}}

                    {{-- Paso 4: resumen antes de guardar producto. --}}
                    @include($productoEmbebido ? 'seguimientos_certificados.producto.revision' : 'productos.create.revision')

                </div>

                {{-- Botones inferiores del wizard. --}}
                <div class="producto-action-bar">
                    <div></div>

                    <div class="producto-action-buttons">
                        @if (! $productoEmbebido)
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
                        <p class="producto-modal-subtitle">Agregue un fabricante temporal para seleccionarlo en el producto.</p>
                    </div>
                </div>
            </div>

            <div class="producto-modal-body">
                <div>
                    <label class="producto-field-label" for="modal_fabricante_nombre">Nombre del fabricante</label>
                    <input class="producto-input" id="modal_fabricante_nombre" type="text"
                        placeholder="Ej: Agroquímica Andina">
                </div>

                <div>
                    <label class="producto-field-label" for="modal_fabricante_razon_social">Razón social</label>
                    <input class="producto-input" id="modal_fabricante_razon_social" type="text"
                        placeholder="Ej: Agroquímica Andina S.R.L.">
                </div>

                <div>
                    <label class="producto-field-label" for="modal_fabricante_descripcion">Descripción</label>
                    <textarea class="producto-textarea" id="modal_fabricante_descripcion"
                        placeholder="Detalle corto del fabricante"></textarea>
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

    {{-- Modal visual para tipo de producto. --}}
    <div class="producto-modal" id="modalTipoProducto">
        <div class="producto-modal-card is-compact">
            <div class="producto-modal-head">
                <div class="producto-modal-title-row">
                    <span class="producto-modal-icon">
                        <i class="fa-solid fa-tags"></i>
                    </span>
                    <div>
                        <h3 class="producto-modal-title">Nuevo tipo de producto</h3>
                        <p class="producto-modal-subtitle">Agregue un tipo temporal si no aparece en el catálogo.</p>
                    </div>
                </div>
            </div>

            <div class="producto-modal-body">
                <div>
                    <label class="producto-field-label" for="modal_tipo_producto_descripcion">Descripción del tipo</label>
                    <input class="producto-input" id="modal_tipo_producto_descripcion" type="text"
                        placeholder="Ej: Herbicida">
                </div>

                <div>
                    <label class="producto-field-label" for="modal_tipo_producto_codigo">Código</label>
                    <input class="producto-input" id="modal_tipo_producto_codigo" type="text"
                        placeholder="Ej: HERB">
                </div>
            </div>

            <div class="producto-modal-actions">
                <button type="button" class="producto-btn producto-btn-secondary"
                    onclick="cerrarModalProducto('modalTipoProducto')">Cancelar</button>
                <button type="button" class="producto-btn producto-btn-primary"
                    onclick="agregarOpcionTemporalProducto('modalTipoProducto', 'form_id_tipo_producto', 'modal_tipo_producto_descripcion')">
                    Agregar a la lista
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

                <div>
                    <label class="producto-field-label" for="modal_ingrediente_composicion">Composición</label>
                    <input class="producto-input" id="modal_ingrediente_composicion" type="text"
                        placeholder="Ej: Sal isopropilamina">
                </div>

                <div>
                    <label class="producto-field-label" for="modal_ingrediente_riesgo_salud">Riesgo de salud</label>
                    <input class="producto-input" id="modal_ingrediente_riesgo_salud" type="text"
                        placeholder="Ej: Moderado">
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

    {{-- Modal de vista previa para etiqueta PDF de presentaciones. --}}
    <div class="producto-modal" id="modalEtiquetaPdfProducto" aria-hidden="true">
        <div class="producto-modal-card is-pdf" role="dialog" aria-modal="true"
            aria-labelledby="tituloModalEtiquetaPdf">
            <div class="producto-modal-head">
                <div class="producto-modal-title-row">
                    <span class="producto-modal-icon">
                        <i class="fa-solid fa-file-pdf"></i>
                    </span>
                    <div>
                        <h3 id="tituloModalEtiquetaPdf" class="producto-modal-title">Vista previa de etiqueta PDF</h3>
                        <p id="etiquetaPdfModalNombre" class="producto-modal-subtitle">Sin archivo seleccionado.</p>
                    </div>
                </div>

                <button type="button" class="producto-modal-close" onclick="cerrarModalEtiquetaProducto()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <iframe id="etiquetaPdfPreviewFrame" class="producto-pdf-frame"
                title="Vista previa de etiqueta PDF"></iframe>
        </div>
    </div>

    <script>
        // Guarda el paso actual del wizard para mostrar una sola seccion a la vez.
        let productoPasoActual = 0;

        // Contadores usados para crear nombres de arrays dinamicos sin repetir indices.
        let productoIngredienteIndice = {{ count($ingredientesOld) }};
        let productoPresentacionIndice = {{ count($presentacionesOld) }};
        let productoRegistroIndice = {{ count($registrosOld) }};
        let productoEtiquetaPreviewUrl = null;
        let productoEtiquetaOrigenActual = null;
        const productoEtiquetaUrls = {};
        const productoEtiquetaOldUrls = {};
        const productoErroresLaravel = @json($errors->messages());
        const productoEmbebidoTramite = @json($productoEmbebido);

        // Entradas del formulario separadas por modulo para evitar mezclar ids entre producto, ingredientes y registro.
        const productoCamposFormulario = {
            producto: {
                codigo: 'form_codigo',
                nombreComercial: 'form_nombre_comercial',
                fabricante: 'form_id_fabricante',
                tipoProducto: 'form_id_tipo_producto',
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
                fechaVigencia: 'form_registro_fecha_vigencia',
                cantidad: 'form_registro_cantidad',
                unidad: 'form_registro_unidad',
                estado: 'form_registro_estado',
            },
        };

        const productoTitulos = [
            ['Datos principales del producto', 'Identifique el producto, importador, fabricante y tipo.'],
            ['Ingredientes', 'Agregue los ingredientes y sus porcentajes.'],
            ['Presentaciones y registros', 'Registre la presentacion y los datos del registro del producto.'],
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
            botonSiguiente.innerHTML = productoPasoActual === ultimoPaso
                ? '<i class="fa-solid fa-floppy-disk"></i> Guardar producto'
                : 'Siguiente <i class="fa-solid fa-chevron-right"></i>';

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

        // Devuelve el elemento visual que debe pintarse en rojo; en PDF se pinta la tarjeta, no el input oculto.
        function objetivoErrorProducto(campo) {
            if (!campo) {
                return null;
            }

            if (campo.classList.contains('producto-upload-input')) {
                return campo.closest('.producto-table-file-input') || document.getElementById('presentacionEtiquetaWrapper');
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
                ['form_id_importador_persona', 'Seleccione el importador.'],
                ['form_id_territorio_pais', 'Seleccione el pais.'],
                ['form_id_fabricante', 'Seleccione el fabricante.'],
                ['form_id_tipo_producto', 'Seleccione el tipo de producto.'],
            ];

            const resultados = reglas.map(([id, mensaje]) => validarRequeridoProducto(id, mensaje));
            const valido = resultados.every(Boolean);

            if (!valido) {
                document.querySelector('.is-invalid')?.focus?.();
            }

            return valido;
        }

        // Actualiza el texto y el estado de los botones del PDF principal de presentacion.
        function actualizarControlEtiquetaActual(nombreArchivo, tienePdf) {
            const nombre = document.getElementById('presentacionEtiquetaNombre');
            const botonVer = document.getElementById('btnVerEtiquetaPdf');
            const botonQuitar = document.getElementById('btnQuitarEtiquetaPdf');

            if (nombre) {
                nombre.textContent = nombreArchivo;
            }

            if (botonVer) {
                botonVer.disabled = !tienePdf;
            }

            if (botonQuitar) {
                botonQuitar.disabled = !tienePdf;
            }
        }

        // Deja el control PDF listo para una nueva presentacion sin tocar las filas ya agregadas.
        function reiniciarControlEtiquetaActual() {
            actualizarControlEtiquetaActual('Sin PDF seleccionado.', false);
        }

        // Quita el PDF seleccionado antes de agregar la presentacion a la tabla.
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

        // Muestra el nombre del PDF seleccionado y prepara una vista previa local antes de agregarlo a la tabla.
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

            if (archivo.type !== 'application/pdf' && !archivo.name.toLowerCase().endsWith('.pdf')) {
                etiqueta.value = '';
                actualizarControlEtiquetaActual('Solo se permite seleccionar archivos PDF.', false);
                marcarErrorProducto(etiqueta, 'Solo se permite seleccionar archivos PDF.');
                return;
            }

            productoEtiquetaPreviewUrl = URL.createObjectURL(archivo);
            actualizarControlEtiquetaActual(archivo.name, true);
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
                const mismoProducto = codigoProducto
                    ? codigoCatalogo === codigoProducto
                    : nombreProducto && nombreCatalogo === nombreProducto;
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
            document.getElementById('form_presentacion_unidad').value = opcion.dataset.unidad || '';
            document.getElementById('form_presentacion_descripcion').value = opcion.dataset.descripcion || '';
            document.getElementById('form_presentacion_estado').value = opcion.dataset.estado || 'ACTIVO';

            productoEtiquetaOrigenActual = opcion.dataset.etiquetaUrl
                ? {
                    id: esTemporal ? opcion.dataset.temporalIndice : opcion.value,
                    url: opcion.dataset.etiquetaUrl,
                    nombre: opcion.dataset.etiquetaNombre || 'Etiqueta registrada',
                }
                : null;

            actualizarControlEtiquetaActual(
                productoEtiquetaOrigenActual ? productoEtiquetaOrigenActual.nombre : 'Presentacion sin PDF registrado.',
                Boolean(productoEtiquetaOrigenActual)
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

            const botonSeleccionarPdf = document.querySelector('#presentacionEtiquetaWrapper .producto-upload-button.is-select');
            if (botonSeleccionarPdf) {
                botonSeleccionarPdf.disabled = bloquear;
            }
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
            opcion.dataset.unidad = datos.unidad || '';
            opcion.dataset.descripcion = datos.descripcion || '';
            opcion.dataset.estado = datos.estado || 'ACTIVO';
            opcion.dataset.etiquetaUrl = datos.etiquetaUrl || '';
            opcion.dataset.etiquetaNombre = datos.etiquetaNombre || 'Etiqueta PDF';

            catalogo.appendChild(opcion);
        }

        // Busca si la presentacion ya existe en el formulario para que el nuevo registro apunte a esa misma fila logica.
        function buscarIndicePresentacionTemporalProducto(origenId, cantidad, unidad, descripcion) {
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
                normalizarTextoProducto(unidad),
                normalizarTextoProducto(descripcion),
            ].join('|');

            for (const bloque of document.querySelectorAll('#presentacionesOcultasProducto [data-presentacion-temporal]')) {
                const indice = bloque.dataset.presentacionTemporal;
                const claveActual = [
                    normalizarTextoProducto(bloque.querySelector(`input[name="presentaciones[${indice}][cantidad]"]`)?.value),
                    normalizarTextoProducto(bloque.querySelector(`input[name="presentaciones[${indice}][unidad]"]`)?.value),
                    normalizarTextoProducto(bloque.querySelector(`input[name="presentaciones[${indice}][descripcion]"]`)?.value),
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
                <input type="hidden" name="presentaciones[${indicePresentacion}][unidad]" value="${escaparHtmlProducto(datos.unidad)}">
                <input type="hidden" name="presentaciones[${indicePresentacion}][descripcion]" value="${escaparHtmlProducto(datos.descripcion)}">
                <input type="hidden" name="presentaciones[${indicePresentacion}][estado]" value="${escaparHtmlProducto(datos.estado)}">
                <input type="hidden" name="presentaciones[${indicePresentacion}][id_presentacion_origen]" value="${escaparHtmlProducto(datos.origenId)}">
                <span class="presentacion-etiqueta-input"></span>
            `;

            contenedor.appendChild(bloque);
        }

        // Evita agregar dos veces el mismo registro para una misma presentacion.
        function existeRegistroDuplicadoProducto(indicePresentacion, codigo, fechaVigencia, cantidad, unidad) {
            const claveNueva = [
                String(indicePresentacion),
                normalizarTextoProducto(codigo),
                normalizarTextoProducto(fechaVigencia),
                normalizarTextoProducto(cantidad),
                normalizarTextoProducto(unidad),
            ].join('|');

            return Array.from(document.querySelectorAll('#tablaRegistrosPresentacionesProducto tr[data-presentacion-indice]'))
                .some((fila) => {
                    const claveFila = [
                        fila.dataset.presentacionIndice,
                        normalizarTextoProducto(fila.querySelector('input[name$="[codigo_autorizacion]"]')?.value),
                        normalizarTextoProducto(fila.querySelector('input[name$="[fecha_vigencia]"]')?.value),
                        normalizarTextoProducto(fila.querySelector('input[name$="[cantidad]"][name^="registros"]')?.value),
                        normalizarTextoProducto(fila.querySelector('input[name$="[unidad]"][name^="registros"]')?.value),
                    ].join('|');

                    return claveFila === claveNueva;
                });
        }

        // Actualiza botones de las filas que vuelven desde Laravel cuando hubo error de validacion.
        function actualizarEtiquetaOldProducto(input) {
            const archivo = input?.files?.[0] ?? null;
            const contenedor = input?.closest('.producto-table-file-input');
            const nombre = contenedor?.querySelector('.producto-table-file-name');
            const botonVer = contenedor?.querySelector('[data-etiqueta-old-ver]');
            const botonQuitar = contenedor?.querySelector('[data-etiqueta-old-quitar]');

            limpiarErrorProducto(input);

            if (productoEtiquetaOldUrls[input.id]?.url) {
                URL.revokeObjectURL(productoEtiquetaOldUrls[input.id].url);
                delete productoEtiquetaOldUrls[input.id];
            }

            if (!archivo) {
                if (nombre) {
                    nombre.textContent = 'Debe volver a seleccionar PDF';
                }
                if (botonVer) {
                    botonVer.disabled = true;
                    botonVer.removeAttribute('onclick');
                }
                if (botonQuitar) {
                    botonQuitar.disabled = true;
                }
                return;
            }

            if (archivo.type !== 'application/pdf' && !archivo.name.toLowerCase().endsWith('.pdf')) {
                input.value = '';
                if (nombre) {
                    nombre.textContent = 'Solo PDF';
                }
                if (botonVer) {
                    botonVer.disabled = true;
                    botonVer.removeAttribute('onclick');
                }
                if (botonQuitar) {
                    botonQuitar.disabled = true;
                }
                marcarErrorProducto(input, 'Solo se permite seleccionar archivos PDF.');
                return;
            }

            productoEtiquetaOldUrls[input.id] = {
                url: URL.createObjectURL(archivo),
                nombre: archivo.name,
            };

            if (nombre) {
                nombre.textContent = archivo.name;
            }

            if (botonVer) {
                botonVer.disabled = false;
                botonVer.setAttribute('onclick', `abrirModalEtiquetaOldProducto('${input.id}')`);
            }

            if (botonQuitar) {
                botonQuitar.disabled = false;
            }
        }

        // Limpia el PDF de una fila restaurada por Laravel sin borrar los demas datos de la presentacion.
        function limpiarEtiquetaOldProducto(idInput) {
            const input = document.getElementById(idInput);

            if (!input) {
                return;
            }

            input.value = '';
            actualizarEtiquetaOldProducto(input);
            limpiarErrorProducto(input);
        }

        // Abre la vista previa de un PDF cargado nuevamente en una fila vieja.
        function abrirModalEtiquetaOldProducto(idInput) {
            const datos = productoEtiquetaOldUrls[idInput];

            if (!datos?.url) {
                notificarProducto('info', 'Sin PDF', 'Seleccione una etiqueta PDF para verla.');
                return;
            }

            document.getElementById('etiquetaPdfPreviewFrame').src = datos.url;
            document.getElementById('etiquetaPdfModalNombre').textContent = datos.nombre;
            document.getElementById('modalEtiquetaPdfProducto').classList.add('is-open');
        }

        // Abre el modal de vista previa para el PDF actual o para el PDF guardado en una fila.
        function abrirModalEtiquetaProducto(indice = null) {
            const datosFila = indice !== null ? productoEtiquetaUrls[indice] : null;
            const url = datosFila?.url || productoEtiquetaPreviewUrl || productoEtiquetaOrigenActual?.url;
            const nombre = datosFila?.nombre ||
                productoEtiquetaOrigenActual?.nombre ||
                document.getElementById('presentacionEtiquetaNombre')?.textContent ||
                'Etiqueta PDF';

            if (!url) {
                notificarProducto('info', 'Sin PDF', 'Seleccione una etiqueta PDF para verla.');
                return;
            }

            document.getElementById('etiquetaPdfPreviewFrame').src = url;
            document.getElementById('etiquetaPdfModalNombre').textContent = nombre;
            document.getElementById('modalEtiquetaPdfProducto').classList.add('is-open');
        }

        // Cierra el modal de vista previa sin borrar el PDF seleccionado.
        function cerrarModalEtiquetaProducto() {
            document.getElementById('modalEtiquetaPdfProducto')?.classList.remove('is-open');
        }

        // Agrega un ingrediente a la tabla y crea inputs hidden para enviarlo al controlador.
        function agregarIngredienteProducto() {
            const select = document.getElementById('form_ingrediente_select');
            const porcentaje = document.getElementById('form_ingrediente_porcentaje');
            const estado = document.getElementById('form_ingrediente_estado');
            const ingredienteId = select.value;
            const opcionIngrediente = select.options[select.selectedIndex];
            const ingredienteNombre = opcionIngrediente?.dataset.nombre || opcionIngrediente?.text || 'Sin nombre';
            const ingredienteComposicion = opcionIngrediente?.dataset.composicion || 'Sin composicion';
            const ingredienteRiesgoSalud = opcionIngrediente?.dataset.riesgoSalud || 'Sin riesgo registrado';
            const productoNombre = document.getElementById('form_nombre_comercial')?.value || 'Sin nombre comercial';
            const productoCodigo = document.getElementById('form_codigo')?.value || 'Sin código';
            const ingredienteValido = validarRequeridoProducto('form_ingrediente_select', 'Seleccione un ingrediente.');
            const porcentajeValido = validarNumeroProducto('form_ingrediente_porcentaje', 'Ingrese un porcentaje valido.', 0, 100);

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
                <td>${productoIngredienteIndice + 1}</td>
                <td>
                    <strong class="ingrediente-producto-nombre">${escaparHtmlProducto(productoNombre)}</strong>
                    <div class="text-xs text-slate-500 ingrediente-producto-codigo">${escaparHtmlProducto(productoCodigo)}</div>
                </td>
                <td>
                    <strong>${escaparHtmlProducto(ingredienteNombre)}</strong>
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][id_ingrediente]" value="${ingredienteId}">
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][nombre]" value="${escaparHtmlProducto(ingredienteNombre)}">
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][composicion]" value="${escaparHtmlProducto(ingredienteComposicion)}">
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][riesgo_salud]" value="${escaparHtmlProducto(ingredienteRiesgoSalud)}">
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][porcentaje]" value="${porcentaje.value}">
                    <input type="hidden" name="ingredientes_productos[${productoIngredienteIndice}][estado]" value="${estado.value}">
                </td>
                <td>${escaparHtmlProducto(ingredienteComposicion)}</td>
                <td>${escaparHtmlProducto(ingredienteRiesgoSalud)}</td>
                <td>${porcentaje.value}%</td>
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
            actualizarResumenProducto();
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
            const fechaVigencia = document.getElementById('form_registro_fecha_vigencia');
            const cantidadRegistro = document.getElementById('form_registro_cantidad');
            const unidadRegistro = document.getElementById('form_registro_unidad');
            const estadoRegistro = document.getElementById('form_registro_estado');
            const catalogoPresentacion = document.getElementById('form_presentacion_catalogo');
            const opcionPresentacion = catalogoPresentacion?.options[catalogoPresentacion.selectedIndex];
            const indiceTemporalSeleccionado = opcionPresentacion?.dataset.temporalPresentacion === '1'
                ? opcionPresentacion.dataset.temporalIndice
                : null;
            const archivoEtiqueta = etiqueta?.files?.[0] ?? null;
            const origenId = origenPresentacion?.value || '';
            const indiceExistente = indiceTemporalSeleccionado ?? buscarIndicePresentacionTemporalProducto(
                origenId,
                cantidadPresentacion.value,
                unidadPresentacion.value,
                descripcion.value
            );
            const reutilizaPresentacion = indiceExistente !== null && indiceExistente !== undefined;
            const tieneEtiquetaOrigen = Boolean(origenId && productoEtiquetaOrigenActual?.id === origenId && productoEtiquetaOrigenActual?.url);
            const tieneEtiquetaTemporal = Boolean(reutilizaPresentacion && productoEtiquetaUrls[indiceExistente]?.url);
            const productoNombre = document.getElementById('form_nombre_comercial')?.value || 'Sin nombre comercial';
            const productoCodigo = document.getElementById('form_codigo')?.value || 'Sin codigo';
            const tipoProductoSelect = document.getElementById('form_id_tipo_producto');
            const productoTipo = tipoProductoSelect?.value
                ? (tipoProductoSelect.selectedOptions?.[0]?.textContent?.trim() || 'Sin tipo')
                : 'Sin tipo';

            const cantidadValida = validarNumeroProducto('form_presentacion_cantidad', 'Ingrese una cantidad valida.', 1);
            const unidadValida = validarRequeridoProducto('form_presentacion_unidad', 'Ingrese la unidad.');
            const codigoValido = validarRequeridoProducto(
                'form_registro_codigo_autorizacion',
                'Ingrese el codigo de autorizacion.'
            );

            if (!cantidadValida || !unidadValida || !codigoValido) {
                return;
            }

            if (!archivoEtiqueta && !tieneEtiquetaOrigen && !tieneEtiquetaTemporal) {
                marcarErrorProducto(etiqueta, 'Seleccione la etiqueta PDF.');
                return;
            }

            if (archivoEtiqueta && archivoEtiqueta.type !== 'application/pdf' && !archivoEtiqueta.name.toLowerCase().endsWith('.pdf')) {
                marcarErrorProducto(etiqueta, 'Solo se permite seleccionar archivos PDF.');
                return;
            }

            document.getElementById('sinRegistrosPresentacionesProducto')?.remove();

            const indicePresentacion = reutilizaPresentacion ? Number(indiceExistente) : productoPresentacionIndice;
            const indiceRegistro = productoRegistroIndice;
            const presentacionTexto = `${cantidadPresentacion.value} ${unidadPresentacion.value}${descripcion.value ? ' - ' + descripcion.value : ''}`;
            const etiquetaUrl = archivoEtiqueta
                ? (productoEtiquetaPreviewUrl || URL.createObjectURL(archivoEtiqueta))
                : (productoEtiquetaOrigenActual?.url || productoEtiquetaUrls[indicePresentacion]?.url);
            const etiquetaNombre = archivoEtiqueta
                ? archivoEtiqueta.name
                : (productoEtiquetaOrigenActual?.nombre || productoEtiquetaUrls[indicePresentacion]?.nombre || 'Etiqueta PDF');
            const origenPresentacionTexto = origenId
                ? `Copiada de presentacion #${origenId}`
                : (reutilizaPresentacion ? 'Presentacion ya agregada' : 'Nueva presentacion');

            if (existeRegistroDuplicadoProducto(
                indicePresentacion,
                codigo.value,
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
                    unidad: unidadPresentacion.value,
                    descripcion: descripcion.value,
                    estado: estadoPresentacion.value,
                    origenId,
                });
            }

            const fila = document.createElement('tr');
            fila.dataset.tipoFila = 'registro';
            fila.dataset.presentacionIndice = indicePresentacion;
            fila.dataset.presentacionTexto = presentacionTexto;

            fila.innerHTML = `
                <td>${indiceRegistro + 1}</td>
                <td>
                    <div class="producto-table-product">
                        <div class="producto-table-product-title">
                            <strong class="presentacion-producto-nombre">${escaparHtmlProducto(productoNombre)}</strong>
                            <span class="producto-table-status">ACTIVO</span>
                        </div>
                        <div class="producto-table-product-line">
                            <i class="fa-solid fa-flask"></i>
                            <span>Tipo de producto</span>
                            <strong class="presentacion-producto-tipo">${escaparHtmlProducto(productoTipo || 'Sin tipo')}</strong>
                        </div>
                        <div class="producto-table-product-line">
                            <i class="fa-solid fa-tag"></i>
                            <span>Codigo producto</span>
                            <strong class="presentacion-producto-codigo">${escaparHtmlProducto(productoCodigo)}</strong>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="producto-table-detail">
                        <div>
                            <span>Cantidad</span>
                            <strong>${escaparHtmlProducto(cantidadPresentacion.value)}</strong>
                        </div>
                        <div>
                            <span>Unidad</span>
                            <strong>${escaparHtmlProducto(unidadPresentacion.value)}</strong>
                        </div>
                        <div class="is-wide">
                            <span>Descripcion</span>
                            <strong>${escaparHtmlProducto(descripcion.value || 'Sin descripcion')}</strong>
                        </div>
                        <div class="is-wide">
                            <span>Origen</span>
                            <strong>${escaparHtmlProducto(origenPresentacionTexto)}</strong>
                        </div>
                        <div class="is-wide">
                            <span>Etiqueta PDF</span>
                            <div class="producto-table-file">
                                <span class="producto-table-file-icon">
                                    <i class="fa-regular fa-file-pdf"></i>
                                </span>
                                <span class="producto-table-file-name">${escaparHtmlProducto(etiquetaNombre)}</span>
                                <button type="button" class="producto-upload-button is-view" onclick="abrirModalEtiquetaProducto(${indicePresentacion})">
                                    <i class="fa-solid fa-eye"></i>
                                    Ver
                                </button>
                                <button type="button" class="producto-upload-button is-remove" onclick="limpiarEtiquetaFilaProducto(${indicePresentacion}, this)">
                                    <i class="fa-solid fa-trash-can"></i>
                                    Quitar
                                </button>
                                <span class="presentacion-etiqueta-input hidden"></span>
                            </div>
                        </div>
                        <div>
                            <span>Estado presentacion</span>
                            <strong class="producto-table-status">${estadoPresentacion.value}</strong>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="producto-table-detail">
                        <div class="is-wide">
                            <span>Codigo autorizacion</span>
                            <strong>${escaparHtmlProducto(codigo.value)}</strong>
                        </div>
                        <div>
                            <span>Vigencia</span>
                            <strong>${escaparHtmlProducto(fechaVigencia.value || 'Sin fecha')}</strong>
                        </div>
                        <div>
                            <span>Cantidad</span>
                            <strong>${escaparHtmlProducto(cantidadRegistro.value || '-')}</strong>
                        </div>
                        <div>
                            <span>Unidad</span>
                            <strong>${escaparHtmlProducto(unidadRegistro.value || '-')}</strong>
                        </div>
                        <div class="is-wide">
                            <span>Relacion</span>
                            <strong>Usa la presentacion de esta fila</strong>
                        </div>
                        <div>
                            <span>Estado registro</span>
                            <strong class="producto-table-status">${estadoRegistro.value}</strong>
                        </div>
                    </div>
                    <input type="hidden" name="registros[${indiceRegistro}][codigo_autorizacion]" value="${escaparHtmlProducto(codigo.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][fecha_vigencia]" value="${escaparHtmlProducto(fechaVigencia.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][cantidad]" value="${escaparHtmlProducto(cantidadRegistro.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][unidad]" value="${escaparHtmlProducto(unidadRegistro.value)}">
                    <input type="hidden" name="registros[${indiceRegistro}][id_presentacion_temporal]" value="${escaparHtmlProducto(indicePresentacion)}">
                    <input type="hidden" name="registros[${indiceRegistro}][presentacion_texto]" value="${escaparHtmlProducto(presentacionTexto)}">
                    <input type="hidden" name="registros[${indiceRegistro}][estado]" value="${estadoRegistro.value}">
                </td>
                <td>
                    <div class="producto-row-actions">
                        <button type="button" class="producto-action-icon is-edit" title="Editar presentacion y registro"
                            onclick="editarFilaRegistroPresentacionProducto(this)">
                            <span>Editar</span>
                        </button>
                        <button type="button" class="producto-action-icon is-delete" title="Eliminar presentacion y registro"
                            onclick="quitarFilaProducto(this)">
                            <span>Quitar</span>
                        </button>
                    </div>
                </td>
            `;

            document.getElementById('tablaRegistrosPresentacionesProducto').appendChild(fila);

            if (archivoEtiqueta && !reutilizaPresentacion) {
                // Mueve el input file real al bloque oculto para enviarlo una sola vez aunque existan varios registros.
                etiqueta.name = `presentaciones[${indicePresentacion}][url_etiqueta]`;
                etiqueta.id = `form_presentacion_etiqueta_${indicePresentacion}`;
                etiqueta.classList.add('hidden');
                etiqueta.removeAttribute('onchange');
                document.querySelector(`#presentacion_oculta_${indicePresentacion} .presentacion-etiqueta-input`)?.appendChild(etiqueta);

                // Crea otro input file limpio para registrar una siguiente presentacion.
                const nuevoInputEtiqueta = document.createElement('input');
                nuevoInputEtiqueta.className = 'producto-upload-input';
                nuevoInputEtiqueta.id = 'form_presentacion_etiqueta';
                nuevoInputEtiqueta.type = 'file';
                nuevoInputEtiqueta.accept = 'application/pdf,.pdf';
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
                    unidad: unidadPresentacion.value,
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
            cantidadPresentacion.value = '';
            unidadPresentacion.value = '';
            descripcion.value = '';
            if (origenPresentacion) {
                origenPresentacion.value = '';
            }
            if (catalogoPresentacion) {
                catalogoPresentacion.value = '';
            }
            bloquearCamposPresentacionProducto(false);
            codigo.value = '';
            fechaVigencia.value = '';
            cantidadRegistro.value = '';
            unidadRegistro.value = '';
            limpiarErrorProducto(cantidadPresentacion);
            limpiarErrorProducto(unidadPresentacion);
            limpiarErrorProducto(etiqueta);
            limpiarErrorProducto(codigo);
            reiniciarControlEtiquetaActual();
            actualizarResumenProducto();
        }

        // Quita solo el PDF de la fila agregada. Si la fila queda sin PDF, Laravel lo validara al guardar.
        function limpiarEtiquetaFilaProducto(indicePresentacion, boton) {
            const filasMismaPresentacion = document.querySelectorAll(
                `#tablaRegistrosPresentacionesProducto tr[data-presentacion-indice="${indicePresentacion}"]`
            );
            const archivo = document.querySelector(
                `#presentacion_oculta_${indicePresentacion} .presentacion-etiqueta-input input[type="file"]`
            );
            const origen = document.querySelector(
                `#presentacion_oculta_${indicePresentacion} input[name="presentaciones[${indicePresentacion}][id_presentacion_origen]"]`
            );

            if (productoEtiquetaUrls[indicePresentacion]) {
                URL.revokeObjectURL(productoEtiquetaUrls[indicePresentacion].url);
                delete productoEtiquetaUrls[indicePresentacion];
            }

            if (archivo) {
                archivo.value = '';
            }

            if (origen) {
                origen.value = '';
            }

            /*
             * Una presentacion puede tener varios registros. Por eso la vista de PDF
             * se actualiza en todas las filas que apuntan a la misma presentacion.
             */
            filasMismaPresentacion.forEach((fila) => {
                const nombre = fila.querySelector('.producto-table-file-name');
                const botonVer = fila.querySelector('.producto-upload-button.is-view');
                const botonQuitar = fila.querySelector('.producto-upload-button.is-remove');

                if (nombre) {
                    nombre.textContent = 'Sin PDF seleccionado';
                }

                if (botonVer) {
                    botonVer.disabled = true;
                    botonVer.removeAttribute('onclick');
                }

                if (botonQuitar) {
                    botonQuitar.disabled = true;
                }
            });
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

            document.getElementById('form_presentacion_cantidad').value = valorCampo(`input[name="presentaciones[${indicePresentacion}][cantidad]"]`);
            document.getElementById('form_presentacion_unidad').value = valorCampo(`input[name="presentaciones[${indicePresentacion}][unidad]"]`);
            document.getElementById('form_presentacion_descripcion').value = valorCampo(`input[name="presentaciones[${indicePresentacion}][descripcion]"]`);
            document.getElementById('form_presentacion_estado').value = valorCampo(`input[name="presentaciones[${indicePresentacion}][estado]"]`) || 'ACTIVO';
            document.getElementById('form_presentacion_origen_id').value = valorCampo(`input[name="presentaciones[${indicePresentacion}][id_presentacion_origen]"]`);

            document.getElementById('form_registro_codigo_autorizacion').value = valorCampo('input[name$="[codigo_autorizacion]"]');
            document.getElementById('form_registro_fecha_vigencia').value = valorCampo('input[name$="[fecha_vigencia]"]');
            document.getElementById('form_registro_cantidad').value = valorCampo('input[name$="[cantidad]"][name^="registros"]');
            document.getElementById('form_registro_unidad').value = valorCampo('input[name$="[unidad]"][name^="registros"]');
            document.getElementById('form_registro_estado').value = valorCampo('input[name$="[estado]"][name^="registros"]') || 'ACTIVO';

            quitarFilaProducto(boton);
            reiniciarControlEtiquetaActual();

            notificarProducto(
                'info',
                'Fila cargada',
                'Revise los datos y seleccione nuevamente la etiqueta PDF antes de agregar la fila corregida.'
            );
        }

        // Elimina una fila dinamica y recalcula los contadores visibles.
        function quitarFilaProducto(boton) {
            const fila = boton.closest('tr');
            const indicePresentacion = fila?.dataset.presentacionIndice;

            fila?.remove();

            const presentacionSigueEnUso = indicePresentacion !== undefined &&
                document.querySelector(`#tablaRegistrosPresentacionesProducto tr[data-presentacion-indice="${indicePresentacion}"]`);

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
            actualizarResumenProducto();
        }

        // Restaura el aviso de tabla vacia cuando el usuario quita todos los registros agregados.
        function asegurarFilaVaciaRegistrosPresentacionesProducto() {
            const cuerpoTabla = document.getElementById('tablaRegistrosPresentacionesProducto');

            if (!cuerpoTabla || cuerpoTabla.querySelector('tr:not(#sinRegistrosPresentacionesProducto)')) {
                return;
            }

            cuerpoTabla.innerHTML = `
                <tr id="sinRegistrosPresentacionesProducto">
                    <td colspan="5">
                        <div class="producto-empty">
                            Todavia no agregaste presentaciones y registros.
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
            const texto = input.value.trim();

            if (!texto) {
                marcarErrorProducto(input, 'Ingrese un nombre para agregarlo.');
                return;
            }

            // Cada select mantiene una sola opcion temporal activa para no llenar el catalogo visual con duplicados.
            select.querySelectorAll('option[data-temporal="1"]').forEach((opcionTemporal) => {
                opcionTemporal.remove();
            });

            const opcion = document.createElement('option');
            opcion.value = `TEMP-${Date.now()}`;
            opcion.textContent = texto;
            opcion.dataset.temporal = '1';
            opcion.selected = true;

            // Fabricante temporal: se guardan datos extras en hidden para crear el catalogo al guardar.
            if (idSelect === 'form_id_fabricante') {
                document.getElementById('form_fabricante_temporal_nombre').value = texto;
                document.getElementById('form_fabricante_temporal_razon_social').value =
                    document.getElementById('modal_fabricante_razon_social')?.value.trim() || '';
                document.getElementById('form_fabricante_temporal_descripcion').value =
                    document.getElementById('modal_fabricante_descripcion')?.value.trim() || '';
            }

            // Tipo de producto temporal: se guarda descripcion y codigo para crear el catalogo al guardar.
            if (idSelect === 'form_id_tipo_producto') {
                document.getElementById('form_tipo_producto_temporal_descripcion').value = texto;
                document.getElementById('form_tipo_producto_temporal_codigo').value =
                    document.getElementById('modal_tipo_producto_codigo')?.value.trim() || '';
            }

            // Si se crea un ingrediente desde el modal, se guardan tambien sus datos visibles para la tabla.
            if (idSelect === 'form_ingrediente_select') {
                const composicion = document.getElementById('modal_ingrediente_composicion')?.value.trim() || '';
                const riesgoSalud = document.getElementById('modal_ingrediente_riesgo_salud')?.value.trim() || '';

                opcion.textContent = texto;
                opcion.dataset.nombre = texto;
                opcion.dataset.composicion = composicion || 'Sin composicion';
                opcion.dataset.riesgoSalud = riesgoSalud || 'Sin riesgo registrado';
            }

            select.appendChild(opcion);
            limpiarErrorProducto(select);
            limpiarErrorProducto(input);
            actualizarDetalleIngredienteProducto();

            cerrarModalProducto(idModal);

            // Limpieza extra despues de agregar: evita que el modal reabra con el ultimo texto escrito.
            limpiarModalProducto(document.getElementById(idModal));
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
                document.getElementById('form_id_fabricante')?.value &&
                document.getElementById('form_id_tipo_producto')?.value;

            ponerTextoProducto('resumenNombreProducto', nombre);
            ponerTextoProducto('resumenCodigoProducto', codigo);
            ponerTextoProducto('resumenIngredientes', totalIngredientes);
            ponerTextoProducto('resumenPresentacionesProducto', totalPresentaciones);
            ponerTextoProducto('resumenRegistrosProducto', totalRegistros);
            ponerTextoProducto('revisionProductoImportador', textoSelectProducto('form_id_importador_persona'));
            ponerTextoProducto('revisionProductoPais', textoSelectProducto('form_id_territorio_pais'));
            ponerTextoProducto('revisionProductoFabricante', textoSelectProducto('form_id_fabricante'));
            ponerTextoProducto('revisionProductoTipo', textoSelectProducto('form_id_tipo_producto'));

            if (document.getElementById('ingredienteProductoNombre')) {
                document.getElementById('ingredienteProductoNombre').textContent = nombre;
            }

            if (document.getElementById('ingredienteProductoCodigo')) {
                document.getElementById('ingredienteProductoCodigo').textContent = codigo;
            }

            // Si el usuario corrige nombre o codigo del producto, las filas ya agregadas se actualizan.
            document.querySelectorAll('.ingrediente-producto-nombre').forEach((elemento) => {
                elemento.textContent = nombre;
            });

            document.querySelectorAll('.ingrediente-producto-codigo').forEach((elemento) => {
                elemento.textContent = codigo;
            });

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

            contenedor?.classList.remove('is-open');
            disparador?.setAttribute('aria-expanded', 'false');
        }

        // Dibuja las opciones con nombre y composicion en dos lineas, algo que el select nativo no permite.
        function renderizarOpcionesIngredienteProducto() {
            const select = document.getElementById('form_ingrediente_select');
            const lista = document.getElementById('form_ingrediente_options');

            if (!select || !lista) {
                return;
            }

            lista.innerHTML = Array.from(select.options).map((opcion) => {
                const nombre = opcion.dataset.nombre || opcion.text.trim();
                const composicion = opcion.dataset.composicion?.trim();
                const seleccionado = select.value === opcion.value && opcion.value !== '';

                return `
                    <button type="button"
                        class="producto-ingredient-option ${seleccionado ? 'is-selected' : ''}"
                        data-ingrediente-option="${escaparHtmlProducto(opcion.value)}"
                        role="option"
                        aria-selected="${seleccionado ? 'true' : 'false'}">
                        <strong>${escaparHtmlProducto(nombre)}</strong>
                        ${composicion ? `<small>Composicion: ${escaparHtmlProducto(composicion)}</small>` : ''}
                    </button>
                `;
            }).join('');

            lista.querySelectorAll('[data-ingrediente-option]').forEach((boton) => {
                boton.addEventListener('click', () => {
                    select.value = boton.dataset.ingredienteOption || '';
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    cerrarSelectorIngredienteProducto();
                });
            });
        }

        // Sincroniza el selector visual con el select real que se envia al controlador.
        function actualizarDetalleIngredienteProducto() {
            const select = document.getElementById('form_ingrediente_select');
            const etiqueta = document.querySelector('[data-ingrediente-label]');
            const detalle = document.querySelector('[data-ingrediente-detail]');
            const opcion = select?.options[select.selectedIndex];
            const nombre = opcion?.dataset.nombre || opcion?.text?.trim() || 'Seleccione un ingrediente';
            const composicion = opcion?.dataset.composicion?.trim();

            if (!etiqueta || !detalle) {
                return;
            }

            etiqueta.textContent = select?.value ? nombre : 'Seleccione un ingrediente';
            detalle.textContent = select?.value && composicion ? `Composicion: ${composicion}` : 'Composicion como detalle';
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

            if (idSelect === 'form_id_tipo_producto') {
                document.getElementById('form_tipo_producto_temporal_descripcion').value = '';
                document.getElementById('form_tipo_producto_temporal_codigo').value = '';
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
            const cuerpo = filasSeguras.length
                ? filasSeguras.map((fila) => `
                    <tr>
                        ${(Array.isArray(fila) ? fila : []).map((valor) => `<td>${escaparHtmlProducto(textoResumenProducto(valor)).replaceAll('\n', '<br>')}</td>`).join('')}
                    </tr>
                `).join('')
                : `
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

        // Agrupa inputs del tipo presentaciones[0][cantidad] o registros[0][unidad] por indice.
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
            const base = [presentacion.cantidad, presentacion.unidad].filter(Boolean).join(' ');
            const descripcion = presentacion.descripcion ? ` - ${presentacion.descripcion}` : '';

            return base || descripcion ? `${base}${descripcion}` : `Presentacion #${indice}`;
        }

        // Actualiza el paso Revision para confirmar antes de guardar producto.
        function actualizarRevisionFinalProducto() {
            const destino = document.getElementById('resumenProductoWizard');

            if (!destino) {
                return;
            }

            const fabricanteNuevo = document.getElementById('form_id_fabricante')?.selectedOptions?.[0]?.dataset.temporal === '1';
            const tipoNuevo = document.getElementById('form_id_tipo_producto')?.selectedOptions?.[0]?.dataset.temporal === '1';
            const ingredientes = gruposPorIndiceResumenProducto('ingredientes_productos');
            const presentaciones = gruposPorIndiceResumenProducto('presentaciones');
            const registros = gruposPorIndiceResumenProducto('registros');

            const datosProducto = [
                itemResumenProducto('Codigo', valorResumenProducto('#form_codigo')),
                itemResumenProducto('Nombre comercial', valorResumenProducto('#form_nombre_comercial')),
                itemResumenProducto('Nombre cientifico', valorResumenProducto('#form_nombre_cientifico')),
                itemResumenProducto('Clasificacion', valorResumenProducto('#form_clasificacion')),
                itemResumenProducto('Importador', textoSelectProducto('form_id_importador_persona')),
                itemResumenProducto('Pais / territorio', textoSelectProducto('form_id_territorio_pais')),
                itemResumenProducto('Fabricante', textoSelectProducto('form_id_fabricante')),
                itemResumenProducto('Tipo de producto', textoSelectProducto('form_id_tipo_producto')),
                itemResumenProducto('Estado del producto', valorResumenProducto('#form_estado')),
            ];

            if (fabricanteNuevo) {
                datosProducto.push(itemResumenProducto('Fabricante nuevo - nombre', valorResumenProducto('#form_fabricante_temporal_nombre')));
                datosProducto.push(itemResumenProducto('Fabricante nuevo - razon social', valorResumenProducto('#form_fabricante_temporal_razon_social')));
                datosProducto.push(itemResumenProducto('Fabricante nuevo - descripcion', valorResumenProducto('#form_fabricante_temporal_descripcion'), true));
            }

            if (tipoNuevo) {
                datosProducto.push(itemResumenProducto('Tipo nuevo - descripcion', valorResumenProducto('#form_tipo_producto_temporal_descripcion')));
                datosProducto.push(itemResumenProducto('Tipo nuevo - codigo', valorResumenProducto('#form_tipo_producto_temporal_codigo')));
            }

            const filasIngredientes = Object.entries(ingredientes).map(([indice, ingrediente]) => [
                Number(indice) + 1,
                ingrediente.nombre || textoResumenProducto(ingrediente.id_ingrediente),
                ingrediente.composicion,
                ingrediente.riesgo_salud,
                ingrediente.porcentaje ? `${ingrediente.porcentaje}%` : '',
                ingrediente.estado || 'ACTIVO',
            ]);

            const filasPresentaciones = Object.entries(presentaciones).map(([indice, presentacion]) => [
                Number(indice) + 1,
                presentacion.cantidad,
                presentacion.unidad,
                presentacion.descripcion,
                etiquetaResumenPresentacionProducto(indice, presentacion),
                presentacion.id_presentacion_origen ? `Reutiliza presentacion #${presentacion.id_presentacion_origen}` : 'Nueva presentacion',
                presentacion.estado || 'ACTIVO',
            ]);

            const filasRegistros = Object.entries(registros).map(([indice, registro]) => [
                Number(indice) + 1,
                registro.codigo_autorizacion,
                registro.fecha_vigencia,
                registro.cantidad,
                registro.unidad,
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
                    'Composicion, riesgo de salud, porcentaje y estado de cada ingrediente agregado.',
                    tablaResumenProducto(
                        ['#', 'Ingrediente', 'Composicion', 'Riesgo salud', 'Porcentaje', 'Estado'],
                        filasIngredientes,
                        'No se agregaron ingredientes.'
                    )
                ),
                grupoResumenProducto(
                    'Presentaciones del producto',
                    'Formatos, cantidades, unidades, etiqueta PDF y estado de cada presentacion.',
                    tablaResumenProducto(
                        ['#', 'Cantidad', 'Unidad', 'Descripcion', 'Etiqueta PDF', 'Origen', 'Estado'],
                        filasPresentaciones,
                        'No se agregaron presentaciones.'
                    )
                ),
                grupoResumenProducto(
                    'Registros del producto',
                    'Codigos, vigencias, cantidades y unidades declaradas para cada presentacion.',
                    tablaResumenProducto(
                        ['#', 'Codigo autorizacion', 'Fecha vigencia', 'Cantidad', 'Unidad', 'Presentacion seleccionada', 'Estado'],
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
                    text: tieneArchivosTemporales
                        ? 'Los datos se guardaron. Los PDF se mantienen en esta pagina hasta guardar el producto; no recargue la vista.'
                        : 'El avance se guardo temporalmente en este navegador.',
                    timer: 1400,
                    showConfirmButton: false,
                });
                return;
            }

            notificarProducto(
                'success',
                'Avance guardado',
                tieneArchivosTemporales
                    ? 'Los PDF se mantienen en esta pagina hasta guardar el producto; no recargue la vista.'
                    : 'El avance se guardo temporalmente en este navegador.'
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

            ['form_nombre_comercial', 'form_codigo', 'form_id_importador_persona', 'form_id_fabricante', 'form_id_tipo_producto'].forEach((
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

                document.querySelector('[data-ingrediente-trigger]')?.setAttribute(
                    'aria-expanded',
                    estaAbierto ? 'true' : 'false'
                );
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('[data-ingrediente-combobox]')) {
                    cerrarSelectorIngredienteProducto();
                }
            });

            document.getElementById('form_ingrediente_select')?.addEventListener('change', actualizarDetalleIngredienteProducto);
            actualizarDetalleIngredienteProducto();

            document.getElementById('form_presentacion_catalogo')?.addEventListener('change', autocompletarPresentacionProducto);
            filtrarPresentacionesCatalogoProducto();

            // Todos los controles del wizard limpian su error apenas el usuario corrige el dato.
            document.querySelectorAll('.producto-input, .producto-select, .producto-textarea, .producto-upload-input')
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
@if (! $productoEmbebido)
</x-admin-layout>
@endif
