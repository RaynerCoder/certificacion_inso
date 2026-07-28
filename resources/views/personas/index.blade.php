<x-admin-layout title="Personas y Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Personas y Empresas',
        'href' => '#',
    ],
    [
        'name' => 'Listado',
        'href' => route('personas_index'),
    ],
]">


    <x-slot name="action">
        <x-wire-button href="{{ route('personas_create') }}" blue>
            Nuevo Solicitante
        </x-wire-button>
    </x-slot>


    <style>
        .personas-table-scroll {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .personas-table-scroll table {
            width: 100%;
            min-width: 1080px;
            table-layout: fixed;
        }

        .personas-table-scroll th,
        .personas-table-scroll td {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            vertical-align: middle;
            line-height: 1.35;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .personas-table-scroll th:first-child,
        .personas-table-scroll td:first-child {
            width: 6%;
            text-align: center;
            white-space: nowrap;
        }

        .personas-table-scroll th:nth-child(2),
        .personas-table-scroll td:nth-child(2) {
            width: 13%;
        }

        .personas-table-scroll th:nth-child(3),
        .personas-table-scroll td:nth-child(3) {
            width: 27%;
        }

        .personas-table-scroll th:nth-child(4),
        .personas-table-scroll td:nth-child(4) {
            width: 12%;
            white-space: nowrap;
        }

        .personas-table-scroll th:nth-child(5),
        .personas-table-scroll td:nth-child(5) {
            width: 16%;
            white-space: nowrap;
        }

        .personas-table-scroll th:nth-child(6),
        .personas-table-scroll td:nth-child(6) {
            width: 10%;
            text-align: center;
            white-space: nowrap;
        }

        .personas-table-scroll th:last-child,
        .personas-table-scroll td:last-child {
            width: 16%;
            text-align: center;
            white-space: nowrap;
        }

        .personas-table-scroll td:nth-child(3),
        .personas-table-scroll td:nth-child(3) * {
            min-width: 0;
            max-width: 100%;
            white-space: normal !important;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .personas-table-scroll th:first-child > div,
        .personas-table-scroll th:first-child button,
        .personas-table-scroll th:nth-child(6) > div,
        .personas-table-scroll th:nth-child(6) button,
        .personas-table-scroll th:last-child > div {
            width: 100%;
            justify-content: center;
            text-align: center;
        }

        .personas-table-scroll td:nth-child(6) .tabla-chip {
            margin-right: auto;
            margin-left: auto;
        }

        .personas-table-scroll td:last-child > div {
            justify-content: center;
            flex-wrap: nowrap;
        }
    </style>

    <div class="personas-table-scroll">
        @livewire('datatables.persona-table')
    </div>

    @push('js')
        <script>
            const formulariosEliminarPersona = document.querySelectorAll('.delete-form');

            formulariosEliminarPersona.forEach(elemento => {
                elemento.addEventListener('submit', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: '¿Está seguro?',
                        text: 'El registro quedará inactivo y conservará su información.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            elemento.submit();
                        }
                    });
                });
            });
        </script>
    @endpush


</x-admin-layout>
