<x-admin-layout title="Clasificaciones toxicológicas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Clasificaciones toxicológicas',
        'href' => route('clasificaciones_toxicologicas_index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button type="button" blue onclick="abrirModalClasificacionToxicologicaCrear()">
            Nueva clasificación
        </x-wire-button>
    </x-slot>

    {{-- Listado principal: columnas y acciones en ClasificacionToxicologicaTable. --}}
    @livewire('datatables.clasificacion-toxicologica-table')

    <div id="modalClasificacionToxicologica" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4">
        <div class="w-full max-w-xl rounded-xl bg-white shadow-xl">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 id="modalClasificacionToxicologicaTitulo" class="text-base font-black text-slate-800">Nueva clasificación toxicológica</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Ingrese el código y agregue una descripción si corresponde.</p>
            </div>

            <form id="formClasificacionToxicologica" action="{{ route('clasificaciones_toxicologicas_store') }}" method="POST" class="space-y-4 p-5">
                @csrf
                <input id="clasificacionToxicologicaMetodo" type="hidden" name="_method" value="POST" disabled>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-wire-input label="Código" id="clasificacion_toxicologica_codigo" name="form_codigo" type="text"
                        placeholder="Ej: CLASE II" />

                    <x-wire-select label="Estado" id="clasificacion_toxicologica_estado" name="form_estado"
                        :options="[
                            ['id' => 'ACTIVO', 'nombre' => 'Activo'],
                            ['id' => 'INACTIVO', 'nombre' => 'Inactivo'],
                        ]"
                        option-label="nombre"
                        option-value="id" />
                </div>

                <x-wire-textarea label="Descripción (opcional)" id="clasificacion_toxicologica_descripcion" name="form_descripcion"
                    placeholder="Ej: Moderadamente peligroso"></x-wire-textarea>

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <x-wire-button type="button" gray onclick="cerrarModalClasificacionToxicologica()">
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
            const modalClasificacionToxicologica = document.getElementById('modalClasificacionToxicologica');
            const formClasificacionToxicologica = document.getElementById('formClasificacionToxicologica');
            const metodoClasificacionToxicologica = document.getElementById('clasificacionToxicologicaMetodo');
            const tituloClasificacionToxicologica = document.getElementById('modalClasificacionToxicologicaTitulo');

            function abrirModalClasificacionToxicologicaCrear() {
                formClasificacionToxicologica.reset();
                formClasificacionToxicologica.action = @json(route('clasificaciones_toxicologicas_store'));
                metodoClasificacionToxicologica.disabled = true;
                metodoClasificacionToxicologica.value = 'POST';
                tituloClasificacionToxicologica.textContent = 'Nueva clasificación toxicológica';
                modalClasificacionToxicologica.classList.remove('hidden');
                modalClasificacionToxicologica.classList.add('flex');
            }

            function abrirModalClasificacionToxicologicaEditar(datos) {
                formClasificacionToxicologica.action = datos.url;
                metodoClasificacionToxicologica.disabled = false;
                metodoClasificacionToxicologica.value = 'PUT';
                tituloClasificacionToxicologica.textContent = 'Editar clasificación toxicológica';

                formClasificacionToxicologica.querySelector('[name="form_descripcion"]').value = datos.descripcion || '';
                formClasificacionToxicologica.querySelector('[name="form_codigo"]').value = datos.codigo || '';
                formClasificacionToxicologica.querySelector('[name="form_estado"]').value = datos.estado || 'ACTIVO';

                modalClasificacionToxicologica.classList.remove('hidden');
                modalClasificacionToxicologica.classList.add('flex');
            }

            function cerrarModalClasificacionToxicologica() {
                modalClasificacionToxicologica.classList.add('hidden');
                modalClasificacionToxicologica.classList.remove('flex');
            }

            document.addEventListener('click', function (evento) {
                const botonEditar = evento.target.closest('[data-clasificacion-toxicologica-editar]');

                if (botonEditar) {
                    abrirModalClasificacionToxicologicaEditar({
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
                    title: '¿Está seguro?',
                    text: 'No podrá revertir esta acción.',
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
