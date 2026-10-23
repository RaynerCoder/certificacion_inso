@php
    $personaImportadora = $producto->importadorPersona;
    $empresaImportadora = $personaImportadora?->empresa;
    $nombreNatural = function ($persona) {
        return trim(implode(' ', array_filter([
            $persona?->natural?->nombres,
            $persona?->natural?->apellido_paterno,
            $persona?->natural?->apellido_materno,
        ])));
    };
    $estadoActivo = fn ($estado) => in_array(strtoupper(trim((string) $estado)), ['1', 'ACTIVO'], true);
    $formatearFormulaQuimica = static function ($formula) {
        $formula = trim((string) $formula);

        return $formula === '' ? '-' : preg_replace('/(\d+)/', '<sub>$1</sub>', e($formula));
    };

    $representantesLegales = collect();
    if ($empresaImportadora) {
        $representantesLegales = $empresaImportadora->responsables
            ->filter(fn ($responsable) => $estadoActivo($responsable->estado)
                && $responsable->rol?->slug === 'solicitante'
                && $estadoActivo($responsable->rol?->estado)
                && $responsable->persona
                && $estadoActivo($responsable->persona->estado)
                && $responsable->persona->natural)
            ->map(fn ($responsable) => $nombreNatural($responsable->persona))
            ->filter()
            ->unique()
            ->values();
    }

    $nombreImportador = $empresaImportadora?->razon_social
        ?: ($nombreNatural($personaImportadora) ?: 'Sin importador');
    $detalleImportador = $empresaImportadora
        ? ($representantesLegales->isNotEmpty()
            ? ($representantesLegales->count() === 1 ? 'Representante legal: ' : 'Representantes legales: ')
                . $representantesLegales->implode(', ')
            : 'Sin representante legal activo')
        : null;

    $unidadCompleta = function ($modelo) {
        $nombre = trim((string) $modelo?->catalogoUnidad?->nombre);
        $abreviatura = trim((string) $modelo?->catalogoUnidad?->abreviatura);

        if (!$nombre) {
            return 'No registrada';
        }

        return $abreviatura ? $nombre . ' (' . $abreviatura . ')' : $nombre;
    };
    // Los campos DECIMAL conservan su precisión en la base de datos, pero MySQL
    // devuelve todos los decimales definidos. Para la vista se eliminan solamente
    // los ceros finales, sin redondear ni convertir el valor a número flotante.
    $formatearCantidad = static function ($valor) {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '-';
        }

        if (str_contains($texto, '.')) {
            $texto = rtrim(rtrim($texto, '0'), '.');
        }

        return $texto === '-0' ? '0' : $texto;
    };
    $urlEtiqueta = fn ($presentacion) => $presentacion->url_etiqueta
        ? route('productos_presentaciones_etiqueta', $presentacion)
        : null;
    $registrosSinPresentacion = $producto->registros
        ->filter(fn ($registro) => !$registro->id_presentacion)
        ->values();
    $frasesRiesgo = $producto->frasesReglamentarias->where('tipo', 'RIESGO')->values();
    $frasesSeguridad = $producto->frasesReglamentarias->where('tipo', 'SEGURIDAD')->values();
@endphp

