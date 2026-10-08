{{--
    Muestra los productos y registros asociados al trámite.
    Esta información solamente es visible para personal interno.

    Condición para personal interno
    Productos asociados
    Registros
    Ingredientes
    Presentaciones
--}}

{{-- Productos y registros técnicos son información interna y no se envían al solicitante. --}}
@if (!$esSolicitante && $requiereProductoTramite)
    <article class="tramite-detail-panel tramite-panel-productos is-wide">
        {{-- SECCION 2: productos asociados al tramite. Cada producto se despliega para no repetir informacion en pantalla. --}}
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
                                {{ $registroDisponible->producto?->nombre_comercial ?: 'Sin producto' }}
                                · Lote {{ $registroDisponible->numero_lote ?: 'Sin lote' }}
                                · {{ $registroDisponible->presentacion?->descripcion ?: 'Sin presentación' }}
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
                    $primerRegistro = $grupoRegistros->first();
                    $producto = $primerRegistro?->producto;
                    $presentacionesProducto = $producto?->presentaciones ?? collect();
                    $ingredientesProducto = $producto?->ingredientesProductos ?? collect();
                @endphp
                <details class="tramite-product is-color-{{ ($loop->index % 4) + 1 }}"
                    @if ($loop->first) open @endif>
                    <summary class="tramite-product-head">
                        <div>
                            <h4 class="tramite-product-title">
                                {{ $producto?->nombre_comercial ?? 'Producto sin nombre comercial' }}</h4>
                            <div class="tramite-product-meta">
                                {{ $producto?->codigo ?: 'Sin código' }} · {{ $grupoRegistros->count() }}
                                registro{{ $grupoRegistros->count() === 1 ? '' : 's' }}
                            </div>
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
                                    <dt>Clasificación toxicológica</dt>
                                    <dd>{{ $producto?->clasificacionToxicologica?->descripcion ?? 'Sin clasificación toxicológica' }}
                                    </dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Nombre técnico</dt>
                                    <dd>{{ $producto?->nombre_tecnico ?: 'Sin dato' }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Clasificación química</dt>
                                    <dd>{{ $producto?->clasificacionQuimica?->nombre ?: 'Sin clasificación química' }}
                                    </dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Fabricante</dt>
                                    <dd>{{ $producto?->fabricante?->nombre ?? 'Sin fabricante' }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>País / territorio</dt>
                                    <dd>{{ $producto?->territorio?->nombre ?? 'Sin país' }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Importador</dt>
                                    <dd>{{ $nombrePersona($producto?->importadorPersona) }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="tramite-product-section">
                            <h4 class="tramite-product-section-title">
                                <i class="fa-solid fa-clipboard-list"></i>
                                Registros
                            </h4>
                            <div class="tramite-table-wrap">
                                <table class="tramite-table">
                                    <thead>
                                        <tr>
                                            <th>Registro</th>
                                            <th>Vigencia</th>
                                            <th>Cantidad registro</th>
                                            <th>Presentación</th>
                                            <th>Etiqueta</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($grupoRegistros as $registro)
                                            @php
                                                $presentacionRegistro = $registro->presentacion;
                                                $etiquetaRegistro = $urlArchivo($presentacionRegistro?->url_etiqueta);
                                            @endphp
                                            <tr>
                                                <td>{{ $registro->codigo_autorizacion ?: 'Sin código' }}</td>
                                                <td>{{ $registro->fecha_vigencia ? \Illuminate\Support\Carbon::parse($registro->fecha_vigencia)->format('d/m/Y') : 'Sin vigencia' }}
                                                </td>
                                                <td>{{ trim(($registro->cantidad ?? '') . ' ' . ($registro->catalogoUnidad?->nombre ?? '')) ?: 'Sin cantidad' }}
                                                </td>
                                                <td>
                                                    {{ trim(($presentacionRegistro?->cantidad ?? '') . ' ' . ($presentacionRegistro?->catalogoUnidad?->nombre ?? '')) ?: 'Sin cantidad' }}
                                                    @if ($presentacionRegistro?->descripcion)
                                                        <div class="text-xs font-semibold text-slate-500">
                                                            {{ $presentacionRegistro->descripcion }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($etiquetaRegistro)
                                                        <a href="{{ $etiquetaRegistro }}" target="_blank"
                                                            class="tramite-doc-link">
                                                            <i class="fa-regular fa-file-pdf"></i>
                                                            Ver etiqueta
                                                        </a>
                                                    @else
                                                        <span class="tramite-pill tramite-pill-warn">Sin etiqueta</span>
                                                    @endif
                                                </td>
                                                <td>{{ $registro->estado ?? 'Sin estado' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
                                                <td>{{ $ingredienteProducto->ingrediente?->nombre ?? 'Sin ingrediente' }}
                                                </td>
                                                <td>{{ $ingredienteProducto->ingrediente?->composicion ?? 'Sin composición' }}
                                                </td>
                                                <td>{{ $ingredienteProducto->ingrediente?->riesgo_salud ?? 'Sin dato' }}
                                                </td>
                                                <td>{{ $ingredienteProducto->porcentaje !== null ? $ingredienteProducto->porcentaje . '%' : 'Sin porcentaje' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">Este producto no tiene
                                                    ingredientes registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="tramite-product-section">
                            <h4 class="tramite-product-section-title">
                                <i class="fa-solid fa-box-open"></i>
                                Presentaciones
                            </h4>
                            <div class="tramite-table-wrap">
                                <table class="tramite-table">
                                    <thead>
                                        <tr>
                                            <th>Presentación</th>
                                            <th>Descripción</th>
                                            <th>Etiqueta</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($presentacionesProducto as $presentacionProducto)
                                            @php
                                                $etiquetaPresentacion = $urlArchivo(
                                                    $presentacionProducto->url_etiqueta,
                                                );
                                            @endphp
                                            <tr>
                                                <td>{{ trim(($presentacionProducto->cantidad ?? '') . ' ' . ($presentacionProducto->catalogoUnidad?->nombre ?? '')) ?: 'Sin cantidad' }}
                                                </td>
                                                <td>{{ $presentacionProducto->descripcion ?: 'Sin descripción' }}</td>
                                                <td>
                                                    @if ($etiquetaPresentacion)
                                                        <a href="{{ $etiquetaPresentacion }}" target="_blank"
                                                            class="tramite-doc-link">
                                                            <i class="fa-regular fa-file-pdf"></i>
                                                            Ver documento
                                                        </a>
                                                    @else
                                                        <span class="tramite-pill tramite-pill-warn">Sin PDF</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">Este producto no tiene
                                                    presentaciones registradas.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
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
