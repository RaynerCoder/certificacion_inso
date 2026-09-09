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

    /* Constructor visual del formulario público. */
    .cap-builder-card { overflow: hidden; }

    .cap-builder-status {
        align-items: center;
        color: #64748b;
        display: flex;
        font-size: 11px;
        gap: 10px;
    }

    .cap-builder-stepbar {
        align-items: center;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        gap: 8px;
        padding: 14px 16px;
    }

    .cap-builder-steps {
        display: flex;
        flex: 1 1 auto;
        gap: 8px;
        min-width: 0;
        overflow-x: auto;
        padding-bottom: 2px;
    }

    .cap-add-step { flex: 0 0 auto; }

    .cap-builder-step {
        align-items: center;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 10px;
        color: #475569;
        cursor: pointer;
        display: grid;
        gap: 2px 9px;
        grid-template-columns: 32px minmax(0, 1fr);
        padding: 9px 10px;
        text-align: left;
        flex: 1 0 170px;
    }

    .cap-builder-step > span {
        align-items: center;
        background: #e2e8f0;
        border-radius: 50%;
        display: flex;
        font-size: 12px;
        font-weight: 800;
        grid-row: 1 / 3;
        height: 30px;
        justify-content: center;
        width: 30px;
    }

    .cap-builder-step strong { font-size: 12px; }
    .cap-builder-step small { color: #94a3b8; font-size: 10px; }

    .cap-builder-step.is-active {
        background: #ecfdf5;
        border-color: #34d399;
        color: #047857;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, .08);
    }

    .cap-builder-step.is-active > span { background: #059669; color: #ffffff; }

    .cap-builder-layout {
        display: grid;
        grid-template-columns: 220px minmax(360px, 1fr) 270px;
        min-height: 580px;
    }

    .cap-builder-palette,
    .cap-builder-properties {
        background: #f8fafc;
        min-width: 0;
        padding: 14px;
    }

    .cap-builder-palette { border-right: 1px solid #e2e8f0; }
    .cap-builder-properties { border-left: 1px solid #e2e8f0; }

    .cap-builder-panel-title {
        border-bottom: 1px solid #e2e8f0;
        display: grid;
        gap: 3px;
        margin: -2px -2px 13px;
        padding: 0 2px 11px;
    }

    .cap-builder-panel-title strong { font-size: 13px; }
    .cap-builder-panel-title small { color: #64748b; font-size: 10px; line-height: 1.4; }

    .cap-builder-tools {
        display: grid;
        gap: 7px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .cap-builder-tools button {
        align-items: center;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        color: #334155;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        font-size: 10px;
        font-weight: 700;
        gap: 6px;
        justify-content: center;
        min-height: 68px;
        padding: 8px 5px;
    }

    .cap-builder-tools button i { color: #059669; font-size: 16px; }
    .cap-builder-tools button:hover { border-color: #34d399; background: #ecfdf5; }

    .cap-system-fields {
        display: grid;
        gap: 6px;
        max-height: 310px;
        overflow-y: auto;
        padding-right: 3px;
    }

    .cap-system-fields button {
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        color: #334155;
        cursor: pointer;
        display: grid;
        gap: 2px;
        padding: 8px 9px;
        text-align: left;
    }

    .cap-system-fields button:hover {
        background: #ecfdf5;
        border-color: #34d399;
    }

    .cap-system-fields strong { font-size: 10px; }
    .cap-system-fields small { color: #64748b; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 8px; }
    .cap-custom-fields-title { margin-top: 16px; }

    .cap-builder-tip {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 9px;
        color: #854d0e;
        display: flex;
        font-size: 10px;
        gap: 8px;
        line-height: 1.45;
        margin-top: 14px;
        padding: 10px;
    }

    .cap-builder-canvas { background: #eef3f7; min-width: 0; padding: 18px; }

    .cap-canvas-head {
        align-items: flex-start;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 11px;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 12px;
        padding: 15px;
    }

    .cap-canvas-kicker {
        color: #059669;
        display: block;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .cap-canvas-head h3 { font-size: 16px; margin: 3px 0 0; }
    .cap-canvas-head p { color: #64748b; font-size: 11px; margin: 4px 0 0; }
    .cap-canvas-fields { display: grid; gap: 9px; }

    .cap-canvas-empty {
        align-items: center;
        background: rgba(255, 255, 255, .72);
        border: 1px dashed #94a3b8;
        border-radius: 10px;
        color: #64748b;
        display: flex;
        flex-direction: column;
        gap: 6px;
        justify-content: center;
        min-height: 210px;
        padding: 20px;
        text-align: center;
    }

    .cap-canvas-empty i { color: #059669; font-size: 25px; }
    .cap-canvas-empty strong { color: #334155; font-size: 13px; }
    .cap-canvas-empty span { font-size: 11px; }

    .cap-canvas-field {
        align-items: flex-start;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 10px;
        color: #0f172a;
        cursor: pointer;
        display: grid;
        gap: 10px;
        grid-template-columns: 14px minmax(0, 1fr) auto;
        padding: 12px;
        text-align: left;
        width: 100%;
    }

    .cap-canvas-field:hover { border-color: #86efac; }
    .cap-canvas-field.is-selected { border-color: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, .11); }
    .cap-field-drag { color: #cbd5e1; padding-top: 2px; }
    .cap-canvas-field-body, .cap-canvas-label, .cap-canvas-help { display: block; }
    .cap-canvas-label { font-size: 12px; font-weight: 800; }
    .cap-canvas-label b { color: #dc2626; }
    .cap-canvas-help { color: #64748b; font-size: 10px; margin-top: 3px; }

    .cap-field-badge {
        background: #e0f2fe;
        border-radius: 999px;
        color: #0369a1;
        font-size: 9px;
        font-weight: 800;
        padding: 4px 7px;
    }

    .cap-field-badge.is-locked { background: #e2e8f0; color: #475569; }

    .cap-preview-control {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 7px;
        color: #94a3b8;
        display: flex;
        font-size: 10px;
        margin-top: 8px;
        min-height: 34px;
        padding: 7px 9px;
    }

    .cap-preview-control.is-textarea { min-height: 58px; }
    .cap-preview-options { color: #64748b; font-size: 10px; margin-top: 10px; }
    .cap-preview-options span { border: 1px solid #94a3b8; border-radius: 50%; display: inline-block; height: 10px; margin: 0 3px 0 8px; width: 10px; }
    .cap-preview-options span:first-child { margin-left: 0; }
    .cap-preview-options span.is-square { border-radius: 2px; }
    .cap-preview-note { background: #eff6ff; border-radius: 7px; color: #475569; font-size: 10px; margin-top: 8px; padding: 9px; }

    .cap-properties-empty {
        align-items: center;
        color: #94a3b8;
        display: flex;
        flex-direction: column;
        font-size: 11px;
        gap: 9px;
        justify-content: center;
        min-height: 220px;
        text-align: center;
    }

    .cap-properties-empty i { font-size: 24px; }
    .cap-properties-form { display: grid; gap: 13px; }

    .cap-property-check {
        align-items: flex-start;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        display: flex;
        gap: 8px;
        padding: 10px;
    }

    .cap-property-check input { accent-color: #059669; margin-top: 2px; }
    .cap-property-check strong, .cap-property-check small { display: block; }
    .cap-property-check strong { font-size: 11px; }
    .cap-property-check small { color: #64748b; font-size: 9px; margin-top: 2px; }
    .cap-property-actions { display: grid; gap: 7px; grid-template-columns: 1fr 1fr; }

    .cap-builder-footer {
        align-items: center;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        padding: 13px 16px;
    }

    .cap-builder-url { flex: 1 1 420px; max-width: 620px; }

    .cap-prototype-message {
        background: #0f172a;
        border-radius: 9px;
        bottom: 22px;
        color: #ffffff;
        font-size: 12px;
        left: 50%;
        opacity: 0;
        padding: 11px 16px;
        pointer-events: none;
        position: fixed;
        transform: translate(-50%, 15px);
        transition: opacity .2s ease, transform .2s ease;
        z-index: 100;
    }

    .cap-prototype-message.is-visible { opacity: 1; transform: translate(-50%, 0); }

    @media (max-width: 960px) {
        .cap-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .cap-hero { align-items: flex-start; flex-direction: column; }
        .cap-builder-layout { grid-template-columns: 190px minmax(330px, 1fr); }
        .cap-builder-properties { border-left: 0; border-top: 1px solid #e2e8f0; grid-column: 1 / -1; }
        .cap-properties-empty { min-height: 90px; }
        .cap-properties-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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
        .cap-builder-status { align-items: flex-start; flex-direction: column; }
        .cap-builder-stepbar { align-items: stretch; flex-direction: column; }
        .cap-builder-steps { display: flex; overflow-x: auto; }
        .cap-add-step { width: 100%; }
        .cap-builder-step { flex: 0 0 170px; }
        .cap-builder-layout { display: block; }
        .cap-builder-palette { border-bottom: 1px solid #e2e8f0; border-right: 0; }
        .cap-builder-tools { grid-template-columns: repeat(4, minmax(78px, 1fr)); overflow-x: auto; }
        .cap-system-fields { grid-template-columns: repeat(2, minmax(130px, 1fr)); max-height: 220px; }
        .cap-builder-canvas { padding: 12px; }
        .cap-canvas-head { align-items: stretch; flex-direction: column; }
        .cap-builder-properties { border-top: 1px solid #e2e8f0; }
        .cap-properties-form { grid-template-columns: 1fr; }
        .cap-builder-footer { align-items: stretch; flex-direction: column; }
        .cap-builder-url { max-width: none; }
        .cap-canvas-field { grid-template-columns: 12px minmax(0, 1fr); }
        .cap-field-badge { grid-column: 2; justify-self: start; }
    }
</style>
