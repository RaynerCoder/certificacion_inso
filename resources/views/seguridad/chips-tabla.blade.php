@php
    // Vista reutilizable para mostrar listas relacionadas como chips compactos dentro de tablas.
    $coleccion = collect($items ?? [])->filter();
    $campo = $campo ?? 'nombre';
    $textoVacio = $vacio ?? 'Sin datos';
    $limite = isset($limite) ? (int) $limite : null;
    $tituloModal = $tituloModal ?? 'Detalle';
    $colores = ['is-blue', 'is-emerald', 'is-violet', 'is-amber', 'is-rose', 'is-cyan', 'is-slate'];
    $itemsVisibles = $limite ? $coleccion->take($limite) : $coleccion;
    $itemsOcultos = $limite ? max($coleccion->count() - $limite, 0) : 0;
    $modalId = 'seg_modal_chips_' . md5($tituloModal . '_' . $campo . '_' . $coleccion->pluck('id')->join('_'));
@endphp

@if ($coleccion->isEmpty())
    <span class="seg-table-empty">{{ $textoVacio }}</span>
@else
    <div class="seg-chip-list is-table seg-table-chip-wrap">
        @foreach ($itemsVisibles as $item)
            @php
                $texto = is_scalar($item) ? $item : data_get($item, $campo);
                $baseColor = data_get($item, 'id') ?: crc32((string) $texto);
                $claseColor = $colores[abs((int) $baseColor) % count($colores)];
            @endphp

            @if ($texto)
                <span class="seg-chip {{ $claseColor }}" title="{{ $texto }}">{{ $texto }}</span>
            @endif
        @endforeach

        @if ($itemsOcultos > 0)
            <button type="button" class="seg-chip is-slate"
                onclick="document.getElementById('{{ $modalId }}')?.classList.remove('hidden'); document.getElementById('{{ $modalId }}')?.classList.add('flex')">
                + {{ $itemsOcultos }} permisos
            </button>
        @endif
    </div>

    @if ($itemsOcultos > 0)
        <div id="{{ $modalId }}" class="seg-modal hidden">
            <div class="seg-modal-box" style="width: min(100%, 720px);">
                <div class="seg-modal-head">
                    <div>
                        <h2 class="seg-modal-title">{{ $tituloModal }}</h2>
                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            {{ $coleccion->count() }} permisos asignados
                        </p>
                    </div>

                    <button type="button" class="seg-modal-close"
                        onclick="document.getElementById('{{ $modalId }}')?.classList.add('hidden'); document.getElementById('{{ $modalId }}')?.classList.remove('flex')">
                        x
                    </button>
                </div>

                <div class="p-4">
                    <div class="seg-chip-list">
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
                </div>
            </div>
        </div>
    @endif
@endif
