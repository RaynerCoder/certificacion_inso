<style>
    .reporte-page {
        display: grid;
        gap: 16px;
    }

    .reporte-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .reporte-title {
        color: #0f172a;
        font-size: 24px;
        font-weight: 900;
        line-height: 1.15;
    }

    .reporte-subtitle {
        margin-top: 4px;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .reporte-filtros {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr)) auto;
        gap: 12px;
        align-items: end;
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        background: #ffffff;
        padding: 14px;
    }

    .reporte-field label {
        display: block;
        margin-bottom: 6px;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .reporte-field input,
    .reporte-field select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #0f172a;
        font-size: 13px;
        font-weight: 600;
        padding: 9px 10px;
    }

    .reporte-filter-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 39px;
        border-radius: 8px;
        background: #059669;
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
        padding: 9px 16px;
        transition: background 160ms ease;
    }

    .reporte-filter-button:hover {
        background: #047857;
    }

    .reporte-kpis {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
    }

    .reporte-card {
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        background: #ffffff;
        padding: 14px;
    }

    .reporte-kpi-label {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .reporte-kpi-value {
        margin-top: 8px;
        color: #0f172a;
        font-size: 26px;
        font-weight: 950;
        line-height: 1;
    }

    .reporte-kpi-detail {
        margin-top: 6px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .reporte-grid-main {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(280px, .8fr);
        gap: 14px;
    }

    .reporte-grid-bottom {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .reporte-card-title {
        color: #0f172a;
        font-size: 15px;
        font-weight: 900;
    }

    .reporte-card-note {
        margin-top: 3px;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .reporte-table-wrap {
        margin-top: 12px;
        overflow-x: auto;
    }

    .reporte-table {
        width: 100%;
        min-width: 680px;
        border-collapse: collapse;
    }

    .reporte-table th {
        border-bottom: 1px solid #e2e8f0;
        color: #475569;
        font-size: 11px;
        font-weight: 900;
        padding: 9px 8px;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .reporte-table td {
        border-bottom: 1px solid #edf2f7;
        color: #0f172a;
        font-size: 13px;
        font-weight: 650;
        padding: 10px 8px;
        vertical-align: middle;
    }

    .reporte-bar-cell {
        display: grid;
        gap: 5px;
    }

    .reporte-bar-track {
        height: 8px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .reporte-bar-fill {
        height: 100%;
        border-radius: inherit;
        background: #059669;
    }

    .reporte-side-stack {
        display: grid;
        gap: 14px;
    }

    .reporte-big-metric {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .reporte-icon {
        display: inline-flex;
        width: 42px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #d1fae5;
        color: #047857;
        font-size: 18px;
    }

    .reporte-mini-title {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
    }

    .reporte-mini-value {
        margin-top: 4px;
        color: #0f172a;
        font-size: 18px;
        font-weight: 950;
        line-height: 1.2;
    }

    .reporte-chart-list {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }

    .reporte-chart-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 48px;
        gap: 10px;
        align-items: center;
    }

    .reporte-chart-label {
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .reporte-chart-value {
        color: #0f172a;
        font-size: 12px;
        font-weight: 900;
        text-align: right;
    }

    .reporte-donut {
        width: 150px;
        height: 150px;
        margin: 14px auto 0;
        border-radius: 999px;
        background: conic-gradient(#059669 0 45%, #2563eb 45% 70%, #f59e0b 70% 88%, #ef4444 88% 100%);
        display: grid;
        place-items: center;
    }

    .reporte-donut-center {
        display: grid;
        width: 92px;
        height: 92px;
        place-items: center;
        border-radius: 999px;
        background: #ffffff;
        color: #0f172a;
        font-size: 22px;
        font-weight: 950;
        line-height: 1;
    }

    .reporte-empty {
        margin-top: 14px;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        padding: 14px;
        text-align: center;
    }

    .reporte-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #e2e8f0;
        color: #334155;
        font-size: 11px;
        font-weight: 900;
        line-height: 1;
        padding: 6px 9px;
        white-space: nowrap;
    }

    .reporte-chip.verde {
        background: #d1fae5;
        color: #047857;
    }

    .reporte-chip.ambar {
        background: #fef3c7;
        color: #b45309;
    }

    .reporte-chip.rojo {
        background: #fee2e2;
        color: #b91c1c;
    }

    .reporte-chip.azul {
        background: #dbeafe;
        color: #1d4ed8;
    }

    @media (max-width: 1180px) {
        .reporte-filtros,
        .reporte-kpis,
        .reporte-grid-bottom {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .reporte-grid-main {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .reporte-header,
        .reporte-filtros,
        .reporte-kpis,
        .reporte-grid-bottom {
            grid-template-columns: 1fr;
            display: grid;
        }
    }
</style>
