{{-- Estilos del detalle del tramite: mantiene separada la presentacion visual de la vista principal. --}}
    <style>
        .cert-show-page {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
        }

        .is-hidden {
            display: none !important;
        }

        .cert-show-panel {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .cert-show-topline {
            display: none;
        }

        .cert-show-header {
            padding: 24px 24px 16px;
        }

        .cert-show-title-row {
            align-items: flex-start;
            display: flex;
            gap: 18px;
            justify-content: space-between;
        }

        .cert-show-title-left {
            align-items: center;
            display: flex;
            gap: 18px;
        }

        .cert-show-icon {
            align-items: center;
            border: 1px solid #99f6e4;
            border-radius: 12px;
            color: #0f766e;
            display: inline-flex;
            height: 56px;
            justify-content: center;
            width: 56px;
        }

        .cert-show-title {
            color: #0f172a;
            font-size: 1.45rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .cert-show-subtitle {
            color: #64748b;
            font-size: 0.92rem;
            margin-top: 8px;
        }

        .cert-show-actions {
            display: none;
            flex-wrap: wrap;
            gap: 10px;
        }

        .cert-show-action {
            align-items: center;
            border-radius: 10px;
            display: inline-flex;
            font-size: 0.9rem;
            font-weight: 800;
            justify-content: center;
            min-height: 42px;
            padding: 0 16px;
        }

        .cert-show-action-edit {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
        }

        .cert-show-action-close {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            color: #0f172a;
        }

        .cert-show-badge {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.69rem;
            font-weight: 900;
            line-height: 1;
            padding: 5px 10px;
            text-transform: uppercase;
        }

        .cert-show-badge i {
            display: none;
        }

        .cert-show-badge-ok {
            background: #d1fae5;
            color: #047857;
        }

        .cert-show-badge-danger {
            background: #ffe4e6;
            color: #b91c1c;
        }

        .cert-show-badge-warning {
            background: #fef3c7;
            color: #b45309;
        }

        .cert-show-badge-info {
            background: #cffafe;
            color: #0e7490;
        }

        .cert-show-badge-neutral {
            background: #e2e8f0;
            color: #475569;
        }

        .cert-show-facts {
            display: grid;
            gap: 0;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            margin-top: 22px;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            overflow: hidden;
        }

        .cert-show-fact {
            align-items: center;
            background: #ffffff;
            border-left: 1px solid #dbe4ee;
            display: flex;
            gap: 12px;
            min-height: 78px;
            padding: 0 18px;
        }

        .cert-show-fact:first-child {
            border-left: 0;
        }

        .cert-show-label {
            color: #334155;
            display: block;
            font-size: 0.76rem;
            font-weight: 750;
            margin-bottom: 4px;
        }

        .cert-show-value {
            color: #0f172a;
            font-size: 0.92rem;
            font-weight: 900;
        }

        .cert-show-fact-icon {
            color: #1f2937;
            flex: 0 0 auto;
        }

        .cert-show-content {
            counter-reset: cert-section;
            padding: 0 24px 24px;
        }

        .cert-flow-stepper {
            display: none;
        }

        .cert-flow-stepper-inner {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            position: relative;
        }

        .cert-flow-stepper-inner::before {
            background: #d7dee8;
            content: "";
            height: 2px;
            left: 12.5%;
            position: absolute;
            right: 12.5%;
            top: 20px;
        }

        .cert-flow-step {
            align-items: center;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        .cert-flow-step-number {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #94a3b8;
            border-radius: 999px;
            color: #475569;
            display: inline-flex;
            font-size: 0.86rem;
            font-weight: 900;
            height: 42px;
            justify-content: center;
            width: 42px;
        }

        .cert-flow-step.is-active .cert-flow-step-number {
            background: linear-gradient(135deg, #10b981, #059669);
            border-color: #10b981;
            box-shadow: 0 10px 18px rgba(5, 150, 105, 0.22);
            color: #ffffff;
        }

        .cert-flow-step-label {
            color: #0f172a;
            font-size: 0.78rem;
            font-weight: 900;
            line-height: 1.25;
            text-align: center;
        }

        .cert-show-grid-two {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .cert-show-content > .cert-show-grid-two {
            background: #ffffff;
            border: 1px solid #dfe7ef;
            border-radius: 12px;
            gap: 0;
            overflow: hidden;
        }

        .cert-show-content > .cert-show-grid-two .cert-show-card {
            border: 0;
            border-radius: 0;
        }

        .cert-show-content > .cert-show-grid-two .cert-show-card + .cert-show-card {
            border-left: 1px solid #e2e8f0;
        }

        .cert-show-info-grid {
            display: grid;
            gap: 18px 28px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .cert-show-info-block {
            min-width: 0;
        }

        .cert-show-info-block-title {
            color: #0f766e;
            font-size: 0.82rem;
            font-weight: 900;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .cert-show-card {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            overflow: hidden;
        }

        .cert-show-card-head {
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            min-height: 50px;
            padding: 0 18px;
        }

        .cert-show-content > .cert-show-card > .cert-show-card-head::before {
            display: none;
        }

        .cert-section-number {
            align-items: center;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 0.82rem;
            font-weight: 950;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .cert-show-card-head > svg,
        .cert-show-card-head > i {
            display: none;
        }

        .cert-show-card-title {
            color: #0f172a;
            font-size: 0.95rem;
            font-weight: 900;
        }

        .cert-show-card-body {
            padding: 18px;
        }

        .cert-info-stack {
            display: grid;
            gap: 16px;
        }

        .cert-info-block {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #10b981;
            border-radius: 12px;
            overflow: hidden;
        }

        .cert-info-block.is-product {
            border-left-color: #10b981;
        }

        .cert-info-block.is-payment {
            border-left-color: #f59e0b;
        }

        .cert-info-block-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 13px 16px;
        }

        .cert-info-block-title {
            color: #0f172a;
            font-size: 0.95rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .cert-info-block-meta {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
            margin-top: 3px;
        }

        .cert-info-block-body {
            padding: 16px;
        }

        .cert-subsection-title {
            align-items: center;
            color: #0f766e;
            display: flex;
            font-size: 0.8rem;
            font-weight: 900;
            gap: 8px;
            margin: 18px 0 8px;
            text-transform: uppercase;
        }

        .cert-subsection-title:first-child {
            margin-top: 0;
        }

        .cert-show-definition {
            display: grid;
            gap: 10px;
        }

        .cert-show-definition-compact {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .cert-show-definition-wide {
            grid-column: span 3;
        }

        .cert-show-definition-row {
            display: grid;
            gap: 12px;
            grid-template-columns: 180px minmax(0, 1fr);
        }

        .cert-show-definition dt {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .cert-show-definition dd {
            color: #0f172a;
            font-size: 0.86rem;
            font-weight: 700;
            min-width: 0;
        }

        .cert-party-grid {
            display: grid;
            gap: 0;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .cert-party-block {
            min-width: 0;
            padding-right: 22px;
        }

        .cert-party-block + .cert-party-block {
            border-left: 1px solid #e2e8f0;
            padding-left: 22px;
            padding-right: 0;
        }

        .cert-party-block h3 {
            color: #0f766e;
            font-size: 0.86rem;
            font-weight: 900;
            margin: 0 0 12px;
            text-transform: uppercase;
        }

        .cert-show-table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow-x: auto;
        }

        .cert-show-mini-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .cert-show-list {
            display: grid;
            gap: 14px;
        }

        .cert-show-item {
            border-bottom: 1px solid #eef2f7;
            padding-bottom: 12px;
        }

        .cert-show-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .cert-show-table {
            border-collapse: collapse;
            width: 100%;
        }

        .cert-show-table th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 0.78rem;
            font-weight: 900;
            padding: 11px 12px;
            text-align: left;
        }

        .cert-show-table td {
            border-top: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.88rem;
            padding: 11px 12px;
            vertical-align: top;
        }

        /* Tabla de correccion del solicitante: mantiene las columnas legibles aunque el requisito u observacion sean largos. */
        .cert-correction-table th,
        .cert-correction-table td {
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .cert-correction-table th:nth-child(1),
        .cert-correction-table td:nth-child(1) {
            text-align: center;
            width: 58px;
        }

        .cert-correction-table th:nth-child(2),
        .cert-correction-table td:nth-child(2) {
            min-width: 220px;
        }

        .cert-correction-table th:nth-child(3),
        .cert-correction-table td:nth-child(3),
        .cert-correction-table th:nth-child(6),
        .cert-correction-table td:nth-child(6) {
            width: 132px;
        }

        .cert-correction-table th:nth-child(4),
        .cert-correction-table td:nth-child(4) {
            min-width: 210px;
        }

        .cert-correction-table th:nth-child(5),
        .cert-correction-table td:nth-child(5) {
            min-width: 230px;
        }

        .cert-requirement-row.is-observed {
            background: #fff7f7;
            outline: 1px solid #fca5a5;
            outline-offset: -1px;
        }

        .cert-requirement-row.is-observed td {
            border-top-color: #fecaca;
        }

        .cert-show-pill {
            border-radius: 999px;
            display: inline-flex;
            gap: 6px;
            font-size: 0.72rem;
            font-weight: 900;
            align-items: center;
            padding: 4px 10px;
            text-transform: uppercase;
        }

        .cert-show-pill-ok {
            background: #d1fae5;
            color: #047857;
        }

        .cert-show-pill-warn {
            background: #fef3c7;
            color: #b45309;
        }

        .cert-show-pill-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .cert-review-alert {
            border: 1px solid #a7f3d0;
            border-radius: 10px;
            background: #ecfdf5;
            color: #065f46;
            margin-bottom: 14px;
            padding: 12px 14px;
        }

        .cert-review-alert strong {
            display: block;
            font-size: 0.9rem;
            font-weight: 900;
        }

        .cert-review-alert span {
            display: block;
            font-size: 0.82rem;
            font-weight: 650;
            margin-top: 3px;
        }

        .cert-review-select,
        .cert-review-textarea {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #334155;
            font-size: 0.86rem;
            width: 100%;
        }

        .cert-review-select {
            min-height: 38px;
            padding: 0 10px;
        }

        .cert-review-textarea {
            min-height: 58px;
            padding: 9px 10px;
            resize: vertical;
        }

        .cert-review-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 14px;
        }

        .cert-review-actions .tramite-warning-box {
            max-width: 620px;
        }

        .cert-review-submit {
            align-items: center;
            background: #059669;
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            font-size: 0.84rem;
            font-weight: 900;
            gap: 7px;
            justify-content: center;
            min-height: 38px;
            padding: 0 13px;
            white-space: nowrap;
        }

        .cert-review-submit:hover {
            background: #047857;
        }

        .cert-review-table {
            min-width: 980px;
            table-layout: fixed;
        }

        .cert-review-col-number {
            width: 40px;
        }

        .cert-review-col-requirement {
            width: 22%;
        }

        .cert-review-col-evidence-code {
            width: 92px;
        }

        .cert-review-col-document {
            width: 125px;
        }

        .cert-review-col-result {
            width: 140px;
        }

        .cert-review-col-status {
            width: 118px;
        }

        .cert-review-col-observation {
            width: auto;
        }

        .cert-evidence-code-chip {
            max-width: 100%;
            justify-content: center;
            text-align: center;
            white-space: normal;
            word-break: break-word;
        }

        .cert-review-col-history {
            width: 82px;
        }

        .cert-review-observation-box {
            align-items: flex-start;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: none;
            gap: 10px;
            justify-content: space-between;
            padding: 9px 10px;
        }

        .cert-review-observation-box.is-visible {
            display: flex;
        }

        .cert-review-observation-text {
            color: #475569;
            font-size: 0.82rem;
            font-weight: 650;
            line-height: 1.35;
            white-space: pre-line;
        }

        .cert-review-edit {
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            color: #334155;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 0.74rem;
            font-weight: 900;
            gap: 6px;
            min-height: 30px;
            padding: 0 9px;
        }

        .cert-review-edit:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .cert-review-observation-preview {
            margin-top: 7px;
            color: #92400e;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .cert-review-note {
            border: 1px solid #fde68a;
            border-radius: 10px;
            background: #fffbeb;
            color: #92400e;
            margin-bottom: 14px;
            padding: 12px 14px;
            white-space: pre-line;
        }

        .cert-requirements-layout {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 400px);
            align-items: start;
        }

        .cert-requirements-main {
            min-width: 0;
        }

        .cert-requirement-history-panel {
            border: 1px solid #dfe7ef;
            border-radius: 8px;
            background: #ffffff;
            overflow: hidden;
            position: sticky;
            top: 88px;
        }

        .cert-requirement-history-head {
            border-bottom: 1px solid #e2e8f0;
            padding: 13px 14px;
        }

        .cert-requirement-history-title {
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 900;
            margin: 0;
        }

        .cert-requirement-history-subtitle {
            color: #64748b;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.35;
            margin-top: 4px;
        }

        .cert-requirement-history-list {
            display: grid;
            gap: 0;
            max-height: 520px;
            overflow-y: auto;
            padding: 16px 18px;
        }

        .cert-history-item {
            border: 0;
            border-left: 2px solid #cbd5e1;
            border-radius: 0;
            background: #ffffff;
            padding: 0 0 18px 20px;
            position: relative;
        }

        .cert-history-item::before {
            background: #94a3b8;
            border-radius: 999px;
            content: "";
            height: 12px;
            left: -7px;
            position: absolute;
            top: 2px;
            width: 12px;
        }

        .cert-history-item.is-success {
            border-left-color: #059669;
        }

        .cert-history-item.is-success::before {
            background: #059669;
        }

        .cert-history-item.is-danger {
            border-left-color: #dc2626;
        }

        .cert-history-item.is-danger::before {
            background: #dc2626;
        }

        .cert-history-item.is-warning {
            border-left-color: #f59e0b;
        }

        .cert-history-item.is-warning::before {
            background: #f59e0b;
        }

        .cert-history-item-title {
            color: #0f172a;
            font-size: 0.78rem;
            font-weight: 900;
        }

        .cert-history-item-meta {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 750;
            margin-top: 2px;
        }

        .cert-history-item-text {
            color: #334155;
            font-size: 0.8rem;
            font-weight: 650;
            line-height: 1.4;
            margin-top: 7px;
            white-space: pre-line;
        }

        .cert-history-button {
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #334155;
            display: inline-flex;
            font-size: 0.75rem;
            font-weight: 900;
            gap: 6px;
            justify-content: center;
            min-height: 31px;
            padding: 0 9px;
            white-space: nowrap;
        }

        .cert-history-button:hover,
        .cert-history-button.is-active {
            background: #ecfdf5;
            border-color: #10b981;
            color: #047857;
        }

        .cert-detail-actions {
            align-items: center;
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
            padding: 16px 14px 0;
        }

        .cert-detail-action-muted {
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #94a3b8;
            display: inline-flex;
            font-size: 0.84rem;
            font-weight: 850;
            gap: 8px;
            min-height: 42px;
            padding: 0 17px;
        }

        .cert-detail-action-muted[disabled] {
            cursor: not-allowed;
            opacity: 0.72;
        }

        .cert-detail-warning {
            align-items: center;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            color: #92400e;
            display: inline-flex;
            font-size: 0.8rem;
            font-weight: 800;
            gap: 10px;
            min-height: 42px;
            max-width: 360px;
            padding: 0 14px;
        }

        .cert-detail-upload {
            align-items: center;
            background: #059669;
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            font-size: 0.82rem;
            font-weight: 900;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
        }

        .cert-history-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 700;
            padding: 16px;
            text-align: center;
        }

        .cert-timeline-note {
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .cert-flow-timeline {
            display: grid;
            gap: 12px;
            grid-auto-columns: minmax(170px, 1fr);
            grid-auto-flow: column;
            overflow-x: auto;
            padding: 2px 2px 10px;
        }

        .cert-flow-timeline-item {
            display: grid;
            gap: 11px;
            grid-template-rows: auto 1fr;
            min-width: 175px;
            position: relative;
        }

        .cert-flow-timeline-item:not(:last-child)::after {
            border-top: 2px dashed #cbd5e1;
            content: "";
            left: 43px;
            position: absolute;
            right: -45px;
            top: 20px;
        }

        .cert-flow-timeline-dot {
            align-items: center;
            background: #94a3b8;
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            font-size: 0.75rem;
            font-weight: 900;
            height: 34px;
            justify-content: center;
            position: relative;
            width: 34px;
            z-index: 2;
        }

        .cert-flow-timeline-item.is-active .cert-flow-timeline-dot {
            background: #059669;
        }

        .cert-flow-timeline-item.is-danger .cert-flow-timeline-dot {
            background: #dc2626;
        }

        .cert-flow-timeline-item.is-warning .cert-flow-timeline-dot {
            background: #f59e0b;
        }

        .cert-flow-timeline-box {
            background: #ffffff;
            padding: 0 2px;
        }

        .cert-detail-full-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .cert-detail-full-panel {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            min-width: 0;
            padding: 14px;
        }

        .cert-detail-full-panel.is-wide {
            grid-column: 1 / -1;
        }

        .cert-detail-full-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 0.92rem;
            font-weight: 900;
            gap: 8px;
            margin-bottom: 12px;
        }

        .cert-detail-full-title i {
            color: #059669;
        }

        .cert-product-list {
            display: grid;
            gap: 16px;
        }

        .cert-product-panel {
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            overflow: hidden;
        }

        .cert-product-panel-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .cert-product-panel-title {
            color: #0f172a;
            font-size: 0.95rem;
            font-weight: 900;
        }

        .cert-product-panel-meta {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
            margin-top: 2px;
        }

        .cert-product-panel-body {
            display: grid;
            gap: 16px;
            padding: 14px;
        }

        .cert-flow-timeline-title {
            color: #0f172a;
            font-size: 0.86rem;
            font-weight: 900;
        }

        .cert-flow-timeline-meta {
            color: #64748b;
            font-size: 0.76rem;
            font-weight: 750;
            margin-top: 3px;
        }

        .cert-flow-timeline-text {
            color: #334155;
            font-size: 0.82rem;
            font-weight: 650;
            line-height: 1.35;
            margin-top: 8px;
            white-space: pre-line;
        }

        .cert-derive-box {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            margin-bottom: 16px;
            padding: 14px;
        }

        .cert-derive-title {
            color: #0f172a;
            font-size: 0.95rem;
            font-weight: 900;
            margin: 0 0 4px;
        }

        .cert-derive-help {
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 650;
            margin-bottom: 12px;
        }

        .cert-derive-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(280px, 420px) auto;
            align-items: end;
        }

        .cert-derive-grid.is-transfer {
            grid-template-columns: minmax(240px, 340px) minmax(280px, 1fr) auto;
        }

        .cert-derive-submit {
            align-items: center;
            background: #0f172a;
            border-radius: 8px;
            color: #ffffff;
            display: inline-flex;
            font-size: 0.82rem;
            font-weight: 900;
            gap: 7px;
            justify-content: center;
            min-height: 38px;
            padding: 0 12px;
            white-space: nowrap;
        }

        .cert-derive-submit:hover {
            background: #1e293b;
        }

        .cert-show-progress {
            background: #e2e8f0;
            border-radius: 999px;
            height: 8px;
            overflow: hidden;
            width: 100%;
        }

        .cert-show-progress-bar {
            background: #0f766e;
            height: 100%;
        }

        .cert-detail-shell {
            display: grid;
            gap: 22px;
            grid-template-columns: minmax(0, 1.45fr) minmax(330px, 0.75fr);
        }

        .cert-summary-strip {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 22px;
        }

        .cert-summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
        }

        .cert-summary-box.is-date {
            background: #ecfeff;
            border-color: #bae6fd;
        }

        .cert-summary-box.is-status {
            background: #f8fafc;
        }

        .cert-person-block {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
        }

        .cert-person-block+.cert-person-block {
            margin-top: 14px;
        }

        .cert-doc-button {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 10px;
            color: #047857;
            display: inline-flex;
            font-size: 0.86rem;
            font-weight: 900;
            justify-content: center;
            min-height: 42px;
            padding: 0 16px;
        }

        .cert-empty-note {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            color: #64748b;
            font-size: 0.9rem;
            padding: 18px;
            text-align: center;
        }

        @media (max-width: 1180px) {

            .cert-show-title-row,
            .cert-show-grid-two,
            .cert-detail-shell,
            .cert-requirements-layout,
            .cert-detail-full-grid {
                grid-template-columns: 1fr;
            }

            .cert-requirement-history-panel {
                position: static;
            }

            .cert-show-content > .cert-show-grid-two .cert-show-card + .cert-show-card {
                border-left: 0;
                border-top: 1px solid #e2e8f0;
            }

            .cert-show-title-row {
                display: grid;
            }

            .cert-show-facts {
                grid-template-columns: 1fr;
            }

            .cert-summary-strip {
                grid-template-columns: 1fr;
            }

            .cert-show-mini-grid {
                grid-template-columns: 1fr;
            }

            .cert-show-fact {
                border-left: 0;
                border-top: 1px solid #e2e8f0;
                padding: 14px 0 0;
            }

            .cert-show-definition-row {
                grid-template-columns: 1fr;
            }

            .cert-show-definition-compact,
            .cert-party-grid {
                grid-template-columns: 1fr;
            }

            .cert-show-definition-wide {
                grid-column: span 1;
            }

            .cert-party-block {
                padding-right: 0;
            }

            .cert-party-block + .cert-party-block {
                border-left: 0;
                border-top: 1px solid #e2e8f0;
                margin-top: 16px;
                padding-left: 0;
                padding-top: 16px;
            }

            .cert-derive-grid {
                grid-template-columns: 1fr;
            }

            .cert-show-fact:first-child {
                border-top: 0;
            }
        }
        .tramite-detail-v2 {
            background: #f8fafc;
            border-radius: 10px;
            padding: 14px;
        }

        .tramite-shell {
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            padding: 0;
        }

        .tramite-header {
            align-items: center;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 16px;
            order: 1;
        }

        .tramite-title {
            color: #0f172a;
            font-size: 1.45rem;
            font-weight: 950;
            line-height: 1.15;
        }

        .tramite-breadcrumb {
            color: #64748b;
            display: flex;
            flex-wrap: wrap;
            font-size: 0.8rem;
            font-weight: 750;
            gap: 8px;
        }

        .tramite-breadcrumb strong {
            color: #047857;
        }

        .tramite-summary-bar {
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin-bottom: 16px;
            order: 2;
            overflow: hidden;
        }

        .tramite-summary-item {
            align-items: center;
            background: #ffffff;
            border-left: 1px solid #dbe4ee;
            display: flex;
            gap: 13px;
            min-height: 76px;
            min-width: 0;
            padding: 13px 18px;
        }

        .tramite-summary-item:first-child {
            border-left: 0;
        }

        .tramite-summary-icon {
            color: #1f2937;
            flex: 0 0 auto;
            font-size: 1.35rem;
        }

        .tramite-summary-label {
            color: #475569;
            display: block;
            font-size: 0.76rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .tramite-summary-value {
            color: #0f172a;
            display: block;
            font-size: 0.92rem;
            font-weight: 950;
            overflow-wrap: anywhere;
        }

        .tramite-grid-main {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1fr) 400px;
            margin-bottom: 16px;
        }

        .tramite-section-detail {
            margin-bottom: 16px;
            order: 4;
        }

        .tramite-section-review {
            order: 6;
        }

        .tramite-section-timeline {
            order: 7;
        }

        .tramite-section-technical {
            order: 3;
        }

        .tramite-card {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            min-width: 0;
            overflow: hidden;
        }

        .tramite-card-head {
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            min-height: 56px;
            padding: 0 18px;
        }

        .tramite-card-title {
            color: #0f172a;
            font-size: 0.98rem;
            font-weight: 950;
        }

        .tramite-card-body {
            padding: 16px;
        }

        .tramite-table-wrap {
            overflow-x: auto;
            width: 100%;
        }

        .tramite-table {
            border-collapse: collapse;
            min-width: 860px;
            width: 100%;
        }

        .tramite-requirements-table {
            min-width: 940px;
        }

        .tramite-requirements-table th:first-child,
        .tramite-requirements-table td:first-child {
            width: 52px;
        }

        .tramite-requirements-table th:nth-child(3),
        .tramite-requirements-table td:nth-child(3) {
            width: 120px;
        }

        .tramite-requirements-table th:nth-child(4),
        .tramite-requirements-table td:nth-child(4) {
            width: 150px;
        }

        .tramite-requirements-table th:nth-child(5),
        .tramite-requirements-table td:nth-child(5) {
            width: 150px;
        }

        .tramite-requirements-table th:last-child,
        .tramite-requirements-table td:last-child {
            width: 132px;
        }

        .tramite-requirements-table .cert-review-observation-box {
            display: none !important;
        }

        .tramite-section-review {
            grid-template-columns: minmax(0, 1fr);
        }

        .tramite-check-options {
            align-items: center;
            display: inline-flex;
            gap: 8px;
            white-space: nowrap;
        }

        .tramite-check-option {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 6px;
            color: #475569;
            cursor: pointer;
            display: inline-flex;
            font-size: 0.76rem;
            font-weight: 900;
            gap: 6px;
            min-height: 24px;
            padding: 0 2px;
            transition: color 160ms ease;
            user-select: none;
        }

        .tramite-check-option input {
            appearance: none;
            -webkit-appearance: none;
            height: 0;
            opacity: 0;
            pointer-events: none;
            position: absolute;
            width: 0;
        }

        .tramite-check-box {
            align-items: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            color: transparent;
            display: inline-flex;
            height: 18px;
            justify-content: center;
            transition: all 160ms ease;
            width: 18px;
        }

        .tramite-check-option.is-yes.is-selected .tramite-check-box {
            background: #059669;
            border-color: #047857;
            color: #ffffff;
        }

        .tramite-check-option.is-yes.is-selected {
            color: #047857;
        }

        .tramite-check-option.is-no.is-selected .tramite-check-box {
            background: #dc2626;
            border-color: #b91c1c;
            color: #ffffff;
        }

        .tramite-check-option.is-no.is-selected {
            color: #b91c1c;
        }

        .tramite-payment-link {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #86efac;
            border-radius: 7px;
            color: #047857;
            display: inline-flex;
            font-size: 0.75rem;
            font-weight: 950;
            gap: 7px;
            min-height: 30px;
            padding: 0 10px;
            white-space: nowrap;
        }

        .tramite-payment-link:hover {
            background: #d1fae5;
            border-color: #10b981;
        }

        .tramite-modal {
            display: none;
            inset: 0;
            position: fixed;
            z-index: 80;
        }

        .tramite-modal.is-open {
            align-items: center;
            display: flex;
            justify-content: center;
            padding: 18px;
        }

        .tramite-modal-backdrop {
            background: rgba(15, 23, 42, .42);
            inset: 0;
            position: absolute;
        }

        .tramite-modal-panel {
            background: #ffffff;
            border: 1px solid #dbe4ef;
            border-radius: 10px;
            box-shadow: 0 22px 70px rgba(15, 23, 42, .18);
            max-height: min(720px, calc(100vh - 36px));
            max-width: 980px;
            overflow: auto;
            padding: 18px;
            position: relative;
            width: min(980px, 100%);
            z-index: 1;
        }

        .tramite-modal-head {
            align-items: flex-start;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
        }

        .tramite-modal-head p {
            color: #64748b;
            font-size: .82rem;
            font-weight: 650;
            margin-top: 3px;
        }

        .tramite-modal-close {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #dbe4ef;
            border-radius: 8px;
            color: #475569;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            width: 34px;
        }

        .tramite-modal-close:hover {
            background: #eef2f7;
            color: #0f172a;
        }

        .tramite-payment-form-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(12, minmax(0, 1fr));
        }

        .tramite-payment-field-3 { grid-column: span 3 / span 3; }
        .tramite-payment-field-4 { grid-column: span 4 / span 4; }
        .tramite-payment-field-6 { grid-column: span 6 / span 6; }

        .tramite-payment-pdf {
            align-items: center;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            min-height: 42px;
            padding: 7px 8px;
        }

        .tramite-payment-pdf-icon {
            align-items: center;
            background: #fff1f2;
            border-radius: 7px;
            color: #dc2626;
            display: inline-flex;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .tramite-payment-pdf-name {
            color: #334155;
            flex: 1 1 180px;
            font-size: 0.82rem;
            font-weight: 850;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .tramite-payment-pdf-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .tramite-payment-pdf-button {
            align-items: center;
            border-radius: 7px;
            display: inline-flex;
            font-size: 0.75rem;
            font-weight: 900;
            gap: 6px;
            min-height: 30px;
            padding: 0 10px;
        }

        .tramite-payment-pdf-button.is-select {
            background: #ecfdf5;
            border: 1px solid #86efac;
            color: #047857;
        }

        .tramite-payment-pdf-button.is-view {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .tramite-payment-pdf-button.is-remove {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #dc2626;
        }

        .tramite-payment-pdf-button:disabled {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
        }

        @media (max-width: 900px) {
            .tramite-payment-field-3,
            .tramite-payment-field-4,
            .tramite-payment-field-6 {
                grid-column: 1 / -1;
            }
        }

        .tramite-table th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
            font-size: 0.77rem;
            font-weight: 950;
            padding: 12px;
            text-align: left;
            white-space: nowrap;
        }

        .tramite-table td {
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.86rem;
            padding: 13px 12px;
            vertical-align: middle;
        }

        .tramite-row-observed {
            background: #fff7f7;
            box-shadow: inset 0 0 0 1px #fca5a5;
        }

        .tramite-pill {
            align-items: center;
            border: 1px solid;
            border-radius: 7px;
            display: inline-flex;
            font-size: 0.75rem;
            font-weight: 900;
            gap: 7px;
            min-height: 30px;
            padding: 0 10px;
            white-space: nowrap;
        }

        .tramite-pill-ok {
            background: #d1fae5;
            border-color: #86efac;
            color: #047857;
        }

        .tramite-pill-danger {
            background: #ffe4e6;
            border-color: #fca5a5;
            color: #dc2626;
        }

        .tramite-pill-warn {
            background: #fef3c7;
            border-color: #fbbf24;
            color: #d97706;
        }

        .tramite-pill-neutral {
            background: #e2e8f0;
            border-color: #cbd5e1;
            color: #475569;
        }

        .tramite-status-chip {
            border: 0;
            border-radius: 999px;
            cursor: default;
            font-size: 0.68rem;
            gap: 0;
            line-height: 1.15;
            min-height: 22px;
            padding: 3px 8px;
            pointer-events: none;
            user-select: none;
        }

        .tramite-status-chip [data-status-icon] {
            display: none;
        }

        .tramite-status-button {
            cursor: pointer;
            transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        .tramite-status-button:hover {
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }

        .tramite-observation-box {
            border: 1px solid transparent;
            border-radius: 7px;
            color: #475569;
            display: inline-flex;
            font-size: 0.84rem;
            font-weight: 750;
            line-height: 1.4;
            max-width: 100%;
            padding: 9px 11px;
        }

        .tramite-observation-box.is-danger {
            background: #fff7f7;
            border-color: #fca5a5;
            color: #dc2626;
        }

        .tramite-requirement-note {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-top: 8px;
            max-width: 100%;
        }

        .tramite-requirement-note strong {
            color: #991b1b;
            font-size: 0.72rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .cert-correction-document-cell {
            align-items: flex-start;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 190px;
        }

        .cert-correction-upload {
            align-items: center;
            background: #f8fafc;
            border: 1px dashed #94a3b8;
            border-radius: 7px;
            color: #0f766e;
            cursor: pointer;
            display: inline-flex;
            gap: 7px;
            min-height: 34px;
            padding: 7px 10px;
            transition: border-color 160ms ease, background 160ms ease;
        }

        .cert-correction-upload:hover {
            background: #ecfdf5;
            border-color: #10b981;
        }

        .cert-correction-upload input {
            display: none;
        }

        .cert-correction-upload-text {
            font-size: 0.78rem;
            font-weight: 850;
        }

        .cert-correction-observation-text {
            color: #991b1b;
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            line-height: 1.45;
            max-width: 100%;
        }

        .cert-correction-selected-file {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 7px;
            color: #065f46;
            display: none;
            gap: 8px;
            max-width: 100%;
            padding: 7px 9px;
        }

        .cert-correction-selected-file.is-visible {
            display: flex;
        }

        .cert-correction-selected-name {
            flex: 1 1 auto;
            font-size: 0.78rem;
            font-weight: 850;
            line-height: 1.25;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .cert-correction-remove-file {
            align-items: center;
            background: #ffffff;
            border: 1px solid #86efac;
            border-radius: 999px;
            color: #047857;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 0.72rem;
            font-weight: 900;
            min-height: 26px;
            padding: 0 9px;
        }

        .cert-correction-remove-file:hover {
            background: #dcfce7;
        }

        .tramite-detail-v2 .cert-history-button {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            color: #0f172a;
            min-height: 34px;
            padding: 0 12px;
        }

        .tramite-detail-v2 .cert-history-button:hover,
        .tramite-detail-v2 .cert-history-button.is-active {
            background: #ffffff;
            border-color: #059669;
            box-shadow: inset 0 0 0 1px #059669;
            color: #047857;
        }

        .tramite-history-panel {
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }

        .tramite-history-list {
            display: grid;
            gap: 0;
            max-height: 430px;
            overflow-y: auto;
            padding: 18px 18px 8px;
        }

        .tramite-history-item {
            border-left: 2px solid #cbd5e1;
            padding: 0 0 18px 22px;
            position: relative;
        }

        .tramite-history-item::before {
            background: #94a3b8;
            border-radius: 999px;
            content: "";
            height: 13px;
            left: -7px;
            position: absolute;
            top: 1px;
            width: 13px;
        }

        .tramite-history-item.is-success,
        .tramite-history-item.is-success::before {
            border-left-color: #059669;
        }

        .tramite-history-item.is-success::before {
            background: #059669;
        }

        .tramite-history-item.is-danger {
            border-left-color: #dc2626;
        }

        .tramite-history-item.is-danger::before {
            background: #dc2626;
        }

        .tramite-history-item.is-warning {
            border-left-color: #f59e0b;
        }

        .tramite-history-item.is-warning::before {
            background: #f59e0b;
        }

        .tramite-history-title {
            color: #0f172a;
            font-size: 0.83rem;
            font-weight: 950;
        }

        .tramite-history-meta,
        .tramite-history-item .cert-history-item-meta {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 750;
            margin-top: 4px;
        }

        .cert-history-item-user {
            color: #0f172a;
            font-size: 0.78rem;
            font-weight: 850;
            margin-top: 3px;
        }

        .cert-history-item-cargo {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 650;
            line-height: 1.25;
            margin-top: 1px;
        }

        .tramite-history-text {
            color: #334155;
            font-size: 0.8rem;
            font-weight: 650;
            line-height: 1.4;
            margin-top: 8px;
            white-space: pre-line;
        }

        .tramite-actions-row {
            align-items: center;
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 14px;
        }

        .tramite-btn {
            align-items: center;
            border-radius: 7px;
            display: inline-flex;
            font-size: 0.82rem;
            font-weight: 900;
            gap: 8px;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            white-space: nowrap;
        }

        .tramite-btn-muted {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .tramite-btn-muted:disabled {
            cursor: not-allowed;
            opacity: 0.75;
        }

        .tramite-btn-notify {
            background: #fff7ed;
            border: 1px solid #fdba74;
            box-shadow: 0 8px 16px rgba(234, 88, 12, 0.12);
            color: #9a3412;
        }

        .tramite-btn-notify:hover {
            background: #ffedd5;
            border-color: #fb923c;
            color: #7c2d12;
        }

        .tramite-btn-emit {
            background: #ecfdf5;
            border: 1px solid #34d399;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.12);
            color: #047857;
        }

        .tramite-btn-emit:hover {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .tramite-btn-primary {
            background: #059669;
            border: 1px solid #047857;
            box-shadow: 0 10px 18px rgba(5, 150, 105, 0.18);
            color: #ffffff;
        }

        .tramite-warning-box {
            align-items: center;
            border: 1px solid #fbbf24;
            border-radius: 7px;
            color: #92400e;
            display: inline-flex;
            flex: 1 1 280px;
            font-size: 0.8rem;
            font-weight: 800;
            gap: 10px;
            min-height: 40px;
            padding: 8px 12px;
        }

        .tramite-detail-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .tramite-detail-panel {
            border: 0;
            border-top: 1px solid #e2e8f0;
            border-radius: 0;
            min-width: 0;
            padding: 18px 0;
        }

        .tramite-section-detail .tramite-card-body {
            padding: 0 16px 16px;
        }

        .tramite-section-detail .tramite-detail-grid {
            gap: 0 22px;
        }

        .tramite-section-detail .tramite-detail-panel:nth-child(1),
        .tramite-section-detail .tramite-detail-panel:nth-child(2) {
            border-top: 0;
        }

        .tramite-section-detail .tramite-detail-panel:nth-child(2) {
            border-left: 1px solid #e2e8f0;
            padding-left: 22px;
        }

        .tramite-detail-panel.is-wide {
            grid-column: 1 / -1;
        }

        .tramite-detail-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 0.92rem;
            font-weight: 950;
            gap: 8px;
            margin-bottom: 12px;
        }

        .tramite-section-title-row {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .tramite-section-title-row .tramite-detail-title {
            margin-bottom: 0;
        }

        .tramite-product-register-btn {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 7px;
            color: #047857;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 0.78rem;
            font-weight: 850;
            gap: 7px;
            min-height: 34px;
            padding: 7px 11px;
            text-decoration: none;
            transition: background 0.16s ease, border-color 0.16s ease, color 0.16s ease;
            white-space: nowrap;
        }

        .tramite-product-register-btn:hover {
            background: #d1fae5;
            border-color: #34d399;
            color: #065f46;
        }

        .tramite-payment-register-btn {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .tramite-payment-register-btn:hover {
            background: #ffedd5;
            border-color: #fdba74;
            color: #9a3412;
        }

        .tramite-payment-inline-btn {
            cursor: pointer;
            font: inherit;
        }

        @media (max-width: 640px) {
            .tramite-section-title-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .tramite-product-register-btn {
                justify-content: center;
                width: 100%;
            }
        }

        .tramite-product-list {
            display: grid;
            gap: 14px;
        }

        .tramite-product {
            border: 0;
            border-left: 4px solid #059669;
            border-radius: 0;
            overflow: visible;
            padding-left: 12px;
        }

        .tramite-product + .tramite-product {
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }

        .tramite-product.is-color-1 {
            border-left-color: #059669;
        }

        .tramite-product.is-color-2 {
            border-left-color: #2563eb;
        }

        .tramite-product.is-color-3 {
            border-left-color: #d97706;
        }

        .tramite-product.is-color-4 {
            border-left-color: #7c3aed;
        }

        .tramite-product-head {
            align-items: center;
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 0 0 12px;
        }

        .tramite-product.is-color-1 .tramite-product-head {
            background: transparent;
        }

        .tramite-product.is-color-2 .tramite-product-head {
            background: transparent;
        }

        .tramite-product.is-color-3 .tramite-product-head {
            background: transparent;
        }

        .tramite-product.is-color-4 .tramite-product-head {
            background: transparent;
        }

        .tramite-product-title {
            color: #0f172a;
            font-size: 0.95rem;
            font-weight: 950;
        }

        .tramite-product-meta {
            color: #64748b;
            font-size: 0.77rem;
            font-weight: 800;
            margin-top: 3px;
        }

        .tramite-product-body {
            display: grid;
            gap: 14px;
            padding: 14px 0 0;
        }

        .tramite-definition {
            display: grid;
            gap: 10px;
        }

        .tramite-definition.is-compact {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .tramite-definition-row {
            min-width: 0;
        }

        .tramite-definition-row dt {
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 850;
            margin-bottom: 3px;
        }

        .tramite-definition-row dd {
            color: #0f172a;
            font-size: 0.84rem;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .tramite-user-stack {
            display: grid;
            gap: 2px;
            line-height: 1.25;
        }

        .tramite-user-name {
            color: #0f172a;
            display: block;
            font-weight: 780;
        }

        .tramite-user-cargo {
            color: #64748b;
            display: block;
            font-size: 0.74rem;
            font-weight: 750;
            line-height: 1.2;
        }

        .tramite-subtitle {
            color: #0f766e;
            font-size: 0.8rem;
            font-weight: 950;
            margin: 4px 0 0;
            text-transform: uppercase;
        }

        .tramite-timeline {
            display: grid;
            gap: 12px;
            grid-auto-columns: minmax(165px, 1fr);
            grid-auto-flow: column;
            overflow-x: auto;
            padding: 2px 2px 10px;
        }

        .tramite-timeline-item {
            display: grid;
            gap: 10px;
            min-width: 170px;
            position: relative;
        }

        .tramite-timeline-item:not(:last-child)::after {
            border-top: 2px dashed #cbd5e1;
            content: "";
            left: 43px;
            position: absolute;
            right: -45px;
            top: 20px;
        }

        .tramite-timeline-dot {
            align-items: center;
            background: #059669;
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            height: 36px;
            justify-content: center;
            position: relative;
            width: 36px;
            z-index: 2;
        }

        .tramite-timeline-item.is-danger .tramite-timeline-dot {
            background: #dc2626;
        }

        .tramite-timeline-item.is-warning .tramite-timeline-dot {
            background: #f59e0b;
        }

        .tramite-timeline-title {
            color: #0f172a;
            font-size: 0.82rem;
            font-weight: 950;
        }

        .tramite-timeline-meta,
        .tramite-timeline-text {
            color: #475569;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.35;
            margin-top: 6px;
        }

        @media (max-width: 1180px) {
            .tramite-summary-bar,
            .tramite-grid-main,
            .tramite-detail-grid {
                grid-template-columns: 1fr;
            }

            .tramite-section-detail .tramite-detail-panel:nth-child(1),
            .tramite-section-detail .tramite-detail-panel:nth-child(2) {
                border-top: 1px solid #e2e8f0;
            }

            .tramite-section-detail .tramite-detail-panel:nth-child(1) {
                border-top: 0;
            }

            .tramite-section-detail .tramite-detail-panel:nth-child(2) {
                border-left: 0;
                padding-left: 0;
            }

            .tramite-summary-item {
                border-left: 0;
                border-top: 1px solid #dbe4ee;
            }

            .tramite-summary-item:first-child {
                border-top: 0;
            }
        }

        @media (max-width: 760px) {
            .tramite-shell {
                padding: 0;
            }

            .tramite-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .tramite-title {
                font-size: 1.2rem;
            }

            .tramite-definition.is-compact {
                grid-template-columns: 1fr;
            }

            .tramite-actions-row {
                align-items: stretch;
                flex-direction: column;
            }

            .tramite-btn,
            .tramite-warning-box {
                width: 100%;
            }
        }

        /* Ajuste visual tipo boceto: lectura por secciones, chips y tabla compacta. */
        .tramite-detail-v2 {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            padding: 14px;
        }

        .tramite-header {
            margin-bottom: 12px;
        }

        .tramite-summary-bar {
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .tramite-summary-item {
            min-height: 72px;
        }

        .tramite-summary-icon {
            color: #0f172a;
            font-size: 1.25rem;
        }

        .tramite-section-detail {
            order: 4;
        }

        #registrarPagoTramite {
            order: 5;
        }

        .tramite-section-review {
            order: 6;
        }

        .tramite-section-technical {
            order: 3;
        }

        .tramite-card {
            border-radius: 8px;
            box-shadow: none;
        }

        .tramite-card-head {
            border-bottom: 1px solid #dbe4ee;
            min-height: 46px;
            padding: 0 12px;
        }

        .tramite-card-title {
            align-items: center;
            display: flex;
            gap: 9px;
        }

        .tramite-card-title::before {
            align-items: center;
            background: #059669;
            border-radius: 999px;
            color: #ffffff;
            content: attr(data-section-number);
            display: inline-flex;
            font-size: 0.76rem;
            font-weight: 950;
            height: 24px;
            justify-content: center;
            min-width: 24px;
        }

        .tramite-card-title:not([data-section-number])::before {
            display: none;
        }

        .tramite-detail-grid {
            gap: 0;
            grid-template-columns: 1fr;
        }

        .tramite-detail-panel {
            border-top: 1px solid #dbe4ee;
            padding: 13px 0;
        }

        .tramite-detail-panel:first-child {
            border-top: 0;
        }

        .tramite-detail-title {
            color: #0f172a;
            font-size: 0.94rem;
            margin-bottom: 10px;
        }

        .tramite-definition.is-compact {
            gap: 14px 36px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .tramite-product {
            background: linear-gradient(90deg, #ecfdf5 0, #f8fafc 58%, #ffffff 100%);
            border: 1px solid #cfe7df;
            border-left: 4px solid #10b981;
            border-radius: 8px;
            padding: 0;
        }

        .tramite-product + .tramite-product {
            border-top: 1px solid #cfe7df;
            padding-top: 0;
        }

        .tramite-product-head {
            border-bottom: 1px solid #dbe4ee;
            padding: 12px 16px;
        }

        .tramite-product-body {
            padding: 14px 16px 16px;
        }

        .tramite-table-wrap {
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            overflow: auto;
        }

        .tramite-table {
            min-width: 0;
            table-layout: fixed;
        }

        .tramite-requirements-table {
            min-width: 980px;
        }

        .tramite-table th {
            background: #f8fafc;
            border-bottom: 1px solid #dbe4ee;
            color: #0f172a;
            padding: 10px 12px;
        }

        .tramite-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 12px;
        }

        .tramite-requirements-table th:first-child,
        .tramite-requirements-table td:first-child {
            text-align: center;
            width: 54px;
        }

        .tramite-requirements-table th:nth-child(3),
        .tramite-requirements-table td:nth-child(3) {
            text-align: center;
            width: 130px;
        }

        .tramite-requirements-table th:nth-child(4),
        .tramite-requirements-table td:nth-child(4),
        .tramite-requirements-table th:nth-child(5),
        .tramite-requirements-table td:nth-child(5),
        .tramite-requirements-table th:last-child,
        .tramite-requirements-table td:last-child {
            text-align: center;
            width: 136px;
        }

        .tramite-check-options {
            justify-content: center;
            width: 100%;
        }

        .tramite-check-option {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 6px;
            min-height: 28px;
            padding: 0 8px;
        }

        .tramite-check-box {
            height: 15px;
            width: 15px;
        }

        .tramite-pill,
        .tramite-status-chip {
            border-radius: 7px;
            box-shadow: none;
            min-height: 28px;
        }

        .tramite-status-chip {
            border: 1px solid currentColor;
            font-size: 0.72rem;
            padding: 0 10px;
            text-transform: uppercase;
        }

        .tramite-observation-box {
            background: transparent;
            border: 0;
            color: #334155;
            display: block;
            max-width: 260px;
            padding: 0;
            text-align: left;
        }

        .tramite-observation-box.is-danger {
            background: transparent;
            border: 0;
            color: #b91c1c;
        }

        .tramite-history-panel {
            display: flex;
            margin-top: 12px;
        }

        .cert-derive-grid,
        .cert-derive-grid.is-transfer {
            align-items: end;
            grid-template-columns: minmax(260px, 380px) minmax(320px, 1fr) auto;
        }

        .cert-derive-grid .tramite-btn {
            min-width: 96px;
            padding-inline: 13px;
        }

        .tramite-section-review {
            grid-template-columns: 1fr;
        }

        .tramite-actions-row {
            gap: 10px;
        }

        @media (max-width: 1180px) {
            .tramite-summary-bar,
            .tramite-definition.is-compact,
            .cert-derive-grid,
            .cert-derive-grid.is-transfer {
                grid-template-columns: 1fr;
            }
        }

        /* Rediseño de lectura del detalle: informacion agrupada, productos desplegables y acciones claras. */
        .tramite-detail-v2 {
            background: #f8fafc;
            border: 0;
            padding: 0;
        }

        .tramite-shell {
            gap: 14px;
        }

        .tramite-summary-bar,
        .tramite-card {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.035);
        }

        .tramite-card-head {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .tramite-card-title::before {
            background: #047857;
        }

        .tramite-section-detail .tramite-card-body {
            padding: 0 16px 16px;
        }

        .tramite-detail-grid {
            display: grid;
            gap: 0;
            grid-template-columns: 1fr;
        }

        .tramite-detail-panel {
            border-top: 1px solid #e2e8f0;
            padding: 16px 0;
        }

        .tramite-detail-panel:first-child {
            border-top: 0;
        }

        .tramite-detail-title {
            color: #0f172a;
            font-size: 0.94rem;
            font-weight: 950;
            margin-bottom: 12px;
        }

        .tramite-definition.is-compact {
            display: grid;
            gap: 14px 28px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .tramite-definition-row dt {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 850;
        }

        .tramite-definition-row dd {
            color: #0f172a;
            font-size: 0.86rem;
            font-weight: 760;
        }

        .tramite-product-list {
            gap: 10px;
        }

        .tramite-product {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-left: 4px solid #059669;
            border-radius: 8px;
            overflow: hidden;
            padding: 0;
        }

        .tramite-product[open] {
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
        }

        .tramite-product + .tramite-product {
            border-top: 1px solid #dbe4ee;
            padding-top: 0;
        }

        .tramite-product-head {
            align-items: center;
            background: linear-gradient(90deg, #ecfdf5 0%, #ffffff 70%);
            border-bottom: 0;
            cursor: pointer;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            list-style: none;
            padding: 13px 16px;
        }

        .tramite-product-head::-webkit-details-marker {
            display: none;
        }

        .tramite-product-head::after {
            align-items: center;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            color: #334155;
            content: "\f078";
            display: inline-flex;
            flex: 0 0 auto;
            font-family: "Font Awesome 6 Free";
            font-size: 0.7rem;
            font-weight: 900;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .tramite-product[open] .tramite-product-head::after {
            content: "\f077";
        }

        .tramite-product-title {
            font-size: 0.98rem;
        }

        .tramite-product-body {
            border-top: 1px solid #e2e8f0;
            display: grid;
            gap: 14px;
            padding: 14px 16px 16px;
        }

        .tramite-subtitle {
            align-items: center;
            color: #047857;
            display: flex;
            font-size: 0.78rem;
            gap: 8px;
            margin: 2px 0 0;
        }

        .tramite-subtitle::before {
            background: #10b981;
            border-radius: 999px;
            content: "";
            height: 7px;
            width: 7px;
        }

        .tramite-table-wrap {
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            overflow: auto;
        }

        .tramite-table {
            min-width: 0;
            table-layout: fixed;
            width: 100%;
        }

        .tramite-table th {
            background: #f8fafc;
            color: #0f172a;
            padding: 10px 12px;
        }

        .tramite-table td {
            color: #334155;
            padding: 10px 12px;
        }

        .tramite-requirements-table {
            min-width: 980px;
        }

        .tramite-requirements-table th:first-child,
        .tramite-requirements-table td:first-child {
            text-align: center;
            width: 52px;
        }

        .tramite-requirements-table th:nth-child(3),
        .tramite-requirements-table td:nth-child(3) {
            text-align: center;
            width: 130px;
        }

        .tramite-requirements-table th:nth-child(4),
        .tramite-requirements-table td:nth-child(4),
        .tramite-requirements-table th:nth-child(5),
        .tramite-requirements-table td:nth-child(5),
        .tramite-requirements-table th:last-child,
        .tramite-requirements-table td:last-child {
            text-align: center;
            width: 136px;
        }

        .tramite-check-options {
            justify-content: center;
            width: 100%;
        }

        .tramite-check-option {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 6px;
            min-height: 28px;
            padding: 0 8px;
        }

        .tramite-check-box {
            height: 15px;
            width: 15px;
        }

        .tramite-pill,
        .tramite-status-chip {
            border-radius: 999px;
            box-shadow: none;
            min-height: 28px;
        }

        .tramite-status-chip {
            border: 1px solid currentColor;
            font-size: 0.7rem;
            padding: 0 10px;
            text-transform: uppercase;
        }

        .tramite-doc-link {
            align-items: center;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 999px;
            color: #c2410c;
            display: inline-flex;
            font-size: 0.74rem;
            font-weight: 900;
            gap: 7px;
            min-height: 28px;
            padding: 0 10px;
            white-space: nowrap;
        }

        .tramite-doc-link:hover {
            background: #ffedd5;
            color: #9a3412;
        }

        .tramite-observation-box {
            background: transparent;
            border: 0;
            color: #334155;
            display: block;
            max-width: 260px;
            padding: 0;
            text-align: left;
        }

        .tramite-observation-box.is-danger {
            background: transparent;
            border: 0;
            color: #b91c1c;
        }

        .tramite-history-panel {
            margin-top: 12px;
        }

        .cert-derive-grid,
        .cert-derive-grid.is-transfer {
            align-items: end;
            grid-template-columns: minmax(260px, 380px) minmax(320px, 1fr) auto;
        }

        .cert-derive-grid .tramite-btn {
            min-width: 96px;
            padding-inline: 13px;
        }

        @media (max-width: 1180px) {
            .tramite-summary-bar,
            .tramite-definition.is-compact,
            .cert-derive-grid,
            .cert-derive-grid.is-transfer {
                grid-template-columns: 1fr;
            }
        }
        .tramite-section-detail .tramite-card-body {
            counter-reset: tramiteDetalle;
        }

        .tramite-section-detail .tramite-detail-title::before {
            align-items: center;
            background: #059669;
            border-radius: 999px;
            color: #ffffff;
            counter-increment: tramiteDetalle;
            content: counter(tramiteDetalle);
            display: inline-flex;
            font-size: 0.74rem;
            font-weight: 950;
            height: 22px;
            justify-content: center;
            min-width: 22px;
        }

        .tramite-section-detail .tramite-detail-title.is-plain::before {
            display: none;
        }

        /* Pulido final: chips pequeños, documentos como enlace plano y productos sin tarjetas internas. */
        .tramite-pill,
        .tramite-status-chip {
            border-radius: 999px;
            font-size: 0.64rem;
            gap: 5px;
            min-height: 21px;
            padding: 0 7px;
        }

        .tramite-status-chip {
            font-size: 0.62rem;
            letter-spacing: 0;
            padding: 0 8px;
        }

        .tramite-doc-link {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 0;
            color: #047857;
            display: inline-flex;
            font-size: 0.76rem;
            font-weight: 900;
            gap: 6px;
            min-height: 0;
            padding: 0;
            text-decoration: none;
            white-space: nowrap;
        }

        .tramite-doc-link:hover {
            background: transparent;
            color: #065f46;
            text-decoration: underline;
        }

        .tramite-doc-link.tramite-pill-danger {
            color: #b91c1c;
        }

        .tramite-product-list {
            gap: 8px;
        }

        .tramite-product {
            background: #ffffff;
            border-color: #dbe4ee;
        }

        .tramite-product-head {
            background: #f8fafc;
            padding: 10px 13px;
        }

        .tramite-product[open] .tramite-product-head {
            background: #ecfdf5;
            border-bottom: 1px solid #dbe4ee;
        }

        .tramite-product-body {
            gap: 11px;
            padding: 12px 13px 14px;
        }

        .tramite-product .tramite-table-wrap {
            border: 0;
            border-radius: 0;
        }

        .tramite-product .tramite-table th {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 8px;
        }

        .tramite-product .tramite-table td {
            padding: 9px 8px;
        }

        .tramite-product .tramite-definition.is-compact {
            border-bottom: 1px solid #e2e8f0;
            gap: 10px 24px;
            padding-bottom: 10px;
        }

        .tramite-subtitle {
            margin-top: 0;
        }

        .tramite-table th,
        .tramite-table td {
            line-height: 1.35;
        }

        .tramite-requirements-table th:nth-child(5),
        .tramite-requirements-table td:nth-child(5) {
            width: 150px;
        }

        .tramite-requirements-table th:nth-child(6),
        .tramite-requirements-table td:nth-child(6) {
            text-align: left;
        }

        /* Rehacer productos: separar informacion por secciones planas, sin cards anidadas. */
        .tramite-product-list {
            display: grid;
            gap: 10px;
        }

        .tramite-product {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-left: 4px solid #059669;
            border-radius: 8px;
            overflow: hidden;
            padding: 0;
        }

        .tramite-product-head {
            align-items: center;
            background: #f8fafc;
            cursor: pointer;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(0, 1fr) auto;
            list-style: none;
            padding: 11px 14px;
        }

        .tramite-product[open] .tramite-product-head {
            background: #ecfdf5;
            border-bottom: 1px solid #dbe4ee;
        }

        .tramite-product-body {
            display: grid;
            gap: 0;
            padding: 0;
        }

        .tramite-product-section {
            border-top: 1px solid #e2e8f0;
            padding: 13px 14px;
        }

        .tramite-product-section:first-child {
            border-top: 0;
        }

        .tramite-product-section-title {
            align-items: center;
            color: #0f172a;
            display: flex;
            font-size: 0.86rem;
            font-weight: 950;
            gap: 8px;
            margin: 0 0 10px;
        }

        .tramite-product-section-title i {
            color: #047857;
            font-size: 0.9rem;
        }

        .tramite-product-section .tramite-definition.is-compact {
            border-bottom: 0;
            gap: 12px 28px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding-bottom: 0;
        }

        .tramite-product-section .tramite-table-wrap {
            border: 0;
            border-radius: 0;
        }

        .tramite-product-section .tramite-table th {
            background: #ffffff;
            border-bottom: 1px solid #dbe4ee;
            color: #334155;
            padding: 8px;
        }

        .tramite-product-section .tramite-table td {
            padding: 9px 8px;
        }

        .tramite-product-status {
            align-items: center;
            color: #047857;
            display: inline-flex;
            font-size: 0.74rem;
            font-weight: 900;
            gap: 7px;
            white-space: nowrap;
        }

        .tramite-product-status i {
            color: #059669;
        }

        @media (max-width: 900px) {
            .tramite-product-head,
            .tramite-product-section .tramite-definition.is-compact {
                grid-template-columns: 1fr;
            }
        }

        .tramite-section-technical .cert-review-select,
        .tramite-section-technical .cert-review-textarea,
        .tramite-section-technical .tramite-btn {
            min-height: 42px;
        }

        .tramite-section-technical .tramite-btn {
            height: 42px;
            min-width: 104px;
            padding: 0 14px;
            width: auto;
        }

        .tramite-section-technical .cert-review-textarea {
            height: 42px;
            resize: vertical;
        }

        /* Separacion real de bloques: el contenedor queda limpio y cada seccion respira sola. */
        .tramite-section-detail {
            background: transparent;
            border: 0;
            box-shadow: none;
            overflow: visible;
        }

        .tramite-section-detail > .tramite-card-head {
            display: none;
        }

        .tramite-section-detail .tramite-card-body {
            padding: 0;
        }

        .tramite-section-detail .tramite-detail-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr;
        }

        .tramite-section-detail .tramite-detail-panel {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-left: 4px solid #059669;
            border-radius: 8px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.035);
            padding: 16px;
        }

        .tramite-section-detail .tramite-detail-panel:nth-of-type(2) {
            border-left-color: #0ea5e9;
        }

        .tramite-section-detail .tramite-detail-panel:nth-of-type(3) {
            border-left-color: #f59e0b;
        }

        .tramite-section-detail .tramite-detail-panel:nth-of-type(4) {
            border-left-color: #10b981;
        }

        .tramite-section-detail .tramite-detail-panel:nth-of-type(5) {
            border-left-color: #64748b;
        }

        /* Colores explicitos por seccion para no depender del orden de los article. */
        .tramite-panel-beneficiario {
            border-left-color: #059669 !important;
        }

        .tramite-panel-tramitador {
            border-left-color: #0ea5e9 !important;
        }

        .tramite-panel-inicio {
            border-left-color: #f59e0b !important;
        }

        .tramite-panel-productos {
            border-left-color: #10b981 !important;
        }

        .tramite-panel-pagos {
            border-left-color: #64748b !important;
        }

        .tramite-section-review,
        #registrarPagoTramite,
        .tramite-section-technical {
            margin-top: 0;
        }

        .tramite-grid-main.tramite-section-review {
            display: grid;
            gap: 14px;
        }

        .tramite-history-panel {
            margin-top: 0;
        }

        .tramite-section-review > .tramite-card,
        #registrarPagoTramite,
        .tramite-section-technical {
            border-left: 4px solid #059669;
        }

        #registrarPagoTramite {
            border-left-color: #f59e0b;
        }

        .tramite-section-technical {
            border-left-color: #0ea5e9;
        }

        .tramite-section-review .tramite-history-panel {
            border-left-color: #64748b;
        }

        /* Ajuste final: fuerza la linea lateral por seccion y evita que reglas anteriores la oculten. */
        .tramite-detail-v2 .tramite-section-detail .tramite-detail-panel.tramite-panel-beneficiario {
            border-left: 4px solid #059669 !important;
        }

        .tramite-detail-v2 .tramite-section-detail .tramite-detail-panel.tramite-panel-tramitador {
            border-left: 4px solid #0ea5e9 !important;
        }

        .tramite-detail-v2 .tramite-section-detail .tramite-detail-panel.tramite-panel-inicio {
            border-left: 4px solid #f59e0b !important;
        }

        .tramite-detail-v2 .tramite-section-detail .tramite-detail-panel.tramite-panel-productos {
            border-left: 4px solid #10b981 !important;
        }

        .tramite-detail-v2 .tramite-section-detail .tramite-detail-panel.tramite-panel-pagos {
            border-left: 4px solid #64748b !important;
        }

        /* Ajuste final: alinea select, descripcion y boton de asignacion en una sola base visual. */
        .tramite-detail-v2 .tramite-section-technical .cert-derive-grid {
            align-items: end;
            gap: 12px;
            grid-template-columns: minmax(260px, 380px) minmax(320px, 1fr) max-content;
        }

        .tramite-detail-v2 .tramite-section-technical .cert-derive-grid > div {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-width: 0;
        }

        .tramite-detail-v2 .tramite-section-technical .cert-review-select,
        .tramite-detail-v2 .tramite-section-technical .cert-review-textarea {
            box-sizing: border-box;
            height: 42px;
            line-height: 1.35;
            min-height: 42px;
            padding-bottom: 9px;
            padding-top: 9px;
        }

        .tramite-detail-v2 .tramite-section-technical .cert-review-textarea {
            resize: vertical;
        }

        .tramite-detail-v2 .tramite-section-technical .tramite-btn {
            align-self: end;
            box-sizing: border-box;
            height: 42px;
            justify-content: center;
            min-height: 42px;
            min-width: 108px;
            padding: 0 14px;
            width: auto;
        }

        @media (max-width: 1180px) {
            .tramite-detail-v2 .tramite-section-technical .cert-derive-grid {
                grid-template-columns: 1fr;
            }

            .tramite-detail-v2 .tramite-section-technical .tramite-btn {
                justify-self: start;
            }
        }

        /* Selector visual de funcionarios: reemplaza el select largo por una lista buscable con chips. */
        .cert-technical-field {
            min-width: 0;
            position: relative;
        }

        .cert-technical-native-select {
            height: 1px !important;
            left: 0;
            opacity: 0;
            pointer-events: none;
            position: absolute;
            top: 28px;
            width: 1px !important;
        }

        .cert-technical-control {
            align-items: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
            color: #0f172a;
            display: flex;
            gap: 10px;
            height: 42px;
            padding: 0 11px;
            text-align: left;
            transition: border-color 150ms ease, box-shadow 150ms ease;
            width: 100%;
        }

        .cert-technical-control:hover,
        .cert-technical-control.is-open {
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.10);
        }

        .cert-technical-avatar,
        .cert-technical-option-icon {
            align-items: center;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            color: #047857;
            display: inline-flex;
            flex: 0 0 auto;
            height: 28px;
            justify-content: center;
            width: 28px;
        }

        .cert-technical-selected {
            display: grid;
            flex: 1;
            min-width: 0;
        }

        .cert-technical-selected-name {
            color: #0f172a;
            font-size: 0.82rem;
            font-weight: 900;
            line-height: 1.1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cert-technical-selected-help {
            color: #64748b;
            font-size: 0.68rem;
            font-weight: 750;
            line-height: 1.15;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cert-technical-chip,
        .cert-technical-option-chip {
            align-items: center;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 999px;
            color: #166534;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 0.68rem;
            font-weight: 900;
            line-height: 1;
            min-height: 23px;
            padding: 0 9px;
            white-space: nowrap;
        }

        .cert-technical-chevron {
            color: #64748b;
            flex: 0 0 auto;
            font-size: 0.72rem;
        }

        .cert-technical-dropdown {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            box-shadow: 0 18px 35px rgba(15, 23, 42, 0.14);
            left: 0;
            margin-top: 7px;
            overflow: hidden;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 60;
        }

        .cert-technical-search {
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            display: flex;
            gap: 8px;
            padding: 9px 10px;
        }

        .cert-technical-search input {
            border: 0;
            color: #0f172a;
            flex: 1;
            font-size: 0.78rem;
            font-weight: 750;
            outline: 0;
            padding: 0;
        }

        .cert-technical-options {
            max-height: 290px;
            overflow-y: auto;
            padding: 6px;
        }

        .cert-technical-option {
            align-items: center;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 8px;
            color: #0f172a;
            display: flex;
            gap: 10px;
            padding: 9px;
            text-align: left;
            width: 100%;
        }

        .cert-technical-option:hover,
        .cert-technical-option.is-selected {
            background: #f8fafc;
            border-color: #bbf7d0;
        }

        .cert-technical-option-main {
            display: grid;
            flex: 1;
            min-width: 0;
        }

        .cert-technical-option-main strong {
            font-size: 0.82rem;
            font-weight: 950;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cert-technical-option-main span,
        .cert-technical-option-main small {
            color: #64748b;
            font-size: 0.71rem;
            font-weight: 750;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cert-technical-option-main small {
            color: #047857;
        }

        .cert-technical-empty {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 12px;
            text-align: center;
        }

        .tramite-detail-v2 .tramite-section-technical {
            overflow: visible;
        }

        .tramite-detail-v2 .tramite-section-technical .tramite-card-body,
        .tramite-detail-v2 .tramite-section-technical .cert-derive-grid {
            overflow: visible;
        }

        @media (max-width: 760px) {
            .cert-technical-control {
                height: auto;
                min-height: 42px;
                padding-bottom: 8px;
                padding-top: 8px;
            }

            .cert-technical-chip {
                display: none;
            }

            .cert-technical-option {
                align-items: flex-start;
            }

            .cert-technical-option-chip {
                margin-top: 2px;
            }
        }

        /* SweetAlert de revision: muestra el requisito exacto que se esta calificando. */
        .cert-swal-requirement {
            display: grid;
            gap: 10px;
            text-align: left;
        }

        .cert-swal-requirement p {
            color: #475569;
            font-size: 0.9rem;
            font-weight: 650;
            line-height: 1.45;
            margin: 0;
        }

        .cert-swal-requirement-line {
            border-top: 1px solid #e2e8f0;
            margin-top: 2px !important;
            padding-top: 9px;
        }

        .cert-swal-requirement-line span {
            color: #64748b;
            display: block;
            font-size: 0.74rem;
            font-weight: 900;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .cert-swal-requirement-line strong {
            color: #0f172a;
            font-size: 0.92rem;
            font-weight: 900;
            line-height: 1.3;
        }

        /*
         * Tabla de revision de requisitos.
         * Estas reglas van al final para ganar prioridad sobre estilos antiguos repetidos.
         */
        .tramite-detail-v2 .tramite-section-review .tramite-table-wrap {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-top: 8px;
            overflow-x: auto;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table {
            border-collapse: collapse;
            min-width: 1240px;
            table-layout: fixed;
            width: 100%;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th,
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td {
            overflow-wrap: break-word;
            padding: 13px 12px;
            white-space: normal;
            word-break: normal;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 0.82rem;
            font-weight: 900;
            line-height: 1.25;
            text-align: center;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td {
            border-top: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.92rem;
            font-weight: 700;
            line-height: 1.45;
            vertical-align: middle;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th:nth-child(1),
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(1) {
            width: 48px;
            text-align: center;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th:nth-child(2),
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(2) {
            width: 290px;
            text-align: left;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th:nth-child(3),
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(3) {
            width: 120px;
            text-align: center;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th:nth-child(4),
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(4) {
            width: 130px;
            text-align: center;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th:nth-child(5),
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(5) {
            width: 150px;
            text-align: center;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th:nth-child(6),
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(6) {
            width: 150px;
            text-align: center;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th:nth-child(7),
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(7) {
            width: 250px;
            text-align: left;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th:nth-child(8),
        .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(8) {
            width: 110px;
            text-align: center;
        }

        .tramite-detail-v2 .tramite-section-review .cert-requirement-row.is-observed td {
            background: #fffafa;
            border-top-color: #fecaca;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-check-options {
            align-items: center;
            display: inline-flex;
            flex-wrap: nowrap;
            gap: 8px;
            justify-content: center;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-check-option {
            flex: 0 0 auto;
            font-size: 0.84rem;
            min-height: 28px;
            padding: 0 4px;
        }

        .tramite-detail-v2 .tramite-section-review .cert-evidence-code-chip,
        .tramite-detail-v2 .tramite-section-review .tramite-status-chip,
        .tramite-detail-v2 .tramite-section-review .cert-show-pill,
        .tramite-detail-v2 .tramite-section-review .tramite-payment-inline-btn {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.72rem;
            font-weight: 850;
            gap: 5px;
            justify-content: center;
            line-height: 1.15;
            min-height: 24px;
            padding: 3px 9px;
            text-decoration: none;
            text-transform: none;
            white-space: nowrap;
        }

        .tramite-detail-v2 .tramite-section-review .cert-show-pill-ok {
            background: #ecfdf5;
            border: 1px solid #86efac;
            color: #047857;
        }

        .tramite-detail-v2 .tramite-section-review .cert-show-pill-warn {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            color: #b45309;
        }

        .tramite-detail-v2 .tramite-section-review .cert-show-pill-danger {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
        }
        .tramite-detail-v2 .tramite-section-review .tramite-observation-box {
            display: inline-block;
            max-width: 100%;
            overflow-wrap: break-word;
            padding: 10px 12px;
            white-space: normal;
        }

        .tramite-detail-v2 .tramite-section-review .cert-history-button {
            max-width: 100%;
            padding-left: 10px;
            padding-right: 10px;
            white-space: nowrap;
        }

        .tramite-detail-v2 .tramite-section-review .tramite-actions-row {
            border-top: 0;
            padding-top: 14px;
        }

        @media (max-width: 760px) {
            .tramite-detail-v2 .tramite-section-review .tramite-table-wrap {
                overflow-x: visible;
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table,
            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table thead,
            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table tbody,
            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table tr,
            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table th,
            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td {
                display: block;
                width: 100% !important;
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table thead {
                display: none;
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table tr {
                border-bottom: 1px solid #e2e8f0;
                padding: 10px 0;
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td {
                align-items: flex-start;
                display: grid;
                gap: 8px;
                grid-template-columns: 110px minmax(0, 1fr);
                text-align: left !important;
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td::before {
                color: #64748b;
                font-size: 0.72rem;
                font-weight: 900;
                text-transform: uppercase;
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(1)::before {
                content: "Nro";
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(2)::before {
                content: "Requisito";
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(3)::before {
                content: "Código evidencia";
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(4)::before {
                content: "Cumple";
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(5)::before {
                content: "Estado";
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(6)::before {
                content: "Evidencia";
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(7)::before {
                content: "Observación";
            }

            .tramite-detail-v2 .tramite-section-review .tramite-requirements-table td:nth-child(8)::before {
                content: "Acción";
            }
        }
    </style>

