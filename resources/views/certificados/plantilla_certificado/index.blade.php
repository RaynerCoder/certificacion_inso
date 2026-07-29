<x-admin-layout title="Plantillas de Certificado | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Certificados', 'href' => route('certificados_index')],
    ['name' => 'Plantillas', 'href' => route('certificados_plantillas_index')],
]">
    <style>
        .plantillas-certificado-tabla {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            -webkit-overflow-scrolling: touch;
        }

        .plantillas-certificado-tabla table {
            width: 100%;
            min-width: 1120px;
            table-layout: fixed;
        }

        .plantillas-certificado-tabla th,
        .plantillas-certificado-tabla td {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            vertical-align: middle;
            white-space: normal !important;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .plantillas-certificado-tabla th:first-child,
        .plantillas-certificado-tabla td:first-child {
            width: 5%;
            min-width: 60px;
            text-align: center;
            white-space: nowrap !important;
        }

        .plantillas-certificado-tabla th:nth-child(2),
        .plantillas-certificado-tabla td:nth-child(2) {
            width: 24%;
            min-width: 240px;
        }

        .plantillas-certificado-tabla th:nth-child(3),
        .plantillas-certificado-tabla td:nth-child(3) {
            width: 13%;
            min-width: 150px;
            text-align: center;
            white-space: nowrap !important;
        }

        .plantillas-certificado-tabla th:nth-child(4),
        .plantillas-certificado-tabla td:nth-child(4) {
            width: 22%;
            min-width: 220px;
        }

        .plantillas-certificado-tabla th:nth-child(5),
        .plantillas-certificado-tabla td:nth-child(5),
        .plantillas-certificado-tabla th:nth-child(6),
        .plantillas-certificado-tabla td:nth-child(6) {
            width: 10%;
            min-width: 110px;
            text-align: center;
            white-space: nowrap !important;
        }

        .plantillas-certificado-tabla th:last-child,
        .plantillas-certificado-tabla td:last-child {
            width: 16%;
            min-width: 170px;
            text-align: center;
            white-space: nowrap !important;
        }

        .plantillas-certificado-tabla th:first-child button {
            width: 100%;
            justify-content: center;
            text-align: center;
        }

        .plantillas-certificado-tabla td:nth-child(3) .tabla-chip,
        .plantillas-certificado-tabla td:nth-child(5) .tabla-chip,
        .plantillas-certificado-tabla td:nth-child(6) .tabla-chip {
            margin-right: auto;
            margin-left: auto;
        }

        .plantillas-certificado-acciones {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }
    </style>

    <x-slot name="action">
        <x-wire-button href="{{ route('certificados_plantillas_create') }}" blue>
            Nueva plantilla
        </x-wire-button>
    </x-slot>

    {{-- Tabla principal del modulo. La logica esta en app/Livewire/Datatables/PlantillaCertificadoTable.php --}}
    <div class="plantillas-certificado-tabla">
        @livewire('datatables.plantilla-certificado-table')
    </div>

    @push('js')
        <script>
            // Confirma la eliminación lógica incluso cuando Livewire vuelve a renderizar la tabla.
            document.addEventListener('submit', function(evento) {
                const formulario = evento.target.closest('.delete-form-plantilla');

                if (!formulario) {
                    return;
                }

                evento.preventDefault();

                Swal.fire({
                    title: 'Eliminar plantilla',
                    text: 'La plantilla se marcará como Inactiva y dejará de aparecer en el listado si no está relacionada con certificados.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        formulario.submit();
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
