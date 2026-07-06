<style>
    /*
        ESTILOS DEL FORMULARIO INICIAR TRAMITE
        Este archivo solo contiene clases usadas por:
        - nuevo_tramite/create.blade.php
        - nuevo_tramite/inicio.blade.php
        - nuevo_tramite/script.blade.php
    */

    .tramite-shell {
        display: grid;
        gap: 14px;
    }

    .tramite-content {
        min-width: 0;
    }

    .tramite-flow-form {
        display: grid;
        gap: 14px;
    }

    /*
        Seccion del formulario.
        overflow visible evita que los menus de los selectores se recorten.
    */
    .tramite-persona-card {
        position: relative;
        overflow: visible;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    .tramite-persona-card:focus-within {
        z-index: 40;
    }

    .tramite-persona-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(90deg, #f0fdfa, #ffffff);
        padding: 12px 14px;
    }

    .tramite-persona-head.is-documents {
        background: linear-gradient(90deg, #ecfdf5, #ffffff);
    }

    .tramite-persona-head-left {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 10px;
    }

    .tramite-persona-icon {
        display: inline-flex;
        width: 32px;
        height: 32px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #0d9488;
        color: #ffffff;
        font-size: 13px;
    }

    .tramite-persona-icon.is-documents {
        background: #059669;
    }

    .tramite-persona-title {
        margin: 0;
        color: #0f172a;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.2;
    }

    .tramite-persona-subtitle {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.35;
    }

    .tramite-persona-body {
        padding: 16px;
    }

    /*
        Franja de usuario que registra.
        No participa en el guardado; solo orienta al funcionario.
    */
    .tramite-registro-strip {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        border: 1px solid #dbeafe;
        border-radius: 8px;
        background: #f8fbff;
        padding: 9px 11px;
        color: #334155;
        font-size: 12px;
    }

    .tramite-registro-icon {
        display: inline-flex;
        width: 26px;
        height: 26px;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        background: #e0f2fe;
        color: #0369a1;
    }

    .tramite-registro-label {
        color: #64748b;
        font-weight: 800;
    }

    .tramite-registro-strip strong {
        color: #0f172a;
        font-weight: 900;
    }

    .tramite-registro-separator {
        width: 1px;
        height: 18px;
        background: #cbd5e1;
    }

    .tramite-registro-role {
        color: #475569;
        font-weight: 800;
    }

    /*
        Grilla del formulario.
        Los campos principales se acomodan en dos columnas y bajan a una columna en movil.
    */
    .tramite-fields {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 14px 16px;
    }

    .tramite-field-6 {
        grid-column: span 6;
    }

    .tramite-field-12 {
        grid-column: span 12;
    }

    .tramite-inicio-field {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: 6px;
    }

    /*
        Selector visual de beneficiario, tramitador y tipo de certificado.
        Mantiene un select real oculto para que el POST llegue igual al controlador.
    */
    .tramite-persona-select {
        min-width: 0;
        position: relative;
    }

    .tramite-persona-select-label {
        display: block;
        margin-bottom: 4px;
        color: #334155;
        font-size: 13px;
        font-weight: 700;
    }

    .tramite-persona-native-select {
        height: 1px !important;
        left: 0;
        opacity: 0;
        pointer-events: none;
        position: absolute;
        top: 28px;
        width: 1px !important;
    }

    .tramite-persona-select-control {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-sizing: border-box;
        color: #0f172a;
        display: flex;
        gap: 10px;
        min-height: 42px;
        padding: 6px 11px;
        text-align: left;
        transition: border-color 150ms ease, box-shadow 150ms ease;
        width: 100%;
    }

    .tramite-persona-select-control:hover,
    .tramite-persona-select-control.is-open {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, .10);
    }

    .tramite-persona-select-text,
    .tramite-persona-select-option-main {
        display: grid;
        flex: 1;
        min-width: 0;
    }

    .tramite-persona-select-name,
    .tramite-persona-select-option-main strong {
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.15;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tramite-persona-select-help,
    .tramite-persona-select-option-main small {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tramite-persona-select.is-single-line .tramite-persona-select-control {
        min-height: 40px;
        padding-top: 8px;
        padding-bottom: 8px;
    }

    .tramite-persona-select.is-single-line .tramite-persona-select-help {
        display: none;
    }

    .tramite-persona-select.is-single-line .tramite-persona-select-option-main small {
        display: none;
    }

    .tramite-persona-select.is-single-line .tramite-persona-select-option {
        align-items: center;
        min-height: 36px;
    }

    .tramite-persona-select-chevron {
        color: #64748b;
        flex: 0 0 auto;
        font-size: 11px;
    }

    .tramite-persona-select-dropdown {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        box-shadow: 0 18px 35px rgba(15, 23, 42, .14);
        left: 0;
        margin-top: 7px;
        overflow: hidden;
        position: absolute;
        right: 0;
        top: 100%;
        z-index: 70;
    }

    .tramite-persona-select-search {
        border-bottom: 1px solid #e2e8f0;
        padding: 8px 10px;
    }

    .tramite-persona-select-search input {
        border: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 700;
        outline: 0;
        padding: 0;
        width: 100%;
    }

    .tramite-persona-select-options {
        max-height: 260px;
        overflow-y: auto;
        padding: 6px;
    }

    .tramite-persona-select-option {
        background: transparent;
        border: 1px solid transparent;
        border-radius: 8px;
        color: #0f172a;
        display: flex;
        padding: 8px 9px;
        text-align: left;
        width: 100%;
    }

    .tramite-persona-select-option:hover,
    .tramite-persona-select-option.is-selected {
        background: #f8fafc;
        border-color: #bbf7d0;
    }

    .tramite-persona-select-empty {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        padding: 12px;
        text-align: center;
    }

    .is-hidden {
        display: none !important;
    }

    .tramite-mini-check {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .tramite-mini-check input {
        width: 14px;
        height: 14px;
        accent-color: #059669;
    }

    /*
        Tabla de requisitos.
        Se mantiene simple para que los documentos no se sobrepongan.
    */
    .tramite-table-wrap {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
    }

    .tramite-table {
        width: 100%;
        min-width: 760px;
        border-collapse: collapse;
        color: #0f172a;
        font-size: 13px;
    }

    .tramite-table th,
    .tramite-table td {
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 12px;
        text-align: left;
        vertical-align: middle;
    }

    .tramite-table th {
        background: #f8fafc;
        color: #334155;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .tramite-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .tramite-table td:first-child,
    .tramite-table th:first-child {
        text-align: center;
    }

    /*
        Evidencia requerida por el requisito.
        Se muestra como informacion de apoyo, no como accion del usuario.
    */
    .tramite-evidencia-info {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .tramite-evidencia-chip {
        display: inline-flex;
        width: fit-content;
        max-width: 100%;
        align-items: center;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        background: #f0fdf4;
        color: #047857;
        font-size: 10px;
        font-weight: 900;
        line-height: 1;
        padding: 5px 8px;
        text-transform: uppercase;
    }

    .tramite-evidencia-description {
        color: #475569;
        display: block;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
        max-width: 280px;
        white-space: normal;
    }

    .tramite-evidencia-certificados {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .tramite-evidencia-certificados span {
        border: 1px solid #dbeafe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e40af;
        font-size: 10px;
        font-weight: 800;
        line-height: 1.15;
        padding: 4px 7px;
    }

    .tramite-evidencia-pendiente {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
        padding: 9px 10px;
    }

    /*
        Control PDF compacto:
        input oculto + botones Seleccionar / Ver / Quitar.
    */
    .tramite-pdf-control {
        display: flex;
        width: 100%;
        min-height: 38px;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 6px 7px;
    }

    .tramite-pdf-control.is-invalid {
        border-color: #dc2626;
        background: #fef2f2;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, .10);
    }

    .tramite-pdf-input {
        display: none;
    }

    .tramite-pdf-info {
        display: flex;
        min-width: 0;
        flex: 1 1 auto;
        align-items: center;
        gap: 8px;
    }

    .tramite-pdf-info i {
        flex: 0 0 auto;
        color: #ef4444;
        font-size: 16px;
    }

    .tramite-pdf-info div {
        min-width: 0;
    }

    .tramite-pdf-name,
    .tramite-pdf-status {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tramite-pdf-name {
        color: #334155;
        font-size: 11px;
        font-weight: 900;
    }

    .tramite-pdf-status {
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
    }

    .tramite-pdf-actions {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 5px;
    }

    .tramite-pdf-button {
        display: inline-flex;
        min-height: 28px;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        padding: 0 8px;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
    }

    .tramite-pdf-button:disabled {
        cursor: not-allowed;
        opacity: .55;
    }

    .tramite-pdf-button.is-select {
        border-color: #a7f3d0;
        background: #ecfdf5;
        color: #047857;
        cursor: pointer;
    }

    .tramite-pdf-button.is-view {
        border-color: #bae6fd;
        background: #f0f9ff;
        color: #0369a1;
    }

    .tramite-pdf-button.is-remove {
        border-color: #fecaca;
        background: #fff7f7;
        color: #dc2626;
    }

    /*
        Botones finales.
        Se dejan en un solo bloque para ubicar facil las acciones del formulario.
    */
    .tramite-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .tramite-btn {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 8px;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 900;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
    }

    .tramite-btn-neutral {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
    }

    .tramite-btn-primary {
        border: 1px solid #059669;
        background: #059669;
        color: #ffffff;
    }

    .tramite-persona-select.is-locked .tramite-persona-select-control {
        background: #f8fafc;
        border-color: #cbd5e1;
        cursor: not-allowed;
    }

    .tramite-persona-select.is-locked .tramite-persona-select-control i {
        color: #94a3b8;
    }

    @media (max-width: 900px) {
        .tramite-field-6 {
            grid-column: span 12;
        }

        .tramite-registro-separator {
            display: none;
        }
    }

    @media (max-width: 640px) {
        .tramite-persona-body {
            padding: 12px;
        }

        .tramite-pdf-control {
            align-items: stretch;
            flex-wrap: wrap;
        }

        .tramite-pdf-actions {
            width: 100%;
        }

        .tramite-pdf-button {
            flex: 1 1 0;
        }

        .tramite-actions {
            justify-content: stretch;
        }

        .tramite-btn {
            width: 100%;
        }
    }
</style>
