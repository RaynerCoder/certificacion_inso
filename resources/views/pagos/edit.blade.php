<x-admin-layout
    title="Editar pago | Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Pagos', 'href' => route('pagos_index')],
        ['name' => 'Editar'],
    ]">

    <style>
        .pago-edit-shell {
            margin: 0;
            max-width: none;
            width: 100%;
        }

        .pago-edit-card {
            background: #fff;
            border: 1px solid #dbe4ee;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgb(15 23 42 / 6%);
            overflow: hidden;
        }

        .pago-edit-head {
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 20px;
        }

        .pago-edit-head h1 {
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 800;
            margin: 0;
        }

        .pago-edit-head p {
            color: #64748b;
            font-size: .82rem;
            margin: 4px 0 0;
        }

        .pago-edit-summary {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .pago-edit-summary > div {
            border-right: 1px solid #e2e8f0;
            min-width: 0;
            padding: 12px 16px;
        }

        .pago-edit-summary > div:last-child {
            border-right: 0;
        }

        .pago-edit-summary dt {
            color: #64748b;
            font-size: .68rem;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .pago-edit-summary dd {
            color: #0f172a;
            font-size: .82rem;
            font-weight: 750;
            line-height: 1.35;
            margin: 0;
            overflow-wrap: anywhere;
        }

        .pago-edit-type {
            color: #047857;
            display: block;
            font-size: .72rem;
            margin-top: 2px;
        }

        .pago-edit-form {
            padding: 16px 20px;
        }

        .pago-edit-grid {
            display: grid;
            gap: 14px 16px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pago-edit-field > label {
            color: #334155;
            display: block;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .pago-edit-control {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #0f172a;
            min-height: 40px;
            padding: 8px 11px;
            width: 100%;
        }

        .pago-edit-control:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 2px rgb(13 148 136 / 12%);
            outline: none;
        }

        /* Mismo selector compacto de PDF usado en responsables y tramitadores. */
        .responsable-modal-pdf {
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            gap: 8px;
            height: 42px;
            justify-content: space-between;
            padding: 4px 7px 4px 9px;
            width: 100%;
        }

        .responsable-modal-pdf-info {
            align-items: center;
            display: flex;
            gap: 9px;
            min-width: 0;
        }

        .responsable-modal-pdf-info > div {
            min-width: 0;
        }

        .responsable-modal-pdf-info i {
            color: #dc2626;
            font-size: 16px;
        }

        .responsable-modal-pdf-info strong {
            color: #334155;
            display: block;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.15;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .responsable-modal-pdf-info span {
            color: #64748b;
            display: block;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.15;
            margin-top: 1px;
            white-space: nowrap;
        }

        .responsable-modal-pdf-actions {
            display: flex;
            flex: 0 0 auto;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
        }

        .responsable-modal-pdf-button {
            align-items: center;
            border-radius: 6px;
            display: inline-flex;
            font-size: 11px;
            font-weight: 900;
            gap: 5px;
            height: 28px;
            justify-content: center;
            line-height: 1;
            margin: 0;
            padding: 0 8px;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease;
            vertical-align: middle;
        }

        .responsable-modal-pdf-button.is-select {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            color: #0f766e;
            cursor: pointer;
        }

        .responsable-modal-pdf-button.is-view {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .responsable-modal-pdf-button.is-remove {
            background: #fff1f2;
            border: 1px solid #fecaca;
            color: #be123c;
        }

        .responsable-modal-pdf-button:hover:not(:disabled) {
            filter: brightness(.97);
        }

        .responsable-modal-pdf-button:disabled {
            cursor: not-allowed;
            opacity: .5;
        }

        .pago-edit-actions {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 14px;
            padding-top: 12px;
        }

        @media (max-width: 960px) {
            .pago-edit-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pago-edit-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pago-edit-summary > div:nth-child(2) {
                border-right: 0;
            }

            .pago-edit-summary > div:nth-child(-n+2) {
                border-bottom: 1px solid #e2e8f0;
            }
        }

        @media (max-width: 640px) {
            .pago-edit-head,
            .pago-edit-form {
                padding: 14px;
            }

            .pago-edit-summary,
            .pago-edit-grid {
                grid-template-columns: 1fr;
            }

            .pago-edit-summary > div,
            .pago-edit-summary > div:nth-child(2) {
                border-bottom: 1px solid #e2e8f0;
                border-right: 0;
            }

            .pago-edit-summary > div:last-child {
                border-bottom: 0;
            }

            .responsable-modal-pdf {
                align-items: flex-start;
                flex-direction: column;
                height: auto;
                min-height: 42px;
                padding: 8px;
            }

            .responsable-modal-pdf-info span {
                white-space: normal;
            }

            .responsable-modal-pdf-actions {
                justify-content: flex-start;
                width: 100%;
            }

            .pago-edit-actions > * {
                flex: 1;
                justify-content: center;
            }
        }
    </style>

    <div class="pago-edit-shell">
        <section class="pago-edit-card">
            <header class="pago-edit-head">
                <h1>Editar pago</h1>
                <p>Corrija únicamente los datos registrados para este pago.</p>
            </header>

            <dl class="pago-edit-summary">
                <div>
                    <dt>Código del trámite</dt>
                    <dd>{{ $certificado->codigo }}</dd>
                </div>
                <div>
                    <dt>Tipo de trámite</dt>
                    <dd>{{ $certificado->tipoCertificado?->nombre ?? 'Sin tipo registrado' }}</dd>
                </div>
                <div>
                    <dt>Beneficiario</dt>
                    <dd>
                        {{ $nombreBeneficiario }}
                        <span class="pago-edit-type">{{ $tipoBeneficiario }}</span>
                    </dd>
                </div>
                <div>
                    <dt>Pago</dt>
                    <dd>ID {{ $pago->id }}</dd>
                </div>
            </dl>

            <form action="{{ route('pagos_update', $pago) }}" method="POST" enctype="multipart/form-data" class="pago-edit-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_id_pago" value="{{ $pago->id }}">
                <input type="hidden" name="form_return_to" value="pagos_index">

                <div class="pago-edit-grid">
                    <div class="pago-edit-field">
                        <label for="form_id_procedencia_pago">Procedencia</label>
                        <select id="form_id_procedencia_pago" name="form_id_procedencia_pago" class="pago-edit-control" required>
                            <option value="">Seleccione procedencia</option>
                            @foreach ($procedencias as $procedencia)
                                <option value="{{ $procedencia->id }}" @selected(old('form_id_procedencia_pago', $pago->id_procedencia) == $procedencia->id)>
                                    {{ $procedencia->codigo }}{{ $procedencia->descripcion ? ' - ' . $procedencia->descripcion : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('form_id_procedencia_pago') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="pago-edit-field">
                        <label for="form_tipo_pago">Tipo de pago</label>
                        <select id="form_tipo_pago" name="form_tipo_pago" class="pago-edit-control" required>
                            <option value="">Seleccione tipo</option>
                            @foreach (\App\Models\Pago::TIPOS_PAGOS as $valor => $texto)
                                <option value="{{ $valor }}" @selected(old('form_tipo_pago', $pago->tipo_pago) === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('form_tipo_pago') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="pago-edit-field">
                        <label for="form_fecha_pago">Fecha de pago</label>
                        <input id="form_fecha_pago" name="form_fecha_pago" type="date" required
                            value="{{ old('form_fecha_pago', $pago->fecha ? \Illuminate\Support\Carbon::parse($pago->fecha)->format('Y-m-d') : '') }}"
                            class="pago-edit-control">
                        @error('form_fecha_pago') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="pago-edit-field">
                        <label for="form_monto_pago">Monto</label>
                        <input id="form_monto_pago" name="form_monto_pago" type="number" min="0.01" step="0.01" required
                            value="{{ old('form_monto_pago', $pago->monto) }}" class="pago-edit-control">
                        @error('form_monto_pago') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="pago-edit-field">
                        <label for="form_factura_pago">Factura</label>
                        <input id="form_factura_pago" name="form_factura_pago" type="text" maxlength="100"
                            value="{{ old('form_factura_pago', $pago->factura) }}" class="pago-edit-control">
                        @error('form_factura_pago') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="pago-edit-field">
                        <label for="form_comprobante_pago">Comprobante PDF</label>
                        <input id="form_comprobante_pago" name="form_comprobante_pago" type="file"
                            accept="application/pdf,.pdf" class="sr-only" data-payment-edit-file>

                        <div class="responsable-modal-pdf">
                            <div class="responsable-modal-pdf-info">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                <div>
                                    <strong data-payment-edit-file-name>
                                        {{ $tieneComprobanteDisponible ? 'Comprobante registrado' : 'Sin PDF seleccionado' }}
                                    </strong>
                                    <span data-payment-edit-file-state>
                                        {{ $tieneComprobanteDisponible ? 'PDF guardado actualmente.' : 'Seleccione un documento PDF si corresponde.' }}
                                    </span>
                                </div>
                            </div>

                            <div class="responsable-modal-pdf-actions">
                                <label for="form_comprobante_pago" class="responsable-modal-pdf-button is-select">
                                    <i class="fa-solid fa-upload" aria-hidden="true"></i>
                                    <span>Seleccionar</span>
                                </label>
                                <button type="button" class="responsable-modal-pdf-button is-view"
                                    data-payment-edit-file-view
                                    data-current-url="{{ $tieneComprobanteDisponible ? route('pagos_comprobante', $pago) : '' }}"
                                    @disabled(!$tieneComprobanteDisponible)>
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    <span>Ver</span>
                                </button>
                                <button type="button" class="responsable-modal-pdf-button is-remove"
                                    data-payment-edit-file-remove disabled>
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    <span>Quitar</span>
                                </button>
                            </div>
                        </div>
                        @if ($pago->comprobante && !$tieneComprobanteDisponible)
                            <p class="mt-1 text-xs font-semibold text-red-600">La ruta está registrada, pero el archivo no existe en el almacenamiento.</p>
                        @endif
                        @error('form_comprobante_pago') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="pago-edit-actions">
                    <x-wire-button href="{{ route('pagos_index') }}" secondary>
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Actualizar pago
                    </x-wire-button>
                </div>
            </form>
        </section>
    </div>

    @push('js')
        <script>
            const archivoPago = document.querySelector('[data-payment-edit-file]');
            const nombreArchivoPago = document.querySelector('[data-payment-edit-file-name]');
            const estadoArchivoPago = document.querySelector('[data-payment-edit-file-state]');
            const verArchivoPago = document.querySelector('[data-payment-edit-file-view]');
            const quitarArchivoPago = document.querySelector('[data-payment-edit-file-remove]');
            const urlArchivoActual = verArchivoPago?.dataset.currentUrl || '';
            let urlArchivoTemporal = '';

            const mostrarArchivoActual = () => {
                nombreArchivoPago.textContent = urlArchivoActual ? 'Comprobante registrado' : 'Sin PDF seleccionado';
                estadoArchivoPago.textContent = urlArchivoActual
                    ? 'PDF guardado actualmente.'
                    : 'Seleccione un documento PDF si corresponde.';
                verArchivoPago.disabled = !urlArchivoActual;
                quitarArchivoPago.disabled = true;
            };

            archivoPago?.addEventListener('change', () => {
                const archivo = archivoPago.files?.[0];

                if (urlArchivoTemporal) {
                    URL.revokeObjectURL(urlArchivoTemporal);
                    urlArchivoTemporal = '';
                }

                if (!archivo) {
                    mostrarArchivoActual();
                    return;
                }

                const esPdf = archivo.type === 'application/pdf' || archivo.name.toLowerCase().endsWith('.pdf');
                if (!esPdf) {
                    archivoPago.value = '';
                    mostrarArchivoActual();
                    estadoArchivoPago.textContent = 'Solo se permiten archivos PDF.';
                    return;
                }

                urlArchivoTemporal = URL.createObjectURL(archivo);
                nombreArchivoPago.textContent = archivo.name;
                estadoArchivoPago.textContent = 'Documento listo para reemplazar el comprobante.';
                verArchivoPago.disabled = false;
                quitarArchivoPago.disabled = false;
            });

            verArchivoPago?.addEventListener('click', () => {
                const url = urlArchivoTemporal || urlArchivoActual;
                if (url) window.open(url, '_blank', 'noopener,noreferrer');
            });

            quitarArchivoPago?.addEventListener('click', () => {
                archivoPago.value = '';
                if (urlArchivoTemporal) URL.revokeObjectURL(urlArchivoTemporal);
                urlArchivoTemporal = '';
                mostrarArchivoActual();
            });

            window.addEventListener('beforeunload', () => {
                if (urlArchivoTemporal) URL.revokeObjectURL(urlArchivoTemporal);
            });
        </script>
    @endpush
</x-admin-layout>
