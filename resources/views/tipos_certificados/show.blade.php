<x-admin-layout title="Tipo de Certificado | Certificador" :breadcrumbs="[
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
        'name' => 'Ver',
    ],
]">

    @php
        // Requisitos de primer nivel: son los requisitos directos del certificado base.
        $requisitosRaiz = collect($arbolRequisitos['requisitos'] ?? []);

        // Total del encabezado: solo cuenta los requisitos directos, no los requisitos internos.
        $totalRequisitosNivelUno = $requisitosRaiz->count();

        // FUNCION PARA DEFINIR EL COLOR DEL ESTADO
        // Devuelve clases CSS para mostrar el estado como chip visual.
        $claseEstado = function (?string $estado): string {
            return match ($estado) {
                'ACTIVO' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'INACTIVO' => 'border-slate-200 bg-slate-100 text-slate-600',
                default => 'border-amber-200 bg-amber-50 text-amber-700',
            };
        };

        // FUNCION PARA DEFINIR EL COLOR DEL TIPO DE EVIDENCIA
        // Permite diferenciar certificado previo, PDF, pago, texto, imagen o presencial.
        $claseEvidencia = function (?string $codigo): string {
            return match ($codigo) {
                'CERTIFICADO' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'PAGO' => 'border-violet-200 bg-violet-50 text-violet-700',
                'PDF' => 'border-sky-200 bg-sky-50 text-sky-700',
                'IMAGEN' => 'border-amber-200 bg-amber-50 text-amber-700',
                'TEXTO' => 'border-slate-200 bg-slate-50 text-slate-700',
                'PRESENCIAL' => 'border-orange-200 bg-orange-50 text-orange-700',
                default => 'border-slate-200 bg-white text-slate-600',
            };
        };

        // FUNCION PARA PINTAR CADA NODO DEL ARBOL HORIZONTAL
        // El padre queda a la izquierda y sus hijos se muestran hacia la derecha.
        $renderizarNodoHorizontal = function (array $nodo, int $nivel = 1) use (
            &$renderizarNodoHorizontal,
            $claseEvidencia,
        ): string {
            // Identifica si este nodo representa un certificado previo.
            $esCertificado = ($nodo['tipo'] ?? 'requisito') === 'certificado';

            // Limpia textos antes de imprimirlos en HTML.
            $nombre = e($nodo['nombre'] ?? 'Sin requisito');
            $codigoEvidencia = e($nodo['evidencia_codigo'] ?? 'SIN_EVIDENCIA');
            $nombreEvidencia = e($nodo['evidencia_nombre'] ?? 'Sin evidencia');

            // Datos visuales del nodo.
            $hijos = $nodo['hijos'] ?? [];
            $tipoTexto = $esCertificado ? 'Certificado previo' : 'Requisito';
            $claseEvidenciaNodo = $claseEvidencia($nodo['evidencia_codigo'] ?? null);
            $claseNodo = $esCertificado ? 'cert-horizontal-node-certificado' : 'cert-horizontal-node-requisito';

            // Renderiza los hijos para formar el siguiente nivel hacia la derecha.
            $htmlHijos = collect($hijos)
                ->map(fn($hijo) => $renderizarNodoHorizontal($hijo, $nivel + 1))
                ->implode('');

            // Si el nodo tiene hijos, se dibuja el grupo derecho.
            $ramaHijos = '';
            if (filled($htmlHijos)) {
                $ramaHijos = <<<HTML
                    <div class="cert-horizontal-children">
                        {$htmlHijos}
                    </div>
                HTML;
            }

            return <<<HTML
                <div class="cert-horizontal-branch">
                    <div class="cert-horizontal-node {$claseNodo}">
                        <div class="cert-horizontal-node-top">
                            <span class="cert-horizontal-chip cert-horizontal-level">Nivel {$nivel}</span>
                            <span class="cert-horizontal-chip cert-horizontal-type">{$tipoTexto}</span>
                            <span class="cert-horizontal-chip {$claseEvidenciaNodo}">{$codigoEvidencia}</span>
                        </div>

                        <p class="cert-horizontal-title">{$nombre}</p>
                        <p class="cert-horizontal-subtitle">{$nombreEvidencia}</p>
                    </div>

                    {$ramaHijos}
                </div>
            HTML;
        };
    @endphp

    <style>
        /* Contenedor principal del organigrama horizontal. */
        .cert-horizontal-tree {
            overflow-x: auto;
            padding: 1rem 0.5rem 1.25rem;
        }

        /* Raiz del arbol: certificado a la izquierda y requisitos hacia la derecha. */
        .cert-horizontal-root {
            align-items: flex-start;
            display: flex;
            gap: 2.75rem;
            min-width: max-content;
            position: relative;
        }

        /* Nodo base del certificado donde se inicia el tramite. */
        .cert-horizontal-base {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 0.875rem;
            min-height: 108px;
            padding: 0.875rem;
            position: relative;
            width: 340px;
        }

        /* Linea que conecta el certificado base con los requisitos de nivel 1. */
        .cert-horizontal-base::after {
            background: #99f6e4;
            content: '';
            height: 2px;
            position: absolute;
            right: -2.75rem;
            top: 54px;
            width: 2.75rem;
        }

        /* Grupo vertical de hijos de un nodo. */
        .cert-horizontal-children {
            border-left: 2px solid #cbd5e1;
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
            padding-left: 2.25rem;
            position: relative;
        }

        /* Cada rama contiene un nodo y, si corresponde, sus hijos a la derecha. */
        .cert-horizontal-branch {
            align-items: flex-start;
            display: flex;
            gap: 2.25rem;
            position: relative;
        }

        /* Linea horizontal que conecta cada nodo con el grupo padre. */
        .cert-horizontal-branch::before {
            background: #cbd5e1;
            content: '';
            height: 1px;
            left: -2.25rem;
            position: absolute;
            top: 46px;
            width: 2.25rem;
        }

        /* Nodo general de requisito o certificado previo. */
        .cert-horizontal-node {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            min-height: 92px;
            padding: 0.75rem;
            width: 256px;
        }

        /* Nodo normal de requisito. */
        .cert-horizontal-node-requisito {
            background: #ffffff;
        }

        /* Nodo que representa un certificado previo. */
        .cert-horizontal-node-certificado {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        /* Fila superior de chips dentro de cada nodo. */
        .cert-horizontal-node-top {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            margin-bottom: 0.5rem;
        }

        /* Chip compacto reutilizado en los nodos. */
        .cert-horizontal-chip {
            align-items: center;
            border-radius: 0.375rem;
            border-width: 1px;
            display: inline-flex;
            font-size: 9px;
            font-weight: 800;
            min-height: 19px;
            padding: 0 0.35rem;
            white-space: nowrap;
        }

        /* Chip de nivel. */
        .cert-horizontal-level {
            background: #f0fdfa;
            border-color: #99f6e4;
            color: #0f766e;
        }

        /* Chip de tipo de nodo. */
        .cert-horizontal-type {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #475569;
        }

        /* Titulo principal del nodo. */
        .cert-horizontal-title {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.25rem;
            overflow-wrap: anywhere;
        }

        /* Texto secundario del nodo. */
        .cert-horizontal-subtitle {
            color: #64748b;
            font-size: 11px;
            line-height: 1rem;
            margin-top: 0.375rem;
            overflow-wrap: anywhere;
        }

        /* En pantallas pequenas se mantiene el organigrama con desplazamiento horizontal. */
        @media (max-width: 900px) {
            .cert-horizontal-tree {
                overflow-x: auto;
            }
        }
    </style>

    <div class="space-y-5">
        {{-- Encabezado principal del detalle. --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-5">
                <div class="absolute inset-x-0 top-0 h-1 bg-emerald-600"></div>
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Tipo de Certificado</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Consulta la estructura completa de requisitos configurados para este certificado.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('tipos_certificados_index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            <span>Volver al listado</span>
                        </a>
                        <a href="{{ route('tipos_certificados_edit', $tipoCertificado) }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                            <span>Editar</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Arbol horizontal de requisitos. --}}
        <section class="overflow-hidden rounded-2xl border border-teal-100 bg-white shadow-sm">
            <div
                class="flex flex-col gap-2 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-white px-5 py-3 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-sm text-white">
                        <i class="fa-solid fa-sitemap"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-teal-800">Arbol de requisitos</h2>
                        <p class="text-xs text-teal-700">
                            El certificado inicia en su area responsable y sus requisitos se despliegan hacia la derecha.
                        </p>
                    </div>
                </div>

                <span
                    class="w-fit rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                    {{ $totalRequisitosNivelUno }} requisitos nivel 1
                </span>
            </div>

            @if ($totalRequisitosNivelUno === 0)
                <div class="px-5 py-8 text-center text-sm text-slate-500">
                    Sin requisitos registrados.
                </div>
            @else
                <div class="px-5 py-5">
                    <div class="cert-horizontal-tree">
                        <div class="cert-horizontal-root">
                            {{-- Nodo base: aqui inicia el tramite del certificado. --}}
                            <div class="cert-horizontal-base">
                                <div class="cert-horizontal-node-top">
                                    <span class="cert-horizontal-chip cert-horizontal-level">Nivel 0</span>
                                    <span
                                        class="cert-horizontal-chip border-emerald-200 bg-emerald-50 text-emerald-700">Certificado</span>
                                    <span class="cert-horizontal-chip {{ $claseEstado($tipoCertificado->estado) }}">
                                        {{ $tipoCertificado->estado ?? 'Sin estado' }}
                                    </span>
                                </div>

                                <p class="cert-horizontal-title">{{ $tipoCertificado->nombre }}</p>
                                <p class="cert-horizontal-subtitle">
                                    Area donde se inicia el tramite: {{ $tipoCertificado->area?->nombre ?? 'Sin area' }}
                                </p>
                            </div>

                            {{-- Requisitos de primer nivel y sus dependencias. --}}
                            <div class="cert-horizontal-children">
                                @foreach ($requisitosRaiz as $requisito)
                                    {!! $renderizarNodoHorizontal($requisito, 1) !!}
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>
