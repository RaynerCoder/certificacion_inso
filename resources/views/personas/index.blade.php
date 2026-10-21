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
        .personas-table-scroll { width:100%; max-width:100%; min-width:0; }
        .personas-table-scroll > div { min-width:0; max-width:100%; }
        .personas-table-scroll table { width:100%; min-width:0; table-layout:auto; }
        .personas-table-scroll th,
        .personas-table-scroll td {
            box-sizing:border-box; padding:10px 12px !important; height:auto;
            vertical-align:middle; line-height:1.4; text-align:left;
            white-space:normal !important; overflow-wrap:break-word;
        }
        .personas-table-scroll th { font-size:11px; }
        .personas-table-scroll th:nth-child(2) { width:40%; }
        .personas-table-scroll th:nth-child(3) { width:auto; }
        .personas-table-scroll th:nth-child(1),
        .personas-table-scroll th:nth-child(4),
        .personas-table-scroll th:nth-child(5),
        .personas-table-scroll th:nth-child(6),
        .personas-table-scroll th:nth-child(7),
        .personas-table-scroll td:nth-child(1),
        .personas-table-scroll td:nth-child(4),
        .personas-table-scroll td:nth-child(5),
        .personas-table-scroll td:nth-child(6),
        .personas-table-scroll td:nth-child(7) {
            width:1%; white-space:nowrap !important; overflow-wrap:normal;
            padding-left:8px !important; padding-right:8px !important;
        }
        .personas-table-scroll td:nth-child(2) *,
        .personas-table-scroll td:nth-child(3) * { min-width:0; max-width:100%; white-space:normal; }
        .personas-table-scroll td:last-child > div {
            display:flex; gap:6px; flex-wrap:nowrap; align-items:center;
        }
        .personas-table-scroll td:last-child > div > * { margin:0 !important; }
        @media(max-width:1199px) {
            .personas-table-scroll table,
            .personas-table-scroll tbody { display:block; width:100%; }
            .personas-table-scroll thead { display:none; }
            .personas-table-scroll tbody { display:grid; gap:12px; padding:4px; }
            .personas-table-scroll tbody tr {
                display:grid; grid-template-columns:repeat(2,minmax(0,1fr));
                background:white; border:1px solid #e2e8f0; border-radius:10px;
                overflow:hidden;
            }
            .personas-table-scroll tbody td {
                display:block; width:auto !important; min-width:0 !important;
                border:0 !important; padding:8px 12px !important;
            }
            .personas-table-scroll tbody td::before {
                content:attr(data-label); display:block; margin-bottom:4px;
                color:#64748b; font-size:11px; font-weight:600;
            }
            .personas-table-scroll tbody td:nth-child(2),
            .personas-table-scroll tbody td:nth-child(3),
            .personas-table-scroll tbody td:last-child { grid-column:1 / -1; }
            .personas-table-scroll tbody td:nth-child(2) { font-size:14px; }
            .personas-table-scroll tbody td:last-child { border-top:1px solid #f1f5f9 !important; }
            .personas-table-scroll tbody td:last-child > div { flex-wrap:wrap; }
            .personas-table-scroll tbody td[colspan] { grid-column:1 / -1; }
            .personas-table-scroll tbody td[colspan]::before { display:none; }
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
