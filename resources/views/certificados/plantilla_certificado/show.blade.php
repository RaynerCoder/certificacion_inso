<x-admin-layout title="Ver plantilla | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Certificados', 'href' => route('certificados_index')],
    ['name' => 'Plantillas', 'href' => route('certificados_plantillas_index')],
    ['name' => 'Ver'],
]">
    @include('certificados.plantilla_certificado.estilo')

    <div class="plantilla-shell">
        <section class="plantilla-panel">
            <div class="plantilla-toolbar justify-end">
                <x-wire-button href="{{ route('certificados_plantillas_index') }}" secondary xs>
                    Volver
                </x-wire-button>
                <x-wire-button href="{{ route('certificados_plantillas_edit', $tipoCertificado) }}" blue xs>
                    Editar
                </x-wire-button>
            </div>

            <div class="plantilla-canvas-wrap plantilla-show-only">
                @if ($plantilla)
                    @php
                        $clasesLienzo = collect([
                            'plantilla-canvas',
                            $plantilla->tamano_papel === 'OFICIO' ? 'is-oficio' : null,
                            $plantilla->orientacion === 'HORIZONTAL' ? 'is-horizontal' : null,
                            $plantilla->fondo_trabajo === 'BLANCO' ? 'is-work-white' : null,
                        ])->filter()->implode(' ');
                        $ajusteFondo = match (strtoupper((string) ($plantilla->ajuste_fondo ?? 'ESTIRAR'))) {
                            'CONTENER' => 'contain',
                            'CUBRIR' => 'cover',
                            default => 'fill',
                        };
                    @endphp

                    <div class="{{ $clasesLienzo }}"
                        style="width: {{ (int) ($plantilla->ancho_lienzo_px ?: 816) }}px; height: {{ (int) ($plantilla->alto_lienzo_px ?: 1056) }}px;">
                        @if ($plantilla->url_fondo)
                            <img class="plantilla-canvas-bg is-visible"
                                src="{{ route('certificados_plantillas_archivo', $plantilla) }}"
                                alt="Plantilla del certificado"
                                style="object-fit: {{ $ajusteFondo }};">
                        @endif

                        @foreach ($plantilla->elementos as $elemento)
                            @php
                                $textoElemento = $elemento->texto_fijo
                                    ?: '{' . '{' . str_replace('.', '_', (string) $elemento->codigo_elemento) . '}' . '}';
                                $paddingXElemento = 0;
                                $paddingYElemento = 0;
                                $configuracion = (array) ($elemento->configuracion ?? []);
                                $bordeEstilo = match (strtoupper((string) data_get($configuracion, 'borde_estilo', 'SOLIDO'))) {
                                    'PUNTEADO' => 'dotted',
                                    'DISCONTINUO' => 'dashed',
                                    'DOBLE' => 'double',
                                    'NINGUNO' => 'none',
                                    default => 'solid',
                                };
                                $tabla = (array) data_get($configuracion, 'tabla', []);
                                $tablaCampoValor = data_get($tabla, 'tipo') === 'CAMPO_VALOR';
                                $tablaMostrarEncabezado = (bool) data_get($tabla, 'mostrar_encabezado', true);
                                $tablaEtiquetasNegrita = (bool) data_get($tabla, 'etiquetas_negrita', true);
                                $claseBordesTabla = 'is-bordes-' . strtolower((string) data_get($tabla, 'bordes', 'TODOS'));
                                $contenidoTabla = json_decode((string) $elemento->texto_fijo, true);
                                $filasTabla = is_array($contenidoTabla['filas'] ?? null) ? $contenidoTabla['filas'] : [];
                                $clasesElemento = collect([
                                    'plantilla-element',
                                    $elemento->tipo_elemento === 'TEXTO' ? 'is-texto' : null,
                                    $elemento->tipo_elemento === 'IMAGEN' ? 'is-imagen' : null,
                                    $elemento->tipo_elemento === 'TABLA' ? 'is-tabla' : null,
                                    $elemento->tipo_elemento === 'QR' ? 'is-qr' : null,
                                    $elemento->tipo_elemento === 'FORMA' ? 'is-forma' : null,
                                    $elemento->tipo_elemento === 'LINEA' ? 'is-linea' : null,
                                    $elemento->alineacion === 'CENTRO' ? 'is-center' : null,
                                    $elemento->alineacion === 'DERECHA' ? 'is-right' : null,
                                    $elemento->alineacion === 'JUSTIFICADO' ? 'is-justify' : null,
                                ])->filter()->implode(' ');
                            @endphp

                            <div class="{{ $clasesElemento }}"
                                style="
                                    left: {{ (int) $elemento->posicion_x }}px;
                                    top: {{ (int) $elemento->posicion_y }}px;
                                    width: {{ (int) $elemento->ancho }}px;
                                    height: {{ (int) $elemento->alto }}px;
                                    font-size: {{ (int) $elemento->tamano_letra }}px;
                                    font-weight: {{ $elemento->negrita ? '700' : '400' }};
                                    font-style: {{ $elemento->cursiva ? 'italic' : 'normal' }};
                                    text-decoration: {{ $elemento->subrayado ? 'underline' : 'none' }};
                                    color: {{ $elemento->color_texto ?: '#0f172a' }};
                                    font-family: '{{ $elemento->tipo_letra ?: 'Arial' }}', Arial, sans-serif;
                                    line-height: {{ (float) ($elemento->interlineado ?? 1.25) }};
                                    padding: {{ $paddingYElemento }}px {{ $paddingXElemento }}px;
                                    z-index: {{ (int) ($elemento->z_index ?? 3) }};
                                    opacity: {{ (float) data_get($configuracion, 'opacidad', 1) }};
                                    transform: rotate({{ (float) data_get($configuracion, 'rotacion', 0) }}deg);
                                    transform-origin: center center;
                                    background: {{ in_array($elemento->tipo_elemento, ['TABLA', 'LINEA'], true) ? 'transparent' : data_get($configuracion, 'fondo_color', 'transparent') }};
                                    border: {{ in_array($elemento->tipo_elemento, ['TABLA', 'LINEA'], true) || $bordeEstilo === 'none' ? '0' : (float) data_get($configuracion, 'borde_ancho', 0) . 'px ' . $bordeEstilo . ' ' . data_get($configuracion, 'borde_color', '#0f766e') }};
                                    border-radius: {{ $elemento->tipo_elemento === 'FORMA' && data_get($configuracion, 'forma') === 'ELIPSE' ? '50%' : (float) data_get($configuracion, 'radio_borde', 0) . 'px' }};
                                ">
                                @if ($elemento->tipo_elemento === 'IMAGEN' && $textoElemento)
                                    <img src="{{ $textoElemento }}" alt="Imagen de la plantilla" style="object-fit: {{ data_get($configuracion, 'ajuste_imagen') === 'CUBRIR' ? 'cover' : (data_get($configuracion, 'ajuste_imagen') === 'ESTIRAR' ? 'fill' : 'contain') }};">
                                @elseif ($elemento->tipo_elemento === 'TABLA')
                                    <table class="plantilla-word-table {{ $claseBordesTabla }} {{ data_get($tabla, 'filas_alternas') ? 'is-filas-alternas' : '' }}" style="
                                        --tabla-borde-color: {{ data_get($tabla, 'borde_color', '#0f172a') }};
                                        --tabla-borde-ancho: {{ (float) data_get($tabla, 'borde_ancho', 1) }}px;
                                        --tabla-borde-estilo: {{ match (data_get($tabla, 'borde_estilo', 'SOLIDO')) { 'PUNTEADO' => 'dotted', 'DISCONTINUO' => 'dashed', 'DOBLE' => 'double', default => 'solid' } }};
                                        --tabla-encabezado-fondo: {{ data_get($tabla, 'encabezado_fondo', '#f1f5f9') }};
                                        --tabla-encabezado-texto: {{ data_get($tabla, 'encabezado_texto', '#0f172a') }};
                                        --tabla-celdas-fondo: {{ $tablaCampoValor ? 'transparent' : data_get($tabla, 'celdas_fondo', '#ffffff') }};
                                        --tabla-alterna-fondo: {{ data_get($tabla, 'filas_alternas_color', '#f8fafc') }};
                                        --tabla-padding-x: {{ (float) data_get($tabla, 'padding_x', 6) }}px;
                                        --tabla-padding-y: {{ (float) data_get($tabla, 'padding_y', 4) }}px;
                                    ">
                                        <colgroup>
                                            @foreach ($elemento->columnas as $columna)
                                                <col style="width: {{ (float) ($columna->ancho ?: 25) }}%;">
                                            @endforeach
                                        </colgroup>
                                        @if ($tablaMostrarEncabezado)
                                            <thead><tr>
                                                @foreach ($elemento->columnas as $columna)
                                                    <th>{{ $columna->titulo_columna }}</th>
                                                @endforeach
                                            </tr></thead>
                                        @endif
                                        <tbody>
                                            @forelse ($filasTabla as $fila)
                                                <tr>@foreach ($fila as $celda)<td style="font-weight: {{ $elemento->negrita || ($tablaCampoValor && $tablaEtiquetasNegrita && $loop->iteration % 2 === 1) ? 700 : 400 }};">{{ $celda }}</td>@endforeach</tr>
                                            @empty
                                                <tr><td>Sin datos</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                @elseif ($elemento->tipo_elemento === 'QR' && str_starts_with((string) $textoElemento, 'data:image'))
                                    <img src="{{ $textoElemento }}" alt="Código QR">
                                @elseif ($elemento->tipo_elemento === 'LINEA')
                                    <span class="plantilla-linea-contenido" style="display:block; position:absolute; left:0; right:0; top:50%; border-top: {{ max(1, (float) data_get($configuracion, 'borde_ancho', 1)) }}px {{ $bordeEstilo === 'none' ? 'solid' : $bordeEstilo }} {{ data_get($configuracion, 'borde_color', '#0f766e') }};"></span>
                                @elseif ($elemento->tipo_elemento !== 'FORMA')
                                    {!! $elemento->tipo_elemento === 'TEXTO' ? $elemento->textoConEstilos($textoElemento) : nl2br(e($textoElemento)) !!}
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="plantilla-empty-preview">
                        Este tipo de certificado todavía no tiene una plantilla activa.
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-admin-layout>
