<x-admin-layout
    title="Pagos | Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Pagos', 'href' => route('pagos_index')],
    ]">

    <style>
        .pagos-table-shell {
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        .pagos-table-shell > div,
        .pagos-table-shell > div > div {
            max-width: none !important;
            width: 100% !important;
        }

        .pagos-table-shell table {
            table-layout: auto;
            width: 100% !important;
        }

        .pagos-table-shell th,
        .pagos-table-shell td {
            vertical-align: middle;
            white-space: normal !important;
        }

        .pagos-table-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .pagos-table-shell table {
                min-width: 780px;
            }
        }
    </style>

    {{-- Este listado consulta pagos existentes; el registro se realiza desde el trámite correspondiente. --}}
    <div class="pagos-table-shell">
        @livewire('datatables.pago-table')
    </div>
</x-admin-layout>
