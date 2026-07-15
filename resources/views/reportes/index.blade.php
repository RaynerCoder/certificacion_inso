<x-admin-layout
    title="Reportes | Sistema Certificador"
    :breadcrumbs="[
        ['name' => 'Menu', 'href' => route('admin_dashboard')],
        ['name' => 'Reportes'],
    ]">

    @include('reportes.estilo')

    @php
        $maxRanking = max(1, $rankingSolicitantes->max('total') ?? 1);
        $maxRequisitos = max(1, $requisitosObservados->max('total') ?? 1);
        $maxMeses = max(1, $tramitesPorMes->max('total') ?? 1);
        $totalEstados = max(1, $estadosTramite->sum('total'));
        $primerRequisitoObservado = $requisitosObservados->first();
        $primerFuncionarioCarga = $cargaFuncionarios->first();
        $estadoOpciones = [
            'PENDIENTE' => 'Pendiente',
            'EN_REVISION' => 'En revision',
            'OBSERVADO' => 'Observado',
            'APROBADO' => 'Aprobado',
            'FINALIZADO' => 'Finalizado',
            'EMITIDO' => 'Emitido',
            'RECHAZADO' => 'Rechazado',
        ];

        $coloresDona = ['#059669', '#2563eb', '#f59e0b', '#ef4444', '#94a3b8', '#7c3aed'];
        $inicioDona = 0;
        $partesDona = $estadosTramite->map(function ($estado, $indice) use (&$inicioDona, $coloresDona) {
            $finDona = $inicioDona + (float) $estado['porcentaje'];
            $color = $coloresDona[$indice % count($coloresDona)];
            $parte = "{$color} {$inicioDona}% {$finDona}%";
            $inicioDona = $finDona;

            return $parte;
        });
        $fondoDona = $partesDona->isNotEmpty()
            ? 'conic-gradient(' . $partesDona->implode(', ') . ')'
            : '#e2e8f0';
    @endphp

    <section class="reporte-page">
        <div class="reporte-header">
            <div>
                <h1 class="reporte-title">Reportes de gestion</h1>
                <p class="reporte-subtitle">
                    Indicadores de tramites, revisiones, solicitantes y carga interna del sistema certificador.
                </p>
            </div>
        </div>

        <form method="GET" action="{{ route('reportes_index') }}" class="reporte-filtros">
            <div class="reporte-field">
                <label for="fecha_desde">Fecha desde</label>
                <input id="fecha_desde" name="fecha_desde" type="date" value="{{ $filtros['fecha_desde'] }}">
            </div>

            <div class="reporte-field">
                <label for="fecha_hasta">Fecha hasta</label>
                <input id="fecha_hasta" name="fecha_hasta" type="date" value="{{ $filtros['fecha_hasta'] }}">
            </div>

            <div class="reporte-field">
                <label for="id_tipo_certificado">Tipo de certificado</label>
                <select id="id_tipo_certificado" name="id_tipo_certificado">
                    <option value="">Todos</option>
                    @foreach ($tiposCertificados as $tipoCertificado)
                        <option value="{{ $tipoCertificado->id }}" @selected((string) $filtros['id_tipo_certificado'] === (string) $tipoCertificado->id)>
                            {{ $tipoCertificado->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="reporte-field">
                <label for="id_area">Area</label>
                <select id="id_area" name="id_area">
                    <option value="">Todas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) $filtros['id_area'] === (string) $area->id)>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="reporte-field">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach ($estadoOpciones as $codigo => $texto)
                        <option value="{{ $codigo }}" @selected($filtros['estado'] === $codigo)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="reporte-filter-button">
                <i class="fa-solid fa-filter"></i>
                Filtrar
            </button>
        </form>

        <div class="reporte-kpis">
            <div class="reporte-card">
                <div class="reporte-kpi-label">Tramites iniciados</div>
                <div class="reporte-kpi-value">{{ $resumen['iniciados'] }}</div>
                <div class="reporte-kpi-detail">Solicitudes dentro del periodo</div>
            </div>

            <div class="reporte-card">
                <div class="reporte-kpi-label">Certificados emitidos</div>
                <div class="reporte-kpi-value">{{ $resumen['emitidos'] }}</div>
                <div class="reporte-kpi-detail">Tramites con emision registrada</div>
            </div>

            <div class="reporte-card">
                <div class="reporte-kpi-label">Tramites observados</div>
                <div class="reporte-kpi-value">{{ $resumen['observados'] }}</div>
                <div class="reporte-kpi-detail">Solicitudes con observacion activa</div>
            </div>

            <div class="reporte-card">
                <div class="reporte-kpi-label">Promedio de emision</div>
                <div class="reporte-kpi-value">{{ $resumen['promedio_emision'] }} dias</div>
                <div class="reporte-kpi-detail">Inicio hasta finalizacion</div>
            </div>

            <div class="reporte-card">
                <div class="reporte-kpi-label">Promedio de revision</div>
                <div class="reporte-kpi-value">{{ $resumen['promedio_revision'] }} dias</div>
                <div class="reporte-kpi-detail">Recepcion hasta decision</div>
            </div>

            <div class="reporte-card">
                <div class="reporte-kpi-label">Promedio de correccion</div>
                <div class="reporte-kpi-value">{{ $resumen['promedio_correccion'] }} dias</div>
                <div class="reporte-kpi-detail">Observacion hasta reenvio</div>
            </div>
        </div>

        <div class="reporte-grid-main">
            <div class="reporte-card">
                <h2 class="reporte-card-title">Solicitantes con mas tramites</h2>
                <p class="reporte-card-note">Ranking de empresas o personas naturales con mayor movimiento.</p>

                @if ($rankingSolicitantes->isNotEmpty())
                    <div class="reporte-table-wrap">
                        <table class="reporte-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Solicitante</th>
                                    <th>Tramites</th>
                                    <th>Observados</th>
                                    <th>Finalizados</th>
                                    <th>Promedio de emision</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rankingSolicitantes as $indice => $solicitante)
                                    <tr>
                                        <td>{{ $indice + 1 }}</td>
                                        <td>
                                            <div class="reporte-bar-cell">
                                                <strong>{{ $solicitante['nombre'] }}</strong>
                                                <div class="reporte-bar-track">
                                                    <div class="reporte-bar-fill" style="width: {{ ($solicitante['total'] * 100) / $maxRanking }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $solicitante['total'] }}</td>
                                        <td><span class="reporte-chip ambar">{{ $solicitante['observados'] }}</span></td>
                                        <td><span class="reporte-chip verde">{{ $solicitante['finalizados'] }}</span></td>
                                        <td><span class="reporte-chip azul">{{ $solicitante['promedio_emision'] }} dias</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="reporte-empty">No hay tramites para el periodo seleccionado.</div>
                @endif
            </div>

            <div class="reporte-side-stack">
                <div class="reporte-card">
                    <div class="reporte-big-metric">
                        <span class="reporte-icon"><i class="fa-regular fa-clock"></i></span>
                        <div>
                            <div class="reporte-mini-title">Tramites atrasados</div>
                            <div class="reporte-mini-value">{{ $resumen['atrasados'] }}</div>
                            <div class="reporte-card-note">Mas de 10 dias sin finalizar</div>
                        </div>
                    </div>
                </div>

                <div class="reporte-card">
                    <div class="reporte-big-metric">
                        <span class="reporte-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <div>
                            <div class="reporte-mini-title">Requisito mas observado</div>
                            <div class="reporte-mini-value">
                                {{ $primerRequisitoObservado['requisito'] ?? 'Sin observaciones' }}
                            </div>
                            <div class="reporte-card-note">
                                {{ $primerRequisitoObservado['total'] ?? 0 }} observaciones
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reporte-card">
                    <div class="reporte-big-metric">
                        <span class="reporte-icon"><i class="fa-regular fa-user"></i></span>
                        <div>
                            <div class="reporte-mini-title">Funcionario con mas carga</div>
                            <div class="reporte-mini-value">
                                {{ $primerFuncionarioCarga['funcionario'] ?? 'Sin carga asignada' }}
                            </div>
                            <div class="reporte-card-note">
                                {{ $primerFuncionarioCarga['activos'] ?? 0 }} tramites activos
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="reporte-grid-bottom">
            <div class="reporte-card">
                <h2 class="reporte-card-title">Estados del tramite</h2>
                <div class="reporte-donut" style="background: {{ $fondoDona }}">
                    <div class="reporte-donut-center">{{ $totalEstados }}</div>
                </div>
                <div class="reporte-chart-list">
                    @foreach ($estadosTramite as $estado)
                        <div class="reporte-chart-row">
                            <span class="reporte-chart-label">{{ $estado['texto'] }}</span>
                            <span class="reporte-chart-value">{{ $estado['total'] }} ({{ $estado['porcentaje'] }}%)</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="reporte-card">
                <h2 class="reporte-card-title">Tramites iniciados por mes</h2>
                <div class="reporte-chart-list">
                    @forelse ($tramitesPorMes as $mes)
                        <div>
                            <div class="reporte-chart-row">
                                <span class="reporte-chart-label">{{ $mes['mes'] }}</span>
                                <span class="reporte-chart-value">{{ $mes['total'] }}</span>
                            </div>
                            <div class="reporte-bar-track">
                                <div class="reporte-bar-fill" style="width: {{ ($mes['total'] * 100) / $maxMeses }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="reporte-empty">Sin datos mensuales.</div>
                    @endforelse
                </div>
            </div>

            <div class="reporte-card">
                <h2 class="reporte-card-title">Requisitos mas observados</h2>
                <div class="reporte-chart-list">
                    @forelse ($requisitosObservados as $requisito)
                        <div>
                            <div class="reporte-chart-row">
                                <span class="reporte-chart-label">{{ $requisito['requisito'] }}</span>
                                <span class="reporte-chart-value">{{ $requisito['total'] }}</span>
                            </div>
                            <div class="reporte-bar-track">
                                <div class="reporte-bar-fill" style="width: {{ ($requisito['total'] * 100) / $maxRequisitos }}%; background:#ef4444"></div>
                            </div>
                        </div>
                    @empty
                        <div class="reporte-empty">Sin requisitos observados.</div>
                    @endforelse
                </div>
            </div>

            <div class="reporte-card">
                <h2 class="reporte-card-title">Carga por funcionario</h2>
                <div class="reporte-table-wrap">
                    <table class="reporte-table" style="min-width: 520px">
                        <thead>
                            <tr>
                                <th>Funcionario</th>
                                <th>Activos</th>
                                <th>Atrasados</th>
                                <th>Revision</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cargaFuncionarios as $funcionario)
                                <tr>
                                    <td>
                                        <strong>{{ $funcionario['funcionario'] }}</strong>
                                        <span class="block text-xs font-semibold text-slate-500">{{ $funcionario['area'] }}</span>
                                    </td>
                                    <td><span class="reporte-chip azul">{{ $funcionario['activos'] }}</span></td>
                                    <td><span class="reporte-chip {{ $funcionario['atrasados'] > 0 ? 'rojo' : 'verde' }}">{{ $funcionario['atrasados'] }}</span></td>
                                    <td>{{ $funcionario['promedio_revision'] }} dias</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">Sin carga asignada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
