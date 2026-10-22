<x-admin-layout title="Editar plantilla | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Certificados', 'href' => route('certificados_index')],
    ['name' => 'Plantillas', 'href' => route('certificados_plantillas_index')],
    ['name' => 'Editar'],
]">
    @php
        $urlFondo = $plantilla?->url_fondo ? route('certificados_plantillas_archivo', $plantilla) : null;
        $nombreFondo = $plantilla?->url_fondo ? basename($plantilla->url_fondo) : 'Sin archivo seleccionado';
        $elementosGuardados = ($plantilla?->elementos ?? collect())
            ->map(function ($elemento) {
                return [
                    'tipo_elemento' => $elemento->tipo_elemento,
                    'codigo_elemento' => $elemento->codigo_elemento,
                    'texto_fijo' => $elemento->texto_fijo,
                    'pagina' => $elemento->pagina,
                    'posicion_x' => $elemento->posicion_x,
                    'posicion_y' => $elemento->posicion_y,
                    'ancho' => $elemento->ancho,
                    'alto' => $elemento->alto,
                    'tamano_letra' => $elemento->tamano_letra,
                    'alineacion' => $elemento->alineacion,
                    'padding_x' => $elemento->padding_x ?? 7,
                    'padding_y' => $elemento->padding_y ?? 5,
                    'interlineado' => $elemento->interlineado ?? 1.25,
                    'negrita' => (bool) $elemento->negrita,
                    'cursiva' => (bool) $elemento->cursiva,
                    'subrayado' => (bool) $elemento->subrayado,
                    'color_texto' => $elemento->color_texto ?: '#0f172a',
                    'tipo_letra' => $elemento->tipo_letra ?: 'Arial',
                    'z_index' => $elemento->z_index ?? 3,
                    'configuracion' => $elemento->configuracion ?? [],
                    'estado' => $elemento->estado,
                    'columnas' => $elemento->columnas
                        ->map(fn ($columna) => [
                            'codigo_campo' => $columna->codigo_campo,
                            'titulo_columna' => $columna->titulo_columna,
                            'ancho' => $columna->ancho,
                            'estado' => $columna->estado,
                        ])
                        ->values(),
                ];
            })
            ->values();
        $elementosIniciales = old('form_elementos_plantilla')
            ? (json_decode(old('form_elementos_plantilla'), true) ?: [])
            : $elementosGuardados;
        $disenoFabricInicial = old('form_diseno_fabric_json')
            ? json_decode(old('form_diseno_fabric_json'), true)
            : ($plantilla?->diseno_fabric_json ?? null);
    @endphp

    @include('certificados.plantilla_certificado.estilo')

    <script>
        window.tiposCertificadosPlantilla = @json($tiposCertificadosPlantillaJson);
        window.elementosPlantillaIniciales = @json($elementosIniciales);
        window.disenoFabricPlantillaInicial = @json($disenoFabricInicial);
    </script>

    <form class="plantilla-shell"
        action="{{ $plantilla ? route('certificados_plantillas_update', $plantilla) : route('certificados_plantillas_store') }}"
        method="POST"
        enctype="multipart/form-data">
        @csrf
        @if ($plantilla)
            @method('PUT')
        @endif

        <input type="hidden" name="form_elementos_plantilla" data-plantilla-elementos-input value="{{ old('form_elementos_plantilla', $elementosGuardados->toJson()) }}">
        <textarea name="form_diseno_fabric_json" data-plantilla-diseno-fabric-input hidden>{{ old('form_diseno_fabric_json', $plantilla?->diseno_fabric_json ? json_encode($plantilla->diseno_fabric_json) : '') }}</textarea>
        <input type="hidden" name="form_quitar_fondo_plantilla" value="0" data-plantilla-fondo-quitar-input>

        <section class="plantilla-card">
            <div class="plantilla-head">
                <div>
                    <h1 class="plantilla-title">Editar plantilla de certificado</h1>
                    <p class="plantilla-subtitle">Ajuste el diseño que se usará para emitir {{ $tipoCertificado->nombre }}.</p>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <x-wire-button href="{{ route('certificados_plantillas_show', $tipoCertificado) }}" secondary>
                        Volver
                    </x-wire-button>
                    <x-wire-button type="submit" emerald>
                        Guardar cambios
                    </x-wire-button>
                </div>
            </div>

            <div class="plantilla-step-title">
                <span>1</span>
                Datos principales
            </div>

            <div class="grid gap-4 p-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-wire-input label="Nombre de la plantilla" name="form_nombre"
                        value="{{ old('form_nombre', $plantilla?->nombre ?? 'Plantilla oficial') }}" />
                </div>

                <div class="lg:col-span-5">
                    <x-wire-native-select label="Tipo de certificado" name="form_id_tipo_certificado" data-plantilla-tipo>
                        @foreach ($tiposCertificados as $tipo)
                            <option value="{{ $tipo->id }}" @selected(old('form_id_tipo_certificado', $tipoCertificado->id) == $tipo->id)>
                                {{ $tipo->nombre }}
                            </option>
                        @endforeach
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-3">
                    <x-wire-native-select label="Estado" name="form_estado">
                        <option value="ACTIVO" @selected(old('form_estado', $plantilla?->estado ?? 'ACTIVO') === 'ACTIVO')>Activo</option>
                        <option value="INACTIVO" @selected(old('form_estado', $plantilla?->estado) === 'INACTIVO')>Inactivo</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-5">
                    <label class="plantilla-label">Archivo de plantilla</label>
                    <input type="file" name="form_url_fondo"
                        accept=".doc,.docx,.pdf,.png,.jpg,.jpeg,.webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf,image/png,image/jpeg,image/webp"
                        class="hidden"
                        data-plantilla-fondo-input data-plantilla-fondo-url="{{ $urlFondo }}">
                    <div class="plantilla-file-control">
                        <span class="plantilla-file-icon">
                            <i class="fa-solid fa-file-arrow-up"></i>
                        </span>
                        <button type="button" class="plantilla-file-btn is-select" data-plantilla-fondo-seleccionar>Seleccionar</button>
                        <button type="button" class="plantilla-file-btn" data-plantilla-fondo-ver @disabled(!$urlFondo)>Ver</button>
                        <button type="button" class="plantilla-file-btn is-danger" data-plantilla-fondo-quitar @disabled(!$urlFondo)>Quitar</button>
                        <span class="plantilla-file-name" data-plantilla-fondo-nombre>{{ $nombreFondo }}</span>
                    </div>
                    @error('form_url_fondo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-2">
                    <x-wire-native-select label="Tamaño" id="form_tamano_papel" name="form_tamano_papel" data-plantilla-tamano>
                        <option value="CARTA" @selected(old('form_tamano_papel', $plantilla?->tamano_papel ?? 'CARTA') === 'CARTA')>Carta</option>
                        <option value="OFICIO" @selected(old('form_tamano_papel', $plantilla?->tamano_papel) === 'OFICIO')>Oficio</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-2">
                    <x-wire-native-select label="Orientación" id="form_orientacion" name="form_orientacion" data-plantilla-orientacion>
                        <option value="VERTICAL" @selected(old('form_orientacion', $plantilla?->orientacion ?? 'VERTICAL') === 'VERTICAL')>Vertical</option>
                        <option value="HORIZONTAL" @selected(old('form_orientacion', $plantilla?->orientacion) === 'HORIZONTAL')>Horizontal</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-2" title="Ajustar a la hoja: la plantilla ocupa todo el espacio. Mostrar completa: se ve toda la plantilla, aunque puedan quedar márgenes. Cubrir toda la hoja: llena todo, pero puede recortar partes.">
                    <x-wire-native-select label="Cómo adaptar la plantilla" id="form_ajuste_fondo" name="form_ajuste_fondo" data-plantilla-ajuste-fondo aria-label="Cómo adaptar la plantilla">
                        <option value="ESTIRAR" @selected(old('form_ajuste_fondo', $plantilla?->ajuste_fondo ?? 'ESTIRAR') === 'ESTIRAR')>Ajustar a la hoja</option>
                        <option value="CONTENER" @selected(old('form_ajuste_fondo', $plantilla?->ajuste_fondo) === 'CONTENER')>Mostrar completa</option>
                        <option value="CUBRIR" @selected(old('form_ajuste_fondo', $plantilla?->ajuste_fondo) === 'CUBRIR')>Cubrir toda la hoja</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-2">
                    <x-wire-native-select label="Fondo de trabajo" id="form_fondo_trabajo" name="form_fondo_trabajo" data-plantilla-fondo-trabajo>
                        <option value="PLANTILLA" @selected(old('form_fondo_trabajo', $plantilla?->fondo_trabajo ?? 'PLANTILLA') === 'PLANTILLA')>Plantilla</option>
                        <option value="BLANCO" @selected(old('form_fondo_trabajo', $plantilla?->fondo_trabajo) === 'BLANCO')>Blanco</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-2">
                    <input type="hidden" name="form_imprimir_fondo" value="0">
                    <label class="plantilla-option-check">
                        <input type="checkbox" name="form_imprimir_fondo" value="1"
                            @checked((bool) old('form_imprimir_fondo', !($plantilla?->imprimir_transparente ?? false)))
                            data-plantilla-imprimir-fondo>
                        <span>
                            <strong>Imprimir fondo</strong>
                            <small>Incluye la plantilla de fondo al emitir.</small>
                        </span>
                    </label>
                </div>

                <div class="hidden" data-plantilla-resumen-tipo></div>
            </div>
        </section>

        <section class="plantilla-designer">
            {{-- Las herramientas comparten una sola barra lateral para dejar más espacio al certificado. --}}
            <aside class="plantilla-panel plantilla-workspace-sidebar">
                <div class="plantilla-sidebar-tabs" role="tablist" aria-label="Herramientas del diseñador">
                    <button type="button" class="plantilla-sidebar-tab is-active" role="tab" aria-selected="true"
                        data-plantilla-sidebar-tab="campos">Campos</button>
                    <button type="button" class="plantilla-sidebar-tab" role="tab" aria-selected="false"
                        data-plantilla-sidebar-tab="propiedades">Propiedades</button>
                    <button type="button" class="plantilla-sidebar-tab" role="tab" aria-selected="false"
                        data-plantilla-sidebar-tab="capas">
                        Capas <span data-plantilla-contador-capas>0</span>
                    </button>
                </div>

                <div class="plantilla-sidebar-content">
                    <section class="plantilla-sidebar-pane is-active" data-plantilla-sidebar-pane="campos">
                        <input type="search" class="plantilla-search" placeholder="Buscar campo..." data-plantilla-buscar-campo>

                        @foreach ($camposPlantilla as $grupo => $campos)
                            <details class="plantilla-field-group" @if ($loop->first) open @endif>
                                <summary class="plantilla-field-title">
                                    <span>{{ $grupo }}</span>
                                    <span>{{ count($campos) }}</span>
                                </summary>

                                <div class="plantilla-field-list">
                                    @foreach ($campos as $campo)
                                        <button type="button" class="plantilla-field"
                                            data-plantilla-campo
                                            data-codigo="{{ $campo['codigo'] }}"
                                            data-nombre="{{ $campo['nombre'] }}"
                                            data-grupo="{{ $grupo }}"
                                            title="Dato interno: {{ $campo['codigo'] }}">
                                            <span class="plantilla-field-name">{{ $campo['nombre'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                        <div class="plantilla-preview-box">
                            <div class="font-black text-emerald-700">Marcadores dinámicos</div>
                            <p class="mt-2">
                                Puede usar valores como
                                <span class="plantilla-token">@{{beneficiario_nombre}}</span>
                                y el sistema los reemplazará al emitir.
                            </p>
                        </div>
                    </section>

                    <section class="plantilla-sidebar-pane" data-plantilla-sidebar-pane="propiedades" hidden>
                        <div data-plantilla-propiedades></div>
                    </section>

                    <section class="plantilla-sidebar-pane" data-plantilla-sidebar-pane="capas" hidden>
                        <div data-plantilla-capas></div>
                    </section>
                </div>
            </aside>

            <main class="plantilla-panel plantilla-canvas-panel">
                <div class="plantilla-panel-title">Diseñador del certificado</div>

                <div class="plantilla-toolbar">
                    {{-- Las acciones directas usan iconos; el nombre completo permanece en el título accesible. --}}
                    <div class="plantilla-toolbar-group" aria-label="Agregar elementos">
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn is-primary" data-plantilla-tool="texto"
                            title="Agregar texto" aria-label="Agregar texto"><i class="fa-solid fa-font"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="tabla"
                            title="Agregar tabla de datos: campo y valor" aria-label="Agregar tabla de datos"><i class="fa-solid fa-table-cells"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="firma"
                            title="Agregar firma" aria-label="Agregar firma"><i class="fa-solid fa-signature"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="qr"
                            title="Agregar código QR" aria-label="Agregar código QR"><i class="fa-solid fa-qrcode"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="imagen"
                            title="Agregar imagen" aria-label="Agregar imagen"><i class="fa-solid fa-image"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="forma"
                            title="Agregar forma" aria-label="Agregar forma"><i class="fa-solid fa-shapes"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="linea"
                            title="Agregar línea" aria-label="Agregar línea"><i class="fa-solid fa-minus"></i></button>
                    </div>

                    <span class="plantilla-toolbar-separator" aria-hidden="true"></span>

                    <div class="plantilla-toolbar-group" aria-label="Edición">
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="deshacer"
                            title="Deshacer" aria-label="Deshacer"><i class="fa-solid fa-rotate-left"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="rehacer"
                            title="Rehacer" aria-label="Rehacer"><i class="fa-solid fa-rotate-right"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="duplicar"
                            title="Duplicar selección" aria-label="Duplicar selección"><i class="fa-solid fa-copy"></i></button>
                        <button type="button" class="plantilla-action-btn plantilla-icon-btn is-danger" data-plantilla-tool="eliminar"
                            title="Quitar selección" aria-label="Quitar selección"><i class="fa-solid fa-trash-can"></i></button>
                    </div>

                    <span class="plantilla-toolbar-separator" aria-hidden="true"></span>

                    <select class="plantilla-toolbar-select" data-plantilla-alinear aria-label="Alinear campos seleccionados">
                        <option value="">Alinear...</option>
                        <option value="IZQUIERDA">A la izquierda</option>
                        <option value="CENTRO_H">Centrar horizontal</option>
                        <option value="DERECHA">A la derecha</option>
                        <option value="ARRIBA">Arriba</option>
                        <option value="CENTRO_V">Centrar vertical</option>
                        <option value="ABAJO">Abajo</option>
                        <option value="MISMO_ANCHO">Igualar ancho</option>
                        <option value="MISMO_ALTO">Igualar alto</option>
                        <option value="MISMO_TAMANO">Igualar ancho y alto</option>
                    </select>
                    <select class="plantilla-toolbar-select" data-plantilla-seleccion aria-label="Acciones de selección">
                        <option value="">Selección...</option>
                        <option value="TODO">Seleccionar todo</option>
                        <option value="AGRUPAR">Agrupar</option>
                        <option value="DESAGRUPAR">Desagrupar</option>
                    </select>
                    <select class="plantilla-toolbar-select" data-plantilla-distribuir aria-label="Distribuir capas seleccionadas">
                        <option value="">Distribuir...</option>
                        <option value="HORIZONTAL">Horizontalmente</option>
                        <option value="VERTICAL">Verticalmente</option>
                    </select>

                    <button type="button" class="plantilla-action-btn plantilla-grid-btn" data-plantilla-tool="grid"
                        title="Mostrar u ocultar cuadrícula" aria-label="Mostrar u ocultar cuadrícula">
                        <i class="fa-solid fa-border-all"></i> Cuadrícula
                    </button>
                    <select class="plantilla-toolbar-select is-grid-control" data-plantilla-grid-type
                        aria-label="Tipo de cuadrícula" title="Tipo de cuadrícula">
                        <option value="LINEAS">Líneas</option>
                        <option value="PUNTOS">Puntos</option>
                    </select>
                    <select class="plantilla-toolbar-select is-grid-control" data-plantilla-grid-size
                        aria-label="Tamaño de cuadrícula" title="Tamaño de cuadrícula">
                        <option value="5">5 px</option>
                        <option value="10" selected>10 px</option>
                        <option value="20">20 px</option>
                        <option value="25">25 px</option>
                        <option value="40">40 px</option>
                        <option value="50">50 px</option>
                        <option value="75">75 px</option>
                        <option value="100">100 px</option>
                        <option value="150">150 px</option>
                        <option value="200">200 px</option>
                    </select>
                    <button type="button" class="plantilla-action-btn plantilla-snap-btn" data-plantilla-tool="snap"
                        title="Alinear bordes y centros con otros campos y con la cuadrícula"
                        aria-label="Alinear bordes y centros con otros campos y con la cuadrícula">
                        <i class="fa-solid fa-magnet"></i> Imán
                    </button>

                    <div class="plantilla-zoom-control" aria-label="Control de zoom">
                        <button type="button" data-plantilla-zoom="menos" title="Alejar" aria-label="Alejar">−</button>
                        <button type="button" class="plantilla-zoom-fit" data-plantilla-zoom="ajustar"
                            title="Ajustar el certificado al espacio disponible">Ajustar</button>
                        <button type="button" class="plantilla-zoom-value" data-plantilla-zoom="restablecer"
                            data-plantilla-zoom-valor title="Restablecer zoom al 100%">100%</button>
                        <button type="button" data-plantilla-zoom="mas" title="Acercar" aria-label="Acercar">+</button>
                    </div>

                    <button type="button" class="plantilla-action-btn plantilla-icon-btn" data-plantilla-tool="maximizar"
                        title="Maximizar el área de diseño" aria-label="Maximizar el área de diseño">
                        <i class="fa-solid fa-expand"></i>
                    </button>

                    <select class="plantilla-toolbar-select plantilla-toolbar-export" data-plantilla-exportar aria-label="Exportar vista previa">
                        <option value="">Exportar...</option>
                        <option value="PNG">Vista previa PNG</option>
                        <option value="SVG">Vista previa SVG</option>
                    </select>
                </div>

                <div class="plantilla-canvas-wrap">
                    <div class="plantilla-canvas-stage" data-plantilla-stage>
                        <canvas class="plantilla-canvas" data-plantilla-lienzo width="816" height="1056"
                            aria-label="Lienzo para diseñar el certificado"></canvas>
                    </div>
                </div>
            </main>
        </section>

        <section class="plantilla-card">
            <div class="plantilla-actions">
                <x-wire-button href="{{ route('certificados_plantillas_show', $tipoCertificado) }}" secondary>
                    Salir sin guardar
                </x-wire-button>
                <x-wire-button type="submit" emerald>
                    Guardar cambios
                </x-wire-button>
            </div>
        </section>
    </form>

</x-admin-layout>
