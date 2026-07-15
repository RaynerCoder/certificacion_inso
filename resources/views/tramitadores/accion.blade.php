@php
    // Los tramitadores se guardan como responsables. Por eso se reutilizan sus vistas de detalle y edición.
    $registro = $tramitador ?? $responsable ?? null;
    $rutaVer = $registro && Route::has('tramitadores_show')
        ? route('tramitadores_show', $registro)
        : ($registro && Route::has('responsables_show') ? route('responsables_show', $registro) : '#');
    $rutaEditar = $registro && Route::has('tramitadores_edit')
        ? route('tramitadores_edit', $registro)
        : ($registro && Route::has('responsables_edit') ? route('responsables_edit', $registro) : '#');
@endphp

<div class="flex items-center space-x-2">
    <x-wire-button href="{{ $rutaVer }}" emerald xs>
        Ver
    </x-wire-button>

    <x-wire-button href="{{ $rutaEditar }}" blue xs>
        Editar
    </x-wire-button>

    @if ($registro && $registro->estado === 'ACTIVO' && Route::has('tramitadores_baja'))
        <form action="{{ route('tramitadores_baja', $registro) }}" method="POST"
            data-tramitador-baja
            data-tramitador-nombre="{{ $registro->nombre_tramitador ?: 'este tramitador' }}"
            data-tramites-pendientes="{{ (int) ($registro->tramites_pendientes ?? 0) }}">
            @csrf
            <x-wire-button type="submit" red xs>
                Dar de baja
            </x-wire-button>
        </form>
    @endif
</div>
