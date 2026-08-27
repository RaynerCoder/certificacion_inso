<style>
    .cap-page {
        color: #0f172a;
        display: grid;
        gap: 16px;
    }

    .cap-card {
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 12px;
        box-shadow: 0 2px 5px rgba(15, 23, 42, .035);
    }

    .cap-hero {
        align-items: center;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        padding: 18px 20px;
    }

    .cap-hero-copy {
        align-items: flex-start;
        display: flex;
        gap: 13px;
    }

    .cap-hero-icon,
    .cap-stat-icon {
        align-items: center;
        background: #dcfce7;
        border-radius: 10px;
        color: #047857;
        display: inline-flex;
        flex: 0 0 auto;
        height: 42px;
        justify-content: center;
        width: 42px;
    }

    .cap-title {
        font-size: 20px;
        font-weight: 800;
        line-height: 1.25;
        margin: 0;
    }

    .cap-description {
        color: #64748b;
        font-size: 13px;
        line-height: 1.45;
        margin: 4px 0 0;
    }

    .cap-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .cap-button {
        align-items: center;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        color: #334155;
        display: inline-flex;
        font-size: 13px;
        font-weight: 700;
        gap: 8px;
        justify-content: center;
        min-height: 38px;
        padding: 8px 13px;
        text-decoration: none;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }

    .cap-button:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .cap-button-primary {
        background: #059669;
        border-color: #059669;
        color: #ffffff;
    }

    .cap-button-primary:hover {
        background: #047857;
        border-color: #047857;
    }

    .cap-button-danger {
        border-color: #fecaca;
        color: #b91c1c;
    }

    .cap-stats {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .cap-stat {
        align-items: center;
        display: flex;
        gap: 12px;
        padding: 15px;
    }

    .cap-stat-value {
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
    }

    .cap-stat-label {
        color: #64748b;
        font-size: 12px;
        margin-top: 5px;
    }

    .cap-section-head {
        align-items: center;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        padding: 14px 16px;
    }

    .cap-section-title {
        font-size: 15px;
        font-weight: 800;
        margin: 0;
    }

    .cap-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .cap-field {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #0f172a;
        font-size: 13px;
        min-height: 37px;
        outline: none;
        padding: 8px 11px;
    }

    .cap-field:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .13);
    }

    .cap-form-body {
        padding: 18px;
    }

    .cap-form-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .cap-form-group {
        display: grid;
        gap: 6px;
    }

    .cap-form-group-wide {
        grid-column: 1 / -1;
    }

    .cap-form-label {
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .cap-form-help {
        color: #64748b;
        font-size: 11px;
        line-height: 1.4;
    }

    .cap-form-footer {
        align-items: center;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 9px;
        justify-content: flex-end;
        padding: 14px 18px;
    }

    .cap-config-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
        padding: 18px;
    }

    .cap-config-panel {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }

    .cap-config-panel-head {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 11px 13px;
    }

    .cap-config-panel-body {
        display: grid;
        gap: 10px;
        padding: 13px;
    }

    .cap-url-row {
        display: grid;
        gap: 8px;
        grid-template-columns: minmax(0, 1fr) auto auto;
    }

    .cap-field-list {
        display: grid;
        gap: 8px;
    }

    .cap-field-item {
        align-items: center;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        display: grid;
        gap: 8px;
        grid-template-columns: minmax(130px, .8fr) minmax(160px, 1fr) auto;
        padding: 9px 10px;
    }

    .cap-field-item strong {
        font-size: 12px;
    }

    .cap-field-source {
        color: #64748b;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 11px;
    }

    .cap-preview-flow {
        display: grid;
        gap: 10px;
    }

    .cap-preview-step {
        align-items: flex-start;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        display: flex;
        gap: 10px;
        padding: 11px;
    }

    .cap-preview-step-number {
        align-items: center;
        background: #dcfce7;
        border-radius: 50%;
        color: #047857;
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 12px;
        font-weight: 800;
        height: 28px;
        justify-content: center;
        width: 28px;
    }

    .cap-preview-step strong,
    .cap-preview-step span {
        display: block;
    }

    .cap-preview-step strong { font-size: 12px; }
    .cap-preview-step span { color: #64748b; font-size: 11px; margin-top: 2px; }

    .cap-button[disabled] {
        cursor: not-allowed;
        opacity: .55;
    }

    .cap-table-wrap {
        overflow-x: auto;
    }

    .cap-table {
        border-collapse: collapse;
        min-width: 850px;
        width: 100%;
    }

    .cap-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .02em;
        padding: 11px 13px;
        text-align: left;
        text-transform: uppercase;
    }

    .cap-table td {
        border-top: 1px solid #e2e8f0;
        color: #334155;
        font-size: 13px;
        padding: 12px 13px;
        vertical-align: middle;
    }

    .cap-table strong {
        color: #0f172a;
        display: block;
        font-weight: 700;
    }

    .cap-table small {
        color: #64748b;
    }

    .cap-chip {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 11px;
        font-weight: 800;
        gap: 6px;
        padding: 5px 9px;
        white-space: nowrap;
    }

    .cap-chip-green { background: #dcfce7; color: #047857; }
    .cap-chip-amber { background: #fef3c7; color: #a16207; }
    .cap-chip-slate { background: #e2e8f0; color: #475569; }
    .cap-chip-red { background: #fee2e2; color: #b91c1c; }

    .cap-empty-note {
        align-items: center;
        background: #f0fdfa;
        border: 1px solid #ccfbf1;
        border-radius: 9px;
        color: #0f766e;
        display: flex;
        font-size: 12px;
        gap: 9px;
        margin: 14px 16px 16px;
        padding: 11px 12px;
    }

    .cap-modal-backdrop {
        align-items: center;
        background: rgba(15, 23, 42, .58);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 18px;
        position: fixed;
        z-index: 80;
    }

    .cap-modal-backdrop.is-open {
        display: flex;
    }

    .cap-modal {
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        max-height: calc(100vh - 36px);
        max-width: 680px;
        overflow-y: auto;
        width: 100%;
    }

    .cap-modal-head,
    .cap-modal-footer {
        align-items: center;
        display: flex;
        gap: 10px;
        justify-content: space-between;
        padding: 16px 18px;
    }

    .cap-modal-head { border-bottom: 1px solid #e2e8f0; }
    .cap-modal-footer { border-top: 1px solid #e2e8f0; justify-content: flex-end; }
    .cap-modal-body { padding: 18px; }

    .cap-detail-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .cap-detail {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 11px;
    }

    .cap-detail span {
        color: #64748b;
        display: block;
        font-size: 11px;
        margin-bottom: 3px;
    }

    .cap-detail strong {
        font-size: 13px;
    }

    @media (max-width: 960px) {
        .cap-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .cap-hero { align-items: flex-start; flex-direction: column; }
    }

    @media (max-width: 640px) {
        .cap-stats { grid-template-columns: 1fr; }
        .cap-section-head { align-items: flex-start; flex-direction: column; }
        .cap-actions, .cap-toolbar { width: 100%; }
        .cap-button { flex: 1 1 auto; }
        .cap-detail-grid { grid-template-columns: 1fr; }
        .cap-form-grid { grid-template-columns: 1fr; }
        .cap-form-group-wide { grid-column: auto; }
        .cap-config-grid { grid-template-columns: 1fr; }
        .cap-url-row { grid-template-columns: 1fr; }
        .cap-field-item { grid-template-columns: 1fr; }
        .cap-form-footer { align-items: stretch; flex-direction: column-reverse; }
        .cap-modal-footer { align-items: stretch; flex-direction: column-reverse; }
    }
</style>
