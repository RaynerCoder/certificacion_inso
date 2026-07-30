<x-admin-layout title="Tipos de Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Tipos de Empresas',
        'href' => route('tipos_empresas_index'),
    ],
]">

    @include('seguridad.estilos')

    <style>
        .tipos-empresas-tabla {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            -webkit-overflow-scrolling: touch;
        }

        .tipos-empresas-tabla table {
            width: 100%;
            min-width: 680px;
            table-layout: fixed;
        }

        .tipos-empresas-tabla th,
        .tipos-empresas-tabla td {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            vertical-align: middle;
            white-space: normal !important;
            overflow-wrap: break-word;
        }

        .tipos-empresas-tabla th:first-child,
        .tipos-empresas-tabla td:first-child {
            width: 10%;
            min-width: 70px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-empresas-tabla th:nth-child(2),
        .tipos-empresas-tabla td:nth-child(2) {
            width: auto;
        }

        .tipos-empresas-tabla th:nth-child(3),
        .tipos-empresas-tabla td:nth-child(3) {
            width: 18%;
            min-width: 115px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-empresas-tabla th:last-child,
        .tipos-empresas-tabla td:last-child {
            width: 24%;
            min-width: 175px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-empresas-tabla th:first-child button,
        .tipos-empresas-tabla th:nth-child(3) button,
        .tipos-empresas-tabla th:last-child > div {
            width: 100%;
            justify-content: center;
            text-align: center;
        }

        .tipos-empresas-tabla td:nth-child(3) .tabla-chip {
            margin-right: auto;
            margin-left: auto;
        }

        .tipo-empresa-modal-box {
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
        }

        @media (max-width: 640px) {
            .tipo-empresa-modal-form {
                padding: 16px;
            }
        }
    </style>

    <x-slot name="action">
        <x-wire-button type="button" blue data-crear-tipo-empresa>
            Nuevo
        </x-wire-button>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabla principal del catálogo de tipos de empresa. --}}
    <div class="tipos-empresas-tabla">
        @livewire('datatables.tipo-empresa-table')
    </div>

    {{-- El alta se mantiene en el listado porque el catálogo solo administra dos datos. --}}
    <div id="modalCrearTipoEmpresa" class="seg-modal hidden">
        <div class="seg-modal-box tipo-empresa-modal-box">
            <div class="seg-modal-head">
                <div>
                    <h2 class="seg-modal-title">Nuevo tipo de empresa</h2>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Complete los datos del tipo de empresa.</p>
                </div>
                <button type="button" class="seg-modal-close" data-cerrar-tipo-empresa
                    aria-label="Cerrar modal">
                    x
                </button>
            </div>

            <form action="{{ route('tipos_empresas_store') }}" method="POST"
                class="tipo-empresa-modal-form space-y-4 p-5">
                @csrf
                <input type="hidden" name="form_modal" value="crear">

                <x-wire-textarea label="Descripción" id="crear_tipo_empresa_descripcion"
                    name="form_descripcion" placeholder="Descripción del tipo de empresa"
                    rows="3">{{ old('form_modal') === 'crear' ? old('form_descripcion') : '' }}</x-wire-textarea>

                <x-wire-native-select label="Estado" id="crear_tipo_empresa_estado" name="form_estado">
                    <option value="ACTIVO" @selected(old('form_modal') !== 'crear' || old('form_estado') === 'ACTIVO')>
                        Activo
                    </option>
                    <option value="INACTIVO" @selected(old('form_modal') === 'crear' && old('form_estado') === 'INACTIVO')>
                        Inactivo
                    </option>
                </x-wire-native-select>

                <div class="seg-actions !px-0 !pb-0">
                    <x-wire-button type="button" secondary data-cerrar-tipo-empresa>
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Guardar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            function abrirModalCrearTipoEmpresa() {
                const modal = document.getElementById('modalCrearTipoEmpresa');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function cerrarModalCrearTipoEmpresa() {
                const modal = document.getElementById('modalCrearTipoEmpresa');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.addEventListener('click', function (evento) {
                if (evento.target.closest('[data-crear-tipo-empresa]')) {
                    abrirModalCrearTipoEmpresa();
                    return;
                }

                if (evento.target.closest('[data-cerrar-tipo-empresa]')) {
                    cerrarModalCrearTipoEmpresa();
                }
            });

            document.addEventListener('submit', function (evento) {
                const formulario = evento.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                evento.preventDefault();

                Swal.fire({
                    title: 'Eliminar tipo de empresa',
                    text: 'El tipo de empresa se marcará como Inactivo y dejará de aparecer si no está relacionado con otros datos.',
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

            @if ($errors->any() && old('form_modal') === 'crear')
                abrirModalCrearTipoEmpresa();
            @endif
        </script>
    @endpush


</x-admin-layout>
