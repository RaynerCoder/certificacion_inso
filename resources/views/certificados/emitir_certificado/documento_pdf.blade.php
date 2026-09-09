<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        * { box-sizing: border-box; }

        .documento-certificado {
            height: {{ $documento['alto'] }}px;
            overflow: hidden;
            position: relative;
            width: {{ $documento['ancho'] }}px;
        }

        .documento-fondo {
            height: 100%;
            left: 0;
            position: absolute;
            top: 0;
            width: 100%;
            z-index: 1;
        }

        .documento-fondo img {
            display: block;
            height: 100%;
            object-fit: {{ $documento['ajuste_fondo'] ?? 'fill' }};
            width: 100%;
        }

        .documento-renderizado {
            display: block;
            height: 100%;
            left: 0;
            position: absolute;
            top: 0;
            width: 100%;
            z-index: 10;
        }

        .documento-elemento {
            border: 0;
            overflow: hidden;
            position: absolute;
            z-index: 2;
        }

        .documento-elemento.tabla { padding: 0 !important; }
        .documento-elemento.imagen { padding: 0 !important; }
        .documento-elemento.forma,
        .documento-elemento.linea { padding: 0 !important; }
        .documento-elemento.texto { overflow: visible; }

        .documento-elemento.imagen img,
        .documento-elemento.qr img {
            display: block;
            height: 100%;
            width: 100%;
        }

        .documento-tabla {
            border-collapse: collapse;
            height: 100%;
            table-layout: fixed;
            width: 100%;
        }

        .documento-tabla th,
        .documento-tabla td {
            border: 1px solid currentColor;
            line-height: 1.15;
            padding: 4px 6px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .documento-tabla th { font-weight: 900; }
    </style>
</head>
<body>
    <div class="documento-certificado">
        @if ($documento['imagen_renderizada'] ?? null)
            <img class="documento-renderizado" src="{{ $documento['imagen_renderizada'] }}" alt="Certificado emitido">
        @else
        @if ($documento['fondo'])
            <div class="documento-fondo">
                <img src="{{ $documento['fondo'] }}" alt="Plantilla del certificado">
            </div>
        @endif

        @foreach ($documento['elementos'] as $item)
            @php
                $elemento = $item['elemento'];
                $tipo = $elemento->tipo_elemento;
                $valor = $item['valor'];
                $alineacion = match (strtoupper((string) ($elemento->alineacion ?? 'IZQUIERDA'))) {
                    'CENTRO' => 'center',
                    'DERECHA' => 'right',
                    'JUSTIFICADO' => 'justify',
                    default => 'left',
                };
                // Las medidas provienen directamente del Textbox de Fabric.js, que no incluye padding de contenido.
                $paddingX = 0;
                $paddingY = 0;
                $claseTipo = strtolower(match ($tipo) {
                    'TABLA' => 'tabla',
                    'IMAGEN' => 'imagen',
                    'QR' => 'qr',
                    'FORMA' => 'forma',
                    'LINEA' => 'linea',
                    default => 'texto',
                });
                $configuracion = (array) ($elemento->configuracion ?? []);
                $rotacion = max(-360, min(360, (float) data_get($configuracion, 'rotacion', 0)));
                $opacidad = max(0.05, min(1, (float) data_get($configuracion, 'opacidad', 1)));
                $fondoColor = data_get($configuracion, 'fondo_color', 'transparent') ?: 'transparent';
                $bordeColor = data_get($configuracion, 'borde_color', '#0f766e') ?: '#0f766e';
                $bordeAncho = max(0, (float) data_get($configuracion, 'borde_ancho', 0));
                $bordeEstilo = match (strtoupper((string) data_get($configuracion, 'borde_estilo', 'SOLIDO'))) {
                    'PUNTEADO' => 'dotted',
                    'DISCONTINUO' => 'dashed',
                    'DOBLE' => 'double',
                    'NINGUNO' => 'none',
                    default => 'solid',
                };
                $radioBorde = max(0, (float) data_get($configuracion, 'radio_borde', 0));
                $ajusteImagen = match (strtoupper((string) data_get($configuracion, 'ajuste_imagen', 'CONTENER'))) {
                    'CUBRIR' => 'cover',
                    'ESTIRAR' => 'fill',
                    default => 'contain',
                };

                $tabla = (array) data_get($configuracion, 'tabla', []);
                $tablaBordes = strtoupper((string) data_get($tabla, 'bordes', 'TODOS'));
                $tablaBordeColor = data_get($tabla, 'borde_color', '#0f172a') ?: '#0f172a';
                $tablaBordeAncho = max(0, (float) data_get($tabla, 'borde_ancho', 1));
                $tablaBordeEstilo = match (strtoupper((string) data_get($tabla, 'borde_estilo', 'SOLIDO'))) {
                    'PUNTEADO' => 'dotted',
                    'DISCONTINUO' => 'dashed',
                    'DOBLE' => 'double',
                    default => 'solid',
                };
                $tablaBorde = $tablaBordeAncho.'px '.$tablaBordeEstilo.' '.$tablaBordeColor;
                $tablaPaddingX = max(0, (float) data_get($tabla, 'padding_x', 6));
                $tablaPaddingY = max(0, (float) data_get($tabla, 'padding_y', 4));
                $tablaVertical = match (strtoupper((string) data_get($tabla, 'alineacion_vertical', 'MEDIO'))) {
                    'SUPERIOR' => 'top',
                    'INFERIOR' => 'bottom',
                    default => 'middle',
                };
                $estiloCelda = function (bool $encabezado, bool $primeraColumna) use ($tablaBordes, $tablaBorde, $tablaPaddingX, $tablaPaddingY, $tablaVertical): string {
                    $borde = match ($tablaBordes) {
                        'NINGUNO', 'EXTERIOR' => 'border: 0;',
                        'HORIZONTAL' => 'border: 0; border-bottom: '.$tablaBorde.';',
                        'VERTICAL' => $primeraColumna ? 'border: 0;' : 'border: 0; border-left: '.$tablaBorde.';',
                        'ENCABEZADO' => $encabezado ? 'border: 0; border-bottom: '.$tablaBorde.';' : 'border: 0;',
                        default => 'border: '.$tablaBorde.';',
                    };

                    return $borde.' padding: '.$tablaPaddingY.'px '.$tablaPaddingX.'px; vertical-align: '.$tablaVertical.';';
                };
            @endphp

            @if (in_array($tipo, ['TABLA', 'FORMA', 'LINEA'], true) || filled($valor))
                <div class="documento-elemento {{ $claseTipo }}"
                    style="
                        background: {{ in_array($tipo, ['TABLA', 'LINEA'], true) ? 'transparent' : $fondoColor }};
                        border: {{ in_array($tipo, ['TABLA', 'LINEA'], true) || $bordeEstilo === 'none' ? '0' : $bordeAncho.'px '.$bordeEstilo.' '.$bordeColor }};
                        border-radius: {{ $tipo === 'FORMA' && data_get($configuracion, 'forma') === 'ELIPSE' ? '50%' : $radioBorde.'px' }};
                        color: {{ data_get($elemento, 'color_texto', '#0f172a') ?: '#0f172a' }};
                        font-family: '{{ data_get($elemento, 'tipo_letra', 'Arial') ?: 'Arial' }}', Arial, sans-serif;
                        font-size: {{ (float) $elemento->tamano_letra }}px;
                        font-style: {{ data_get($elemento, 'cursiva') ? 'italic' : 'normal' }};
                        font-weight: {{ data_get($elemento, 'negrita') ? 700 : 400 }};
                        height: {{ (float) $elemento->alto }}px;
                        left: {{ (float) $elemento->posicion_x }}px;
                        line-height: {{ (float) data_get($elemento, 'interlineado', 1.25) }};
                        opacity: {{ $opacidad }};
                        padding: {{ $paddingY }}px {{ $paddingX }}px;
                        text-align: {{ $alineacion }};
                        text-decoration: {{ data_get($elemento, 'subrayado') ? 'underline' : 'none' }};
                        top: {{ (float) $elemento->posicion_y }}px;
                        transform: rotate({{ $rotacion }}deg);
                        transform-origin: center center;
                        width: {{ (float) $elemento->ancho }}px;
                    ">
                    @if ($tipo === 'IMAGEN' || ($tipo === 'QR' && str_starts_with((string) $valor, 'data:image')))
                        <img src="{{ $valor }}" alt="Imagen del certificado" style="object-fit: {{ $ajusteImagen }};">
                    @elseif ($tipo === 'TABLA')
                        <table class="documento-tabla" style="
                            border: {{ $tablaBordes === 'EXTERIOR' ? $tablaBorde : '0' }};
                            color: {{ data_get($elemento, 'color_texto', '#0f172a') ?: '#0f172a' }};
                        ">
                            <thead>
                                <tr>
                                    @forelse ($elemento->columnas as $columna)
                                        <th style="
                                            {{ $estiloCelda(true, $loop->first) }}
                                            background: {{ data_get($tabla, 'encabezado_fondo', '#f1f5f9') }};
                                            color: {{ data_get($tabla, 'encabezado_texto', '#0f172a') }};
                                            width: {{ (float) ($columna->ancho ?: 25) }}%;
                                        ">
                                            {{ $columna->titulo_columna ?: $columna->codigo_campo }}
                                        </th>
                                    @empty
                                        <th>Dato</th>
                                    @endforelse
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($item['filas'] as $fila)
                                    <tr>
                                        @foreach ($fila as $celda)
                                            <td style="
                                                {{ $estiloCelda(false, $loop->first) }}
                                                background: {{ data_get($tabla, 'filas_alternas') && $loop->parent->iteration % 2 === 0 ? data_get($tabla, 'filas_alternas_color', '#f8fafc') : data_get($tabla, 'celdas_fondo', '#ffffff') }};
                                            ">{{ $celda }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td>Sin datos</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif ($tipo === 'LINEA')
                        <div style="position:absolute; left:0; right:0; top:50%; border-top: {{ max(1, $bordeAncho) }}px {{ $bordeEstilo === 'none' ? 'solid' : $bordeEstilo }} {{ $bordeColor }};"></div>
                    @elseif ($tipo !== 'FORMA')
                        {!! $elemento instanceof \App\Models\PlantillaElemento && $tipo === 'TEXTO'
                            ? $elemento->textoConEstilosResueltos($documento['valores'] ?? [])
                            : nl2br(e($valor)) !!}
                    @endif
                </div>
            @endif
        @endforeach
        @endif
    </div>
</body>
</html>
