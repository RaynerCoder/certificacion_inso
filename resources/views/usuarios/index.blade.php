<x-admin-layout title="Usuarios | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Usuarios',
        'href' => route('usuarios_index'),
    ],
]">

    @include('seguridad.estilos')

    <style>
        .usuarios-tabla-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            -webkit-overflow-scrolling: touch;
        }

        .usuarios-tabla-responsive table {
            width: 100% !important;
            min-width: 1120px;
            table-layout: auto;
        }

        .usuarios-tabla-responsive th,
        .usuarios-tabla-responsive td {
            min-width: 120px;
            vertical-align: middle;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .usuarios-tabla-responsive th:first-child,
        .usuarios-tabla-responsive td:first-child {
            width: 64px;
            min-width: 64px;
            text-align: center;
        }

        .usuarios-tabla-responsive th:last-child,
        .usuarios-tabla-responsive td:last-child {
            width: 190px;
            min-width: 190px;
        }

        .usuarios-tabla-responsive .seg-table-chip-wrap {
            max-width: 100%;
        }

        .usuarios-tabla-responsive th:nth-child(5),
        .usuarios-tabla-responsive td:nth-child(5),
        .usuarios-tabla-responsive th:nth-child(6),
        .usuarios-tabla-responsive td:nth-child(6) {
            width: 185px;
            min-width: 185px;
        }

        .usuarios-tabla-responsive td:nth-child(5) .seg-chip-list,
        .usuarios-tabla-responsive td:nth-child(6) .seg-chip-list,
        .usuarios-tabla-responsive td:nth-child(7) .seg-chip-list {
            gap: 4px;
        }

        .usuarios-tabla-responsive td:nth-child(5) .seg-chip,
        .usuarios-tabla-responsive td:nth-child(6) .seg-chip,
        .usuarios-tabla-responsive td:nth-child(7) .seg-chip {
            max-width: 100%;
            padding: 4px 8px;
            font-size: 10.5px;
            line-height: 1.15;
            word-break: normal;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .usuarios-tabla-responsive td:nth-child(5) .seg-table-empty,
        .usuarios-tabla-responsive td:nth-child(6) .seg-table-empty,
        .usuarios-tabla-responsive td:nth-child(7) .seg-table-empty {
            padding: 4px 8px;
            font-size: 10.5px;
            line-height: 1.15;
        }
    </style>

    <x-slot name="action">
        <x-wire-button href="{{ route('usuarios_create') }}" blue>
            Nuevo usuario
        </x-wire-button>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/UsuarioTable.php --}}
    <div class="usuarios-tabla-responsive">
        @livewire('datatables.usuario-table')
    </div>

    @push('js')
        <script>
            // Confirma la baja sin borrar el historial ni las relaciones del usuario.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar usuario',
                    text: 'El usuario dejará de estar activo, pero conservará su historial.',
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
