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

        @if ($puedeGestionarProductosTramite && $registrosDisponiblesTramite->isNotEmpty())
            <form action="{{ route('certificados_registros_existentes_vincular', $certificado) }}" method="POST"
                class="mb-4 flex flex-col gap-2 rounded-lg border border-cyan-200 bg-cyan-50 p-3 md:flex-row md:items-end">
                @csrf
                <input type="hidden" name="form_bandeja" value="{{ request('bandeja', 'recibidas') }}">
                <label class="block flex-1 text-sm font-semibold text-slate-700">
                    Vincular registro existente
                    <select name="form_id_registro" class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                        required>
                        <option value="">Seleccione producto, lote y presentación</option>
                        @foreach ($registrosDisponiblesTramite as $registroDisponible)
                            <option value="{{ $registroDisponible->id }}">
                                {{ $registroDisponible->producto?->nombre_comercial ?: '-' }}
                                · Lote {{ $registroDisponible->numero_lote ?: '-' }}
                                · {{ $registroDisponible->presentacion?->descripcion ?: '-' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="tramite-product-register-btn">
                    <i class="fa-solid fa-link"></i>
                    Vincular al trámite
                </button>
            </form>
        @endif

        <div class="tramite-product-list">
            @forelse ($registrosPorProducto as $grupoRegistros)
                @php
                    $producto = $grupoRegistros->first()?->producto;
                    $ingredientesProducto = $producto?->ingredientesProductos ?? collect();
                    $presentacionesConRegistros = $grupoRegistros->groupBy(
                        static fn($registro) => $registro->id_presentacion
                            ? 'presentacion-' . $registro->id_presentacion
                            : 'registro-' . $registro->id,
                    );
                @endphp

                <details class="tramite-product" @if ($loop->first) open @endif>
                    <summary class="tramite-product-head">
                        <div>
                            <h4 class="tramite-product-title">{{ $mostrarTexto($producto?->nombre_comercial) }}</h4>
                            <div class="tramite-product-meta">Código: {{ $mostrarTexto($producto?->codigo) }}</div>
                        </div>
                        <span class="tramite-product-status">
                            <i class="fa-solid fa-box"></i>
                            {{ $grupoRegistros->count() }} registro{{ $grupoRegistros->count() === 1 ? '' : 's' }}
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
                                            <th>Composición</th>
                                            <th>Riesgo de salud</th>
                                            <th>Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ingredientesProducto as $ingredienteProducto)
                                            <tr>
                                                <td>{{ $mostrarTexto($ingredienteProducto->ingrediente?->nombre) }}</td>
                                                <td>{{ $mostrarTexto($ingredienteProducto->ingrediente?->composicion) }}</td>
                                                <td>{{ $mostrarTexto($ingredienteProducto->ingrediente?->riesgo_salud) }}</td>
                                                <td>
                                                    {{ $ingredienteProducto->porcentaje !== null ? $formatearDecimal($ingredienteProducto->porcentaje) . '%' : '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">-</td>
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
                                @foreach ($presentacionesConRegistros as $registrosPresentacion)
                                    @php
                                        $presentacion = $registrosPresentacion->first()?->presentacion;
                                        $etiqueta = $urlArchivo($presentacion?->url_etiqueta);
                                        $rutaEtiqueta = $etiqueta ? parse_url($etiqueta, PHP_URL_PATH) : null;
                                        $extensionEtiqueta = $rutaEtiqueta
                                            ? strtolower(pathinfo($rutaEtiqueta, PATHINFO_EXTENSION))
                                            : null;
                                        $esImagen = in_array($extensionEtiqueta, ['jpg', 'jpeg', 'png', 'webp'], true);
                                    @endphp

                                    <article class="tramite-presentation">
                                        <div class="tramite-presentation-summary">
                                            <div class="tramite-presentation-media">
                                                @if ($etiqueta && $esImagen)
                                                    <a href="{{ $etiqueta }}" target="_blank" title="Ver etiqueta completa">
                                                        <img src="{{ $etiqueta }}" alt="Etiqueta de la presentación">
                                                    </a>
                                                @elseif ($etiqueta)
                                                    <a href="{{ $etiqueta }}" target="_blank" class="tramite-presentation-file"
                                                        title="Ver etiqueta">
                                                        <i class="fa-regular fa-file-pdf"></i>
                                                    </a>
                                                @else
                                                    <span class="tramite-presentation-empty">-</span>
                                                @endif
                                            </div>

                                            <dl class="tramite-presentation-data">
                                                <div>
                                                    <dt>Tipo de envase</dt>
                                                    <dd>{{ $mostrarTexto($presentacion?->tipo_envase) }}</dd>
                                                </div>
                                                <div>
                                                    <dt>Cantidad</dt>
                                                    <dd>{{ $formatearDecimal($presentacion?->cantidad) }}</dd>
                                                </div>
                                                <div>
                                                    <dt>Unidad</dt>
                                                    <dd>{{ $mostrarTexto($presentacion?->catalogoUnidad?->nombre) }}</dd>
                                                </div>
                                                <div class="is-wide">
                                                    <dt>Descripción</dt>
                                                    <dd>{{ $mostrarTexto($presentacion?->descripcion) }}</dd>
                                                </div>
                                            </dl>
                                        </div>

                                        <div class="tramite-presentation-records">
                                            <div class="tramite-table-wrap">
                                                <table class="tramite-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Código de autorización</th>
                                                            <th>Número de lote</th>
                                                            <th>Vigencia</th>
                                                            <th>Cantidad registrada</th>
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
                                        </div>
                                    </article>
                                @endforeach
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
