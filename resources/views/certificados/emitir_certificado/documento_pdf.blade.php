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
            width: 100%;
        }

        .documento-elemento {
            border: 0;
            overflow: hidden;
            position: absolute;
            z-index: 2;
        }

        .documento-elemento.tabla { padding: 0 !important; }
        .documento-elemento.imagen { padding: 0 !important; }

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
                $usaPadding = !in_array($tipo, ['IMAGEN', 'TABLA', 'QR'], true);
                $paddingX = (float) data_get($elemento, 'padding_x', $usaPadding ? 7 : 0);
                $paddingY = (float) data_get($elemento, 'padding_y', $usaPadding ? 5 : 0);
                $claseTipo = strtolower($tipo === 'TABLA' ? 'tabla' : ($tipo === 'IMAGEN' ? 'imagen' : ($tipo === 'QR' ? 'qr' : 'texto')));
            @endphp

            @if ($tipo === 'TABLA' || filled($valor))
                <div class="documento-elemento {{ $claseTipo }}"
                    style="
                        color: {{ data_get($elemento, 'color_texto', '#0f172a') ?: '#0f172a' }};
                        font-family: '{{ data_get($elemento, 'tipo_letra', 'Arial') ?: 'Arial' }}', Arial, sans-serif;
                        font-size: {{ (int) $elemento->tamano_letra }}px;
                        font-style: {{ data_get($elemento, 'cursiva') ? 'italic' : 'normal' }};
                        font-weight: {{ data_get($elemento, 'negrita') ? 900 : 700 }};
                        height: {{ (float) $elemento->alto }}px;
                        left: {{ (float) $elemento->posicion_x }}px;
                        line-height: {{ (float) data_get($elemento, 'interlineado', 1.25) }};
                        padding: {{ $paddingY }}px {{ $paddingX }}px;
                        text-align: {{ $alineacion }};
                        text-decoration: {{ data_get($elemento, 'subrayado') ? 'underline' : 'none' }};
                        top: {{ (float) $elemento->posicion_y }}px;
                        width: {{ (float) $elemento->ancho }}px;
                    ">
                    @if ($tipo === 'IMAGEN' || ($tipo === 'QR' && str_starts_with((string) $valor, 'data:image')))
                        <img src="{{ $valor }}" alt="Imagen del certificado">
                    @elseif ($tipo === 'TABLA')
                        <table class="documento-tabla">
                            <thead>
                                <tr>
                                    @forelse ($elemento->columnas as $columna)
                                        <th style="width: {{ (float) ($columna->ancho ?: 25) }}%;">
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
                                            <td>{{ $celda }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td>Sin datos</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        {!! nl2br(e($valor)) !!}
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</body>
</html>
