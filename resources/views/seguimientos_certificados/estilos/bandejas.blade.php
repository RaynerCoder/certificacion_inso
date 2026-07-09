    {{-- Estilos compartidos para las bandejas de trámites. --}}
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
            overflow-x: auto;
            padding: 14px;
            -webkit-overflow-scrolling: touch;
        }

        .solicitudes-panel-body table {
            width: 100%;
        }

        .solicitudes-panel-body table th,
        .solicitudes-panel-body table td {
            line-height: 1.35;
            vertical-align: top;
            white-space: normal !important;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .solicitudes-panel-body table th {
            white-space: nowrap !important;
        }

        .solicitudes-panel-body table th:first-child,
        .solicitudes-panel-body table td:first-child {
            min-width: 64px;
            white-space: nowrap !important;
        }

        .solicitudes-panel-body table td:last-child {
            white-space: nowrap !important;
        }

        .solicitudes-panel-body.is-sent-table table {
            min-width: 1040px;
        }

        .solicitudes-panel-body.is-inbox-table table {
            min-width: 1180px;
        }

        .solicitudes-panel-body.is-follow-table table {
            min-width: 1120px;
        }

        .solicitudes-panel-body.is-final-table table {
            min-width: 1280px;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(2),
        .solicitudes-panel-body.is-sent-table table td:nth-child(2),
        .solicitudes-panel-body.is-follow-table table th:nth-child(2),
        .solicitudes-panel-body.is-follow-table table td:nth-child(2),
        .solicitudes-panel-body.is-final-table table th:nth-child(2),
        .solicitudes-panel-body.is-final-table table td:nth-child(2) {
            min-width: 140px;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(3),
        .solicitudes-panel-body.is-sent-table table td:nth-child(3),
        .solicitudes-panel-body.is-follow-table table th:nth-child(3),
        .solicitudes-panel-body.is-follow-table table td:nth-child(3),
        .solicitudes-panel-body.is-final-table table th:nth-child(3),
        .solicitudes-panel-body.is-final-table table td:nth-child(3) {
            min-width: 220px;
        }

        .solicitudes-panel-body.is-sent-table table th:nth-child(4),
        .solicitudes-panel-body.is-sent-table table td:nth-child(4),
        .solicitudes-panel-body.is-sent-table table th:nth-child(5),
        .solicitudes-panel-body.is-sent-table table td:nth-child(5),
        .solicitudes-panel-body.is-follow-table table th:nth-child(4),
        .solicitudes-panel-body.is-follow-table table td:nth-child(4),
        .solicitudes-panel-body.is-follow-table table th:nth-child(5),
        .solicitudes-panel-body.is-follow-table table td:nth-child(5),
        .solicitudes-panel-body.is-final-table table th:nth-child(4),
        .solicitudes-panel-body.is-final-table table td:nth-child(4),
        .solicitudes-panel-body.is-final-table table th:nth-child(5),
        .solicitudes-panel-body.is-final-table table td:nth-child(5) {
            min-width: 180px;
        }

        .solicitudes-panel-body.is-inbox-table table th:nth-child(2),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(2) {
            min-width: 140px;
        }

        .solicitudes-panel-body.is-inbox-table table th:nth-child(3),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(3) {
            min-width: 220px;
        }

        .solicitudes-panel-body.is-inbox-table table th:nth-child(4),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(4),
        .solicitudes-panel-body.is-inbox-table table th:nth-child(5),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(5) {
            min-width: 180px;
        }

        .solicitudes-panel-body.is-inbox-table table th:nth-child(7),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(7),
        .solicitudes-panel-body.is-inbox-table table th:nth-child(8),
        .solicitudes-panel-body.is-inbox-table table td:nth-child(8) {
            min-width: 160px;
        }

        .solicitudes-panel-body.is-follow-table table th:nth-child(6),
        .solicitudes-panel-body.is-follow-table table td:nth-child(6),
        .solicitudes-panel-body.is-follow-table table th:nth-child(7),
        .solicitudes-panel-body.is-follow-table table td:nth-child(7),
        .solicitudes-panel-body.is-final-table table th:nth-child(6),
        .solicitudes-panel-body.is-final-table table td:nth-child(6),
        .solicitudes-panel-body.is-final-table table th:nth-child(7),
        .solicitudes-panel-body.is-final-table table td:nth-child(7) {
            min-width: 170px;
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

        @media (max-width: 768px) {
            .solicitudes-panel-body {
                padding: 10px;
            }
        }
    </style>