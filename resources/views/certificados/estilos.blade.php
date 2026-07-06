<style>
    /* CSS propio: mantiene la estructura aun si Tailwind no recompila clases nuevas. */
    .cert-page-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.25rem;
        align-items: start;
    }

    /* En escritorio el resumen funciona como guia lateral, no como paso numerado. */
    @media (min-width: 1180px) {
        .cert-page-grid {
            grid-template-columns: minmax(0, 1fr) 430px;
        }
    }

    .cert-grid-three,
    .cert-grid-twelve {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.5rem;
    }

    /* Evita que textos largos de selects o nombres de personas empujen otras columnas. */
    .cert-grid-three>div,
    .cert-grid-twelve>div {
        min-width: 0;
    }

    /* Desde pantallas medianas los campos se acomodan a los costados. */
    @media (min-width: 860px) {
        .cert-grid-three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .cert-grid-twelve {
            grid-template-columns: repeat(12, minmax(0, 1fr));
        }

        .cert-span-2 {
            grid-column: span 2 / span 2;
        }

        .cert-span-3 {
            grid-column: span 3 / span 3;
        }

        .cert-span-4 {
            grid-column: span 4 / span 4;
        }

        .cert-span-5 {
            grid-column: span 5 / span 5;
        }

        .cert-span-6 {
            grid-column: span 6 / span 6;
        }

        .cert-span-7 {
            grid-column: span 7 / span 7;
        }

        .cert-span-12 {
            grid-column: span 12 / span 12;
        }
    }

    /* En movil se aumenta el aire entre campos para que los labels y controles no se sientan pegados. */
    @media (max-width: 640px) {
        .cert-grid-twelve {
            gap: 1.75rem;
        }
    }

    .cert-card {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    /* Selects nativos del certificado: aseguran que beneficiario y tramitador se envien siempre al backend. */
    .cert-native-label {
        display: block;
        margin-bottom: 0.35rem;
        color: #0f172a;
        font-size: 0.875rem;
        font-weight: 700;
    }

    .cert-native-select {
        width: 100%;
        min-height: 42px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #334155;
        padding: 0.55rem 0.75rem;
        font-size: 0.875rem;
        outline: none;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .cert-native-select:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
    }

    /* Tom Select en certificados: select con flecha, escritura y filtro dentro del mismo campo. */
    .cert-page-grid .ts-wrapper.single .ts-control {
        min-height: 42px !important;
        height: 42px !important;
        display: flex !important;
        align-items: center !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        background: #ffffff !important;
        color: #334155 !important;
        padding: 0 0.75rem !important;
        font-size: 0.875rem !important;
        box-shadow: none !important;
    }

    .cert-page-grid .ts-wrapper.focus .ts-control {
        border-color: #0f766e !important;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, .12) !important;
    }

    .cert-page-grid .ts-wrapper.single .ts-control .item,
    .cert-page-grid .ts-wrapper.single .ts-control input {
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #334155 !important;
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
    }

    /* Centra el texto seleccionado y el cursor de escritura dentro del select buscable. */
    .cert-page-grid .ts-wrapper.single .ts-control .item {
        display: flex !important;
        align-items: center !important;
    }

    .cert-page-grid .ts-dropdown {
        z-index: 80 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        overflow: hidden !important;
        box-shadow: 0 18px 40px rgba(15, 23, 42, .16) !important;
    }

    .cert-page-grid .ts-dropdown .option {
        padding: 0.65rem 0.75rem !important;
        color: #334155 !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
    }

    .cert-page-grid .ts-dropdown .active {
        background: #ecfdf5 !important;
        color: #0f766e !important;
    }

    /* Interruptor de requisitos: muestra claramente si el documento cumple o no cumple. */
    .cert-switch {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        user-select: none;
    }

    .cert-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .cert-switch-track {
        position: relative;
        width: 44px;
        height: 24px;
        border-radius: 999px;
        background: #cbd5e1;
        transition: background 160ms ease;
    }

    .cert-switch-track::after {
        content: "";
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 999px;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .24);
        transition: transform 160ms ease;
    }

    .cert-switch input:checked+.cert-switch-track {
        background: #059669;
    }

    .cert-switch input:checked+.cert-switch-track::after {
        transform: translateX(20px);
    }

    .cert-action-btn,
    .cert-ghost-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 8px;
        font-weight: 800;
        transition: all 160ms ease;
    }

    .cert-action-btn {
        min-height: 46px;
        border: 1px solid #047857;
        background: #059669;
        color: #ffffff;
        padding: 0 16px;
        font-size: 14px;
        box-shadow: 0 10px 20px rgba(5, 150, 105, .18);
    }

    .cert-action-btn:hover {
        background: #047857;
    }

    .cert-ghost-btn {
        min-height: 44px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        padding: 0 16px;
        font-size: 14px;
    }

    .cert-ghost-btn:hover {
        border-color: #94a3b8;
        background: #f1f5f9;
    }

    .cert-pdf-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 38px;
        border: 1px solid #99f6e4;
        border-radius: 8px;
        background: #ecfdf5;
        color: #0f766e;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 800;
        transition: all 160ms ease;
    }

    .cert-pdf-button:hover {
        background: #ccfbf1;
    }

    /* Refuerza la clase hidden porque estos elementos tienen display definido en el CSS propio. */
    .cert-pdf-button.hidden,
    .cert-pdf-frame.hidden {
        display: none;
    }

    .cert-pdf-modal {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, .65);
        padding: 24px;
    }

    .cert-pdf-modal.is-open {
        display: flex;
    }

    .cert-pdf-dialog {
        display: flex;
        width: min(980px, 100%);
        max-height: 92vh;
        flex-direction: column;
        overflow: hidden;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .35);
    }

    .cert-pdf-modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 18px;
    }

    .cert-modal-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        color: #334155;
        padding: 0 14px;
        font-size: 13px;
        font-weight: 800;
        transition: all 160ms ease;
    }

    .cert-modal-close:hover {
        border-color: #0f766e;
        background: #ecfdf5;
        color: #0f766e;
    }

    .cert-pdf-frame {
        display: block;
        width: 100%;
        height: min(72vh, 760px);
        border: 0;
        background: #f8fafc;
    }

    .cert-svg {
        width: 22px;
        height: 22px;
        flex: 0 0 auto;
    }

    .cert-svg-sm {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
    }
</style>
