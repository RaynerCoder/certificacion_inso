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

    <style>
        .tabla-areas-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .tabla-areas-wrap table {
            width: 100%;
            min-width: 960px;
            table-layout: fixed;
        }

        .tabla-areas-wrap th,
        .tabla-areas-wrap td {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            vertical-align: middle;
            word-break: normal;
            overflow-wrap: break-word;
            line-height: 1.35;
        }

        .tabla-areas-wrap th:first-child,
        .tabla-areas-wrap td:first-child {
            width: 5%;
            text-align: center;
            white-space: nowrap !important;
        }

        .tabla-areas-wrap th:nth-child(2),
        .tabla-areas-wrap td:nth-child(2) {
            width: 27%;
        }

        .tabla-areas-wrap th:nth-child(3),
        .tabla-areas-wrap td:nth-child(3) {
            width: 30%;
        }

        .tabla-areas-wrap th:nth-child(4),
        .tabla-areas-wrap td:nth-child(4) {
            width: 12%;
            text-align: center;
            white-space: nowrap !important;
        }

        .tabla-areas-wrap th:nth-child(5),
        .tabla-areas-wrap td:nth-child(5) {
            width: 9%;
            text-align: center;
            white-space: nowrap !important;
        }

        .tabla-areas-wrap th:last-child,
        .tabla-areas-wrap td:last-child {
            width: 17%;
            text-align: center;
            white-space: nowrap !important;
        }

        .tabla-areas-wrap th:nth-child(4) > div,
        .tabla-areas-wrap th:nth-child(4) button,
        .tabla-areas-wrap th:nth-child(5) > div,
        .tabla-areas-wrap th:nth-child(5) button,
        .tabla-areas-wrap th:last-child > div {
            width: 100%;
            justify-content: center;
            text-align: center;
        }

        .tabla-areas-wrap td:nth-child(4) .tabla-chip,
        .tabla-areas-wrap td:nth-child(5) .tabla-chip {
            margin-right: auto;
            margin-left: auto;
        }

        .tabla-areas-wrap td:nth-child(2) .tabla-texto-ajustado,
        .tabla-areas-wrap td:nth-child(3) .tabla-texto-ajustado {
            display: block;
            max-width: none;
            overflow: visible;
            -webkit-line-clamp: unset;
        }

        .tabla-areas-wrap .tabla-acciones {
            justify-content: center;
            flex-wrap: nowrap;
        }

        .area-detail-modal {
            width: min(500px, calc(100vw - 2rem));
            max-height: calc(100vh - 2rem);
            overflow: hidden;
        }

        .area-detail-header {
            position: relative;
            display: flex;
            align-items: center;
            min-height: 76px;
            padding: 16px 56px 16px 24px;
            border-bottom: 1px solid #cfe7df;
            background: #edf8f4;
        }

        .area-detail-heading {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 14px;
        }

        .area-detail-heading-icon {
            display: inline-flex;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            align-items: center;
            justify-content: center;
            color: #15803d;
            font-size: 24px;
        }

        .area-detail-title {
            color: #172033;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.25;
        }

        .area-detail-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.35;
        }

        .area-detail-close {
            position: absolute;
            top: 50%;
            right: 18px;
            display: inline-flex;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 6px;
            color: #475569;
            font-size: 24px;
            line-height: 1;
            transform: translateY(-50%);
            transition: background-color 150ms ease, color 150ms ease;
        }

        .area-detail-close:hover {
            background: #dff1eb;
            color: #0f766e;
        }

        .area-detail-content {
            padding: 0 28px 20px;
            color: #334155;
            overflow-y: auto;
        }

        .area-detail-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            padding: 18px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .area-detail-row {
            padding: 14px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .area-detail-label {
            display: block;
            margin-bottom: 5px;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .area-detail-value {
            min-width: 0;
            color: #172033;
            font-size: 14px;
            font-weight: 400;
            overflow-wrap: anywhere;
        }

        .area-detail-status {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            min-height: 24px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
        }

        .area-detail-status.is-active {
            border: 1px solid #a7e1bf;
            background: #dcfce7;
            color: #166534;
        }

        .area-detail-status.is-inactive {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .area-detail-section {
            padding: 16px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .area-detail-section-title {
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
        }

        .area-detail-description {
            color: #172033;
            font-size: 14px;
            font-weight: 400;
            white-space: pre-line;
            line-height: 1.6;
        }

        .area-detail-positions {
            display: grid;
            gap: 0;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .area-detail-positions li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-top: 1px solid #f1f5f9;
            color: #172033;
            font-size: 14px;
            font-weight: 400;
        }

        .area-detail-positions li:first-child {
            border-top: 0;
        }

        .area-detail-positions li i {
            width: 20px;
            flex: 0 0 20px;
            color: #15803d;
            text-align: center;
        }

        .area-detail-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 20px;
        }

        @media (max-width: 540px) {
            .area-detail-header {
                padding-left: 18px;
            }

            .area-detail-heading {
                gap: 10px;
            }

            .area-detail-heading-icon {
                width: 32px;
                height: 32px;
                flex-basis: 32px;
                font-size: 20px;
            }

            .area-detail-subtitle {
                font-size: 12px;
            }

            .area-detail-content {
                padding-right: 20px;
                padding-left: 20px;
            }

            .area-detail-summary {
                grid-template-columns: 1fr;
                gap: 14px;
            }
        }
    </style>

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
    <div class="tabla-compacta tabla-areas tabla-areas-wrap">
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

    {{-- Muestra la información completa del área sin cargarla en la tabla. --}}
    <div id="modalVerArea" class="seg-modal hidden">
        <div class="seg-modal-box area-detail-modal">
            <div class="area-detail-header">
                <div class="area-detail-heading">
                    <span class="area-detail-heading-icon" aria-hidden="true">
                        <i class="fa-solid fa-building"></i>
                    </span>
                    <div>
                        <h2 class="area-detail-title">Detalle del área</h2>
                        <p class="area-detail-subtitle">Información institucional y cargos relacionados</p>
                    </div>
                </div>
                <button type="button" class="area-detail-close" onclick="cerrarModalVerArea()"
                    aria-label="Cerrar detalle del área" title="Cerrar">
                    &times;
                </button>
            </div>

            <div class="area-detail-content">
                <div class="area-detail-summary">
                    <div>
                        <span class="area-detail-label">ID</span>
                        <div id="ver_area_id" class="area-detail-value">-</div>
                    </div>
                    <div>
                        <span class="area-detail-label">Estado</span>
                        <div>
                            <span id="ver_area_estado" class="area-detail-status">-</span>
                        </div>
                    </div>
                </div>

                <div class="area-detail-row">
                    <span class="area-detail-label">Área</span>
                    <div id="ver_area_nombre" class="area-detail-value">-</div>
                </div>

                <div class="area-detail-row">
                    <span class="area-detail-label">Área superior</span>
                    <div id="ver_area_padre" class="area-detail-value">-</div>
                </div>

                <div class="area-detail-section">
                    <div class="area-detail-section-title">Descripción</div>
                    <div id="ver_area_descripcion" class="area-detail-description">-</div>
                </div>

                <div class="area-detail-section">
                    <div class="area-detail-section-title">
                        Cargos asignados (<span id="ver_area_cargos_total">0</span>)
                    </div>
                    <ul id="ver_area_cargos" class="area-detail-positions"></ul>
                </div>

                <div class="area-detail-actions">
                    <x-wire-button type="button" secondary onclick="cerrarModalVerArea()">
                        Cerrar
                    </x-wire-button>
                </div>
            </div>
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

            // Muestra todos los datos del area seleccionada en una ventana aparte.
            function abrirModalVerArea(boton) {
                document.getElementById('ver_area_id').textContent = boton.dataset.id || '-';
                document.getElementById('ver_area_nombre').textContent = boton.dataset.nombre || '-';
                document.getElementById('ver_area_padre').textContent = boton.dataset.areaPadre || '-';
                document.getElementById('ver_area_descripcion').textContent = boton.dataset.descripcion || 'Sin descripcion';

                const estado = boton.dataset.estado || '-';
                const chipEstado = document.getElementById('ver_area_estado');
                chipEstado.textContent = estado;
                chipEstado.classList.toggle('is-active', estado === 'Activo');
                chipEstado.classList.toggle('is-inactive', estado === 'Inactivo');

                const cargos = (boton.dataset.cargos || '').split('|').filter(Boolean);
                const listaCargos = document.getElementById('ver_area_cargos');
                document.getElementById('ver_area_cargos_total').textContent = cargos.length;
                listaCargos.replaceChildren();

                const cargosParaMostrar = cargos.length ? cargos : ['Sin cargos asignados'];
                cargosParaMostrar.forEach((cargo) => {
                    const item = document.createElement('li');
                    const icono = document.createElement('i');
                    const texto = document.createElement('span');

                    icono.className = 'fa-solid fa-briefcase';
                    icono.setAttribute('aria-hidden', 'true');
                    texto.textContent = cargo;

                    item.append(icono, texto);
                    listaCargos.appendChild(item);
                });

                const modal = document.getElementById('modalVerArea');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            // Cierra el detalle sin salir del listado de areas.
            function cerrarModalVerArea() {
                const modal = document.getElementById('modalVerArea');
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
                const botonVer = e.target.closest('[data-ver-area]');

                if (botonCrear) {
                    abrirModalCrearArea();
                    return;
                }

                if (botonVer) {
                    abrirModalVerArea(botonVer);
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

            // Confirma el cambio antes de enviar la solicitud.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form-area');

                if (!formulario) return;

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar area',
                    text: 'El área se marcará como Inactiva y dejará de aparecer en el listado si no está relacionada con otros datos.',
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
