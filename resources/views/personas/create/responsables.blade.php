{{-- RESPONSABLES DE LA EMPRESA --}}
<div id="seccion_responsables" class="hidden">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- HEADER --}}
        <div class="bg-gradient-to-r from-teal-50 to-cyan-50 border-b border-teal-100 px-5 py-3">

            <div class="flex items-center justify-between">

                <div class="flex items-center gap-3">

                    {{-- ICONO RESPONSABLE --}}
                    <div class="w-9 h-9 rounded-lg bg-teal-500 flex items-center justify-center text-white shadow">

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A9 9 0 1118.879 17.8M15 11a3 3 0 11-6 0 3 3 0 016 0z" />

                        </svg>

                    </div>

                    <div>

                        <h2 class="text-base font-bold text-teal-700">
                            Responsables de la Empresa
                        </h2>

                        <p class="text-xs text-gray-500">
                            Personas asignadas como responsables de la empresa.
                        </p>

                    </div>

                </div>

                <button type="button" onclick="abrirModalResponsable()"
                    class="px-4 py-2 rounded-lg bg-teal-500 text-white text-sm hover:bg-teal-600">

                    Agregar Responsable

                </button>

            </div>

        </div>

        {{-- BODY --}}
        <div class="p-6 space-y-5">

            <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">

                <h3 class="text-sm font-semibold text-gray-700">
                    Responsables agregados
                </h3>

                <div class="responsables-review-table mt-3">
                    <div id="listaResponsablesEmpresa" class="responsables-review-body">

                    <span id="mensajeSinResponsables" class="responsables-review-empty">

                        Todavía no se agregaron responsables.

                    </span>

                    </div>
                </div>

            </div>

        </div>

    </div>
</div>


