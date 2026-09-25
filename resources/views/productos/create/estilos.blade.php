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
        padding: 14px 18px;
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
        padding: 0;
    }

    /* Los encabezados internos se ocultan porque el encabezado numerado ahora vive arriba, junto al Paso X de 5. */
    .producto-step>.producto-section>.producto-section-head {
        display: none;
    }

    /* El card principal ya contiene el paso; por eso se quita el segundo marco visual interno. */
    .producto-step-grid,
    .producto-step>.producto-section>.grid,
    .producto-step>.producto-section>.space-y-5 {
        padding: 18px 20px !important;
    }

    /* Separación interna del formulario: evita que los campos y sus etiquetas se unan entre columnas. */
    .producto-step-grid {
        column-gap: 18px !important;
        row-gap: 14px !important;
    }

    .producto-step {
        display: none;
    }

    .producto-step.is-active {
        display: block;
    }

    /* Distribución principal del paso Registro y presentación. */
    .producto-registro-presentacion {
        container: producto-registro / inline-size;
        display: grid;
        gap: 20px;
        padding: 20px;
        background: #f8fafc;
    }

    .producto-rp-layout {
        display: grid;
        grid-template-columns: minmax(650px, 1.55fr) minmax(320px, 0.75fr);
        align-items: stretch;
        gap: 20px;
    }

    .producto-rp-formularios {
        display: grid;
        gap: 20px;
        min-width: 0;
    }

    .producto-rp-card {
        min-width: 0;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }

    .producto-rp-card-head {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 16px;
    }

    .producto-rp-card-head h3,
    .producto-rp-listado-head h3 {
        margin: 0;
        color: #0f172a;
        font-size: 15px;
        font-weight: 900;
        line-height: 1.25;
    }

    .producto-rp-card-head p,
    .producto-rp-listado-head p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.35;
    }

    .producto-rp-card-number,
    .producto-rp-card-icon {
        display: inline-flex;
        width: 28px;
        height: 28px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 900;
    }

    .producto-registro-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        align-items: start;
        column-gap: 18px;
        row-gap: 16px;
        padding: 0;
    }

    .producto-registro-grid .md\:col-span-12,
    .producto-registro-grid .producto-campo-catalogo {
        grid-column: 1 / -1;
    }

    .producto-registro-grid-presentacion .producto-campo-cantidad {
        grid-column: span 3;
    }

    .producto-registro-grid-presentacion .producto-campo-unidad {
        grid-column: span 5;
    }

    .producto-registro-grid-presentacion .producto-campo-estado {
        grid-column: span 4;
    }

    .producto-registro-grid .producto-campo-descripcion,
    .producto-registro-grid .producto-campo-acciones {
        grid-column: 1 / -1;
    }

    /* El registro se divide en dos filas para conservar un ancho cómodo en cada control. */
    .producto-registro-grid-autorizacion {
        grid-template-columns: repeat(12, minmax(0, 1fr));
    }

    .producto-registro-grid-autorizacion .producto-campo-codigo,
    .producto-registro-grid-autorizacion .producto-campo-lote,
    .producto-registro-grid-autorizacion .producto-campo-fecha {
        grid-column: span 4;
    }

    .producto-registro-grid-autorizacion .producto-campo-cantidad {
        grid-column: span 3;
    }

    .producto-registro-grid-autorizacion .producto-campo-unidad {
        grid-column: span 5;
    }

    .producto-registro-grid-autorizacion .producto-campo-estado {
        grid-column: span 4;
    }

    .producto-campo-acciones {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        min-height: 44px;
    }

    .producto-field-label-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 6px;
    }

    .producto-field-label-row .producto-field-label {
        margin-bottom: 0;
    }

    .producto-field-link {
        flex: 0 0 auto;
        margin-left: auto;
        color: #1d4ed8;
        font-size: 10px;
        font-weight: 800;
        line-height: 1.35;
    }

    .producto-field-link:hover {
        color: #1e40af;
        text-decoration: underline;
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

    .producto-inline-head.is-teal strong {
        color: #0f766e;
    }

    .producto-field-label {
        display: block;
        min-height: 18px;
        margin-bottom: 6px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 800;
    }

    /* Mantiene juntos el nombre del campo y la acción para crear un registro del catálogo. */
    .producto-field-head {
        display: flex;
        min-height: 20px;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 5px;
    }

    .producto-field-link {
        flex: 0 0 auto;
        border: 0;
        background: transparent;
        color: #0f766e;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .producto-field-link:hover {
        color: #134e4a;
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
        height: 42px;
        min-height: 42px;
        padding: 9px 11px;
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

    .producto-search-native-select {
        display: none;
    }

    .producto-ingredient-select {
        position: relative;
        width: 100%;
    }

    .producto-catalog-search .producto-ingredient-trigger {
        min-height: 42px;
        padding: 8px 11px;
    }

    .producto-catalog-search .producto-ingredient-trigger-text strong {
        font-size: 14px;
        font-weight: 400;
        line-height: 20px;
    }

    .producto-search-selected-detail {
        display: block;
        margin-top: 1px;
        color: #64748b;
        font-size: 11px;
        font-weight: 400;
        line-height: 15px;
    }

    .producto-catalog-search .producto-ingredient-search {
        min-height: 42px;
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

    .producto-ingredient-select:has(.producto-search-clear:not([hidden])) .producto-ingredient-trigger-text {
        padding-right: 30px;
    }

    .producto-search-clear {
        position: absolute;
        z-index: 2;
        top: 7px;
        right: 32px;
        display: inline-flex;
        width: 26px;
        height: 26px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 6px;
        background: #ffffff;
        color: #94a3b8;
        cursor: pointer;
    }

    .producto-search-clear[hidden],
    .producto-ingredient-select.is-open > .producto-search-clear {
        display: none;
    }

    .producto-search-clear:hover,
    .producto-search-clear:focus-visible {
        background: #f1f5f9;
        color: #dc2626;
        outline: none;
    }

    .producto-ingredient-trigger:hover,
    .producto-ingredient-select.is-open .producto-ingredient-trigger {
        border-color: #0d9488;
        box-shadow: none;
    }

    .producto-ingredient-select.is-open .producto-ingredient-trigger {
        visibility: hidden;
    }

    .producto-ingredient-select.is-invalid .producto-ingredient-trigger {
        border-color: #dc2626;
        box-shadow: none;
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

    .producto-ingredient-trigger i {
        color: #64748b;
        font-size: 12px;
    }

    .producto-ingredient-dropdown {
        position: absolute;
        z-index: 80;
        top: 0;
        left: 0;
        right: 0;
        display: none;
        overflow: hidden;
        border: 1px solid #0d9488;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: none;
    }

    .producto-ingredient-select.is-open .producto-ingredient-dropdown {
        display: block;
    }

    .producto-ingredient-search {
        display: flex;
        min-height: 40px;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
        border-bottom: 1px solid #e2e8f0;
        color: #64748b;
        background: #ffffff;
    }

    .producto-ingredient-search input {
        min-width: 0;
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        color: #0f172a;
        font-size: 12px;
        box-shadow: none;
    }

    .producto-ingredient-search input:focus {
        border: 0;
        outline: 0;
        box-shadow: none;
    }

    .producto-ingredient-search i {
        flex: 0 0 auto;
        font-size: 12px;
    }

    .producto-search-close {
        display: inline-flex;
        width: 26px;
        height: 26px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #64748b;
        cursor: pointer;
    }

    .producto-search-close:hover,
    .producto-search-close:focus-visible {
        background: #f1f5f9;
        color: #0f766e;
        outline: none;
    }

    .producto-ingredient-options {
        position: static;
        display: block;
        max-height: 220px;
        overflow-y: auto;
        border: 0;
        background: #ffffff;
    }

    .producto-ingredient-option {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border: 0;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 7px 11px;
        text-align: left;
        transition: background 160ms ease;
    }

    .producto-ingredient-option:last-child {
        border-bottom: 0;
    }

    .producto-ingredient-option:hover,
    .producto-ingredient-option.is-selected {
        background: #f0fdfa;
    }

    .producto-ingredient-option strong {
        display: block;
        overflow: hidden;
        color: #0f172a;
        font-size: 12px;
        font-weight: 900;
        line-height: 1.2;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .producto-ingredient-option-copy {
        min-width: 0;
    }

    .producto-ingredient-option-detail {
        display: block;
        overflow: hidden;
        margin-top: 2px;
        color: #64748b;
        font-size: 11px;
        font-weight: 400;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .producto-ingredient-option-check {
        flex: 0 0 auto;
        color: #0d9488;
        font-size: 13px;
    }

    .producto-ingredient-empty {
        padding: 14px 12px;
        color: #64748b;
        font-size: 12px;
        text-align: center;
    }

    .producto-input.is-invalid,
    .producto-select.is-invalid,
    .producto-textarea.is-invalid,
    .producto-table-etiqueta-cell.is-invalid,
    .producto-etiqueta-preview.is-invalid {
        border-color: #dc2626;
        box-shadow: none;
    }

    .producto-field-error {
        margin-top: 6px;
        color: #dc2626;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.35;
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

    /* La tabla de ingredientes es compacta porque cada fila contiene un solo dato por columna. */
    .producto-table-ingredientes {
        min-width: 640px;
        table-layout: fixed;
    }

    .producto-table-ingredientes th,
    .producto-table-ingredientes td {
        padding: 9px 12px;
        vertical-align: middle;
    }

    .producto-table-ingredientes th:first-child,
    .producto-table-ingredientes td:first-child {
        width: 58px;
        text-align: center;
    }

    .producto-table-ingredientes th:nth-child(2) {
        width: 44%;
    }

    .producto-table-ingredientes th:nth-child(3),
    .producto-table-ingredientes td:nth-child(3) {
        width: 150px;
        text-align: center;
    }

    .producto-table-ingredientes th:nth-child(4),
    .producto-table-ingredientes td:nth-child(4) {
        width: 130px;
        text-align: center;
    }

    .producto-table-ingredientes th:last-child,
    .producto-table-ingredientes td:last-child {
        width: 120px;
        text-align: center;
    }

    .producto-table-ingredientes .producto-ingredient-name {
        font-weight: 700;
    }

    .producto-table-ingredientes .producto-ingredient-percentage {
        font-variant-numeric: tabular-nums;
    }

    .producto-table-ingredientes .producto-pill {
        padding: 3px 8px;
        font-size: 10px;
    }

    .producto-table-ingredientes .producto-btn-danger {
        min-height: 26px;
        padding: 4px 8px;
    }

    .producto-table-ingredientes tfoot td {
        width: auto !important;
        border-top: 1px solid #cbd5e1;
        background: #f8fafc;
        padding: 11px 14px;
        text-align: left;
    }

    .producto-ingredientes-total-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .producto-ingredientes-total-head strong {
        color: #0f172a;
        font-size: 12px;
        font-variant-numeric: tabular-nums;
    }

    .producto-ingredientes-progress {
        height: 4px;
        margin-top: 8px;
        overflow: hidden;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .producto-ingredientes-progress span {
        display: block;
        width: 0;
        height: 100%;
        border-radius: inherit;
        background: #10b981;
        transition: width 180ms ease;
    }

    .producto-ingredientes-progress span.is-over-limit {
        background: #ef4444;
    }

    .producto-ingredientes-alert {
        margin: 7px 0 0;
        color: #b91c1c;
        font-size: 11px;
        font-weight: 700;
    }

    .producto-field-help {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.35;
    }

    .producto-rp-listado {
        padding: 0;
        overflow: hidden;
    }

    .producto-rp-listado-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 18px;
        border-bottom: 1px solid #e2e8f0;
    }

    .producto-rp-listado-tools {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .producto-table-filter,
    .producto-table-search {
        display: flex;
        min-height: 36px;
        align-items: center;
        gap: 7px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        padding: 0 10px;
        color: #475569;
    }

    .producto-table-filter select,
    .producto-table-search input {
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        color: #334155;
        font-size: 11px;
        font-weight: 700;
    }

    .producto-table-search {
        width: min(230px, 30vw);
    }

    .producto-table-search input {
        width: 100%;
    }

    .producto-table-registros-presentaciones {
        width: 100%;
        min-width: 1120px;
        table-layout: auto;
        border: 0;
    }

    .producto-table-registros-presentaciones th,
    .producto-table-registros-presentaciones td {
        border-right: 1px solid #e2e8f0;
        white-space: normal;
    }

    .producto-table-registros-presentaciones th:last-child,
    .producto-table-registros-presentaciones td:last-child {
        border-right: 0;
    }

    .producto-table-registros-presentaciones th {
        background: #f8fafc;
        color: #334155;
        padding: 10px 9px;
        font-size: 10px;
        font-weight: 900;
        text-transform: none;
    }

    .producto-table-registros-presentaciones td {
        padding: 10px 9px;
        color: #334155;
        font-size: 11px;
        vertical-align: middle;
    }

    .producto-table-registros-presentaciones tbody tr:hover td {
        background: #f8fbff;
    }

    .producto-table-registros-presentaciones th:first-child,
    .producto-table-registros-presentaciones td:first-child {
        width: 40px;
        text-align: center;
    }

    .producto-table-registros-presentaciones th:nth-child(7),
    .producto-table-registros-presentaciones td:nth-child(7) {
        min-width: 230px;
    }

    .producto-table-registros-presentaciones th:nth-child(8),
    .producto-table-registros-presentaciones td:nth-child(8) {
        width: 112px;
        text-align: center;
    }

    .producto-table-registros-presentaciones th:nth-child(9),
    .producto-table-registros-presentaciones td:nth-child(9) {
        width: 86px;
        text-align: center;
    }

    .producto-table-registros-presentaciones th:nth-child(10),
    .producto-table-registros-presentaciones td:nth-child(10) {
        min-width: 124px;
        text-align: center;
    }

    .producto-table-presentacion {
        display: grid;
        gap: 3px;
    }

    .producto-table-presentacion strong {
        color: #0f172a;
        font-size: 11px;
        line-height: 1.35;
    }

    .producto-table-presentacion .producto-table-descripcion {
        color: #64748b;
        display: block;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.35;
    }

    .producto-table-status {
        display: inline-flex !important;
        width: fit-content;
        align-items: center;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857 !important;
        padding: 4px 8px;
        font-size: 12px !important;
        font-weight: 900 !important;
        line-height: 1 !important;
        text-transform: none;
    }

    .producto-table-status::before {
        width: 6px;
        height: 6px;
        margin-right: 5px;
        border-radius: 999px;
        background: currentColor;
        content: '';
    }

    .producto-table-status.is-inactive {
        border-color: #e2e8f0;
        background: #f1f5f9;
        color: #64748b !important;
    }

    .producto-row-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .producto-action-icon {
        display: inline-flex;
        width: 32px;
        height: 32px;
        min-height: 32px;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        border-radius: 7px;
        background: #ffffff;
        color: #1d4ed8;
        padding: 0;
        font-size: 11px;
        font-weight: 800;
        transition: all 160ms ease;
    }

    .producto-action-icon.is-view:hover {
        background: #eff6ff;
    }

    .producto-action-icon.is-edit {
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .producto-action-icon.is-edit:hover {
        background: #eff6ff;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.10);
    }

    .producto-action-icon.is-delete {
        border: 1px solid #f87171;
        color: #dc2626;
    }

    .producto-action-icon.is-delete:hover {
        background: #fff1f2;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.12);
    }

    .producto-table-review {
        min-width: 760px;
    }

    .producto-table-review .producto-btn {
        display: none;
    }

    /* El paso de revisión necesita margen propio porque no utiliza la cuadrícula de los formularios anteriores. */
    .producto-review-panel {
        padding: 20px;
    }

    .producto-review-intro {
        border-bottom: 1px solid #e2e8f0;
        padding: 0 0 16px;
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
        gap: 16px;
        margin-top: 16px;
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
        padding: 13px 16px;
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
        padding: 10px 16px;
    }

    .producto-review-row {
        display: grid;
        grid-template-columns: minmax(165px, 205px) minmax(0, 1fr);
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
        overflow-wrap: break-word;
        word-break: normal;
    }

    .producto-review-new-catalog {
        grid-column: 1 / -1;
        margin: 8px 0;
        overflow: hidden;
        border: 1px solid #99f6e4;
        border-radius: 10px;
        background: #ffffff;
    }

    .producto-review-new-catalog>header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
        padding: 10px 12px;
        border-bottom: 1px solid #ccfbf1;
        background: #f0fdfa;
    }

    .producto-review-new-catalog>header>div {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .producto-review-new-catalog h5,
    .producto-review-new-catalog p {
        margin: 0;
    }

    .producto-review-new-catalog h5 {
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
    }

    .producto-review-new-catalog>header p {
        color: #0f766e;
        font-size: 11px;
        font-weight: 700;
    }

    .producto-review-new-badge {
        flex: 0 0 auto;
        border-radius: 999px;
        background: #0d9488;
        color: #ffffff;
        padding: 3px 8px;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .producto-review-new-catalog>dl {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 24px;
        margin: 0;
        padding: 4px 12px 8px;
    }

    .producto-review-new-catalog>dl>div {
        display: grid;
        grid-template-columns: minmax(165px, 205px) minmax(0, 1fr);
        gap: 10px;
        align-items: baseline;
        border-bottom: 1px solid #f1f5f9;
        padding: 8px 0;
    }

    .producto-review-new-catalog dt {
        color: #64748b;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .producto-review-new-catalog dd {
        margin: 0;
        color: #0f172a;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.45;
        overflow-wrap: break-word;
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

    .producto-review-overview {
        align-items: center;
        background: linear-gradient(110deg, #ecfdf5, #f0fdfa 55%, #ffffff);
        border: 1px solid #a7f3d0;
        border-radius: 12px;
        display: flex;
        gap: 24px;
        justify-content: space-between;
        padding: 17px 18px;
    }

    .producto-review-overview-identity {
        display: grid;
        gap: 8px;
        min-width: 0;
    }

    .producto-review-overview-identity > span,
    .producto-review-eyebrow {
        color: #0f766e;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .producto-review-product-data {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(130px, .55fr);
        gap: 10px;
        margin: 0;
    }

    .producto-review-product-data div {
        min-width: 0;
        border-left: 3px solid #34d399;
        padding-left: 9px;
    }

    .producto-review-product-data dt {
        color: #64748b;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .producto-review-product-data dd {
        margin: 3px 0 0;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .producto-review-overview > dl {
        display: flex;
        gap: 8px;
        margin: 0;
    }

    .producto-review-overview > dl div {
        background: rgba(255, 255, 255, .82);
        border: 1px solid #d1fae5;
        border-radius: 8px;
        min-width: 92px;
        padding: 8px 11px;
        text-align: center;
    }

    .producto-review-overview dt {
        color: #64748b;
        font-size: 9px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .producto-review-overview dd {
        color: #047857;
        font-size: 18px;
        font-weight: 900;
        margin: 2px 0 0;
    }

    .producto-review-presentations {
        display: grid;
        gap: 16px;
        grid-column: 1 / -1;
        margin: 6px 0;
        width: 100%;
    }

    .producto-review-presentation {
        border: 1px solid #dbe4ee;
        border-radius: 12px;
        background: #ffffff;
        overflow: hidden;
    }

    .producto-review-presentation-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 12px 16px;
    }

    .producto-review-presentation-main {
        display: grid;
        grid-template-columns: 170px minmax(0, 1fr);
        gap: 18px;
        padding: 16px;
    }

    .producto-review-visual {
        display: grid;
        align-content: start;
        gap: 7px;
    }

    .producto-review-block-label,
    .producto-review-presentation-data dt {
        color: #64748b;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .producto-review-image {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        display: flex;
        justify-content: center;
        min-height: 170px;
        overflow: hidden;
        padding: 10px;
        position: relative;
    }

    .producto-review-image img {
        height: 148px;
        object-fit: contain;
        transition: transform 160ms ease;
        width: 100%;
    }

    button.producto-review-image:hover img {
        transform: scale(1.04);
    }

    .producto-review-image > span {
        align-items: center;
        background: rgba(15, 23, 42, .72);
        border-radius: 999px;
        bottom: 10px;
        color: #ffffff;
        display: inline-flex;
        height: 28px;
        justify-content: center;
        position: absolute;
        right: 10px;
        width: 28px;
    }

    .producto-review-image.is-empty {
        color: #94a3b8;
        flex-direction: column;
        font-size: 22px;
        gap: 7px;
    }

    .producto-review-image.is-empty span {
        background: transparent;
        color: #64748b;
        font-size: 10px;
        height: auto;
        position: static;
        width: auto;
    }

    .producto-review-presentation-content {
        min-width: 0;
    }

    .producto-review-presentation-head h5 {
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
        margin: 3px 0 0;
    }

    .producto-review-presentation-data {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 18px;
        margin: 0;
    }

    .producto-review-presentation-data div {
        min-width: 0;
        border-bottom: 1px solid #f1f5f9;
        padding: 3px 0 9px;
    }

    .producto-review-presentation-data dd {
        margin: 3px 0 0;
        color: #0f172a;
        font-size: 12px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .producto-review-status {
        background: #ecfdf5;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        color: #047857;
        font-size: 9px;
        font-weight: 900;
        padding: 4px 8px;
    }

    .producto-review-status.is-inactive {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #64748b;
    }

    .producto-review-description {
        background: #f8fafc;
        border-radius: 6px;
        margin-top: 12px;
        padding: 9px 10px;
    }

    .producto-review-description span,
    .producto-review-register-row dt {
        color: #64748b;
        font-size: 9px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .producto-review-description p {
        color: #334155;
        font-size: 11px;
        font-weight: 700;
        margin: 2px 0 0;
    }

    .producto-review-registers {
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 14px 16px 16px;
    }

    .producto-review-registers-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .producto-review-registers-head h6 {
        color: #334155;
        font-size: 11px;
        font-weight: 900;
        margin: 0;
        text-transform: uppercase;
    }

    .producto-review-registers-head p {
        color: #64748b;
        font-size: 10px;
        font-weight: 600;
        margin: 2px 0 0;
    }

    .producto-review-registers-head > span {
        background: #dbeafe;
        border-radius: 999px;
        color: #1d4ed8;
        flex: 0 0 auto;
        font-size: 10px;
        font-weight: 900;
        padding: 3px 8px;
    }

    .producto-review-register-list {
        display: grid;
        gap: 8px;
    }

    .producto-review-register-row {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 10px 12px;
    }

    .producto-review-register-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }

    .producto-review-register-head strong {
        color: #0f766e;
        font-size: 11px;
        font-weight: 900;
    }

    .producto-review-register-row dl {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin: 0;
    }

    .producto-review-register-row dl div {
        min-width: 0;
        border-left: 2px solid #e2e8f0;
        padding-left: 8px;
    }

    .producto-review-register-row dd {
        color: #0f172a;
        font-size: 11px;
        font-weight: 800;
        margin: 2px 0 0;
        overflow-wrap: anywhere;
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
        padding: 12px 20px;
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

    .producto-modal-card.is-image {
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

    .producto-upload-input {
        display: none;
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
        border-color: #bfdbfe;
        background: #ffffff;
        color: #1d4ed8;
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

    .producto-table-file-name {
        display: block;
        max-width: 150px;
        margin: 5px auto 0;
        overflow: hidden;
        color: #64748b;
        font-size: 9px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .producto-rp-preview {
        display: flex;
        flex-direction: column;
    }

    .producto-etiqueta-workspace {
        display: flex;
        min-width: 0;
        flex: 1 1 auto;
        flex-direction: column;
        gap: 12px;
    }

    .producto-etiqueta-sidebar {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: 12px;
    }

    .producto-etiqueta-sidebar .producto-etiqueta-info {
        margin-top: 0;
    }

    .producto-etiqueta-controls {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .producto-etiqueta-controls .producto-upload-button {
        width: 100%;
        min-height: 36px;
        justify-content: center;
    }

    .producto-etiqueta-preview,
    .producto-table-image-preview {
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        color: #64748b;
    }

    .producto-etiqueta-preview {
        width: 100%;
        min-height: 315px;
        flex: 1 1 auto;
        padding: 12px;
        cursor: pointer;
    }

    .producto-etiqueta-preview:disabled,
    .producto-table-image-preview:disabled {
        cursor: default;
    }

    .producto-etiqueta-preview img {
        width: 100%;
        height: 100%;
        min-height: 290px;
        max-height: 410px;
        object-fit: contain;
    }

    .producto-etiqueta-preview>span,
    .producto-table-image-preview span {
        padding: 6px;
        font-size: 11px;
        text-align: center;
    }

    .producto-etiqueta-preview>span {
        display: grid;
        justify-items: center;
        gap: 8px;
    }

    /* El mensaje vacío no debe compartir espacio con una imagen ya seleccionada. */
    .producto-etiqueta-preview>span[hidden],
    .producto-table-image-preview span[hidden] {
        display: none !important;
    }

    .producto-etiqueta-preview>span i {
        color: #94a3b8;
        font-size: 34px;
    }

    .producto-etiqueta-preview>span strong {
        color: #475569;
        font-size: 13px;
    }

    .producto-table-ingredientes tbody tr.is-invalid {
        background: #fff7f7;
    }

    .producto-table-field-error {
        margin: 4px 0 0;
        color: #dc2626;
        font-size: 11px;
        font-weight: 700;
    }

    .producto-etiqueta-preview>span small {
        color: #94a3b8;
        font-size: 10px;
    }

    .producto-etiqueta-info {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        margin-top: 12px;
        border-radius: 9px;
        background: #eff6ff;
        color: #1d4ed8;
        padding: 10px 12px;
    }

    .producto-etiqueta-info>i {
        padding-top: 2px;
    }

    .producto-etiqueta-info div {
        display: grid;
        gap: 2px;
        min-width: 0;
    }

    .producto-etiqueta-info span,
    .producto-etiqueta-info small {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .producto-etiqueta-info span {
        color: #1e3a8a;
        font-size: 10px;
        font-weight: 800;
    }

    .producto-etiqueta-info small {
        color: #475569;
        font-size: 9px;
        font-weight: 600;
    }

    .producto-table-etiqueta-cell {
        min-width: 112px;
    }

    .producto-table-image-preview {
        width: 54px;
        height: 48px;
        margin: 0 auto;
        padding: 3px;
        cursor: pointer;
    }

    .producto-table-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .producto-image-preview-modal {
        display: block;
        max-width: 100%;
        max-height: min(72vh, 720px);
        object-fit: contain;
        transform-origin: left top;
        user-select: none;
    }

    .producto-image-preview-viewport {
        background: #f8fafc;
        display: block;
        height: min(72vh, 720px);
        min-height: 320px;
        overflow: auto;
        padding: 18px;
    }

    .producto-image-preview-stage {
        margin: 0 auto;
        position: relative;
    }

    .producto-image-modal-actions,
    .producto-image-zoom-tools {
        align-items: center;
        display: flex;
        gap: 7px;
    }

    .producto-image-zoom-tools {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        gap: 0;
        overflow: hidden;
    }

    .producto-image-zoom-tools button,
    .producto-image-zoom-tools output {
        align-items: center;
        color: #334155;
        display: inline-flex;
        font-size: 12px;
        height: 32px;
        justify-content: center;
        min-width: 34px;
        padding: 0 8px;
    }

    .producto-image-zoom-tools button + button,
    .producto-image-zoom-tools output + button {
        border-left: 1px solid #e2e8f0;
    }

    .producto-image-zoom-tools button:hover {
        background: #ecfdf5;
        color: #0f766e;
    }

    .producto-image-zoom-tools output {
        border-left: 1px solid #e2e8f0;
        color: #0f766e;
        font-weight: 900;
        min-width: 52px;
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

    .producto-modal-actions[hidden] {
        display: none !important;
    }

    /*
     * El menú lateral reduce el espacio real del formulario. Estas reglas observan el ancho
     * del módulo y no el de la ventana, evitando que los campos se compriman dentro del card.
     */
    @container producto-registro (max-width: 1180px) {
        .producto-rp-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .producto-rp-preview {
            display: flex;
            min-height: 0;
        }

        .producto-rp-preview .producto-etiqueta-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 270px);
            align-items: stretch;
        }

        .producto-rp-preview .producto-rp-card-head {
            margin-bottom: 12px;
        }

        .producto-rp-preview .producto-etiqueta-preview {
            min-height: 280px;
        }

        .producto-rp-preview .producto-etiqueta-preview img {
            min-height: 255px;
            max-height: 330px;
        }

        .producto-rp-preview .producto-etiqueta-info {
            margin-top: 0;
        }
    }

    @container producto-registro (max-width: 760px) {
        .producto-registro-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .producto-registro-grid-autorizacion .producto-campo-codigo,
        .producto-registro-grid-autorizacion .producto-campo-lote,
        .producto-registro-grid-autorizacion .producto-campo-fecha,
        .producto-registro-grid-autorizacion .producto-campo-cantidad,
        .producto-registro-grid-autorizacion .producto-campo-unidad,
        .producto-registro-grid-autorizacion .producto-campo-estado {
            grid-column: span 3;
        }

        .producto-registro-grid-presentacion .producto-campo-catalogo {
            grid-column: 1 / -1;
        }

        .producto-registro-grid-presentacion .producto-campo-cantidad,
        .producto-registro-grid-presentacion .producto-campo-unidad,
        .producto-registro-grid-presentacion .producto-campo-estado {
            grid-column: span 2;
        }

        .producto-rp-preview {
            display: flex;
        }

        .producto-rp-preview .producto-etiqueta-workspace {
            display: flex;
        }

        .producto-rp-preview .producto-rp-card-head {
            margin-bottom: 12px;
        }

        .producto-rp-preview .producto-etiqueta-info {
            margin-top: 0;
        }

        .producto-rp-listado-head {
            align-items: stretch;
            flex-direction: column;
        }

        .producto-rp-listado-tools {
            width: 100%;
            flex-wrap: wrap;
        }

        .producto-table-filter,
        .producto-table-search {
            flex: 1 1 220px;
            width: auto;
        }

        /* En tamaños reducidos cada fila pasa a ser una tarjeta legible. */
        .producto-table-registros-presentaciones {
            display: block;
            min-width: 0 !important;
        }

        .producto-table-registros-presentaciones thead {
            display: none;
        }

        .producto-table-registros-presentaciones tbody {
            display: grid;
            gap: 12px;
            padding: 12px;
        }

        .producto-table-registros-presentaciones tbody tr {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
        }

        .producto-table-registros-presentaciones #sinRegistrosPresentacionesProducto {
            display: block;
        }

        .producto-table-registros-presentaciones #sinRegistrosPresentacionesProducto td {
            display: block;
            border-bottom: 0;
        }

        .producto-table-registros-presentaciones #sinRegistrosPresentacionesProducto td::before {
            display: none;
        }

        .producto-table-registros-presentaciones tbody td {
            display: flex;
            min-width: 0;
            align-items: flex-start;
            gap: 8px;
            border-right: 0;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 12px;
            text-align: left !important;
        }

        .producto-table-registros-presentaciones tbody td::before {
            flex: 0 0 78px;
            color: #64748b;
            content: attr(data-label);
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .producto-table-registros-presentaciones tbody td:nth-child(7),
        .producto-table-registros-presentaciones tbody td:nth-child(10) {
            grid-column: 1 / -1;
        }

        .producto-table-registros-presentaciones tbody td:nth-child(10) {
            border-bottom: 0;
        }

        .producto-table-registros-presentaciones .producto-table-presentacion,
        .producto-table-registros-presentaciones .producto-row-actions {
            flex: 1 1 auto;
            min-width: 0;
        }

        .producto-table-registros-presentaciones .producto-table-etiqueta-cell {
            align-items: center;
            flex-wrap: wrap;
        }

        .producto-table-registros-presentaciones .producto-table-image-preview {
            margin: 0;
        }

        .producto-table-registros-presentaciones .producto-table-file-name {
            margin: 0;
        }

        .producto-table-registros-presentaciones .producto-row-actions {
            justify-content: flex-end;
        }
    }

    @container producto-registro (max-width: 520px) {
        .producto-rp-layout,
        .producto-rp-formularios {
            gap: 12px;
        }

        .producto-rp-card {
            padding: 14px;
        }

        .producto-rp-listado {
            padding: 0;
        }

        .producto-registro-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 12px;
        }

        .producto-registro-grid .producto-campo-catalogo,
        .producto-registro-grid-presentacion .producto-campo-cantidad,
        .producto-registro-grid-presentacion .producto-campo-unidad,
        .producto-registro-grid-presentacion .producto-campo-estado,
        .producto-registro-grid .producto-campo-descripcion,
        .producto-registro-grid .producto-campo-acciones,
        .producto-registro-grid-autorizacion .producto-campo-codigo,
        .producto-registro-grid-autorizacion .producto-campo-lote,
        .producto-registro-grid-autorizacion .producto-campo-fecha,
        .producto-registro-grid-autorizacion .producto-campo-cantidad,
        .producto-registro-grid-autorizacion .producto-campo-unidad,
        .producto-registro-grid-autorizacion .producto-campo-estado {
            grid-column: 1 / -1;
        }

        .producto-campo-acciones .producto-btn,
        .producto-rp-listado-tools,
        .producto-table-filter,
        .producto-table-search {
            width: 100%;
        }

        .producto-rp-listado-tools {
            align-items: stretch;
            flex-direction: column;
        }

        .producto-table-registros-presentaciones tbody tr {
            grid-template-columns: minmax(0, 1fr);
        }

        .producto-table-registros-presentaciones tbody td,
        .producto-table-registros-presentaciones tbody td:nth-child(7),
        .producto-table-registros-presentaciones tbody td:nth-child(10) {
            grid-column: 1;
        }

        .producto-table-registros-presentaciones tbody td:last-child {
            border-bottom: 0;
        }
    }

    @media (max-width: 1100px) {
        .producto-layout {
            grid-template-columns: 1fr;
        }

        .producto-side {
            position: static;
        }

        .producto-review-overview {
            align-items: stretch;
            flex-direction: column;
        }

        .producto-review-overview > dl {
            width: 100%;
        }

        .producto-review-overview > dl div {
            flex: 1 1 0;
        }

    }

    @media (max-width: 640px) {

        .producto-review-panel {
            padding: 14px;
        }

        .producto-review-presentation-main {
            grid-template-columns: 1fr;
        }

        .producto-review-image {
            min-height: 210px;
        }

        .producto-review-presentation-data {
            grid-template-columns: 1fr;
        }

        .producto-review-product-data {
            grid-template-columns: 1fr;
        }

        .producto-review-register-row dl {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .producto-review-overview > dl {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .producto-review-overview > dl div {
            min-width: 0;
            padding-inline: 5px;
        }

        .producto-action-bar,
        .producto-form-head {
            padding-inline: 14px;
        }

        .producto-step-grid,
        .producto-step>.producto-section>.grid,
        .producto-step>.producto-section>.space-y-5 {
            padding: 14px !important;
        }

        .producto-step-grid {
            row-gap: 12px !important;
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

        .producto-registro-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .producto-registro-grid .md\:col-span-12,
        .producto-registro-grid .producto-campo-catalogo,
        .producto-registro-grid-presentacion .producto-campo-cantidad,
        .producto-registro-grid-presentacion .producto-campo-unidad,
        .producto-registro-grid-presentacion .producto-campo-estado,
        .producto-registro-grid .producto-campo-descripcion,
        .producto-registro-grid .producto-campo-acciones,
        .producto-registro-grid-autorizacion .producto-campo-codigo,
        .producto-registro-grid-autorizacion .producto-campo-lote,
        .producto-registro-grid-autorizacion .producto-campo-fecha,
        .producto-registro-grid-autorizacion .producto-campo-cantidad,
        .producto-registro-grid-autorizacion .producto-campo-unidad,
        .producto-registro-grid-autorizacion .producto-campo-estado {
            grid-column: 1 / -1;
        }

        .producto-campo-acciones {
            justify-content: stretch;
        }

        .producto-campo-acciones .producto-btn {
            width: 100%;
        }

        .producto-registro-presentacion {
            padding: 10px;
        }

        .producto-rp-card {
            padding: 14px;
        }

        .producto-rp-listado {
            padding: 0;
        }

        .producto-rp-listado-head {
            align-items: stretch;
            flex-direction: column;
        }

        .producto-rp-listado-tools,
        .producto-table-search {
            width: 100%;
        }

        .producto-rp-listado-tools {
            align-items: stretch;
            flex-direction: column;
        }

        .producto-etiqueta-preview {
            min-height: 260px;
        }

        .producto-etiqueta-preview img {
            min-height: 235px;
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

        .producto-review-new-catalog>header {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }

        .producto-review-new-catalog>dl {
            grid-template-columns: 1fr;
        }

        .producto-review-new-catalog>dl>div {
            grid-template-columns: 1fr;
            gap: 3px;
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
