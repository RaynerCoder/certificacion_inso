<style>
    .plantilla-shell {
        color: #0f172a;
        display: grid;
        gap: 16px;
    }

    .plantilla-card,
    .plantilla-panel {
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 9px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .plantilla-head {
        align-items: center;
        border-bottom: 1px solid #e5edf5;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        padding: 16px 18px;
    }

    .plantilla-title {
        color: #10233f;
        font-size: 21px;
        font-weight: 900;
        margin: 0;
    }

    .plantilla-subtitle {
        color: #64748b;
        font-size: 14px;
        margin-top: 4px;
    }

    .plantilla-step-title {
        align-items: center;
        background: #ecfdf5;
        border-bottom: 1px solid #d7f2ea;
        color: #0f766e;
        display: flex;
        font-size: 15px;
        font-weight: 900;
        gap: 10px;
        padding: 11px 16px;
    }

    .plantilla-step-title span {
        align-items: center;
        background: #0d9488;
        border-radius: 7px;
        color: #ffffff;
        display: inline-flex;
        font-size: 13px;
        height: 26px;
        justify-content: center;
        width: 26px;
    }

    .plantilla-label {
        color: #334155;
        display: block;
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .plantilla-file-control {
        align-items: center;
        border: 1px solid #d6e1ec;
        border-radius: 8px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        min-height: 34px;
        padding: 5px;
    }

    .plantilla-file-icon {
        align-items: center;
        background: #ecfdf5;
        border: 1px solid #99f6e4;
        border-radius: 8px;
        color: #047857;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 12px;
        height: 24px;
        justify-content: center;
        width: 24px;
    }

    .plantilla-file-btn,
    .plantilla-action-btn {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        color: #334155;
        display: inline-flex;
        font-size: 12px;
        font-weight: 900;
        gap: 6px;
        justify-content: center;
        min-height: 32px;
        padding: 0 10px;
        white-space: nowrap;
    }

    .plantilla-file-btn.is-select,
    .plantilla-action-btn.is-primary {
        background: #ecfdf5;
        border-color: #99f6e4;
        color: #047857;
    }

    .plantilla-file-btn.is-danger,
    .plantilla-action-btn.is-danger {
        background: #fff1f2;
        border-color: #fecaca;
        color: #be123c;
    }

    .plantilla-file-btn:disabled,
    .plantilla-action-btn:disabled {
        cursor: not-allowed;
        opacity: 0.45;
    }

    .plantilla-file-name {
        color: #64748b;
        flex: 1 1 140px;
        font-size: 12px;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .plantilla-file-control .plantilla-file-btn {
        border-radius: 6px;
        font-size: 11px;
        min-height: 25px;
        padding: 0 7px;
    }

    .plantilla-option-check {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #0f172a;
        display: flex;
        gap: 9px;
        min-height: 42px;
        padding: 8px 10px;
    }

    .plantilla-option-check input {
        accent-color: #059669;
        height: 16px;
        width: 16px;
    }

    .plantilla-option-check strong,
    .plantilla-option-check small {
        display: block;
        line-height: 1.2;
    }

    .plantilla-option-check strong {
        font-size: 13px;
        font-weight: 900;
    }

    .plantilla-option-check small {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        margin-top: 2px;
    }

    .plantilla-chip {
        align-items: center;
        background: #ecfdf5;
        border: 1px solid #b7e4d4;
        border-radius: 999px;
        color: #047857;
        display: inline-flex;
        font-size: 11px;
        font-weight: 900;
        line-height: 1;
        padding: 4px 9px;
        width: fit-content;
    }

    .plantilla-designer {
        align-items: start;
        display: grid;
        gap: 14px;
        grid-template-columns: minmax(260px, 310px) minmax(0, 1fr) minmax(270px, 320px);
    }

    .plantilla-panel-title {
        background: #eefaf7;
        border-bottom: 1px solid #e5edf5;
        color: #0f766e;
        font-size: 14px;
        font-weight: 900;
        padding: 12px 14px;
    }

    .plantilla-panel-body {
        padding: 14px;
    }

    .plantilla-search {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #0f172a;
        font-size: 14px;
        margin-bottom: 12px;
        min-height: 40px;
        padding: 0 12px;
        width: 100%;
    }

    .plantilla-field-group {
        border: 1px solid #dbe7f0;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .plantilla-field-title {
        align-items: center;
        background: #f8fafc;
        color: #475569;
        cursor: pointer;
        display: flex;
        font-size: 12px;
        font-weight: 900;
        gap: 10px;
        justify-content: space-between;
        list-style: none;
        padding: 10px 12px;
        text-transform: uppercase;
    }

    .plantilla-field-title::-webkit-details-marker {
        display: none;
    }

    .plantilla-field-title span:last-child {
        background: #e2e8f0;
        border-radius: 999px;
        color: #475569;
        font-size: 11px;
        padding: 3px 8px;
    }

    .plantilla-field-group[open] .plantilla-field-title {
        background: #eefaf7;
        color: #0f766e;
    }

    .plantilla-field-list {
        display: grid;
        gap: 8px;
        max-height: 350px;
        overflow: auto;
        padding: 10px;
    }

    .plantilla-field {
        background: #ffffff;
        border: 1px solid #d6e1ec;
        border-radius: 8px;
        cursor: grab;
        padding: 10px 11px;
        text-align: left;
        width: 100%;
    }

    .plantilla-field:hover {
        border-color: #14b8a6;
        box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
    }

    .plantilla-field-name {
        color: #10233f;
        display: block;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.25;
    }

    .plantilla-toolbar {
        align-items: center;
        border-bottom: 1px solid #dbe4ee;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px;
    }

    .plantilla-toolbar-separator {
        background: #dbe4ee;
        height: 30px;
        width: 1px;
    }

    .plantilla-zoom-control {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        display: inline-flex;
        height: 34px;
        overflow: hidden;
    }

    .plantilla-zoom-control button {
        color: #0f766e;
        font-size: 15px;
        font-weight: 900;
        height: 100%;
        width: 34px;
    }

    .plantilla-zoom-control span {
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
        min-width: 54px;
        text-align: center;
    }

    .plantilla-canvas-wrap {
        background: #eef4f8;
        overflow: auto;
        padding: 18px;
    }

    .plantilla-canvas {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.1);
        height: 1056px;
        margin: 0 auto;
        overflow: hidden;
        position: relative;
        width: 816px;
    }

    .plantilla-canvas.is-grid {
        background-image:
            linear-gradient(rgba(15, 118, 110, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(15, 118, 110, 0.08) 1px, transparent 1px);
        background-size: 20px 20px;
    }

    .plantilla-canvas.is-work-white .plantilla-canvas-bg,
    .plantilla-canvas.is-work-white .plantilla-canvas-placeholder {
        display: none !important;
    }

    .plantilla-canvas.is-oficio {
        height: 1248px;
    }

    .plantilla-canvas.is-horizontal {
        height: 816px;
        width: 1056px;
    }

    .plantilla-canvas.is-oficio.is-horizontal {
        height: 816px;
        width: 1248px;
    }

    .plantilla-canvas-bg {
        display: none;
        height: 100%;
        inset: 0;
        object-fit: fill;
        position: absolute;
        width: 100%;
        z-index: 1;
    }

    .plantilla-canvas-bg.is-visible {
        display: block;
    }

    .plantilla-canvas-placeholder {
        align-items: center;
        background: #f8fafc;
        color: #64748b;
        display: grid;
        gap: 6px;
        inset: 0;
        justify-items: center;
        padding: 24px;
        position: absolute;
        text-align: center;
        z-index: 1;
    }

    .plantilla-canvas-placeholder strong {
        color: #0f172a;
        font-size: 17px;
        font-weight: 950;
    }

    .plantilla-image-info {
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        margin: 12px auto 0;
        max-width: 816px;
        padding: 10px 12px;
    }

    .plantilla-image-info strong {
        color: #0f766e;
        font-weight: 900;
    }

    .plantilla-element {
        appearance: none;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 7px;
        box-sizing: border-box;
        color: #0f172a;
        cursor: move;
        display: block;
        line-height: 1.25;
        min-height: 26px;
        overflow: hidden;
        padding: 5px 7px;
        position: absolute;
        text-align: left;
        user-select: none;
        white-space: normal;
        z-index: 3;
    }

    .plantilla-element:hover {
        border-color: rgba(16, 185, 129, 0.55);
    }

    .plantilla-element.is-selected {
        border: 1px dashed #047857;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.16);
    }

    .plantilla-resize-handle {
        background: #047857;
        border: 2px solid #ffffff;
        border-radius: 999px;
        bottom: -8px;
        cursor: nwse-resize;
        height: 14px;
        position: absolute;
        right: -8px;
        width: 14px;
    }

    .plantilla-element.is-texto {
        white-space: pre-wrap;
    }

    .plantilla-element.is-texto.is-justify {
        white-space: pre-line;
    }

    .plantilla-element.is-imagen {
        background: rgba(255, 255, 255, 0.72);
        padding: 0;
    }

    .plantilla-element.is-imagen img {
        display: block;
        height: 100%;
        object-fit: contain;
        pointer-events: none;
        width: 100%;
    }

    .plantilla-element.is-firma {
        white-space: pre-line;
    }

    .plantilla-element.is-qr {
        align-items: center;
        border: 1px dashed #0f766e;
        display: flex;
        font-weight: 900;
        justify-content: center;
        text-align: center;
    }

    .plantilla-element.is-qr img {
        display: block;
        height: 100%;
        object-fit: contain;
        pointer-events: none;
        width: 100%;
    }

    .plantilla-element.is-tabla {
        padding: 0;
    }

    .plantilla-word-table {
        border-collapse: collapse;
        font-size: inherit;
        height: 100%;
        table-layout: fixed;
        width: 100%;
    }

    .plantilla-word-table th,
    .plantilla-word-table td {
        border: 1px solid currentColor;
        line-height: 1.15;
        padding: 4px 6px;
        text-align: inherit;
        vertical-align: middle;
        word-break: break-word;
    }

    .plantilla-word-table th {
        font-weight: 900;
    }

    .plantilla-element.is-center {
        text-align: center;
    }

    .plantilla-element.is-right {
        text-align: right;
    }

    .plantilla-element.is-justify {
        text-align: justify;
    }

    .plantilla-token {
        background: #d1fae5;
        border: 1px solid #99f6e4;
        border-radius: 999px;
        color: #047857;
        display: inline-flex;
        font-size: 0.92em;
        font-weight: 900;
        padding: 1px 6px;
    }

    .plantilla-editor-ayuda {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        color: #475569;
        font-size: 13px;
        margin-bottom: 12px;
        padding: 10px 12px;
    }

    .plantilla-prop-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .plantilla-prop-full {
        grid-column: 1 / -1;
    }

    .plantilla-prop-label {
        color: #334155;
        display: block;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .plantilla-prop-input,
    .plantilla-prop-select,
    .plantilla-prop-textarea {
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        color: #0f172a;
        font-size: 13px;
        min-height: 36px;
        padding: 0 9px;
        width: 100%;
    }

    .plantilla-prop-textarea {
        min-height: 170px;
        padding: 9px;
        resize: vertical;
    }

    .plantilla-table-editor {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px;
    }

    .plantilla-table-editor-head {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-bottom: 8px;
    }

    .plantilla-table-editor-note {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.35;
        margin: 6px 0 8px;
    }

    .plantilla-table-editor-scroll {
        max-height: 230px;
        overflow: auto;
        padding-bottom: 2px;
    }

    .plantilla-table-editor-grid {
        display: grid;
        gap: 6px;
        min-width: max-content;
    }

    .plantilla-table-editor-input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        color: #0f172a;
        font-size: 12px;
        min-height: 32px;
        padding: 0 8px;
        width: 100%;
    }

    .plantilla-table-editor-input.is-header {
        background: #ecfdf5;
        border-color: #99f6e4;
        color: #065f46;
        font-weight: 900;
    }

    .plantilla-qr-editor {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px;
    }

    .plantilla-qr-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 8px;
    }

    .plantilla-format-row {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .plantilla-format-btn {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        color: #334155;
        display: inline-flex;
        font-size: 12px;
        font-weight: 900;
        height: 34px;
        justify-content: center;
        min-width: 34px;
        padding: 0 9px;
    }

    .plantilla-format-btn.is-active {
        background: #ecfdf5;
        border-color: #99f6e4;
        color: #047857;
    }

    .plantilla-preview-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        color: #475569;
        font-size: 13px;
        margin-top: 12px;
        padding: 12px;
    }

    .plantilla-layers-box {
        border-top: 1px solid #e2e8f0;
        margin-top: 14px;
        padding-top: 12px;
    }

    .plantilla-layers-title {
        color: #0f766e;
        font-size: 12px;
        font-weight: 950;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .plantilla-layer-item {
        align-items: center;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        color: #334155;
        display: flex;
        gap: 8px;
        margin-bottom: 7px;
        padding: 8px 10px;
        text-align: left;
        width: 100%;
    }

    .plantilla-layer-item.is-active {
        background: #ecfdf5;
        border-color: #5eead4;
        color: #065f46;
    }

    .plantilla-layer-item span {
        align-items: center;
        background: #e2e8f0;
        border-radius: 999px;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 11px;
        font-weight: 900;
        height: 22px;
        justify-content: center;
        width: 22px;
    }

    .plantilla-layer-item strong {
        display: block;
        font-size: 12px;
        line-height: 1.2;
    }

    .plantilla-layer-item small {
        color: #64748b;
        display: block;
        font-size: 10px;
        font-weight: 800;
        margin-top: 2px;
    }

    .plantilla-actions {
        align-items: center;
        border-top: 1px solid #e5edf5;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
        padding: 14px;
    }

    .plantilla-show-only {
        min-height: calc(100vh - 210px);
    }

    .plantilla-show-only .plantilla-element {
        cursor: default;
        pointer-events: none;
    }

    .plantilla-empty-preview {
        background: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        color: #64748b;
        font-weight: 800;
        margin: 0 auto;
        max-width: 620px;
        padding: 24px;
        text-align: center;
    }

    @media (max-width: 1280px) {
        .plantilla-designer {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .plantilla-head,
        .plantilla-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .plantilla-canvas-wrap {
            padding: 12px;
        }
    }
</style>
