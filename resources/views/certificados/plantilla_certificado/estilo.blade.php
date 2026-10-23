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
        min-width: 0;
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
    .plantilla-action-btn.is-primary,
    .plantilla-action-btn.is-active {
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
        gap: 12px;
        grid-template-columns: minmax(280px, 320px) minmax(0, 1fr);
    }

    .plantilla-panel-title {
        background: #eefaf7;
        border-bottom: 1px solid #e5edf5;
        color: #0f766e;
        font-size: 14px;
        font-weight: 900;
        padding: 12px 14px;
    }

    /* Campos, propiedades y capas viven en el mismo lateral para ampliar el lienzo. */
    .plantilla-workspace-sidebar {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 92px);
        min-height: 520px;
        overflow: hidden;
        position: sticky;
        top: 80px;
    }

    .plantilla-sidebar-tabs {
        background: #f8fafc;
        border-bottom: 1px solid #dbe4ee;
        display: grid;
        flex: 0 0 auto;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        padding: 5px;
    }

    .plantilla-sidebar-tab {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 7px;
        color: #64748b;
        display: inline-flex;
        font-size: 12px;
        font-weight: 900;
        gap: 5px;
        justify-content: center;
        min-height: 36px;
        padding: 7px 6px;
    }

    .plantilla-sidebar-tab:hover {
        background: #ffffff;
        color: #0f766e;
    }

    .plantilla-sidebar-tab.is-active {
        background: #ffffff;
        border-color: #99f6e4;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
        color: #0f766e;
    }

    .plantilla-sidebar-tab span {
        align-items: center;
        background: #e2e8f0;
        border-radius: 999px;
        display: inline-flex;
        font-size: 10px;
        height: 19px;
        justify-content: center;
        min-width: 19px;
        padding: 0 5px;
    }

    .plantilla-sidebar-content {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }

    .plantilla-sidebar-pane {
        padding: 12px;
    }

    .plantilla-sidebar-pane[hidden] {
        display: none;
    }

    .plantilla-workspace-sidebar .plantilla-field-list {
        max-height: none;
        overflow: visible;
    }

    .plantilla-canvas-panel {
        min-width: 0;
    }

    /* El modo máximo permite trabajar en el certificado sin distracciones del resto de la pantalla. */
    body.plantilla-editor-maximizado {
        overflow: hidden;
    }

    .plantilla-designer.is-maximized {
        align-items: stretch;
        background: #eef4f8;
        grid-template-columns: minmax(280px, 320px) minmax(0, 1fr);
        inset: 8px;
        padding: 8px;
        position: fixed;
        z-index: 9999;
    }

    .plantilla-designer.is-maximized .plantilla-workspace-sidebar {
        height: 100%;
        max-height: none;
        min-height: 0;
        position: static;
    }

    .plantilla-designer.is-maximized .plantilla-canvas-panel {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .plantilla-designer.is-maximized .plantilla-canvas-wrap {
        flex: 1 1 auto;
        min-height: 0;
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

    .plantilla-field-help {
        color: #64748b;
        display: block;
        font-size: 11px;
        font-weight: 400;
        line-height: 1.35;
        margin-top: 3px;
        text-align: left;
        text-transform: none;
    }

    .plantilla-toolbar {
        align-items: center;
        border-bottom: 1px solid #dbe4ee;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 8px 10px;
    }

    .plantilla-toolbar-group {
        align-items: center;
        display: inline-flex;
        gap: 3px;
    }

    .plantilla-action-btn.plantilla-icon-btn {
        flex: 0 0 34px;
        font-size: 13px;
        height: 34px;
        min-height: 34px;
        padding: 0;
        width: 34px;
    }

    .plantilla-toolbar-separator {
        background: #dbe4ee;
        height: 30px;
        width: 1px;
    }

    .plantilla-toolbar-select {
        min-height: 32px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #ffffff;
        color: #334155;
        padding: 5px 28px 5px 9px;
        font-size: 12px;
        font-weight: 800;
        max-width: 118px;
    }

    .plantilla-toolbar-export {
        max-width: 105px;
    }

    .plantilla-toolbar-select.is-grid-control {
        max-width: 82px;
    }

    .plantilla-grid-btn,
    .plantilla-snap-btn {
        gap: 6px;
        white-space: nowrap;
    }

    .plantilla-grid-btn {
        min-width: 100px;
    }

    .plantilla-snap-btn {
        min-width: 72px;
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

    .plantilla-zoom-value,
    .plantilla-zoom-fit {
        background: #ffffff;
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
        min-width: 54px;
        padding: 0 9px;
        text-align: center;
        width: auto !important;
    }

    .plantilla-zoom-fit {
        color: #047857;
        min-width: 62px;
    }

    .plantilla-canvas-wrap {
        background: #eef4f8;
        overflow: auto;
        padding: 34px 30px 20px 42px;
    }

    .plantilla-canvas-stage {
        background: #ffffff;
        height: 1056px;
        margin: 0 auto;
        position: relative;
        width: 816px;
    }

    .plantilla-canvas-stage .canvas-container {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.1);
        overflow: hidden;
    }

    /* Indica con claridad dónde se colocará el campo que se está arrastrando. */
    .plantilla-canvas-stage.is-drop-target .canvas-container {
        box-shadow: 0 0 0 3px rgba(13, 148, 136, .3), 0 14px 32px rgba(15, 23, 42, .1);
    }

    .plantilla-canvas {
        display: block;
    }

    /* Estas guías aparecen al alinear objetos y no forman parte del certificado. */
    .plantilla-alignment-guide {
        background: #2563eb;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, .75);
        display: none;
        pointer-events: none;
        position: absolute;
        z-index: 5;
    }

    .plantilla-alignment-guide.is-visible {
        display: block;
    }

    .plantilla-alignment-guide.is-vertical {
        top: 0;
        width: 2px;
    }

    .plantilla-alignment-guide.is-horizontal {
        height: 2px;
        left: 0;
    }

    /* La cuadrícula es una guía visual; nunca forma parte del certificado guardado. */
    .plantilla-canvas-stage.is-grid::after {
        background-image:
            linear-gradient(rgba(15, 118, 110, 0.14) 1px, transparent 1px),
            linear-gradient(90deg, rgba(15, 118, 110, 0.14) 1px, transparent 1px);
        background-size: var(--plantilla-grid-step, 10px) var(--plantilla-grid-step, 10px);
        content: '';
        inset: 0;
        pointer-events: none;
        position: absolute;
        z-index: 2;
    }

    .plantilla-canvas-stage.is-grid.is-grid-points::after {
        background-image: radial-gradient(circle, rgba(15, 118, 110, 0.28) 1px, transparent 1.25px);
        background-position: 0 0;
    }

    /* Las reglas usan las mismas unidades del lienzo y acompañan el nivel de zoom. */
    .plantilla-ruler {
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #64748b;
        font-size: 8px;
        overflow: hidden;
        pointer-events: none;
        position: absolute;
        z-index: 7;
    }

    .plantilla-ruler.is-horizontal {
        background-image:
            linear-gradient(to right, #94a3b8 1px, transparent 1px),
            linear-gradient(to right, #475569 1px, transparent 1px);
        background-position: left bottom;
        background-repeat: repeat-x;
        background-size: var(--ruler-step, 10px) 5px, var(--ruler-major-step, 50px) 10px;
        height: 22px;
        left: 0;
        top: -24px;
    }

    .plantilla-ruler.is-vertical {
        background-image:
            linear-gradient(to bottom, #94a3b8 1px, transparent 1px),
            linear-gradient(to bottom, #475569 1px, transparent 1px);
        background-position: right top;
        background-repeat: repeat-y;
        background-size: 5px var(--ruler-step, 10px), 10px var(--ruler-major-step, 50px);
        left: -24px;
        top: 0;
        width: 22px;
    }

    .plantilla-ruler span {
        line-height: 10px;
        position: absolute;
    }

    .plantilla-ruler.is-horizontal span {
        top: 1px;
        transform: translateX(2px);
    }

    .plantilla-ruler.is-vertical span {
        left: 2px;
        transform: translateY(2px);
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

    .plantilla-prop-section-title {
        margin-top: 4px;
        border-top: 1px solid #e2e8f0;
        color: #0f766e;
        padding-top: 10px;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
    }

    .plantilla-check-row {
        display: flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
    }

    .plantilla-check-row input[type="checkbox"] {
        width: 15px;
        height: 15px;
        accent-color: #0d9488;
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

    .plantilla-tabla-datos-lista {
        display: grid;
        gap: 8px;
    }

    .plantilla-tabla-dato {
        background: #f8fafc;
        border: 1px solid #dce4ed;
        border-radius: 7px;
        display: grid;
        gap: 8px;
        padding: 9px;
    }

    .plantilla-tabla-dato-encabezado {
        align-items: center;
        color: #334155;
        display: flex;
        font-size: 11px;
        justify-content: space-between;
    }

    .plantilla-tabla-dato-encabezado > div {
        display: flex;
        gap: 4px;
    }

    .plantilla-tabla-dato-btn {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        color: #475569;
        cursor: pointer;
        display: inline-flex;
        font-size: 12px;
        height: 26px;
        justify-content: center;
        width: 28px;
    }

    .plantilla-tabla-dato-btn:hover {
        background: #ecfdf5;
        border-color: #5eead4;
        color: #047857;
    }

    .plantilla-tabla-dato-btn.is-danger {
        color: #dc2626;
    }

    .plantilla-tabla-formato {
        border-top: 1px solid #e2e8f0;
        margin-top: 8px;
        padding-top: 8px;
    }

    .plantilla-tabla-formato summary {
        color: #0f766e;
        cursor: pointer;
        font-size: 11px;
        font-weight: 800;
        list-style: none;
    }

    .plantilla-tabla-formato summary::-webkit-details-marker {
        display: none;
    }

    .plantilla-tabla-formato summary::before {
        content: '▸';
        display: inline-block;
        margin-right: 5px;
    }

    .plantilla-tabla-formato[open] summary::before {
        transform: rotate(90deg);
    }

    .plantilla-tabla-formato-grid {
        display: grid;
        gap: 7px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 8px;
    }

    .plantilla-tabla-formato-grid .plantilla-check-row {
        font-size: 11px;
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

    .plantilla-layer-item {
        align-items: center;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        color: #334155;
        display: flex;
        gap: 6px;
        margin-bottom: 7px;
        padding: 7px 8px;
        text-align: left;
        width: 100%;
    }

    .plantilla-layer-item.is-hidden {
        opacity: .55;
    }

    .plantilla-layer-data {
        min-width: 0;
        flex: 1 1 auto;
    }

    .plantilla-layer-control {
        align-items: center;
        border: 1px solid #dbe4ee;
        border-radius: 6px;
        color: #64748b;
        display: inline-flex;
        flex: 0 0 auto;
        height: 28px;
        justify-content: center;
        width: 28px;
    }

    .plantilla-layer-control:hover {
        background: #f0fdfa;
        border-color: #5eead4;
        color: #0f766e;
    }

    .plantilla-layer-control:disabled {
        cursor: not-allowed;
        opacity: .35;
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

    /* El inspector toma la organización de un editor gráfico, pero conserva el tema claro del sistema. */
    .plantilla-workspace-sidebar,
    .plantilla-canvas-panel {
        background: #ffffff;
        border-color: #d8e1eb;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        color: #172033;
    }

    .plantilla-sidebar-tabs {
        background: #f5f7fa;
        border-color: #d8e1eb;
        gap: 0;
        padding: 0 8px;
    }

    .plantilla-sidebar-tab {
        border: 0;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        color: #64748b;
        font-size: 11px;
        min-height: 42px;
    }

    .plantilla-sidebar-tab:hover {
        background: #eef3f8;
        color: #1e3a5f;
    }

    .plantilla-sidebar-tab.is-active {
        background: transparent;
        border-color: #0f766e;
        box-shadow: none;
        color: #0f5f59;
    }

    .plantilla-sidebar-tab span {
        background: #e4eaf1;
        color: #475569;
        height: 18px;
        min-width: 18px;
    }

    .plantilla-sidebar-content {
        background: #ffffff;
        scrollbar-color: #cbd5e1 #ffffff;
        scrollbar-width: thin;
    }

    .plantilla-sidebar-pane {
        padding: 10px;
    }

    .plantilla-workspace-sidebar .plantilla-search,
    .plantilla-workspace-sidebar .plantilla-prop-input,
    .plantilla-workspace-sidebar .plantilla-prop-select,
    .plantilla-workspace-sidebar .plantilla-prop-textarea {
        background: #ffffff;
        border-color: #cbd5e1;
        border-radius: 6px;
        color: #172033;
        outline: none;
    }

    .plantilla-workspace-sidebar .plantilla-search {
        font-size: 12px;
        margin-bottom: 10px;
        min-height: 36px;
    }

    .plantilla-workspace-sidebar .plantilla-search:focus,
    .plantilla-workspace-sidebar .plantilla-prop-input:focus,
    .plantilla-workspace-sidebar .plantilla-prop-select:focus,
    .plantilla-workspace-sidebar .plantilla-prop-textarea:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 2px rgba(13, 148, 136, .12);
    }

    .plantilla-workspace-sidebar .plantilla-field-group {
        border-color: #dce4ed;
        border-radius: 6px;
        margin-bottom: 7px;
    }

    .plantilla-workspace-sidebar .plantilla-field-title,
    .plantilla-workspace-sidebar .plantilla-field-group[open] .plantilla-field-title {
        background: #f6f8fb;
        color: #334155;
        font-size: 10px;
        padding: 9px 10px;
    }

    .plantilla-workspace-sidebar .plantilla-field-title span:last-child {
        background: #e4eaf1;
        color: #475569;
    }

    .plantilla-workspace-sidebar .plantilla-field-list {
        gap: 2px;
        padding: 4px;
    }

    .plantilla-workspace-sidebar .plantilla-field {
        background: transparent;
        border: 0;
        border-radius: 5px;
        padding: 8px;
    }

    .plantilla-workspace-sidebar .plantilla-field:hover {
        background: #edf9f7;
        box-shadow: inset 2px 0 #0d9488;
    }

    .plantilla-workspace-sidebar .plantilla-field-name,
    .plantilla-workspace-sidebar .plantilla-prop-label,
    .plantilla-workspace-sidebar .plantilla-check-row {
        color: #334155;
    }

    .plantilla-workspace-sidebar .plantilla-prop-label {
        color: #64748b;
        font-size: 10px;
        letter-spacing: .02em;
        margin-bottom: 4px;
    }

    .plantilla-workspace-sidebar .plantilla-prop-grid {
        gap: 8px;
    }

    .plantilla-workspace-sidebar .plantilla-prop-input,
    .plantilla-workspace-sidebar .plantilla-prop-select {
        font-size: 12px;
        min-height: 34px;
    }

    .plantilla-workspace-sidebar .plantilla-prop-textarea {
        font-size: 12px;
        min-height: 118px;
    }

    .plantilla-workspace-sidebar .plantilla-prop-section-title {
        background: #f5f7fa;
        border: 0;
        border-left: 3px solid #0d9488;
        border-radius: 4px;
        color: #1e3a5f;
        font-size: 10px;
        grid-column: 1 / -1;
        letter-spacing: .04em;
        margin: 3px 0 0;
        padding: 6px 8px;
        text-transform: uppercase;
    }

    .plantilla-workspace-sidebar .plantilla-editor-ayuda {
        background: #f8fafc;
        border: 1px solid #dce4ed;
        border-radius: 6px;
        color: #64748b;
        font-size: 11px;
        margin-bottom: 8px;
        padding: 10px;
    }

    .plantilla-workspace-sidebar .plantilla-format-btn,
    .plantilla-workspace-sidebar .plantilla-action-btn {
        background: #ffffff;
        border-color: #cbd5e1;
        border-radius: 6px;
        color: #334155;
    }

    .plantilla-workspace-sidebar .plantilla-format-btn:hover,
    .plantilla-workspace-sidebar .plantilla-action-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    .plantilla-workspace-sidebar .plantilla-format-btn.is-active {
        background: #e6f7f4;
        border-color: #0d9488;
        color: #0f766e;
    }

    .plantilla-workspace-sidebar .plantilla-layer-item {
        background: #ffffff;
        border: 0;
        border-bottom: 1px solid #e6ebf1;
        border-radius: 0;
        color: #334155;
        gap: 8px;
        margin: 0;
        min-height: 50px;
        padding: 6px 5px;
    }

    .plantilla-workspace-sidebar .plantilla-layer-item:hover {
        background: #f6f9fc;
    }

    .plantilla-workspace-sidebar .plantilla-layer-item.is-active {
        background: #eaf7f5;
        box-shadow: inset 3px 0 #0d9488;
        color: #153e3a;
    }

    .plantilla-workspace-sidebar .plantilla-layer-thumbnail {
        background: #f1f5f9;
        border: 1px solid #dbe3ec;
        border-radius: 5px;
        color: #64748b;
        font-size: 11px;
        height: 28px;
        width: 28px;
    }

    .plantilla-workspace-sidebar .plantilla-layer-item.is-active .plantilla-layer-thumbnail {
        background: #ffffff;
        border-color: #99d8d1;
        color: #0f766e;
    }

    .plantilla-workspace-sidebar .plantilla-layer-item strong {
        color: inherit;
        font-size: 11px;
    }

    .plantilla-workspace-sidebar .plantilla-layer-item small {
        color: #7c8a9c;
        font-size: 9px;
    }

    .plantilla-layer-actions {
        align-items: center;
        display: flex;
        gap: 1px;
    }

    .plantilla-workspace-sidebar .plantilla-layer-control {
        background: transparent;
        border-color: transparent;
        border-radius: 5px;
        color: #7c8a9c;
        height: 27px;
        width: 25px;
    }

    .plantilla-workspace-sidebar .plantilla-layer-control:hover {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #0f766e;
    }

    .plantilla-canvas-panel .plantilla-panel-title {
        background: #f8fafc;
        border-color: #d8e1eb;
        color: #1e3a5f;
    }

    .plantilla-canvas-panel .plantilla-toolbar {
        background: #ffffff;
        border-color: #d8e1eb;
        color: #334155;
    }

    .plantilla-canvas-panel .plantilla-action-btn,
    .plantilla-canvas-panel .plantilla-toolbar-select,
    .plantilla-canvas-panel .plantilla-zoom-control,
    .plantilla-canvas-panel .plantilla-zoom-control button,
    .plantilla-canvas-panel .plantilla-zoom-value,
    .plantilla-canvas-panel .plantilla-zoom-fit {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #334155;
    }

    .plantilla-canvas-panel .plantilla-action-btn:hover,
    .plantilla-canvas-panel .plantilla-zoom-control button:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #0f766e;
    }

    .plantilla-canvas-panel .plantilla-action-btn.is-active,
    .plantilla-canvas-panel .plantilla-action-btn.is-primary {
        background: #0f766e;
        border-color: #0f766e;
        color: #ffffff;
    }

    .plantilla-canvas-panel .plantilla-toolbar-separator {
        background: #d8e1eb;
    }

    .plantilla-canvas-panel .plantilla-canvas-wrap {
        background: #e9eef4;
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
        box-sizing: border-box;
        cursor: default;
        overflow: visible;
        pointer-events: none;
        position: absolute;
        white-space: pre-wrap;
    }

    .plantilla-show-only .plantilla-element.is-tabla {
        overflow: hidden;
        white-space: normal;
    }

    .plantilla-word-table {
        border-collapse: collapse;
        height: 100%;
        table-layout: fixed;
        width: 100%;
    }

    .plantilla-word-table th,
    .plantilla-word-table td {
        background: var(--tabla-celdas-fondo, transparent);
        box-sizing: border-box;
        line-height: 1.05;
        max-width: 0;
        overflow: hidden;
        overflow-wrap: anywhere;
        padding: var(--tabla-padding-y, 4px) var(--tabla-padding-x, 6px);
        text-overflow: ellipsis;
        vertical-align: middle;
        white-space: normal;
        word-break: break-word;
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

        .plantilla-workspace-sidebar {
            max-height: none;
            min-height: 0;
            position: static;
        }

        .plantilla-designer.is-maximized {
            grid-template-columns: minmax(260px, 300px) minmax(0, 1fr);
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
