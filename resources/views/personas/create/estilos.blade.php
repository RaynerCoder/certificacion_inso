{{-- CSS propio del wizard: asegura el diseno aunque Tailwind no recompile clases nuevas. --}}
<style>
    .persona-wizard {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .persona-wizard-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .persona-wizard-title {
        margin: 0;
        color: #0f172a;
        font-size: 26px;
        font-weight: 700;
        letter-spacing: 0;
    }

    .persona-wizard-subtitle {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 14px;
    }

    .persona-save-pill {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .persona-save-pill.bg-emerald-50 {
        background: #ecfdf5;
        color: #047857;
    }

    .persona-save-pill.bg-blue-50 {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .persona-submit-status {
        display: none;
        gap: 10px;
        align-items: center;
        min-width: min(100%, 330px);
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        background: #f0fdf4;
        padding: 10px 12px;
        color: #166534;
        font-size: 12px;
        font-weight: 800;
    }

    .persona-submit-status.is-visible {
        display: flex;
    }

    .persona-submit-spinner {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
        border: 3px solid #bbf7d0;
        border-top-color: #16a34a;
        border-radius: 999px;
        animation: persona-spin 850ms linear infinite;
    }

    .persona-submit-progress {
        position: relative;
        height: 5px;
        flex: 1;
        overflow: hidden;
        border-radius: 999px;
        background: #dcfce7;
    }

    .persona-submit-progress::before {
        content: "";
        position: absolute;
        inset: 0;
        width: 42%;
        border-radius: inherit;
        background: #16a34a;
        animation: persona-progress 1.1s ease-in-out infinite;
    }

    @keyframes persona-spin {
        to { transform: rotate(360deg); }
    }

    @keyframes persona-progress {
        0% { transform: translateX(-120%); }
        100% { transform: translateX(260%); }
    }

    .persona-stepper-card {
        position: relative;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 22px 24px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .persona-stepper {
        position: relative;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        min-width: 820px;
        gap: 12px;
    }

    .persona-stepper::before {
        content: "";
        position: absolute;
        left: 52px;
        right: 52px;
        top: 20px;
        height: 2px;
        background: #e2e8f0;
    }

    .paso-burbuja {
        position: relative;
        z-index: 1;
        display: flex;
        width: 132px;
        cursor: pointer;
        flex-direction: column;
        align-items: center;
        gap: 9px;
        border: 0;
        background: transparent;
        color: #64748b;
        text-align: center;
    }

    .paso-circulo {
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

    .paso-burbuja span:last-child {
        display: block;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
    }

    .paso-burbuja.is-active .paso-circulo {
        border-color: #0d9488;
        background: #0d9488;
        color: #ffffff;
        box-shadow: 0 0 0 6px #ccfbf1;
    }

    .paso-burbuja.is-completed .paso-circulo {
        border-color: #10b981;
        background: #10b981;
        color: #ffffff;
    }

    .persona-wizard-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 20px;
        align-items: start;
    }

    .persona-form-card,
    .persona-progress-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .persona-form-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        border-bottom: 1px solid #e2e8f0;
        padding: 18px 20px;
    }

    .persona-form-head-left {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .persona-form-icon {
        display: flex;
        width: 42px;
        height: 42px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #0d9488;
        color: #ffffff;
    }

    .persona-form-title {
        margin: 0;
        color: #0f172a;
        font-size: 17px;
        font-weight: 800;
    }

    .persona-form-subtitle {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .persona-type-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .persona-type-pill.bg-teal-50 {
        background: #ccfbf1;
        color: #0f766e;
    }

    .persona-type-tabs {
        padding: 18px 20px 0;
    }

    .persona-type-error {
        margin: 8px 20px 0;
        color: #dc2626;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;
    }

    .persona-type-tabs-inner {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        border-radius: 8px;
        background: #f1f5f9;
        padding: 6px;
    }

    .tipo-rapido {
        min-height: 64px;
        cursor: pointer;
        border: 1px solid transparent;
        border-radius: 7px;
        background: transparent;
        color: #475569;
        padding: 12px 14px;
        text-align: left;
        font-size: 14px;
        font-weight: 800;
        transition: all 160ms ease;
    }

    .tipo-rapido span {
        display: block;
        margin-top: 3px;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
    }

    .tipo-rapido.is-active {
        border-color: #14b8a6;
        background: #ffffff;
        color: #0f766e;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }

    .persona-form-body {
        padding: 20px;
    }

    .wizard-persona-step.hidden {
        display: none !important;
    }

    .hidden {
        display: none !important;
    }

    .wizard-persona-step.space-y-5>*+* {
        margin-top: 20px;
    }

    .persona-empty-step {
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        padding: 34px 18px;
        text-align: center;
    }

    .persona-empty-step strong {
        display: block;
        color: #334155;
        font-size: 15px;
    }

    .persona-empty-step span {
        display: block;
        margin-top: 6px;
        color: #64748b;
        font-size: 13px;
    }

    .persona-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 16px 20px;
    }

    .persona-help {
        margin: 0;
        color: #64748b;
        font-size: 14px;
    }

    .persona-buttons {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .persona-edit-top-actions {
        display: flex;
        min-height: 44px;
        align-items: center;
        align-self: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .persona-buttons>.persona-btn,
    .persona-edit-top-actions>.persona-btn {
        align-self: center;
    }

    .persona-edit-top-actions>.persona-btn {
        min-height: 36px;
    }

    .persona-btn {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        padding: 0 16px;
        font-size: 14px;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
    }

    .persona-btn-light {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
    }

    .persona-btn-primary {
        border: 1px solid #0d9488;
        background: #0d9488;
        color: #ffffff;
    }

    .persona-progress-card {
        position: sticky;
        top: 16px;
        padding: 20px;
    }

    .persona-progress-title {
        margin: 0;
        color: #0f172a;
        font-size: 17px;
        font-weight: 800;
    }

    .persona-progress-subtitle {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .persona-progress-list {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-top: 20px;
    }

    .persona-progress-list::before {
        content: "";
        position: absolute;
        bottom: 20px;
        left: 16px;
        top: 20px;
        width: 1px;
        background: #e2e8f0;
    }

    .progreso-item {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .progreso-punto {
        z-index: 1;
        display: flex;
        width: 32px;
        height: 32px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #ffffff;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 800;
    }

    .progreso-item-box {
        min-width: 0;
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 10px 11px;
    }

    .progreso-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .progreso-label {
        margin: 0;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.25;
    }

    .progreso-estado {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #fffbeb;
        color: #b45309;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .progreso-item.is-complete .progreso-punto {
        border-color: #10b981;
        background: #10b981;
        color: #ffffff;
    }

    .progreso-item.is-complete .progreso-item-box {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .progreso-item.is-complete .progreso-estado {
        background: #dcfce7;
        color: #047857;
    }
    .progreso-detalle {
        margin: 7px 0 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
    }

    .persona-wizard-flat>.bg-white.rounded-2xl,
    .persona-wizard-flat .wizard-section-block>.bg-white.rounded-2xl,
    .persona-wizard-flat>#seccion_natural>.bg-white.rounded-2xl,
    .persona-wizard-flat>#seccion_empresa>.bg-white.rounded-2xl,
    .persona-wizard-flat>#seccion_rubros>.bg-white.rounded-2xl,
    .persona-wizard-flat>#seccion_responsables>.bg-white.rounded-2xl,
    .persona-wizard-flat #seccion_rubros>.bg-white.rounded-2xl,
    .persona-wizard-flat #seccion_responsables>.bg-white.rounded-2xl {
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        overflow: visible !important;
    }

    .persona-wizard-flat>.bg-white.rounded-2xl>.bg-gradient-to-r,
    .persona-wizard-flat .wizard-section-block>.bg-white.rounded-2xl>.bg-gradient-to-r,
    .persona-wizard-flat>#seccion_natural>.bg-white.rounded-2xl>.bg-gradient-to-r,
    .persona-wizard-flat>#seccion_empresa>.bg-white.rounded-2xl>.bg-gradient-to-r,
    .persona-wizard-flat>#seccion_rubros>.bg-white.rounded-2xl>.bg-gradient-to-r,
    .persona-wizard-flat>#seccion_responsables>.bg-white.rounded-2xl>.bg-gradient-to-r,
    .persona-wizard-flat #seccion_rubros>.bg-white.rounded-2xl>.bg-gradient-to-r,
    .persona-wizard-flat #seccion_responsables>.bg-white.rounded-2xl>.bg-gradient-to-r {
        display: none !important;
    }

    .persona-wizard-flat>.bg-white.rounded-2xl>.p-6,
    .persona-wizard-flat .wizard-section-block>.bg-white.rounded-2xl>.p-6,
    .persona-wizard-flat>#seccion_natural>.bg-white.rounded-2xl>.p-6,
    .persona-wizard-flat>#seccion_empresa>.bg-white.rounded-2xl>.p-6,
    .persona-wizard-flat>#seccion_rubros>.bg-white.rounded-2xl>.p-6,
    .persona-wizard-flat>#seccion_responsables>.bg-white.rounded-2xl>.p-6,
    .persona-wizard-flat #seccion_rubros>.bg-white.rounded-2xl>.p-6,
    .persona-wizard-flat #seccion_responsables>.bg-white.rounded-2xl>.p-6 {
        padding: 0 !important;
    }

    .persona-wizard-flat .bg-gray-50 {
        background: #f8fafc !important;
    }

    .persona-wizard-flat .rounded-xl,
    .persona-wizard-flat .rounded-2xl {
        border-radius: 8px !important;
    }

    .persona-wizard-flat #map {
        height: 320px !important;
    }

    .persona-wizard-flat button[onclick*="agregarTelefonoPersona"] {
        min-height: 40px !important;
        border-radius: 8px !important;
        background: #0d9488 !important;
        font-weight: 800 !important;
    }

    .responsables-wizard-action {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        padding: 16px;
    }

    .responsables-wizard-action.is-visible {
        display: flex;
        margin-bottom: 18px;
    }

    .responsables-wizard-action h3 {
        margin: 0;
        color: #334155;
        font-size: 14px;
        font-weight: 800;
    }

    .responsables-wizard-action p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .responsables-wizard-action button {
        min-height: 40px;
        flex: 0 0 auto;
        border: 1px solid #0d9488;
        border-radius: 8px;
        background: #0d9488;
        color: #ffffff;
        padding: 0 16px;
        font-size: 14px;
        font-weight: 800;
    }

    .responsables-review-table {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
    }

    .responsables-review-head,
    .responsable-agregado.responsable-review-row {
        display: grid;
        grid-template-columns: 46px minmax(180px, 1.35fr) minmax(150px, 1fr) minmax(190px, 1.15fr) minmax(150px, 0.9fr) 96px;
        min-width: 900px;
        align-items: stretch;
    }

    .responsables-review-head {
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .responsables-review-head span {
        padding: 10px 12px;
        color: #475569;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .responsables-review-body {
        min-width: 900px;
    }

    .responsable-agregado.responsable-review-row {
        position: relative;
        border-bottom: 1px solid #eef2f7;
        background: #ffffff;
    }

    .responsable-agregado.responsable-review-row.is-duplicated-focus {
        background: #fffbeb;
        box-shadow: inset 0 0 0 2px #f59e0b;
    }

    .responsable-modal-warning {
        border: 1px solid #fed7aa;
        border-radius: 10px;
        background: #fff7ed;
        color: #9a3412;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.35;
    }

    .responsable-modal-pdf {
        display: flex;
        min-height: 42px;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 7px 8px 7px 10px;
    }

    .responsable-modal-pdf-info {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 9px;
    }

    .responsable-modal-pdf-info i {
        color: #dc2626;
        font-size: 16px;
    }

    .responsable-modal-pdf-info strong {
        display: block;
        max-width: 220px;
        overflow: hidden;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .responsable-modal-pdf-info span {
        display: block;
        margin-top: 1px;
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
    }

    .responsable-modal-pdf-actions {
        display: flex;
        flex: 0 0 auto;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 6px;
    }

    .responsable-modal-pdf-button {
        display: inline-flex;
        min-height: 28px;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border-radius: 6px;
        padding: 0 8px;
        font-size: 11px;
        font-weight: 900;
        transition: background 160ms ease, border-color 160ms ease, color 160ms ease;
    }

    .responsable-modal-pdf-button.is-select {
        border: 1px solid #99f6e4;
        background: #f0fdfa;
        color: #0f766e;
        cursor: pointer;
    }

    .responsable-modal-pdf-button.is-view {
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .responsable-modal-pdf-button.is-remove {
        border: 1px solid #fecaca;
        background: #fff1f2;
        color: #be123c;
    }

    .responsable-modal-pdf-button:hover:not(:disabled) {
        filter: brightness(0.97);
    }

    .responsable-modal-pdf-button:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .responsable-agregado.responsable-review-row:last-child {
        border-bottom: 0;
    }

    .responsable-agregado.responsable-review-row::before {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        width: 4px;
        content: "";
        background: #14b8a6;
    }

    .responsable-agregado.responsable-review-row:nth-child(4n+1)::before {
        background: #14b8a6;
    }

    .responsable-agregado.responsable-review-row:nth-child(4n+2)::before {
        background: #60a5fa;
    }

    .responsable-agregado.responsable-review-row:nth-child(4n+3)::before {
        background: #a78bfa;
    }

    .responsable-agregado.responsable-review-row:nth-child(4n+4)::before {
        background: #f59e0b;
    }

    .responsables-review-cell {
        padding: 12px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
    }

    .responsables-review-cell strong {
        display: block;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
    }

    .responsables-review-cell small,
    .responsables-review-muted {
        display: block;
        margin-top: 3px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .responsables-review-number {
        display: inline-flex;
        width: 26px;
        height: 26px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 12px;
        font-weight: 900;
    }

    .responsables-review-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
        padding: 3px 8px;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .responsables-review-pill.is-ok {
        background: #dcfce7;
        color: #047857;
    }

    .responsables-review-pill.is-file {
        background: #fef3c7;
        color: #a16207;
    }

    .responsables-review-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .responsables-review-edit {
        display: inline-flex;
        min-height: 30px;
        align-items: center;
        justify-content: center;
        border: 1px solid #99f6e4;
        border-radius: 7px;
        background: #ecfdf5;
        color: #047857;
        padding: 0 10px;
        font-size: 12px;
        font-weight: 900;
        text-decoration: none;
    }

    .responsables-review-edit:hover {
        border-color: #14b8a6;
        background: #d1fae5;
        color: #065f46;
    }

    .responsables-review-remove {
        display: inline-flex;
        min-height: 30px;
        align-items: center;
        justify-content: center;
        border: 1px solid #fecaca;
        border-radius: 7px;
        background: #fff5f5;
        color: #b91c1c;
        padding: 0 10px;
        font-size: 12px;
        font-weight: 900;
    }

    .responsables-review-empty {
        display: block;
        padding: 14px;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
    }

    .responsables-review-table {
        overflow: visible;
        border: 0;
        background: transparent;
    }

    .responsables-review-body {
        display: grid;
        min-width: 0;
        gap: 10px;
    }

    .responsable-agregado.responsable-review-row {
        display: block;
        min-width: 0;
        overflow: hidden;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: #ffffff;
    }

    .responsable-agregado.responsable-review-row::before {
        width: 5px;
    }

    .responsables-review-title {
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(90deg, #f0fdfa 0%, #f8fafc 100%);
        padding: 11px 12px 11px 17px;
    }

    .responsables-review-title>div {
        min-width: 0;
        flex: 1;
    }

    .responsables-review-title strong {
        display: block;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .responsables-review-title small {
        display: block;
        margin-top: 2px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .responsables-review-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        padding-left: 5px;
    }

    .responsables-review-grid section {
        min-width: 0;
        border-right: 1px solid #edf2f7;
        border-bottom: 1px solid #edf2f7;
        padding: 11px 12px;
    }

    .responsables-review-grid section:nth-child(3n) {
        border-right: 0;
    }

    .responsables-review-grid section:nth-last-child(-n+3) {
        border-bottom: 0;
    }

    .responsables-review-grid h6 {
        margin: 0 0 8px;
        color: #0f766e;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .responsables-review-grid .persona-review-responsable-data {
        grid-template-columns: minmax(88px, .75fr) minmax(0, 1.25fr);
    }

    .responsables-review-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        max-width: 100%;
        margin: 0 5px 6px 0;
        border: 1px solid #d1fae5;
        border-radius: 999px;
        background: #f0fdfa;
        color: #0f766e;
        padding: 5px 8px;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.25;
    }

    .responsables-review-chip small {
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
    }

    .responsables-review-empty-inline {
        display: inline-flex;
        border: 1px dashed #cbd5e1;
        border-radius: 999px;
        background: #f8fafc;
        color: #64748b;
        padding: 5px 8px;
        font-size: 11px;
        font-weight: 800;
    }

    .responsables-review-inline-pills {
        margin-top: 6px;
    }

    .wizard-section-block {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        padding: 16px;
    }

    .wizard-section-block.is-soft {
        background: #f8fafc;
    }

    .wizard-section-heading {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 16px;
    }

    .wizard-section-number {
        display: flex;
        width: 30px;
        height: 30px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #ccfbf1;
        color: #0f766e;
        font-size: 12px;
        font-weight: 900;
    }

    .wizard-section-heading h3 {
        margin: 0;
        color: #0f172a;
        font-size: 15px;
        font-weight: 900;
    }

    .wizard-section-heading p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .persona-account-intro {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        padding: 12px 14px;
    }

    .persona-account-intro strong {
        display: block;
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
    }

    .persona-account-intro span {
        display: block;
        margin-top: 3px;
        color: #64748b;
        font-size: 12px;
    }

    .persona-account-panel {
        margin-top: 14px;
        border-top: 1px solid #e2e8f0;
        padding-top: 14px;
    }

    .persona-account-link {
        margin-top: 7px;
        border: 0;
        background: transparent;
        color: #0f766e;
        padding: 0;
        font-size: 12px;
        font-weight: 900;
    }

    .persona-account-note {
        margin: 12px 0 0;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        background: #f0fdf4;
        color: #166534;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 700;
    }

    .persona-review-intro {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        padding: 16px;
    }

    .persona-review-intro h3 {
        margin: 0;
        color: #334155;
        font-size: 14px;
        font-weight: 800;
    }

    .persona-review-intro p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 12px;
    }

    .persona-review-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 14px;
    }

    .persona-review-section {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
    }

    .persona-review-section-head {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        padding: 11px 14px;
    }

    .persona-review-section-dot {
        width: 9px;
        height: 9px;
        flex: 0 0 auto;
        margin-top: 5px;
        border-radius: 999px;
        background: #0d9488;
        box-shadow: 0 0 0 4px #ccfbf1;
    }

    .persona-review-section-head h4 {
        margin: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
    }

    .persona-review-section-head p {
        margin: 2px 0 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .persona-review-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0 24px;
        margin: 0;
        padding: 8px 14px;
    }

    .persona-review-row {
        display: grid;
        grid-template-columns: minmax(120px, 150px) minmax(0, 1fr);
        gap: 10px;
        align-items: baseline;
        border-bottom: 1px solid #f1f5f9;
        padding: 8px 0;
    }

    .persona-review-row.is-wide {
        grid-column: 1 / -1;
    }

    .persona-review-row dt {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .persona-review-row dd {
        margin: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .persona-review-table-block {
        grid-column: 1 / -1;
        margin: 8px 0 10px;
    }

    .persona-review-table-block h5 {
        margin: 0 0 8px;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .persona-review-table-wrap {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
    }

    .persona-review-table {
        width: 100%;
        min-width: 620px;
        border-collapse: collapse;
    }

    .persona-review-table th {
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

    .persona-review-table td {
        border-bottom: 1px solid #f1f5f9;
        color: #0f172a;
        padding: 10px;
        vertical-align: top;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.45;
    }

    .persona-review-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .persona-review-responsables-block {
        margin-top: 10px;
    }

    .persona-review-responsables-list {
        display: grid;
        gap: 10px;
    }

    .persona-review-responsable-item {
        position: relative;
        overflow: hidden;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        background: #ffffff;
    }

    .persona-review-responsable-item::before {
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        content: "";
        background: #0f766e;
    }

    .persona-review-responsable-item:nth-child(4n+2)::before {
        background: #2563eb;
    }

    .persona-review-responsable-item:nth-child(4n+3)::before {
        background: #7c3aed;
    }

    .persona-review-responsable-item:nth-child(4n+4)::before {
        background: #ca8a04;
    }

    .persona-review-responsable-title {
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(90deg, #f0fdfa 0%, #f8fafc 100%);
        padding: 11px 12px 11px 17px;
    }

    .persona-review-responsable-number {
        display: inline-flex;
        width: 26px;
        height: 26px;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #0f766e;
        color: #ffffff;
        font-size: 12px;
        font-weight: 900;
    }

    .persona-review-responsable-title h6 {
        margin: 0;
        color: #0f172a;
        font-size: 13px;
        font-weight: 900;
        line-height: 1.25;
    }

    .persona-review-responsable-title p {
        margin: 2px 0 0;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .persona-review-responsable-status {
        margin-left: auto;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        background: #f0fdf4;
        color: #047857;
        padding: 4px 8px;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .persona-review-responsable-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0;
        padding-left: 5px;
    }

    .persona-review-responsable-grid section {
        min-width: 0;
        border-right: 1px solid #edf2f7;
        border-bottom: 1px solid #edf2f7;
        padding: 11px 12px;
    }

    .persona-review-responsable-grid section:nth-child(3n) {
        border-right: 0;
    }

    .persona-review-responsable-grid section:nth-last-child(-n+3) {
        border-bottom: 0;
    }

    .persona-review-responsable-grid h6 {
        margin: 0 0 8px;
        color: #0f766e;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .persona-review-responsable-data {
        display: grid;
        grid-template-columns: minmax(92px, .75fr) minmax(0, 1.25fr);
        gap: 8px;
        padding: 4px 0;
        color: #475569;
        font-size: 12px;
        line-height: 1.35;
    }

    .persona-review-responsable-data span {
        color: #64748b;
        font-weight: 800;
    }

    .persona-review-responsable-data strong {
        color: #0f172a;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .persona-review-pdf-link {
        display: inline-flex;
        min-height: 28px;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        border-radius: 7px;
        background: #eff6ff;
        color: #1d4ed8;
        padding: 0 10px;
        font-size: 11px;
        font-weight: 900;
        text-decoration: none;
    }

    .persona-review-pdf-link:hover {
        border-color: #60a5fa;
        background: #dbeafe;
        color: #1e40af;
    }

    .persona-review-responsable-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        max-width: 100%;
        margin: 0 5px 6px 0;
        border: 1px solid #d1fae5;
        border-radius: 999px;
        background: #f0fdfa;
        color: #0f766e;
        padding: 5px 8px;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.25;
    }

    .persona-review-responsable-chip small {
        color: #64748b;
        font-size: 10px;
        font-weight: 800;
    }

    .persona-review-responsable-empty,
    .persona-review-responsable-empty-state {
        display: block;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        color: #64748b;
        padding: 10px;
        font-size: 12px;
        font-weight: 800;
    }

    .sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
    }

    @media (max-width: 1100px) {
        .persona-wizard-layout {
            grid-template-columns: 1fr;
        }

        .persona-progress-card {
            position: static;
        }
    }

    @media (max-width: 720px) {

        .persona-wizard-header,
        .persona-form-head,
        .persona-actions,
        .persona-edit-top-actions,
        .responsables-wizard-action {
            align-items: stretch;
            flex-direction: column;
        }

        .persona-type-tabs-inner,
        .persona-review-list {
            grid-template-columns: 1fr;
        }

        .persona-buttons {
            justify-content: stretch;
        }

        .persona-buttons>* {
            width: 100%;
        }

        .persona-edit-top-actions {
            align-items: center;
            justify-content: center;
        }

        .persona-edit-top-actions>* {
            width: min(100%, 240px);
        }

        .persona-review-responsable-title {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .persona-review-responsable-status {
            margin-left: 0;
        }

        .persona-review-responsable-grid {
            grid-template-columns: 1fr;
        }

        .persona-review-responsable-grid section,
        .persona-review-responsable-grid section:nth-child(3n),
        .persona-review-responsable-grid section:nth-last-child(-n+3) {
            border-right: 0;
            border-bottom: 1px solid #edf2f7;
        }

        .persona-review-responsable-grid section:last-child {
            border-bottom: 0;
        }

        .responsables-review-title {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .responsables-review-grid {
            grid-template-columns: 1fr;
        }

        .responsables-review-grid section,
        .responsables-review-grid section:nth-child(3n),
        .responsables-review-grid section:nth-last-child(-n+3) {
            border-right: 0;
            border-bottom: 1px solid #edf2f7;
        }

        .responsables-review-grid section:last-child {
            border-bottom: 0;
        }
    }
</style>
