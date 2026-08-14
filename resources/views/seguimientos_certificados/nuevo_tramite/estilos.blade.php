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

    .tramite-documentos-progress {
        display: grid;
        width: min(220px, 32vw);
        flex: 0 0 auto;
        gap: 6px;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
    }

    .tramite-documentos-progress-track {
        overflow: hidden;
        height: 6px;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .tramite-documentos-progress-track span {
        display: block;
        width: 0;
        height: 100%;
        border-radius: inherit;
        background: #059669;
        transition: width .2s ease;
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

    .tramite-persona-body.is-documents {
        padding: 6px 14px 8px;
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

    /* Cada requisito conserva su número, descripción, evidencia y estado en una sola fila. */
    .tramite-requisitos-form {
        width: 100%;
        background: #ffffff;
    }

    .tramite-requisitos-empty {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        padding: 24px 16px;
        text-align: center;
    }

    .tramite-requisito-item {
        display: grid;
        grid-template-columns: 32px minmax(280px, 1.5fr) minmax(330px, 390px) minmax(90px, auto);
        align-items: center;
        gap: 8px 16px;
        border-bottom: 1px solid #e2e8f0;
        padding: 8px 0;
        scroll-margin-top: 90px;
    }

    .tramite-requisito-item:last-child {
        border-bottom: 0;
    }

    .tramite-requisito-number {
        display: inline-flex;
        width: 30px;
        height: 30px;
        align-items: center;
        justify-content: center;
        border: 1px solid #6ee7b7;
        border-radius: 999px;
        background: #ffffff;
        color: #047857;
        font-size: 12px;
        font-weight: 900;
    }

    .tramite-requisito-item.is-complete .tramite-requisito-number {
        background: #047857;
        border-color: #047857;
        color: #ffffff;
    }

    .tramite-requisito-description,
    .tramite-requisito-evidencia,
    .tramite-requisito-field {
        min-width: 0;
    }

    .tramite-requisito-description {
        align-self: center;
    }

    .tramite-requisito-title {
        display: block;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .tramite-requisito-evidencia {
        display: grid;
        width: 100%;
        gap: 3px;
    }

    .tramite-requisito-status {
        display: inline-flex;
        min-height: 22px;
        align-items: center;
        justify-content: center;
        justify-self: end;
        gap: 5px;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 10px;
        font-weight: 900;
        padding: 0 7px;
        white-space: nowrap;
    }

    .tramite-requisito-status.is-review {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .tramite-requisito-item.is-complete .tramite-requisito-status {
        border-color: #a7f3d0;
        background: #ecfdf5;
        color: #047857;
    }

    /*
        Evidencia requerida por el requisito.
        Se muestra como informacion de apoyo, no como accion del usuario.
    */
    .tramite-evidencia-info {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
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
        font-size: 9px;
        font-weight: 900;
        line-height: 1;
        padding: 3px 7px;
        text-transform: uppercase;
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
        overflow-wrap: anywhere;
        padding: 4px 7px;
    }

    .tramite-evidencia-pendiente {
        display: inline-flex;
        width: fit-content;
        max-width: 100%;
        align-items: center;
        gap: 6px;
        color: #1e40af;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.3;
    }

    .tramite-evidencia-pendiente-icon {
        display: inline-flex;
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        font-size: 11px;
    }

    .tramite-evidencia-pendiente > span:last-child {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .tramite-texto-input {
        width: min(100%, 360px);
        min-height: 38px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #0f172a;
        font-size: 12px;
        font-weight: 700;
        outline: none;
        padding: 8px 10px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .tramite-texto-input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .12);
    }

    .tramite-texto-input.is-invalid {
        border-color: #dc2626;
        background: #fef2f2;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, .10);
    }

    .tramite-texto-input::placeholder {
        color: #94a3b8;
        font-weight: 600;
    }

    /*
        Control de archivo integrado:
        input oculto + botones Seleccionar / Ver / Quitar.
    */
    .tramite-pdf-control {
        display: flex;
        width: min(100%, 390px);
        min-height: 38px;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 4px 6px;
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
        gap: 6px;
    }

    .tramite-pdf-info i {
        flex: 0 0 auto;
        color: #ef4444;
        font-size: 15px;
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
        gap: 4px;
    }

    .tramite-pdf-button {
        display: inline-flex;
        min-height: 25px;
        align-items: center;
        justify-content: center;
        gap: 4px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #ffffff;
        padding: 0 6px;
        color: #475569;
        font-size: 10px;
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

    .tramite-documentos-summary {
        display: grid;
        grid-template-columns: 34px auto minmax(140px, 320px);
        align-items: center;
        gap: 12px;
        margin-top: 14px;
        border-top: 1px solid #e2e8f0;
        color: #334155;
        padding: 14px 0 0;
        font-size: 12px;
    }

    .tramite-documentos-summary-icon {
        display: inline-flex;
        width: 32px;
        height: 32px;
        align-items: center;
        justify-content: center;
        border: 1px solid #a7f3d0;
        border-radius: 8px;
        background: #ecfdf5;
        color: #047857;
    }

    .tramite-documentos-summary-track {
        overflow: hidden;
        height: 6px;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .tramite-documentos-summary-track span {
        display: block;
        width: 0;
        height: 100%;
        border-radius: inherit;
        background: #059669;
        transition: width .2s ease;
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

    /* En tablet la evidencia baja de línea para conservar espacio para el requisito. */
    @media (max-width: 1100px) {
        .tramite-requisito-item {
            grid-template-columns: 30px minmax(0, 1fr) auto;
            align-items: start;
            gap: 6px 10px;
            padding: 9px 0;
        }

        .tramite-requisito-number {
            grid-column: 1;
            grid-row: 1;
        }

        .tramite-requisito-description {
            grid-column: 2;
            grid-row: 1;
        }

        .tramite-requisito-status {
            grid-column: 3;
            grid-row: 1;
        }

        .tramite-requisito-evidencia {
            grid-column: 2 / -1;
            grid-row: 2;
            max-width: 390px;
        }
    }

    @media (max-width: 900px) {
        .tramite-field-6 {
            grid-column: span 12;
        }
    }

    @media (max-width: 640px) {
        .tramite-persona-body {
            padding: 12px;
        }

        .tramite-persona-body.is-documents {
            padding: 5px 10px 7px;
        }

        .tramite-persona-head.is-documents {
            align-items: stretch;
            flex-direction: column;
        }

        .tramite-documentos-progress {
            width: 100%;
        }

        .tramite-requisito-item {
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 7px 8px;
        }

        .tramite-requisito-number {
            width: 28px;
            height: 28px;
            font-size: 12px;
        }

        .tramite-requisito-status {
            grid-column: 2;
            grid-row: 2;
            justify-self: start;
        }

        .tramite-requisito-evidencia {
            grid-column: 2;
            grid-row: 3;
        }

        .tramite-pdf-control {
            align-items: stretch;
            flex-wrap: wrap;
        }

        .tramite-pdf-info {
            width: 100%;
        }

        .tramite-pdf-actions {
            width: auto;
            flex-wrap: wrap;
        }

        .tramite-pdf-button {
            flex: 0 0 auto;
        }

        .tramite-documentos-summary {
            grid-template-columns: 32px minmax(0, 1fr);
        }

        .tramite-documentos-summary-track {
            grid-column: 1 / -1;
        }

        .tramite-actions {
            justify-content: stretch;
        }

        .tramite-btn {
            width: 100%;
        }
    }

    @media (max-width: 420px) {
        .tramite-requisito-item {
            grid-template-columns: 24px minmax(0, 1fr);
            gap: 6px;
        }

        .tramite-requisito-number {
            width: 24px;
            height: 24px;
            font-size: 10px;
        }

        .tramite-requisito-title {
            font-size: 12px;
        }

        .tramite-requisito-status {
            min-height: 20px;
            font-size: 9px;
        }

        .tramite-evidencia-chip,
        .tramite-evidencia-certificados span {
            font-size: 8px;
        }

        .tramite-pdf-control,
        .tramite-texto-input,
        .tramite-evidencia-pendiente {
            width: 100%;
        }

        .tramite-pdf-button {
            min-height: 24px;
            padding: 0 5px;
            font-size: 9px;
        }

        .tramite-documentos-summary {
            gap: 8px;
            font-size: 11px;
        }
    }
</style>
