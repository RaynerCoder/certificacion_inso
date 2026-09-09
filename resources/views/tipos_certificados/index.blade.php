<x-admin-layout title="Tipos de Certificado | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Certificados',
        'href' => '',
    ],
    [
        'name' => 'Tipos de Certificado',
        'href' => route('tipos_certificados_index'),
    ],
]">
    <style>
        .tipos-certificados-tabla {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-inline: contain;
            -webkit-overflow-scrolling: touch;
        }

        .tipos-certificados-tabla table {
            width: 100%;
            min-width: 900px;
            table-layout: fixed;
        }

        .tipos-certificados-tabla th,
        .tipos-certificados-tabla td {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
            vertical-align: middle;
            white-space: normal !important;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .tipos-certificados-tabla th:first-child,
        .tipos-certificados-tabla td:first-child {
            width: 8%;
            min-width: 60px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-certificados-tabla th:nth-child(2),
        .tipos-certificados-tabla td:nth-child(2) {
            width: 44%;
            min-width: 320px;
        }

        .tipos-certificados-tabla th:nth-child(3),
        .tipos-certificados-tabla td:nth-child(3) {
            width: 14%;
            min-width: 110px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-certificados-tabla th:last-child,
        .tipos-certificados-tabla td:last-child {
            width: 34%;
            min-width: 330px;
            text-align: center;
            white-space: nowrap !important;
        }

        .tipos-certificados-tabla th:first-child button,
        .tipos-certificados-tabla th:nth-child(3) button {
            width: 100%;
            justify-content: center;
            text-align: center;
        }

        .tipos-certificados-tabla td:nth-child(3) .tabla-chip {
            margin-right: auto;
            margin-left: auto;
        }

        .tipos-certificados-acciones {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }

    </style>

    @permiso('tipos_certificados.gestionar')
        <x-slot name="action">
            <x-wire-button type="button" blue onclick="abrirModalNuevoTipoCertificado()">
                Nuevo tipo
            </x-wire-button>
        </x-slot>
    @endpermiso



    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/TipoCertificadoTable.php --}}
    <div class="tipos-certificados-tabla">
        @livewire('datatables.tipo-certificado-table')
    </div>

    @permiso('tipos_certificados.gestionar')
        {{-- El mismo formulario sirve para crear o editar únicamente nombre y estado. --}}
        <div id="modalDatosBasicosTipoCertificado"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 px-4"
            aria-hidden="true">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog"
                aria-modal="true" aria-labelledby="tituloModalTipoCertificado">
                <div class="flex items-center justify-between border-b border-teal-100 bg-teal-50 px-5 py-4">
                    <div>
                        <h2 id="tituloModalTipoCertificado" class="text-lg font-bold text-slate-800">
                            Nuevo tipo de certificado
                        </h2>
                        <p id="descripcionModalTipoCertificado" class="mt-0.5 text-sm text-slate-500">
                            Registre el nombre y su estado inicial.
                        </p>
                    </div>
                    <button type="button" onclick="cerrarModalTipoCertificado()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-500 transition hover:bg-slate-50"
                        aria-label="Cerrar modal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="formDatosBasicosTipoCertificado" action="{{ route('tipos_certificados_store') }}"
                    method="POST">
                    @csrf
                    <input id="metodoModalTipoCertificado" type="hidden" name="_method" value="PATCH" disabled>
                    <input id="idModalTipoCertificado" type="hidden" name="form_id_tipo_certificado"
                        value="{{ old('form_id_tipo_certificado') }}">

                    <div class="space-y-4 px-5 py-5">
                        <div>
                            <label for="nombreModalTipoCertificado"
                                class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Nombre del tipo de certificado
                            </label>
                            <input id="nombreModalTipoCertificado" name="form_nombre" type="text" maxlength="255"
                                value="{{ old('form_nombre') }}" placeholder="Ej.: Certificado de registro"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-800 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
                            @error('form_nombre')
                                <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="estadoModalTipoCertificado"
                                class="mb-1.5 block text-sm font-semibold text-slate-700">Estado</label>
                            <select id="estadoModalTipoCertificado" name="form_estado"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600">
                                <option value="ACTIVO" @selected(old('form_estado') === 'ACTIVO')>Activo</option>
                                <option value="INACTIVO" @selected(old('form_estado', 'INACTIVO') === 'INACTIVO')>Inactivo</option>
                            </select>
                            @error('form_estado')
                                <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
                        <button type="button" onclick="cerrarModalTipoCertificado()"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                            <i class="fa-solid fa-floppy-disk text-xs"></i>
                            <span id="textoGuardarTipoCertificado">Crear tipo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endpermiso

    @permiso('tipos_certificados.gestionar')
        @push('js')
            <script>
            const urlCrearTipoCertificado = @json(route('tipos_certificados_store'));
            const urlEditarTipoCertificado = @json(route('tipos_certificados_update_basico', ['tipoCertificado' => '__ID__']));

            // Prepara el modal para crear un registro sin mezclar su configuración técnica.
            function abrirModalNuevoTipoCertificado() {
                prepararModalTipoCertificado({
                    titulo: 'Nuevo tipo de certificado',
                    descripcion: 'Registre el nombre y su estado inicial.',
                    boton: 'Crear tipo',
                    accion: urlCrearTipoCertificado,
                    id: '',
                    nombre: '',
                    estado: 'INACTIVO',
                    editar: false,
                });
            }

            // Lee la fila seleccionada y abre el mismo modal en modo edición.
            function abrirModalEdicionTipoCertificado(boton) {
                prepararModalTipoCertificado({
                    titulo: 'Editar datos básicos',
                    descripcion: 'Modifique solamente el nombre o el estado.',
                    boton: 'Guardar cambios',
                    accion: boton.dataset.url,
                    id: boton.dataset.id,
                    nombre: boton.dataset.nombre,
                    estado: boton.dataset.estado,
                    editar: true,
                });
            }

            function prepararModalTipoCertificado(datos) {
                const modal = document.getElementById('modalDatosBasicosTipoCertificado');
                const formulario = document.getElementById('formDatosBasicosTipoCertificado');
                const metodo = document.getElementById('metodoModalTipoCertificado');

                if (!modal || !formulario || !metodo) return;

                document.getElementById('tituloModalTipoCertificado').textContent = datos.titulo;
                document.getElementById('descripcionModalTipoCertificado').textContent = datos.descripcion;
                document.getElementById('textoGuardarTipoCertificado').textContent = datos.boton;
                document.getElementById('idModalTipoCertificado').value = datos.id;
                document.getElementById('nombreModalTipoCertificado').value = datos.nombre;
                document.getElementById('estadoModalTipoCertificado').value = datos.estado || 'INACTIVO';

                formulario.action = datos.accion;
                metodo.disabled = !datos.editar;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                document.getElementById('nombreModalTipoCertificado').focus();
            }

            function cerrarModalTipoCertificado() {
                const modal = document.getElementById('modalDatosBasicosTipoCertificado');
                if (!modal) return;

                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') cerrarModalTipoCertificado();
            });

            document.addEventListener('DOMContentLoaded', () => {
                const idConError = @json(old('form_id_tipo_certificado'));
                const debeReabrir = @json($errors->has('form_nombre') || $errors->has('form_estado'));
                const idSolicitado = new URLSearchParams(window.location.search).get('editar');

                if (debeReabrir && idConError) {
                    prepararModalTipoCertificado({
                        titulo: 'Editar datos básicos',
                        descripcion: 'Revise los campos marcados antes de guardar.',
                        boton: 'Guardar cambios',
                        accion: urlEditarTipoCertificado.replace('__ID__', idConError),
                        id: idConError,
                        nombre: @json(old('form_nombre')),
                        estado: @json(old('form_estado', 'INACTIVO')),
                        editar: true,
                    });
                    return;
                }

                if (debeReabrir) {
                    abrirModalNuevoTipoCertificado();
                    document.getElementById('nombreModalTipoCertificado').value = @json(old('form_nombre'));
                    document.getElementById('estadoModalTipoCertificado').value = @json(old('form_estado', 'INACTIVO'));
                    return;
                }

                if (idSolicitado) {
                    const botonEditar = document.querySelector(`[data-id="${CSS.escape(idSolicitado)}"][data-url]`);
                    if (botonEditar) abrirModalEdicionTipoCertificado(botonEditar);
                }
            });
            </script>
        @endpush
    @endpermiso

</x-admin-layout>