<x-admin-layout title="Detalle del producto | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Productos', 'href' => route('productos_index')],
    ['name' => $producto->nombre_comercial ?: 'Detalle'],
]">
    <x-slot name="action">
        <div class="flex flex-wrap items-center gap-2">
            <x-wire-button href="{{ route('productos_index') }}" secondary>Volver</x-wire-button>
            <x-wire-button href="{{ route('productos_edit', $producto) }}" blue>Editar producto</x-wire-button>
        </div>
    </x-slot>

    @include('productos.show_estilos')

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="producto-show">
        <section class="producto-show-section">
            <header class="producto-show-section-head">
                <span class="producto-show-section-number">1</span>
                <div>
                    <h1>Datos generales</h1>
                    <p>Información que identifica y clasifica el producto.</p>
                </div>
            </header>

            <dl class="producto-show-general-grid">
                <div><dt>Código</dt><dd>{{ $producto->codigo ?: 'No registrado' }}</dd></div>
                <div class="producto-show-field-wide"><dt>Nombre comercial</dt><dd>{{ $producto->nombre_comercial ?: 'No registrado' }}</dd></div>
                <div><dt>Estado</dt><dd><span class="producto-show-status {{ $producto->estado === 'ACTIVO' ? '' : 'is-inactive' }}">{{ $producto->estado ?: 'SIN ESTADO' }}</span></dd></div>
                <div><dt>Nombre técnico</dt><dd>{{ $producto->nombre_tecnico ?: 'No registrado' }}</dd></div>
                <div><dt>Tipo de producto</dt><dd>{{ \App\Models\Producto::TIPOS_PRODUCTO[$producto->tipo_producto] ?? ($producto->tipo_producto ?: 'No registrado') }}</dd></div>
                <div class="producto-show-field-wide">
                    <dt>Importador</dt>
                    <dd>{{ $nombreImportador }}</dd>
                    @if ($detalleImportador)
                        <span class="producto-show-field-detail">{{ $detalleImportador }}</span>
                    @endif
                </div>
                <div><dt>País de origen</dt><dd>{{ $producto->territorio?->nombre ?: 'No registrado' }}</dd></div>
                <div><dt>Fabricante</dt><dd>{{ $producto->fabricante?->razon_social ?: ($producto->fabricante?->nombre ?: 'No registrado') }}</dd></div>
                <div><dt>Clasificación química</dt><dd>{{ $producto->clasificacionQuimica?->nombre ?: 'No registrada' }}</dd></div>
                <div class="producto-show-field-wide"><dt>Observación toxicológica de muestra</dt><dd>{{ $producto->observacion_toxicologica_muestra ?: '-' }}</dd></div>
                <div class="producto-show-field-wide"><dt>Clasificación toxicológica</dt><dd>{{ trim(($producto->clasificacionToxicologica?->codigo ?? '') . ' ' . ($producto->clasificacionToxicologica?->descripcion ?? '')) ?: 'No registrada' }}</dd></div>
                <div><dt>Operaciones de aduana</dt><dd>{{ $producto->aduanas->count() }}</dd></div>
            </dl>
        </section>

        <section class="producto-show-section">
            <header class="producto-show-section-head">
                <span class="producto-show-section-number">2</span>
                <div>
                    <h2>Ingredientes ({{ $producto->ingredientesProductos->count() }})</h2>
                    <p>Ingredientes y porcentajes registrados para este producto.</p>
                </div>
            </header>

            <div class="producto-show-table-wrap">
                <table class="producto-show-table">
                    <thead><tr><th>#</th><th>Ingrediente</th><th>Fórmula química</th><th>Porcentaje</th></tr></thead>
                    <tbody>
                        @forelse ($producto->ingredientesProductos as $relacion)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $relacion->ingrediente?->nombre ?: 'Sin nombre' }}</strong></td>
                                <td>{!! $formatearFormulaQuimica($relacion->ingrediente?->formula_quimica) !!}</td>
                                <td>{{ rtrim(rtrim(number_format((float) $relacion->porcentaje, 5, '.', ''), '0'), '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="producto-show-empty">No hay ingredientes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="producto-show-section">
            <header class="producto-show-section-head">
                <span class="producto-show-section-number">3</span>
                <div>
                    <h2>Riesgos y seguridad</h2>
                    <p>Frases reglamentarias seleccionadas para este producto.</p>
                </div>
            </header>

            <div class="producto-show-frases-grid">
                <section class="producto-show-frases-group">
                    <h3>Frases de riesgo</h3>
                    <div class="producto-show-table-wrap">
                        <table class="producto-show-table">
                            <thead><tr><th>Código</th><th>Frase</th></tr></thead>
                            <tbody>
                                @forelse ($frasesRiesgo as $frase)
                                    <tr><td><strong>{{ $frase->codigo }}</strong></td><td>{{ $frase->descripcion }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="producto-show-empty">-</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="producto-show-frases-group">
                    <h3>Frases de seguridad</h3>
                    <div class="producto-show-table-wrap">
                        <table class="producto-show-table">
                            <thead><tr><th>Código</th><th>Frase</th></tr></thead>
                            <tbody>
                                @forelse ($frasesSeguridad as $frase)
                                    <tr><td><strong>{{ $frase->codigo }}</strong></td><td>{{ $frase->descripcion }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="producto-show-empty">-</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </section>

        <section class="producto-show-section">
            <header class="producto-show-section-head">
                <span class="producto-show-section-number">4</span>
                <div>
                    <h2>Presentaciones y registros vinculados</h2>
                    <p>Cada presentación muestra su etiqueta y únicamente los registros que le corresponden.</p>
                </div>
            </header>

            <div class="producto-show-presentations">
                @forelse ($producto->presentaciones as $presentacion)
                    @php
                        $etiqueta = $urlEtiqueta($presentacion);
                        $nombreArchivoEtiqueta = $presentacion->url_etiqueta ? basename($presentacion->url_etiqueta) : 'No registrada';
                        $esImagenEtiqueta = in_array(strtolower(pathinfo($presentacion->url_etiqueta ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
                        $registrosPresentacion = $producto->registros
                            ->where('id_presentacion', $presentacion->id)
                            ->values();
                    @endphp

                    <article class="producto-show-presentation">
                        <header class="producto-show-presentation-head">
                            <div>
                                <span>Presentación {{ $loop->iteration }}</span>
                                <h3>{{ $formatearCantidad($presentacion->cantidad) }} {{ $unidadCompleta($presentacion) }}</h3>
                            </div>
                            <span class="producto-show-status {{ $presentacion->estado === 'ACTIVO' ? '' : 'is-inactive' }}">{{ $presentacion->estado ?: 'SIN ESTADO' }}</span>
                        </header>

                        <div class="producto-show-presentation-body">
                            <div class="producto-show-label-preview">
                                @if ($etiqueta && $esImagenEtiqueta)
                                    <button type="button" class="producto-show-label-image" data-etiqueta-url="{{ $etiqueta }}" data-etiqueta-nombre="{{ $nombreArchivoEtiqueta }}" aria-label="Ampliar etiqueta de la presentación {{ $loop->iteration }}">
                                        <img src="{{ $etiqueta }}" alt="Etiqueta de la presentación {{ $loop->iteration }}">
                                        <span><i class="fa-solid fa-magnifying-glass-plus"></i></span>
                                    </button>
                                    <button type="button" class="producto-show-label-action" data-etiqueta-url="{{ $etiqueta }}" data-etiqueta-nombre="{{ $nombreArchivoEtiqueta }}">
                                        <i class="fa-regular fa-eye"></i> Ver etiqueta
                                    </button>
                                @elseif ($etiqueta)
                                    <a href="{{ $etiqueta }}" target="_blank" class="producto-show-file-link">Ver archivo de etiqueta</a>
                                @else
                                    <div class="producto-show-label-empty"><i class="fa-regular fa-image"></i><span>Sin imagen</span></div>
                                @endif
                                <span class="producto-show-file-name">{{ $nombreArchivoEtiqueta }}</span>
                            </div>

                            <div class="producto-show-presentation-data">
                                <span class="producto-show-block-label">Datos de la presentación</span>
                                <dl>
                                    <div><dt>Tipo de presentación</dt><dd>{{ $presentacion->tipo_presentacion ?: 'No registrado' }}</dd></div>
                                    <div><dt>Contenido</dt><dd>{{ $formatearCantidad($presentacion->cantidad) }} {{ $unidadCompleta($presentacion) }}</dd></div>
                                    <div><dt>Descripción</dt><dd>{{ $presentacion->descripcion ?: 'No registrada' }}</dd></div>
                                </dl>
                            </div>

                            <div class="producto-show-registers">
                                <div class="producto-show-registers-head">
                                    <h4>Registros de esta presentación</h4>
                                    <span>{{ $registrosPresentacion->count() }} {{ $registrosPresentacion->count() === 1 ? 'registro' : 'registros' }}</span>
                                </div>
                                <div class="producto-show-register-list">
                                    @forelse ($registrosPresentacion as $registro)
                                        @include('productos.registro_resumen', [
                                            'registro' => $registro,
                                            'numeroRegistro' => $loop->iteration,
                                            'subtituloRegistro' => 'Registro vinculado a esta presentación',
                                        ])
                                    @empty
                                        <div class="producto-show-empty-box">Esta presentación todavía no tiene registros asociados.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="producto-show-empty-box">No hay presentaciones registradas.</div>
                @endforelse

                @if ($registrosSinPresentacion->isNotEmpty())
                    <article class="producto-show-presentation">
                        <header class="producto-show-presentation-head">
                            <div><span>Registros pendientes de relación</span><h3>Sin presentación vinculada</h3></div>
                        </header>
                        <div class="producto-show-orphan-registers">
                            @foreach ($registrosSinPresentacion as $registro)
                                @include('productos.registro_resumen', [
                                    'registro' => $registro,
                                    'numeroRegistro' => $loop->iteration,
                                    'subtituloRegistro' => 'Registro sin presentación vinculada',
                                ])
                            @endforeach
                        </div>
                    </article>
                @endif
            </div>
        </section>
    </div>

    <div id="modalEtiquetaProducto" class="producto-show-modal" aria-hidden="true">
        <div class="producto-show-modal-dialog">
            <div class="producto-show-modal-head">
                <strong id="modalEtiquetaProductoNombre">Etiqueta</strong>
                <button type="button" data-cerrar-etiqueta>Cerrar</button>
            </div>
            <img id="modalEtiquetaProductoImagen" src="" alt="Vista ampliada de la etiqueta">
        </div>
    </div>

    @push('js')
        <script>
            const modalEtiquetaProducto = document.getElementById('modalEtiquetaProducto');
            const imagenEtiquetaProducto = document.getElementById('modalEtiquetaProductoImagen');
            const nombreEtiquetaProducto = document.getElementById('modalEtiquetaProductoNombre');

            document.querySelectorAll('[data-etiqueta-url]').forEach((boton) => {
                boton.addEventListener('click', () => {
                    imagenEtiquetaProducto.src = boton.dataset.etiquetaUrl;
                    nombreEtiquetaProducto.textContent = boton.dataset.etiquetaNombre || 'Etiqueta';
                    modalEtiquetaProducto.classList.add('is-open');
                    modalEtiquetaProducto.setAttribute('aria-hidden', 'false');
                });
            });

            function cerrarEtiquetaProducto() {
                modalEtiquetaProducto.classList.remove('is-open');
                modalEtiquetaProducto.setAttribute('aria-hidden', 'true');
                imagenEtiquetaProducto.src = '';
            }

            document.querySelector('[data-cerrar-etiqueta]')?.addEventListener('click', cerrarEtiquetaProducto);
            modalEtiquetaProducto?.addEventListener('click', (evento) => {
                if (evento.target === modalEtiquetaProducto) cerrarEtiquetaProducto();
            });
        </script>
    @endpush
</x-admin-layout>