{{-- MODAL RESPONSABLES DE LA EMPRESA --}}
<div id="modalNuevoResponsable" class="hidden fixed inset-0 bg-black/40 z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-6xl overflow-hidden">
        {{-- HEADER --}}
        <div
            class="bg-gradient-to-r from-violet-50 to-purple-50 border-b border-violet-100 px-6 py-4 flex justify-between items-center">
            <div>
                <h2 id="tituloModalResponsable" class="text-lg font-bold text-gray-700">
                    Registrar responsable
                </h2>

                <p class="text-xs text-gray-500">
                    Registro y asignación de responsables relacionados a la empresa.
                </p>
            </div>

            <button type="button" onclick="cerrarModalResponsable()" class="text-red-500 text-2xl font-bold">
                ×
            </button>

        </div>

        {{-- BODY --}}
        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto" data-modal-body-responsable>
            {{-- SECCIÓN 1 --}}
            <div class="rounded-xl border border-slate-200 overflow-visible">

                <div class="bg-slate-100 px-4 py-2 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-700">
                        1. Persona registrada
                    </h3>
                </div>

                <div class="p-4">

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Seleccionar persona existente
                    </label>

                    <select id="modal_id_persona_responsable" onchange="cargarPersonaResponsable()" class="w-full">

                        <option value="">
                            Nueva persona / No registrada
                        </option>
                        @foreach ($personas as $persona)
                            <option value="{{ $persona->id }}" data-domicilio="{{ $persona->domicilio }}"
                                data-nit="{{ $persona->nit }}" data-correo="{{ $persona->correo }}"
                                data-territorio="{{ $persona->territorio?->id ?? '' }}"
                                data-nombres="{{ $persona->natural?->nombres ?? '' }}"
                                data-paterno="{{ $persona->natural?->apellido_paterno ?? '' }}"
                                data-materno="{{ $persona->natural?->apellido_materno ?? '' }}"
                                data-casado="{{ $persona->natural?->apellido_casado ?? '' }}"
                                data-ci="{{ $persona->natural?->ci ?? '' }}"
                                data-complemento="{{ $persona->natural?->complemento ?? '' }}"
                                data-expedido="{{ $persona->natural?->expedido ?? '' }}"
                                data-fecha="{{ $persona->natural?->fecha_nacimiento ?? '' }}"
                                data-genero="{{ $persona->natural?->genero ?? '' }}"
                                data-ocupacion="{{ $persona->natural?->ocupacion ?? '' }}"
                                data-telefonos='@json($persona->telefonos)'
                                data-rubros='@json($persona->rubros)'>

                                {{ $persona->natural?->ci ?? 'Sin CI' }} -
                                {{ $persona->natural?->nombres ?? '' }}
                                {{ $persona->natural?->apellido_paterno ?? '' }}
                                {{ $persona->natural?->apellido_materno ?? '' }}

                            </option>
                        @endforeach

                    </select>

                    <input type="hidden" id="nuevo_id_persona_existente">

                    <p class="text-xs text-gray-500 mt-2">
                        Busque y seleccione una persona registrada para autocompletar los datos.
                    </p>

                </div>

            </div>

            {{-- SECCIÓN 2 --}}
            <div class="rounded-xl border border-blue-200 overflow-hidden">

                <div class="bg-blue-50 px-4 py-2 border-b border-blue-100">
                    <h3 class="text-sm font-bold text-blue-700">
                        2. Información general del responsable
                    </h3>
                </div>

                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                    <x-wire-input label="Domicilio del responsable" id="nuevo_domicilio" placeholder="Dirección o domicilio" />

                    <x-wire-input label="NIT del responsable" id="nuevo_nit" placeholder="NIT si corresponde" />

                    <x-wire-input label="Correo del responsable" id="nuevo_correo" type="email"
                        placeholder="correo@ejemplo.com" />

                    <x-wire-native-select label="Territorio del responsable" id="nuevo_id_territorio">

                        <option value="">
                            Seleccione territorio
                        </option>

                        @foreach ($territorios as $elemento)
                            <option value="{{ $elemento->id }}">
                                {{ $elemento->nombre }}
                            </option>
                        @endforeach

                    </x-wire-native-select>

                </div>

            </div>

            {{-- SECCIÓN 3 --}}
            <div class="rounded-xl border border-violet-200 overflow-hidden">

                <div class="bg-violet-50 px-4 py-2 border-b border-violet-100">
                    <h3 class="text-sm font-bold text-violet-700">
                        3. Datos personales del responsable
                    </h3>
                </div>

                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                    <x-wire-input label="Nombres" id="nuevo_nombres" placeholder="Ingrese nombres" />

                    <x-wire-input label="Apellido paterno" id="nuevo_apellido_paterno" placeholder="Apellido paterno" />

                    <x-wire-input label="Apellido materno" id="nuevo_apellido_materno" placeholder="Apellido materno" />

                    <x-wire-input label="Apellido de casado" id="nuevo_apellido_casado"
                        placeholder="Apellido de casado si corresponde" />

                    <x-wire-input label="CI" id="nuevo_ci" placeholder="Carnet de identidad" />

                    <x-wire-input label="Complemento" id="nuevo_complemento" placeholder="Complemento" />

                    <x-wire-input label="Expedido" id="nuevo_expedido" placeholder="LP, CB, SC..." />

                    <x-wire-datetime-picker label="Fecha de nacimiento" id="nuevo_fecha_nacimiento"
                        name="nuevo_fecha_nacimiento" without-time />

                    <x-wire-native-select label="Género" id="nuevo_genero">
                        <option value="">Seleccione una opción</option>
                        <option value="1">Masculino</option>
                        <option value="0">Femenino</option>
                    </x-wire-native-select>

                    <x-wire-input label="Ocupación" id="nuevo_ocupacion" placeholder="Ocupación" />

                </div>

            </div>
            {{-- SECCIÓN 4 --}}
            <div class="rounded-xl border border-emerald-200 overflow-hidden">

                <div class="bg-emerald-50 px-4 py-2 border-b border-emerald-100">
                    <h3 class="text-sm font-bold text-emerald-700">
                        4. Teléfonos del responsable
                    </h3>

                    <p id="textoModoTelefonosResponsable" class="text-xs text-gray-500 mt-1">
                        Si registra una persona nueva, puede agregar uno o varios teléfonos.
                    </p>
                </div>

                <div class="p-4 space-y-4">

                    <div id="formTelefonosResponsable" class="grid grid-cols-1 md:grid-cols-12 gap-4">

                        <div class="md:col-span-6">
                            <x-wire-input label="Teléfono" id="nuevo_telefono" placeholder="Ejemplo: 70123456" />
                        </div>

                        <div class="md:col-span-4">
                            <x-wire-native-select label="Tipo" id="nuevo_tipo_telefono">
                                <option value="CELULAR">Celular</option>
                                <option value="FIJO">Fijo</option>
                                <option value="REFERENCIA">Referencia</option>
                            </x-wire-native-select>
                        </div>

                        <div class="md:col-span-2 flex items-end">
                            <button type="button" onclick="agregarTelefonoResponsableModal()"
                                class="w-full px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">
                                Agregar
                            </button>
                        </div>

                    </div>

                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                        <h4 class="text-sm font-semibold text-gray-700">
                            Teléfonos registrados
                        </h4>

                        <div id="listaTelefonosResponsableModal" class="flex flex-wrap gap-2 mt-3">
                            <span id="mensajeSinTelefonosResponsableModal" class="text-sm text-gray-500">
                                Todavía no se agregaron teléfonos.
                            </span>
                        </div>
                    </div>

                </div>
            </div>


            {{-- SECCIÓN 5 --}}
            <div class="rounded-xl border border-sky-200 overflow-hidden">

                <div class="bg-sky-50 px-4 py-2 border-b border-sky-100">
                    <h3 class="text-sm font-bold text-sky-700">
                        5. Rubros o actividad económica
                    </h3>
                    <p id="textoModoRubrosResponsable" class="text-xs text-gray-500 mt-1">
                        Si registra una persona nueva, puede agregar uno o varios rubros.
                    </p>
                </div>

                <div class="p-4 space-y-4">

                    <div id="formRubrosResponsable" class="grid grid-cols-1 md:grid-cols-12 gap-4">

                        <div class="md:col-span-8">
                            <x-wire-input label="Nombre del rubro" id="nuevo_rubro"
                                placeholder="Ej: Control de plagas" />
                        </div>

                        <div class="md:col-span-2">
                            <x-wire-native-select label="Estado" id="nuevo_estado_rubro">
                                {{-- Estado textual para guardar rubros del responsable como ACTIVO / INACTIVO. --}}
                                <option value="ACTIVO">Activo</option>
                                <option value="INACTIVO">Inactivo</option>
                            </x-wire-native-select>
                        </div>

                        <div class="md:col-span-2 flex items-end">
                            <button type="button" onclick="agregarRubroResponsableModal()"
                                class="w-full px-4 py-2 rounded-lg bg-sky-600 text-white text-sm hover:bg-sky-700">
                                Agregar
                            </button>
                        </div>

                    </div>

                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                        <h4 class="text-sm font-semibold text-gray-700">
                            Rubros registrados
                        </h4>

                        <div id="listaRubrosResponsableModal" class="flex flex-wrap gap-2 mt-3">
                            <span id="mensajeSinRubrosResponsableModal" class="text-sm text-gray-500">
                                Todavía no se agregaron rubros.
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- SECCIÓN 6 --}}
            <div class="rounded-xl border border-lime-200 overflow-visible">

                <div class="bg-lime-50 px-4 py-2 border-b border-lime-100">
                    <h3 class="text-sm font-bold text-lime-700">
                        6. Datos del responsable
                    </h3>
                </div>

                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- Rol del responsable: se guarda como id_rol en la tabla responsables. --}}
                    <x-wire-native-select label="Rol o cargo" id="nuevo_id_rol">
                        <option value="">Seleccione rol</option>
                        @foreach ($rolesResponsablesCatalogo as $rolResponsable)
                            <option value="{{ $rolResponsable->id }}">
                                {{ $rolResponsable->name }}
                            </option>
                        @endforeach
                    </x-wire-native-select>

                    <div data-error-wrapper="nuevo_url_respaldo">
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="nuevo_url_respaldo">
                            Respaldo en formato PDF
                        </label>

                        <input type="file" id="nuevo_url_respaldo" accept=".pdf,application/pdf" class="sr-only">

                        {{-- Control compacto del PDF: evita que el input nativo ocupe demasiado espacio. --}}
                        <div id="responsableModalPdfControl" class="responsable-modal-pdf">
                            <div class="responsable-modal-pdf-info">
                                <i class="fa-solid fa-file-pdf"></i>
                                <div>
                                    <strong id="responsableModalPdfNombre">Sin PDF seleccionado</strong>
                                    <span id="responsableModalPdfEstado">Seleccione un respaldo PDF si corresponde.</span>
                                </div>
                            </div>

                            <div class="responsable-modal-pdf-actions">
                                <label for="nuevo_url_respaldo" class="responsable-modal-pdf-button is-select">
                                    <i class="fa-solid fa-upload"></i>
                                    <span>Seleccionar</span>
                                </label>

                                <button type="button" id="btnVerRespaldoResponsableModal"
                                    class="responsable-modal-pdf-button is-view" disabled>
                                    <i class="fa-solid fa-eye"></i>
                                    <span>Ver</span>
                                </button>

                                <button type="button" id="btnQuitarRespaldoResponsableModal"
                                    class="responsable-modal-pdf-button is-remove" disabled>
                                    <i class="fa-solid fa-xmark"></i>
                                    <span>Quitar</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <x-wire-datetime-picker label="Fecha de registro" id="nuevo_fecha_registro"
                        name="nuevo_fecha_registro" without-time />

                    <x-wire-datetime-picker label="Fecha de baja" id="nuevo_fecha_baja" name="nuevo_fecha_baja"
                        without-time />

                    <x-wire-native-select label="Estado" id="nuevo_estado_responsable">

                        <option value="ACTIVO">
                            Activo
                        </option>

                        <option value="INACTIVO">
                            Inactivo
                        </option>

                    </x-wire-native-select>

                </div>

            </div>



        </div>

        {{-- FOOTER --}}
        <div class="px-6 py-4 border-t flex justify-end gap-3 bg-gray-50">

            <button type="button" onclick="cerrarModalResponsable()"
                class="px-4 py-2 rounded-lg border text-gray-600 bg-white">

                Cancelar

            </button>

            <button id="btnGuardarResponsableModal" type="button" onclick="agregarNuevoResponsableTemporal()"
                class="px-4 py-2 rounded-lg bg-teal-600 text-white hover:bg-teal-700 transition">
                Agregar Responsable
            </button>

        </div>

    </div>
</div>
