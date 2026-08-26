<x-admin-layout
    title="Administración OpenBCB | Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'OpenBCB', 'href' => route('pagos_openbcb_usuario')],
        ['name' => 'Administración'],
    ]">

    @include('pagos.openbcb._estilos')

    @php
        // Lista de ejemplo para diseñar la bandeja. Cuando se conecte, saldrá de pagos y pagos_certificados.
        $pagos = [
            ['tramite' => 'TR-2026-00124', 'cliente' => 'Empresa Exportadora ABC S.A.', 'monto' => '150.00', 'estado' => 'QR_GENERADO'],
            ['tramite' => 'TR-2026-00125', 'cliente' => 'María López', 'monto' => '90.00', 'estado' => 'PROCESADO'],
            ['tramite' => 'TR-2026-00126', 'cliente' => 'Importadora Sur', 'monto' => '210.00', 'estado' => 'RECHAZADO'],
            ['tramite' => 'TR-2026-00127', 'cliente' => 'Juan Pérez', 'monto' => '120.00', 'estado' => 'NO_PROCESADO'],
        ];

        $estadoClase = fn(string $estado) => match ($estado) {
            'PROCESADO' => 'is-success',
            'RECHAZADO' => 'is-danger',
            'QR_GENERADO' => 'is-generated',
            default => 'is-pending',
        };
    @endphp

    <div class="bcb-page">
        <div class="bcb-head">
            <div>
                <h1 class="bcb-title">Pantallas para administración y finanzas</h1>
                <p class="bcb-subtitle">Aquí se controlan pagos generados, consultas de estado y validación financiera.</p>
            </div>

            <div class="bcb-mini-nav">
                <a href="{{ route('pagos_openbcb_usuario') }}">Usuario</a>
                <a href="{{ route('pagos_openbcb_administracion') }}" class="is-active">Administración</a>
                <a href="{{ route('pagos_openbcb_integracion') }}">Integración</a>
            </div>
        </div>

        <section class="bcb-card">
            <h2 class="bcb-card-title">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                Pagos OpenBCB
            </h2>

            <div class="bcb-actions" style="justify-content: flex-start;">
                <x-wire-button teal xs>Todos</x-wire-button>
                <x-wire-button blue xs>QR_GENERADO</x-wire-button>
                <x-wire-button green xs>PROCESADO</x-wire-button>
                <x-wire-button red xs>RECHAZADO</x-wire-button>
            </div>

            <div class="bcb-table-wrap">
                <table class="bcb-table">
                    <thead>
                        <tr>
                            <th>Trámite</th>
                            <th>Cliente</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pagos as $pago)
                            <tr>
                                <td>{{ $pago['tramite'] }}</td>
                                <td>{{ $pago['cliente'] }}</td>
                                <td>{{ $pago['monto'] }} BOB</td>
                                <td><span class="bcb-status {{ $estadoClase($pago['estado']) }}">{{ $pago['estado'] }}</span></td>
                                <td>
                                    <x-wire-button green xs>
                                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                        Ver
                                    </x-wire-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="bcb-grid-three">
            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                    Detalle del pago
                </h2>

                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">idQr</span><span class="bcb-value">10000121715970417000</span></div>
                    <div class="bcb-field"><span class="bcb-label">idOrdenDestinatario</span><span class="bcb-value">ORD-2026-000987</span></div>
                    <div class="bcb-field"><span class="bcb-label">Importe</span><span class="bcb-value">150.00 BOB</span></div>
                    <div class="bcb-field"><span class="bcb-label">Fecha pago</span><span class="bcb-value">Sin confirmar</span></div>
                    <div class="bcb-field"><span class="bcb-label">Estado OpenBCB</span><span class="bcb-value"><span class="bcb-status is-generated">QR_GENERADO</span></span></div>
                </div>
            </section>

            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    Consultar estado
                </h2>

                <div class="bcb-note">Esta acción usará GET /v1/qr/{idQR}. En esta maqueta todavía no se llama a OpenBCB.</div>

                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">Endpoint</span><span class="bcb-value">GET /v1/qr/{idQR}</span></div>
                    <div class="bcb-field"><span class="bcb-label">ordenes</span><span class="bcb-value">1 registro encontrado</span></div>
                    <div class="bcb-field"><span class="bcb-label">estado</span><span class="bcb-value"><span class="bcb-status is-success">PROCESADO</span></span></div>
                    <div class="bcb-field"><span class="bcb-label">fecha</span><span class="bcb-value">26/08/2026 10:32</span></div>
                </div>

                <x-wire-button blue>
                    <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                    Actualizar estado
                </x-wire-button>
            </section>

            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    Validación financiera
                </h2>

                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">Pago confirmado</span><span class="bcb-value"><span class="bcb-status is-success">PROCESADO</span></span></div>
                    <div class="bcb-field"><span class="bcb-label">Coincide monto</span><span class="bcb-value">Sí, 150.00 BOB</span></div>
                    <div class="bcb-field"><span class="bcb-label">Orden recibida</span><span class="bcb-value">ORD-2026-000987</span></div>
                </div>

                <div class="bcb-actions" style="justify-content: flex-start;">
                    <x-wire-button green>Aceptar pago</x-wire-button>
                    <x-wire-button amber>Observar pago</x-wire-button>
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
