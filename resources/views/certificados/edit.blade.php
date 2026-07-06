<x-admin-layout title="Certificados | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Certificados',
        'href' => route('certificados_index'),
    ],
    [
        'name' => 'Editar',
        'href' => route('certificados_edit', $certificado->id),
    ],
]">

    @php
        // Requisitos agrupados por tipo de certificado; JavaScript los usa para cargar la tabla al elegir un tipo.
        $requisitosPorTipoCertificadoJson = $requisitosPorTipoCertificado ?? collect();

        // Personas listas para WireUI select: permite buscar y seleccionar en un solo campo.
        $personasSelect = collect($personas)
            ->map(
                fn($persona) => [
                    'id' => $persona['id'],
                    'nombre' => trim(($persona['nombre'] ?? '') . ' - ' . ($persona['detalle'] ?? '')),
                ],
            )
            ->values()
            ->toArray();
        // Mantiene las personas elegidas cuando Laravel devuelve el formulario por errores de validacion.
        $beneficiarioSeleccionado = old('form_id_persona_beneficiario', $certificado->id_persona_beneficiario ?? null);
        $tramitadorSeleccionado = old('form_id_persona_tramitador', $certificado->id_persona_tramitador ?? null);

        // Iconos SVG locales: evitan que los botones queden vacios si FontAwesome no carga.
        $icon = function (string $nombre, string $class = 'cert-svg') {
            $paths = [
                'back' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h11" />',
                'document' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h8l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3v5h5M8 13h8M8 17h6" />',
                'user' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21a8 8 0 0 0-16 0" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />',
                'user-check' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21a6 6 0 0 0-12 0" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM17 11l2 2 4-4" />',
                'search' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35" /><circle cx="11" cy="11" r="7" stroke-width="2" />',
                'refresh' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8 8 0 0 0-14.9-4M4 7V3h4M4 13a8 8 0 0 0 14.9 4M20 17v4h-4" />',
                'link' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L11 4.93" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 11a5 5 0 0 0-7.07 0L4.8 13.12a5 5 0 1 0 7.07 7.07L13 19.07" />',
                'check' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-5" /><circle cx="12" cy="12" r="9" stroke-width="2" />',
                'alert' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 4.3 2.8 17.2A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.8L13.7 4.3a2 2 0 0 0-3.4 0z" />',
                'users' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21a5 5 0 0 0-10 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 21a4 4 0 0 0-5-3.87M16 4.13a4 4 0 0 1 0 7.75" />',
                'chart' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v9h9" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 1 1-9-9" />',
                'save' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3h12l2 2v16H5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3v6h8V3M8 21v-7h8v7" />',
                'plus' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" />',
                'broom' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 4l5 5-9 9-5-5 9-9zM4 20l2-7 5 5-7 2z" />',
            ];

            return '<svg xmlns="http://www.w3.org/2000/svg" class="' .
                $class .
                '" fill="none" viewBox="0 0 24 24" stroke="currentColor">' .
                ($paths[$nombre] ?? $paths['document']) .
                '</svg>';
        };
    @endphp

    @include('certificados.estilos')    

    {{-- Formulario principal: guarda certificados y sus requisitos al enviar. --}}
    <form action="{{ route('certificados_update', $certificado) }}" method="POST" id="formRegistrarCertificado"
        enctype="multipart/form-data" autocomplete="off" data-tiene-errores="{{ $errors->any() ? '1' : '0' }}">
        @csrf
        @method('PUT')

        <div class="space-y-5">
            {{-- Encabezado principal: mismo formato usado el Tipo de Certificado. --}}
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="relative px-6 py-6">
                    {{-- Linea superior verde usada como acento principal en los formularios del sistema. --}}
                    <div class="absolute inset-x-0 top-0 h-1 bg-emerald-600"></div>
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                                Editar Certificado
                            </h1>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                                Actualice los datos del certificado y revise el cumplimiento de sus requisitos.
                            </p>
                        </div>

                        {{-- Regresa al listado sin enviar el formulario. --}}
                        <a href="{{ route('certificados_index') }}"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 sm:w-auto">
                            {!! $icon('back', 'cert-svg-sm') !!}
                            <span>Volver al listado</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Muestra errores generales sin cambiar la distribucion del formulario. --}}
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-bold">Revisa los campos marcados antes de guardar.</p>
                </div>
            @endif

            <div class="cert-page-grid">
                {{-- Columna izquierda: datos principales y requisitos. --}}
                <main class="space-y-5">
                    {{-- 1. Datos que se guardan en certificados. --}}
                    <section class="cert-card">
                        {{-- Encabezado replicado del CRUD de tipos de certificado. --}}
                        <div
                            class="flex items-start gap-3 border-b border-blue-100 bg-gradient-to-r from-blue-50 to-sky-50 px-4 py-3 sm:px-5">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white shadow">
                                <span class="text-sm font-bold">1</span>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-base font-bold text-blue-700">Datos del certificado</h2>
                                <p class="text-xs text-gray-500">Registra el tipo, personas relacionadas, fechas y
                                    documento.</p>
                            </div>
                        </div>

                        <div class="space-y-8 p-5 sm:p-6">
                            {{-- Primera fila: datos cortos para que el formulario no se vea apretado. --}}
                            <div class="cert-grid-twelve">
                                <div class="cert-span-4">
                                    {{-- Select nativo: se mantiene asi porque al cambiar carga los requisitos del tipo elegido. --}}
                                    <x-wire-native-select label="Tipo de certificado" id="id_tipo_certificado"
                                        name="form_id_tipo_certificado" required>
                                        <option value="">Seleccione un tipo</option>
                                        @foreach ($tiposCertificados as $tipoCertificado)
                                            <option value="{{ $tipoCertificado->id }}" @selected(old('form_id_tipo_certificado', $certificado->id_tipo_certificado ?? '') == $tipoCertificado->id)>
                                                {{ $tipoCertificado->nombre }}
                                            </option>
                                        @endforeach
                                    </x-wire-native-select>
                                </div>

                                <div class="cert-span-4">
                                    {{-- El codigo queda como dato editable; no se genera desde la vista. --}}
                                    <x-wire-input label="Codigo del certificado" id="codigo" name="form_codigo"
                                        placeholder="CERT-IMP-2026-000123"
                                        value="{{ old('form_codigo', $certificado->codigo ?? '') }}" />
                                </div>

                                <div class="cert-span-4">
                                    <x-wire-native-select label="Estado" id="estado" name="form_estado" required>
                                        <option value="">
                                            Seleccione un estado
                                        </option>
                                        @foreach (\App\Models\Certificado::ESTADOS_CERTIFICADO as $valor => $texto)
                                            <option value="{{ $valor }}" @selected(old('form_estado', $certificado->estado ?? 'EN_REVISION') === $valor)>
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </x-wire-native-select>
                                </div>
                            </div>

                            {{-- Segunda fila: personas ocupan mas espacio porque sus nombres suelen ser largos. --}}
                            <div class="cert-grid-twelve">
                                <div class="cert-span-6">
                                    {{-- Tom Select: select con flecha y buscador, enviando el id real del beneficiario. --}}
                                    <label for="id_persona_beneficiario" class="cert-native-label">
                                        Persona beneficiaria <span class="text-red-500">*</span>
                                    </label>
                                    <select id="id_persona_beneficiario" name="form_id_persona_beneficiario"
                                        class="cert-persona-select" data-placeholder="Buscar beneficiario..." required>
                                        <option value="">Seleccione beneficiario</option>
                                        @foreach ($personasSelect as $persona)
                                            <option value="{{ $persona['id'] }}" @selected((string) $beneficiarioSeleccionado === (string) $persona['id'])>
                                                {{ $persona['nombre'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form_id_persona_beneficiario')
                                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="cert-span-6">
                                    {{-- Tom Select: permite escribir para filtrar y elegir otro tramitador desde la flecha. --}}
                                    <label for="id_persona_tramitador" class="cert-native-label">
                                        Persona tramitadora <span class="text-red-500">*</span>
                                    </label>
                                    <select id="id_persona_tramitador" name="form_id_persona_tramitador"
                                        class="cert-persona-select" data-placeholder="Buscar tramitador..." required>
                                        <option value="">Seleccione tramitador</option>
                                        @foreach ($personasSelect as $persona)
                                            <option value="{{ $persona['id'] }}" @selected((string) $tramitadorSeleccionado === (string) $persona['id'])>
                                                {{ $persona['nombre'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form_id_persona_tramitador')
                                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Tercera fila: fechas y documento final del certificado. --}}
                            <div class="cert-grid-twelve">
                                <div class="cert-span-3">
                                    <x-wire-input label="Fecha de vigencia inicial" id="fecha_inicio"
                                        name="form_fecha_inicio" type="date"
                                        value="{{ old('form_fecha_inicio', isset($certificado->fecha_inicio) ? \Illuminate\Support\Carbon::parse($certificado->fecha_inicio)->format('Y-m-d') : '') }}" />
                                </div>

                                <div class="cert-span-3">
                                    <x-wire-input label="Fecha de vigencia final" id="fecha_fin" name="form_fecha_fin"
                                        type="date"
                                        value="{{ old('form_fecha_fin', isset($certificado->fecha_fin) ? \Illuminate\Support\Carbon::parse($certificado->fecha_fin)->format('Y-m-d') : '') }}" />
                                </div>

                                <div class="cert-span-6">
                                    {{-- Se mantiene el nombre form_url_documento para que el backend pueda guardar la ruta del PDF en url_documento. --}}
                                    <x-wire-input type="file" label="Documento PDF" id="documento_pdf"
                                        name="form_url_documento" accept="application/pdf,.pdf" />

                                    {{-- Muestra el PDF actual; si no se sube otro archivo se conserva esta ruta. --}}
                                    @if ($certificado->url_documento)
                                        @php
                                            $documentoActual = \Illuminate\Support\Str::startsWith($certificado->url_documento, ['http://', 'https://'])
                                                ? $certificado->url_documento
                                                : asset('storage/' . $certificado->url_documento);
                                        @endphp
                                        <a href="{{ $documentoActual }}" target="_blank"
                                            class="mt-2 inline-flex text-xs font-bold text-emerald-700 underline">
                                            Ver documento actual
                                        </a>
                                    @endif

                                    {{-- <p class="mt-1 text-xs text-slate-500">
                                        Seleccione un archivo PDF. Al guardar, el sistema debe almacenar la ruta del
                                        archivo.
                                    </p> --}}

                                    {{-- Boton compacto: evita que la vista previa del PDF ocupe espacio dentro del formulario. --}}
                                    <div
                                        class="mt-3 flex flex-col gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <p id="pdfFileName" class="text-xs font-semibold text-slate-500">
                                            Sin archivo seleccionado.
                                        </p>
                                        <button type="button" id="btnVerPdf" onclick="abrirModalPdf()"
                                            class="cert-pdf-button hidden">
                                            {!! $icon('document', 'cert-svg-sm') !!}
                                            <span>Ver PDF</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Cuarta fila: descripcion completa en una fila propia para mejorar lectura. --}}
                            <div class="cert-grid-twelve">
                                <div class="cert-span-12">
                                    <x-wire-textarea label="Descripción" id="descripcion" name="form_descripcion"
                                        rows="4"
                                        placeholder="Ingrese una descripción u observación general del certificado...">{{ old('form_descripcion', $certificado->descripcion ?? '') }}</x-wire-textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- 2. Requisitos que se guardan en requisitos_certificados. --}}
                    <section class="cert-card">
                        {{-- Encabezado replicado del CRUD de tipos de certificado. --}}
                        <div
                            class="flex items-start gap-3 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-cyan-50 px-4 py-3 sm:px-5">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-500 text-white shadow">
                                <span class="text-sm font-bold">2</span>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-teal-700">Requisitos necesarios</h2>
                                <p class="text-xs text-gray-500">Se cargan desde el tipo de certificado seleccionado.
                                </p>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5">
                            <div class="overflow-x-auto rounded-lg border border-slate-200">
                                {{-- Cada fila representa un registro futuro en requisitos_certificados. --}}
                                <table class="w-full min-w-[640px] table-fixed divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-xs font-bold text-sky-950">
                                        <tr>
                                            <th class="w-14 px-3 py-3 text-center">#</th>
                                            <th class="min-w-[260px] px-3 py-3 text-left">Requisito</th>
                                            <th class="w-32 px-3 py-3 text-center">Cumple</th>
                                            <th class="min-w-[240px] px-3 py-3 text-left">Observación</th>
                                            <th class="w-32 px-3 py-3 text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRequisitosCertificado" class="divide-y divide-slate-100 bg-white">
                                        <tr>
                                            <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                                                Seleccione un tipo de certificado para cargar sus requisitos.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Nota de uso: aclara al usuario de donde vienen y como se validan los requisitos. --}}
                            {{-- <div
                                class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                <p class="font-semibold">Nota.</p>
                                <p class="mt-1 text-xs leading-relaxed">
                                    Los requisitos se cargan automaticamente segun el tipo de certificado seleccionado.
                                </p>
                            </div> --}}

                            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:justify-end sm:p-6">
                                {{-- Reinicia solo la vista del formulario actual. --}}
                                <button type="reset" onclick="restaurarFormularioEdicionCertificado()"
                                    class="cert-ghost-btn px-4">
                                    {!! $icon('broom', 'cert-svg-sm') !!}
                                    <span>Restaurar cambios</span>
                                </button>

                                {{-- Actualiza el certificado principal y sus requisitos evaluados. --}}
                                <button type="submit" name="accion" value="guardar" class="cert-action-btn px-5">
                                    {!! $icon('save', 'cert-svg-sm') !!}
                                    <span>Actualizar certificado</span>
                                </button>
                            </div>
                        </div>
                    </section>
                </main>

                {{-- Guia lateral: no lleva numeracion porque no es un paso del registro. --}}
                <aside class="space-y-4 xl:sticky xl:top-4">
                    <section class="cert-card border-teal-700">
                        <div
                            class="border-b border-amber-100 bg-gradient-to-r from-amber-50 to-orange-50 px-4 py-3 sm:px-5">
                            <h2 class="text-base font-bold text-amber-700">Resumen del certificado registrado</h2>
                            <p class="text-xs text-gray-500">Guia para revisar y guardar el certificado.</p>
                        </div>

                        <div class="space-y-4 p-5">
                            <div class="flex gap-3 border-b border-slate-200 pb-4">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                    {!! $icon('document', 'cert-svg-sm') !!}
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">Tipo de certificado</p>
                                    <p id="resumenTipo" class="mt-1 text-sm font-bold text-slate-950">Sin seleccionar
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-3 border-b border-slate-200 pb-4">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                    {!! $icon('users', 'cert-svg-sm') !!}
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">Beneficiario</p>
                                    <p id="resumenBeneficiario" class="mt-1 text-sm font-bold text-slate-950">Sin
                                        seleccionar</p>
                                </div>
                            </div>

                            <div class="flex gap-3 border-b border-slate-200 pb-4">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                    {!! $icon('user-check', 'cert-svg-sm') !!}
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-slate-500">Tramitador</p>
                                    <p id="resumenTramitador" class="mt-1 text-sm font-bold text-slate-950">Sin
                                        seleccionar</p>
                                </div>
                            </div>

                            <div class="flex gap-3 border-b border-slate-200 pb-4">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-cyan-700">
                                    {!! $icon('chart', 'cert-svg-sm') !!}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-500">Cumplimiento de requisitos</p>
                                    <p class="mt-1 text-sm font-bold text-teal-800">
                                        <span id="contadorCumplidos">0</span>/<span id="contadorTotal">0</span>
                                        requisitos cumplidos
                                    </p>
                                    <div class="mt-2 flex items-center justify-between gap-3">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div id="barraCumplimiento"
                                                class="h-full w-0 rounded-full bg-teal-600 transition-all"></div>
                                        </div>
                                        <span id="porcentajeCumplido"
                                            class="text-sm font-bold text-slate-700">0%</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </form>

    {{-- Modal de vista previa: muestra el PDF solo cuando el usuario lo solicita. --}}
    <div id="modalPdfPreview" class="cert-pdf-modal" aria-hidden="true">
        <div class="cert-pdf-dialog" role="dialog" aria-modal="true" aria-labelledby="tituloModalPdf">
            <div class="cert-pdf-modal-head">
                <div class="min-w-0">
                    <h2 id="tituloModalPdf" class="text-base font-bold text-slate-800">Vista previa del PDF</h2>
                    <p id="pdfModalFileName" class="mt-1 truncate text-xs text-slate-500">Sin archivo seleccionado.
                    </p>
                </div>

                {{-- Cierra el modal sin modificar el archivo seleccionado. --}}
                <button type="button" onclick="cerrarModalPdf()" class="cert-modal-close">
                    Cerrar
                </button>
            </div>

            <div id="pdfPreviewEmpty" class="px-4 py-8 text-center text-sm text-slate-500">
                Seleccione un PDF para previsualizarlo aqui.
            </div>
            <iframe id="pdfPreviewFrame" class="cert-pdf-frame hidden" title="Vista previa del PDF cargado"></iframe>
        </div>
    </div>

    @push('js')
        {{-- Tom Select: se usa aqui para tener select con flecha y buscador en beneficiario/tramitador. --}}
        <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

        <script>
            // Requisitos agrupados por tipo de certificado; vienen del controlador.
            const requisitosPorTipoCertificado = @json($requisitosPorTipoCertificadoJson);
            const requisitosOldCertificado = @json(old('requisitos_certificados', []));
            const personasSelectPorId = @json(collect($personasSelect)->pluck('nombre', 'id'));
            const beneficiarioSeleccionadoServidor = @json((string) ($beneficiarioSeleccionado ?? ''));
            const tramitadorSeleccionadoServidor = @json((string) ($tramitadorSeleccionado ?? ''));

            const tipoSelect = document.getElementById('id_tipo_certificado');
            const beneficiarioSelect = document.getElementById('id_persona_beneficiario');
            const tramitadorSelect = document.getElementById('id_persona_tramitador');
            const documentoPdfInput = document.getElementById('documento_pdf');
            const tbodyRequisitos = document.getElementById('tablaRequisitosCertificado');
            const formCertificado = document.getElementById('formRegistrarCertificado');
            const tieneErroresServidorCertificado = formCertificado?.dataset.tieneErrores === '1';
            let pdfPreviewUrl = null;

            // Obtiene el texto visible del option seleccionado para mostrarlo en el resumen.
            function textoSeleccionado(select, catalogo = null) {
                if (catalogo && select?.value) {
                    return catalogo[select.value] || 'Sin seleccionar';
                }

                return select?.selectedOptions?.[0]?.text?.trim() || 'Sin seleccionar';
            }

            // Actualiza texto solo si el elemento existe; ayuda cuando una seccion visual cambia de lugar.
            function asignarTexto(id, texto) {
                const elemento = document.getElementById(id);

                if (elemento) {
                    elemento.textContent = texto;
                }
            }

            // Restaura el id elegido cuando la validacion falla en otro campo y Laravel regresa al formulario.
            function restaurarSelectWireUiCertificado(select, valor) {
                if (!select) {
                    return;
                }

                if (select.tomselect) {
                    select.tomselect.setValue(valor || '', true);
                } else {
                    select.value = valor || '';
                }

                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Activa Tom Select para buscar escribiendo, abrir con flecha y mantener envio normal del select.
            function inicializarBuscadoresPersonasCertificado() {
                if (!window.TomSelect) {
                    return;
                }

                document.querySelectorAll('.cert-persona-select').forEach((select) => {
                    if (select.tomselect) {
                        return;
                    }

                    new TomSelect(select, {
                        create: false,
                        allowEmptyOption: true,
                        maxItems: 1,
                        placeholder: select.dataset.placeholder || 'Buscar...',
                        sortField: {
                            field: 'text',
                            direction: 'asc',
                        },
                        onChange: () => {
                            select.dispatchEvent(new Event('input', { bubbles: true }));
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        },
                    });
                });
            }

            // Icono local para filas dinamicas; evita depender de librerias externas.
            function iconoDocumentoRequisito() {
                return `
                    <svg xmlns="http://www.w3.org/2000/svg" class="cert-svg-sm mt-0.5 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h8l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3v5h5M8 13h8M8 17h6" />
                    </svg>
                `;
            }

            // Carga la tabla requisitos_certificados segun el tipo elegido.
            function cargarRequisitosDelTipo() {
                const tipoId = tipoSelect.value;
                const requisitos = (requisitosPorTipoCertificado[tipoId] || []).map((requisito) => {
                    // Si el formulario vuelve por validacion, se respeta lo que el usuario marco.
                    const anterior = Object.values(requisitosOldCertificado).find((item) => {
                        return String(item.id_requisito) === String(requisito.id_requisito);
                    });

                    return anterior ? {
                        ...requisito,
                        cumple: anterior.cumple || '',
                        estado: anterior.estado || requisito.estado,
                    } : requisito;
                });

                tbodyRequisitos.innerHTML = '';

                if (requisitos.length === 0) {
                    tbodyRequisitos.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                                Este tipo de certificado no tiene requisitos activos configurados.
                            </td>
                        </tr>
                    `;
                    actualizarResumenCertificado();
                    return;
                }

                requisitos.forEach((requisito, index) => {
                    const cumple = requisito.cumple === 'SI';
                    const textoCumple = cumple ? 'Si' : 'Pendiente';
                    const estadoRequisito = cumple ? 'ACTIVO' : (requisito.estado || 'PENDIENTE_REVISION');
                    const claseEstado = cumple ?
                        'estado-requisito-badge rounded-md bg-green-100 px-2 py-1 text-xs font-bold text-green-700' :
                        'estado-requisito-badge rounded-md bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700';

                    const fila = document.createElement('tr');
                    fila.className = 'hover:bg-slate-50';
                    fila.innerHTML = `
                        <td class="px-3 py-3 text-center font-semibold text-slate-600">${index + 1}</td>
                        <td class="px-3 py-3">
                            <div class="flex min-w-0 items-start gap-2">
                                ${iconoDocumentoRequisito()}
                                <p class="break-words font-semibold text-slate-800">${escaparHtml(requisito.descripcion)}</p>
                            </div>
                            <input type="hidden" name="requisitos_certificados[${index}][id_requisito]" value="${requisito.id_requisito}">
                        </td>
                        <td class="px-3 py-3 text-center">
                            <label class="cert-switch">
                                <input type="hidden" name="requisitos_certificados[${index}][cumple]" value="">
                                <input type="checkbox"
                                    name="requisitos_certificados[${index}][cumple]"
                                    value="SI"
                                    class="requisito-cumple"
                                    ${cumple ? 'checked' : ''}
                                    onchange="actualizarFilaRequisito(this)">
                                <span class="cert-switch-track"></span>
                                <span class="texto-cumple text-sm font-semibold text-slate-700">${textoCumple}</span>
                            </label>
                        </td>
                        <td class="px-3 py-3">
                            <input type="text"
                                name="requisitos_certificados[${index}][observacion]"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-teal-600 focus:ring-teal-600"
                                placeholder="Escriba una observación (opcional)">
                        </td>
                        <td class="px-3 py-3 text-center">
                            <input class="estado-requisito-input" type="hidden" name="requisitos_certificados[${index}][estado]" value="${estadoRequisito}">
                            <span class="${claseEstado}">${estadoRequisito}</span>
                        </td>
                    `;

                    tbodyRequisitos.appendChild(fila);
                });

                actualizarResumenCertificado();
            }

            // Cambia la etiqueta y estado visual de una fila segun cumple o no cumple.
            function actualizarFilaRequisito(checkbox) {
                const fila = checkbox.closest('tr');
                const textoCumple = fila.querySelector('.texto-cumple');
                const estadoInput = fila.querySelector('.estado-requisito-input');
                const estadoBadge = fila.querySelector('.estado-requisito-badge');

                textoCumple.textContent = checkbox.checked ? 'Si' : 'Pendiente';
                estadoInput.value = checkbox.checked ? 'ACTIVO' : 'PENDIENTE_REVISION';
                estadoBadge.textContent = checkbox.checked ? 'ACTIVO' : 'PENDIENTE_REVISION';
                estadoBadge.className = checkbox.checked ?
                    'estado-requisito-badge rounded-md bg-green-100 px-2 py-1 text-xs font-bold text-green-700' :
                    'estado-requisito-badge rounded-md bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700';

                actualizarResumenCertificado();
            }

            // Evita que textos de base de datos rompan el HTML dinamico.
            function escaparHtml(valor) {
                return String(valor ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Cierra el modal del PDF; se usa al limpiar, cerrar o cambiar archivo.
            function cerrarModalPdf() {
                const modal = document.getElementById('modalPdfPreview');

                modal?.classList.remove('is-open');
                modal?.setAttribute('aria-hidden', 'true');
            }

            // Abre la vista previa solo si ya existe un PDF valido seleccionado.
            function abrirModalPdf() {
                if (!pdfPreviewUrl) {
                    return;
                }

                const modal = document.getElementById('modalPdfPreview');

                modal?.classList.add('is-open');
                modal?.setAttribute('aria-hidden', 'false');
            }

            // Prepara una vista previa local del PDF, pero la muestra solo dentro del modal.
            function actualizarVistaPreviaPdf() {
                const archivo = documentoPdfInput?.files?.[0] ?? null;
                const frame = document.getElementById('pdfPreviewFrame');
                const vacio = document.getElementById('pdfPreviewEmpty');
                const nombre = document.getElementById('pdfFileName');
                const nombreModal = document.getElementById('pdfModalFileName');
                const botonVerPdf = document.getElementById('btnVerPdf');

                cerrarModalPdf();

                if (pdfPreviewUrl) {
                    URL.revokeObjectURL(pdfPreviewUrl);
                    pdfPreviewUrl = null;
                }

                if (!archivo) {
                    frame?.classList.add('hidden');
                    if (frame) {
                        frame.removeAttribute('src');
                    }
                    vacio?.classList.remove('hidden');
                    asignarTexto('pdfFileName', 'Sin archivo seleccionado.');
                    asignarTexto('pdfModalFileName', 'Sin archivo seleccionado.');
                    botonVerPdf?.classList.add('hidden');
                    return;
                }

                if (archivo.type !== 'application/pdf') {
                    documentoPdfInput.value = '';
                    frame?.classList.add('hidden');
                    if (frame) {
                        frame.removeAttribute('src');
                    }
                    vacio?.classList.remove('hidden');
                    asignarTexto('pdfFileName', 'Solo se permite seleccionar archivos PDF.');
                    asignarTexto('pdfModalFileName', 'Solo se permite seleccionar archivos PDF.');
                    botonVerPdf?.classList.add('hidden');
                    return;
                }

                pdfPreviewUrl = URL.createObjectURL(archivo);
                if (frame) {
                    frame.src = pdfPreviewUrl;
                    frame.classList.remove('hidden');
                }
                vacio?.classList.add('hidden');
                if (nombre) {
                    nombre.textContent = archivo.name;
                }
                if (nombreModal) {
                    nombreModal.textContent = archivo.name;
                }
                botonVerPdf?.classList.remove('hidden');
            }

            // Actualiza contadores, barra de progreso y resumen lateral.
            function actualizarResumenCertificado() {
                const checks = Array.from(document.querySelectorAll('.requisito-cumple'));
                const total = checks.length;
                const cumplidos = checks.filter((check) => check.checked).length;
                const porcentaje = total === 0 ? 0 : Math.round((cumplidos / total) * 100);
                const observado = total > 0 && cumplidos < total;

                asignarTexto('contadorCumplidos', cumplidos);
                asignarTexto('contadorTotal', total);
                asignarTexto('porcentajeCumplido', `${porcentaje}%`);
                document.getElementById('barraCumplimiento').style.width = `${porcentaje}%`;
                asignarTexto('resumenTipo', textoSeleccionado(tipoSelect));
                asignarTexto('resumenBeneficiario', textoSeleccionado(beneficiarioSelect, personasSelectPorId));
                asignarTexto('resumenTramitador', textoSeleccionado(tramitadorSelect, personasSelectPorId));

                const estadoSugerido = document.getElementById('resumenEstadoSugerido');

                if (estadoSugerido) {
                    // El resumen usa la misma regla del backend: si falta algun requisito queda observado; si cumple todo queda aprobado.
                    estadoSugerido.textContent = observado ? 'OBSERVADO' : 'APROBADO';
                    estadoSugerido.className = observado ?
                        'mt-2 inline-flex rounded-md bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700' :
                        'mt-2 inline-flex rounded-md bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700';
                }
            }

            // Limpia datos visuales del resumen cuando el usuario reinicia el formulario.
            function reiniciarResumenCertificado() {
                setTimeout(() => {
                    tbodyRequisitos.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                                Seleccione un tipo de certificado para cargar sus requisitos.
                            </td>
                        </tr>
                    `;
                    actualizarResumenCertificado();
                    actualizarVistaPreviaPdf();
                }, 0);
            }

            // Restaura la tabla de requisitos en edicion sin borrar los datos guardados del certificado.
            function restaurarFormularioEdicionCertificado() {
                setTimeout(() => {
                    if (tipoSelect?.value) {
                        cargarRequisitosDelTipo();
                    } else {
                        reiniciarResumenCertificado();
                    }

                    actualizarVistaPreviaPdf();
                }, 0);
            }

            // Limpia el create cuando se entra desde Registrar; si hay errores se conserva lo escrito.
            function limpiarRegistroNuevoCertificado() {
                if (tieneErroresServidorCertificado || !formCertificado) {
                    return;
                }

                formCertificado.reset();

                if (tipoSelect) tipoSelect.value = '';
                restaurarSelectWireUiCertificado(beneficiarioSelect, '');
                restaurarSelectWireUiCertificado(tramitadorSelect, '');
                if (document.getElementById('codigo')) document.getElementById('codigo').value = '';
                if (document.getElementById('fecha_inicio')) document.getElementById('fecha_inicio').value = '';
                if (document.getElementById('fecha_fin')) document.getElementById('fecha_fin').value = '';
                if (document.getElementById('estado')) document.getElementById('estado').value = 'EN_REVISION';
                if (document.getElementById('descripcion')) document.getElementById('descripcion').value = '';

                tbodyRequisitos.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                            Seleccione un tipo de certificado para cargar sus requisitos.
                        </td>
                    </tr>
                `;

                actualizarVistaPreviaPdf();
                actualizarResumenCertificado();
            }

            tipoSelect?.addEventListener('change', cargarRequisitosDelTipo);
            beneficiarioSelect?.addEventListener('change', actualizarResumenCertificado);
            beneficiarioSelect?.addEventListener('input', actualizarResumenCertificado);
            tramitadorSelect?.addEventListener('change', actualizarResumenCertificado);
            tramitadorSelect?.addEventListener('input', actualizarResumenCertificado);
            documentoPdfInput?.addEventListener('change', actualizarVistaPreviaPdf);

            // Permite cerrar la vista previa haciendo clic fuera del contenido del modal.
            document.getElementById('modalPdfPreview')?.addEventListener('click', function(event) {
                if (event.target === this) {
                    cerrarModalPdf();
                }
            });

            // Cierre rapido con Escape para que el modal no estorbe el llenado del formulario.
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    cerrarModalPdf();
                }
            });

            // En edicion se cargan los datos guardados; no se limpia el formulario.
            inicializarBuscadoresPersonasCertificado();
            restaurarSelectWireUiCertificado(beneficiarioSelect, beneficiarioSeleccionadoServidor);
            restaurarSelectWireUiCertificado(tramitadorSelect, tramitadorSeleccionadoServidor);

            if (tipoSelect?.value) {
                cargarRequisitosDelTipo();
            } else {
                actualizarResumenCertificado();
            }
        </script>
    @endpush

</x-admin-layout>
