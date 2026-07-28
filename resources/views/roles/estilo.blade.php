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
    }
</style>
