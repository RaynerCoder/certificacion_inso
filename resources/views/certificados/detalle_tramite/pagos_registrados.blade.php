{{--
    Muestra al personal interno los pagos relacionados con el trámite.
    Permite consultar comprobantes y abrir la edición del pago manual.
--}}

{{-- Pagos, comprobantes y datos de validación solo son visibles para personal interno. --}}
@if (!$esSolicitante && $requierePagoTramite)
    <article class="tramite-detail-panel tramite-panel-pagos is-wide">
        {{-- SECCION 3: pagos relacionados al tramite. Solo se muestra lo guardado en la base de datos. --}}
        <div class="tramite-section-title-row">
            <h3 class="tramite-detail-title">
                <i class="fa-solid fa-credit-card"></i>
                Pagos
            </h3>

            @if ($requierePagoTramite && $puedeRegistrarPago)
                <button type="button" class="tramite-product-register-btn tramite-payment-register-btn"
                    data-open-payment-modal>
                    <i class="fa-solid fa-plus"></i>
                    Registrar pago
                </button>
            @endif
        </div>
        <div class="tramite-payment-records">
            @forelse ($certificado->pagos as $pago)
                <article class="tramite-payment-record">
                    <header class="tramite-payment-record-head">
                        <div class="tramite-payment-record-heading">
                            <div>
                                <strong>Pago registrado</strong>
                                <span>{{ $textoTipoPago($pago->tipo_pago) }}</span>
                            </div>
                        </div>
                        <div class="tramite-payment-record-amount">
                            <small>Monto</small>
                            <strong>{{ number_format((float) $pago->monto, 2, ',', '.') }} Bs.</strong>
                        </div>
                    </header>

                    @if ($puedeEditarPago)
                        <div class="tramite-payment-record-actions">
                            <button type="button" class="tramite-btn tramite-btn-secondary" data-edit-payment
                                data-update-url="{{ route('pagos_update', $pago) }}"
                                data-payment-id="{{ $pago->id }}" data-procedencia="{{ $pago->id_procedencia }}"
                                data-tipo="{{ $pago->tipo_pago }}"
                                data-fecha="{{ $pago->fecha ? \Illuminate\Support\Carbon::parse($pago->fecha)->format('Y-m-d') : '' }}"
                                data-monto="{{ $pago->monto }}" data-factura="{{ $pago->factura }}"
                                data-comprobante="{{ $pago->comprobante ? route('pagos_comprobante', $pago) : '' }}">
                                <i class="fa-solid fa-pen-to-square"></i>
                                Editar pago
                            </button>
                        </div>
                    @endif

                    <dl class="tramite-payment-record-grid">
                        <div>
                            <dt>Procedencia</dt>
                            <dd>{{ $pago->procedencia?->descripcion ?? 'Sin procedencia' }}</dd>
                        </div>
                        <div>
                            <dt>Fecha de pago</dt>
                            <dd>{{ $fechaCorta($pago->fecha) }}</dd>
                        </div>
                        <div>
                            <dt>Factura</dt>
                            <dd>{{ $pago->factura ?: 'Sin factura' }}</dd>
                        </div>
                        <div>
                            <dt>Fecha de validación</dt>
                            <dd>{{ $fechaHora($pago->fecha_validacion) }}</dd>
                        </div>
                        <div class="is-wide">
                            <dt>Cliente</dt>
                            <dd>{{ $nombrePersona($pago->clientePersona) }}</dd>
                        </div>
                        <div class="is-wide">
                            <dt>Registrado por</dt>
                            <dd class="tramite-user-stack">
                                <span
                                    class="tramite-user-name">{{ $nombreUsuario($pago->funcionarioUsuario, 'Sin funcionario') }}</span>
                                <span class="tramite-user-cargo">{{ $cargoUsuario($pago->funcionarioUsuario) }}</span>
                            </dd>
                        </div>
                        <div class="is-full">
                            <dt>Comprobante</dt>
                            <dd>
                                @php
                                    $rutaComprobantePago = preg_replace(
                                        '#^/?storage/#',
                                        '',
                                        (string) $pago->comprobante,
                                    );
                                    $comprobantePagoDisponible =
                                        filled($rutaComprobantePago) &&
                                        (\Illuminate\Support\Facades\Storage::disk('local')->exists(
                                            $rutaComprobantePago,
                                        ) ||
                                            \Illuminate\Support\Facades\Storage::disk('public')->exists(
                                                $rutaComprobantePago,
                                            ) ||
                                            file_exists(public_path('storage/' . $rutaComprobantePago)));
                                @endphp
                                @if ($comprobantePagoDisponible)
                                    <a href="{{ route('pagos_comprobante', $pago) }}" target="_blank"
                                        class="tramite-doc-link tramite-payment-document-link">
                                        <i class="fa-regular fa-file-pdf"></i>
                                        Ver comprobante PDF
                                    </a>
                                @elseif ($pago->comprobante)
                                    <span class="tramite-pill tramite-pill-danger">Archivo no disponible</span>
                                @else
                                    <span class="tramite-pill tramite-pill-warn">Sin PDF</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </article>
            @empty
                <div class="tramite-payment-empty">Este trámite no tiene pago registrado.</div>
            @endforelse
        </div>
    </article>
@endif
