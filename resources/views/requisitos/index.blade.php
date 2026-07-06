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

    <x-slot name="action">
        <x-wire-button type="button" blue data-crear-requisito onclick="abrirModalCrearRequisito()">
            Nuevo
        </x-wire-button>
    </x-slot>

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/RequisitoTable.php --}}
    @livewire('datatables.requisito-table')

    {{-- MODAL PARA CREAR UN NUEVO REQUISITO --}}
    <div id="modalCrearRequisito" class="hidden fixed inset-0 z-[9999] bg-black/45 items-center justify-center p-4">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-2">
                <h2 class="text-lg font-bold text-slate-800">Nuevo Requisito</h2>
                <button type="button" onclick="cerrarModalCrearRequisito()"
                    class="rounded-lg px-3 py-1 text-xl font-bold text-slate-500 hover:bg-slate-100">
                    x
                </button>
            </div>

            <form action="{{ route('requisitos_store') }}" method="POST" class="space-y-2 px-5 pb-4 pt-2">
                @csrf

                <input type="hidden" name="form_modal" value="crear">

                <x-wire-textarea label="Descripción del requisito" id="crear_descripcion" name="form_descripcion"
                    placeholder="Ejemplo: Fotocopia simple del NIT vigente." rows="3" :value="old('form_descripcion')" />

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
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-2">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Editar requisito</h2>
                </div>
                <button type="button" onclick="cerrarModalEditarRequisito()"
                    class="rounded-lg px-3 py-1 text-xl font-bold text-slate-500 hover:bg-slate-100">
                    x
                </button>
            </div>

            <form id="formEditarRequisito" action="#" method="POST" class="space-y-2 px-5 pb-4 pt-2">
                @csrf
                @method('PUT')
                {{-- Permite reabrir este modal si Laravel devuelve errores de validacion. --}}
                <input type="hidden" name="form_modal" value="editar">
                <input type="hidden" id="editar_id_requisito" name="form_id_requisito" value="{{ old('form_id_requisito') }}">

                <x-wire-textarea label="Descripción del requisito" id="editar_descripcion" name="form_descripcion"
                    placeholder="Ejemplo: Fotocopia simple del NIT vigente." rows="3" :value="old('form_descripcion')" />

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

            // Confirma eliminacion incluso cuando Livewire vuelve a renderizar la tabla.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Estas seguro?',
                    text: 'No podras revertir esta accion.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Si, eliminar',
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
