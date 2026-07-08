<style>
    .plantilla-shell {
        display: grid;
        gap: 16px;
        color: #0f172a;
    }

    .plantilla-card {
        border: 1px solid #dbe4ee;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
    }

    .plantilla-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-bottom: 1px solid #e5edf5;
        padding: 18px 20px;
    }

    .plantilla-title {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        color: #10233f;
    }

    .plantilla-subtitle {
        margin-top: 4px;
        font-size: 14px;
        color: #64748b;
    }

    .plantilla-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        border: 1px solid #b7e4d4;
        background: #ecfdf5;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 800;
        color: #047857;
        line-height: 1;
    }

    .plantilla-file-control {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        min-height: 42px;
        border: 1px solid #d6e1ec;
        border-radius: 10px;
        background: #ffffff;
        padding: 8px;
    }

    .plantilla-file-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 64px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        padding: 7px 10px;
        font-size: 12px;
        font-weight: 800;
        color: #334155;
        line-height: 1;
    }

    .plantilla-file-btn.is-select {
        border-color: #99f6e4;
        background: #ecfdf5;
        color: #047857;
    }

    .plantilla-file-btn.is-danger {
        border-color: #fecaca;
        background: #fff1f2;
        color: #be123c;
    }

    .plantilla-file-btn:disabled {
        cursor: not-allowed;
        opacity: 0.45;
    }

    .plantilla-file-name {
        flex: 1 1 180px;
        min-width: 0;
        overflow: hidden;
        color: #64748b;
        font-size: 13px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .plantilla-step-title {
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #d7f2ea;
        background: linear-gradient(90deg, #ecfdf5, #f8fafc);
        padding: 12px 18px;
        color: #0f766e;
        font-size: 15px;
        font-weight: 800;
    }

    .plantilla-step-title span {
        display: inline-flex;
        width: 28px;
        height: 28px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #0d9488;
        color: #ffffff;
        font-size: 14px;
        font-weight: 900;
    }

    .plantilla-step-title.is-amber {
        border-color: #fdecc8;
        background: linear-gradient(90deg, #fff7ed, #ffffff);
        color: #b45309;
    }

    .plantilla-step-title.is-amber span {
        background: #d97706;
    }

    .plantilla-grid {
        display: grid;
        grid-template-columns: minmax(260px, 310px) minmax(420px, 1fr) minmax(280px, 330px);
        gap: 18px;
        align-items: start;
    }

    .plantilla-panel {
        border: 1px solid #dbe4ee;
        border-radius: 10px;
        background: #ffffff;
        overflow: hidden;
    }

    .plantilla-panel-title {
        border-bottom: 1px solid #e5edf5;
        background: #eefaf7;
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 800;
        color: #0f766e;
    }

    .plantilla-panel-body {
        padding: 16px;
    }

    .plantilla-field-group {
        display: grid;
        margin-bottom: 14px;
        overflow: hidden;
        border: 1px solid #dbe7f0;
        border-radius: 10px;
        background: #ffffff;
    }

    .plantilla-field-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #f8fafc;
        border-bottom: 1px solid transparent;
        cursor: pointer;
        list-style: none;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
    }

    .plantilla-field-title::-webkit-details-marker {
        display: none;
    }

    .plantilla-field-title span:last-child {
        border-radius: 999px;
        background: #e2e8f0;
        padding: 4px 8px;
        color: #475569;
        font-size: 11px;
        text-transform: none;
        white-space: nowrap;
    }

    .plantilla-field-group[open] .plantilla-field-title {
        border-bottom-color: #dbe7f0;
        background: #eefaf7;
        color: #0f766e;
    }

    .plantilla-field-list {
        display: grid;
        gap: 8px;
        max-height: 360px;
        overflow: auto;
        padding: 10px;
    }

    .plantilla-field {
        display: block;
        width: 100%;
        border: 1px solid #d6e1ec;
        border-radius: 8px;
        background: #ffffff;
        padding: 10px 11px;
        text-align: left;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .plantilla-field:hover {
        border-color: #14b8a6;
        box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
    }

    .plantilla-field-code {
        display: block;
        font-size: 12px;
        font-weight: 800;
        color: #0f766e;
    }

    .plantilla-field-name {
        display: block;
        margin-top: 2px;
        font-size: 13px;
        color: #64748b;
    }

    .plantilla-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        border-bottom: 1px solid #e5edf5;
        padding: 12px 14px;
        background: #f8fafc;
    }

    .plantilla-tool {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        padding: 8px 11px;
        font-size: 13px;
        font-weight: 800;
        color: #334155;
    }

    .plantilla-canvas-wrap {
        background: #eef4f8;
        padding: 20px;
        overflow: auto;
    }

    .plantilla-canvas {
        position: relative;
        aspect-ratio: 794 / 1123;
        width: min(100%, 720px);
        margin: 0 auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(255,255,255,0.92)), #ffffff;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.13);
        overflow: hidden;
    }

    .plantilla-fondo {
        position: absolute;
        inset: 0;
        display: none;
        width: 100%;
        height: 100%;
        border: 0;
        opacity: 0.9;
        pointer-events: none;
    }

    .plantilla-fondo {
        object-fit: cover;
    }

    .plantilla-paper-content {
        position: absolute;
        inset: 0;
        z-index: 1;
        padding: 0;
    }

    .plantilla-cert-title {
        text-align: center;
        font-size: 18px;
        font-weight: 900;
        color: #0f172a;
    }

    .plantilla-cert-subtitle {
        margin-top: 8px;
        text-align: center;
        font-size: 13px;
        color: #475569;
    }

    .plantilla-drop-zone {
        position: absolute;
        inset: 0;
        margin: 0;
        min-height: 0;
        border: 1px dashed rgba(20, 184, 166, 0.45);
        border-radius: 0;
        background: transparent;
        padding: 0;
    }

    .plantilla-empty-message {
        display: grid;
        height: 100%;
        place-items: center;
        border: 0;
        color: #64748b;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
        background: rgba(255, 255, 255, 0.72);
    }

    .plantilla-element {
        position: absolute;
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0;
        border: 1px dashed #10b981;
        border-radius: 7px;
        background: rgba(236, 253, 245, 0.92);
        padding: 4px 7px;
        font-size: 13px;
        font-weight: 800;
        color: #065f46;
        cursor: pointer;
        overflow: hidden;
        user-select: none;
    }

    .plantilla-element-text {
        display: block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        width: 100%;
    }

    .plantilla-element.is-selected {
        border-style: solid;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.14);
    }

    .plantilla-resize-handle {
        position: absolute;
        right: -5px;
        bottom: -5px;
        width: 12px;
        height: 12px;
        border: 2px solid #ffffff;
        border-radius: 999px;
        background: #0d9488;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.22);
        cursor: nwse-resize;
        z-index: 3;
    }

    [data-plantilla-elemento]:not(.is-selected) .plantilla-resize-handle {
        display: none;
    }

    .plantilla-table-sample {
        position: absolute;
        display: block;
        box-sizing: border-box;
        margin: 0;
        border-radius: 8px;
        overflow: hidden;
        border: 1px dashed #10b981;
        background: #ffffff;
    }

    .plantilla-table-sample table {
        width: 100%;
        font-size: 11px;
        border-collapse: collapse;
    }

    .plantilla-table-sample th,
    .plantilla-table-sample td {
        border-bottom: 1px solid #e2e8f0;
        padding: 7px;
        text-align: left;
    }

    .plantilla-summary {
        display: grid;
        gap: 8px;
        font-size: 13px;
        color: #334155;
    }

    .plantilla-remove-field {
        border: 1px solid #fecaca;
        border-radius: 8px;
        background: #fff1f2;
        padding: 9px 12px;
        color: #be123c;
        font-size: 13px;
        font-weight: 800;
    }

    .plantilla-remove-field:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .plantilla-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
    }

    .plantilla-toggle input {
        width: 16px;
        height: 16px;
        border-radius: 4px;
        accent-color: #0d9488;
    }

    .plantilla-format-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .plantilla-format-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 34px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #334155;
        font-size: 13px;
        font-weight: 900;
        line-height: 1;
    }

    .plantilla-format-btn:hover {
        border-color: #14b8a6;
        background: #ecfdf5;
        color: #047857;
    }

    .plantilla-color-input {
        width: 42px;
        height: 34px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        cursor: pointer;
        padding: 3px;
    }

    .plantilla-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
        border-top: 1px solid #e5edf5;
        padding: 16px;
    }

    @media (max-width: 1180px) {
        .plantilla-grid {
            grid-template-columns: 1fr;
        }

        .plantilla-canvas {
            width: 100%;
        }
    }

    @media (max-width: 640px) {
        .plantilla-head,
        .plantilla-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .plantilla-title {
            font-size: 20px;
        }

        .plantilla-step-title {
            padding: 11px 14px;
        }

        .plantilla-canvas-wrap {
            padding: 12px;
        }

        .plantilla-paper-content {
            padding: 0;
        }
    }
</style>
