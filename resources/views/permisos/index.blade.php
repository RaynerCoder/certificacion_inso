<x-admin-layout title="Permisos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Permisos',
        'href' => route('permisos_index'),
    ],
]">

    @include('seguridad.estilos')

    <x-slot name="action">
        <x-wire-button type="button" blue data-crear-permiso>
            Nuevo permiso
        </x-wire-button>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/PermisoTable.php --}}
    @livewire('datatables.permiso-table')

    {{-- Modal crear permiso --}}
    <div id="modalCrearPermiso" class="seg-modal hidden">
        <div class="seg-modal-box">
            <div class="seg-modal-head">
                <h2 class="seg-modal-title">Nuevo permiso</h2>
                <button type="button" class="seg-modal-close" onclick="cerrarModalCrearPermiso()">x</button>
            </div>

            <form action="{{ route('permisos_store') }}" method="POST" class="space-y-4 p-4">
                @csrf
                <input type="hidden" name="form_modal" value="crear">

                <x-wire-input label="Nombre del permiso" id="crear_nombre" name="form_nombre" type="text"
                    placeholder="Ej: productos.crear" value="{{ old('form_modal') === 'crear' ? old('form_nombre') : '' }}" />

                <x-wire-native-select label="Estado" id="crear_estado" name="form_estado">
                    <option value="1" @selected(old('form_modal') !== 'crear' || old('form_estado') === '1')>Activo</option>
                    <option value="0" @selected(old('form_modal') === 'crear' && old('form_estado') === '0')>Inactivo</option>
                </x-wire-native-select>

                <div class="seg-actions !px-0 !pb-0">
                    <x-wire-button type="button" onclick="cerrarModalCrearPermiso()" secondary>
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Guardar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal editar permiso --}}
    <div id="modalEditarPermiso" class="seg-modal hidden">
        <div class="seg-modal-box">
            <div class="seg-modal-head">
                <h2 class="seg-modal-title">Editar permiso</h2>
                <button type="button" class="seg-modal-close" onclick="cerrarModalEditarPermiso()">x</button>
            </div>

            <form id="formEditarPermiso" action="#" method="POST" class="space-y-4 p-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_modal" value="editar">
                <input type="hidden" id="editar_id_permiso" name="form_id_permiso" value="{{ old('form_id_permiso') }}">

                <x-wire-input label="Nombre del permiso" id="editar_nombre" name="form_nombre" type="text"
                    placeholder="Ej: productos.crear" value="{{ old('form_modal') === 'editar' ? old('form_nombre') : '' }}" />

                <x-wire-native-select label="Estado" id="editar_estado" name="form_estado">
                    <option value="1" @selected(old('form_modal') !== 'editar' || old('form_estado') === '1')>Activo</option>
                    <option value="0" @selected(old('form_modal') === 'editar' && old('form_estado') === '0')>Inactivo</option>
                </x-wire-native-select>

                <div class="seg-actions !px-0 !pb-0">
                    <x-wire-button type="button" onclick="cerrarModalEditarPermiso()" secondary>
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
            // Ruta base usada para construir dinamicamente la accion del formulario de edicion.
            const rutaActualizarPermiso = @json(route('permisos_update', ['permiso' => '__ID__']));

            // Abre el modal para registrar un permiso nuevo.
            function abrirModalCrearPermiso() {
                const modal = document.getElementById('modalCrearPermiso');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Cierra el modal de crear y limpia errores visuales previos.
            function cerrarModalCrearPermiso() {
                const modal = document.getElementById('modalCrearPermiso');
                limpiarErroresModalPermiso(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Carga los datos del permiso seleccionado y abre el modal de edicion.
            function abrirModalEditarPermiso(id, nombre, estado) {
                document.getElementById('editar_id_permiso').value = id;
                document.getElementById('editar_nombre').value = nombre || '';
                document.getElementById('editar_estado').value = String(estado ?? '1');
                document.getElementById('formEditarPermiso').action = rutaActualizarPermiso.replace('__ID__', id);

                const modal = document.getElementById('modalEditarPermiso');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Cierra el modal de edicion y limpia errores visuales previos.
            function cerrarModalEditarPermiso() {
                const modal = document.getElementById('modalEditarPermiso');
                limpiarErroresModalPermiso(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Quita mensajes de validacion que el componente de inputs deja dentro del modal.
            function limpiarErroresModalPermiso(modal) {
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

            // Centraliza clicks de crear/editar para no duplicar listeners por boton.
            document.addEventListener('click', function(e) {
                const botonCrear = e.target.closest('[data-crear-permiso]');
                const botonEditar = e.target.closest('[data-editar-permiso]');

                if (botonCrear) {
                    abrirModalCrearPermiso();
                    return;
                }

                if (!botonEditar) {
                    return;
                }

                abrirModalEditarPermiso(
                    botonEditar.dataset.id,
                    botonEditar.dataset.nombre,
                    botonEditar.dataset.estado
                );
            });

            // Confirma con SweetAlert antes de eliminar un permiso.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar permiso',
                    text: 'Se quitara de roles y usuarios directos.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        formulario.submit();
                    }
                });
            });

            // Si Laravel devuelve errores al crear, reabre el modal correcto.
            @if ($errors->any() && old('form_modal') === 'crear')
                abrirModalCrearPermiso();
            @endif

            // Si Laravel devuelve errores al editar, reabre el modal correcto con sus datos.
            @if ($errors->any() && old('form_modal') === 'editar')
                abrirModalEditarPermiso(
                    @json(old('form_id_permiso')),
                    @json(old('form_nombre')),
                    @json(old('form_estado', '1'))
                );
            @endif
        </script>
    @endpush

</x-admin-layout>
