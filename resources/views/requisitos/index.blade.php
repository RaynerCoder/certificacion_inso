<x-admin-layout title="Requisitos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Certificados',
        'href' => '',
    ],
    [
        'name' => 'Requisitos',
        'href' => route('requisitos_index'),
    ],
]">
    <style>
        .requisitos-datatable .requisitos-description-cell {
            white-space: normal !important;
            overflow-wrap: anywhere;
            word-break: normal;
            line-height: 1.55;
        }

        .requisitos-datatable .requisitos-table-shell {
            overflow-x: hidden;
        }

        @media (max-width: 767px) {
            .requisitos-datatable .requisitos-table-shell {
                overflow: visible;
                border: 0;
                background: transparent;
                box-shadow: none;
            }

            .requisitos-datatable .requisitos-table,
            .requisitos-datatable .requisitos-table tbody {
                display: block;
                width: 100%;
                min-width: 0;
            }

            .requisitos-datatable .requisitos-table thead {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            .requisitos-datatable .requisitos-table tbody {
                display: grid;
                gap: 0.75rem;
                background: transparent;
            }

            .requisitos-datatable .requisitos-table tbody tr {
                display: block;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                background: #ffffff;
                box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
            }

            .requisitos-datatable .requisitos-table .requisitos-cell {
                display: grid;
                grid-template-columns: 5.5rem minmax(0, 1fr);
                gap: 0.75rem;
                width: 100% !important;
                min-width: 0 !important;
                max-width: none !important;
                padding: 0.7rem 0.9rem;
                text-align: left;
                white-space: normal;
                border-bottom: 1px solid #f1f5f9;
            }

            .requisitos-datatable .requisitos-table .requisitos-cell::before {
                content: attr(data-label);
                color: #64748b;
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .requisitos-datatable .requisitos-table .requisitos-cell:last-child {
                border-bottom: 0;
            }

            .requisitos-datatable .requisitos-table .requisitos-cell[data-column="descripcion"] {
                display: block;
            }

            .requisitos-datatable .requisitos-table .requisitos-cell[data-column="descripcion"]::before {
                display: block;
                margin-bottom: 0.35rem;
            }

            .requisitos-datatable .requisitos-table .requisitos-cell[data-column="acciones"] > div {
                justify-content: flex-start;
            }

            .dark .requisitos-datatable .requisitos-table tbody tr {
                border-color: #334155;
                background: #1e293b;
            }

            .dark .requisitos-datatable .requisitos-table .requisitos-cell {
                border-color: #334155;
            }

            .dark .requisitos-datatable .requisitos-table .requisitos-cell::before {
                color: #94a3b8;
            }
        }
    </style>

    <x-slot name="action">
        <x-wire-button type="button" blue data-crear-requisito onclick="abrirModalCrearRequisito()">
            Nuevo
        </x-wire-button>
    </x-slot>

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/RequisitoTable.php --}}
    @livewire('datatables.requisito-table')

    {{-- MODAL PARA CREAR UN NUEVO REQUISITO --}}
    <div id="modalCrearRequisito" class="hidden fixed inset-0 z-[9999] bg-black/45 items-center justify-center p-4">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-2">
                <h2 class="text-lg font-bold text-slate-800">Nuevo Requisito</h2>
                <button type="button" onclick="cerrarModalCrearRequisito()"
                    class="rounded-lg px-3 py-1 text-xl font-bold text-slate-500 hover:bg-slate-100">
                    x
                </button>
            </div>

            <form action="{{ route('requisitos_store') }}" method="POST" autocomplete="off"
                class="space-y-2 px-5 pb-4 pt-2">
                @csrf

                <input type="hidden" name="form_modal" value="crear">

                <x-wire-textarea label="Descripción del requisito" id="crear_descripcion" name="form_descripcion"
                    placeholder="Ejemplo:&#10;- Fotocopia simple del NIT vigente.&#10;- Certificado original firmado."
                    rows="6" autocomplete="off" :value="old('form_descripcion')" />
                <p class="text-xs leading-5 text-slate-500">
                    Para mostrar una lista, escriba cada elemento en una línea nueva y comience con un guion (-).
                </p>

                <x-wire-native-select label="Estado" id="crear_estado" name="form_estado">
                    <option value="ACTIVO" @selected(old('form_modal') !== 'crear' || old('form_estado') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(old('form_modal') === 'crear' && old('form_estado') === 'INACTIVO')>Inactivo</option>
                </x-wire-native-select>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-2">
                    <x-wire-button type="button" onclick="cerrarModalCrearRequisito()" secondary>
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Guardar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL PARA EDITAR REQUISITO --}}
    <div id="modalEditarRequisito"
        class="hidden fixed inset-0 z-[9999] bg-black/45 items-center justify-center p-4">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-2">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Editar requisito</h2>
                </div>
                <button type="button" onclick="cerrarModalEditarRequisito()"
                    class="rounded-lg px-3 py-1 text-xl font-bold text-slate-500 hover:bg-slate-100">
                    x
                </button>
            </div>

            <form id="formEditarRequisito" action="#" method="POST" autocomplete="off"
                class="space-y-2 px-5 pb-4 pt-2">
                @csrf
                @method('PUT')
                {{-- Permite reabrir este modal si Laravel devuelve errores de validacion. --}}
                <input type="hidden" name="form_modal" value="editar">
                <input type="hidden" id="editar_id_requisito" name="form_id_requisito" value="{{ old('form_id_requisito') }}">

                <x-wire-textarea label="Descripción del requisito" id="editar_descripcion" name="form_descripcion"
                    placeholder="Ejemplo:&#10;- Fotocopia simple del NIT vigente.&#10;- Certificado original firmado."
                    rows="6" autocomplete="off" :value="old('form_descripcion')" />
                <p class="text-xs leading-5 text-slate-500">
                    Los saltos de línea se conservarán. Para una lista, utilice un guion (-) al inicio de cada elemento.
                </p>

                <x-wire-native-select label="Estado" id="editar_estado" name="form_estado">
                    <option value="ACTIVO" @selected(old('form_modal') !== 'editar' || old('form_estado') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(old('form_modal') === 'editar' && old('form_estado') === 'INACTIVO')>Inactivo</option>
                </x-wire-native-select>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-2">
                    <x-wire-button type="button" onclick="cerrarModalEditarRequisito()" secondary>
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Actualizar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            // Ruta para editar el requisito
            const rutaActualizarRequisito = @json(route('requisitos_update', ['requisito' => '__ID__']));

            // Muestra el modal de creacion desde el boton Nuevo.
            function abrirModalCrearRequisito() {
                const modal = document.getElementById('modalCrearRequisito');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Oculta el modal de creacion.
            function cerrarModalCrearRequisito() {
                const modal = document.getElementById('modalCrearRequisito');
                limpiarErroresModalRequisito(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Carga datos de la fila y prepara la ruta PUT para editar el registro correcto.
            function abrirModalEditarRequisito(id, descripcion, estado) {
                document.getElementById('editar_id_requisito').value = id;
                document.getElementById('editar_descripcion').value = descripcion || '';
                document.getElementById('editar_estado').value = estado || 'ACTIVO';
                document.getElementById('formEditarRequisito').action = rutaActualizarRequisito.replace('__ID__', id);

                const modal = document.getElementById('modalEditarRequisito');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Oculta el modal de edicion.
            function cerrarModalEditarRequisito() {
                const modal = document.getElementById('modalEditarRequisito');
                limpiarErroresModalRequisito(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Limpia mensajes required de WireUI al cerrar el modal para que no reaparezcan al volver a abrir.
            function limpiarErroresModalRequisito(modal) {
                if (!modal) {
                    return;
                }
                modal.querySelectorAll('[group-invalidated]').forEach((elemento) => {
                    elemento.removeAttribute('group-invalidated');
                });

                modal.querySelectorAll('label.text-negative-600, label.text-red-600').forEach((elemento) => {
                    elemento.remove();
                });
            }

            // SE ENCARGA DE MOSTRAR EL MODAL PARA CREAR O EDITAR
            document.addEventListener('click', function(e) {
                const botonCrear = e.target.closest('[data-crear-requisito]');
                const botonEditar = e.target.closest('[data-editar-requisito]');

                if (botonCrear) {
                    abrirModalCrearRequisito();
                    return;
                }

                if (!botonEditar) {
                    return;
                }

                abrirModalEditarRequisito(
                    botonEditar.dataset.id,
                    botonEditar.dataset.descripcion,
                    botonEditar.dataset.estado
                );
            });

            // Confirma la eliminación lógica incluso cuando Livewire vuelve a renderizar la tabla.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar requisito',
                    text: 'El requisito se marcará como Inactivo y dejará de aparecer en el listado si no está relacionado con otros datos.',
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

            // Reabre el modal correcto cuando Laravel devuelve errores de validacion.
            @if ($errors->any() && old('form_modal') === 'crear')
                abrirModalCrearRequisito();
            @endif

            @if ($errors->any() && old('form_modal') === 'editar')
                abrirModalEditarRequisito(
                    @json(old('form_id_requisito')),
                    @json(old('form_descripcion')),
                    @json(old('form_estado', 'ACTIVO'))
                );
            @endif
        </script>
    @endpush

</x-admin-layout>
