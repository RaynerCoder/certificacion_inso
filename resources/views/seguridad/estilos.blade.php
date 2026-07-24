<style>
    .seg-page {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .seg-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .seg-section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #0d9488;
        color: #ffffff;
    }

    .seg-section-icon {
        width: 34px;
        height: 34px;
        flex: 0 0 auto;
        font-size: 14px;
    }

    .seg-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 8px 8px 0 0;
        background: #f8fafc;
        padding: 13px 16px;
    }

    .seg-card.is-blue .seg-card-head {
        border-bottom-color: #bfdbfe;
        background: linear-gradient(90deg, #eff6ff, #f8fafc);
    }

    .seg-card.is-emerald .seg-card-head {
        border-bottom-color: #bbf7d0;
        background: linear-gradient(90deg, #ecfdf5, #f8fafc);
    }

    .seg-card.is-violet .seg-card-head {
        border-bottom-color: #ddd6fe;
        background: linear-gradient(90deg, #f5f3ff, #f8fafc);
    }

    .seg-card.is-blue .seg-section-icon {
        background: #2563eb;
    }

    .seg-card.is-emerald .seg-section-icon {
        background: #059669;
    }

    .seg-card.is-violet .seg-section-icon {
        background: #7c3aed;
    }

    .seg-card-title {
        margin: 0;
        color: #0f172a;
        font-size: 15px;
        font-weight: 900;
    }

    .seg-card-subtitle {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .seg-card-body {
        padding: 16px;
    }

    .seg-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 16px;
    }

    .seg-col-3 { grid-column: span 3 / span 3; }
    .seg-col-4 { grid-column: span 4 / span 4; }
    .seg-col-6 { grid-column: span 6 / span 6; }
    .seg-col-12 { grid-column: span 12 / span 12; }

    .seg-check-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .seg-check-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-height: 44px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 10px 12px;
        transition: border-color 160ms ease, background 160ms ease;
    }

    .seg-check-item:hover {
        border-color: #99f6e4;
        background: #f8fafc;
    }

    .seg-check-item input {
        margin-top: 3px;
        width: 16px;
        height: 16px;
        accent-color: #0d9488;
    }

    .seg-check-name {
        display: block;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.25;
    }

    .seg-check-meta {
        display: block;
        margin-top: 2px;
        color: #64748b;
        font-size: 11px;
    }

    .seg-select-row {
        display: block;
    }

    .seg-field-label {
        display: block;
        margin-bottom: 6px;
        color: #374151;
        font-size: 13px;
        font-weight: 800;
    }

    .seg-native-select,
    .seg-native-input {
        width: 100%;
        min-height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #374151;
        padding: 0 12px;
        font-size: 14px;
        outline: none;
    }

    .seg-native-select:focus,
    .seg-native-input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14);
    }

    .seg-mini-button {
        min-height: 42px;
        border: 1px solid #0d9488;
        border-radius: 8px;
        background: #0d9488;
        color: #ffffff;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }

    .seg-mini-button:hover {
        background: #0f766e;
    }

    .seg-selected-table-wrap {
        margin-top: 12px;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .seg-selected-table {
        width: 100%;
        min-width: 620px;
        border-collapse: collapse;
    }

    .seg-selected-table th {
        background: #f8fafc;
        color: #475569;
        padding: 10px 12px;
        text-align: left;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .seg-selected-table td {
        border-top: 1px solid #e2e8f0;
        color: #334155;
        padding: 10px 12px;
        font-size: 13px;
        vertical-align: top;
    }

    .seg-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 12px;
    }

    .seg-chip-list.is-table {
        margin-top: 0;
    }

    .seg-table-chip-wrap {
        max-width: min(100%, 360px);
        gap: 5px;
        align-items: flex-start;
    }

    .seg-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        max-width: 100%;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.15;
    }

    .seg-chip-list.is-table .seg-chip {
        max-width: 150px;
        padding: 4px 7px;
        font-size: 10.5px;
        overflow-wrap: anywhere;
        text-align: left;
        white-space: normal;
    }

    .seg-table-empty {
        display: inline-flex;
        border-radius: 999px;
        background: #f1f5f9;
        color: #64748b;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .seg-chip.is-emerald {
        background: #d1fae5;
        color: #065f46;
    }

    .seg-chip.is-blue {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .seg-chip.is-violet {
        background: #ede9fe;
        color: #6d28d9;
    }

    .seg-chip.is-amber {
        background: #fef3c7;
        color: #92400e;
    }

    .seg-chip.is-rose {
        background: #ffe4e6;
        color: #be123c;
    }

    .seg-chip.is-cyan {
        background: #cffafe;
        color: #0e7490;
    }

    .seg-chip.is-slate {
        background: #e2e8f0;
        color: #334155;
    }

    .seg-chip-button {
        border: 0;
        cursor: pointer;
    }

    .seg-chip-button:hover {
        filter: brightness(0.97);
    }

    .seg-chip-remove {
        display: inline-flex;
        width: 18px;
        height: 18px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.12);
        color: currentColor;
        font-size: 11px;
        font-weight: 900;
    }

    .seg-chip-tag {
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.55);
        padding: 1px 6px;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .seg-empty-state {
        margin: 12px 0 0;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        color: #64748b;
        padding: 12px;
        font-size: 13px;
        font-weight: 700;
    }

    .seg-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 14px 18px;
    }

    .seg-alert {
        border: 1px solid #fecaca;
        border-radius: 8px;
        background: #fef2f2;
        color: #b91c1c;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 700;
    }

    .seg-wizard {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 10px;
    }

    .seg-wizard-step {
        display: flex;
        min-height: 48px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 900;
        transition: all 160ms ease;
    }

    .seg-wizard-step span {
        display: inline-flex;
        width: 24px;
        height: 24px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #e2e8f0;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
    }

    .seg-wizard-step.is-active {
        border-color: #0d9488;
        background: #ecfdf5;
        color: #065f46;
    }

    .seg-wizard-step.is-active span,
    .seg-wizard-step.is-done span {
        background: #0d9488;
        color: #ffffff;
    }

    .seg-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.48);
        padding: 16px;
    }

    .seg-modal-box {
        width: min(100%, 460px);
        overflow: hidden;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
    }

    .seg-modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff, #f8fafc);
        padding: 14px 16px;
    }

    .seg-modal-title {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 900;
    }

    .seg-modal-close {
        border: 0;
        border-radius: 8px;
        background: #f1f5f9;
        color: #475569;
        padding: 6px 10px;
        font-size: 16px;
        font-weight: 900;
    }

    .tabla-compacta table {
        table-layout: fixed;
        width: 100%;
    }

    .tabla-compacta th,
    .tabla-compacta td {
        white-space: normal !important;
        word-break: break-word;
        vertical-align: middle;
        font-size: 12px;
        line-height: 1.25;
        padding-top: 8px;
        padding-bottom: 8px;
    }

    .tabla-compacta th:first-child,
    .tabla-compacta td:first-child {
        width: 78px;
        min-width: 78px;
        text-align: center;
        white-space: nowrap !important;
        word-break: normal;
    }

    .tabla-compacta th:last-child,
    .tabla-compacta td:last-child {
        width: 165px;
        min-width: 165px;
        white-space: nowrap !important;
        word-break: normal;
    }

    .tabla-areas th:nth-child(2),
    .tabla-areas td:nth-child(2) {
        width: 21%;
    }

    .tabla-areas th:nth-child(3),
    .tabla-areas td:nth-child(3) {
        width: 23%;
    }

    .tabla-areas th:nth-child(4),
    .tabla-areas td:nth-child(4) {
        width: auto;
    }

    .tabla-areas th:nth-child(5),
    .tabla-areas td:nth-child(5) {
        width: 115px;
        min-width: 115px;
        text-align: center;
    }

    .tabla-areas th:nth-child(6),
    .tabla-areas td:nth-child(6),
    .tabla-cargos th:nth-child(5),
    .tabla-cargos td:nth-child(5) {
        width: 112px;
        min-width: 112px;
        text-align: center;
        white-space: nowrap !important;
        word-break: normal;
    }

    .tabla-cargos th:nth-child(2),
    .tabla-cargos td:nth-child(2) {
        width: 24%;
    }

    .tabla-cargos th:nth-child(3),
    .tabla-cargos td:nth-child(3) {
        width: 28%;
    }

    .tabla-cargos th:nth-child(4),
    .tabla-cargos td:nth-child(4) {
        width: auto;
    }

    .tabla-texto-ajustado {
        display: -webkit-box;
        max-width: 260px;
        overflow: hidden;
        white-space: normal;
        word-break: break-word;
        font-size: 12px;
        line-height: 1.25;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .tabla-chip {
        display: inline-flex;
        max-width: 130px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 4px 8px;
        white-space: nowrap;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
    }

    .tabla-acciones {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .tabla-acciones form {
        margin: 0;
        display: inline-flex;
    }

    @media (max-width: 900px) {
        .seg-col-3,
        .seg-col-4,
        .seg-col-6 {
            grid-column: span 12 / span 12;
        }

        .seg-check-grid {
            grid-template-columns: 1fr;
        }

        .seg-select-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .seg-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .seg-wizard {
            grid-template-columns: 1fr;
        }
    }
</style>
