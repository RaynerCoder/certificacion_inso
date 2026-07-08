<x-admin-layout title="Ver plantilla | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Certificados', 'href' => route('certificados_index')],
    ['name' => 'Plantillas', 'href' => route('certificados_plantillas_index')],
    ['name' => 'Ver'],
]">
    @include('certificados.plantilla_certificado.estilo')

    <div class="plantilla-shell">
        <section class="plantilla-card">
            <div class="plantilla-head">
                <div>
                    <h1 class="plantilla-title">{{ $tipoCertificado->nombre }}</h1>
                    <p class="plantilla-subtitle">
                        Revise la plantilla configurada para este tipo de certificado.
                    </p>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <x-wire-button href="{{ route('certificados_plantillas_index') }}" secondary>
                        Volver
                    </x-wire-button>
                    <x-wire-button href="{{ route('certificados_plantillas_edit', $tipoCertificado) }}" blue>
                        Editar plantilla
                    </x-wire-button>
                </div>
            </div>

            <div class="grid gap-4 p-4 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase text-slate-400">Área responsable</div>
                    <div class="mt-1 font-bold text-slate-800">{{ $tipoCertificado->area?->nombre ?? 'Sin área' }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase text-slate-400">Plantilla</div>
                    <div class="mt-1 font-bold text-slate-800">{{ $plantilla?->nombre ?? 'Sin plantilla activa' }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase text-slate-400">Formato</div>
                    <div class="mt-1 font-bold text-slate-800">
                        {{ $plantilla ? $plantilla->tamano_papel . ' · ' . $plantilla->orientacion : 'Sin formato' }}
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-xs font-bold uppercase text-slate-400">Estado</div>
                    <div class="mt-2"><span class="plantilla-chip">{{ $plantilla?->estado ?? $tipoCertificado->estado }}</span></div>
                </div>
            </div>
        </section>

        @if ($plantilla)
            @php
                $esImagenFondo = $plantilla->url_fondo && preg_match('/\.(jpg|jpeg|png|webp)$/i', $plantilla->url_fondo);
            @endphp

            <section class="grid gap-4 lg:grid-cols-[1fr_360px]">
                <div class="plantilla-panel">
                    <div class="plantilla-panel-title">Vista de la plantilla</div>
                    <div class="plantilla-panel-body">
                        <div class="plantilla-canvas-wrap">
                            <div class="plantilla-canvas">
                                @if ($esImagenFondo)
                                    <img src="{{ asset('storage/' . $plantilla->url_fondo) }}" class="plantilla-fondo" style="display: block;" alt="Fondo de plantilla">
                                @endif

                                <div class="plantilla-paper-content">
                                    <div class="plantilla-drop-zone" data-plantilla-lienzo>
                                        @foreach ($plantilla->elementos as $elemento)
                                            @php
                                                $textoElemento = $elemento->codigo_campo ?: $elemento->texto_fijo;
                                            @endphp
                                            <span class="plantilla-element"
                                                style="left: {{ $elemento->posicion_x }}px; top: {{ $elemento->posicion_y }}px; width: {{ $elemento->ancho }}px; height: {{ $elemento->alto }}px; font-size: {{ $elemento->tamano_letra }}px; font-weight: {{ $elemento->negrita ? 900 : 700 }}; font-style: {{ $elemento->cursiva ? 'italic' : 'normal' }}; text-decoration: {{ $elemento->subrayado ? 'underline' : 'none' }}; color: {{ $elemento->color_texto ?: '#0f172a' }};">
                                                {{ '{' . '{ ' . $textoElemento . ' }' . '}' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="plantilla-panel">
                    <div class="plantilla-panel-title">Campos configurados</div>
                    <div class="plantilla-panel-body">
                        <div class="space-y-2">
                            @forelse ($plantilla->elementos as $elemento)
                                <div class="rounded-lg border border-slate-200 p-3">
                                    <div class="font-bold text-slate-800">
                                        {{ $elemento->codigo_campo ?: $elemento->texto_fijo }}
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                        <span class="plantilla-chip">{{ $elemento->tipo_elemento }}</span>
                                        <span class="plantilla-chip">Página {{ $elemento->pagina }}</span>
                                        @if ($elemento->columnas->isNotEmpty())
                                            <span class="plantilla-chip">{{ $elemento->columnas->count() }} columnas</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">La plantilla no tiene campos configurados.</div>
                            @endforelse
                        </div>
                    </div>
                </aside>
            </section>
        @else
            <section class="plantilla-card p-5">
                <div class="text-sm text-slate-600">
                    Este tipo de certificado todavía no tiene una plantilla activa.
                </div>
            </section>
        @endif

        <section class="plantilla-panel">
            <div class="plantilla-panel-title">Requisitos y evidencias del certificado</div>
            <div class="plantilla-panel-body">
                <div class="grid gap-3">
                    @forelse ($tipoCertificado->tipoCertificadoRequisitos->where('estado', 'ACTIVO') as $asignacion)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="font-bold text-slate-800">
                                {{ $asignacion->requisito?->descripcion ?? 'Requisito sin descripción' }}
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="plantilla-chip">
                                    {{ $asignacion->tipoEvidencia?->codigo ?? 'Sin evidencia' }}
                                </span>

                                @foreach ($asignacion->dependenciasRequisitos as $dependencia)
                                    <span class="plantilla-chip">
                                        {{ $dependencia->tipoCertificadoRequerido?->nombre }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Este tipo de certificado no tiene requisitos activos.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
