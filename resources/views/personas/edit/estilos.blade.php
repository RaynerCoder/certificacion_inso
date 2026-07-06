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
        min-width: 680px;
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
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .persona-btn {
        min-height: 40px;
        border-radius: 8px;
        padding: 0 16px;
        font-size: 14px;
        font-weight: 800;
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

    .persona-wizard-flat button[onclick*="agregarTelefonoPersona"],
    .persona-wizard-flat button[onclick*="agregarRubroPersona"] {
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
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 14px;
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
        .responsables-wizard-action {
            align-items: stretch;
            flex-direction: column;
        }

        .persona-type-tabs-inner,
        .persona-review-grid {
            grid-template-columns: 1fr;
        }

        .persona-buttons {
            justify-content: stretch;
        }

        .persona-buttons>* {
            width: 100%;
        }
    }
</style>
