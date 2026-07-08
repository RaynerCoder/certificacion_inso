<x-admin-layout title="Tipos de Productos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Tipos de Productos',
        'href' => route('tipos_productos_index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button type="button" blue onclick="abrirModalTipoProductoCrear()">
            Nuevo tipo
        </x-wire-button>
    </x-slot>

    {{-- Listado principal: columnas y acciones en app/Livewire/Datatables/TipoProductoTable.php. --}}
    @livewire('datatables.tipo-producto-table')

    <div id="modalTipoProducto" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4">
        <div class="w-full max-w-xl rounded-xl bg-white shadow-xl">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 id="modalTipoProductoTitulo" class="text-base font-black text-slate-800">Nuevo tipo de producto</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Complete la descripcion y el codigo si corresponde.</p>
            </div>

            <form id="formTipoProducto" action="{{ route('tipos_productos_store') }}" method="POST" class="space-y-4 p-5">
                @csrf
                <input id="tipoProductoMetodo" type="hidden" name="_method" value="POST" disabled>

                <x-wire-textarea label="Descripcion" id="tipo_producto_descripcion" name="form_descripcion"
                    placeholder="Descripcion del tipo de producto"></x-wire-textarea>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-wire-input label="Codigo" id="tipo_producto_codigo" name="form_codigo" type="text"
                        placeholder="Codigo interno" />

                    <x-wire-select label="Estado" id="tipo_producto_estado" name="form_estado"
                        :options="[
                            ['id' => 'ACTIVO', 'nombre' => 'Activo'],
                            ['id' => 'INACTIVO', 'nombre' => 'Inactivo'],
                        ]"
                        option-label="nombre"
                        option-value="id" />
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <x-wire-button type="button" gray onclick="cerrarModalTipoProducto()">
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" emerald>
                        Guardar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            const modalTipoProducto = document.getElementById('modalTipoProducto');
            const formTipoProducto = document.getElementById('formTipoProducto');
            const metodoTipoProducto = document.getElementById('tipoProductoMetodo');
            const tituloTipoProducto = document.getElementById('modalTipoProductoTitulo');

            function abrirModalTipoProductoCrear() {
                formTipoProducto.reset();
                formTipoProducto.action = @json(route('tipos_productos_store'));
                metodoTipoProducto.disabled = true;
                metodoTipoProducto.value = 'POST';
                tituloTipoProducto.textContent = 'Nuevo tipo de producto';
                modalTipoProducto.classList.remove('hidden');
                modalTipoProducto.classList.add('flex');
            }

            function abrirModalTipoProductoEditar(datos) {
                formTipoProducto.action = datos.url;
                metodoTipoProducto.disabled = false;
                metodoTipoProducto.value = 'PUT';
                tituloTipoProducto.textContent = 'Editar tipo de producto';

                formTipoProducto.querySelector('[name="form_descripcion"]').value = datos.descripcion || '';
                formTipoProducto.querySelector('[name="form_codigo"]').value = datos.codigo || '';
                formTipoProducto.querySelector('[name="form_estado"]').value = datos.estado || 'ACTIVO';

                modalTipoProducto.classList.remove('hidden');
                modalTipoProducto.classList.add('flex');
            }

            function cerrarModalTipoProducto() {
                modalTipoProducto.classList.add('hidden');
                modalTipoProducto.classList.remove('flex');
            }

            document.addEventListener('click', function (evento) {
                const botonEditar = evento.target.closest('[data-tipo-producto-editar]');

                if (botonEditar) {
                    abrirModalTipoProductoEditar({
                        url: botonEditar.dataset.url,
                        descripcion: botonEditar.dataset.descripcion,
                        codigo: botonEditar.dataset.codigo,
                        estado: botonEditar.dataset.estado,
                    });
                }
            });

            document.addEventListener('submit', function (evento) {
                const formularioEliminar = evento.target.closest('.delete-form');

                if (!formularioEliminar) {
                    return;
                }

                evento.preventDefault();

                Swal.fire({
                    title: 'Estas seguro?',
                    text: 'No podras revertir esta accion.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar',
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        formularioEliminar.submit();
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
