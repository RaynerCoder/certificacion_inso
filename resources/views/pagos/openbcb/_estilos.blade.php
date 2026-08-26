<style>
    .bcb-page {
        color: #172033;
        display: grid;
        gap: 16px;
    }

    .bcb-head {
        align-items: flex-start;
        display: flex;
        gap: 14px;
        justify-content: space-between;
    }

    .bcb-title {
        color: #0f172a;
        font-size: 24px;
        font-weight: 900;
        margin: 0;
    }

    .bcb-subtitle {
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        margin: 4px 0 0;
    }

    .bcb-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .bcb-card,
    .bcb-panel {
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, .05);
    }

    .bcb-card {
        display: grid;
        gap: 14px;
        min-width: 0;
        padding: 16px;
    }

    .bcb-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .bcb-grid-three {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .bcb-card-title {
        align-items: center;
        color: #172033;
        display: flex;
        font-size: 16px;
        font-weight: 900;
        gap: 8px;
        margin: 0;
    }

    .bcb-card-title i {
        color: #0f766e;
        width: 18px;
    }

    .bcb-note {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        color: #1e3a8a;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
        padding: 10px 12px;
    }

    .bcb-field-list {
        display: grid;
        gap: 8px;
    }

    .bcb-field {
        align-items: center;
        border-bottom: 1px solid #edf1f6;
        display: grid;
        gap: 12px;
        grid-template-columns: minmax(145px, .7fr) minmax(0, 1.3fr);
        min-height: 34px;
        padding-bottom: 8px;
    }

    .bcb-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
    }

    .bcb-value {
        color: #172033;
        font-size: 13px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .bcb-input,
    .bcb-select,
    .bcb-textarea {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        color: #172033;
        font-size: 13px;
        font-weight: 700;
        min-height: 38px;
        padding: 8px 10px;
        width: 100%;
    }

    .bcb-textarea {
        min-height: 72px;
        resize: vertical;
    }

    .bcb-table-wrap {
        overflow-x: auto;
        width: 100%;
    }

    .bcb-table {
        border-collapse: collapse;
        min-width: 760px;
        width: 100%;
    }

    .bcb-table th,
    .bcb-table td {
        border-bottom: 1px solid #e2e8f0;
        font-size: 12px;
        padding: 10px;
        text-align: left;
        vertical-align: middle;
    }

    .bcb-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 900;
    }

    .bcb-table td {
        color: #172033;
        font-weight: 650;
    }

    .bcb-status {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 11px;
        font-weight: 900;
        gap: 6px;
        padding: 5px 9px;
        white-space: nowrap;
    }

    .bcb-status.is-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .bcb-status.is-generated {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .bcb-status.is-success {
        background: #dcfce7;
        color: #166534;
    }

    .bcb-status.is-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .bcb-steps {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .bcb-step {
        background: #f8fafc;
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        min-width: 0;
        padding: 12px;
    }

    .bcb-step-number {
        align-items: center;
        background: #0f766e;
        border-radius: 999px;
        color: #fff;
        display: inline-flex;
        font-size: 12px;
        font-weight: 900;
        height: 26px;
        justify-content: center;
        width: 26px;
    }

    .bcb-step strong {
        color: #172033;
        display: block;
        font-size: 13px;
        margin-top: 8px;
    }

    .bcb-step span {
        color: #64748b;
        display: block;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
        margin-top: 3px;
    }

    .bcb-qr-box {
        align-items: center;
        background:
            linear-gradient(90deg, #111827 12px, transparent 12px) 0 0 / 32px 32px,
            linear-gradient(#111827 12px, transparent 12px) 0 0 / 32px 32px,
            #fff;
        border: 10px solid #fff;
        box-shadow: inset 0 0 0 1px #cbd5e1;
        display: flex;
        height: 220px;
        justify-content: center;
        margin: 0 auto;
        max-width: 220px;
        width: 100%;
    }

    .bcb-qr-box span {
        background: rgba(255, 255, 255, .92);
        border-radius: 6px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 900;
        padding: 6px 8px;
    }

    .bcb-mini-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .bcb-mini-nav a {
        border: 1px solid #cbd5e1;
        border-radius: 999px;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
        padding: 7px 11px;
    }

    .bcb-mini-nav a.is-active {
        background: #0f766e;
        border-color: #0f766e;
        color: #fff;
    }

    @media (max-width: 1100px) {
        .bcb-grid,
        .bcb-grid-three {
            grid-template-columns: 1fr;
        }

        .bcb-steps {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .bcb-head {
            display: grid;
        }

        .bcb-actions {
            justify-content: flex-start;
        }

        .bcb-field {
            grid-template-columns: 1fr;
            gap: 2px;
        }

        .bcb-steps {
            grid-template-columns: 1fr;
        }
    }
</style>
