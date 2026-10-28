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
    [
        'name' => 'Configurar',
    ],
]">

    @php
        // Catálogos necesarios para modificar requisitos y evidencias sin tocar los datos básicos.
        $requisitosDisponibles = $requisitos ?? collect();
        $tiposEvidenciasDisponibles = $tiposEvidencias ?? collect();
        $tiposCertificadosDisponibles = $tiposCertificadosRequeridos ?? collect();
        $areasDisponibles = $areas ?? collect();
        $funcionariosReceptoresDisponibles = $funcionariosReceptores ?? collect();
        $idFuncionarioReceptorSeleccionado = (string) old(
            'form_id_funcionario_receptor_inicial',
            $tipoCertificado->id_funcionario_receptor_inicial ?? ''
        );
        $datosVigencia = $tipoCertificado->datosVigencia();
        $cantidadVigencia = old('form_vigencia_cantidad', $datosVigencia['cantidad'] ?? '');
        $unidadVigencia = old('form_vigencia_unidad', $datosVigencia['unidad'] ?? '');

        $tiposEvidenciasJson = $tiposEvidenciasDisponibles
            ->map(function ($tipo) {
                return [
                    'id' => (string) $tipo->id,
                    'codigo' => $tipo->codigo,
                    'nombre' => $tipo->nombre,
                ];
            })
            ->values();

        $tiposCertificadosJson = $tiposCertificadosDisponibles
            ->map(function ($tipoCertificado) {
                return [
                    'id' => (string) $tipoCertificado->id,
                    'nombre' => $tipoCertificado->nombre,
                    'requisitos' => $tipoCertificado->tipoCertificadoRequisitos
                        ->where('estado', 'ACTIVO')
                        ->map(function ($asignacion) {
                            $dependencia = $asignacion->dependenciasRequisitos->first();

                            return [
                                'descripcion' => $asignacion->requisito?->descripcion ?? '',
                                'tipo_evidencia' => $asignacion->tipoEvidencia?->nombre ?? '',
                                'certificado_requerido' => $dependencia?->tipoCertificadoRequerido?->nombre ?? '',
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        // Usa old() si hubo error de validacion; si no, usa lo que viene de la base de datos.
        $requisitosBase = old('requisitos_asignados') ?? ($requisitosAsignados ?? []);

        $requisitosAsignadosAnteriores = collect($requisitosBase)
            ->map(function ($item) {
                return [
                    'id' => filled($item['id_requisito'] ?? null)
                        ? (string) $item['id_requisito']
                        : (string) ($item['id'] ?? 'nuevo_' . uniqid()),
                    'descripcion' => $item['descripcion'] ?? '',
                    'id_tipo_evidencia' => filled($item['id_tipo_evidencia'] ?? null)
                        ? (string) $item['id_tipo_evidencia']
                        : '',
                    'calificacion_minima_aprobacion' => $item['calificacion_minima_aprobacion'] ?? '',
                    'id_tipo_certificado_requerido' => filled($item['id_tipo_certificado_requerido'] ?? null)
                        ? (string) $item['id_tipo_certificado_requerido']
                        : '',
                    'nombre_certificado_requerido' => $item['nombre_certificado_requerido'] ?? '',
                    'estado' => $item['estado'] ?? 'ACTIVO',
                    'nuevo' => (bool) ($item['nuevo'] ?? false),
                    'es_certificado_previo' =>
                        (bool) ($item['es_certificado_previo'] ??
                            filled($item['id_tipo_certificado_requerido'] ?? null)),
                ];
            })
            ->values();
    @endphp

    <div class="space-y-5">
        {{-- El encabezado identifica el registro, pero el nombre y estado no se editan aquí. --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-5">
                <div class="absolute inset-x-0 top-0 h-1 bg-emerald-600"></div>
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                                {{ $tipoCertificado->nombre }}
                            </h1>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $tipoCertificado->estado === 'ACTIVO' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $tipoCertificado->estado }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Configure el área, los requisitos y las evidencias que solicitará el sistema.
                        </p>
                    </div>

                    <a href="{{ route('tipos_certificados_index') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 sm:w-auto">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        <span>Volver al listado</span>
                    </a>
                </div>
            </div>
        </div>

        <form action="{{ route('tipos_certificados_configuracion_update', $tipoCertificado) }}" method="POST"
            id="formTipoCertificadoRequisitos">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                {{-- 1. Datos operativos. El nombre y estado se administran desde el modal del listado. --}}
                <section class="overflow-hidden rounded-2xl border border-teal-100 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-3 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-white px-5 py-3">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-sm font-bold text-white">1</span>
                        <h2 class="text-base font-bold text-teal-800">Configuración general</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-5 px-5 py-4 lg:grid-cols-12">
                        <div class="lg:col-span-4">
                            <x-wire-native-select label="Área desde donde se iniciará el trámite" id="form_id_area"
                                name="form_id_area">
                                <option value="">Seleccione el área</option>
                                @foreach ($areasDisponibles as $area)
                                    <option value="{{ $area->id }}" @selected(old('form_id_area', $tipoCertificado->id_area) == $area->id)>
                                        {{ $area->nombre }}
                                    </option>
                                @endforeach
                            </x-wire-native-select>
                        </div>

                        <div class="lg:col-span-4">
                            <label for="form_id_funcionario_receptor_inicial_boton"
                                class="mb-1 block text-sm font-medium text-slate-700">
                                Funcionario que recepcionará el trámite
                            </label>
                            <div id="selectorFuncionarioReceptor" class="relative">
                                <input type="hidden" id="form_id_funcionario_receptor_inicial"
                                    name="form_id_funcionario_receptor_inicial"
                                    value="{{ $idFuncionarioReceptorSeleccionado }}">

                                <button type="button" id="form_id_funcionario_receptor_inicial_boton"
                                    class="flex min-h-[42px] w-full items-center justify-between gap-3 rounded-md border border-slate-300 bg-white px-3 py-2 text-left shadow-sm transition hover:border-teal-500 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600"
                                    aria-haspopup="listbox" aria-expanded="false"
                                    onclick="alternarSelectorFuncionarioReceptor()">
                                    <span id="funcionarioReceptorSeleccionado" class="min-w-0 flex-1"></span>
                                    <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                                </button>

                                <div id="opcionesFuncionarioReceptor"
                                    class="absolute z-30 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
                                    role="listbox"></div>
                            </div>
                            <p id="ayudaFuncionarioReceptor" class="mt-1 text-xs text-slate-500"></p>
                            @error('form_id_funcionario_receptor_inicial')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <x-wire-native-select label="Forma de emisión" id="form_modo_emision"
                                name="form_modo_emision">
                                @foreach (\App\Models\TipoCertificado::MODOS_EMISION as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected(old('form_modo_emision', $tipoCertificado->modo_emision ?? 'UNICO_POR_TRAMITE') === $valor)>
                                        {{ $etiqueta }}
                                    </option>
                                @endforeach
                            </x-wire-native-select>
                            <p class="mt-1 text-xs text-slate-500">
                                Use emisión por lote únicamente cuando cada lote de la solicitud requiera su propio certificado.
                            </p>
                        </div>

                        <div class="lg:col-span-4">
                            <label for="form_vigencia_cantidad" class="mb-1 block text-sm font-medium text-slate-700">
                                Vigencia
                            </label>
                            <div class="flex gap-2">
                                <input id="form_vigencia_cantidad" name="form_vigencia_cantidad" type="number"
                                    min="1" max="9999" value="{{ $cantidadVigencia }}" placeholder="Ej.: 4"
                                    class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                                <select id="form_vigencia_unidad" name="form_vigencia_unidad"
                                    class="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                                    <option value="">Seleccione</option>
                                    @foreach (\App\Models\TipoCertificado::UNIDADES_VIGENCIA as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected($unidadVigencia === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Ej.: 30 días, 6 meses, 4 años o indefinida.</p>
                            @error('form_vigencia_cantidad')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                            @error('form_vigencia_unidad')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <x-wire-input label="Costo (Bs.)" id="form_costo" name="form_costo" type="text"
                                inputmode="decimal" maxlength="11" placeholder="Ej.: 250,50"
                                value="{{ old('form_costo', $tipoCertificado->costo) }}" />
                            <p class="mt-1 text-xs text-slate-500">Déjelo vacío si el costo aún no está definido. Acepta hasta dos decimales.</p>
                            @error('form_costo')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="lg:col-span-4">
                            <x-wire-input label="Descargas por usuario" id="limite_descargas"
                                name="form_limite_descargas" type="number" min="0" max="65535"
                                placeholder="Sin límite"
                                value="{{ old('form_limite_descargas', $tipoCertificado->limite_descargas) }}" />
                            <p class="mt-1 text-xs text-slate-500">Déjelo vacío para permitir descargas ilimitadas. Use 0 para bloquearlas.</p>
                        </div>

                        <div class="lg:col-span-8">
                            <x-wire-input label="Prefijo del número de certificado" id="form_prefijo_numero_certificado"
                                name="form_prefijo_numero_certificado" maxlength="100" placeholder="Ej.: CAP-07-2026-"
                                value="{{ old('form_prefijo_numero_certificado', $tipoCertificado->prefijo_numero_certificado) }}" />
                            <p class="mt-1 text-xs text-slate-500">Se conserva tal cual; el sistema añade el correlativo al final.</p>
                        </div>

                        <div class="lg:col-span-4">
                            <x-wire-input label="Siguiente correlativo" id="form_siguiente_correlativo"
                                name="form_siguiente_correlativo" type="number" min="1" max="999999999"
                                value="{{ old('form_siguiente_correlativo', $tipoCertificado->siguiente_correlativo ?? 1) }}" />
                            <p class="mt-1 text-xs text-slate-500">Se incrementa automáticamente al emitir.</p>
                        </div>

                    </div>
                </section>

                {{-- 2. Buscadores separados: requisitos generales y certificados previos. --}}
                <section class="overflow-hidden rounded-2xl border border-teal-100 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-3 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-white px-5 py-3">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-sm font-bold text-white">2</span>
                            <h2 class="text-base font-bold text-teal-800">Seleccionar requisitos</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 px-5 py-4 xl:grid-cols-2">
                        {{-- Requisitos generales: se guardan en requisitos_tipos_certificados. --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-sm font-bold text-slate-800">Requisitos generales</h3>
                                <button type="button" onclick="abrirModalRequisito()"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-teal-300 bg-white px-3 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-50">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                    <span>Nuevo requisito</span>
                                </button>
                            </div>

                            <label for="buscarRequisitoExistente"
                                class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Buscar requisito
                            </label>
                            <div class="relative">
                                <input type="text" id="buscarRequisitoExistente"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-10 text-sm text-slate-700 focus:border-teal-600 focus:ring-teal-600"
                                    placeholder="Nombre del requisito...">
                                <i
                                    class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            </div>

                            <div id="listaRequisitosExistentes"
                                class="mt-4 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200">
                                @forelse ($requisitosDisponibles as $requisito)
                                    <label
                                        class="requisito-existente flex cursor-pointer items-center justify-between gap-3 bg-white px-4 py-3 text-sm transition hover:bg-teal-50"
                                        data-descripcion="{{ \Illuminate\Support\Str::lower($requisito->descripcion) }}">
                                        <span class="flex min-w-0 items-center gap-3">
                                            <input type="checkbox"
                                                class="rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                                value="{{ $requisito->id }}"
                                                data-requisito-descripcion="{{ $requisito->descripcion }}">
                                            <span
                                                class="whitespace-pre-line break-words font-medium text-slate-700">{{ $requisito->descripcion }}</span>
                                        </span>
                                        <span
                                            class="shrink-0 rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">
                                            {{ $requisito->estado }}
                                        </span>
                                    </label>
                                @empty
                                    <p class="px-4 py-5 text-center text-sm text-slate-500">Aun no existen requisitos
                                        activos.</p>
                                @endforelse
                            </div>

                            <div class="mt-4 flex justify-end">
                                <button type="button" onclick="agregarRequisitosSeleccionados()"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                                    <span>Agregar requisitos</span>
                                </button>
                            </div>
                        </div>

                        {{-- Certificados previos: se guardan como dependencias en dependencias_requisitos. --}}
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/30 p-4">
                            <div class="mb-3">
                                <div class="mb-3">
                                    <h3 class="text-sm font-bold text-emerald-800">
                                        Certificados existentes registrados
                                    </h3>
                                    <p class="mt-1 text-xs text-emerald-700">
                                        Selecciona uno o varios certificados que serán considerados como requisitos para
                                        este nuevo certificado.
                                    </p>
                                </div>
                            </div>

                            <label for="buscarCertificadoPrevio"
                                class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Buscar certificado registrado
                            </label>
                            <div class="relative">
                                <input type="text" id="buscarCertificadoPrevio"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-700 focus:border-teal-600 focus:ring-teal-600"
                                    placeholder="Nombre del certificado...">
                                <i
                                    class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            </div>

                            <div id="listaCertificadosPrevios"
                                class="mt-4 max-h-72 divide-y divide-emerald-100 overflow-y-auto rounded-lg border border-emerald-100 bg-white">
                                @forelse ($tiposCertificadosDisponibles as $tipoCertificadoRequerido)
                                    <label
                                        class="certificado-previo flex cursor-pointer items-center justify-between gap-3 bg-white px-4 py-3 text-sm transition hover:bg-emerald-50"
                                        data-nombre="{{ \Illuminate\Support\Str::lower($tipoCertificadoRequerido->nombre) }}">
                                        <span class="flex min-w-0 items-center gap-3">
                                            <input type="checkbox"
                                                class="rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                                value="{{ $tipoCertificadoRequerido->id }}"
                                                data-certificado-nombre="{{ $tipoCertificadoRequerido->nombre }}">
                                            <span
                                                class="break-words font-medium text-slate-700">{{ $tipoCertificadoRequerido->nombre }}</span>
                                        </span>
                                        <span
                                            class="shrink-0 rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">
                                            {{ $tipoCertificadoRequerido->estado }}
                                        </span>
                                    </label>
                                @empty
                                    <p class="px-4 py-5 text-center text-sm text-slate-500">Aun no existen certificados
                                        activos.</p>
                                @endforelse
                            </div>

                            <div class="mt-4 flex justify-end">
                                <button type="button" onclick="agregarCertificadosPreviosSeleccionados()"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                    <span>Agregar certificados</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 3. Configuracion final de requisitos, evidencias y certificados previos. --}}
                @include('tipos_certificados.configuracion_qr')

                <section class="overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-3 border-b border-amber-100 bg-gradient-to-r from-amber-50 to-white px-5 py-3">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500 text-sm font-bold text-white">3</span>
                        <h2 class="text-base font-bold text-amber-800">Configurar la evidencia del requisito</h2>
                    </div>

                    <div class="p-5">
                        {{-- La posicion de cada fila se envia al controlador y se conserva como orden del requisito. --}}
                        <div id="inputsOcultosRequisitos"></div>

                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full min-w-[980px] table-fixed divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                                    <tr>
                                        <th class="w-16 px-2 py-3 text-left">#</th>
                                        <th class="w-[30%] px-3 py-3 text-left">Requisito</th>
                                        <th class="w-[22%] px-3 py-3 text-left">Evidencia que debe presentar</th>
                                        <th class="w-[24%] px-3 py-3 text-left">Requisitos previos</th>
                                        <th class="w-28 px-3 py-3 text-center">Estado</th>
                                        <th class="w-40 px-3 py-3 text-center">Accion</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaRequisitosAsignados" class="divide-y divide-slate-100 bg-white">
                                    <tr id="filaSinRequisitos">
                                        <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">
                                            Todavia no agregaste requisitos a este tipo de certificado.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @error('requisitos_asignados')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        {{-- <div
                            class="mt-3 flex items-start gap-2 rounded-lg border border-teal-100 bg-teal-50 px-3 py-2 text-sm text-teal-800">
                            <i class="fa-solid fa-circle-info mt-0.5 text-teal-600"></i>
                            <p>Si la evidencia es un certificado vigente, se selecciona que certificado previo debe
                                tener el solicitante.</p>
                        </div> --}}

                        <div class="mt-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <p class="text-sm font-semibold text-slate-600">
                                <span id="contadorRequisitosAsignados">0</span> requisitos asignados
                            </p>

                            <div class="flex flex-col justify-end gap-3 sm:flex-row">
                                <button type="button" onclick="limpiarRequisitosAsignados()"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    <i class="fa-solid fa-eraser text-xs"></i>
                                    <span>Limpiar</span>
                                </button>

                                <a href="{{ route('tipos_certificados_index') }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                                    <span>Salir sin guardar</span>
                                </a>

                                <button type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                                    <span>Guardar cambios</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    </div>

    {{-- Modal sencillo para crear un requisito nuevo sin mostrar otra seccion en pantalla. --}}
    <div id="modalRequisitoRapido" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 px-4">
        <div class="w-full max-w-xl rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Nuevo requisito</h3>
                    <p class="text-sm text-slate-500">Se agregara a la tabla de configuracion.</p>
                </div>
                <button type="button" onclick="cerrarModalRequisito()"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="space-y-4 px-5 py-4">
                <div>
                    <label for="nuevo_requisito_descripcion"
                        class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Descripcion del requisito
                    </label>
                    <textarea id="nuevo_requisito_descripcion" rows="6"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-teal-600 focus:ring-teal-600"
                        placeholder="Ejemplo:&#10;- Hoja de seguridad actualizada.&#10;- Certificado original firmado."></textarea>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Para mostrar una lista, escriba cada elemento en una línea nueva y comience con un guion (-).
                    </p>
                    <p id="errorNuevoRequisito" class="mt-1 hidden text-sm text-red-600">Ingrese la descripcion del
                        requisito.</p>
                </div>

                <div>
                    <label for="nuevo_requisito_estado"
                        class="mb-1.5 block text-sm font-semibold text-slate-700">Estado</label>
                    <select id="nuevo_requisito_estado"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-teal-600 focus:ring-teal-600">
                        <option value="ACTIVO">Activo</option>
                        <option value="INACTIVO">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" onclick="cerrarModalRequisito()"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="button" onclick="crearRequisitoRapido()"
                    class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                    Guardar requisito
                </button>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            // Datos temporales que se convierten en inputs hidden antes de enviar el formulario.
            let requisitosAsignados = @json($requisitosAsignadosAnteriores);
            let indiceRequisitoNuevo = requisitosAsignados.length;
            let mostrarErroresEvidencia = @json($errors->has('requisitos_asignados.*.id_tipo_evidencia'));

            document.getElementById('formTipoCertificadoRequisitos').addEventListener('submit', function (event) {
                if (!requisitosAsignados.some((requisito) => !String(requisito.id_tipo_evidencia || '').trim())) return;

                event.preventDefault();
                event.stopImmediatePropagation();
                mostrarErroresEvidencia = true;
                renderizarTablaRequisitos();
                const selector = document.querySelector('[data-evidencia-faltante="true"]');
                selector?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                selector?.focus({ preventScroll: true });
            }, true);

            function actualizarCantidadVigencia() {
                const cantidad = document.getElementById('form_vigencia_cantidad');
                const unidad = document.getElementById('form_vigencia_unidad');

                if (!cantidad || !unidad) return;

                const esIndefinida = unidad.value === 'INDEFINIDA';
                cantidad.disabled = esIndefinida;
                cantidad.required = !esIndefinida;
                cantidad.placeholder = esIndefinida ? 'No aplica' : 'Ej.: 4';

                if (esIndefinida) cantidad.value = '';
            }

            document.getElementById('form_vigencia_unidad')?.addEventListener('change', actualizarCantidadVigencia);
            const tiposEvidencias = @json($tiposEvidenciasJson);
            const tiposCertificadosRequeridos = @json($tiposCertificadosJson);
            const funcionariosReceptores = @json($funcionariosReceptoresDisponibles);
            let selectorFuncionarioReceptorAbierto = false;

            // Evita que textos escritos por el usuario rompan el HTML de la tabla dinamica.
            function escaparHtml(valor) {
                return String(valor ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;')
                    .replace(/\r?\n/g, '&#10;');
            }

            // Muestra solamente los funcionarios que tienen un cargo activo en el area elegida.
            function receptoresDelAreaSeleccionada() {
                const idArea = String(document.getElementById('form_id_area')?.value || '');

                return funcionariosReceptores.filter((funcionario) => String(funcionario.id_area) === idArea);
            }

            function renderizarSelectorFuncionarioReceptor(permitirSeleccionAutomaticamente = true) {
                const input = document.getElementById('form_id_funcionario_receptor_inicial');
                const boton = document.getElementById('form_id_funcionario_receptor_inicial_boton');
                const seleccionado = document.getElementById('funcionarioReceptorSeleccionado');
                const opciones = document.getElementById('opcionesFuncionarioReceptor');
                const ayuda = document.getElementById('ayudaFuncionarioReceptor');
                const idArea = String(document.getElementById('form_id_area')?.value || '');
                const receptores = receptoresDelAreaSeleccionada();

                if (!idArea) {
                    input.value = '';
                    seleccionado.innerHTML = '<span class="block text-sm text-slate-400">Primero seleccione el área</span>';
                    ayuda.textContent = '';
                    boton.disabled = true;
                    opciones.classList.add('hidden');
                    boton.setAttribute('aria-expanded', 'false');
                    selectorFuncionarioReceptorAbierto = false;
                    return;
                }

                boton.disabled = false;
                const seleccionActualEsValida = receptores.some((item) => String(item.id) === String(input.value));

                if (!seleccionActualEsValida) {
                    input.value = permitirSeleccionAutomaticamente && receptores.length === 1
                        ? String(receptores[0].id)
                        : '';
                }

                const funcionarioSeleccionado = receptores.find((item) => String(item.id) === String(input.value));
                seleccionado.innerHTML = funcionarioSeleccionado
                    ? `<span class="block truncate text-sm font-semibold text-slate-800">${escaparHtml(funcionarioSeleccionado.nombre)}</span>
                       <span class="mt-0.5 block truncate text-xs font-medium text-emerald-600">${escaparHtml(funcionarioSeleccionado.cargo)}</span>`
                    : '<span class="block text-sm text-slate-500">Seleccione el funcionario</span>';

                ayuda.textContent = receptores.length === 0
                    ? 'No existen funcionarios activos con cargo en esta área.'
                    : (receptores.length === 1
                        ? 'Se seleccionó automáticamente porque es el único funcionario disponible.'
                        : 'Seleccione quién recibirá inicialmente los trámites de este tipo.');
                ayuda.classList.toggle('text-red-600', receptores.length === 0);
                ayuda.classList.toggle('text-slate-500', receptores.length > 0);

                opciones.innerHTML = receptores.length
                    ? receptores.map((funcionario) => `
                        <button type="button" role="option"
                            class="block w-full border-b border-slate-100 px-3 py-2 text-left transition last:border-b-0 hover:bg-teal-50"
                            aria-selected="${String(funcionario.id) === String(input.value)}"
                            onclick="seleccionarFuncionarioReceptor('${escaparHtml(funcionario.id)}')">
                            <span class="block text-sm font-semibold text-slate-800">${escaparHtml(funcionario.nombre)}</span>
                            <span class="mt-0.5 block text-xs font-medium text-emerald-600">${escaparHtml(funcionario.cargo)}</span>
                        </button>
                    `).join('')
                    : '<p class="px-3 py-3 text-sm text-slate-500">No hay funcionarios disponibles.</p>';

                opciones.classList.toggle('hidden', !selectorFuncionarioReceptorAbierto);
                boton.setAttribute('aria-expanded', String(selectorFuncionarioReceptorAbierto));
            }

            function alternarSelectorFuncionarioReceptor() {
                if (document.getElementById('form_id_funcionario_receptor_inicial_boton')?.disabled) return;

                selectorFuncionarioReceptorAbierto = !selectorFuncionarioReceptorAbierto;
                renderizarSelectorFuncionarioReceptor(false);
            }

            function seleccionarFuncionarioReceptor(idFuncionario) {
                document.getElementById('form_id_funcionario_receptor_inicial').value = idFuncionario;
                selectorFuncionarioReceptorAbierto = false;
                renderizarSelectorFuncionarioReceptor(false);
            }

            document.getElementById('form_id_area')?.addEventListener('change', function () {
                selectorFuncionarioReceptorAbierto = false;
                renderizarSelectorFuncionarioReceptor(true);
            });

            document.addEventListener('click', function (event) {
                if (!selectorFuncionarioReceptorAbierto || event.target.closest('#selectorFuncionarioReceptor')) return;

                selectorFuncionarioReceptorAbierto = false;
                renderizarSelectorFuncionarioReceptor(false);
            });

            // Abre el modal para crear un requisito nuevo.
            function abrirModalRequisito() {
                document.getElementById('errorNuevoRequisito')?.classList.add('hidden');
                document.getElementById('modalRequisitoRapido')?.classList.remove('hidden');
                document.getElementById('modalRequisitoRapido')?.classList.add('flex');
                document.getElementById('nuevo_requisito_descripcion')?.focus();
            }

            // Cierra el modal sin modificar los requisitos ya agregados.
            function cerrarModalRequisito() {
                document.getElementById('modalRequisitoRapido')?.classList.add('hidden');
                document.getElementById('modalRequisitoRapido')?.classList.remove('flex');
            }

            // Filtra requisitos existentes por el texto del buscador.
            document.getElementById('buscarRequisitoExistente')?.addEventListener('input', function() {
                const busqueda = this.value.trim().toLowerCase();

                document.querySelectorAll('.requisito-existente').forEach((item) => {
                    item.classList.toggle('hidden', !item.dataset.descripcion.includes(busqueda));
                });
            });

            // Filtra los tipos de certificados que pueden agregarse como certificados previos.
            document.getElementById('buscarCertificadoPrevio')?.addEventListener('input', function() {
                const busqueda = this.value.trim().toLowerCase();

                document.querySelectorAll('.certificado-previo').forEach((item) => {
                    item.classList.toggle('hidden', !item.dataset.nombre.includes(busqueda));
                });
            });

            // Agrega a la tabla de configuracion los requisitos marcados en el buscador.
            function agregarRequisitosSeleccionados() {
                document.querySelectorAll('#listaRequisitosExistentes input[type="checkbox"]:checked').forEach((checkbox) => {
                    agregarRequisitoAsignado({
                        id: checkbox.value,
                        descripcion: checkbox.dataset.requisitoDescripcion,
                        id_tipo_evidencia: '',
                        calificacion_minima_aprobacion: '',
                        id_tipo_certificado_requerido: '',
                        estado: 'ACTIVO',
                        nuevo: false,
                    });

                    checkbox.checked = false;
                });
            }

            // Agrega certificados previos como requisitos y conserva sus requisitos internos para mostrarlos en la tabla.
            function agregarCertificadosPreviosSeleccionados() {
                const idTipoEvidenciaCertificado = idEvidenciaCertificadoVigente();

                document.querySelectorAll('#listaCertificadosPrevios input[type="checkbox"]:checked').forEach((checkbox) => {
                    const certificado = buscarTipoCertificadoRequerido(checkbox.value);

                    agregarRequisitoAsignado({
                        id: `certificado_previo_${checkbox.value}`,
                        descripcion: checkbox.dataset.certificadoNombre,
                        id_tipo_evidencia: idTipoEvidenciaCertificado,
                        calificacion_minima_aprobacion: '',
                        id_tipo_certificado_requerido: checkbox.value,
                        nombre_certificado_requerido: checkbox.dataset.certificadoNombre,
                        requisitos_certificado_requerido: certificado?.requisitos || [],
                        estado: 'ACTIVO',
                        nuevo: true,
                        es_certificado_previo: true,
                    });

                    checkbox.checked = false;
                });
            }

            // Crea un requisito temporal y lo agrega directamente a la tabla de configuracion.
            function crearRequisitoRapido() {
                const descripcionInput = document.getElementById('nuevo_requisito_descripcion');
                const estadoInput = document.getElementById('nuevo_requisito_estado');
                const error = document.getElementById('errorNuevoRequisito');
                const descripcion = descripcionInput.value.trim();

                if (!descripcion) {
                    error?.classList.remove('hidden');
                    descripcionInput.focus();
                    return;
                }

                agregarRequisitoAsignado({
                    id: `nuevo_${indiceRequisitoNuevo++}`,
                    descripcion,
                    id_tipo_evidencia: '',
                    calificacion_minima_aprobacion: '',
                    id_tipo_certificado_requerido: '',
                    estado: estadoInput.value || 'ACTIVO',
                    nuevo: true,
                });

                descripcionInput.value = '';
                estadoInput.value = 'ACTIVO';
                cerrarModalRequisito();
            }

            // Evita duplicar el mismo requisito dentro del tipo de certificado.
            function agregarRequisitoAsignado(requisito) {
                const descripcion = requisito.descripcion || '';
                const existe = requisitosAsignados.some((item) =>
                    item.id === requisito.id ||
                    String(item.descripcion).toLowerCase() === descripcion.toLowerCase() ||
                    (
                        requisito.es_certificado_previo &&
                        String(item.id_tipo_certificado_requerido) === String(requisito.id_tipo_certificado_requerido)
                    )
                );

                if (existe) {
                    return;
                }

                requisitosAsignados.push({
                    ...requisito,
                    id_tipo_evidencia: requisito.id_tipo_evidencia || '',
                    calificacion_minima_aprobacion: requisito.calificacion_minima_aprobacion ?? '',
                    id_tipo_certificado_requerido: requisito.id_tipo_certificado_requerido || '',
                    nombre_certificado_requerido: requisito.nombre_certificado_requerido || '',
                    requisitos_certificado_requerido: requisito.requisitos_certificado_requerido ||
                        requisitosDelCertificado(requisito.id_tipo_certificado_requerido),
                    es_certificado_previo: requisito.es_certificado_previo || false,
                });

                renderizarTablaRequisitos();
            }

            // Dibuja cada requisito en una fila independiente.
            // Si el requisito es un certificado previo, igual se muestra como una fila propia.
            function renderizarTablaRequisitos() {
                const tbody = document.getElementById('tablaRequisitosAsignados');
                const contenedorInputs = document.getElementById('inputsOcultosRequisitos');
                tbody.innerHTML = '';
                contenedorInputs.innerHTML = '';

                if (requisitosAsignados.length === 0) {
                    tbody.innerHTML = `
                        <tr id="filaSinRequisitos">
                            <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">
                                Todavia no agregaste requisitos a este tipo de certificado.
                            </td>
                        </tr>
                    `;
                    actualizarContadoresRequisitos();
                    return;
                }

                requisitosAsignados.forEach((requisito, index) => {
                    contenedorInputs.insertAdjacentHTML('beforeend', inputsOcultosRequisito(requisito, index));
                    tbody.appendChild(filaRequisitoAsignado(requisito, index));
                });

                actualizarContadoresRequisitos();
            }

            // Crea los inputs que realmente se guardan en requisitos_tipos_certificados y dependencias_requisitos.
            function inputsOcultosRequisito(requisito, index) {
                const descripcionParaGuardar = requisito.es_certificado_previo ?
                    limpiarNombreCertificadoPrevio(requisito.descripcion) :
                    requisito.descripcion;

                return `
                    <input type="hidden" name="requisitos_asignados[${index}][id_requisito]" value="${requisito.nuevo ? '' : escaparHtml(requisito.id)}">
                    <input type="hidden" name="requisitos_asignados[${index}][descripcion]" value="${escaparHtml(descripcionParaGuardar)}">
                    <input type="hidden" name="requisitos_asignados[${index}][id_tipo_evidencia]" value="${escaparHtml(requisito.id_tipo_evidencia || '')}">
                    <input type="hidden" id="calificacionMinimaRequisito-${index}" name="requisitos_asignados[${index}][calificacion_minima_aprobacion]" value="${escaparHtml(requisito.calificacion_minima_aprobacion ?? '')}">
                    <input type="hidden" name="requisitos_asignados[${index}][id_tipo_certificado_requerido]" value="${escaparHtml(requisito.id_tipo_certificado_requerido || '')}">
                    <input type="hidden" name="requisitos_asignados[${index}][nombre_certificado_requerido]" value="${escaparHtml(requisito.nombre_certificado_requerido || '')}">
                    <input type="hidden" name="requisitos_asignados[${index}][nuevo]" value="${requisito.nuevo ? 1 : 0}">
                    <input type="hidden" name="requisitos_asignados[${index}][estado]" value="${escaparHtml(requisito.estado || 'ACTIVO')}">
                    <input type="hidden" name="requisitos_asignados[${index}][es_certificado_previo]" value="${requisito.es_certificado_previo ? 1 : 0}">
                `;
            }

            // Construye la fila visible de un requisito.
            // Los certificados previos no se agrupan: cada certificado queda como requisito independiente.
            function filaRequisitoAsignado(requisito, index) {
                const fila = document.createElement('tr');

                // Primero se identifica si la fila viene de un certificado previo.
                const esCertificadoPrevio = Boolean(requisito.es_certificado_previo);
                const descripcion = esCertificadoPrevio ?
                    limpiarNombreCertificadoPrevio(requisito.descripcion) :
                    requisito.descripcion;
                const descripcionSegura = escaparHtml(descripcion);
                const estadoSeguro = escaparHtml(requisito.estado || 'ACTIVO');
                const idTipoEvidencia = requisito.id_tipo_evidencia || '';
                const requisitosCertificado = requisito.requisitos_certificado_requerido || requisitosDelCertificado(requisito
                    .id_tipo_certificado_requerido);
                const claseFila = esCertificadoPrevio ? 'bg-emerald-50/30 hover:bg-emerald-50' : 'hover:bg-slate-50';
                const puedeSubir = index > 0;
                const puedeBajar = index < requisitosAsignados.length - 1;

                // Todo requisito debe permitir definir que tipo de evidencia pedira el sistema.
                const evidencia = selectorTipoEvidencia(index, idTipoEvidencia, esCertificadoPrevio);

                // Si es certificado previo, aqui se muestran los requisitos propios de ese certificado.
                const dependencia = esCertificadoPrevio ?
                    `<div class="space-y-2">
                            ${requisitosInternosCertificado(requisitosCertificado)}
                        </div>` :
                    '<span class="text-sm font-medium text-slate-400">No requiere</span>';

                fila.className = claseFila;
                fila.innerHTML = `
                    <td class="px-2 py-3">
                        <div class="flex items-center gap-1">
                            <span class="w-4 text-center font-semibold text-slate-600">${index + 1}</span>
                            <span class="inline-flex flex-col overflow-hidden rounded border border-slate-200 bg-white">
                                <button type="button"
                                    class="flex h-4 w-7 items-center justify-center border-b border-slate-200 ${puedeSubir ? 'text-slate-600 hover:bg-slate-50 hover:text-teal-700' : 'cursor-not-allowed bg-slate-50 text-slate-300'}"
                                    title="Subir requisito"
                                    aria-label="Subir requisito ${index + 1}"
                                    onclick="moverRequisitoAsignado(${index}, -1)"
                                    ${puedeSubir ? '' : 'disabled'}>
                                    <i class="fa-solid fa-chevron-up text-[8px]"></i>
                                </button>
                                <button type="button"
                                    class="flex h-4 w-7 items-center justify-center ${puedeBajar ? 'text-slate-600 hover:bg-slate-50 hover:text-teal-700' : 'cursor-not-allowed bg-slate-50 text-slate-300'}"
                                    title="Bajar requisito"
                                    aria-label="Bajar requisito ${index + 1}"
                                    onclick="moverRequisitoAsignado(${index}, 1)"
                                    ${puedeBajar ? '' : 'disabled'}>
                                    <i class="fa-solid fa-chevron-down text-[8px]"></i>
                                </button>
                            </span>
                        </div>
                    </td>
                    <td class="px-3 py-3">
                        <p class="whitespace-pre-line break-words font-semibold text-slate-800">${descripcionSegura}</p>
                        ${requisito.nuevo && !esCertificadoPrevio ? '<p class="text-xs font-medium text-teal-600">Nuevo requisito</p>' : ''}
                    </td>
                    <td class="px-3 py-3">
                        ${evidencia}
                    </td>
                    <td class="px-3 py-3">
                        ${dependencia}
                    </td>
                    <td class="px-3 py-3 text-center">
                        <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">${estadoSeguro}</span>
                    </td>
                    <td class="px-3 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button"
                                class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                onclick="quitarRequisitoAsignado(${index})">
                                Quitar
                            </button>
                        </div>
                    </td>
                `;

                return fila;
            }

            // Selector visual propio para mostrar codigo y nombre en dos lineas.
            function selectorTipoEvidencia(index, idSeleccionado, incluirCertificadoVigente = false) {
                const abierto = window.selectorEvidenciaAbierto === index;
                const tipoSeleccionado = tiposEvidencias.find((item) => String(item.id) === String(idSeleccionado));
                const opciones = tiposEvidencias.filter((tipo) => incluirCertificadoVigente || tipo.codigo !==
                    'CERTIFICADO');
                const campoCalificacionMinima = tipoSeleccionado?.codigo === 'CALIFICACION' ? `
                    <label class="mt-2 block text-[11px] font-bold text-slate-600">
                        Calificación mínima de aprobación
                        <input type="number" min="0" max="100" step="0.01"
                            class="mt-1 w-full rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-800 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600"
                            value="${escaparHtml(requisitosAsignados[index]?.calificacion_minima_aprobacion ?? '')}"
                            oninput="actualizarCalificacionMinima(${index}, this.value)"
                            placeholder="Ej.: 70">
                    </label>
                ` : '';

                return `
                    <div class="relative">
                        <button type="button"
                            class="w-full rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-left transition hover:border-teal-500 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600"
                            data-evidencia-faltante="${!tipoSeleccionado}"
                            aria-invalid="${mostrarErroresEvidencia && !tipoSeleccionado}"
                            aria-describedby="errorEvidencia-${index}"
                            onclick="alternarSelectorEvidencia(${index})">
                            ${tipoSeleccionado ? `
                                                                        <span class="block text-xs font-bold leading-tight text-slate-800">${escaparHtml(tipoSeleccionado.codigo)}</span>
                                                                        <span class="mt-0.5 block text-[11px] leading-tight text-slate-500">${escaparHtml(tipoSeleccionado.nombre)}</span>
                                                                    ` : `
                                                                        <span class="block text-xs font-semibold leading-tight text-slate-500">Seleccione</span>
                                                                        <span class="mt-0.5 block text-[11px] leading-tight text-slate-400">Tipo de evidencia</span>
                                                                    `}
                        </button>

                        ${abierto ? `
                                                                    <div class="mt-1.5 max-h-48 overflow-y-auto rounded-md border border-slate-200 bg-white shadow-sm">
                                                                        ${opciones.map((tipo) => `
                                    <button type="button"
                                        class="block w-full border-b border-slate-100 px-2.5 py-1.5 text-left transition last:border-b-0 hover:bg-teal-50"
                                        onclick="actualizarTipoEvidenciaAsignado(${index}, '${escaparHtml(tipo.id)}')">
                                        <span class="block text-xs font-bold leading-tight text-slate-800">${escaparHtml(tipo.codigo)}</span>
                                        <span class="mt-0.5 block text-[11px] leading-tight text-slate-500">${escaparHtml(tipo.nombre)}</span>
                                    </button>
                                `).join('')}
                                                                    </div>
                                                                ` : ''}
                        <p id="errorEvidencia-${index}" class="mt-1 text-xs text-red-600" role="alert">${mostrarErroresEvidencia && !tipoSeleccionado ? 'Seleccione el tipo de evidencia para este requisito.' : ''}</p>
                        ${campoCalificacionMinima}
                    </div>
                `;
            }

            // Conserva la calificación mínima mientras se edita sin volver a dibujar toda la fila.
            function actualizarCalificacionMinima(index, valor) {
                if (!requisitosAsignados[index]) return;

                requisitosAsignados[index].calificacion_minima_aprobacion = valor;
                const inputOculto = document.getElementById(`calificacionMinimaRequisito-${index}`);
                if (inputOculto) inputOculto.value = valor;
            }

            // Abre o cierra el selector personalizado de una fila.
            function alternarSelectorEvidencia(index) {
                window.selectorEvidenciaAbierto = window.selectorEvidenciaAbierto === index ? null : index;
                renderizarTablaRequisitos();
            }

            // Obtiene el id del tipo de evidencia que representa certificado vigente.
            function idEvidenciaCertificadoVigente() {
                const tipo = tiposEvidencias.find((item) => item.codigo === 'CERTIFICADO');
                return tipo ? tipo.id : '';
            }

            // Busca en el catalogo el certificado previo seleccionado.
            function buscarTipoCertificadoRequerido(idTipoCertificado) {
                return tiposCertificadosRequeridos.find((item) => String(item.id) === String(idTipoCertificado));
            }

            // Obtiene los requisitos internos del certificado previo para mostrarlos debajo del chip.
            function requisitosDelCertificado(idTipoCertificado) {
                const certificado = buscarTipoCertificadoRequerido(idTipoCertificado);
                return certificado?.requisitos || [];
            }

            // Limpia textos antiguos que fueron creados con la palabra "vigente".
            function limpiarNombreCertificadoPrevio(nombre) {
                return String(nombre ?? '').replace(/\s+vigente$/i, '').trim();
            }

            // Muestra los requisitos que debe cumplir el certificado previo seleccionado.
            function requisitosInternosCertificado(requisitos) {
                if (!requisitos.length) {
                    return '<p class="text-xs font-medium text-slate-400">Sin requisitos</p>';
                }

                return `
                    <div class="rounded-lg border border-emerald-100 bg-white px-2 py-2">
                        <p class="mb-1 text-xs font-bold text-slate-600">Requisitos del certificado previo</p>
                        <div class="space-y-1">
                            ${requisitos.map((requisito) => `
                                                                        <div class="rounded-md bg-slate-50 px-2 py-1">
                                                                            <p class="text-xs font-semibold text-slate-700">${escaparHtml(requisito.descripcion)}</p>
                                                                            <p class="text-[11px] text-slate-500">
                                                                                ${escaparHtml(requisito.tipo_evidencia || 'Sin evidencia')}
                                                                                ${requisito.certificado_requerido ? ` · ${escaparHtml(requisito.certificado_requerido)}` : ''}
                                                                            </p>
                                                                        </div>
                                                                    `).join('')}
                        </div>
                    </div>
                `;
            }

            // Actualiza en memoria el tipo de evidencia elegido.
            function actualizarTipoEvidenciaAsignado(index, idTipoEvidencia) {
                if (!requisitosAsignados[index]) {
                    return;
                }

                requisitosAsignados[index].id_tipo_evidencia = idTipoEvidencia;
                const tipoSeleccionado = tiposEvidencias.find((item) => String(item.id) === String(idTipoEvidencia));
                if (tipoSeleccionado?.codigo !== 'CALIFICACION') {
                    requisitosAsignados[index].calificacion_minima_aprobacion = '';
                }
                window.selectorEvidenciaAbierto = null;

                renderizarTablaRequisitos();
            }

            // Intercambia dos filas y conserva la nueva posicion al guardar.
            function moverRequisitoAsignado(index, desplazamiento) {
                const nuevoIndice = index + desplazamiento;

                if (!requisitosAsignados[index] || nuevoIndice < 0 || nuevoIndice >= requisitosAsignados.length) {
                    return;
                }

                [requisitosAsignados[index], requisitosAsignados[nuevoIndice]] = [
                    requisitosAsignados[nuevoIndice],
                    requisitosAsignados[index],
                ];
                window.selectorEvidenciaAbierto = null;
                renderizarTablaRequisitos();
            }

            // Quita una fila de la tabla de configuracion.
            function quitarRequisitoAsignado(index) {
                requisitosAsignados.splice(index, 1);
                renderizarTablaRequisitos();
            }

            // Limpia todos los requisitos asociados sin borrar los datos principales.
            function limpiarRequisitosAsignados() {
                requisitosAsignados = [];
                renderizarTablaRequisitos();
            }

            // Actualiza el contador inferior.
            function actualizarContadoresRequisitos() {
                document.getElementById('contadorRequisitosAsignados').textContent = requisitosAsignados.length;
            }

            // Reconstruye la tabla al cargar la vista.
            actualizarCantidadVigencia();
            renderizarSelectorFuncionarioReceptor(true);
            renderizarTablaRequisitos();
        </script>
    @endpush

</x-admin-layout>
