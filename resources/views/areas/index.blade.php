<x-admin-layout title="Areas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Areas',
        'href' => route('areas_index'),
    ],
]">

    @include('seguridad.estilos')

    <x-slot name="action">
        <x-wire-button type="button" blue data-crear-area>
            Nueva area
        </x-wire-button>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/AreaTable.php --}}
    <div class="tabla-compacta tabla-areas">
        @livewire('datatables.area-table')
    </div>

    {{-- Modal para crear areas sin salir del listado. --}}
    <div id="modalCrearArea" class="seg-modal hidden">
        <div class="seg-modal-box">
            <div class="seg-modal-head">
                <h2 class="seg-modal-title">Nueva area</h2>
                <button type="button" class="seg-modal-close" onclick="cerrarModalCrearArea()">x</button>
            </div>

            <form action="{{ route('areas_store') }}" method="POST" class="space-y-4 p-4">
                @csrf
                <input type="hidden" name="form_modal" value="crear">

                <x-wire-native-select label="Area superior" id="crear_id_area_padre" name="form_id_area_padre">
                    <option value="">Sin area superior</option>
                    @foreach ($areasPadre as $areaPadre)
                        <option value="{{ $areaPadre->id }}" @selected(old('form_modal') === 'crear' && (string) old('form_id_area_padre') === (string) $areaPadre->id)>
                            {{ $areaPadre->nombre }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Nombre del area" id="crear_nombre" name="form_nombre" type="text"
                    placeholder="Ej: Area de laboratorio" value="{{ old('form_modal') === 'crear' ? old('form_nombre') : '' }}" />

                <x-wire-textarea label="Descripcion" id="crear_descripcion" name="form_descripcion"
                    placeholder="Describa el alcance del area" rows="3">{{ old('form_modal') === 'crear' ? old('form_descripcion') : '' }}</x-wire-textarea>

                <x-wire-native-select label="Estado" id="crear_estado" name="form_estado">
                    <option value="1" @selected(old('form_modal') !== 'crear' || old('form_estado') === '1')>Activo</option>
                    <option value="0" @selected(old('form_modal') === 'crear' && old('form_estado') === '0')>Inactivo</option>
                </x-wire-native-select>

                <div class="seg-actions !px-0 !pb-0">
                    <x-wire-button type="button" onclick="cerrarModalCrearArea()" secondary>
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Guardar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal para editar el area elegida en la tabla. --}}
    <div id="modalEditarArea" class="seg-modal hidden">
        <div class="seg-modal-box">
            <div class="seg-modal-head">
                <h2 class="seg-modal-title">Editar area</h2>
                <button type="button" class="seg-modal-close" onclick="cerrarModalEditarArea()">x</button>
            </div>

            @php
                // Mantiene la accion correcta si Laravel devuelve el formulario con errores de validacion.
                $accionEditarArea = old('form_modal') === 'editar' && old('form_id_area')
                    ? route('areas_update', old('form_id_area'))
                    : '#';
            @endphp

            <form id="formEditarArea" action="{{ $accionEditarArea }}" method="POST" class="space-y-4 p-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_modal" value="editar">
                <input type="hidden" id="editar_id_area" name="form_id_area" value="{{ old('form_id_area') }}">

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                    <span class="font-black text-slate-800">Registro seleccionado:</span>
                    <span id="editar_resumen_area">Seleccione un area de la tabla.</span>
                </div>

                <x-wire-native-select label="Area superior" id="editar_id_area_padre" name="form_id_area_padre">
                    <option value="">Sin area superior</option>
                    @foreach ($areasPadre as $areaPadre)
                        <option value="{{ $areaPadre->id }}" @selected(old('form_modal') === 'editar' && (string) old('form_id_area_padre') === (string) $areaPadre->id)>
                            {{ $areaPadre->nombre }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Nombre del area" id="editar_nombre" name="form_nombre" type="text"
                    placeholder="Ej: Area de laboratorio" value="{{ old('form_modal') === 'editar' ? old('form_nombre') : '' }}" />

                <x-wire-textarea label="Descripcion" id="editar_descripcion" name="form_descripcion"
                    placeholder="Describa el alcance del area" rows="3">{{ old('form_modal') === 'editar' ? old('form_descripcion') : '' }}</x-wire-textarea>

                <x-wire-native-select label="Estado" id="editar_estado" name="form_estado">
                    <option value="1" @selected(old('form_modal') !== 'editar' || old('form_estado') === '1')>Activo</option>
                    <option value="0" @selected(old('form_modal') === 'editar' && old('form_estado') === '0')>Inactivo</option>
                </x-wire-native-select>

                <div class="seg-actions !px-0 !pb-0">
                    <x-wire-button type="button" onclick="cerrarModalEditarArea()" secondary>
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
            const rutaActualizarArea = @json(route('areas_update', ['area' => '__ID__']));

            // Abre el modal de registro de area.
            function abrirModalCrearArea() {
                const modal = document.getElementById('modalCrearArea');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Cierra el modal de registro y limpia errores visuales previos.
            function cerrarModalCrearArea() {
                const modal = document.getElementById('modalCrearArea');
                limpiarErroresModalArea(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Carga datos en el modal de edicion desde los atributos del boton.
            function abrirModalEditarArea(id, idAreaPadre, nombre, descripcion, estado) {
                document.getElementById('editar_id_area').value = id;
                document.getElementById('editar_id_area_padre').value = idAreaPadre || '';
                document.getElementById('editar_nombre').value = nombre || '';
                document.getElementById('editar_descripcion').value = descripcion || '';
                document.getElementById('editar_estado').value = String(estado ?? '1');
                document.getElementById('formEditarArea').action = rutaActualizarArea.replace('__ID__', id);
                document.getElementById('editar_resumen_area').textContent = id
                    ? `ID ${id} - ${nombre || 'Sin nombre'}`
                    : 'Seleccione un area de la tabla.';

                const modal = document.getElementById('modalEditarArea');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Cierra el modal de edicion y limpia errores visuales previos.
            function cerrarModalEditarArea() {
                const modal = document.getElementById('modalEditarArea');
                limpiarErroresModalArea(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Limpia marcas de validacion generadas por los componentes del formulario.
            function limpiarErroresModalArea(modal) {
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
                const botonCrear = e.target.closest('[data-crear-area]');
                const botonEditar = e.target.closest('[data-editar-area]');

                if (botonCrear) {
                    abrirModalCrearArea();
                    return;
                }

                if (!botonEditar) return;

                abrirModalEditarArea(
                    botonEditar.dataset.id,
                    botonEditar.dataset.idAreaPadre,
                    botonEditar.dataset.nombre,
                    botonEditar.dataset.descripcion,
                    botonEditar.dataset.estado
                );
            });

            // Confirma con SweetAlert antes de eliminar un area.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form-area');

                if (!formulario) return;

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar area',
                    text: 'Solo se eliminara si no tiene subareas ni cargos relacionados.',
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
                abrirModalCrearArea();
            @endif

            @if ($errors->any() && old('form_modal') === 'editar')
                abrirModalEditarArea(
                    @json(old('form_id_area')),
                    @json(old('form_id_area_padre')),
                    @json(old('form_nombre')),
                    @json(old('form_descripcion')),
                    @json(old('form_estado', '1'))
                );
            @endif
        </script>
    @endpush

</x-admin-layout>
