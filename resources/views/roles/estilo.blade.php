<style>
    .roles-module {
        width: 100%;
        min-width: 0;
    }

    .roles-table-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
        -webkit-overflow-scrolling: touch;
    }

    .roles-table-scroll table {
        width: 100%;
        min-width: 980px;
        table-layout: auto;
    }

    .roles-table-scroll th,
    .roles-table-scroll td {
        padding-top: 10px;
        padding-bottom: 10px;
        vertical-align: middle;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
    }

    .roles-table-scroll th:first-child,
    .roles-table-scroll td:first-child {
        width: 60px;
        min-width: 60px;
        text-align: center;
        white-space: nowrap;
    }

    .roles-table-scroll th:nth-child(2),
    .roles-table-scroll td:nth-child(2) {
        min-width: 130px;
    }

    .roles-table-scroll th:nth-child(3),
    .roles-table-scroll td:nth-child(3) {
        min-width: 145px;
    }

    .roles-table-scroll th:nth-child(4),
    .roles-table-scroll td:nth-child(4) {
        min-width: 220px;
    }

    .roles-table-scroll th:nth-child(5),
    .roles-table-scroll td:nth-child(5) {
        min-width: 105px;
        text-align: center;
    }

    .roles-table-scroll th:nth-child(6),
    .roles-table-scroll td:nth-child(6),
    .roles-table-scroll th:nth-child(7),
    .roles-table-scroll td:nth-child(7) {
        min-width: 90px;
        text-align: center;
    }

    .roles-table-scroll th:last-child,
    .roles-table-scroll td:last-child {
        width: 160px;
        min-width: 160px;
        text-align: center;
        white-space: nowrap;
    }

    .roles-table-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        white-space: nowrap;
    }

    .roles-table-actions form {
        display: inline-flex;
        margin: 0;
    }

    .roles-permission-selector {
        position: relative;
        min-width: 0;
    }

    .roles-permission-select-control {
        display: flex;
        width: 100%;
        min-height: 42px;
        align-items: center;
        gap: 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #374151;
        padding: 6px 12px;
        text-align: left;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .roles-permission-select-control:hover,
    .roles-permission-select-control.is-open,
    .roles-permission-select-control:focus-visible {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14);
        outline: none;
    }

    .roles-permission-select-text {
        display: grid;
        flex: 1;
        min-width: 0;
    }

    .roles-permission-select-name {
        overflow: hidden;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.2;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .roles-permission-select-help {
        overflow: hidden;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .roles-permission-select-chevron {
        flex: 0 0 auto;
        color: #64748b;
        font-size: 11px;
        transition: transform 160ms ease;
    }

    .roles-permission-select-control.is-open .roles-permission-select-chevron {
        transform: rotate(180deg);
    }

    .roles-permission-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        left: 0;
        z-index: 70;
        margin-top: 7px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        background: #ffffff;
        box-shadow: 0 18px 35px rgba(15, 23, 42, 0.14);
    }

    .roles-permission-search {
        position: relative;
        border-bottom: 1px solid #e2e8f0;
        padding: 9px 10px;
    }

    .roles-permission-search i {
        position: absolute;
        top: 50%;
        left: 20px;
        color: #64748b;
        font-size: 12px;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .roles-permission-search input {
        width: 100%;
        min-height: 36px;
        border: 1px solid #d1d5db;
        border-radius: 7px;
        color: #0f172a;
        padding: 0 10px 0 34px;
        font-size: 13px;
        font-weight: 700;
        outline: none;
    }

    .roles-permission-search input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
    }

    .roles-permission-toolbar,
    .roles-permission-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 12px;
    }

    .roles-permission-toolbar {
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .roles-permission-toolbar-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .roles-permission-check-all,
    .roles-permission-option {
        display: flex;
        align-items: center;
        gap: 9px;
        cursor: pointer;
    }

    .roles-permission-check-all {
        color: #334155;
        font-size: 12px;
        font-weight: 800;
    }

    .roles-permission-check-all input,
    .roles-permission-option input {
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
        accent-color: #0d9488;
    }

    .roles-permission-toolbar-button {
        border: 0;
        background: transparent;
        color: #0f766e;
        padding: 2px 0;
        font-size: 12px;
        font-weight: 800;
    }

    .roles-permission-toolbar-button:hover,
    .roles-permission-toolbar-button:focus-visible {
        color: #115e59;
        text-decoration: underline;
        outline: none;
    }

    .roles-permission-options {
        max-height: 260px;
        overflow-y: auto;
        padding: 6px;
    }

    .roles-permission-option {
        min-height: 38px;
        border: 1px solid transparent;
        border-radius: 8px;
        color: #334155;
        padding: 8px 9px;
        font-size: 13px;
        font-weight: 800;
        overflow-wrap: anywhere;
        transition: border-color 150ms ease, background 150ms ease;
    }

    .roles-permission-option:hover,
    .roles-permission-option.is-selected {
        border-color: #bbf7d0;
        background: #f8fafc;
    }

    .roles-permission-empty {
        margin: 0;
        color: #64748b;
        padding: 16px 12px;
        font-size: 12px;
        font-weight: 800;
        text-align: center;
    }

    .roles-permission-footer {
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .roles-permission-counter {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
    }

    @media (max-width: 640px) {
        .roles-module .seg-card-head {
            align-items: flex-start;
            padding: 12px 14px;
        }

        .roles-module .seg-card-body {
            padding: 14px;
        }

        .roles-module .seg-modal {
            padding: 12px;
        }

        .roles-module .seg-modal-box {
            max-height: calc(100vh - 24px);
            overflow-y: auto;
        }

        .roles-module .seg-actions {
            padding: 12px 14px;
        }

        .roles-permission-toolbar,
        .roles-permission-footer {
            align-items: stretch;
            flex-direction: column;
        }

        .roles-permission-toolbar-actions {
            justify-content: flex-start;
        }

        .roles-permission-footer button {
            width: 100%;
        }
    }
</style>
