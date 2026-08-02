@php($registro = $tramitador ?? null)

<div class="flex items-center gap-2">
    @if ($registro)
        <x-wire-button href="{{ route('tramitadores_show', $registro) }}" emerald xs>
            Ver
        </x-wire-button>

        @permiso('tramitadores.ver')
            @if (in_array((string) $registro->estado, ['1', 'ACTIVO'], true))
                <form action="{{ route('tramitadores_baja', $registro) }}" method="POST"
                    data-tramitador-baja
                    data-tramitador-nombre="{{ $registro->nombre_tramitador ?: 'este tramitador' }}"
                    data-tramites-pendientes="{{ (int) ($registro->tramites_pendientes ?? 0) }}">
                    @csrf
                    <x-wire-button type="submit" red xs>Dar de baja</x-wire-button>
                </form>
            @endif
        @endpermiso
    @endif
</div>
