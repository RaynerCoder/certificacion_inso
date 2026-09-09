<style>
    :root {
        --public-bg: #f3f7f6;
        --public-border: #d7e1e8;
        --public-green: #078442;
        --public-green-dark: #056934;
        --public-green-soft: #ecfdf3;
        --public-navy: #10233c;
        --public-slate: #607086;
    }

    * { box-sizing: border-box; }

    html { scroll-behavior: smooth; }

    body {
        background: var(--public-bg);
        color: var(--public-navy);
        font-family: Figtree, Arial, sans-serif;
        margin: 0;
        min-height: 100vh;
    }

    button, input, select, textarea { font: inherit; }

    .public-header {
        align-items: center;
        background: #13243a;
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        min-height: 66px;
        padding: 9px clamp(18px, 4vw, 52px);
    }

    .public-brand {
        align-items: center;
        color: inherit;
        display: flex;
        gap: 11px;
        text-decoration: none;
    }

    .public-brand img { height: 38px; width: 38px; }
    .public-brand span, .public-brand strong, .public-brand small { display: block; }
    .public-brand strong { font-size: 17px; letter-spacing: .02em; }
    .public-brand small { color: #b9c8d8; font-size: 10px; margin-top: 1px; }

    .public-header-help {
        align-items: center;
        color: #dbe6ef;
        display: flex;
        font-size: 12px;
        gap: 8px;
    }

    .public-main {
        margin: 0 auto;
        max-width: 1180px;
        padding: 28px clamp(16px, 4vw, 36px) 42px;
    }

    .public-course {
        align-items: center;
        background: linear-gradient(120deg, #ffffff 0%, #ffffff 70%, #effcf5 100%);
        border: 1px solid var(--public-border);
        border-radius: 15px;
        display: grid;
        gap: 24px;
        grid-template-columns: minmax(0, 1fr) auto;
        margin-bottom: 18px;
        padding: 21px 23px;
    }

    .public-kicker {
        color: var(--public-green);
        display: block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .06em;
        margin-bottom: 5px;
        text-transform: uppercase;
    }

    .public-course h1 {
        font-size: clamp(22px, 3vw, 30px);
        letter-spacing: -.02em;
        line-height: 1.2;
        margin: 0;
    }

    .public-course p { color: var(--public-slate); font-size: 13px; margin: 7px 0 0; }

    .course-meta { display: flex; gap: 10px; }

    .course-meta > span {
        align-items: center;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 10px;
        color: var(--public-slate);
        display: grid;
        font-size: 10px;
        gap: 1px 7px;
        grid-template-columns: 22px auto;
        min-width: 130px;
        padding: 9px 10px;
    }

    .course-meta i {
        color: var(--public-green);
        font-size: 14px;
        grid-row: 1 / 3;
        text-align: center;
    }

    .course-meta b { color: #334155; font-size: 10px; }

    .public-form-shell {
        background: #ffffff;
        border: 1px solid var(--public-border);
        border-radius: 15px;
        box-shadow: 0 12px 34px rgba(15, 35, 55, .05);
        overflow: hidden;
        scroll-margin-top: 16px;
    }

    .public-progress-head {
        align-items: flex-end;
        display: flex;
        justify-content: space-between;
        padding: 15px 22px 9px;
    }

    .public-progress-head div { display: grid; gap: 2px; }
    .public-progress-head span { color: var(--public-slate); font-size: 11px; font-weight: 700; }
    .public-progress-head strong { font-size: 14px; }

    .public-progress {
        background: #e2e8f0;
        height: 4px;
        overflow: hidden;
    }

    .public-progress span {
        background: var(--public-green);
        display: block;
        height: 100%;
        transition: width .25s ease;
    }

    .public-stepper {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        padding: 13px 22px;
    }

    .public-step {
        align-items: center;
        background: transparent;
        border: 0;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        font-size: 11px;
        gap: 8px;
        min-width: 0;
        padding: 4px;
        text-align: left;
    }

    .public-step:disabled { cursor: default; }

    .public-step span {
        align-items: center;
        border: 1px solid #cbd5e1;
        border-radius: 50%;
        display: flex;
        flex: 0 0 auto;
        font-size: 11px;
        font-weight: 800;
        height: 28px;
        justify-content: center;
        width: 28px;
    }

    .public-step b { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .public-step.is-active { color: var(--public-green); }
    .public-step.is-active span { background: var(--public-green); border-color: var(--public-green); color: #ffffff; }
    .public-step.is-complete { color: #047857; }
    .public-step.is-complete span { background: var(--public-green-soft); border-color: #34d399; }

    .public-panel {
        animation: public-panel-enter .18s ease-out;
        padding: 24px;
    }

    .public-panel[hidden] { display: none; }

    @keyframes public-panel-enter {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .public-panel-head {
        align-items: flex-start;
        display: flex;
        gap: 12px;
        margin-bottom: 22px;
    }

    .public-panel-icon {
        align-items: center;
        background: var(--public-green-soft);
        border-radius: 10px;
        color: var(--public-green);
        display: flex;
        flex: 0 0 auto;
        font-size: 18px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }

    .public-panel-head h2 { font-size: 18px; margin: 0; }
    .public-panel-head p { color: var(--public-slate); font-size: 12px; line-height: 1.45; margin: 5px 0 0; }

    .public-grid { display: grid; gap: 15px; }
    .public-grid-two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .public-grid-three { grid-template-columns: 1.3fr .7fr .8fr; }
    .public-field-wide { grid-column: 1 / -1; }

    .public-field { display: grid; gap: 6px; }

    .public-field > span,
    .public-choice-group legend {
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .public-field > span b,
    .public-choice-group legend b,
    .public-required-note b { color: #dc2626; }

    .public-field > span small { color: #64748b; font-weight: 500; }

    .public-field-help {
        color: #64748b;
        display: block;
        font-size: 10px;
        line-height: 1.4;
        margin-top: -2px;
    }

    .public-field input,
    .public-field select,
    .public-field textarea {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        color: var(--public-navy);
        min-height: 43px;
        outline: none;
        padding: 9px 11px;
        width: 100%;
    }

    .public-field textarea { line-height: 1.45; min-height: 90px; resize: vertical; }

    .public-field input:focus,
    .public-field select:focus,
    .public-field textarea:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .12);
    }

    .public-field input:-webkit-autofill,
    .public-field input:-webkit-autofill:hover,
    .public-field input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 1000px #ffffff inset;
        -webkit-text-fill-color: var(--public-navy);
    }

    .public-section-label {
        border-bottom: 1px solid #e2e8f0;
        color: var(--public-green-dark);
        font-size: 12px;
        font-weight: 800;
        margin: 23px 0 13px;
        padding-bottom: 7px;
    }

    .public-section-label:first-of-type { margin-top: 0; }
    .public-section-label small { color: #64748b; font-weight: 500; }

    .public-button {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        gap: 8px;
        justify-content: center;
        min-height: 42px;
        padding: 9px 15px;
    }

    .public-button:hover { background: #f8fafc; }
    .public-button:disabled { cursor: not-allowed; opacity: .58; }
    .public-button-primary { background: var(--public-green); border-color: var(--public-green); color: #ffffff; }
    .public-button-primary:hover { background: var(--public-green-dark); }

    .public-panel-actions {
        align-items: center;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 9px;
        justify-content: flex-end;
        margin: 24px -24px -24px;
        padding: 14px 24px;
    }

    .public-required-note { color: #64748b; font-size: 11px; margin-right: auto; }

    .public-identity-result {
        align-items: center;
        background: var(--public-green-soft);
        border: 1px solid #a7e7c1;
        border-radius: 10px;
        color: #047857;
        display: flex;
        gap: 10px;
        margin-top: 16px;
        padding: 11px 12px;
    }

    .public-identity-result[hidden] { display: none; }
    .public-identity-result strong, .public-identity-result span { display: block; }
    .public-identity-result strong { font-size: 12px; }
    .public-identity-result span { color: #4b7060; font-size: 10px; margin-top: 2px; }

    .public-question-list { display: grid; gap: 18px; }
    .public-extra-fields { margin-top: 18px; }

    .public-choice-group {
        border: 1px solid #dbe4ee;
        border-radius: 10px;
        display: grid;
        gap: 8px;
        margin: 0;
        padding: 13px;
    }

    .public-choice-group legend { padding: 0 5px; }
    .public-choice-group label { align-items: center; display: flex; font-size: 12px; gap: 8px; }
    .public-choice-group input { accent-color: var(--public-green); }

    .public-confirmation {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 11px;
        display: flex;
        gap: 13px;
        padding: 15px;
    }

    .public-confirmation > i { color: var(--public-green); font-size: 24px; }
    .public-confirmation strong, .public-confirmation span { display: block; }
    .public-confirmation strong { font-size: 14px; }
    .public-confirmation span { color: var(--public-slate); font-size: 11px; margin-top: 3px; }

    .public-consent {
        align-items: flex-start;
        border: 1px solid #dbe4ee;
        border-radius: 11px;
        display: flex;
        gap: 11px;
        margin-top: 16px;
        padding: 14px;
    }

    .public-consent input { accent-color: var(--public-green); margin-top: 3px; }
    .public-consent strong, .public-consent small { display: block; }
    .public-consent strong { font-size: 12px; }
    .public-consent small { color: var(--public-slate); font-size: 10px; line-height: 1.45; margin-top: 4px; }

    .public-prototype-note {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 9px;
        color: #1e40af;
        display: flex;
        font-size: 11px;
        gap: 9px;
        margin-top: 14px;
        padding: 10px 12px;
    }

    .public-footer {
        align-items: center;
        background: #ffffff;
        border-top: 1px solid var(--public-border);
        color: var(--public-slate);
        display: flex;
        font-size: 11px;
        justify-content: space-between;
        padding: 16px clamp(18px, 4vw, 52px);
    }

    .public-footer strong { color: var(--public-navy); }

    .public-toast {
        background: #10233c;
        border-radius: 9px;
        bottom: 22px;
        color: #ffffff;
        font-size: 12px;
        left: 50%;
        max-width: calc(100vw - 32px);
        opacity: 0;
        padding: 11px 16px;
        pointer-events: none;
        position: fixed;
        text-align: center;
        transform: translate(-50%, 15px);
        transition: opacity .2s ease, transform .2s ease;
        z-index: 20;
    }

    .public-toast.is-visible { opacity: 1; transform: translate(-50%, 0); }

    @media (max-width: 900px) {
        .public-course { align-items: flex-start; grid-template-columns: 1fr; }
        .course-meta { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .course-meta > span { min-width: 0; }
    }

    @media (max-width: 680px) {
        .public-header { min-height: 60px; }
        .public-brand img { height: 34px; width: 34px; }
        .public-brand small, .public-header-help span { display: none; }
        .public-main { padding-top: 16px; }
        .public-course { gap: 16px; padding: 17px; }
        .course-meta { grid-template-columns: 1fr; width: 100%; }
        .public-stepper { overflow-x: auto; padding-left: 14px; padding-right: 14px; }
        .public-step { min-width: 38px; justify-content: center; }
        .public-step b { display: none; }
        .public-progress-head { padding-left: 16px; padding-right: 16px; }
        .public-panel { padding: 18px 16px; }
        .public-grid-two, .public-grid-three { grid-template-columns: 1fr; }
        .public-field-wide { grid-column: auto; }
        .public-panel-actions { align-items: stretch; flex-direction: column-reverse; margin: 22px -16px -18px; padding: 13px 16px; }
        .public-panel-actions .public-button { width: 100%; }
        .public-required-note { margin: 0; text-align: center; }
        .public-footer { align-items: flex-start; flex-direction: column; gap: 7px; }
    }
</style>
