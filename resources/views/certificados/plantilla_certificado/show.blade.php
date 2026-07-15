<x-admin-layout title="Ver plantilla | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Certificados', 'href' => route('certificados_index')],
    ['name' => 'Plantillas', 'href' => route('certificados_plantillas_index')],
    ['name' => 'Ver'],
]">
    @include('certificados.plantilla_certificado.estilo')

    <div class="plantilla-shell">
        <section class="plantilla-panel">
            <div class="plantilla-toolbar justify-end">
                <x-wire-button href="{{ route('certificados_plantillas_index') }}" secondary xs>
                    Volver
                </x-wire-button>
                <x-wire-button href="{{ route('certificados_plantillas_edit', $tipoCertificado) }}" blue xs>
                    Editar
                </x-wire-button>
            </div>

            <div class="plantilla-canvas-wrap plantilla-show-only">
                @if ($plantilla)
                    @php
                        $clasesLienzo = collect([
                            'plantilla-canvas',
                            $plantilla->tamano_papel === 'OFICIO' ? 'is-oficio' : null,
                            $plantilla->orientacion === 'HORIZONTAL' ? 'is-horizontal' : null,
                            $plantilla->fondo_trabajo === 'BLANCO' ? 'is-work-white' : null,
                        ])->filter()->implode(' ');
                        $ajusteFondo = match (strtoupper((string) ($plantilla->ajuste_fondo ?? 'ESTIRAR'))) {
                            'CONTENER' => 'contain',
                            'CUBRIR' => 'cover',
                            default => 'fill',
                        };
                    @endphp

                    <div class="{{ $clasesLienzo }}"
                        style="width: {{ (int) ($plantilla->ancho_lienzo_px ?: 816) }}px; height: {{ (int) ($plantilla->alto_lienzo_px ?: 1056) }}px;">
                        @if ($plantilla->url_fondo)
                            <img class="plantilla-canvas-bg is-visible"
                                src="{{ asset('storage/' . $plantilla->url_fondo) }}"
                                alt="Plantilla del certificado"
                                style="object-fit: {{ $ajusteFondo }};">
                        @else
                            <div class="plantilla-canvas-placeholder">
                                <strong>Sin archivo visual</strong>
                                <span>Edite la plantilla para cargar el fondo del certificado.</span>
                            </div>
                        @endif

                        @foreach ($plantilla->elementos as $elemento)
                            @php
                                $textoElemento = $elemento->texto_fijo
                                    ?: '{' . '{' . str_replace('.', '_', (string) $elemento->codigo_elemento) . '}' . '}';
                                $usaPaddingElemento = !in_array($elemento->tipo_elemento, ['IMAGEN', 'TABLA', 'QR'], true);
                                $paddingXElemento = (float) ($elemento->padding_x ?? ($usaPaddingElemento ? 7 : 0));
                                $paddingYElemento = (float) ($elemento->padding_y ?? ($usaPaddingElemento ? 5 : 0));
                                $clasesElemento = collect([
                                    'plantilla-element',
                                    $elemento->tipo_elemento === 'TEXTO' ? 'is-texto' : null,
                                    $elemento->tipo_elemento === 'IMAGEN' ? 'is-imagen' : null,
                                    $elemento->alineacion === 'CENTRO' ? 'is-center' : null,
                                    $elemento->alineacion === 'DERECHA' ? 'is-right' : null,
                                    $elemento->alineacion === 'JUSTIFICADO' ? 'is-justify' : null,
                                ])->filter()->implode(' ');
                            @endphp

                            <div class="{{ $clasesElemento }}"
                                style="
                                    left: {{ (int) $elemento->posicion_x }}px;
                                    top: {{ (int) $elemento->posicion_y }}px;
                                    width: {{ (int) $elemento->ancho }}px;
                                    height: {{ (int) $elemento->alto }}px;
                                    font-size: {{ (int) $elemento->tamano_letra }}px;
                                    font-weight: {{ $elemento->negrita ? '900' : '700' }};
                                    font-style: {{ $elemento->cursiva ? 'italic' : 'normal' }};
                                    text-decoration: {{ $elemento->subrayado ? 'underline' : 'none' }};
                                    color: {{ $elemento->color_texto ?: '#0f172a' }};
                                    font-family: '{{ $elemento->tipo_letra ?: 'Arial' }}', Arial, sans-serif;
                                    line-height: {{ (float) ($elemento->interlineado ?? 1.25) }};
                                    padding: {{ $paddingYElemento }}px {{ $paddingXElemento }}px;
                                    z-index: {{ (int) ($elemento->z_index ?? 3) }};
                                ">
                                @if ($elemento->tipo_elemento === 'IMAGEN' && $textoElemento)
                                    <img src="{{ $textoElemento }}" alt="Imagen de la plantilla">
                                @else
                                    {!! nl2br(e($textoElemento)) !!}
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="plantilla-empty-preview">
                        Este tipo de certificado todavía no tiene una plantilla activa.
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-admin-layout>
