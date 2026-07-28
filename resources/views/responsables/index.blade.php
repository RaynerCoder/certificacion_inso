<x-admin-layout title="Responsables de Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Responsables de Empresas',
        'href' => route('responsables_index'),
    ],
]">

    <style>
        .tabla-responsables-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .tabla-responsables-wrap table {
            width: 100%;
            min-width: 980px;
            table-layout: fixed;
        }

        .tabla-responsables-wrap th,
        .tabla-responsables-wrap td {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            vertical-align: middle;
            line-height: 1.35;
            overflow-wrap: break-word;
        }

        .tabla-responsables-wrap th:first-child,
        .tabla-responsables-wrap td:first-child {
            width: 5%;
            text-align: center;
            white-space: nowrap;
        }

        .tabla-responsables-wrap th:nth-child(2),
        .tabla-responsables-wrap td:nth-child(2) {
            width: 22%;
        }

        .tabla-responsables-wrap th:nth-child(3),
        .tabla-responsables-wrap td:nth-child(3) {
            width: 24%;
        }

        .tabla-responsables-wrap th:nth-child(4),
        .tabla-responsables-wrap td:nth-child(4) {
            width: 14%;
        }

        .tabla-responsables-wrap th:nth-child(5),
        .tabla-responsables-wrap td:nth-child(5) {
            width: 13%;
            text-align: center;
            white-space: nowrap;
        }

        .tabla-responsables-wrap th:nth-child(6),
        .tabla-responsables-wrap td:nth-child(6) {
            width: 9%;
            text-align: center;
            white-space: nowrap;
        }

        .tabla-responsables-wrap th:last-child,
        .tabla-responsables-wrap td:last-child {
            width: 13%;
            text-align: center;
            white-space: nowrap;
        }

        .tabla-responsables-wrap th:nth-child(5) > div,
        .tabla-responsables-wrap th:nth-child(5) button,
        .tabla-responsables-wrap th:nth-child(6) > div,
        .tabla-responsables-wrap th:nth-child(6) button,
        .tabla-responsables-wrap th:last-child > div {
            width: 100%;
            justify-content: center;
            text-align: center;
        }

        .tabla-responsables-wrap td:nth-child(6) .tabla-chip {
            margin-right: auto;
            margin-left: auto;
        }

        .tabla-responsables-wrap td:last-child > div {
            justify-content: center;
            flex-wrap: nowrap;
        }
    </style>

    {{-- El ancho mínimo evita que los datos se compriman en pantallas pequeñas. --}}
    <div class="tabla-responsables-wrap">
        @livewire('datatables.responsable-table')
    </div>
</x-admin-layout>
