<x-admin-layout title="Fabricantes | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Control de Productos',
        'href' => '#',
    ],
    [
        'name' => 'Fabricantes',
        'href' => route('fabricantes_index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button type="button" blue onclick="abrirModalFabricanteCrear()">
            Nuevo fabricante
        </x-wire-button>
    </x-slot>

    {{-- Listado principal: columnas y acciones en app/Livewire/Datatables/FabricanteTable.php. --}}
    @livewire('datatables.fabricante-table')

    <div id="modalFabricante" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4">
        <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 id="modalFabricanteTitulo" class="text-base font-black text-slate-800">Nuevo fabricante</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Complete los datos del fabricante.</p>
            </div>

            <form id="formFabricante" action="{{ route('fabricantes_store') }}" method="POST" class="space-y-4 p-5">
                @csrf
                <input id="fabricanteMetodo" type="hidden" name="_method" value="POST" disabled>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-wire-input label="Nombre" id="fabricante_nombre" name="form_nombre" type="text"
                        placeholder="Nombre del fabricante" />

                    <x-wire-input label="Razon social" id="fabricante_razon_social" name="form_razon_social" type="text"
                        placeholder="Razon social" />
                </div>

                <x-wire-textarea label="Descripcion" id="fabricante_descripcion" name="form_descripcion"
                    placeholder="Descripcion del fabricante"></x-wire-textarea>

                <x-wire-select label="Estado" id="fabricante_estado" name="form_estado"
                    :options="[
                        ['id' => 'ACTIVO', 'nombre' => 'Activo'],
                        ['id' => 'INACTIVO', 'nombre' => 'Inactivo'],
                    ]"
                    option-label="nombre"
                    option-value="id" />

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <x-wire-button type="button" gray onclick="cerrarModalFabricante()">
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
            const modalFabricante = document.getElementById('modalFabricante');
            const formFabricante = document.getElementById('formFabricante');
            const metodoFabricante = document.getElementById('fabricanteMetodo');
            const tituloFabricante = document.getElementById('modalFabricanteTitulo');

            function abrirModalFabricanteCrear() {
                formFabricante.reset();
                formFabricante.action = @json(route('fabricantes_store'));
                metodoFabricante.disabled = true;
                metodoFabricante.value = 'POST';
                tituloFabricante.textContent = 'Nuevo fabricante';
                modalFabricante.classList.remove('hidden');
                modalFabricante.classList.add('flex');
            }

            function abrirModalFabricanteEditar(datos) {
                formFabricante.action = datos.url;
                metodoFabricante.disabled = false;
                metodoFabricante.value = 'PUT';
                tituloFabricante.textContent = 'Editar fabricante';

                formFabricante.querySelector('[name="form_nombre"]').value = datos.nombre || '';
                formFabricante.querySelector('[name="form_razon_social"]').value = datos.razonSocial || '';
                formFabricante.querySelector('[name="form_descripcion"]').value = datos.descripcion || '';
                formFabricante.querySelector('[name="form_estado"]').value = datos.estado || 'ACTIVO';

                modalFabricante.classList.remove('hidden');
                modalFabricante.classList.add('flex');
            }

            function cerrarModalFabricante() {
                modalFabricante.classList.add('hidden');
                modalFabricante.classList.remove('flex');
            }

            document.addEventListener('click', function (evento) {
                const botonEditar = evento.target.closest('[data-fabricante-editar]');

                if (botonEditar) {
                    abrirModalFabricanteEditar({
                        url: botonEditar.dataset.url,
                        nombre: botonEditar.dataset.nombre,
                        descripcion: botonEditar.dataset.descripcion,
                        razonSocial: botonEditar.dataset.razonSocial,
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
