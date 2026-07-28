<style>
    .permisos-module {
        width: 100%;
        min-width: 0;
    }

    .permisos-table-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-inline: contain;
        -webkit-overflow-scrolling: touch;
    }

    .permisos-table-scroll table {
        width: 100%;
        min-width: 860px;
        table-layout: fixed;
    }

    .permisos-table-scroll th,
    .permisos-table-scroll td {
        padding-top: 10px;
        padding-bottom: 10px;
        vertical-align: middle;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
    }

    .permisos-table-scroll th:first-child,
    .permisos-table-scroll td:first-child {
        width: 6%;
        text-align: center;
        white-space: nowrap;
    }

    .permisos-table-scroll th:nth-child(2),
    .permisos-table-scroll td:nth-child(2) {
        width: 26%;
    }

    .permisos-table-scroll th:nth-child(3),
    .permisos-table-scroll td:nth-child(3) {
        width: 38%;
    }

    .permisos-table-scroll th:nth-child(4),
    .permisos-table-scroll td:nth-child(4) {
        width: 14%;
        text-align: center;
    }

    .permisos-table-scroll th:nth-child(4) > div,
    .permisos-table-scroll th:nth-child(4) button {
        width: 100%;
        justify-content: center;
        text-align: center;
    }

    .permisos-table-scroll td:nth-child(4) .tabla-chip {
        margin-right: auto;
        margin-left: auto;
    }

    .permisos-table-scroll th:last-child,
    .permisos-table-scroll td:last-child {
        width: 16%;
        text-align: center;
        white-space: nowrap;
    }

    .permisos-table-scroll .seg-table-chip-wrap {
        width: 100%;
        max-width: none;
    }

    .permisos-table-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        white-space: nowrap;
    }

    .permisos-table-actions form {
        display: inline-flex;
        margin: 0;
    }

    @media (max-width: 640px) {
        .permisos-module .seg-modal {
            padding: 12px;
        }

        .permisos-module .seg-modal-box {
            max-height: calc(100vh - 24px);
            overflow-y: auto;
        }

        .permisos-module .seg-actions {
            padding: 12px 14px;
        }
    }
</style>
