<style>
    /*
    Estilos propios de esta vista.
    Se usan nombres producto-* para no afectar el wizard de personas.
    */

    .producto-wizard {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .producto-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .producto-header {
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .producto-header::before {
        content: "";
        position: absolute;
        inset-inline: 0;
        top: 0;
        height: 4px;
        background: #0d9488;
    }

    .producto-stepper-card {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 22px 24px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .producto-stepper {
        position: relative;
        display: flex;
        min-width: 760px;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .producto-stepper::before {
        content: "";
        position: absolute;
        left: 52px;
        right: 52px;
        top: 20px;
        height: 2px;
        background: #e2e8f0;
    }

    .producto-burbuja {
        position: relative;
        z-index: 1;
        display: flex;
        width: 136px;
        cursor: pointer;
        flex-direction: column;
        align-items: center;
        gap: 9px;
        border: 0;
        background: transparent;
        color: #64748b;
        text-align: center;
    }

    .producto-circulo {
        display: flex;
        width: 42px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border: 2px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
        color: #64748b;
        font-size: 14px;
        font-weight: 800;
        transition: all 160ms ease;
    }

    .producto-burbuja span:last-child {
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
    }

    .producto-burbuja.is-active .producto-circulo {
        border-color: #0d9488;
        background: #0d9488;
        color: #ffffff;
        box-shadow: 0 0 0 6px #ccfbf1;
    }

    .producto-burbuja.is-completed .producto-circulo {
        border-color: #10b981;
        background: #10b981;
        color: #ffffff;
    }

    .producto-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 20px;
        align-items: start;
    }

    .producto-form-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-bottom: 1px solid #e2e8f0;
        padding: 18px 20px;
        background: linear-gradient(90deg, #eff6ff, #f0f9ff);
    }

    .producto-form-head.is-teal {
        background: linear-gradient(90deg, #f0fdfa, #ecfeff);
        border-bottom-color: #99f6e4;
    }

    .producto-form-head.is-amber {
        background: linear-gradient(90deg, #fffbeb, #fff7ed);
        border-bottom-color: #fde68a;
    }

    .producto-form-head-left {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .producto-form-icon {
        display: flex;
        width: 36px;
        height: 36px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #2563eb;
        color: #ffffff;
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.18);
    }

    .producto-form-head.is-teal .producto-form-icon {
        background: #14b8a6;
    }

    .producto-form-head.is-amber .producto-form-icon {
        background: #d97706;
    }

    .producto-form-title {
        margin: 0;
        color: #1d4ed8;
        font-size: 17px;
        font-weight: 800;
    }

    .producto-form-head.is-teal .producto-form-title {
        color: #0f766e;
    }

    .producto-form-head.is-amber .producto-form-title {
        color: #b45309;
    }

    .producto-form-subtitle {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .producto-body {
        padding: 20px;
    }

    /* Los encabezados internos se ocultan porque el encabezado numerado ahora vive arriba, junto al Paso X de 5. */
    .producto-step>.producto-section>.producto-section-head {
        display: none;
    }

    /* El card principal ya contiene el paso; por eso se quita el segundo marco visual interno. */
    .producto-step>.producto-section>.grid,
    .producto-step>.producto-section>.space-y-5 {
        padding: 0 !important;
    }

    .producto-step {
        display: none;
    }

    .producto-step.is-active {
        display: block;
    }

    .producto-step-compose {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 0;
    }

    .producto-form-panel {
        display: flex;
        flex-direction: column;
        gap: 12px;
        border: 0;
        border-radius: 0;
        background: transparent;
        padding: 0;
    }

    .producto-form-panel-separated {
        margin-top: 8px;
        padding-top: 4px;
    }

    .producto-inline-head {
        display: flex;
        min-height: 32px;
        align-items: center;
        gap: 8px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 8px;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
    }

    .producto-inline-head span {
        display: inline-flex;
        width: 26px;
        height: 26px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        font-size: 12px;
    }

    .producto-inline-head.is-amber span {
        background: #fffbeb;
        color: #b45309;
    }

    .producto-inline-head.is-teal span {
        background: #f0fdfa;
        color: #0f766e;
    }

    .producto-field-label {
        display: block;
        margin-bottom: 5px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 800;
    }

    .producto-input,
    .producto-select,
    .producto-textarea {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        font-size: 14px;
        outline: none;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .producto-input,
    .producto-select {
        height: 40px;
        min-height: 40px;
        padding: 8px 11px;
    }

    .producto-textarea {
        min-height: 92px;
        padding: 10px 12px;
        resize: vertical;
    }

    .producto-input:focus,
    .producto-select:focus,
    .producto-textarea:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px #ccfbf1;
    }

    .producto-ingredient-native-select {
        display: none;
    }

    .producto-ingredient-select {
        position: relative;
        width: 100%;
    }

    .producto-ingredient-trigger {
        display: flex;
        width: 100%;
        min-height: 40px;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        padding: 4px 10px;
        text-align: left;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .producto-ingredient-trigger:hover,
    .producto-ingredient-select.is-open .producto-ingredient-trigger {
        border-color: #0d9488;
        box-shadow: none;
    }

    .producto-ingredient-select.is-invalid .producto-ingredient-trigger {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px #fee2e2;
    }

    .producto-ingredient-trigger-text {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 1px;
    }

    .producto-ingredient-trigger-text strong {
        color: #0f172a;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.15;
    }

    .producto-ingredient-trigger-text small {
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.2;
    }

    .producto-ingredient-trigger i {
        color: #64748b;
        font-size: 12px;
    }

    .producto-ingredient-options {
        position: absolute;
        z-index: 80;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        display: none;
        max-height: 270px;
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: none;
    }

    .producto-ingredient-select.is-open .producto-ingredient-options {
        display: block;
    }

    .producto-ingredient-option {
        display: block;
        width: 100%;
        border: 0;
        border-left: 4px solid transparent;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 7px 11px 7px 8px;
        text-align: left;
        transition: background 160ms ease, border-color 160ms ease;
    }

    .producto-ingredient-option:last-child {
        border-bottom: 0;
    }

    .producto-ingredient-option:hover,
    .producto-ingredient-option.is-selected {
        background: #f0fdfa;
        border-left-color: #10b981;
    }

    .producto-ingredient-option strong {
        display: block;
        color: #0f172a;
        font-size: 12px;
        font-weight: 900;
        line-height: 1.2;
    }

    .producto-ingredient-option small {
        display: block;
        margin-top: 2px;
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.25;
    }

    .producto-input.is-invalid,
    .producto-select.is-invalid,
    .producto-textarea.is-invalid,
    .producto-upload-card.is-invalid {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px #fee2e2;
    }

    .producto-field-error {
        margin-top: 5px;
        color: #dc2626;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.25;
    }

    .producto-section {
        overflow: visible;
        border: 0;
        border-radius: 0;
        background: transparent;
    }

    .producto-section-head {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff, #f0f9ff);
        padding: 12px 16px;
    }

    .producto-section-head.is-teal {
        border-color: #99f6e4;
        background: linear-gradient(90deg, #f0fdfa, #ecfeff);
    }

    .producto-section-head.is-amber {
        border-color: #fde68a;
        background: linear-gradient(90deg, #fffbeb, #fff7ed);
    }

    /* Numero del encabezado: replica el cuadro usado en tipos de certificado y certificados. */
    .producto-section-number {
        display: flex;
        width: 36px;
        height: 36px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #2563eb;
        color: #ffffff;
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.18);
    }

    .producto-section-head.is-teal .producto-section-number {
        background: #14b8a6;
    }

    .producto-section-head.is-amber .producto-section-number {
        background: #d97706;
    }

    .producto-section-title {
        margin: 0;
        color: #1d4ed8;
        font-size: 16px;
        font-weight: 800;
    }

    .producto-section-head.is-teal .producto-section-title {
        color: #0f766e;
    }

    .producto-section-head.is-amber .producto-section-title {
        color: #b45309;
    }

    .producto-section-subtitle {
        margin: 2px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .producto-table-wrap {
        width: 100%;
        background: #ffffff;
        -webkit-overflow-scrolling: touch;
    }

    .producto-table {
        width: 100%;
        min-width: 1120px;
        border-collapse: collapse;
        font-size: 13px;
    }

    .producto-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 900;
        padding: 11px 12px;
        text-align: left;
        text-transform: uppercase;
    }

    .producto-table td {
        border-top: 1px solid #e2e8f0;
        color: #0f172a;
        padding: 11px 12px;
        vertical-align: top;
    }

    .producto-table-presentaciones {
        min-width: 980px;
    }

    .producto-table-registros-presentaciones {
        min-width: 1080px;
    }

    .producto-table-presentaciones th,
    .producto-table-presentaciones td,
    .producto-table-registros-presentaciones th,
    .producto-table-registros-presentaciones td {
        white-space: nowrap;
    }

    .producto-table-presentaciones th:nth-child(2),
    .producto-table-presentaciones td:nth-child(2),
    .producto-table-registros-presentaciones th:nth-child(2),
    .producto-table-registros-presentaciones td:nth-child(2) {
        min-width: 180px;
    }

    .producto-table-presentaciones th:nth-child(5),
    .producto-table-presentaciones td:nth-child(5),
    .producto-table-registros-presentaciones th:nth-child(7),
    .producto-table-registros-presentaciones td:nth-child(7) {
        min-width: 240px;
        white-space: normal;
    }

    .producto-table-presentaciones th:nth-child(6),
    .producto-table-presentaciones td:nth-child(6) {
        min-width: 190px;
    }

    .producto-table-review {
        min-width: 760px;
    }

    .producto-table-review .producto-btn {
        display: none;
    }

    .producto-review-intro {
        border-bottom: 1px solid #e2e8f0;
        padding: 2px 0 14px;
    }

    .producto-review-intro h3 {
        margin: 0;
        color: #0f172a;
        font-size: 17px;
        font-weight: 900;
    }

    .producto-review-intro p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .producto-review-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 14px;
    }

    .producto-review-section {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
    }

    .producto-review-section-head {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        padding: 11px 14px;
    }

    .producto-review-section-dot {
        width: 9px;
        height: 9px;
        flex: 0 0 auto;
        margin-top: 5px;
        border-radius: 999px;
        background: #0d9488;
        box-shadow: 0 0 0 4px #ccfbf1;
    }

    .producto-review-section-head h4 {
        margin: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
    }

    .producto-review-section-head p {
        margin: 2px 0 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .producto-review-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 24px;
        margin: 0;
        padding: 8px 14px;
    }

    .producto-review-row {
        display: grid;
        grid-template-columns: minmax(130px, 170px) minmax(0, 1fr);
        gap: 10px;
        align-items: baseline;
        border-bottom: 1px solid #f1f5f9;
        padding: 8px 0;
    }

    .producto-review-row.is-wide {
        grid-column: 1 / -1;
    }

    .producto-review-row dt {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .producto-review-row dd {
        margin: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .producto-review-table-block {
        grid-column: 1 / -1;
        margin: 8px 0 10px;
    }

    .producto-review-table-wrap {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
    }

    .producto-review-table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
    }

    .producto-review-table th {
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        padding: 9px 10px;
        text-align: left;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .producto-review-table td {
        border-bottom: 1px solid #f1f5f9;
        color: #0f172a;
        padding: 10px;
        vertical-align: top;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
    }

    .producto-review-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .producto-review-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        color: #64748b;
        padding: 10px;
        font-size: 12px;
        font-weight: 800;
    }

    .producto-textarea-compact {
        height: auto;
        min-height: 72px;
        resize: vertical;
    }

    .producto-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 900;
    }

    .producto-side {
        position: sticky;
        top: 16px;
        padding: 18px;
    }

    .producto-flow-card {
        border: 1px solid #dbeafe;
        border-radius: 14px;
        background: #f8fbff;
        padding: 14px;
    }

    .producto-flow-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
    }

    .producto-flow-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-top: 12px;
    }

    .producto-flow-item {
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: #ffffff;
        padding: 10px;
    }

    .producto-flow-item strong {
        display: block;
        color: #0f766e;
        font-size: 12px;
        font-weight: 900;
    }

    .producto-flow-item span {
        display: block;
        margin-top: 3px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.35;
    }

    .producto-flow-arrow {
        color: #0f766e;
        font-size: 13px;
        font-weight: 900;
        text-align: center;
    }

    .producto-progress-line {
        height: 9px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .producto-progress-line span {
        display: block;
        height: 100%;
        width: 20%;
        border-radius: inherit;
        background: #0d9488;
        transition: width 180ms ease;
    }

    .producto-action-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-top: 1px solid #e2e8f0;
        padding: 16px 20px;
    }

    /* Grupo inferior del wizard: permite que los botones respiren en pantallas medianas. */
    .producto-action-buttons {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 12px;
    }

    .producto-btn {
        display: inline-flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 10px;
        border: 1px solid transparent;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 800;
        transition: all 160ms ease;
    }

    .producto-btn-primary {
        background: #0d9488;
        color: #ffffff;
    }

    .producto-btn-primary:hover {
        background: #0f766e;
    }

    .producto-btn-secondary {
        border-color: #cbd5e1;
        background: #ffffff;
        color: #334155;
    }

    .producto-btn-secondary:hover {
        border-color: #0d9488;
        color: #0f766e;
    }

    /* Mismo color del boton Guardar avance usado en el formulario de personas. */
    .producto-btn-light {
        border-color: #cbd5e1;
        background: #ffffff;
        color: #475569;
    }

    .producto-btn-light:hover {
        border-color: #94a3b8;
        background: #f8fafc;
        color: #334155;
    }

    .producto-btn-success {
        background: #16a34a;
        color: #ffffff;
    }

    .producto-btn-success:hover {
        background: #15803d;
    }

    .producto-btn-danger {
        min-height: 28px;
        border-color: #e2e8f0;
        border-radius: 7px;
        background: #ffffff;
        color: #b91c1c;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 900;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .producto-btn-danger:hover {
        border-color: #fecaca;
        background: #fff1f2;
        color: #991b1b;
        box-shadow: 0 2px 6px rgba(190, 18, 60, 0.10);
    }

    .producto-btn-orange {
        background: #f97316;
        color: #ffffff;
    }

    .producto-btn-orange:hover {
        background: #ea580c;
    }

    .producto-btn-inline {
        width: auto;
        min-width: 170px;
        min-height: 40px;
        border-radius: 9px;
        padding: 8px 12px;
    }

    .producto-btn-verde {
        background: #28a745;
        color: #ffffff;
    }

    .producto-btn-verde:hover {
        background: #218838;
    }

    .producto-context-card {
        display: none;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid #ccfbf1;
        border-radius: 12px;
        background: #f0fdfa;
        padding: 12px 14px;
    }

    .producto-context-title {
        margin: 0;
        color: #0f766e;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .producto-context-name {
        margin-top: 3px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
    }

    .producto-context-code {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #ffffff;
        color: #0f766e;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 900;
    }

    .producto-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        padding: 22px;
        color: #64748b;
        text-align: center;
        font-size: 13px;
    }

    .producto-modal {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.55);
        padding: 18px;
    }

    .producto-modal.is-open {
        display: flex;
    }

    .producto-modal-card {
        width: min(100%, 520px);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.25);
    }

    .producto-modal-card.is-compact {
        width: min(100%, 480px);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    .producto-modal-card.is-pdf {
        width: min(100%, 920px);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    .producto-modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #ccfbf1;
        background: linear-gradient(90deg, #f0fdfa, #ecfeff);
        padding: 14px 18px;
    }

    .producto-modal-close {
        display: inline-flex;
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        transition: all 160ms ease;
    }

    .producto-modal-close:hover {
        border-color: #0d9488;
        color: #0f766e;
    }

    .producto-pdf-button {
        display: inline-flex;
        min-height: 28px;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #99f6e4;
        border-radius: 7px;
        background: #f0fdfa;
        color: #0f766e;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 900;
        transition: all 160ms ease;
    }

    .producto-pdf-button:hover {
        border-color: #0d9488;
        background: #ccfbf1;
        color: #115e59;
    }

    .producto-upload-card {
        display: flex;
        width: 100%;
        min-height: 42px;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
        padding: 5px 8px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .producto-upload-input {
        display: none;
    }

    .producto-upload-icon {
        display: inline-flex;
        width: 30px;
        height: 30px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #fee2e2;
        color: #b91c1c;
        font-size: 13px;
    }

    .producto-upload-title {
        margin: 0;
        color: #0f172a;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.1;
    }

    .producto-upload-name {
        display: block;
        max-width: 100%;
        overflow: hidden;
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.2;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .producto-upload-actions {
        display: flex;
        flex: 0 0 auto;
        flex-direction: row;
        gap: 6px;
    }

    .producto-upload-button {
        display: inline-flex;
        min-height: 28px;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #ffffff;
        color: #334155;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 900;
        transition: all 160ms ease;
    }

    .producto-upload-button:hover {
        border-color: #0d9488;
        color: #0f766e;
        box-shadow: 0 2px 6px rgba(13, 148, 136, 0.10);
    }

    .producto-upload-button:disabled {
        cursor: not-allowed;
        opacity: 0.55;
        box-shadow: none;
    }

    .producto-upload-button.is-select {
        border-color: #99f6e4;
        background: #f0fdfa;
        color: #0f766e;
    }

    .producto-upload-button.is-view {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .producto-upload-button.is-remove {
        border-color: #fecaca;
        background: #fff1f2;
        color: #be123c;
    }

    .producto-upload-button.is-remove:hover:not(:disabled) {
        border-color: #fca5a5;
        color: #991b1b;
        box-shadow: 0 2px 6px rgba(190, 18, 60, 0.10);
    }

    .producto-upload-card-compact {
        height: 40px;
        min-height: 40px;
        gap: 7px;
        background: #ffffff;
        padding: 4px 6px;
    }

    .producto-upload-card-compact .producto-upload-icon {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        font-size: 12px;
    }

    .producto-upload-card-compact .producto-upload-name {
        flex: 1 1 auto;
        min-width: 0;
        color: #475569;
        font-size: 11px;
    }

    .producto-upload-card-compact .producto-upload-button {
        min-height: 28px;
        padding-inline: 7px;
    }

    .producto-table-file {
        display: inline-flex;
        max-width: 320px;
        align-items: center;
        gap: 8px;
        padding: 0;
    }

    .producto-table-file-input {
        display: inline-flex;
        max-width: 420px;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .producto-table-file-icon {
        display: inline-flex;
        width: 26px;
        height: 26px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        background: #fee2e2;
        color: #b91c1c;
        font-size: 12px;
    }

    .producto-table-file-name {
        max-width: 140px;
        overflow: hidden;
        color: #334155;
        font-size: 11px;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .producto-pdf-frame {
        width: 100%;
        height: min(72vh, 720px);
        border: 0;
        background: #f8fafc;
    }

    .producto-modal-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .producto-modal-icon {
        display: inline-flex;
        width: 34px;
        height: 34px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #0d9488;
        color: #ffffff;
        font-size: 14px;
    }

    .producto-modal-title {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 900;
        line-height: 1.1;
    }

    .producto-modal-subtitle {
        margin-top: 3px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.35;
    }

    .producto-modal-body {
        display: grid;
        gap: 12px;
        padding: 16px 18px;
    }

    .producto-modal-body .producto-input {
        min-height: 40px;
        border-radius: 9px;
        font-size: 13px;
    }

    .producto-modal-body .producto-textarea {
        min-height: 76px;
        border-radius: 9px;
        font-size: 13px;
    }

    .producto-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 12px 18px;
    }

    @media (max-width: 1100px) {
        .producto-layout {
            grid-template-columns: 1fr;
        }

        .producto-side {
            position: static;
        }
    }

    @media (max-width: 640px) {

        .producto-body,
        .producto-action-bar,
        .producto-form-head {
            padding-inline: 14px;
        }

        .producto-action-bar {
            align-items: stretch;
            flex-direction: column;
        }

        .producto-action-bar>div,
        .producto-action-buttons,
        .producto-action-bar .producto-btn {
            width: 100%;
        }

        .producto-action-buttons {
            flex-direction: column;
        }

        /* En movil el cargador PDF se apila ordenado para que los botones no aplasten el nombre del archivo. */
        .producto-upload-card {
            align-items: stretch;
            flex-wrap: wrap;
            min-height: auto;
        }

        .producto-upload-actions {
            width: 100%;
        }

        .producto-upload-button {
            flex: 1 1 0;
        }

        .producto-table-wrap {
            margin-inline: -2px;
        }

        .producto-table {
            min-width: 920px;
            font-size: 12px;
        }

        .producto-review-list {
            grid-template-columns: 1fr;
        }

        .producto-review-row {
            grid-template-columns: 1fr;
            gap: 3px;
        }

        .producto-table-presentaciones {
            min-width: 940px;
        }

        .producto-table-registros-presentaciones {
            min-width: 980px;
        }

        .producto-table th,
        .producto-table td {
            padding: 9px 10px;
        }

        .producto-empty {
            padding: 16px;
            font-size: 12px;
        }
    }
</style>
