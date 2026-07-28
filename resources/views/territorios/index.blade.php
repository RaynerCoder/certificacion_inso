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

    <style>
        /* Mantiene las columnas legibles cuando la pantalla no tiene espacio suficiente. */
        .tabla-territorios-wrap {
            width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            -webkit-overflow-scrolling: touch;
        }

        .tabla-territorios-wrap table {
            width: 100%;
            min-width: 780px;
            table-layout: fixed;
        }

        .tabla-territorios-wrap th,
        .tabla-territorios-wrap td {
            vertical-align: middle;
            word-break: normal;
            overflow-wrap: break-word;
            font-size: 13px;
            line-height: 1.35;
            padding-top: 9px !important;
            padding-bottom: 9px !important;
        }

        .tabla-territorios-wrap th:first-child,
        .tabla-territorios-wrap td:first-child {
            width: 7%;
            min-width: 55px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tabla-territorios-wrap th:nth-child(2),
        .tabla-territorios-wrap td:nth-child(2) {
            width: 38%;
            min-width: 295px;
            text-align: left;
        }

        .tabla-territorios-wrap th:nth-child(3),
        .tabla-territorios-wrap td:nth-child(3) {
            width: 22%;
            min-width: 170px;
            text-align: center !important;
        }

        .tabla-territorios-wrap th:nth-child(3) > *,
        .tabla-territorios-wrap td:nth-child(3) > * {
            justify-content: center;
            text-align: center !important;
        }

        .tabla-territorios-wrap th:nth-child(3) button {
            width: 100%;
            justify-content: center !important;
        }

        .tabla-territorios-wrap th:nth-child(4),
        .tabla-territorios-wrap td:nth-child(4) {
            width: 14%;
            min-width: 110px;
            text-align: center !important;
            white-space: nowrap !important;
        }

        .tabla-territorios-wrap th:nth-child(4) button {
            width: 100%;
            justify-content: center !important;
            text-align: center !important;
        }

        .tabla-territorios-wrap th:last-child,
        .tabla-territorios-wrap td:last-child {
            width: 19%;
            min-width: 150px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tabla-territorios-wrap tbody tr {
            height: auto;
        }

        @media (max-width: 768px) {
            .tabla-territorios-wrap table {
                width: 780px;
            }
        }
    </style>

    {{-- La tabla conserva el listado; los formularios se abren en los modales de esta misma pantalla. --}}
    <div class="tabla-territorios-wrap">
        @livewire('datatables.territorio-table')
    </div>

    {{-- Registro de un territorio sin salir del listado. --}}
    <div id="modalCrearTerritorio" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/45 p-4">
        <div style="width: 520px; max-width: calc(100vw - 2rem); max-height: calc(100vh - 2rem);" class="overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="text-base font-black text-slate-800">Nuevo territorio</h2>
                <button type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-xl font-bold leading-none text-slate-500 hover:bg-slate-200 hover:text-slate-700"
                    onclick="cerrarModalCrearTerritorio()" aria-label="Cerrar" title="Cerrar">
                    &times;
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
        <div style="width: 520px; max-width: calc(100vw - 2rem); max-height: calc(100vh - 2rem);" class="overflow-y-auto rounded-lg bg-white shadow-xl">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="text-base font-black text-slate-800">Editar territorio</h2>
                <button type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-xl font-bold leading-none text-slate-500 hover:bg-slate-200 hover:text-slate-700"
                    onclick="cerrarModalEditarTerritorio()" aria-label="Cerrar" title="Cerrar">
                    &times;
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
            const rutaEditarTerritorio = @json(route('territorios_edit', ['territorio' => '__ID__']));

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

            // Selecciona una opción dentro del formulario y mantiene sincronizado el componente.
            function seleccionarValorTerritorio(formulario, nombreCampo, valor) {
                const select = formulario.querySelector(`select[name="${nombreCampo}"]`);

                if (!select) {
                    return;
                }

                const valorSeleccionado = valor === null || valor === undefined
                    ? ''
                    : String(valor);
                const opcionExiste = Array.from(select.options)
                    .some((opcion) => opcion.value === valorSeleccionado);

                const valorFinal = opcionExiste ? valorSeleccionado : '';

                Array.from(select.options).forEach((opcion) => {
                    opcion.selected = opcion.value === valorFinal;
                });

                select.value = valorFinal;
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Completa el formulario con los datos recibidos desde el controlador.
            function completarFormularioEditarTerritorio(territorio) {
                const formulario = document.getElementById('formEditarTerritorio');

                document.getElementById('editar_id_territorio').value = territorio.id;
                document.getElementById('editar_nombre').value = territorio.nombre || '';
                document.getElementById('editar_codigo').value = territorio.codigo || '';
                formulario.action = rutaActualizarTerritorio.replace('__ID__', territorio.id);

                mostrarModalTerritorio('modalEditarTerritorio');

                requestAnimationFrame(() => {
                    seleccionarValorTerritorio(formulario, 'form_id_ambito', territorio.id_ambito);
                    seleccionarValorTerritorio(formulario, 'form_id_padre_territorio', territorio.id_padre_territorio);
                    seleccionarValorTerritorio(formulario, 'form_id_estado', territorio.estado || 'ACTIVO');
                });
            }

            // Consulta el registro antes de abrir el modal para no depender de datos guardados en la tabla.
            async function abrirModalEditarTerritorio(id) {
                try {
                    const respuesta = await fetch(rutaEditarTerritorio.replace('__ID__', id), {
                        headers: {
                            Accept: 'application/json'
                        }
                    });

                    if (!respuesta.ok) {
                        throw new Error('No se pudo consultar el territorio.');
                    }

                    completarFormularioEditarTerritorio(await respuesta.json());
                } catch (error) {
                    Swal.fire({
                        title: 'No se pudo abrir el registro',
                        text: 'Actualice la página e intente nuevamente.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            }

            function cerrarModalEditarTerritorio() {
                ocultarModalTerritorio('modalEditarTerritorio');
            }

            document.addEventListener('click', function(evento) {
                const botonCrear = evento.target.closest('[data-crear-territorio]');
                const botonEditar = evento.target.closest('[data-editar-territorio]');

                if (botonCrear) {
                    abrirModalCrearTerritorio();
                    return;
                }

                if (!botonEditar) {
                    return;
                }

                abrirModalEditarTerritorio(botonEditar.getAttribute('data-id'));
            });

            document.addEventListener('submit', function(evento) {
                const formulario = evento.target.closest('.delete-form-territorio');

                if (!formulario) {
                    return;
                }

                evento.preventDefault();

                Swal.fire({
                    title: 'Eliminar territorio',
                    text: 'El estado del territorio cambiará a Inactivo si no está relacionado con otros datos.',
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
                completarFormularioEditarTerritorio({
                    id: @json(old('form_id_territorio')),
                    id_ambito: @json(old('form_id_ambito')),
                    id_padre_territorio: @json(old('form_id_padre_territorio')),
                    nombre: @json(old('form_nombre')),
                    codigo: @json(old('form_codigo')),
                    estado: @json(old('form_id_estado', 'ACTIVO'))
                });
            @endif
        </script>
    @endpush
</x-admin-layout>
