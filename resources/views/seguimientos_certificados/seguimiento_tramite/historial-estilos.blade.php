{{-- Estilos de la pantalla contenedora del historial de seguimiento. --}}
    <style>
        .timeline-page {
            background: #f8fafc;
            border-radius: 10px;
            padding: 14px;
        }

        .timeline-page-head {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .timeline-page-title {
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 950;
            line-height: 1.15;
        }

        .timeline-page-description {
            color: #64748b;
            font-size: 0.86rem;
            font-weight: 700;
            margin-top: 4px;
        }

        .timeline-page-action {
            align-items: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            color: #334155;
            display: inline-flex;
            font-size: 0.82rem;
            font-weight: 900;
            gap: 8px;
            min-height: 38px;
            padding: 0 13px;
            white-space: nowrap;
        }

        .timeline-page-action:hover {
            border-color: #059669;
            color: #047857;
        }

        @media (max-width: 760px) {
            .timeline-page-head {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
