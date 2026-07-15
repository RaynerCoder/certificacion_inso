<x-admin-layout title="Editar plantilla | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Certificados', 'href' => route('certificados_index')],
    ['name' => 'Plantillas', 'href' => route('certificados_plantillas_index')],
    ['name' => 'Editar'],
]">
    @php
        $urlFondo = $plantilla?->url_fondo ? asset('storage/' . $plantilla->url_fondo) : null;
        $nombreFondo = $plantilla?->url_fondo ? basename($plantilla->url_fondo) : 'Sin archivo seleccionado';
        $elementosGuardados = ($plantilla?->elementos ?? collect())
            ->map(function ($elemento) {
                return [
                    'tipo_elemento' => $elemento->tipo_elemento,
                    'codigo_elemento' => $elemento->codigo_elemento,
                    'texto_fijo' => $elemento->texto_fijo,
                    'pagina' => $elemento->pagina,
                    'posicion_x' => $elemento->posicion_x,
                    'posicion_y' => $elemento->posicion_y,
                    'ancho' => $elemento->ancho,
                    'alto' => $elemento->alto,
                    'tamano_letra' => $elemento->tamano_letra,
                    'alineacion' => $elemento->alineacion,
                    'padding_x' => $elemento->padding_x ?? 7,
                    'padding_y' => $elemento->padding_y ?? 5,
                    'interlineado' => $elemento->interlineado ?? 1.25,
                    'negrita' => (bool) $elemento->negrita,
                    'cursiva' => (bool) $elemento->cursiva,
                    'subrayado' => (bool) $elemento->subrayado,
                    'color_texto' => $elemento->color_texto ?: '#0f172a',
                    'tipo_letra' => $elemento->tipo_letra ?: 'Arial',
                    'z_index' => $elemento->z_index ?? 3,
                    'estado' => $elemento->estado,
                    'columnas' => $elemento->columnas
                        ->map(fn ($columna) => [
                            'codigo_campo' => $columna->codigo_campo,
                            'titulo_columna' => $columna->titulo_columna,
                            'ancho' => $columna->ancho,
                            'estado' => $columna->estado,
                        ])
                        ->values(),
                ];
            })
            ->values();
        $elementosIniciales = old('elementos_plantilla')
            ? (json_decode(old('elementos_plantilla'), true) ?: [])
            : $elementosGuardados;
    @endphp

    @include('certificados.plantilla_certificado.estilo')

    <script>
        window.tiposCertificadosPlantilla = @json($tiposCertificadosPlantillaJson);
        window.elementosPlantillaIniciales = @json($elementosIniciales);
    </script>

    <form class="plantilla-shell"
        action="{{ $plantilla ? route('certificados_plantillas_update', $plantilla) : route('certificados_plantillas_store') }}"
        method="POST"
        enctype="multipart/form-data">
        @csrf
        @if ($plantilla)
            @method('PUT')
        @endif

        <input type="hidden" name="elementos_plantilla" data-plantilla-elementos-input value="{{ old('elementos_plantilla', $elementosGuardados->toJson()) }}">
        <input type="hidden" name="quitar_fondo_plantilla" value="0" data-plantilla-fondo-quitar-input>

        <section class="plantilla-card">
            <div class="plantilla-head">
                <div>
                    <h1 class="plantilla-title">Editar plantilla de certificado</h1>
                    <p class="plantilla-subtitle">Ajuste el diseño que se usará para emitir {{ $tipoCertificado->nombre }}.</p>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <x-wire-button href="{{ route('certificados_plantillas_show', $tipoCertificado) }}" secondary>
                        Volver
                    </x-wire-button>
                    <x-wire-button type="submit" emerald>
                        Guardar cambios
                    </x-wire-button>
                </div>
            </div>

            <div class="plantilla-step-title">
                <span>1</span>
                Datos principales
            </div>

            <div class="grid gap-4 p-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-wire-input label="Nombre de la plantilla" name="form_nombre"
                        value="{{ old('form_nombre', $plantilla?->nombre ?? 'Plantilla oficial') }}" />
                </div>

                <div class="lg:col-span-5">
                    <x-wire-native-select label="Tipo de certificado" name="form_id_tipo_certificado" data-plantilla-tipo>
                        @foreach ($tiposCertificados as $tipo)
                            <option value="{{ $tipo->id }}" @selected(old('form_id_tipo_certificado', $tipoCertificado->id) == $tipo->id)>
                                {{ $tipo->nombre }}
                            </option>
                        @endforeach
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-3">
                    <x-wire-native-select label="Estado" name="form_estado">
                        <option value="ACTIVO" @selected(old('form_estado', $plantilla?->estado ?? 'ACTIVO') === 'ACTIVO')>Activo</option>
                        <option value="INACTIVO" @selected(old('form_estado', $plantilla?->estado) === 'INACTIVO')>Inactivo</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-5">
                    <label class="plantilla-label">Archivo de plantilla</label>
                    <input type="file" name="form_url_fondo"
                        accept=".doc,.docx,.pdf,.png,.jpg,.jpeg,.webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf,image/png,image/jpeg,image/webp"
                        class="hidden"
                        data-plantilla-fondo-input data-plantilla-fondo-url="{{ $urlFondo }}">
                    <div class="plantilla-file-control">
                        <span class="plantilla-file-icon">
                            <i class="fa-solid fa-file-arrow-up"></i>
                        </span>
                        <button type="button" class="plantilla-file-btn is-select" data-plantilla-fondo-seleccionar>Seleccionar</button>
                        <button type="button" class="plantilla-file-btn" data-plantilla-fondo-ver @disabled(!$urlFondo)>Ver</button>
                        <button type="button" class="plantilla-file-btn is-danger" data-plantilla-fondo-quitar @disabled(!$urlFondo)>Quitar</button>
                        <span class="plantilla-file-name" data-plantilla-fondo-nombre>{{ $nombreFondo }}</span>
                    </div>
                    @error('form_url_fondo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-2">
                    <x-wire-native-select label="Tamaño" id="form_tamano_papel" name="form_tamano_papel" data-plantilla-tamano>
                        <option value="CARTA" @selected(old('form_tamano_papel', $plantilla?->tamano_papel ?? 'CARTA') === 'CARTA')>Carta</option>
                        <option value="OFICIO" @selected(old('form_tamano_papel', $plantilla?->tamano_papel) === 'OFICIO')>Oficio</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-2">
                    <x-wire-native-select label="Orientación" id="form_orientacion" name="form_orientacion" data-plantilla-orientacion>
                        <option value="VERTICAL" @selected(old('form_orientacion', $plantilla?->orientacion ?? 'VERTICAL') === 'VERTICAL')>Vertical</option>
                        <option value="HORIZONTAL" @selected(old('form_orientacion', $plantilla?->orientacion) === 'HORIZONTAL')>Horizontal</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-2" title="Ajustar a la hoja: la plantilla ocupa todo el espacio. Mostrar completa: se ve toda la plantilla, aunque puedan quedar márgenes. Cubrir toda la hoja: llena todo, pero puede recortar partes.">
                    <x-wire-native-select label="Cómo adaptar la plantilla" id="form_ajuste_fondo" name="form_ajuste_fondo" data-plantilla-ajuste-fondo aria-label="Cómo adaptar la plantilla">
                        <option value="ESTIRAR" @selected(old('form_ajuste_fondo', $plantilla?->ajuste_fondo ?? 'ESTIRAR') === 'ESTIRAR')>Ajustar a la hoja</option>
                        <option value="CONTENER" @selected(old('form_ajuste_fondo', $plantilla?->ajuste_fondo) === 'CONTENER')>Mostrar completa</option>
                        <option value="CUBRIR" @selected(old('form_ajuste_fondo', $plantilla?->ajuste_fondo) === 'CUBRIR')>Cubrir toda la hoja</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-2">
                    <x-wire-native-select label="Fondo de trabajo" id="form_fondo_trabajo" name="form_fondo_trabajo" data-plantilla-fondo-trabajo>
                        <option value="PLANTILLA" @selected(old('form_fondo_trabajo', $plantilla?->fondo_trabajo ?? 'PLANTILLA') === 'PLANTILLA')>Plantilla</option>
                        <option value="BLANCO" @selected(old('form_fondo_trabajo', $plantilla?->fondo_trabajo) === 'BLANCO')>Blanco</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-2">
                    <input type="hidden" name="form_imprimir_fondo" value="0">
                    <label class="plantilla-option-check">
                        <input type="checkbox" name="form_imprimir_fondo" value="1"
                            @checked((bool) old('form_imprimir_fondo', !($plantilla?->imprimir_transparente ?? false)))
                            data-plantilla-imprimir-fondo>
                        <span>
                            <strong>Imprimir fondo</strong>
                            <small>Incluye la plantilla de fondo al emitir.</small>
                        </span>
                    </label>
                </div>

                <div class="hidden" data-plantilla-resumen-tipo></div>
            </div>
        </section>

        <section class="plantilla-designer">
            <aside class="plantilla-panel">
                <div class="plantilla-panel-title">Campos del sistema</div>
                <div class="plantilla-panel-body">
                    <div class="plantilla-editor-ayuda">
                        Haga clic en un campo para insertarlo en el texto seleccionado o arrástrelo al lienzo para colocarlo en una posición exacta.
                    </div>
                    <input type="search" class="plantilla-search" placeholder="Buscar campo..." data-plantilla-buscar-campo>

                    @foreach ($camposPlantilla as $grupo => $campos)
                        <details class="plantilla-field-group" @if ($loop->first) open @endif>
                            <summary class="plantilla-field-title">
                                <span>{{ $grupo }}</span>
                                <span>{{ count($campos) }}</span>
                            </summary>

                            <div class="plantilla-field-list">
                                @foreach ($campos as $campo)
                                    <button type="button" class="plantilla-field"
                                        data-plantilla-campo
                                        data-codigo="{{ $campo['codigo'] }}"
                                        data-nombre="{{ $campo['nombre'] }}"
                                        title="Dato interno: {{ $campo['codigo'] }}">
                                        <span class="plantilla-field-name">{{ $campo['nombre'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </aside>

            <main class="plantilla-panel">
                <div class="plantilla-panel-title">Diseñador del certificado</div>

                <div class="plantilla-toolbar">
                    <button type="button" class="plantilla-action-btn is-primary" data-plantilla-tool="texto">Agregar texto</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="tabla">Agregar tabla</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="firma">Agregar firma</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="qr">Agregar QR</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="imagen">Agregar imagen</button>
                    <span class="plantilla-toolbar-separator"></span>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="deshacer">Deshacer</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="rehacer">Rehacer</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="duplicar">Duplicar</button>
                    <button type="button" class="plantilla-action-btn is-danger" data-plantilla-tool="eliminar">Quitar</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="fondo">Enviar atrás</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="frente">Traer al frente</button>
                    <button type="button" class="plantilla-action-btn" data-plantilla-tool="grid">Cuadrícula</button>
                    <div class="plantilla-zoom-control">
                        <button type="button" data-plantilla-zoom="menos">-</button>
                        <span data-plantilla-zoom-valor>100%</span>
                        <button type="button" data-plantilla-zoom="mas">+</button>
                    </div>
                    <span class="ml-auto text-xs font-bold text-slate-500">
                        Bloques: <strong data-plantilla-contador-campos>0</strong>
                    </span>
                </div>

                <div class="plantilla-canvas-wrap">
                    <div class="plantilla-canvas" data-plantilla-lienzo>
                        <img class="plantilla-canvas-bg" data-plantilla-fondo-preview alt="Plantilla del certificado">
                        <div class="plantilla-canvas-placeholder" data-plantilla-fondo-placeholder>
                            <strong>Sin plantilla visual</strong>
                            <span>Suba una imagen para verla como fondo. Si sube PDF, podrá guardarlo y verlo, pero para diseñar encima conviene usar PNG o JPG.</span>
                        </div>
                    </div>
                    <div class="plantilla-image-info" data-plantilla-fondo-medidas>
                        Sin imagen cargada para medir.
                    </div>
                </div>
            </main>

            <aside class="plantilla-panel">
                <div class="plantilla-panel-title">Propiedades</div>
                <div class="plantilla-panel-body">
                    <div data-plantilla-propiedades></div>
                    <div class="plantilla-layers-box">
                        <div class="plantilla-layers-title">Capas del certificado</div>
                        <div data-plantilla-capas></div>
                    </div>

                    <div class="plantilla-preview-box">
                        <div class="font-black text-emerald-700">Vista previa de marcadores</div>
                        <p class="mt-2">
                            En un texto puede usar marcadores como
                            <span class="plantilla-token">@{{beneficiario_nombre}}</span>
                            y el sistema los reemplazará al emitir.
                        </p>
                    </div>
                </div>
            </aside>
        </section>

        <section class="plantilla-card">
            <div class="plantilla-actions">
                <x-wire-button href="{{ route('certificados_plantillas_show', $tipoCertificado) }}" secondary>
                    Salir sin guardar
                </x-wire-button>
                <x-wire-button type="submit" emerald>
                    Guardar cambios
                </x-wire-button>
            </div>
        </section>
    </form>

    @include('certificados.plantilla_certificado.script')
</x-admin-layout>
