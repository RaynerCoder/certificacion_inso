<style>
    .pago-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 90;
        display: grid;
        place-items: center;
        padding: 24px;
        overflow-y: auto;
        background: rgba(15, 23, 42, 0.68);
    }

    .pago-dialog {
        width: min(1180px, calc(100% - 48px));
        max-width: none;
        max-height: calc(100vh - 48px);
        padding: 0;
        overflow: visible;
        border: 0;
        border-radius: 6px;
        background: transparent;
    }

    .pago-dialog::backdrop {
        background: rgba(15, 23, 42, 0.68);
    }

    .pago-dialog .pago-modal {
        width: 100%;
        max-height: calc(100vh - 48px);
    }

    .pago-modal,
    .pago-modal * {
        box-sizing: border-box;
    }

    .pago-modal {
        display: flex;
        flex-direction: column;
        width: min(1180px, 100%);
        max-height: calc(100vh - 48px);
        overflow: hidden;
        border: 1px solid #94a3b8;
        border-radius: 6px;
        background: #ffffff;
        color: #172033;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.32);
    }

    .pago-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex: 0 0 auto;
        min-height: 62px;
        padding: 12px 18px 12px 22px;
        background: #27486b;
        color: #ffffff;
    }

    .pago-modal-header > div {
        min-width: 0;
    }

    .pago-modal-header span {
        display: block;
        margin-bottom: 2px;
        color: #cbd9e8;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .pago-modal-header h1 {
        margin: 0;
        overflow-wrap: anywhere;
        font-size: 17px;
        font-weight: 700;
        line-height: 1.35;
    }

    .pago-modal-close {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        flex: 0 0 auto;
        border: 0;
        border-radius: 4px;
        background: transparent;
        color: #ffffff;
        text-decoration: none;
        cursor: pointer;
    }

    .pago-modal-close:hover {
        background: rgba(255, 255, 255, 0.13);
    }

    .pago-modal-content {
        display: grid;
        grid-template-columns: minmax(330px, 0.82fr) minmax(520px, 1.18fr);
        min-height: 0;
        overflow: auto;
    }

    .pago-transaction,
    .pago-method {
        min-width: 0;
        padding: 26px 28px;
    }

    .pago-transaction {
        border-right: 1px solid #dbe3ec;
        background: #f8fafc;
    }

    .pago-method {
        background: #ffffff;
    }

    .pago-section-title,
    .pago-method-heading {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    .pago-section-title > i {
        color: #315b85;
        font-size: 17px;
    }

    .pago-section-title h2,
    .pago-method-heading h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.35;
    }

    .pago-method-heading {
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }

    .pago-method-heading > div:first-child > span {
        display: block;
        margin-bottom: 3px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .pago-method-heading p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .pago-detail-list {
        margin: 0;
        border-top: 1px solid #dbe3ec;
    }

    .pago-detail-list > div {
        display: grid;
        grid-template-columns: 128px minmax(0, 1fr);
        gap: 14px;
        padding: 11px 0;
        border-bottom: 1px solid #dbe3ec;
    }

    .pago-detail-list dt {
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .pago-detail-list dd {
        margin: 0;
        overflow-wrap: anywhere;
        color: #172033;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.5;
    }

    .pago-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 7px;
        border-radius: 4px;
        background: #fef3c7;
        color: #92400e;
        font-size: 11px;
        font-weight: 700;
    }

    .pago-status.is-confirmed {
        background: #dcfce7;
        color: #166534;
    }

    .pago-concept-table {
        margin-top: 24px;
        border: 1px solid #dbe3ec;
        border-radius: 4px;
        overflow-x: auto;
        background: #ffffff;
    }

    .pago-concept-head,
    .pago-concept-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 82px 64px 88px;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
    }

    .pago-concept-head {
        background: #e9eff5;
        color: #475569;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .pago-concept-row {
        color: #334155;
        font-size: 11px;
        line-height: 1.45;
    }

    .pago-concept-head span:not(:first-child),
    .pago-concept-row span:not(:first-child) {
        text-align: right;
    }

    .pago-total {
        display: flex;
        align-items: baseline;
        justify-content: flex-end;
        gap: 20px;
        padding: 18px 4px 0;
    }

    .pago-total span {
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .pago-total strong {
        color: #0f766e;
        font-size: 25px;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .pago-expiration {
        display: flex;
        align-items: center;
        gap: 9px;
        flex: 0 0 auto;
        padding: 8px 11px;
        border: 1px solid #f2d38a;
        border-radius: 4px;
        background: #fff9e8;
        color: #854d0e;
    }

    .pago-expiration > i {
        font-size: 16px;
    }

    .pago-expiration span,
    .pago-expiration strong {
        display: block;
        white-space: nowrap;
    }

    .pago-expiration span {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .pago-expiration strong {
        margin-top: 2px;
        font-size: 12px;
        font-variant-numeric: tabular-nums;
    }

    .pago-method-options {
        display: flex;
        align-items: center;
        min-height: 86px;
        margin-bottom: 20px;
        padding: 10px 0 14px;
        border-bottom: 1px solid #dbe3ec;
    }

    .pago-method-label {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 150px;
        min-height: 58px;
        padding: 10px 14px;
        border: 1px solid #dbe3ec;
        border-bottom: 3px solid #0f766e;
        border-radius: 4px 4px 0 0;
        background: #ffffff;
        color: #27486b;
        font-size: 12px;
        font-weight: 800;
        box-shadow: 0 7px 18px rgba(15, 23, 42, 0.14);
    }

    .pago-method-label > i:first-child {
        font-size: 23px;
    }

    .pago-method-label i:last-child {
        position: absolute;
        top: -8px;
        right: -7px;
        color: #16a34a;
        font-size: 15px;
        background: #ffffff;
        border-radius: 50%;
    }

    .pago-method-coin {
        display: grid;
        place-items: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #475569;
        color: #ffffff;
        font-size: 12px;
    }

    .pago-qr-section > h3 {
        margin: 0 0 18px;
        color: #334155;
        font-size: 17px;
        font-weight: 700;
    }

    .pago-qr-layout {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(250px, 0.95fr);
        align-items: start;
        gap: 30px;
    }

    .pago-instructions h3 {
        margin: 0 0 16px;
        font-size: 14px;
        font-weight: 700;
    }

    .pago-instructions ol {
        display: grid;
        gap: 13px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .pago-instructions li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .pago-instructions li > span {
        display: grid;
        place-items: center;
        width: 23px;
        height: 23px;
        flex: 0 0 auto;
        border-radius: 50%;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 10px;
        font-weight: 800;
    }

    .pago-instructions p {
        margin: 2px 0 0;
        color: #475569;
        font-size: 12px;
        line-height: 1.55;
    }

    .pago-waiting-note {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 20px !important;
        padding: 11px 12px;
        border-left: 3px solid #315b85;
        background: #eff6ff;
    }

    .pago-waiting-note i {
        flex: 0 0 auto;
        margin-top: 2px;
        color: #315b85;
    }

    .pago-qr-column {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 0;
    }

    .pago-qr-frame {
        display: grid;
        place-items: center;
        width: min(100%, 290px);
        aspect-ratio: 1;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #ffffff;
        padding: 10px;
    }

    .pago-qr-frame img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .pago-qr-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 9px;
        padding: 20px;
        text-align: center;
    }

    .pago-qr-loading.is-error {
        color: #991b1b;
    }

    .pago-qr-loading strong {
        font-size: 13px;
    }

    .pago-qr-loading p,
    .pago-qr-caption {
        margin: 0;
        color: #64748b;
        font-size: 11px;
        line-height: 1.5;
        text-align: center;
    }

    .pago-spinner {
        width: 34px;
        height: 34px;
        border: 4px solid #dbeafe;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: pago-girar 0.8s linear infinite;
    }

    .pago-qr-caption {
        margin-top: 10px;
    }

    .pago-qr-actions {
        display: flex;
        justify-content: flex-start;
        gap: 8px;
        width: 100%;
        margin-top: 18px;
    }

    .pago-action,
    .pago-back-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #ffffff;
        color: #334155;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .pago-action:hover,
    .pago-back-action:hover {
        background: #f1f5f9;
    }

    .pago-action.is-primary {
        border-color: #0f766e;
        background: #0f766e;
        color: #ffffff;
    }

    .pago-action.is-primary:hover {
        background: #115e59;
    }

    .pago-action.is-download {
        border-color: #2186b4;
        background: #2797c5;
        color: #ffffff;
    }

    .pago-action.is-download:hover {
        background: #1f7fa9;
    }

    .pago-action.is-share {
        border-color: #d8a52d;
        background: #eab84a;
        color: #3f2b00;
    }

    .pago-action.is-share:hover {
        background: #dca735;
    }

    .pago-action:disabled {
        cursor: wait;
        opacity: 0.65;
    }

    .pago-retry {
        margin-top: 12px;
    }

    .pago-retry[hidden] {
        display: none;
    }

    .pago-new-qr {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 11px;
        padding: 4px;
        border: 0;
        background: transparent;
        color: #0f766e;
        font-size: 11px;
        font-weight: 700;
        text-decoration: underline;
        text-underline-offset: 3px;
        cursor: pointer;
    }

    .pago-new-qr:disabled {
        color: #0f766e;
        opacity: 0.55;
        cursor: not-allowed;
    }

    .pago-confirmed {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 54px 20px;
        text-align: center;
    }

    .pago-confirmed > span {
        display: grid;
        place-items: center;
        width: 64px;
        height: 64px;
        margin-bottom: 14px;
        border-radius: 50%;
        background: #dcfce7;
        color: #15803d;
        font-size: 30px;
    }

    .pago-confirmed strong {
        font-size: 17px;
    }

    .pago-confirmed p {
        max-width: 360px;
        margin: 7px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.6;
    }

    .pago-modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        flex: 0 0 auto;
        padding: 11px 18px;
        border-top: 1px solid #dbe3ec;
        background: #f8fafc;
    }

    .pago-modal-footer > span {
        margin-left: auto;
        color: #64748b;
        font-size: 10px;
        text-align: right;
    }

    .pago-modal-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        min-height: 260px;
        padding: 30px;
        border-radius: 6px;
        background: #ffffff;
        color: #334155;
        text-align: center;
    }

    .pago-modal-loading.is-error {
        color: #991b1b;
    }

    .pago-modal-loading p {
        margin: 0;
        color: #64748b;
        font-size: 12px;
    }

    @keyframes pago-girar {
        to { transform: rotate(360deg); }
    }

    @media (max-width: 980px) {
        .pago-modal-backdrop {
            align-items: start;
            padding: 14px;
        }

        .pago-modal {
            max-height: none;
        }

        .pago-modal-content {
            grid-template-columns: minmax(0, 1fr);
            overflow: visible;
        }

        .pago-transaction {
            border-right: 0;
            border-bottom: 1px solid #dbe3ec;
        }

        .pago-qr-layout {
            grid-template-columns: minmax(0, 1fr) minmax(250px, 0.85fr);
        }
    }

    @media (max-width: 680px) {
        .pago-modal-backdrop {
            display: block;
            padding: 0;
            background: #ffffff;
        }

        .pago-modal {
            width: 100%;
            min-height: 100vh;
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .pago-dialog {
            width: 100%;
            max-height: 100vh;
            margin: 0;
            border-radius: 0;
        }

        .pago-dialog .pago-modal {
            min-height: 100vh;
            max-height: none;
        }

        .pago-modal-header {
            padding: 12px 14px;
        }

        .pago-transaction,
        .pago-method {
            padding: 20px 16px;
        }

        .pago-method-heading,
        .pago-modal-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .pago-expiration {
            align-self: stretch;
            justify-content: center;
        }

        .pago-qr-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .pago-qr-column {
            order: -1;
        }

        .pago-detail-list > div {
            grid-template-columns: minmax(0, 1fr);
            gap: 3px;
        }

        .pago-concept-head,
        .pago-concept-row {
            grid-template-columns: minmax(100px, 1fr) 76px 52px 78px;
            min-width: 330px;
            padding: 9px 8px;
        }

        .pago-total {
            justify-content: space-between;
        }

        .pago-total strong {
            font-size: 21px;
        }

        .pago-qr-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }

        .pago-modal-footer > span {
            text-align: left;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .pago-spinner {
            animation-duration: 1.5s;
        }
    }
</style>
