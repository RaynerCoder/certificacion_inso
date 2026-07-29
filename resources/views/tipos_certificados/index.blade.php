<x-admin-layout title="Tipos de Certificado | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Certificados',
        'href' => '',
    ],
    [
        'name' => 'Tipos de Certificado',
        'href' => route('tipos_certificados_index'),
    ],
]">
    <style>
        .tipos-certificados-tabla {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            -webkit-overflow-scrolling: touch;
        }

        .tipos-certificados-tabla table {
            width: 100%;
            min-width: 760px;
            table-layout: fixed;
        }

        .tipos-certificados-tabla th,
        .tipos-certificados-tabla td {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            vertical-align: middle;
            white-space: normal !important;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .tipos-certificados-tabla th:first-child,
        .tipos-certificados-tabla td:first-child {
            width: 8%;
            min-width: 60px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-certificados-tabla th:nth-child(2),
        .tipos-certificados-tabla td:nth-child(2) {
            width: 56%;
            min-width: 320px;
        }

        .tipos-certificados-tabla th:nth-child(3),
        .tipos-certificados-tabla td:nth-child(3) {
            width: 14%;
            min-width: 110px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-certificados-tabla th:last-child,
        .tipos-certificados-tabla td:last-child {
            width: 22%;
            min-width: 230px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-certificados-tabla th:first-child button,
        .tipos-certificados-tabla th:nth-child(3) button {
            width: 100%;
            justify-content: center;
            text-align: center;
        }

        .tipos-certificados-tabla td:nth-child(3) .tabla-chip {
            margin-right: auto;
            margin-left: auto;
        }

        .tipos-certificados-acciones {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }

        .tipos-certificados-acciones form {
            display: inline-flex;
            margin: 0;
        }
    </style>

    <x-slot name="action">
        <x-wire-button href="{{ route('tipos_certificados_create') }}" blue>
            Nuevo
        </x-wire-button>
    </x-slot>



    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/TipoCertificadoTable.php --}}
    <div class="tipos-certificados-tabla">
        @livewire('datatables.tipo-certificado-table')
    </div>

    @push('js')
        <script>
            // Confirma la eliminación lógica incluso cuando Livewire vuelve a renderizar la tabla.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar tipo de certificado',
                    text: 'El tipo de certificado se marcará como Inactivo y dejará de aparecer en el listado si no está relacionado con otros datos.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        formulario.submit();
                    }
                });
            });
        </script>
    @endpush

</x-admin-layout>
