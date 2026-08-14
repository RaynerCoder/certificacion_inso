<x-admin-layout
    title="Detalle del pago | Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Pagos', 'href' => route('pagos_index')],
        ['name' => 'Detalle'],
    ]">

    <style>
        /* Mantiene la misma estructura visual utilizada en Detalle del usuario. */
        .pago-detalle {
            display: grid;
            gap: 16px;
        }

        .pago-detalle-toolbar,
        .pago-detalle-actions,
        .pago-identidad,
        .pago-identidad-dato,
        .pago-seccion-titulo,
        .pago-comprobante {
            align-items: center;
            display: flex;
        }

        .pago-detalle-toolbar {
            gap: 16px;
            justify-content: space-between;
        }

        .pago-detalle-titulo {
            color: #0f172a;
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .pago-detalle-actions {
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .pago-identidad {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #dbe3ee;
            border-top: 1px solid #dbe3ee;
            display: grid;
            gap: 0;
            grid-template-columns: minmax(90px, .45fr) minmax(180px, .85fr) minmax(240px, 1.35fr) minmax(120px, .55fr);
            min-width: 0;
            padding: 16px 18px;
        }

        .pago-identidad-dato {
            border-left: 1px solid #dbe3ee;
            gap: 8px;
            margin-left: 18px;
            min-height: 34px;
            min-width: 0;
            padding-left: 18px;
        }

        .pago-identidad-dato:first-child {
            border-left: 0;
            margin-left: 0;
            padding-left: 0;
        }

        .pago-identidad-bloque {
            min-width: 0;
        }

        .pago-identidad-etiqueta {
            color: #64748b;
            display: block;
            font-size: 12px;
            font-weight: 700;
        }

        .pago-identidad-valor {
            color: #172033;
            display: block;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .pago-identidad-monto {
            color: #047857;
            font-size: 17px;
            font-weight: 800;
            white-space: nowrap;
        }

        .pago-contenido {
            background: #fff;
            border-bottom: 1px solid #dbe3ee;
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(400px, .85fr);
        }

        .pago-seccion {
            min-width: 0;
            padding: 16px 18px;
        }

        .pago-seccion + .pago-seccion {
            border-left: 1px solid #dbe3ee;
        }

        .pago-seccion-titulo {
            color: #172033;
            font-size: 15px;
            font-weight: 800;
            gap: 9px;
            margin: 0 0 12px;
        }

        .pago-seccion-titulo i {
            color: #0f766e;
            text-align: center;
            width: 18px;
        }

        .pago-datos {
            display: grid;
            gap: 0 26px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pago-dato {
            border-bottom: 1px solid #edf1f6;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(115px, .8fr) minmax(0, 1.2fr);
            min-width: 0;
            padding: 8px 0;
        }

        .pago-dato-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .pago-dato-valor {
            color: #172033;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.45;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .pago-dato-secundario {
            color: #047857;
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-top: 2px;
        }

        .pago-registro {
            display: grid;
        }

        .pago-registro .pago-dato {
            grid-template-columns: minmax(130px, .75fr) minmax(0, 1.25fr);
        }

        .pago-comprobante {
            gap: 8px;
            min-width: 0;
        }

        .pago-comprobante-linea {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: space-between;
            width: 100%;
        }

        .pago-comprobante > i {
            color: #dc2626;
            flex: 0 0 auto;
        }

        .pago-comprobante-info {
            min-width: 0;
        }

        .pago-comprobante-link {
            align-items: center;
            border: 1px solid #6ee7b7;
            border-radius: 7px;
            color: #047857;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 5px;
            min-height: 30px;
            padding: 4px 9px;
            white-space: nowrap;
        }

        @media (max-width: 1024px) {
            .pago-identidad {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pago-identidad-dato:last-child {
                grid-column: 1 / -1;
                margin-top: 10px;
                padding-top: 10px;
            }

            .pago-contenido {
                grid-template-columns: 1fr;
            }

            .pago-seccion + .pago-seccion {
                border-left: 0;
                border-top: 1px solid #dbe3ee;
            }

            .pago-registro {
                gap: 0 26px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .pago-detalle-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .pago-detalle-actions {
                justify-content: stretch;
            }

            .pago-detalle-actions > * {
                flex: 1 1 0;
                justify-content: center;
            }

            .pago-identidad {
                align-items: flex-start;
                flex-direction: column;
                display: flex;
                padding: 14px;
            }

            .pago-identidad-dato {
                border-left: 0;
                border-top: 1px solid #edf1f6;
                margin: 12px 0 0;
                padding: 12px 0 0;
                width: 100%;
            }

            .pago-identidad-dato:last-child {
                margin-top: 12px;
                padding-top: 12px;
            }

            .pago-datos,
            .pago-registro {
                grid-template-columns: 1fr;
            }

            .pago-dato,
            .pago-registro .pago-dato {
                grid-template-columns: minmax(110px, .75fr) minmax(0, 1.25fr);
            }
        }

        @media (max-width: 430px) {
            .pago-detalle-titulo {
                font-size: 19px;
            }

            .pago-seccion {
                padding: 14px;
            }

            .pago-dato,
            .pago-registro .pago-dato {
                gap: 3px;
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="pago-detalle">
        <div class="pago-detalle-toolbar">
            <h1 class="pago-detalle-titulo">Detalle del pago</h1>

            <div class="pago-detalle-actions">
                <x-wire-button href="{{ route('pagos_index') }}" secondary>
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Volver
                </x-wire-button>

                @if (auth()->user()?->puede('pagos.validar'))
                    <x-wire-button href="{{ route('pagos_edit', $pago) }}" blue>
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        Editar pago
                    </x-wire-button>
                @endif
            </div>
        </div>

        <section class="pago-identidad">
            <div class="pago-identidad-dato">
                <div class="pago-identidad-bloque">
                    <span class="pago-identidad-etiqueta">Pago</span>
                    <span class="pago-identidad-valor">ID {{ $pago->id }}</span>
                </div>
            </div>

            <div class="pago-identidad-dato">
                <div class="pago-identidad-bloque">
                    <span class="pago-identidad-etiqueta">Trámite</span>
                    <span class="pago-identidad-valor">{{ $certificado->codigo }}</span>
                </div>
            </div>

            <div class="pago-identidad-dato">
                <div class="pago-identidad-bloque">
                    <span class="pago-identidad-etiqueta">Beneficiario</span>
                    <span class="pago-identidad-valor">{{ $nombreBeneficiario }}</span>
                    <span class="pago-dato-secundario">{{ $tipoBeneficiario }}</span>
                </div>
            </div>

            <div class="pago-identidad-dato">
                <div class="pago-identidad-bloque">
                    <span class="pago-identidad-etiqueta">Monto</span>
                    <span class="pago-identidad-monto">{{ number_format((float) $pago->monto, 2, ',', '.') }} Bs.</span>
                </div>
            </div>
        </section>

        <div class="pago-contenido">
            <section class="pago-seccion">
                <h2 class="pago-seccion-titulo">
                    <i class="fa-regular fa-address-card" aria-hidden="true"></i>
                    Información del pago
                </h2>

                <div class="pago-datos">
                    <div class="pago-dato">
                        <span class="pago-dato-label">Tipo de trámite</span>
                        <span class="pago-dato-valor">{{ $certificado->tipoCertificado?->nombre ?? 'Sin tipo registrado' }}</span>
                    </div>
                    <div class="pago-dato">
                        <span class="pago-dato-label">Procedencia</span>
                        <span class="pago-dato-valor">{{ $pago->procedencia?->descripcion ?? 'Sin procedencia' }}</span>
                    </div>
                    <div class="pago-dato">
                        <span class="pago-dato-label">Tipo de pago</span>
                        <span class="pago-dato-valor">{{ \App\Models\Pago::TIPOS_PAGOS[$pago->tipo_pago] ?? ($pago->tipo_pago ?: 'Sin tipo') }}</span>
                    </div>
                    <div class="pago-dato">
                        <span class="pago-dato-label">Fecha de pago</span>
                        <span class="pago-dato-valor">{{ $pago->fecha ? \Illuminate\Support\Carbon::parse($pago->fecha)->format('d/m/Y') : 'Sin fecha' }}</span>
                    </div>
                    <div class="pago-dato">
                        <span class="pago-dato-label">Número de factura</span>
                        <span class="pago-dato-valor">{{ filled($pago->factura) ? $pago->factura : 'Sin factura' }}</span>
                    </div>
                </div>
            </section>

            <section class="pago-seccion">
                <h2 class="pago-seccion-titulo">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    Registro y comprobante
                </h2>

                <div class="pago-registro">
                    <div class="pago-dato">
                        <span class="pago-dato-label">Registrado por</span>
                        <span class="pago-dato-valor">
                            {{ $nombreFuncionario }}
                            <span class="pago-dato-secundario">{{ $cargoFuncionario }}</span>
                        </span>
                    </div>
                    <div class="pago-dato">
                        <span class="pago-dato-label">Fecha de validación</span>
                        <span class="pago-dato-valor">{{ $pago->fecha_validacion ? \Illuminate\Support\Carbon::parse($pago->fecha_validacion)->format('d/m/Y') : 'Sin fecha' }}</span>
                    </div>
                    <div class="pago-dato">
                        <span class="pago-dato-label">Comprobante</span>
                        <span class="pago-dato-valor">
                            <span class="pago-comprobante-linea">
                                <span class="pago-comprobante">
                                    <i class="fa-regular fa-file-pdf" aria-hidden="true"></i>
                                    <span class="pago-comprobante-info">
                                        {{ $tieneComprobanteDisponible ? 'Archivo disponible' : ($pago->comprobante ? 'Archivo no disponible' : 'Sin archivo registrado') }}
                                    </span>
                                </span>

                                @if ($tieneComprobanteDisponible)
                                    <a href="{{ route('pagos_comprobante', $pago) }}" target="_blank" rel="noopener noreferrer" class="pago-comprobante-link">
                                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                        Ver PDF
                                    </a>
                                @endif
                            </span>
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
