<style>
    :root {
        --cap-bg: #f5f8f7;
        --cap-border: #d8e2e8;
        --cap-green: #078442;
        --cap-green-dark: #066534;
        --cap-green-soft: #ecfdf3;
        --cap-navy: #0f2037;
        --cap-slate: #607086;
    }

    * { box-sizing: border-box; }

    body {
        background: var(--cap-bg);
        color: var(--cap-navy);
        font-family: Figtree, Arial, sans-serif;
        margin: 0;
    }

    button, input, select, textarea { font: inherit; }

    .public-header {
        align-items: center;
        background: #13243a;
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        min-height: 68px;
        padding: 0 32px;
    }

    .public-brand,
    .public-header-help {
        align-items: center;
        display: flex;
        gap: 10px;
    }

    .public-brand img { height: 37px; width: 37px; }
    .public-brand strong { font-size: 20px; letter-spacing: -.01em; }
    .public-header-help { color: #dbe6ef; font-size: 13px; }

    .public-main {
        margin: 0 auto;
        max-width: 1640px;
        padding: 32px;
    }

    .public-intro {
        align-items: stretch;
        display: grid;
        gap: 22px;
        grid-template-columns: minmax(0, 1fr) minmax(360px, 520px);
        margin-bottom: 30px;
    }

    .public-kicker {
        color: var(--cap-green);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .04em;
        margin: 0 0 6px;
        text-transform: uppercase;
    }

    .public-title {
        font-size: clamp(26px, 3vw, 38px);
        font-weight: 800;
        letter-spacing: -.025em;
        line-height: 1.1;
        margin: 0;
    }

    .public-subtitle {
        color: var(--cap-slate);
        font-size: 15px;
        line-height: 1.5;
        margin: 12px 0 0;
        max-width: 700px;
    }

    .course-summary {
        align-items: center;
        background: #ffffff;
        border: 1px solid var(--cap-border);
        border-radius: 14px;
        display: flex;
        gap: 15px;
        padding: 18px;
    }

    .course-summary-icon {
        align-items: center;
        background: var(--cap-green-soft);
        border-radius: 12px;
        color: var(--cap-green);
        display: inline-flex;
        flex: 0 0 auto;
        font-size: 22px;
        height: 54px;
        justify-content: center;
        width: 54px;
    }

    .course-summary h2 {
        font-size: 16px;
        margin: 0 0 6px;
    }

    .course-meta {
        color: var(--cap-slate);
        display: flex;
        flex-wrap: wrap;
        font-size: 13px;
        gap: 6px 18px;
    }

    .registration-layout {
        display: grid;
        gap: 18px;
        grid-template-columns: minmax(0, 1fr) minmax(285px, 350px);
    }

    .steps-area { min-width: 0; }

    .steps-track {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin: 0 25px 22px;
        position: relative;
    }

    .steps-track::before {
        background: #d8e2e8;
        content: '';
        height: 2px;
        left: 16%;
        position: absolute;
        right: 16%;
        top: 18px;
    }

    .track-step {
        align-items: center;
        color: #94a3b8;
        display: flex;
        flex-direction: column;
        font-size: 11px;
        font-weight: 700;
        gap: 7px;
        position: relative;
        z-index: 1;
    }

    .track-number {
        align-items: center;
        background: var(--cap-bg);
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        display: flex;
        font-size: 14px;
        height: 36px;
        justify-content: center;
        width: 36px;
    }

    .track-step.is-active,
    .track-step.is-complete { color: var(--cap-green); }
    .track-step.is-active .track-number { background: var(--cap-green); border-color: var(--cap-green); color: #ffffff; }
    .track-step.is-complete .track-number { background: var(--cap-green-soft); border-color: var(--cap-green); }

    .step-cards {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .form-step {
        background: #ffffff;
        border: 1px solid var(--cap-border);
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        min-height: 415px;
        padding: 20px;
        transition: border-color .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .form-step.is-active {
        border: 2px solid var(--cap-green);
        box-shadow: 0 10px 30px rgba(7, 132, 66, .08);
        padding: 19px;
    }

    .form-step.is-locked { opacity: .58; }

    .form-step h3 {
        font-size: 17px;
        margin: 0;
    }

    .step-help {
        color: var(--cap-slate);
        font-size: 12px;
        line-height: 1.45;
        margin: 6px 0 18px;
    }

    .field-group { margin-bottom: 14px; }

    .participant-fields {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 14px;
    }

    .participant-fields .field-group {
        margin: 0;
    }

    .participant-fields .field-group-wide {
        grid-column: 1 / -1;
    }

    .field-label {
        display: block;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .public-input {
        background: #ffffff;
        border: 1px solid #c7d2df;
        border-radius: 9px;
        color: var(--cap-navy);
        min-height: 46px;
        outline: none;
        padding: 10px 12px;
        width: 100%;
    }

    .public-input:focus {
        border-color: #16a365;
        box-shadow: 0 0 0 3px rgba(22, 163, 101, .13);
    }

    .public-input:disabled {
        background: #f8fafc;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .field-note {
        color: #7c8a9d;
        display: block;
        font-size: 11px;
        line-height: 1.35;
        margin-top: 5px;
    }

    .public-button {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        color: #334155;
        cursor: pointer;
        display: inline-flex;
        font-size: 13px;
        font-weight: 800;
        gap: 8px;
        justify-content: center;
        min-height: 44px;
        padding: 10px 14px;
        text-decoration: none;
    }

    .public-button-primary {
        background: var(--cap-green);
        border-color: var(--cap-green);
        color: #ffffff;
        margin-top: auto;
        width: 100%;
    }

    .public-button-primary:hover { background: var(--cap-green-dark); }
    .public-button:disabled { cursor: not-allowed; opacity: .55; }

    .person-result {
        background: var(--cap-green-soft);
        border: 1px solid #a7e7c1;
        border-radius: 11px;
        display: flex;
        gap: 10px;
        padding: 12px;
    }

    .result-icon {
        align-items: center;
        background: var(--cap-green);
        border-radius: 50%;
        color: #ffffff;
        display: flex;
        flex: 0 0 auto;
        height: 34px;
        justify-content: center;
        width: 34px;
    }

    .person-result strong { display: block; font-size: 13px; }
    .person-result span { color: #587165; display: block; font-size: 11px; margin-top: 3px; }

    .account-status {
        background: #fef3c7;
        border-radius: 999px;
        color: #92400e !important;
        display: inline-block !important;
        font-weight: 800;
        margin-top: 7px !important;
        padding: 4px 8px;
    }

    .step-message {
        color: var(--cap-slate);
        font-size: 12px;
        line-height: 1.45;
        margin: 15px 0;
        text-align: center;
    }

    .summary-box {
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 10px;
        font-size: 12px;
        line-height: 1.55;
        padding: 13px;
    }

    .summary-box strong { display: block; margin-bottom: 6px; }

    .consent {
        align-items: flex-start;
        color: #475569;
        display: flex;
        font-size: 12px;
        gap: 9px;
        line-height: 1.45;
        margin: 16px 0;
    }

    .consent input { accent-color: var(--cap-green); margin-top: 2px; }

    .identity-guide {
        background: #ffffff;
        border: 1px solid var(--cap-border);
        border-radius: 14px;
        height: max-content;
        padding: 20px;
    }

    .identity-guide h3 { font-size: 16px; margin: 0; }
    .identity-guide > p { color: var(--cap-slate); font-size: 12px; line-height: 1.45; margin: 7px 0 14px; }

    .guide-case {
        align-items: center;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        display: flex;
        gap: 11px;
        margin-top: 9px;
        padding: 11px;
    }

    .guide-case-icon {
        align-items: center;
        background: var(--cap-green-soft);
        border-radius: 9px;
        color: var(--cap-green);
        display: flex;
        flex: 0 0 auto;
        height: 38px;
        justify-content: center;
        width: 38px;
    }

    .guide-case strong { display: block; font-size: 12px; }
    .guide-case span { color: var(--cap-slate); display: block; font-size: 11px; margin-top: 3px; }

    .safety-note {
        align-items: center;
        background: var(--cap-green-soft);
        border: 1px solid #a7e7c1;
        border-radius: 10px;
        color: #05603a;
        display: flex;
        font-size: 12px;
        font-weight: 800;
        gap: 10px;
        line-height: 1.4;
        margin-top: 14px;
        padding: 11px;
    }

    .public-feedback {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 9px;
        color: #9a3412;
        display: none;
        font-size: 12px;
        margin-bottom: 14px;
        padding: 10px;
    }

    .public-feedback.is-visible { display: block; }

    .public-footer {
        align-items: center;
        background: #ffffff;
        border-top: 1px solid var(--cap-border);
        color: var(--cap-slate);
        display: flex;
        font-size: 12px;
        justify-content: space-between;
        margin-top: 32px;
        padding: 18px 32px;
    }

    .public-footer strong { color: var(--cap-navy); display: block; }

    @media (max-width: 1180px) {
        .registration-layout { grid-template-columns: 1fr; }
        .identity-guide { display: grid; gap: 9px; grid-template-columns: repeat(3, 1fr); }
        .identity-guide h3, .identity-guide > p, .safety-note { grid-column: 1 / -1; }
        .guide-case { margin: 0; }
    }

    @media (max-width: 900px) {
        .public-main { padding: 24px 18px; }
        .public-intro { grid-template-columns: 1fr; }
        .step-cards { grid-template-columns: 1fr; }
        .form-step { min-height: auto; }
        .participant-fields { grid-template-columns: 1fr; }
        .participant-fields .field-group-wide { grid-column: auto; }
        .public-button-primary { margin-top: 10px; }
        .identity-guide { grid-template-columns: 1fr; }
        .identity-guide h3, .identity-guide > p, .safety-note { grid-column: auto; }
    }

    @media (max-width: 600px) {
        .public-header { padding: 0 16px; }
        .public-brand strong { font-size: 16px; }
        .public-header-help span { display: none; }
        .course-summary { align-items: flex-start; }
        .course-meta { flex-direction: column; gap: 4px; }
        .steps-track { margin-left: 5px; margin-right: 5px; }
        .track-step span:last-child { display: none; }
        .public-footer { align-items: flex-start; flex-direction: column; gap: 8px; padding: 16px; }
    }
</style>
