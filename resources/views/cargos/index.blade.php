<x-admin-layout title="Cargos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Cargos',
        'href' => route('cargos_index'),
    ],
]">

    @include('seguridad.estilos')

    <x-slot name="action">
        <x-wire-button type="button" blue data-crear-cargo>
            Nuevo cargo
        </x-wire-button>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/CargoTable.php --}}
    <div class="tabla-compacta tabla-cargos">
        @livewire('datatables.cargo-table')
    </div>

    {{-- Modal para crear cargos sin salir del listado. --}}
    <div id="modalCrearCargo" class="seg-modal hidden">
        <div class="seg-modal-box">
            <div class="seg-modal-head">
                <h2 class="seg-modal-title">Nuevo cargo</h2>
                <button type="button" class="seg-modal-close" onclick="cerrarModalCrearCargo()">x</button>
            </div>

            <form action="{{ route('cargos_store') }}" method="POST" class="space-y-4 p-4">
                @csrf
                <input type="hidden" name="form_modal" value="crear">

                <x-wire-input label="Nombre del cargo" id="crear_nombre" name="form_nombre" type="text"
                    placeholder="Ej: Tecnico evaluador" value="{{ old('form_modal') === 'crear' ? old('form_nombre') : '' }}" />

                <x-wire-native-select label="Area" id="crear_id_area" name="form_id_area">
                    <option value="">Seleccione el area</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(old('form_modal') === 'crear' && (string) old('form_id_area') === (string) $area->id)>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-textarea label="Descripcion" id="crear_descripcion" name="form_descripcion"
                    placeholder="Describa la responsabilidad del cargo" rows="3">{{ old('form_modal') === 'crear' ? old('form_descripcion') : '' }}</x-wire-textarea>

                <x-wire-native-select label="Estado" id="crear_estado" name="form_estado">
                    <option value="1" @selected(old('form_modal') !== 'crear' || old('form_estado') === '1')>Activo</option>
                    <option value="0" @selected(old('form_modal') === 'crear' && old('form_estado') === '0')>Inactivo</option>
                </x-wire-native-select>

                <div class="seg-actions !px-0 !pb-0">
                    <x-wire-button type="button" onclick="cerrarModalCrearCargo()" secondary>
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Guardar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal para editar el cargo elegido en la tabla. --}}
    <div id="modalEditarCargo" class="seg-modal hidden">
        <div class="seg-modal-box">
            <div class="seg-modal-head">
                <h2 class="seg-modal-title">Editar cargo</h2>
                <button type="button" class="seg-modal-close" onclick="cerrarModalEditarCargo()">x</button>
            </div>

            @php
                // Mantiene la accion correcta si Laravel devuelve el formulario con errores de validacion.
                $accionEditarCargo = old('form_modal') === 'editar' && old('form_id_cargo')
                    ? route('cargos_update', old('form_id_cargo'))
                    : '#';
            @endphp

            <form id="formEditarCargo" action="{{ $accionEditarCargo }}" method="POST" class="space-y-4 p-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_modal" value="editar">
                <input type="hidden" id="editar_id_cargo" name="form_id_cargo" value="{{ old('form_id_cargo') }}">

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                    <span class="font-black text-slate-800">Registro seleccionado:</span>
                    <span id="editar_resumen_cargo">Seleccione un cargo de la tabla.</span>
                </div>

                <x-wire-input label="Nombre del cargo" id="editar_nombre" name="form_nombre" type="text"
                    placeholder="Ej: Tecnico evaluador" value="{{ old('form_modal') === 'editar' ? old('form_nombre') : '' }}" />

                <x-wire-native-select label="Area" id="editar_id_area" name="form_id_area">
                    <option value="">Seleccione el area</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(old('form_modal') === 'editar' && (string) old('form_id_area') === (string) $area->id)>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-textarea label="Descripcion" id="editar_descripcion" name="form_descripcion"
                    placeholder="Describa la responsabilidad del cargo" rows="3">{{ old('form_modal') === 'editar' ? old('form_descripcion') : '' }}</x-wire-textarea>

                <x-wire-native-select label="Estado" id="editar_estado" name="form_estado">
                    <option value="1" @selected(old('form_modal') !== 'editar' || old('form_estado') === '1')>Activo</option>
                    <option value="0" @selected(old('form_modal') === 'editar' && old('form_estado') === '0')>Inactivo</option>
                </x-wire-native-select>

                <div class="seg-actions !px-0 !pb-0">
                    <x-wire-button type="button" onclick="cerrarModalEditarCargo()" secondary>
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
            // Ruta base usada para construir la accion del formulario de edicion.
            const rutaActualizarCargo = @json(route('cargos_update', ['cargo' => '__ID__']));

            // Abre el modal de registro de cargo.
            function abrirModalCrearCargo() {
                const modal = document.getElementById('modalCrearCargo');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Cierra el modal de registro y limpia errores visuales previos.
            function cerrarModalCrearCargo() {
                const modal = document.getElementById('modalCrearCargo');
                limpiarErroresModalCargo(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Carga datos en el modal de edicion desde los atributos del boton.
            function abrirModalEditarCargo(id, nombre, descripcion, idArea, estado) {
                document.getElementById('editar_id_cargo').value = id;
                document.getElementById('editar_nombre').value = nombre || '';
                document.getElementById('editar_descripcion').value = descripcion || '';
                document.getElementById('editar_id_area').value = idArea || '';
                document.getElementById('editar_estado').value = String(estado ?? '1');
                document.getElementById('formEditarCargo').action = rutaActualizarCargo.replace('__ID__', id);
                document.getElementById('editar_resumen_cargo').textContent = id
                    ? `ID ${id} - ${nombre || 'Sin nombre'}`
                    : 'Seleccione un cargo de la tabla.';

                const modal = document.getElementById('modalEditarCargo');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Cierra el modal de edicion y limpia errores visuales previos.
            function cerrarModalEditarCargo() {
                const modal = document.getElementById('modalEditarCargo');
                limpiarErroresModalCargo(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Limpia marcas de validacion generadas por los componentes del formulario.
            function limpiarErroresModalCargo(modal) {
                if (!modal) return;

                modal.querySelectorAll('[group-invalidated]').forEach((elemento) => {
                    elemento.removeAttribute('group-invalidated');
                });

                modal.querySelectorAll('label.text-negative-600, label.text-red-600').forEach((elemento) => {
                    elemento.remove();
                });
            }

            // Centraliza los clicks de crear/editar para que Livewire no duplique eventos.
            document.addEventListener('click', function(e) {
                const botonCrear = e.target.closest('[data-crear-cargo]');
                const botonEditar = e.target.closest('[data-editar-cargo]');

                if (botonCrear) {
                    abrirModalCrearCargo();
                    return;
                }

                if (!botonEditar) return;

                abrirModalEditarCargo(
                    botonEditar.dataset.id,
                    botonEditar.dataset.nombre,
                    botonEditar.dataset.descripcion,
                    botonEditar.dataset.idArea,
                    botonEditar.dataset.estado
                );
            });

            // Confirma con SweetAlert antes de eliminar un cargo.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) return;

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar cargo',
                    text: 'Solo se eliminara si no esta asignado a funcionarios.',
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

            @if ($errors->any() && old('form_modal') === 'crear')
                abrirModalCrearCargo();
            @endif

            @if ($errors->any() && old('form_modal') === 'editar')
                abrirModalEditarCargo(
                    @json(old('form_id_cargo')),
                    @json(old('form_nombre')),
                    @json(old('form_descripcion')),
                    @json(old('form_id_area')),
                    @json(old('form_estado', '1'))
                );
            @endif
        </script>
    @endpush

</x-admin-layout>
