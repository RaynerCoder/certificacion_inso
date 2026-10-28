<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de certificado | INSO</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, Helvetica, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            align-items: center;
            background: #f1f5f9;
            color: #0f172a;
            display: flex;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
            padding: 24px;
        }

        .verification-card {
            background: #fff;
            border: 1px solid #dbe4ef;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgb(15 23 42 / 12%);
            max-width: 680px;
            overflow: hidden;
            width: 100%;
        }

        .verification-head {
            background: #047857;
            color: #fff;
            padding: 26px 30px;
        }

        .verification-head p {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .09em;
            margin: 0 0 7px;
            text-transform: uppercase;
        }

        .verification-head h1 {
            font-size: 24px;
            margin: 0;
        }

        .verification-content {
            padding: 28px 30px 30px;
        }

        .status {
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            padding: 7px 12px;
            text-transform: uppercase;
        }

        .status-emitido {
            background: #dcfce7;
            color: #166534;
        }

        .status-vencido {
            background: #fef3c7;
            color: #92400e;
        }

        .status-anulado {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-otro {
            background: #e2e8f0;
            color: #334155;
        }

        .verified-title {
            font-size: 19px;
            margin: 18px 0 5px;
        }

        .verified-text {
            color: #475569;
            line-height: 1.55;
            margin: 0;
        }

        dl {
            display: grid;
            gap: 0;
            grid-template-columns: minmax(155px, 1fr) minmax(0, 2fr);
            margin: 24px 0 0;
        }

        dt,
        dd {
            border-bottom: 1px solid #e2e8f0;
            margin: 0;
            padding: 13px 0;
        }

        dt {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        dd {
            font-size: 14px;
            font-weight: 700;
            padding-left: 20px;
        }

        .invalid {
            color: #475569;
            line-height: 1.6;
            text-align: center;
            padding: 18px 0 4px;
        }

        .invalid strong {
            color: #b91c1c;
            display: block;
            font-size: 20px;
            margin-bottom: 8px;
        }

        .footer {
            color: #64748b;
            font-size: 12px;
            margin-top: 25px;
            text-align: center;
        }

        @media (max-width: 520px) {
            body {
                padding: 14px;
            }

            .verification-head,
            .verification-content {
                padding-left: 20px;
                padding-right: 20px;
            }

            dl {
                grid-template-columns: 1fr;
            }

            dt {
                border-bottom: 0;
                padding-bottom: 3px;
            }

            dd {
                padding: 3px 0 13px;
            }
        }
    </style>
</head>

<body>
    <main class="verification-card">
        <header class="verification-head">
            <p>Instituto Nacional de Salud Ocupacional</p>
            <h1>{{ $verificacion['titulo'] ?? 'Verificación de certificado' }}</h1>
        </header>

        <section class="verification-content">
            @if (!$verificacion)
                <div class="invalid">
                    <strong>Certificado no encontrado</strong>
                    El código consultado no corresponde a un certificado emitido o ya no se encuentra disponible.
                </div>
            @else
                @php
                    $claseEstado = match ($verificacion['estado']) {
                        'EMITIDO' => 'status-emitido',
                        'VENCIDO' => 'status-vencido',
                        'ANULADO' => 'status-anulado',
                        default => 'status-otro',
                    };
                @endphp
                <span class="status {{ $claseEstado }}">{{ $verificacion['estado'] }}</span>
                <h2 class="verified-title">Certificado verificado</h2>
                <p class="verified-text">La información corresponde al registro oficial del sistema de certificación del
                    INSO.</p>

                <dl>
                    @foreach ($verificacion['filas'] as $fila)
                        <dt>{{ $fila['etiqueta'] }}</dt>
                        <dd>{{ $fila['valor'] }}</dd>
                    @endforeach
                </dl>
            @endif

            <p class="footer">Consulta pública de validez documental.</p>
        </section>
    </main>
</body>

</html>
