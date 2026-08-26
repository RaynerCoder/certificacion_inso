<x-admin-layout
    title="Integración OpenBCB y VUCE | Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'OpenBCB', 'href' => route('pagos_openbcb_usuario')],
        ['name' => 'Integración'],
    ]">

    @include('pagos.openbcb._estilos')

    <div class="bcb-page">
        <div class="bcb-head">
            <div>
                <h1 class="bcb-title">Pantallas técnicas e integración VUCE</h1>
                <p class="bcb-subtitle">Estas vistas son internas. Sirven para revisar configuración, notificaciones, auditoría y los datos listos para VUCE.</p>
            </div>

            <div class="bcb-mini-nav">
                <a href="{{ route('pagos_openbcb_usuario') }}">Usuario</a>
                <a href="{{ route('pagos_openbcb_administracion') }}">Administración</a>
                <a href="{{ route('pagos_openbcb_integracion') }}" class="is-active">Integración</a>
            </div>
        </div>

        <div class="bcb-grid">
            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-gear" aria-hidden="true"></i>
                    Configuración OpenBCB
                </h2>

                {{-- Los secretos se muestran tapados porque esta pantalla puede abrirse en soporte o capacitación. --}}
                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">Base URL</span><span class="bcb-value">https://openbcb.pruebas.bcb.gob.bo/api</span></div>
                    <div class="bcb-field"><span class="bcb-label">X-Entity-Id</span><span class="bcb-value">••••••••</span></div>
                    <div class="bcb-field"><span class="bcb-label">Key ID</span><span class="bcb-value">••••••••-••••-••••-••••-••••••••</span></div>
                    <div class="bcb-field"><span class="bcb-label">Shared Secret</span><span class="bcb-value">••••••••••••••••</span></div>
                    <div class="bcb-field"><span class="bcb-label">Access Token</span><span class="bcb-value">••••••••••••••••</span></div>
                    <div class="bcb-field"><span class="bcb-label">Endpoint QR</span><span class="bcb-value">POST /v1/qr</span></div>
                    <div class="bcb-field"><span class="bcb-label">Endpoint Consulta</span><span class="bcb-value">GET /v1/qr/{idQR}</span></div>
                </div>

                <x-wire-button teal>
                    <i class="fa-solid fa-plug-circle-check" aria-hidden="true"></i>
                    Probar conexión
                </x-wire-button>
            </section>

            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-bell" aria-hidden="true"></i>
                    Webhook notificaciones
                </h2>

                <div class="bcb-note">OpenBCB llamará este endpoint cuando exista una novedad del pago. Primero se puede trabajar con consulta manual.</div>

                <div class="bcb-table-wrap">
                    <table class="bcb-table">
                        <thead>
                            <tr>
                                <th>idQR</th>
                                <th>Estado</th>
                                <th>Importe</th>
                                <th>Fecha</th>
                                <th>Procesado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>10000121715970417000</td>
                                <td><span class="bcb-status is-success">PROCESADO</span></td>
                                <td>150.00 BOB</td>
                                <td>26/08/2026 10:32</td>
                                <td>Sí</td>
                            </tr>
                            <tr>
                                <td>10000121715970417001</td>
                                <td><span class="bcb-status is-pending">NO_PROCESADO</span></td>
                                <td>90.00 BOB</td>
                                <td>26/08/2026 11:10</td>
                                <td>No</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                    Auditoría OpenBCB
                </h2>

                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">HTTP status</span><span class="bcb-value">200 OK</span></div>
                    <div class="bcb-field"><span class="bcb-label">Mensaje</span><span class="bcb-value">Operación ejecutada correctamente</span></div>
                    <div class="bcb-field"><span class="bcb-label">raw_response</span><span class="bcb-value">Respuesta guardada para soporte</span></div>
                    <div class="bcb-field"><span class="bcb-label">Última consulta</span><span class="bcb-value">26/08/2026 10:35</span></div>
                    <div class="bcb-field"><span class="bcb-label">Error firma</span><span class="bcb-value">Sin errores</span></div>
                </div>
            </section>

            <section class="bcb-card">
                <h2 class="bcb-card-title">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    Preparar envío a VUCE
                </h2>

                {{-- Este resumen junta solo lo que VUCE necesitaría como constancia del pago procesado. --}}
                <div class="bcb-field-list">
                    <div class="bcb-field"><span class="bcb-label">Trámite</span><span class="bcb-value">TR-2026-00124</span></div>
                    <div class="bcb-field"><span class="bcb-label">Certificado</span><span class="bcb-value">Certificado de Origen</span></div>
                    <div class="bcb-field"><span class="bcb-label">Pago</span><span class="bcb-value">PAGO-000124</span></div>
                    <div class="bcb-field"><span class="bcb-label">idQr</span><span class="bcb-value">10000121715970417000</span></div>
                    <div class="bcb-field"><span class="bcb-label">idOrdenDestinatario</span><span class="bcb-value">ORD-2026-000987</span></div>
                    <div class="bcb-field"><span class="bcb-label">Estado</span><span class="bcb-value"><span class="bcb-status is-success">PROCESADO</span></span></div>
                    <div class="bcb-field"><span class="bcb-label">Importe</span><span class="bcb-value">150.00 BOB</span></div>
                    <div class="bcb-field"><span class="bcb-label">Fecha pago</span><span class="bcb-value">26/08/2026 10:32</span></div>
                </div>

                <x-wire-button blue>
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    Enviar a VUCE
                </x-wire-button>
            </section>
        </div>

        <section class="bcb-card">
            <h2 class="bcb-card-title">
                <i class="fa-solid fa-database" aria-hidden="true"></i>
                Campos previstos en pagos
            </h2>

            <div class="bcb-table-wrap">
                <table class="bcb-table">
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Tipo sugerido</th>
                            <th>Uso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>openbcb_id_qr</td><td>string nullable</td><td>ID del QR devuelto por OpenBCB.</td></tr>
                        <tr><td>openbcb_imagen_qr</td><td>longText nullable</td><td>Imagen QR en base64 o ruta del archivo guardado.</td></tr>
                        <tr><td>openbcb_estado</td><td>string nullable</td><td>Estado actual: QR_GENERADO, PROCESADO, RECHAZADO o NO_PROCESADO.</td></tr>
                        <tr><td>openbcb_id_orden_destinatario</td><td>string nullable</td><td>Orden recibida cuando el pago se procesa.</td></tr>
                        <tr><td>openbcb_importe</td><td>decimal nullable</td><td>Monto enviado o confirmado por OpenBCB.</td></tr>
                        <tr><td>openbcb_cod_moneda</td><td>string nullable</td><td>Moneda usada en el pago, normalmente BOB.</td></tr>
                        <tr><td>openbcb_fecha_pago</td><td>datetime nullable</td><td>Fecha de confirmación del pago.</td></tr>
                        <tr><td>openbcb_metadata</td><td>json nullable</td><td>Datos internos enviados en metaData para relacionar trámite y pago.</td></tr>
                        <tr><td>openbcb_raw_response</td><td>json nullable</td><td>Respuesta completa útil para auditoría y soporte.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
