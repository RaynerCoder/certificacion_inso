{{-- Los productos y sus datos técnicos solamente son visibles para el personal interno. --}}
@if (!$esSolicitante && $requiereProductoTramite)
    @php
        // Conserva el valor decimal almacenado y elimina únicamente los ceros finales de presentación.
        $formatearDecimal = static function ($valor): string {
            if ($valor === null || $valor === '') {
                return '-';
            }

            $texto = trim((string) $valor);

            if (str_contains($texto, '.')) {
                $texto = rtrim(rtrim($texto, '0'), '.');
            }

            return $texto === '' ? '0' : $texto;
        };

        $mostrarTexto = static fn($valor): string => filled($valor) ? trim((string) $valor) : '-';
    @endphp

    <article class="tramite-detail-panel tramite-panel-productos is-wide">
        <div class="tramite-section-title-row">
            <h3 class="tramite-detail-title">
                <i class="fa-solid fa-boxes-stacked"></i>
                Productos asociados
            </h3>

            @if ($urlRegistrarProductoTramite)
                <a href="{{ $urlRegistrarProductoTramite }}" class="tramite-product-register-btn">
                    <i class="fa-solid fa-plus"></i>
                    Registrar producto
                </a>
            @endif
        </div>

        <div class="tramite-product-list">
            @forelse ($registrosPorProducto as $grupoRegistros)
                @php
                    $producto = $grupoRegistros->first()?->producto;
                    $ingredientesProducto = $producto?->ingredientesProductos ?? collect();
                    // El trámite siempre toma las presentaciones actuales del producto. También conserva
                    // una presentación inactiva si todavía respalda un registro de este certificado.
                    $presentacionesActuales = ($producto?->presentaciones ?? collect())
                        ->filter(static fn($presentacion) => $presentacion->estado === 'ACTIVO');
                    $presentacionesConRegistroHistorico = $grupoRegistros
                        ->pluck('presentacion')
                        ->filter();
                    $presentacionesProducto = $presentacionesActuales
                        ->concat($presentacionesConRegistroHistorico)
                        ->unique('id')
                        ->sortBy('id')
                        ->values();
                    $registrosActualesProducto = ($producto?->registros ?? collect())
                        ->filter(static fn($registro) => $registro->estado === 'ACTIVO');
                    $registrosProducto = $registrosActualesProducto
                        ->concat($grupoRegistros)
                        ->unique('id')
                        ->sortBy('id')
                        ->values();
                    $registrosSinPresentacion = $registrosProducto->whereNull('id_presentacion');
                @endphp

                <details class="tramite-product" @if ($loop->first) open @endif>
                    <summary class="tramite-product-head">
                        <div>
                            <h4 class="tramite-product-title">{{ $mostrarTexto($producto?->nombre_comercial) }}</h4>
                            <div class="tramite-product-meta">Código: {{ $mostrarTexto($producto?->codigo) }}</div>
                        </div>
                        <span class="tramite-product-status">
                            <i class="fa-solid fa-box"></i>
                            {{ $presentacionesProducto->count() }} presentación{{ $presentacionesProducto->count() === 1 ? '' : 'es' }}
                            · {{ $registrosProducto->count() }} registro{{ $registrosProducto->count() === 1 ? '' : 's' }}
                        </span>
                    </summary>

                    <div class="tramite-product-body">
                        <section class="tramite-product-section">
                            <h4 class="tramite-product-section-title">
                                <i class="fa-solid fa-circle-info"></i>
                                Datos del producto
                            </h4>
                            <dl class="tramite-definition is-compact">
                                <div class="tramite-definition-row">
                                    <dt>Nombre técnico</dt>
                                    <dd>{{ $mostrarTexto($producto?->nombre_tecnico) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Clasificación química</dt>
                                    <dd>{{ $mostrarTexto($producto?->clasificacionQuimica?->nombre) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Clasificación toxicológica</dt>
                                    <dd>{{ $mostrarTexto($producto?->clasificacionToxicologica?->descripcion) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Fabricante</dt>
                                    <dd>{{ $mostrarTexto($producto?->fabricante?->nombre) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>País / territorio</dt>
                                    <dd>{{ $mostrarTexto($producto?->territorio?->nombre) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Importador</dt>
                                    <dd>{{ $producto?->importadorPersona ? $nombrePersona($producto->importadorPersona) : '-' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="tramite-product-section">
                            <h4 class="tramite-product-section-title">
                                <i class="fa-solid fa-flask"></i>
                                Ingredientes
                            </h4>
                            <div class="tramite-table-wrap">
                                <table class="tramite-table">
                                    <thead>
                                        <tr>
                                            <th>Ingrediente</th>
                                            <th>Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ingredientesProducto as $ingredienteProducto)
                                            <tr>
                                                <td>{{ $mostrarTexto($ingredienteProducto->ingrediente?->nombre) }}</td>
                                                <td>
                                                    {{ $ingredienteProducto->porcentaje !== null ? $formatearDecimal($ingredienteProducto->porcentaje) . '%' : '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">-</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="tramite-product-section">
                            <h4 class="tramite-product-section-title">
                                <i class="fa-solid fa-box-open"></i>
                                Presentaciones y registros
                            </h4>

                            <div class="tramite-presentation-list">
                                @forelse ($presentacionesProducto as $presentacion)
                                    @php
                                        $registrosPresentacion = $registrosProducto
                                            ->where('id_presentacion', $presentacion->id)
                                            ->values();
                                        $etiqueta = $presentacion->url_etiqueta
                                            ? route('productos_presentaciones_etiqueta', $presentacion)
                                            : null;
                                        $extensionEtiqueta = $presentacion->url_etiqueta
                                            ? strtolower(pathinfo($presentacion->url_etiqueta, PATHINFO_EXTENSION))
                                            : null;
                                        $esImagen = in_array($extensionEtiqueta, ['jpg', 'jpeg', 'png', 'webp'], true);
                                    @endphp

                                    <article class="tramite-presentation">
                                        <div class="tramite-presentation-summary">
                                            <div class="tramite-presentation-column-title">
                                                <i class="fa-solid fa-box-open"></i>
                                                Presentación {{ $loop->iteration }}
                                            </div>
                                            <div class="tramite-presentation-summary-content">
                                                <div class="tramite-presentation-media">
                                                    @if ($etiqueta && $esImagen)
                                                        <a href="{{ $etiqueta }}" target="_blank" title="Ver etiqueta completa">
                                                            <img src="{{ $etiqueta }}" alt="Etiqueta de la presentación">
                                                        </a>
                                                    @elseif ($etiqueta)
                                                        <a href="{{ $etiqueta }}" target="_blank" class="tramite-presentation-file"
                                                            title="Ver etiqueta">
                                                            <i class="fa-regular fa-file"></i>
                                                        </a>
                                                    @else
                                                        <span class="tramite-presentation-empty">Sin etiqueta</span>
                                                    @endif
                                                </div>

                                                <dl class="tramite-presentation-data">
                                                    <div>
                                                        <dt>Tipo de envase</dt>
                                                        <dd>{{ $mostrarTexto($presentacion->tipo_envase) }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt>Cantidad</dt>
                                                        <dd>
                                                            {{ $formatearDecimal($presentacion->cantidad) }}
                                                            @if (filled($presentacion->catalogoUnidad?->nombre))
                                                                {{ $presentacion->catalogoUnidad->nombre }}
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div class="is-wide">
                                                        <dt>Descripción</dt>
                                                        <dd>{{ $mostrarTexto($presentacion->descripcion) }}</dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        </div>

                                        <div class="tramite-presentation-records">
                                            <div class="tramite-presentation-column-title">
                                                <i class="fa-regular fa-file-lines"></i>
                                                Registros asociados
                                                <span>{{ $registrosPresentacion->count() }}</span>
                                            </div>
                                            @if ($registrosPresentacion->isNotEmpty())
                                                <div class="tramite-table-wrap">
                                                    <table class="tramite-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Código de autorización</th>
                                                                <th>Número de lote</th>
                                                                <th>Vigencia</th>
                                                                <th>Cantidad</th>
                                                                <th>Unidad</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($registrosPresentacion as $registro)
                                                                <tr>
                                                                    <td>{{ $mostrarTexto($registro->codigo_autorizacion) }}</td>
                                                                    <td>{{ $mostrarTexto($registro->numero_lote) }}</td>
                                                                    <td>
                                                                        {{ $registro->fecha_vigencia ? \Illuminate\Support\Carbon::parse($registro->fecha_vigencia)->format('d/m/Y') : '-' }}
                                                                    </td>
                                                                    <td>{{ $formatearDecimal($registro->cantidad) }}</td>
                                                                    <td>{{ $mostrarTexto($registro->catalogoUnidad?->nombre) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="tramite-presentation-no-records">
                                                    Esta presentación todavía no tiene un registro asociado al trámite.
                                                </div>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div class="tramite-presentation-no-records">
                                        El producto no tiene presentaciones activas.
                                    </div>
                                @endforelse

                                @if ($registrosSinPresentacion->isNotEmpty())
                                    <div class="tramite-presentation-unassigned">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        {{ $registrosSinPresentacion->count() }} registro{{ $registrosSinPresentacion->count() === 1 ? '' : 's' }}
                                        sin presentación asignada en el módulo Producto.
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>
                </details>
            @empty
                <div class="cert-history-empty">Este trámite no tiene productos asociados.</div>
            @endforelse
        </div>
    </article>
@endif
