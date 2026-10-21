<x-admin-layout title="Pago del trámite | Certificador" :breadcrumbs="[
    ['name' => 'Mis trámites', 'href' => route('seguimientos_mis_tramites_beneficiario')],
    ['name' => 'Trámite ' . $certificado->id, 'href' => route('certificados_show', ['certificado' => $certificado, 'bandeja' => 'enviadas'])],
    ['name' => 'Pago'],
]">
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
        $qrVigente = $tieneImagenQr && $ultimoQrOpenBcb?->created_at?->copy()->addDay()->isFuture();
        $mostrarQr = $qrVigente && !$pagoConfirmado;
        $urlImagenQr = $tieneImagenQr
            ? route('pagos_openbcb_mostrar_imagen_qr', ['pagoOpenBcb' => $ultimoQrOpenBcb->id], false)
            : null;
    @endphp
    @include('seguimientos_certificados.nuevo_tramite.pagos.estilos')

    <div class="pago-tramite-page">
        <header class="pago-tramite-head">
            <div>
                <span class="pago-tramite-eyebrow">Mis trámites · Pago</span>
                <h1>Pago del trámite {{ $certificado->id }}</h1>
                <p>{{ $pagoConfirmado ? 'Consulta el resumen del pago confirmado de tu solicitud.' : 'Revisa el importe y utiliza tu aplicación bancaria para pagar con QR.' }}</p>
            </div>
            <a href="{{ route('seguimientos_mis_tramites_beneficiario') }}" class="pago-tramite-back">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver
            </a>
        </header>

        <div class="pago-tramite-grid">
            <section class="pago-tramite-card" aria-labelledby="resumenPagoTitulo">
                <div class="pago-tramite-card-title">
                    <span class="pago-tramite-icon"><i class="fa-solid fa-file-invoice" aria-hidden="true"></i></span>
                    <div><h2 id="resumenPagoTitulo">Resumen del pago</h2><p>Información de tu solicitud</p></div>
                </div>
                <dl class="pago-tramite-data">
                    <div><dt>Número de trámite</dt><dd>{{ $certificado->id }}</dd></div>
                    <div><dt>Tipo de trámite</dt><dd>{{ $tipoCertificado?->nombre ?? 'Sin información' }}</dd></div>
                    <div><dt>Beneficiario</dt><dd>{{ $nombreBeneficiario ?: 'Sin información' }}</dd></div>
                    <div><dt>Forma de pago</dt><dd>QR bancario</dd></div>
                    <div><dt>Estado del pago</dt><dd><span class="pago-tramite-status {{ $pagoConfirmado ? 'is-confirmed' : '' }}">{{ $pagoConfirmado ? 'Pago confirmado' : 'Pendiente de confirmación' }}</span></dd></div>
                </dl>
                <div class="pago-tramite-amount">
                    <span>Total a pagar (bolivianos)</span>
                    <strong><small>Bs</small> {{ number_format((float) $pago->monto, 2, ',', '.') }}</strong>
                </div>
                @unless ($pagoConfirmado)
                <div class="pago-tramite-help">
                    <h3>Cómo realizar el pago</h3>
                    <ol>
                        <li>Comprueba el beneficiario y el importe de esta solicitud.</li>
                        <li>Abre tu aplicación bancaria y escanea el QR. Si usas el mismo teléfono, descarga la imagen e impórtala en tu aplicación.</li>
                        <li>Confirma el importe en tu banco antes de completar el pago.</li>
                    </ol>
                </div>
                @endunless
            </section>

            <section class="pago-tramite-card pago-tramite-qr-card" aria-labelledby="pagoQrTitulo">
                <div class="pago-tramite-card-title">
                    <span class="pago-tramite-icon"><i class="fa-solid fa-qrcode" aria-hidden="true"></i></span>
                    <div><h2 id="pagoQrTitulo">{{ $pagoConfirmado ? 'Pago confirmado' : 'Paga con QR' }}</h2><p>{{ $pagoConfirmado ? 'El pago ya fue validado.' : 'Escanea el código desde tu aplicación bancaria.' }}</p></div>
                </div>
                <div class="pago-tramite-qr-body">
                    @if ($pagoConfirmado)
                        <div class="pago-tramite-confirmed" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><strong>El pago está confirmado</strong><p>No necesitas realizar otro pago para esta solicitud.</p></div>
                    @else
                        <div id="contenedorQr" class="pago-tramite-qr">
                            @if ($mostrarQr)
                                <img src="{{ $urlImagenQr }}" alt="Código QR bancario del trámite {{ $certificado->id }}" width="360" height="360">
                            @else
                                <div class="pago-tramite-qr-empty"><i class="fa-solid fa-qrcode" aria-hidden="true"></i><strong>{{ $tieneImagenQr ? 'El QR anterior venció' : 'Tu QR aparecerá aquí' }}</strong><p>{{ $tieneImagenQr ? 'Genera uno nuevo para continuar con el pago.' : 'Presiona «Generar código QR» para continuar.' }}</p></div>
                            @endif
                        </div>
                        @if ($mostrarQr)
                            <div class="pago-tramite-qr-actions">
                                <a href="{{ $urlImagenQr }}" class="pago-tramite-secondary" target="_blank" rel="noopener"><i class="fa-solid fa-expand" aria-hidden="true"></i> Ver QR grande</a>
                                <a href="{{ $urlImagenQr }}" class="pago-tramite-secondary" download="qr-tramite-{{ $certificado->id }}.png"><i class="fa-solid fa-download" aria-hidden="true"></i> Descargar QR</a>
                            </div>
                        @else
                            <button type="button" id="botonGenerarQr" data-url="{{ route('pagos_openbcb_generar_qr', ['pago' => $pago->id]) }}" class="pago-tramite-primary"><i class="fa-solid fa-qrcode" aria-hidden="true"></i><span>Generar código QR</span></button>
                        @endif
                        <div class="pago-tramite-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><p>Generar un QR no confirma el pago. El estado cambiará cuando se registre su validación.</p></div>
                        <a href="{{ request()->fullUrl() }}" class="pago-tramite-refresh"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Actualizar estado del pago</a>
                    @endif
                </div>
            </section>
        </div>
    </div>
    @include('seguimientos_certificados.nuevo_tramite.pagos.script')
</x-admin-layout>
