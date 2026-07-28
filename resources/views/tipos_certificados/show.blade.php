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
        // Ordena los requisitos por nivel y conserva el identificador de su padre.
        // Estos datos permiten dibujar cada conexión sin alterar la información guardada.
        $requisitosRaiz = collect($arbolRequisitos['requisitos'] ?? []);
        $nodosPorNivel = [];
        $secuenciaNodo = 0;

        $organizarNodo = function (array $nodo, int $nivel, string $idPadre) use (
            &$organizarNodo,
            &$nodosPorNivel,
            &$secuenciaNodo,
        ): void {
            $secuenciaNodo++;
            $idNodo = 'requisito-' . $secuenciaNodo;

            $nodosPorNivel[$nivel][] = [
                'id' => $idNodo,
                'id_padre' => $idPadre,
                'nivel' => $nivel,
                'datos' => $nodo,
            ];

            foreach ($nodo['hijos'] ?? [] as $hijo) {
                $organizarNodo($hijo, $nivel + 1, $idNodo);
            }
        };

        foreach ($requisitosRaiz as $requisito) {
            $organizarNodo($requisito, 1, 'certificado-raiz');
        }

        $totalRequisitos = $secuenciaNodo;
        $totalNiveles = empty($nodosPorNivel) ? 0 : max(array_keys($nodosPorNivel));
        $cantidadColumnas = max(1, $totalNiveles + 1);

        // Mantiene los mismos colores de estado utilizados en los demás módulos.
        $claseEstado = function (?string $estado): string {
            return match ($estado) {
                'ACTIVO' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'INACTIVO' => 'border-rose-200 bg-rose-50 text-rose-700',
                default => 'border-amber-200 bg-amber-50 text-amber-700',
            };
        };

        // Diferencia cada tipo de evidencia mediante un chip de color suave.
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
    @endphp

    <style>
        /* El desplazamiento horizontal mantiene legible el árbol en pantallas pequeñas. */
        .cert-tree-viewport {
            overflow-x: auto;
            overflow-y: hidden;
            padding: 1.75rem 1.5rem 2.25rem;
            scrollbar-color: #94a3b8 #f1f5f9;
            scrollbar-width: thin;
        }

        .cert-tree-viewport::-webkit-scrollbar {
            height: 10px;
        }

        .cert-tree-viewport::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 999px;
        }

        .cert-tree-viewport::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border: 2px solid #f1f5f9;
            border-radius: 999px;
        }

        /* Cada nivel ocupa una columna y deja un canal exclusivo para los conectores. */
        .cert-tree-canvas {
            --cert-column-width: 360px;
            --cert-column-gap: 120px;
            display: grid;
            gap: var(--cert-column-gap);
            grid-template-columns: repeat(var(--cert-tree-columns), var(--cert-column-width));
            min-height: 360px;
            min-width: max-content;
            position: relative;
        }

        .cert-tree-lines {
            height: 100%;
            inset: 0;
            overflow: visible;
            pointer-events: none;
            position: absolute;
            width: 100%;
            z-index: 3;
        }

        .cert-tree-column {
            min-width: 0;
            position: relative;
            z-index: 2;
        }

        .cert-level-title {
            color: #0f766e;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 1.4rem;
            min-height: 20px;
            text-align: center;
        }

        .cert-level-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* La tarjeta raíz identifica el punto exacto donde comienza la estructura. */
        .cert-root-card,
        .cert-requirement-card {
            background: #ffffff;
            border: 1px solid #dbe3ec;
            border-radius: 0.875rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            min-width: 0;
            padding: 1.1rem;
            position: relative;
        }

        .cert-root-card {
            background: linear-gradient(135deg, #f0fdfa 0%, #f8fffe 100%);
            border-color: #5eead4;
        }

        .cert-requirement-card.is-certificate {
            background: #f0fdfa;
            border-color: #99f6e4;
        }

        .cert-root-title,
        .cert-requirement-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.5;
            margin-top: 0.85rem;
            overflow-wrap: anywhere;
        }

        .cert-root-area {
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 0.65rem;
            overflow-wrap: anywhere;
        }

        .cert-requirement-evidence {
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
            margin-top: 0.75rem;
        }

        .cert-requirement-area {
            align-items: flex-start;
            color: #64748b;
            display: flex;
            font-size: 11px;
            gap: 0.4rem;
            line-height: 1.4;
            margin-top: 0.65rem;
        }

        .cert-requirement-area i {
            color: #0d9488;
            margin-top: 0.15rem;
        }

        .cert-chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .cert-chip {
            align-items: center;
            border-radius: 0.45rem;
            border-width: 1px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            min-height: 24px;
            padding: 0 0.55rem;
            white-space: nowrap;
        }

        .cert-chip-level {
            background: #f0fdfa;
            border-color: #99f6e4;
            color: #0f766e;
        }

        .cert-chip-type {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #475569;
        }

        .cert-empty-level {
            border: 1px dashed #cbd5e1;
            border-radius: 0.75rem;
            color: #64748b;
            font-size: 12px;
            padding: 1rem;
            text-align: center;
        }

        @media (max-width: 1400px) {
            .cert-tree-canvas {
                --cert-column-width: 330px;
                --cert-column-gap: 105px;
            }
        }

        @media (max-width: 768px) {
            .cert-tree-viewport {
                padding: 1.25rem 1rem 1.75rem;
            }

            .cert-tree-canvas {
                --cert-column-width: 285px;
                --cert-column-gap: 88px;
            }

            .cert-root-card,
            .cert-requirement-card {
                padding: 0.95rem;
            }

            .cert-level-title {
                margin-bottom: 1rem;
                text-align: left;
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

        {{-- Muestra la estructura horizontal aprobada sin modificar los datos del certificado. --}}
        <section class="overflow-hidden rounded-2xl border border-teal-100 bg-white shadow-sm">
            <div
                class="flex flex-col gap-4 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-white px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-600 text-sm text-white">
                        <i class="fa-solid fa-sitemap"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-teal-800">Árbol de requisitos</h2>
                        <p class="text-xs text-teal-700">
                            El certificado inicia en su área responsable y sus requisitos se despliegan hacia la derecha.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex min-h-9 items-center rounded-lg border border-teal-200 bg-white px-3 text-xs font-bold text-teal-700">
                        {{ $totalRequisitos }} {{ $totalRequisitos === 1 ? 'requisito' : 'requisitos' }}
                    </span>
                    <span
                        class="inline-flex min-h-9 items-center rounded-lg border border-teal-200 bg-white px-3 text-xs font-bold text-teal-700">
                        {{ $totalNiveles }} {{ $totalNiveles === 1 ? 'nivel' : 'niveles' }}
                    </span>
                </div>
            </div>

            @if ($totalRequisitos === 0)
                <div class="px-5 py-8 text-center text-sm text-slate-500">
                    Sin requisitos registrados.
                </div>
            @else
                <div class="cert-tree-viewport" data-cert-tree-viewport>
                    <div id="cert-tree-canvas" class="cert-tree-canvas"
                        style="--cert-tree-columns: {{ $cantidadColumnas }};">
                        <svg class="cert-tree-lines" aria-hidden="true"></svg>

                        {{-- Nivel cero: certificado consultado. --}}
                        <section class="cert-tree-column">
                            <h3 class="cert-level-title">Nivel 0 · Certificado</h3>
                            <div class="cert-level-list">
                                <article class="cert-root-card" data-tree-node="certificado-raiz" data-tree-level="0">
                                    <div class="cert-chip-group">
                                        <span class="cert-chip cert-chip-level">Nivel 0</span>
                                        <span
                                            class="cert-chip border-emerald-200 bg-emerald-50 text-emerald-700">Certificado</span>
                                        <span class="cert-chip {{ $claseEstado($tipoCertificado->estado) }}">
                                            {{ $tipoCertificado->estado ?? 'Sin estado' }}
                                        </span>
                                    </div>

                                    <p class="cert-root-title">{{ $tipoCertificado->nombre }}</p>
                                    <p class="cert-root-area">
                                        Área responsable:
                                        <strong class="font-semibold text-slate-600">
                                            {{ $tipoCertificado->area?->nombre ?? 'Sin área' }}
                                        </strong>
                                    </p>
                                </article>
                            </div>
                        </section>

                        {{-- Cada columna siguiente representa un nivel real del árbol. --}}
                        @for ($nivel = 1; $nivel <= $totalNiveles; $nivel++)
                            @php
                                $nodosNivel = collect($nodosPorNivel[$nivel] ?? []);
                                $cantidadNivel = $nodosNivel->count();
                            @endphp

                            <section class="cert-tree-column">
                                <h3 class="cert-level-title">
                                    Nivel {{ $nivel }} · {{ $cantidadNivel }}
                                    {{ $cantidadNivel === 1 ? 'requisito' : 'requisitos' }}
                                </h3>

                                <div class="cert-level-list">
                                    @forelse ($nodosNivel as $nodoNivel)
                                        @php
                                            $nodo = $nodoNivel['datos'];
                                            $esCertificado = ($nodo['tipo'] ?? 'requisito') === 'certificado';
                                            $tipoTexto = $esCertificado ? 'Certificado previo' : 'Requisito';
                                            $codigoEvidencia = $nodo['evidencia_codigo'] ?? 'SIN_EVIDENCIA';
                                            $nombreEvidencia = $nodo['evidencia_nombre'] ?? 'Sin evidencia';
                                        @endphp

                                        <article
                                            class="cert-requirement-card {{ $esCertificado ? 'is-certificate' : '' }}"
                                            data-tree-node="{{ $nodoNivel['id'] }}"
                                            data-tree-parent="{{ $nodoNivel['id_padre'] }}"
                                            data-tree-level="{{ $nodoNivel['nivel'] }}">
                                            <div class="cert-chip-group">
                                                <span class="cert-chip cert-chip-level">
                                                    Nivel {{ $nodoNivel['nivel'] }}
                                                </span>
                                                <span class="cert-chip cert-chip-type">{{ $tipoTexto }}</span>
                                                <span class="cert-chip {{ $claseEvidencia($codigoEvidencia) }}"
                                                    title="{{ $nombreEvidencia }}">
                                                    {{ $codigoEvidencia }}
                                                </span>
                                            </div>

                                            <p class="cert-requirement-title">
                                                {{ $nodo['nombre'] ?? 'Sin requisito' }}
                                            </p>

                                            <p class="cert-requirement-evidence">{{ $nombreEvidencia }}</p>

                                            @if (filled($nodo['area'] ?? null))
                                                <p class="cert-requirement-area">
                                                    <i class="fa-solid fa-building"></i>
                                                    <span>{{ $nodo['area'] }}</span>
                                                </p>
                                            @endif
                                        </article>
                                    @empty
                                        <p class="cert-empty-level">Sin requisitos en este nivel.</p>
                                    @endforelse
                                </div>
                            </section>
                        @endfor
                    </div>
                </div>
            @endif
        </section>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const lienzo = document.getElementById('cert-tree-canvas');

                if (!lienzo) {
                    return;
                }

                const svg = lienzo.querySelector('.cert-tree-lines');
                let cuadroPendiente = null;

                // Crea un elemento SVG con los atributos indicados.
                const crearElementoSvg = function (nombre, atributos) {
                    const elemento = document.createElementNS('http://www.w3.org/2000/svg', nombre);

                    Object.entries(atributos).forEach(([atributo, valor]) => {
                        elemento.setAttribute(atributo, valor);
                    });

                    return elemento;
                };

                // Dibuja una línea horizontal o vertical con extremos suaves.
                const dibujarLinea = function (x1, y1, x2, y2, color, ancho = 2) {
                    svg.appendChild(crearElementoSvg('line', {
                        x1,
                        y1,
                        x2,
                        y2,
                        stroke: color,
                        'stroke-width': ancho,
                        'stroke-linecap': 'round',
                    }));
                };

                // Redibuja las conexiones según la posición real de cada tarjeta.
                const dibujarConexiones = function () {
                    const rectanguloLienzo = lienzo.getBoundingClientRect();
                    const nodos = [...lienzo.querySelectorAll('[data-tree-node]')];
                    const nodosPorId = new Map(nodos.map((nodo) => [nodo.dataset.treeNode, nodo]));
                    const hijosPorPadre = new Map();

                    nodos.forEach((nodo) => {
                        const idPadre = nodo.dataset.treeParent;

                        if (!idPadre) {
                            return;
                        }

                        if (!hijosPorPadre.has(idPadre)) {
                            hijosPorPadre.set(idPadre, []);
                        }

                        hijosPorPadre.get(idPadre).push(nodo);
                    });

                    svg.replaceChildren();
                    svg.setAttribute('viewBox', `0 0 ${lienzo.clientWidth} ${lienzo.clientHeight}`);

                    hijosPorPadre.forEach((hijos, idPadre) => {
                        const padre = nodosPorId.get(idPadre);

                        if (!padre || hijos.length === 0) {
                            return;
                        }

                        const rectanguloPadre = padre.getBoundingClientRect();
                        const nivelPadre = Number(padre.dataset.treeLevel ?? 0);
                        const color = nivelPadre === 0 ? '#14b8a6' : '#0d9488';
                        const salidaX = rectanguloPadre.right - rectanguloLienzo.left;
                        const salidaY = rectanguloPadre.top - rectanguloLienzo.top + (rectanguloPadre.height / 2);
                        const rectangulosHijos = hijos.map((hijo) => hijo.getBoundingClientRect());
                        const entradasY = rectangulosHijos.map((rectangulo) => (
                            rectangulo.top - rectanguloLienzo.top + (rectangulo.height / 2)
                        ));
                        const entradaMasCercanaX = Math.min(...rectangulosHijos.map((rectangulo) => (
                            rectangulo.left - rectanguloLienzo.left
                        )));
                        const troncoX = salidaX + ((entradaMasCercanaX - salidaX) * 0.48);
                        const extremoSuperior = Math.min(salidaY, ...entradasY);
                        const extremoInferior = Math.max(salidaY, ...entradasY);

                        // Tramo de salida y tronco vertical compartido por los hijos del mismo padre.
                        dibujarLinea(salidaX, salidaY, troncoX, salidaY, color);
                        dibujarLinea(troncoX, extremoSuperior, troncoX, extremoInferior, color);

                        // El punto lleno marca la salida desde la tarjeta padre.
                        svg.appendChild(crearElementoSvg('circle', {
                            cx: salidaX,
                            cy: salidaY,
                            r: 6,
                            fill: color,
                        }));

                        rectangulosHijos.forEach((rectanguloHijo, indice) => {
                            const entradaX = rectanguloHijo.left - rectanguloLienzo.left;
                            const entradaY = entradasY[indice];
                            const puntaFlechaX = entradaX - 10;

                            dibujarLinea(troncoX, entradaY, puntaFlechaX - 7, entradaY, color);

                            // La flecha y el círculo vacío indican el destino de la relación.
                            svg.appendChild(crearElementoSvg('path', {
                                d: `M ${puntaFlechaX - 7} ${entradaY - 5} L ${puntaFlechaX} ${entradaY} L ${puntaFlechaX - 7} ${entradaY + 5} Z`,
                                fill: color,
                            }));

                            svg.appendChild(crearElementoSvg('circle', {
                                cx: entradaX,
                                cy: entradaY,
                                r: 6,
                                fill: '#ffffff',
                                stroke: color,
                                'stroke-width': 2,
                            }));

                            // Un punto pequeño identifica cada ramificación del tronco.
                            svg.appendChild(crearElementoSvg('circle', {
                                cx: troncoX,
                                cy: entradaY,
                                r: 3,
                                fill: color,
                            }));
                        });
                    });
                };

                // Agrupa varios cambios de tamaño en un solo redibujado.
                const programarDibujo = function () {
                    if (cuadroPendiente) {
                        cancelAnimationFrame(cuadroPendiente);
                    }

                    cuadroPendiente = requestAnimationFrame(dibujarConexiones);
                };

                programarDibujo();
                window.addEventListener('load', programarDibujo);
                window.addEventListener('resize', programarDibujo);

                if ('ResizeObserver' in window) {
                    const observador = new ResizeObserver(programarDibujo);
                    observador.observe(lienzo);
                    lienzo.querySelectorAll('[data-tree-node]').forEach((nodo) => observador.observe(nodo));
                }
            });
        </script>
    @endpush
</x-admin-layout>
