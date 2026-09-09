@php
    $nombreImportador = $producto->importadorPersona?->empresa?->razon_social;

    if (!$nombreImportador) {
        $nombreImportador = trim(implode(' ', array_filter([
            $producto->importadorPersona?->natural?->nombres,
            $producto->importadorPersona?->natural?->apellido_paterno,
            $producto->importadorPersona?->natural?->apellido_materno,
        ])));
    }

    $nombreImportador = $nombreImportador ?: 'Sin importador';
    $claseEstado = $producto->estado === 'ACTIVO'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-slate-200 bg-slate-100 text-slate-600';

    $urlEtiqueta = fn ($presentacion) => $presentacion->url_etiqueta
        ? route('productos_presentaciones_etiqueta', $presentacion)
        : null;
@endphp

<x-admin-layout title="Detalle del producto | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Productos', 'href' => route('productos_index')],
    ['name' => $producto->nombre_comercial ?: 'Detalle'],
]">
    <x-slot name="action">
        <div class="flex flex-wrap items-center gap-2">
            <x-wire-button href="{{ route('productos_index') }}" secondary>
                Volver
            </x-wire-button>
            <x-wire-button href="{{ route('productos_edit', $producto) }}" blue>
                Editar producto
            </x-wire-button>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-5">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50 px-5 py-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-black text-slate-800">
                            {{ $producto->nombre_comercial ?: 'Producto sin nombre comercial' }}
                        </h1>
                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $claseEstado }}">
                            {{ $producto->estado ?: 'SIN ESTADO' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Código: <strong class="text-slate-700">{{ $producto->codigo ?: 'Sin código' }}</strong>
                    </p>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <strong>{{ $producto->presentaciones->count() }}</strong> presentaciones ·
                    <strong>{{ $producto->registros->count() }}</strong> registros
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    'Nombre técnico' => $producto->nombre_tecnico ?: 'No registrado',
                    'Tipo de producto' => \App\Models\Producto::TIPOS_PRODUCTO[$producto->tipo_producto] ?? ($producto->tipo_producto ?: 'No registrado'),
                    'Importador' => $nombreImportador,
                    'País' => $producto->territorio?->nombre ?: 'No registrado',
                    'Fabricante' => $producto->fabricante?->razon_social ?: ($producto->fabricante?->nombre ?: 'No registrado'),
                    'Clasificación química' => $producto->clasificacionQuimica?->nombre ?: 'No registrada',
                    'Clasificación toxicológica' => trim(($producto->clasificacionToxicologica?->codigo ?? '') . ' ' . ($producto->clasificacionToxicologica?->descripcion ?? '')) ?: 'No registrada',
                    'Operaciones de aduana' => $producto->aduanas->count(),
                ] as $etiqueta => $valor)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $etiqueta }}</p>
                        <p class="mt-2 text-sm font-bold text-slate-700">{{ $valor }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-800">Ingredientes</h2>
                <p class="text-sm text-slate-500">Composición registrada para este producto.</p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Ingrediente</th>
                            <th class="px-4 py-3">Porcentaje</th>
                            <th class="px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($producto->ingredientesProductos as $relacion)
                            <tr>
                                <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-bold text-slate-700">{{ $relacion->ingrediente?->nombre ?: 'Sin nombre' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ rtrim(rtrim(number_format((float) $relacion->porcentaje, 5, '.', ''), '0'), '.') }}%</td>
                                <td class="px-4 py-3 text-slate-600">{{ $relacion->estado }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No hay ingredientes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-800">Presentaciones</h2>
                <p class="text-sm text-slate-500">Formatos comerciales y etiquetas del producto.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($producto->presentaciones as $presentacion)
                    @php
                        $etiqueta = $urlEtiqueta($presentacion);
                        $nombreArchivoEtiqueta = $presentacion->url_etiqueta
                            ? basename($presentacion->url_etiqueta)
                            : 'No registrada';
                        $esImagenEtiqueta = in_array(
                            strtolower(pathinfo($presentacion->url_etiqueta ?? '', PATHINFO_EXTENSION)),
                            ['jpg', 'jpeg', 'png', 'webp'],
                            true
                        );
                    @endphp
                    <article class="overflow-hidden rounded-xl border border-slate-200 bg-white md:col-span-2 xl:col-span-1">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                            <h3 class="font-black text-slate-800">Presentación {{ $loop->iteration }}</h3>
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $presentacion->estado === 'ACTIVO' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-600' }}">
                                {{ $presentacion->estado ?: 'SIN ESTADO' }}
                            </span>
                        </div>

                        <div class="grid gap-4 p-4 sm:grid-cols-[112px_minmax(0,1fr)]">
                            <div>
                                <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">Imagen de la etiqueta</p>
                                @if ($etiqueta && $esImagenEtiqueta)
                                    <button type="button" class="h-28 w-28 overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
                                        data-etiqueta-url="{{ $etiqueta }}" data-etiqueta-nombre="{{ $nombreArchivoEtiqueta }}">
                                        <img src="{{ $etiqueta }}" alt="Etiqueta de la presentación {{ $loop->iteration }}" class="h-full w-full object-contain">
                                    </button>
                                @elseif ($etiqueta)
                                    <a href="{{ $etiqueta }}" target="_blank"
                                        class="flex h-28 w-28 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-center text-xs font-bold text-teal-700">
                                        Ver archivo de etiqueta
                                    </a>
                                @else
                                    <div class="flex h-28 w-28 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 text-center text-xs text-slate-400">
                                        Sin imagen registrada
                                    </div>
                                @endif
                            </div>

                            <dl class="grid min-w-0 gap-3 sm:grid-cols-2">
                                <div>
                                    <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Cantidad de la presentación</dt>
                                    <dd class="mt-1 font-bold text-slate-800">{{ $presentacion->cantidad ?? 'No registrada' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Unidad de la presentación</dt>
                                    <dd class="mt-1 font-bold text-slate-800">
                                        {{ $presentacion->catalogoUnidad?->nombre ?: 'No registrada' }}
                                        @if ($presentacion->catalogoUnidad?->abreviatura)
                                            ({{ $presentacion->catalogoUnidad->abreviatura }})
                                        @endif
                                    </dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Descripción de la presentación</dt>
                                    <dd class="mt-1 break-words text-sm text-slate-700">{{ $presentacion->descripcion ?: 'No registrada' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Nombre del archivo de etiqueta</dt>
                                    <dd class="mt-1 break-all text-sm text-slate-700">{{ $nombreArchivoEtiqueta }}</dd>
                                </div>
                            </dl>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                        No hay presentaciones registradas.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-black text-slate-800">Registros</h2>
                <p class="text-sm text-slate-500">Autorizaciones, lotes y vigencias relacionados.</p>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Código de autorización</th>
                            <th class="px-4 py-3">Nro. de lote</th>
                            <th class="px-4 py-3">Fecha de vigencia</th>
                            <th class="px-4 py-3">Cantidad registrada</th>
                            <th class="px-4 py-3">Unidad del registro</th>
                            <th class="px-4 py-3">Presentación vinculada</th>
                            <th class="px-4 py-3">Certificados vinculados</th>
                            <th class="px-4 py-3">Estado del registro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($producto->registros as $registro)
                            <tr>
                                <td class="px-4 py-3 font-bold text-slate-700">{{ $registro->codigo_autorizacion ?: 'Sin código' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $registro->numero_lote ?: 'Sin lote' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $registro->fecha_vigencia ?: 'Sin fecha' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $registro->cantidad ?? 'No registrada' }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $registro->catalogoUnidad?->nombre ?: 'No registrada' }}
                                    @if ($registro->catalogoUnidad?->abreviatura)
                                        ({{ $registro->catalogoUnidad->abreviatura }})
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    @if ($registro->presentacion)
                                        <strong class="block text-slate-700">
                                            {{ $registro->presentacion->cantidad ?? 'No registrada' }}
                                            {{ $registro->presentacion->catalogoUnidad?->nombre }}
                                        </strong>
                                        <span class="text-xs">{{ $registro->presentacion->descripcion ?: 'Sin descripción' }}</span>
                                    @else
                                        No vinculada
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $registro->certificadosRegistros->count() }} certificado(s)</td>
                                <td class="px-4 py-3 text-slate-600">{{ $registro->estado }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No hay registros asociados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="modalEtiquetaProducto" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" aria-hidden="true">
        <div class="w-full max-w-3xl rounded-2xl bg-white p-4 shadow-2xl">
            <div class="mb-3 flex items-center justify-between gap-3">
                <strong id="modalEtiquetaProductoNombre" class="text-slate-800">Etiqueta</strong>
                <button type="button" class="rounded-lg px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-cerrar-etiqueta>
                    Cerrar
                </button>
            </div>
            <img id="modalEtiquetaProductoImagen" src="" alt="Vista ampliada de la etiqueta" class="max-h-[75vh] w-full object-contain">
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
                    modalEtiquetaProducto.classList.remove('hidden');
                    modalEtiquetaProducto.classList.add('flex');
                    modalEtiquetaProducto.setAttribute('aria-hidden', 'false');
                });
            });

            function cerrarEtiquetaProducto() {
                modalEtiquetaProducto.classList.add('hidden');
                modalEtiquetaProducto.classList.remove('flex');
                modalEtiquetaProducto.setAttribute('aria-hidden', 'true');
                imagenEtiquetaProducto.src = '';
            }

            document.querySelector('[data-cerrar-etiqueta]')?.addEventListener('click', cerrarEtiquetaProducto);
            modalEtiquetaProducto?.addEventListener('click', (evento) => {
                if (evento.target === modalEtiquetaProducto) {
                    cerrarEtiquetaProducto();
                }
            });
        </script>
    @endpush
</x-admin-layout>
