    {{-- Estilos compartidos de bandejas de tramite: cabecera, chips y tablas compactas. --}}
    <style>
        .solicitudes-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .solicitudes-panel-head {
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 16px 18px;
        }

        .solicitudes-panel-title {
            align-items: center;
            display: flex;
            gap: 11px;
        }

        .solicitudes-panel-icon {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .solicitudes-panel-icon.is-sent {
            background: #ecfeff;
            color: #0e7490;
        }

        .solicitudes-panel-icon.is-inbox {
            background: #ecfdf5;
            color: #047857;
        }

        .solicitudes-panel-title h2 {
            color: #0f172a;
            font-size: 17px;
            font-weight: 900;
            margin: 0;
        }

        .solicitudes-panel-title p {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin: 3px 0 0;
        }

        .solicitudes-panel-body {
            padding: 14px;
        }

        /* Tabla de solicitudes enviadas: pocas columnas y textos largos con salto de linea. */
        .solicitudes-panel-body.is-sent-table table {
            table-layout: fixed;
            width: 100%;
        }

        .solicitudes-panel-body.is-sent-table table th,
        .solicitudes-panel-body.is-sent-table table td {
            line-height: 1.35;
            vertical-align: top;
            white-space: normal !important;
            overflow-wrap: anywhere;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(1),
        .solicitudes-panel-body.is-sent-table table td:nth-child(1) {
            width: 54px;
            white-space: nowrap !important;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(2),
        .solicitudes-panel-body.is-sent-table table td:nth-child(2) {
            width: 130px;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(3),
        .solicitudes-panel-body.is-sent-table table td:nth-child(3) {
            width: 22%;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(4),
        .solicitudes-panel-body.is-sent-table table td:nth-child(4),
        .solicitudes-panel-body.is-sent-table table th:nth-child(5),
        .solicitudes-panel-body.is-sent-table table td:nth-child(5) {
            width: 17%;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(6),
        .solicitudes-panel-body.is-sent-table table td:nth-child(6) {
            width: 125px;
            white-space: nowrap !important;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(7),
        .solicitudes-panel-body.is-sent-table table td:nth-child(7) {
            width: 118px;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(8),
        .solicitudes-panel-body.is-sent-table table td:nth-child(8) {
            width: 150px;
        }

        /* Permite que los textos largos bajen de linea y evita una tabla demasiado ancha. */
        .solicitudes-panel-body.is-inbox-table table th,
        .solicitudes-panel-body.is-inbox-table table td {
            vertical-align: top;
        }

        .solicitudes-panel-body.is-inbox-table table {
            width: 100%;
        }

        .solicitudes-panel-body.is-inbox-table table td {
            line-height: 1.35;
            white-space: normal !important;
            overflow-wrap: anywhere;
        }

        /* Estas columnas son datos cortos o acciones; conviene mantenerlas en una sola linea. */
        .solicitudes-panel-body.is-inbox-table table th:nth-child(1),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(1),
        .solicitudes-panel-body.is-inbox-table table th:nth-child(6),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(6),
        .solicitudes-panel-body.is-inbox-table table th:nth-child(8),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(8),
        .solicitudes-panel-body.is-inbox-table table th:nth-child(9),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(9) {
            white-space: nowrap !important;
            overflow-wrap: normal;
        }

        /* Anchos maximos para que beneficiario, tramitador y etapas se acomoden por filas. */
        .solicitudes-panel-body.is-inbox-table table td:nth-child(3),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(4),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(5),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(7) {
            max-width: 240px;
        }

        .solicitudes-panel-badge {
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            padding: 5px 10px;
            white-space: nowrap;
        }

        .solicitudes-panel-badge.is-sent {
            background: #cffafe;
            color: #155e75;
        }

        .solicitudes-panel-badge.is-inbox {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
