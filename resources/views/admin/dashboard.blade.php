<x-admin-layout
    title="Menu | Sistema Certificador"
    :breadcrumbs="[
        [
            'name' => 'Menu',
            'href' => route('admin_dashboard'),
        ],
        [
            'name' => 'Resumen',
        ]
    ]">

    {{-- Estilos del dashboard: aplica la paleta Verde Sanitario + Grafito. --}}
    <style>
        .dashboard-certificador {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .dashboard-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            padding: 22px;
            box-shadow: 0 1px 2px rgba(31, 41, 55, 0.06);
        }

        .dashboard-eyebrow {
            margin: 0 0 6px;
            color: #059669;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .dashboard-title {
            margin: 0;
            color: #1f2937;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .dashboard-subtitle {
            max-width: 720px;
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.55;
        }

        .dashboard-status {
            align-self: start;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            background: #d1fae5;
            padding: 14px;
        }

        .dashboard-status strong {
            display: block;
            color: #065f46;
            font-size: 14px;
            font-weight: 900;
        }

        .dashboard-status span {
            display: block;
            margin-top: 4px;
            color: #047857;
            font-size: 12px;
            line-height: 1.35;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .dashboard-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            padding: 16px;
            box-shadow: 0 1px 2px rgba(31, 41, 55, 0.05);
        }

        .dashboard-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .dashboard-card-icon {
            display: inline-flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #d1fae5;
            color: #047857;
            font-size: 16px;
        }

        .dashboard-card small {
            color: #6b7280;
            font-size: 12px;
            font-weight: 800;
        }

        .dashboard-card-value {
            margin: 14px 0 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 900;
        }

        .dashboard-card-label {
            margin: 2px 0 0;
            color: #6b7280;
            font-size: 13px;
        }

        .dashboard-sections {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 16px;
        }

        .dashboard-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            padding: 18px;
            box-shadow: 0 1px 2px rgba(31, 41, 55, 0.05);
        }

        .dashboard-panel-title {
            margin: 0;
            color: #1f2937;
            font-size: 16px;
            font-weight: 900;
        }

        .dashboard-panel-subtitle {
            margin: 4px 0 14px;
            color: #6b7280;
            font-size: 12px;
        }

        .dashboard-module-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .dashboard-module {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            padding: 12px;
        }

        .dashboard-module i {
            display: inline-flex;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #1f2937;
            color: #d1fae5;
            font-size: 13px;
        }

        .dashboard-module strong {
            display: block;
            color: #1f2937;
            font-size: 13px;
            font-weight: 900;
        }

        .dashboard-module span {
            display: block;
            margin-top: 2px;
            color: #6b7280;
            font-size: 11px;
        }

        .dashboard-timeline {
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .dashboard-timeline-item {
            border-left: 3px solid #059669;
            border-radius: 8px;
            background: #f9fafb;
            padding: 10px 12px;
        }

        .dashboard-timeline-item strong {
            display: block;
            color: #1f2937;
            font-size: 13px;
            font-weight: 900;
        }

        .dashboard-timeline-item span {
            display: block;
            margin-top: 3px;
            color: #6b7280;
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .dashboard-grid,
            .dashboard-sections,
            .dashboard-hero {
                grid-template-columns: 1fr;
            }

            .dashboard-module-list {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section class="dashboard-certificador">
        {{-- Encabezado principal del menu/dashboard. --}}
        <div class="dashboard-hero">
            <div>
                <p class="dashboard-eyebrow">Sistema certificador</p>
                <h1 class="dashboard-title">Menu principal</h1>
                <p class="dashboard-subtitle">
                    Acceda rapidamente a personas, importadores, productos, certificados, pagos y tramite seguimientos.
                    La paleta Verde Sanitario + Grafito refuerza el enfoque tecnico, sanitario y administrativo.
                </p>
            </div>

            <div class="dashboard-status">
                <strong>Estado del sistema</strong>
                <span>Modulos organizados para registrar, evaluar y certificar informacion.</span>
            </div>
        </div>

        {{-- Tarjetas rapidas: resumen visual de los procesos principales. --}}
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="dashboard-card-head">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-users"></i></span>
                    <small>Personas</small>
                </div>
                <p class="dashboard-card-value">--</p>
                <p class="dashboard-card-label">Registros disponibles</p>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-head">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <small>Productos</small>
                </div>
                <p class="dashboard-card-value">--</p>
                <p class="dashboard-card-label">Productos y fabricantes</p>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-head">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-file-signature"></i></span>
                    <small>Certificados</small>
                </div>
                <p class="dashboard-card-value">--</p>
                <p class="dashboard-card-label">Solicitudes y emitidos</p>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-head">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-route"></i></span>
                    <small>Tramite seguimientos</small>
                </div>
                <p class="dashboard-card-value">--</p>
                <p class="dashboard-card-label">Trazabilidad de tramites</p>
            </div>
        </div>

        <div class="dashboard-sections">
            {{-- Accesos modulares: ayudan al usuario a identificar cada area. --}}
            <div class="dashboard-panel">
                <h2 class="dashboard-panel-title">Modulos principales</h2>
                <p class="dashboard-panel-subtitle">Accesos visuales con iconos y color sanitario.</p>

                <div class="dashboard-module-list">
                    <div class="dashboard-module">
                        <i class="fa-solid fa-user-plus"></i>
                        <div>
                            <strong>Registrar persona</strong>
                            <span>Natural, empresa y responsables</span>
                        </div>
                    </div>

                    <div class="dashboard-module">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                        <div>
                            <strong>Importadores</strong>
                            <span>Solicitudes y aprobaciones</span>
                        </div>
                    </div>

                    <div class="dashboard-module">
                        <i class="fa-solid fa-flask"></i>
                        <div>
                            <strong>Productos</strong>
                            <span>Ingredientes y presentaciones</span>
                        </div>
                    </div>

                    <div class="dashboard-module">
                        <i class="fa-solid fa-qrcode"></i>
                        <div>
                            <strong>Verificacion QR</strong>
                            <span>Consulta de certificados</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Panel de actividad: preparado para conectar datos reales despues. --}}
            <div class="dashboard-panel">
                <h2 class="dashboard-panel-title">Actividad reciente</h2>
                <p class="dashboard-panel-subtitle">Espacio listo para mostrar eventos del sistema.</p>

                <div class="dashboard-timeline">
                    <div class="dashboard-timeline-item">
                        <strong>Solicitudes</strong>
                        <span>Pendiente de conectar con datos reales.</span>
                    </div>

                    <div class="dashboard-timeline-item">
                        <strong>Certificados</strong>
                        <span>Resumen de emitidos y observados.</span>
                    </div>

                    <div class="dashboard-timeline-item">
                        <strong>Pagos</strong>
                        <span>Control de comprobantes y validacion.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-admin-layout>
