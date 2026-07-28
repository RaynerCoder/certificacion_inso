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
        // Los requisitos raiz pertenecen directamente al certificado consultado.
        $requisitosRaiz = collect($arbolRequisitos['requisitos'] ?? []);

        // Cuenta todos los nodos, incluidos los requisitos de certificados previos.
        $contarRequisitos = function ($nodos) use (&$contarRequisitos): int {
            return collect($nodos)->sum(function (array $nodo) use (&$contarRequisitos): int {
                return 1 + $contarRequisitos($nodo['hijos'] ?? []);
            });
        };

        // Calcula la profundidad real para informar cuantos niveles tiene la estructura.
        $calcularNiveles = function ($nodos, int $nivel = 1) use (&$calcularNiveles): int {
            return collect($nodos)->reduce(function (int $maximo, array $nodo) use (&$calcularNiveles, $nivel): int {
                $nivelNodo = empty($nodo['hijos'])
                    ? $nivel
                    : $calcularNiveles($nodo['hijos'], $nivel + 1);

                return max($maximo, $nivelNodo);
            }, 0);
        };

        $totalRequisitos = $contarRequisitos($requisitosRaiz);
        $totalNiveles = $calcularNiveles($requisitosRaiz);
        $totalRequisitosNivelUno = $requisitosRaiz->count();

        // Devuelve las clases del chip segun el estado del certificado.
        $claseEstado = function (?string $estado): string {
            return match ($estado) {
                'ACTIVO' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'INACTIVO' => 'border-rose-200 bg-rose-50 text-rose-700',
                default => 'border-amber-200 bg-amber-50 text-amber-700',
            };
        };

        // Diferencia visualmente cada forma de cumplir un requisito.
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

        $secuenciaNodo = 0;

        // Renderiza un requisito y agrega debajo sus dependencias sin perder la jerarquia.
        $renderizarNodo = function (array $nodo, int $nivel = 1) use (
            &$renderizarNodo,
            $claseEvidencia,
            &$secuenciaNodo,
        ): string {
            $secuenciaNodo++;
            $esCertificado = ($nodo['tipo'] ?? 'requisito') === 'certificado';
            $nombre = e($nodo['nombre'] ?? 'Sin requisito');
            $codigoEvidencia = e($nodo['evidencia_codigo'] ?? 'SIN_EVIDENCIA');
            $nombreEvidencia = e($nodo['evidencia_nombre'] ?? 'Sin evidencia');
            $hijos = $nodo['hijos'] ?? [];
            $tipoTexto = $esCertificado ? 'Certificado previo' : 'Requisito';
            $claseEvidenciaNodo = $claseEvidencia($nodo['evidencia_codigo'] ?? null);
            $claseNodo = $esCertificado ? 'is-certificate' : '';
            $idDescripcion = 'cert-requisito-descripcion-' . $secuenciaNodo;
            $descripcionLarga = mb_strlen($nodo['nombre'] ?? '') > 145;
            $area = filled($nodo['area'] ?? null)
                ? '<p class="cert-requirement-meta"><i class="fa-solid fa-building"></i>' . e($nodo['area']) . '</p>'
                : '';

            $htmlHijos = collect($hijos)
                ->map(fn($hijo) => $renderizarNodo($hijo, $nivel + 1))
                ->implode('');

            $botonDetalle = '';
            if ($descripcionLarga) {
                $botonDetalle = <<<HTML
                    <button type="button" class="cert-detail-button" data-cert-detail="{$idDescripcion}"
                        aria-controls="{$idDescripcion}" aria-expanded="false">
                        <span>Ver detalle</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                HTML;
            }

            $bloqueHijos = '';
            if (filled($htmlHijos)) {
                $cantidadHijos = count($hijos);
                $textoCantidad = $cantidadHijos === 1 ? '1 requisito' : "{$cantidadHijos} requisitos";
                $nivelHijo = $nivel + 1;

                $bloqueHijos = <<<HTML
                    <div class="cert-nested-group">
                        <div class="cert-nested-header">
                            <span>Nivel {$nivelHijo}</span>
                            <span>{$textoCantidad}</span>
                        </div>
                        <div class="cert-nested-grid">
                            {$htmlHijos}
                        </div>
                    </div>
                HTML;
            }

            return <<<HTML
                <article class="cert-requirement-item">
                    <div class="cert-requirement-card {$claseNodo}">
                        <div class="cert-requirement-top">
                            <div class="cert-chip-group">
                                <span class="cert-chip cert-chip-level">Nivel {$nivel}</span>
                                <span class="cert-chip cert-chip-type">{$tipoTexto}</span>
                            </div>
                            <span class="cert-chip {$claseEvidenciaNodo}" title="{$nombreEvidencia}">
                                {$codigoEvidencia}
                            </span>
                        </div>

                        <p id="{$idDescripcion}" class="cert-requirement-title">{$nombre}</p>
                        {$area}
                        {$botonDetalle}
                    </div>

                    {$bloqueHijos}
                </article>
            HTML;
        };
    @endphp

    <style>
        /* Distribuye el certificado base, su conector y el panel de requisitos. */
        .cert-tree-layout {
            display: grid;
            gap: 0;
            grid-template-columns: minmax(280px, 340px) 70px minmax(0, 1fr);
            padding: 2rem 1.5rem;
            position: relative;
        }

        /* Resume los datos del certificado desde donde comienza el tramite. */
        .cert-root-card {
            align-self: start;
            background: linear-gradient(135deg, #f0fdfa 0%, #f8fffe 100%);
            border: 1px solid #99f6e4;
            border-radius: 0.875rem;
            padding: 1.1rem;
        }

        .cert-root-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
            margin-top: 0.9rem;
            overflow-wrap: anywhere;
        }

        .cert-root-area {
            border-top: 1px solid #ccfbf1;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 0.9rem;
            padding-top: 0.9rem;
        }

        /* Une visualmente el certificado base con su primer nivel. */
        .cert-tree-connector {
            align-self: start;
            height: 2px;
            margin-top: 6.45rem;
            position: relative;
            background: #99f6e4;
        }

        .cert-tree-connector::after {
            background: #ffffff;
            border: 5px solid #0d9488;
            border-radius: 999px;
            content: '';
            height: 22px;
            position: absolute;
            right: 50%;
            top: 50%;
            transform: translate(50%, -50%);
            width: 22px;
        }

        /* Agrupa los requisitos de primer nivel en un bloque independiente. */
        .cert-level-panel {
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            min-width: 0;
            overflow: hidden;
        }

        .cert-level-header,
        .cert-nested-header {
            align-items: center;
            background: #f8fafc;
            color: #475569;
            display: flex;
            font-size: 12px;
            font-weight: 700;
            justify-content: space-between;
        }

        .cert-level-header {
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 1.25rem;
        }

        .cert-level-header strong {
            color: #0f766e;
            font-size: 13px;
        }

        .cert-level-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(260px, 1fr));
            padding: 1.25rem;
        }

        /* Cada requisito puede contener a su vez un grupo de dependencias. */
        .cert-requirement-item {
            min-width: 0;
        }

        .cert-requirement-card {
            background: #ffffff;
            border: 1px solid #dbe3ec;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            min-height: 148px;
            padding: 1rem;
        }

        .cert-requirement-card.is-certificate {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .cert-requirement-top {
            align-items: flex-start;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            margin-bottom: 0.85rem;
        }

        .cert-chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
        }

        .cert-chip {
            align-items: center;
            border-radius: 0.4rem;
            border-width: 1px;
            display: inline-flex;
            font-size: 10px;
            font-weight: 800;
            min-height: 22px;
            padding: 0 0.5rem;
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

        /* Limita textos extensos hasta que el usuario solicite verlos completos. */
        .cert-requirement-title {
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 4;
            color: #0f172a;
            display: -webkit-box;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.45;
            overflow: hidden;
            overflow-wrap: anywhere;
        }

        .cert-requirement-title.is-expanded {
            -webkit-line-clamp: unset;
            display: block;
        }

        .cert-requirement-meta {
            align-items: flex-start;
            color: #64748b;
            display: flex;
            font-size: 11px;
            gap: 0.4rem;
            line-height: 1.4;
            margin-top: 0.65rem;
        }

        .cert-requirement-meta i {
            color: #0d9488;
            margin-top: 0.15rem;
        }

        .cert-detail-button {
            align-items: center;
            color: #0f766e;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 0.55rem;
            margin-top: 0.85rem;
        }

        .cert-detail-button:hover {
            color: #115e59;
        }

        .cert-detail-button i {
            font-size: 10px;
            transition: transform 160ms ease;
        }

        .cert-detail-button.is-expanded i {
            transform: rotate(90deg);
        }

        /* Presenta los requisitos internos del certificado previo junto a su padre. */
        .cert-nested-group {
            border-left: 2px solid #99f6e4;
            margin: 0.85rem 0 0 1rem;
            padding-left: 0.85rem;
        }

        .cert-nested-header {
            border: 1px solid #e2e8f0;
            border-radius: 0.55rem 0.55rem 0 0;
            padding: 0.55rem 0.75rem;
        }

        .cert-nested-grid {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0 0 0.55rem 0.55rem;
            border-top: 0;
            display: grid;
            gap: 0.75rem;
            grid-template-columns: 1fr;
            padding: 0.75rem;
        }

        .cert-nested-grid .cert-requirement-card {
            min-height: 0;
        }

        /* Permite alternar a una lectura lineal sin duplicar los datos. */
        .cert-tree-layout.is-list {
            display: block;
        }

        .cert-tree-layout.is-list .cert-root-card,
        .cert-tree-layout.is-list .cert-tree-connector {
            display: none;
        }

        .cert-tree-layout.is-list .cert-level-grid {
            grid-template-columns: 1fr;
        }

        .cert-view-switch {
            background: #ffffff;
            border: 1px solid #dbe3ec;
            border-radius: 0.6rem;
            display: inline-flex;
            overflow: hidden;
        }

        .cert-view-button {
            align-items: center;
            color: #64748b;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 0.45rem;
            min-height: 36px;
            padding: 0 0.85rem;
        }

        .cert-view-button.is-active {
            box-shadow: inset 0 -2px 0 #0d9488;
            color: #0f766e;
        }

        @media (max-width: 1180px) {
            .cert-tree-layout {
                grid-template-columns: minmax(250px, 300px) 48px minmax(0, 1fr);
                padding: 1.25rem;
            }

            .cert-level-grid {
                grid-template-columns: 1fr;
            }

            .cert-tree-connector {
                margin-top: 6rem;
            }
        }

        @media (max-width: 760px) {
            .cert-tree-layout {
                display: block;
                padding: 1rem;
            }

            .cert-root-card {
                width: 100%;
            }

            .cert-tree-connector {
                height: 32px;
                margin: 0 auto;
                width: 2px;
            }

            .cert-tree-connector::after {
                right: 50%;
                top: 50%;
            }

            .cert-level-grid {
                grid-template-columns: minmax(0, 1fr);
                padding: 0.85rem;
            }

            .cert-level-header {
                padding: 0.75rem 0.85rem;
            }

            .cert-requirement-card {
                min-height: 0;
            }
        }

        @media (max-width: 520px) {
            .cert-tree-toolbar {
                align-items: stretch;
            }

            .cert-view-switch {
                width: 100%;
            }

            .cert-view-button {
                flex: 1;
                justify-content: center;
            }

            .cert-nested-group {
                margin-left: 0.35rem;
                padding-left: 0.55rem;
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

        {{-- Vista de consulta de la estructura completa del certificado. --}}
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
                            El certificado inicia en su área responsable y sus requisitos se organizan por niveles.
                        </p>
                    </div>
                </div>

                <div class="cert-tree-toolbar flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex min-h-9 items-center rounded-lg border border-teal-200 bg-white px-3 text-xs font-bold text-teal-700">
                        {{ $totalRequisitos }} {{ $totalRequisitos === 1 ? 'requisito' : 'requisitos' }}
                    </span>
                    <span
                        class="inline-flex min-h-9 items-center rounded-lg border border-teal-200 bg-white px-3 text-xs font-bold text-teal-700">
                        {{ $totalNiveles }} {{ $totalNiveles === 1 ? 'nivel' : 'niveles' }}
                    </span>

                    {{-- Cambia la presentación sin volver a consultar los datos. --}}
                    <div class="cert-view-switch" role="group" aria-label="Vista de requisitos">
                        <button type="button" class="cert-view-button is-active" data-cert-view="tree"
                            aria-pressed="true">
                            <i class="fa-solid fa-sitemap"></i>
                            <span>Árbol</span>
                        </button>
                        <button type="button" class="cert-view-button" data-cert-view="list"
                            aria-pressed="false">
                            <i class="fa-solid fa-list"></i>
                            <span>Lista</span>
                        </button>
                    </div>
                </div>
            </div>

            @if ($totalRequisitos === 0)
                <div class="px-5 py-8 text-center text-sm text-slate-500">
                    Sin requisitos registrados.
                </div>
            @else
                <div id="cert-tree-layout" class="cert-tree-layout">
                    {{-- El certificado base permanece visible como punto de inicio del árbol. --}}
                    <div class="cert-root-card">
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
                    </div>

                    <div class="cert-tree-connector" aria-hidden="true"></div>

                    {{-- El primer nivel usa dos columnas; los niveles internos se agrupan con su padre. --}}
                    <div class="cert-level-panel">
                        <div class="cert-level-header">
                            <strong>Nivel 1</strong>
                            <span>
                                {{ $totalRequisitosNivelUno }}
                                {{ $totalRequisitosNivelUno === 1 ? 'requisito' : 'requisitos' }}
                            </span>
                        </div>

                        <div class="cert-level-grid">
                            @foreach ($requisitosRaiz as $requisito)
                                {!! $renderizarNodo($requisito, 1) !!}
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>

    @push('js')
        <script>
            // Alterna entre la jerarquía visual y una lectura lineal de los mismos requisitos.
            document.addEventListener('click', function (evento) {
                const botonVista = evento.target.closest('[data-cert-view]');

                if (botonVista) {
                    const contenedor = document.getElementById('cert-tree-layout');
                    const vista = botonVista.dataset.certView;

                    if (!contenedor) {
                        return;
                    }

                    contenedor.classList.toggle('is-list', vista === 'list');

                    document.querySelectorAll('[data-cert-view]').forEach((boton) => {
                        const seleccionado = boton === botonVista;
                        boton.classList.toggle('is-active', seleccionado);
                        boton.setAttribute('aria-pressed', seleccionado ? 'true' : 'false');
                    });

                    return;
                }

                // Expande solo la descripción solicitada y conserva compactas las demás tarjetas.
                const botonDetalle = evento.target.closest('[data-cert-detail]');

                if (!botonDetalle) {
                    return;
                }

                const descripcion = document.getElementById(botonDetalle.dataset.certDetail);

                if (!descripcion) {
                    return;
                }

                const expandido = descripcion.classList.toggle('is-expanded');
                botonDetalle.classList.toggle('is-expanded', expandido);
                botonDetalle.setAttribute('aria-expanded', expandido ? 'true' : 'false');
                botonDetalle.querySelector('span').textContent = expandido ? 'Ocultar detalle' : 'Ver detalle';
            });
        </script>
    @endpush
</x-admin-layout>
