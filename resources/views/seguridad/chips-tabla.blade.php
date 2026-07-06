@php
    // Vista reutilizable para mostrar listas relacionadas como chips compactos dentro de tablas.
    $coleccion = collect($items ?? [])->filter();
    $campo = $campo ?? 'nombre';
    $textoVacio = $vacio ?? 'Sin datos';
    $colores = ['is-blue', 'is-emerald', 'is-violet', 'is-amber', 'is-rose', 'is-cyan', 'is-slate'];
@endphp

@if ($coleccion->isEmpty())
    <span class="seg-table-empty">{{ $textoVacio }}</span>
@else
    <div class="seg-chip-list is-table seg-table-chip-wrap">
        @foreach ($coleccion as $item)
            @php
                $texto = is_scalar($item) ? $item : data_get($item, $campo);
                $baseColor = data_get($item, 'id') ?: crc32((string) $texto);
                $claseColor = $colores[abs((int) $baseColor) % count($colores)];
            @endphp

            @if ($texto)
                <span class="seg-chip {{ $claseColor }}" title="{{ $texto }}">{{ $texto }}</span>
            @endif
        @endforeach
    </div>
@endif
