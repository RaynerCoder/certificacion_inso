<x-admin-layout title="Ingredientes | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Ingredientes',
        'href' => route('ingredientes_index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button type="button" blue onclick="abrirModalIngredienteCrear()">
            Nuevo ingrediente
        </x-wire-button>
    </x-slot>

    {{-- Listado principal: columnas y acciones en app/Livewire/Datatables/IngredienteTable.php. --}}
    @livewire('datatables.ingrediente-table')

    <div id="modalIngrediente" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4">
        <div class="w-full max-w-xl rounded-xl bg-white shadow-xl">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 id="modalIngredienteTitulo" class="text-base font-black text-slate-800">Nuevo ingrediente</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Complete los datos del ingrediente.</p>
            </div>

            <form id="formIngrediente" action="{{ route('ingredientes_store') }}" method="POST" class="space-y-4 p-5">
                @csrf
                <input id="ingredienteMetodo" type="hidden" name="_method" value="POST" disabled>

                <x-wire-input label="Nombre" id="form_nombre" name="form_nombre" type="text"
                    placeholder="Nombre del ingrediente" />

                <x-wire-input label="Composicion" id="form_composicion" name="form_composicion" type="text"
                    placeholder="Ej: Composicion quimica" />

                <x-wire-input label="Riesgo salud" id="form_riesgo_salud" name="form_riesgo_salud" type="text"
                    placeholder="Ej: Bajo, medio, alto" />

                <x-wire-select label="Estado" id="form_estado" name="form_estado"
                    :options="[
                        ['id' => 'ACTIVO', 'nombre' => 'Activo'],
                        ['id' => 'INACTIVO', 'nombre' => 'Inactivo'],
                    ]"
                    option-label="nombre"
                    option-value="id" />

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <x-wire-button type="button" gray onclick="cerrarModalIngrediente()">
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
            const modalIngrediente = document.getElementById('modalIngrediente');
            const formIngrediente = document.getElementById('formIngrediente');
            const metodoIngrediente = document.getElementById('ingredienteMetodo');
            const tituloIngrediente = document.getElementById('modalIngredienteTitulo');

            function abrirModalIngredienteCrear() {
                formIngrediente.reset();
                formIngrediente.action = @json(route('ingredientes_store'));
                metodoIngrediente.disabled = true;
                metodoIngrediente.value = 'POST';
                tituloIngrediente.textContent = 'Nuevo ingrediente';
                modalIngrediente.classList.remove('hidden');
                modalIngrediente.classList.add('flex');
            }

            function abrirModalIngredienteEditar(datos) {
                formIngrediente.action = datos.url;
                metodoIngrediente.disabled = false;
                metodoIngrediente.value = 'PUT';
                tituloIngrediente.textContent = 'Editar ingrediente';

                formIngrediente.querySelector('[name="form_nombre"]').value = datos.nombre || '';
                formIngrediente.querySelector('[name="form_composicion"]').value = datos.composicion || '';
                formIngrediente.querySelector('[name="form_riesgo_salud"]').value = datos.riesgoSalud || '';
                formIngrediente.querySelector('[name="form_estado"]').value = datos.estado || 'ACTIVO';

                modalIngrediente.classList.remove('hidden');
                modalIngrediente.classList.add('flex');
            }

            function cerrarModalIngrediente() {
                modalIngrediente.classList.add('hidden');
                modalIngrediente.classList.remove('flex');
            }

            document.addEventListener('click', function (evento) {
                const botonEditar = evento.target.closest('[data-ingrediente-editar]');

                if (botonEditar) {
                    abrirModalIngredienteEditar({
                        url: botonEditar.dataset.url,
                        nombre: botonEditar.dataset.nombre,
                        composicion: botonEditar.dataset.composicion,
                        riesgoSalud: botonEditar.dataset.riesgoSalud,
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
