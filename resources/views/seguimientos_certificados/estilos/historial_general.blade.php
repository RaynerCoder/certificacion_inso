{{-- Estilos de la tabla/hoja de ruta del historial general del tramite. --}}
<style>
    .ruta-simple {
        display: grid;
        gap: 12px;
    }

    .ruta-contexto {
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        overflow: hidden;
    }

    .ruta-contexto-item {
        border-left: 1px solid #e2e8f0;
        min-width: 0;
        padding: 11px 13px;
    }

    .ruta-contexto-item:first-child {
        border-left: 0;
    }

    .ruta-label {
        color: #64748b;
        display: block;
        font-size: 0.72rem;
        font-weight: 850;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .ruta-valor {
        color: #0f172a;
        display: block;
        font-size: 0.86rem;
        font-weight: 950;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .ruta-toolbar {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
    }

    .ruta-toolbar-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
    }

    .ruta-toolbar-actions {
        display: flex;
        flex: 1 1 420px;
        gap: 8px;
        justify-content: flex-end;
    }

    .ruta-control {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        color: #334155;
        display: flex;
        gap: 8px;
        min-height: 38px;
        min-width: 190px;
        padding: 0 11px;
    }

    .ruta-control.is-search {
        min-width: min(100%, 330px);
    }

    .ruta-control i {
        color: #64748b;
        font-size: 0.8rem;
    }

    .ruta-control input,
    .ruta-control select {
        background: transparent;
        border: 0;
        color: #0f172a;
        flex: 1;
        font-size: 0.82rem;
        font-weight: 750;
        min-width: 0;
        outline: 0;
    }

    .ruta-table-wrap {
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 8px;
        overflow-x: auto;
    }

    .ruta-table {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1080px;
        width: 100%;
    }

    .ruta-table th {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 950;
        padding: 10px 12px;
        text-align: left;
        white-space: nowrap;
    }

    .ruta-table td {
        border-bottom: 1px solid #edf2f7;
        color: #334155;
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1.42;
        padding: 11px 12px;
        vertical-align: top;
    }

    .ruta-table tr:last-child td {
        border-bottom: 0;
    }

    .ruta-table tr.is-danger td {
        background: #fffafa;
    }

    .ruta-table tr.is-warning td {
        background: #fffdf5;
    }

    .ruta-movimiento-num {
        color: #0f172a;
        display: block;
        font-size: 0.88rem;
        font-weight: 950;
        margin-bottom: 6px;
    }

    .ruta-chip {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.69rem;
        font-weight: 950;
        line-height: 1;
        padding: 5px 10px;
        white-space: nowrap;
    }

    .ruta-chip.is-success {
        background: #d1fae5;
        color: #047857;
    }

    .ruta-chip.is-info {
        background: #cffafe;
        color: #0e7490;
    }

    .ruta-chip.is-neutral {
        background: #e2e8f0;
        color: #475569;
    }

    .ruta-chip.is-danger {
        background: #ffe4e6;
        color: #b91c1c;
    }

    .ruta-chip.is-warning {
        background: #fef3c7;
        color: #b45309;
    }

    .ruta-persona {
        min-width: 0;
    }

    .ruta-persona-nombre {
        color: #0f172a;
        display: block;
        font-size: 0.8rem;
        font-weight: 950;
        overflow-wrap: anywhere;
    }

    .ruta-persona-cargo {
        color: #64748b;
        display: block;
        font-size: 0.72rem;
        font-weight: 750;
        margin-top: 3px;
        overflow-wrap: anywhere;
    }

    .ruta-texto {
        color: #334155;
        font-size: 0.79rem;
        font-weight: 700;
        line-height: 1.45;
        overflow-wrap: anywhere;
        white-space: pre-line;
    }

    .ruta-dato-faltante {
        color: #94a3b8;
        font-style: italic;
    }

    .ruta-mini {
        color: #64748b;
        display: block;
        font-size: 0.72rem;
        font-weight: 760;
        margin-top: 5px;
    }

    .ruta-empty {
        color: #64748b;
        display: none;
        font-size: 0.86rem;
        font-weight: 850;
        padding: 18px;
        text-align: center;
    }

    .ruta-empty.is-visible {
        display: block;
    }

    @media (max-width: 1040px) {
        .ruta-contexto {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ruta-contexto-item:nth-child(odd) {
            border-left: 0;
        }

        .ruta-contexto-item:nth-child(n + 3) {
            border-top: 1px solid #e2e8f0;
        }

        .ruta-toolbar,
        .ruta-toolbar-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .ruta-control,
        .ruta-control.is-search {
            width: 100%;
        }
    }

    @media (max-width: 640px) {
        .ruta-contexto {
            grid-template-columns: 1fr;
        }

        .ruta-contexto-item {
            border-left: 0;
            border-top: 1px solid #e2e8f0;
        }

        .ruta-contexto-item:first-child {
            border-top: 0;
        }
    }
</style>
