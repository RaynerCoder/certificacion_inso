<x-admin-layout title="Tipos de Evidencias | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Certificados',
        'href' => '',
    ],
    [
        'name' => 'Tipos de Evidencias',
        'href' => route('tipos_evidencias_index'),
    ],
]">

    <x-slot name="action">
        <x-wire-button type="button" blue data-crear-tipo-evidencia onclick="abrirModalCrearTipoEvidencia()">
            Nuevo
        </x-wire-button>
    </x-slot>

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/TipoEvidenciaTable.php --}}
    @livewire('datatables.tipo-evidencia-table')

    {{-- MODAL PARA CREAR UN NUEVO TIPO DE EVIDENCIA --}}
    <div id="modalCrearTipoEvidencia" class="hidden fixed inset-0 z-[9999] bg-black/45 items-center justify-center p-4">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-2">
                <h2 class="text-lg font-bold text-slate-800">Nuevo tipo de evidencia</h2>
                <button type="button" onclick="cerrarModalCrearTipoEvidencia()"
                    class="rounded-lg px-3 py-1 text-xl font-bold text-slate-500 hover:bg-slate-100">
                    x
                </button>
            </div>

            <form action="{{ route('tipos_evidencias_store') }}" method="POST" class="space-y-2 px-5 pb-4 pt-2">
                @csrf

                <input type="hidden" name="form_modal" value="crear">

                <x-wire-input label="Codigo" id="crear_codigo" name="form_codigo" type="text"
                    placeholder="Ejemplo: PDF" :value="old('form_codigo')" />

                <x-wire-input label="Nombre" id="crear_nombre" name="form_nombre" type="text"
                    placeholder="Ejemplo: Documento PDF" :value="old('form_nombre')" />

                <x-wire-textarea label="Descripcion" id="crear_descripcion" name="form_descripcion"
                    placeholder="Ejemplo: Archivo PDF presentado por el solicitante." rows="3" :value="old('form_descripcion')" />

                <x-wire-input label="Peso maximo (MB)" id="crear_tamanio_maximo_mb" name="form_tamanio_maximo_mb"
                    type="number" min="0" max="100" placeholder="Ejemplo: 10" :value="old('form_tamanio_maximo_mb', 0)"
                    hint="Use 0 cuando no se suba archivo para este tipo de evidencia." />

                <x-wire-native-select label="Estado" id="crear_estado" name="form_estado">
                    <option value="ACTIVO" @selected(old('form_modal') !== 'crear' || old('form_estado') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(old('form_modal') === 'crear' && old('form_estado') === 'INACTIVO')>Inactivo</option>
                </x-wire-native-select>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-2">
                    <x-wire-button type="button" onclick="cerrarModalCrearTipoEvidencia()" secondary>
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Guardar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL PARA EDITAR TIPO DE EVIDENCIA --}}
    <div id="modalEditarTipoEvidencia"
        class="hidden fixed inset-0 z-[9999] bg-black/45 items-center justify-center p-4">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-2">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Editar tipo de evidencia</h2>
                </div>
                <button type="button" onclick="cerrarModalEditarTipoEvidencia()"
                    class="rounded-lg px-3 py-1 text-xl font-bold text-slate-500 hover:bg-slate-100">
                    x
                </button>
            </div>

            <form id="formEditarTipoEvidencia" action="#" method="POST" class="space-y-2 px-5 pb-4 pt-2">
                @csrf
                @method('PUT')
                {{-- Permite reabrir este modal si Laravel devuelve errores de validacion. --}}
                <input type="hidden" name="form_modal" value="editar">
                <input type="hidden" id="editar_id_tipo_evidencia" name="form_id_tipo_evidencia" value="{{ old('form_id_tipo_evidencia') }}">

                <x-wire-input label="Codigo" id="editar_codigo" name="form_codigo" type="text"
                    placeholder="Ejemplo: PDF" :value="old('form_codigo')" />

                <x-wire-input label="Nombre" id="editar_nombre" name="form_nombre" type="text"
                    placeholder="Ejemplo: Documento PDF" :value="old('form_nombre')" />

                <x-wire-textarea label="Descripcion" id="editar_descripcion" name="form_descripcion"
                    placeholder="Ejemplo: Archivo PDF presentado por el solicitante." rows="3" :value="old('form_descripcion')" />

                <x-wire-input label="Peso maximo (MB)" id="editar_tamanio_maximo_mb" name="form_tamanio_maximo_mb"
                    type="number" min="0" max="100" placeholder="Ejemplo: 10" :value="old('form_tamanio_maximo_mb', 0)"
                    hint="Use 0 cuando no se suba archivo para este tipo de evidencia." />

                <x-wire-native-select label="Estado" id="editar_estado" name="form_estado">
                    <option value="ACTIVO" @selected(old('form_modal') !== 'editar' || old('form_estado') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(old('form_modal') === 'editar' && old('form_estado') === 'INACTIVO')>Inactivo</option>
                </x-wire-native-select>

                <div class="flex justify-end gap-3 border-t border-slate-100 pt-2">
                    <x-wire-button type="button" onclick="cerrarModalEditarTipoEvidencia()" secondary>
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
            // Ruta para editar el tipo de evidencia.
            const rutaActualizarTipoEvidencia = @json(route('tipos_evidencias_update', ['tipoEvidencia' => '__ID__']));

            // Muestra el modal de creacion desde el boton Nuevo.
            function abrirModalCrearTipoEvidencia() {
                const modal = document.getElementById('modalCrearTipoEvidencia');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Oculta el modal de creacion.
            function cerrarModalCrearTipoEvidencia() {
                const modal = document.getElementById('modalCrearTipoEvidencia');
                limpiarErroresModalTipoEvidencia(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Carga datos de la fila y prepara la ruta PUT para editar el registro correcto.
            function abrirModalEditarTipoEvidencia(id, codigo, nombre, descripcion, tamanioMaximoMb, estado) {
                document.getElementById('editar_id_tipo_evidencia').value = id;
                document.getElementById('editar_codigo').value = codigo || '';
                document.getElementById('editar_nombre').value = nombre || '';
                document.getElementById('editar_descripcion').value = descripcion || '';
                document.getElementById('editar_tamanio_maximo_mb').value = tamanioMaximoMb || '0';
                document.getElementById('editar_estado').value = estado || 'ACTIVO';
                document.getElementById('formEditarTipoEvidencia').action = rutaActualizarTipoEvidencia.replace('__ID__', id);

                const modal = document.getElementById('modalEditarTipoEvidencia');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Oculta el modal de edicion.
            function cerrarModalEditarTipoEvidencia() {
                const modal = document.getElementById('modalEditarTipoEvidencia');
                limpiarErroresModalTipoEvidencia(modal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            // Limpia mensajes required de WireUI al cerrar el modal para que no reaparezcan al volver a abrir.
            function limpiarErroresModalTipoEvidencia(modal) {
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

            // SE ENCARGA DE MOSTRAR EL MODAL PARA CREAR O EDITAR.
            document.addEventListener('click', function(e) {
                const botonCrear = e.target.closest('[data-crear-tipo-evidencia]');
                const botonEditar = e.target.closest('[data-editar-tipo-evidencia]');

                if (botonCrear) {
                    abrirModalCrearTipoEvidencia();
                    return;
                }

                if (!botonEditar) {
                    return;
                }

                abrirModalEditarTipoEvidencia(
                    botonEditar.dataset.id,
                    botonEditar.dataset.codigo,
                    botonEditar.dataset.nombre,
                    botonEditar.dataset.descripcion,
                    botonEditar.dataset.tamanioMaximoMb,
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
                abrirModalCrearTipoEvidencia();
            @endif

            @if ($errors->any() && old('form_modal') === 'editar')
                abrirModalEditarTipoEvidencia(
                    @json(old('form_id_tipo_evidencia')),
                    @json(old('form_codigo')),
                    @json(old('form_nombre')),
                    @json(old('form_descripcion')),
                    @json(old('form_tamanio_maximo_mb', 0)),
                    @json(old('form_estado', 'ACTIVO'))
                );
            @endif
        </script>
    @endpush

</x-admin-layout>
