@php
    $tipoCertificado = $certificado->tipoCertificado;
    $beneficiario = $certificado->beneficiario;
    $nombreBeneficiario = $beneficiario?->empresa?->razon_social ?: collect([
        $beneficiario?->natural?->nombres,
        $beneficiario?->natural?->apellido_paterno,
        $beneficiario?->natural?->apellido_materno,
    ])->filter()->implode(' ');

    $pagoConfirmado = filled($pago->fecha_validacion);
    $tieneImagenQr = filled($ultimoQrOpenBcb?->imagen_qr_path);
    $fechaVencimientoQr = $ultimoQrOpenBcb?->created_at?->copy()->addDay();
    $qrVigente = $tieneImagenQr && $fechaVencimientoQr?->isFuture();
    $mostrarQr = $qrVigente && ! $pagoConfirmado;
    $urlImagenQr = $tieneImagenQr
        ? route('pagos_openbcb_mostrar_imagen_qr', ['pagoOpenBcb' => $ultimoQrOpenBcb->id], false)
        : null;
    $urlContenidoModal = route('pagos_openbcb_mostrar_pago', [
        'certificado' => $certificado,
        'modal' => 1,
    ]);
@endphp

<section class="pago-modal" role="dialog" aria-modal="true" aria-labelledby="pagoModalTitulo"
    data-contenido-pago data-url-modal="{{ $urlContenidoModal }}">
    <header class="pago-modal-header">
        <div>
            <span>Trámite seleccionado</span>
            <h1 id="pagoModalTitulo">N.º {{ $certificado->id }} · Pago electrónico</h1>
        </div>

        <button type="button" class="pago-modal-close" data-cerrar-modal-pago
            data-url-retorno="{{ route('seguimientos_mis_tramites_beneficiario') }}"
            title="Cerrar" aria-label="Cerrar pago">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </header>

    <div class="pago-modal-content">
        {{-- Columna izquierda: datos e importe del trámite seleccionado. --}}
        <section class="pago-transaction" aria-labelledby="detalleTransaccionTitulo">
            <div class="pago-section-title">
                <h2 id="detalleTransaccionTitulo">Detalles de la transacción</h2>
            </div>

            <dl class="pago-detail-list">
                <div><dt>Número de trámite</dt><dd>#{{ $certificado->id }}</dd></div>
                <div><dt>Código de pago</dt><dd>#{{ $pago->id }}</dd></div>
                <div><dt>Beneficiario</dt><dd>{{ $nombreBeneficiario ?: 'Sin información' }}</dd></div>
                <div><dt>Tipo de certificado</dt><dd>{{ $tipoCertificado?->nombre ?? 'Sin información' }}</dd></div>
                <div>
                    <dt>Estado</dt>
                    <dd>
                        <span class="pago-status {{ $pagoConfirmado ? 'is-confirmed' : '' }}">
                            <i class="fa-solid {{ $pagoConfirmado ? 'fa-circle-check' : 'fa-clock' }}" aria-hidden="true"></i>
                            {{ $pagoConfirmado ? 'Pago confirmado' : 'Pago pendiente' }}
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="pago-concept-table" role="table" aria-label="Concepto del pago">
                <div class="pago-concept-head" role="row">
                    <span role="columnheader">Item</span>
                    <span role="columnheader">Precio</span>
                    <span role="columnheader">Cantidad</span>
                    <span role="columnheader">Total</span>
                </div>
                <div class="pago-concept-row" role="row">
                    <span role="cell">{{ $tipoCertificado?->nombre ?? 'Certificado' }}</span>
                    <span role="cell">Bs {{ number_format((float) $pago->monto, 2, ',', '.') }}</span>
                    <span role="cell">1</span>
                    <span role="cell">Bs {{ number_format((float) $pago->monto, 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="pago-total">
                <span>Total</span>
                <strong>Bs {{ number_format((float) $pago->monto, 2, ',', '.') }}</strong>
            </div>
        </section>

        {{-- Columna derecha: método seleccionado, instrucciones y código QR. --}}
        <section class="pago-method" aria-labelledby="metodoPagoTitulo">
            <div class="pago-method-heading">
                <div>
                    <h2 id="metodoPagoTitulo">Métodos de pago</h2>
                    <p>Utiliza el código QR para completar el pago.</p>
                </div>

                @if ($mostrarQr)
                    <div class="pago-expiration" data-qr-vencimiento="{{ $fechaVencimientoQr->toIso8601String() }}">
                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        <div>
                            <span>Tiempo límite para pagar</span>
                            <strong data-qr-contador>Calculando...</strong>
                        </div>
                    </div>
                @endif
            </div>

            <div class="pago-method-options" aria-label="Método de pago seleccionado">
                <div class="pago-method-label">
                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    <span>QR</span>
                    <span class="pago-method-coin">$</span>
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                </div>
            </div>

            @if ($pagoConfirmado)
                <div class="pago-confirmed" role="status">
                    <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                    <strong>Pago confirmado</strong>
                    <p>La operación fue validada. El trámite puede continuar con su proceso.</p>
                </div>
            @else
                <div class="pago-qr-section">
                    <h3>Código QR-SIMPLE</h3>

                    <div class="pago-qr-layout">
                        <div class="pago-instructions">
                            <ol>
                                <li><span>1</span><p>Abre la aplicación móvil de tu entidad financiera.</p></li>
                                <li><span>2</span><p>Selecciona la opción para pagar mediante código QR.</p></li>
                                <li><span>3</span><p>Escanea el código y verifica que el importe sea correcto.</p></li>
                                <li><span>4</span><p>Confirma la operación desde tu aplicación bancaria.</p></li>
                            </ol>
                            <p class="pago-waiting-note">
                                Después de pagar, espera unos segundos para que el sistema confirme la operación.
                            </p>

                            @if ($mostrarQr)
                                <div class="pago-qr-actions">
                                    <a href="{{ $urlImagenQr }}" class="pago-action is-download"
                                        download="qr-tramite-{{ $certificado->id }}.jpg">
                                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                                        Descargar QR
                                    </a>
                                    <button type="button" class="pago-action is-share"
                                        data-compartir-qr
                                        data-url="{{ $urlImagenQr }}"
                                        data-nombre="qr-tramite-{{ $certificado->id }}.jpg">
                                        <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                                        Compartir QR
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="pago-qr-column">
                            <div id="contenedorQr" class="pago-qr-frame">
                                @if ($mostrarQr)
                                    <img src="{{ $urlImagenQr }}"
                                        alt="Código QR bancario del trámite {{ $certificado->id }}"
                                        width="320" height="320">
                                @else
                                    <div class="pago-qr-loading" data-qr-estado>
                                        <span class="pago-spinner" aria-hidden="true"></span>
                                        <strong>Generando código QR</strong>
                                        <p>Estamos preparando el código para este pago.</p>
                                    </div>
                                @endif
                            </div>

                            @if ($mostrarQr)
                                <button type="button" class="pago-new-qr"
                                    data-generar-nuevo-qr
                                    data-url="{{ route('pagos_openbcb_generar_qr', ['pago' => $pago->id]) }}"
                                    title="Se habilitará cuando venza el código QR actual"
                                    disabled>
                                    <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                                    Generar nuevo QR
                                </button>
                            @else
                                <button type="button" id="botonGenerarQr"
                                    data-url="{{ route('pagos_openbcb_generar_qr', ['pago' => $pago->id]) }}"
                                    data-auto-generar-qr="true"
                                    class="pago-action is-primary pago-retry" hidden>
                                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                    <span>Reintentar generación</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>

    <footer class="pago-modal-footer">
        <span>El trámite permanecerá pendiente hasta que el pago sea confirmado.</span>
    </footer>
</section>
