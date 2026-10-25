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
                <div class="tramite-product-actions">
                    <button type="button" class="tramite-product-register-btn is-select"
                        data-open-product-selector>
                        <i class="fa-solid fa-list-check"></i>
                        Seleccionar producto registrado
                    </button>
                    <a href="{{ $urlRegistrarProductoTramite }}" class="tramite-product-register-btn">
                        <i class="fa-solid fa-plus"></i>
                        Registrar nuevo producto
                    </a>
                </div>
            @endif
        </div>

        @if ($urlRegistrarProductoTramite)
            <div class="tramite-product-selector-backdrop {{ $errors->has('id_producto') ? 'is-open' : '' }}"
                data-product-selector aria-hidden="{{ $errors->has('id_producto') ? 'false' : 'true' }}">
                <section class="tramite-product-selector" role="dialog" aria-modal="true"
                    aria-labelledby="titulo-selector-producto">
                    <header class="tramite-product-selector-head">
                        <div>
                            <h4 id="titulo-selector-producto">Seleccionar producto registrado</h4>
                            <p>Solo se muestran productos activos del beneficiario y todavía no asociados a otro trámite.</p>
                        </div>
                        <button type="button" class="tramite-product-selector-close" data-close-product-selector
                            aria-label="Cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </header>

                    <form method="POST"
                        action="{{ route('certificados_productos_vincular', ['certificado' => $certificado]) }}">
                        @csrf
                        <input type="hidden" name="bandeja" value="{{ request('bandeja', 'recibidas') }}">

                        <div class="tramite-product-selector-body">
                            @if ($productosDisponiblesTramite->isNotEmpty())
                                <label for="id_producto_existente" class="tramite-product-selector-label">
                                    Producto disponible
                                </label>
                                <select id="id_producto_existente" name="id_producto"
                                    class="tramite-product-selector-select" data-product-select required>
                                    <option value="">Seleccione un producto</option>
                                    @foreach ($productosDisponiblesTramite as $productoDisponible)
                                        <option value="{{ $productoDisponible->id }}"
                                            data-product-preview="producto-disponible-{{ $productoDisponible->id }}"
                                            @selected((string) old('id_producto') === (string) $productoDisponible->id)>
                                            {{ $mostrarTexto($productoDisponible->nombre_comercial) }} ·
                                            {{ $mostrarTexto($productoDisponible->codigo) }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="tramite-product-selector-help">
                                    Al confirmar se asociará el producto. Sus registros activos se vincularán solo si existen.
                                </p>

                                @foreach ($productosDisponiblesTramite as $productoDisponible)
                                    <dl id="producto-disponible-{{ $productoDisponible->id }}"
                                        class="tramite-product-preview" data-product-preview-panel hidden>
                                        <div>
                                            <dt>Nombre comercial</dt>
                                            <dd>{{ $mostrarTexto($productoDisponible->nombre_comercial) }}</dd>
                                        </div>
                                        <div>
                                            <dt>Código</dt>
                                            <dd>{{ $mostrarTexto($productoDisponible->codigo) }}</dd>
                                        </div>
                                        <div>
                                            <dt>Tipo de producto</dt>
                                            <dd>{{ \App\Models\Producto::TIPOS_PRODUCTO[$productoDisponible->tipo_producto] ?? $mostrarTexto($productoDisponible->tipo_producto) }}</dd>
                                        </div>
                                        <div>
                                            <dt>Importador</dt>
                                            <dd>{{ $nombrePersona($productoDisponible->importadorPersona) }}</dd>
                                        </div>
                                        <div>
                                            <dt>Presentaciones activas</dt>
                                            <dd>{{ $productoDisponible->presentaciones_activas_count }}</dd>
                                        </div>
                                        <div>
                                            <dt>Registros activos</dt>
                                            <dd>{{ $productoDisponible->registros_activos_count }}</dd>
                                        </div>
                                    </dl>
                                @endforeach
                            @else
                                <div class="tramite-product-selector-empty">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <div>
                                        <strong>No hay productos registrados disponibles.</strong>
                                        <span>Puede registrar uno nuevo para este beneficiario.</span>
                                    </div>
                                </div>
                            @endif

                            @error('id_producto')
                                <p class="tramite-product-selector-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <footer class="tramite-product-selector-footer">
                            <button type="button" class="tramite-product-selector-cancel"
                                data-close-product-selector>Cancelar</button>
                            @if ($productosDisponiblesTramite->isNotEmpty())
                                <button type="submit" class="tramite-product-selector-submit" data-product-submit disabled>
                                    <i class="fa-solid fa-link"></i>
                                    Asociar al trámite
                                </button>
                            @else
                                <a href="{{ $urlRegistrarProductoTramite }}" class="tramite-product-selector-submit">
                                    <i class="fa-solid fa-plus"></i>
                                    Registrar nuevo producto
                                </a>
                            @endif
                        </footer>
                    </form>
                </section>
            </div>
        @endif

        <div class="tramite-product-list">
            @forelse ($productosAsociadosTramite as $producto)
                @php
                    $grupoRegistros = $registrosPorProducto->get('producto_' . $producto->id, collect());
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
                                                        <dt>Tipo de presentación</dt>
                                                        <dd>{{ $mostrarTexto($presentacion->tipo_presentacion) }}</dd>
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
                                                    <table class="tramite-table tramite-presentation-records-table">
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

    @if ($urlRegistrarProductoTramite)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const backdrop = document.querySelector('[data-product-selector]');
                const selector = backdrop?.querySelector('[data-product-select]');
                const botonConfirmar = backdrop?.querySelector('[data-product-submit]');

                if (!backdrop) {
                    return;
                }

                const actualizarProducto = () => {
                    backdrop.querySelectorAll('[data-product-preview-panel]').forEach((panel) => {
                        panel.hidden = true;
                    });

                    const opcion = selector?.selectedOptions?.[0];
                    const idPanel = opcion?.dataset.productPreview;
                    const panel = idPanel ? document.getElementById(idPanel) : null;

                    if (panel) {
                        panel.hidden = false;
                    }

                    if (botonConfirmar) {
                        botonConfirmar.disabled = !selector?.value;
                    }
                };

                const abrir = () => {
                    backdrop.classList.add('is-open');
                    backdrop.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('has-product-selector-open');
                    actualizarProducto();
                    window.setTimeout(() => selector?.focus(), 0);
                };

                const cerrar = () => {
                    backdrop.classList.remove('is-open');
                    backdrop.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('has-product-selector-open');
                };

                document.querySelector('[data-open-product-selector]')?.addEventListener('click', abrir);
                backdrop.querySelectorAll('[data-close-product-selector]').forEach((boton) => {
                    boton.addEventListener('click', cerrar);
                });
                backdrop.addEventListener('click', (evento) => {
                    if (evento.target === backdrop) {
                        cerrar();
                    }
                });
                document.addEventListener('keydown', (evento) => {
                    if (evento.key === 'Escape' && backdrop.classList.contains('is-open')) {
                        cerrar();
                    }
                });
                selector?.addEventListener('change', actualizarProducto);

                if (backdrop.classList.contains('is-open')) {
                    abrir();
                }
            });
        </script>
    @endif
@endif
