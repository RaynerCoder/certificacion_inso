<x-admin-layout title="Territorios | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Territorios',
        'href' => route('territorios_index'),
    ],
]">

    <x-slot name="action">
        <x-wire-button type="button" blue data-crear-territorio>
            Nuevo territorio
        </x-wire-button>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- La tabla conserva el listado; los formularios se abren en los modales de esta misma pantalla. --}}
    @livewire('datatables.territorio-table')

    {{-- Registro de un territorio sin salir del listado. --}}
    <div id="modalCrearTerritorio" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/45 p-4">
        <div class="max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="text-base font-black text-slate-800">Nuevo territorio</h2>
                <button type="button" class="rounded-md bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-600" onclick="cerrarModalCrearTerritorio()">
                    Cerrar
                </button>
            </div>

            <form action="{{ route('territorios_store') }}" method="POST" class="space-y-4 p-5">
                @csrf
                <input type="hidden" name="form_modal" value="crear">

                <x-wire-native-select label="Ámbito" name="form_id_ambito" required>
                    <option value="">Seleccione un ámbito</option>
                    @foreach ($ambitos as $ambito)
                        <option value="{{ $ambito->id }}" @selected(old('form_modal') === 'crear' && (string) old('form_id_ambito') === (string) $ambito->id)>
                            {{ $ambito->nombre }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Territorio superior" name="form_id_padre_territorio">
                    <option value="">Sin territorio superior</option>
                    @foreach ($territorios as $territorioPadre)
                        <option value="{{ $territorioPadre->id }}" @selected(old('form_modal') === 'crear' && (string) old('form_id_padre_territorio') === (string) $territorioPadre->id)>
                            {{ $territorioPadre->nombre }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Nombre" name="form_nombre" type="text" required
                    placeholder="Nombre del territorio" value="{{ old('form_modal') === 'crear' ? old('form_nombre') : '' }}" />

                <x-wire-input label="Código" name="form_codigo" type="text"
                    placeholder="Código del territorio" value="{{ old('form_modal') === 'crear' ? old('form_codigo') : '' }}" />

                <x-wire-native-select label="Estado" name="form_id_estado" required>
                    <option value="ACTIVO" @selected(old('form_modal') !== 'crear' || old('form_id_estado') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(old('form_modal') === 'crear' && old('form_id_estado') === 'INACTIVO')>Inactivo</option>
                </x-wire-native-select>

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-wire-button type="button" secondary onclick="cerrarModalCrearTerritorio()">
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Guardar
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edición del registro elegido desde la tabla. --}}
    <div id="modalEditarTerritorio" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/45 p-4">
        <div class="max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="text-base font-black text-slate-800">Editar territorio</h2>
                <button type="button" class="rounded-md bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-600" onclick="cerrarModalEditarTerritorio()">
                    Cerrar
                </button>
            </div>

            @php
                $accionEditar = old('form_modal') === 'editar' && old('form_id_territorio')
                    ? route('territorios_update', old('form_id_territorio'))
                    : '#';
            @endphp

            <form id="formEditarTerritorio" action="{{ $accionEditar }}" method="POST" class="space-y-4 p-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_modal" value="editar">
                <input type="hidden" id="editar_id_territorio" name="form_id_territorio" value="{{ old('form_id_territorio') }}">

                <x-wire-native-select label="Ámbito" id="editar_id_ambito" name="form_id_ambito" required>
                    <option value="">Seleccione un ámbito</option>
                    @foreach ($ambitos as $ambito)
                        <option value="{{ $ambito->id }}" @selected(old('form_modal') === 'editar' && (string) old('form_id_ambito') === (string) $ambito->id)>
                            {{ $ambito->nombre }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Territorio superior" id="editar_id_padre_territorio" name="form_id_padre_territorio">
                    <option value="">Sin territorio superior</option>
                    @foreach ($territorios as $territorioPadre)
                        <option value="{{ $territorioPadre->id }}" @selected(old('form_modal') === 'editar' && (string) old('form_id_padre_territorio') === (string) $territorioPadre->id)>
                            {{ $territorioPadre->nombre }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Nombre" id="editar_nombre" name="form_nombre" type="text" required
                    placeholder="Nombre del territorio" value="{{ old('form_modal') === 'editar' ? old('form_nombre') : '' }}" />

                <x-wire-input label="Código" id="editar_codigo" name="form_codigo" type="text"
                    placeholder="Código del territorio" value="{{ old('form_modal') === 'editar' ? old('form_codigo') : '' }}" />

                <x-wire-native-select label="Estado" id="editar_estado" name="form_id_estado" required>
                    <option value="ACTIVO" @selected(old('form_modal') !== 'editar' || old('form_id_estado') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(old('form_modal') === 'editar' && old('form_id_estado') === 'INACTIVO')>Inactivo</option>
                </x-wire-native-select>

                <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                    <x-wire-button type="button" secondary onclick="cerrarModalEditarTerritorio()">
                        Cancelar
                    </x-wire-button>
                    <x-wire-button type="submit" blue>
                        Guardar cambios
                    </x-wire-button>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            const rutaActualizarTerritorio = @json(route('territorios_update', ['territorio' => '__ID__']));

            function mostrarModalTerritorio(idModal) {
                const modal = document.getElementById(idModal);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function ocultarModalTerritorio(idModal) {
                const modal = document.getElementById(idModal);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function abrirModalCrearTerritorio() {
                mostrarModalTerritorio('modalCrearTerritorio');
            }

            function cerrarModalCrearTerritorio() {
                ocultarModalTerritorio('modalCrearTerritorio');
            }

            // Completa el formulario con el registro seleccionado antes de mostrar el modal.
            function abrirModalEditarTerritorio(id, idAmbito, idPadre, nombre, codigo, estado) {
                document.getElementById('editar_id_territorio').value = id;
                document.getElementById('editar_id_ambito').value = idAmbito || '';
                document.getElementById('editar_id_padre_territorio').value = idPadre || '';
                document.getElementById('editar_nombre').value = nombre || '';
                document.getElementById('editar_codigo').value = codigo || '';
                document.getElementById('editar_estado').value = estado || 'ACTIVO';
                document.getElementById('formEditarTerritorio').action = rutaActualizarTerritorio.replace('__ID__', id);

                mostrarModalTerritorio('modalEditarTerritorio');
            }

            function cerrarModalEditarTerritorio() {
                ocultarModalTerritorio('modalEditarTerritorio');
            }

            document.addEventListener('click', function(evento) {
                if (evento.target.closest('[data-crear-territorio]')) {
                    abrirModalCrearTerritorio();
                }
            });

            document.addEventListener('submit', function(evento) {
                const formulario = evento.target.closest('.delete-form-territorio');

                if (!formulario) {
                    return;
                }

                evento.preventDefault();

                Swal.fire({
                    title: 'Eliminar territorio',
                    text: 'Solo se eliminará si no tiene territorios relacionados.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        formulario.submit();
                    }
                });
            });

            @if ($errors->any() && old('form_modal') === 'crear')
                abrirModalCrearTerritorio();
            @endif

            @if ($errors->any() && old('form_modal') === 'editar')
                abrirModalEditarTerritorio(
                    @json(old('form_id_territorio')),
                    @json(old('form_id_ambito')),
                    @json(old('form_id_padre_territorio')),
                    @json(old('form_nombre')),
                    @json(old('form_codigo')),
                    @json(old('form_id_estado', 'ACTIVO'))
                );
            @endif
        </script>
    @endpush
</x-admin-layout>
