<x-admin-layout
    title="Pantallas usuario OpenBCB | Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'OpenBCB', 'href' => route('pagos_openbcb_usuario')],
        ['name' => 'Pantallas usuario'],
    ]">

    @include('pagos.openbcb._estilos')

    @php
        // Estos datos son de maqueta. Luego vendrán desde certificados, pagos y la respuesta real de OpenBCB.
        $tramites = [
            ['codigo' => 'TR-2026-00124', 'certificado' => 'Certificado de Origen', 'estado' => 'Pendiente de pago', 'pago' => 'PENDIENTE'],
            ['codigo' => 'TR-2026-00125', 'certificado' => 'Certificado Fitosanitario', 'estado' => 'QR generado', 'pago' => 'QR_GENERADO'],
            ['codigo' => 'TR-2026-00126', 'certificado' => 'Certificado de Libre Venta', 'estado' => 'Pagado', 'pago' => 'PROCESADO'],
        ];
    @endphp

    <div class="bcb-page">
        <div class="bcb-head">
            <div>
                <h1 class="bcb-title">Pantallas para el usuario</h1>
                <p class="bcb-subtitle">El usuario solo ve el pago, el QR y la confirmación. La firma OpenBCB queda escondida en el sistema.</p>
            </div>

            <div class="bcb-mini-nav">
                <a href="{{ route('pagos_openbcb_usuario') }}" class="is-active">Usuario</a>
                <a href="{{ route('pagos_openbcb_administracion') }}">Administración</a>
                <a href="{{ route('pagos_openbcb_integracion') }}">Integración</a>
            </div>
        </div>

        <section class="bcb-card">
            <h2 class="bcb-card-title">
                <i class="fa-solid fa-route" aria-hidden="true"></i>
                Recorrido visible
            </h2>

            <div class="bcb-steps">
                <div class="bcb-step">
                    <span class="bcb-step-number">1</span>
                    <strong>Mis trámites</strong>
                    <span>El usuario entra a su bandeja y encuentra los trámites con pago pendiente.</span>
                </div>
                <div class="bcb-step">
                    <span class="bcb-step-number">2</span>
                    <strong>Detalle de pago</strong>
                    <span>Revisa monto, moneda, concepto y fecha de vencimiento antes de generar QR.</span>
                </div>
                <div class="bcb-step">
                    <span class="bcb-step-number">3</span>
                    <strong>Pagar con QR</strong>
                    <span>Escanea el QR desde su aplicación bancaria y puede consultar el estado.</span>
                </div>
                <div class="bcb-step">
                    <span class="bcb-step-number">4</span>
                    <strong>Pago confirmado</strong>
                    <span>Cuando OpenBCB devuelve PROCESADO, el trámite puede continuar.</span>
                </div>
            </div>
        </section>

        <div class="bcb-grid">
            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                    Mis trámites
                </h2>

                <div class="bcb-table-wrap">
                    <table class="bcb-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Certificado</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tramites as $tramite)
                                <tr>
                                    <td>{{ $tramite['codigo'] }}</td>
                                    <td>{{ $tramite['certificado'] }}</td>
                                    <td>
                                        <span class="bcb-status {{ $tramite['pago'] === 'PROCESADO' ? 'is-success' : ($tramite['pago'] === 'QR_GENERADO' ? 'is-generated' : 'is-pending') }}">
                                            {{ $tramite['estado'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <x-wire-button teal xs>
                                            {{ $tramite['pago'] === 'PROCESADO' ? 'Ver' : 'Pagar' }}
                                        </x-wire-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                    Detalle de pago
                </h2>

                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">Trámite</span><span class="bcb-value">TR-2026-00124</span></div>
                    <div class="bcb-field"><span class="bcb-label">Certificado</span><span class="bcb-value">Certificado de Origen</span></div>
                    <div class="bcb-field"><span class="bcb-label">Cliente</span><span class="bcb-value">Empresa Exportadora ABC S.A.</span></div>
                    <div class="bcb-field"><span class="bcb-label">Monto</span><span class="bcb-value">150.00 Bs.</span></div>
                    <div class="bcb-field"><span class="bcb-label">Moneda</span><span class="bcb-value">BOB</span></div>
                    <div class="bcb-field"><span class="bcb-label">Glosa</span><span class="bcb-value">Pago por emisión de certificado</span></div>
                    <div class="bcb-field"><span class="bcb-label">Fecha vencimiento</span><span class="bcb-value">27/08/2026 23:59</span></div>
                </div>

                <x-wire-button teal>
                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    Generar QR
                </x-wire-button>
            </section>

            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    Pagar con QR
                </h2>

                <div class="bcb-qr-box" aria-label="QR de ejemplo">
                    <span>QR</span>
                </div>

                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">Estado</span><span class="bcb-value"><span class="bcb-status is-generated">QR_GENERADO</span></span></div>
                    <div class="bcb-field"><span class="bcb-label">Monto</span><span class="bcb-value">150.00 BOB</span></div>
                    <div class="bcb-field"><span class="bcb-label">Vence</span><span class="bcb-value">27/08/2026 23:59</span></div>
                </div>

                <div class="bcb-actions">
                    <x-wire-button secondary>
                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                        Descargar QR
                    </x-wire-button>
                    <x-wire-button blue>
                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                        Consultar pago
                    </x-wire-button>
                </div>
            </section>

            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    Pago confirmado
                </h2>

                <div class="bcb-note">Esta vista aparece cuando la consulta o la notificación de OpenBCB confirma el estado PROCESADO.</div>

                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">Estado</span><span class="bcb-value"><span class="bcb-status is-success">PROCESADO</span></span></div>
                    <div class="bcb-field"><span class="bcb-label">Fecha de pago</span><span class="bcb-value">26/08/2026 10:32</span></div>
                    <div class="bcb-field"><span class="bcb-label">Importe</span><span class="bcb-value">150.00 BOB</span></div>
                    <div class="bcb-field"><span class="bcb-label">Nro. orden</span><span class="bcb-value">ORD-2026-000987</span></div>
                </div>

                <x-wire-button green>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    Continuar trámite
                </x-wire-button>
            </section>
        </div>
    </div>
</x-admin-layout>
