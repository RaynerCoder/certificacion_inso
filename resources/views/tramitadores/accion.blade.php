@php
    $registro = $tramitador ?? null;
    $usuarioActual = auth()->user()?->loadMissing('persona.empresa');
    $esUsuarioEmpresa = filled($usuarioActual?->empresaDeAccesoActiva());
    $puedeDarDeBaja = $registro
        && in_array((string) $registro->estado, ['1', 'ACTIVO'], true)
        && (
            ($esUsuarioEmpresa && $usuarioActual?->puede('tramitadores.ver'))
            || (! $esUsuarioEmpresa && $usuarioActual?->puede('tramitadores.validar'))
        );
@endphp

<div class="flex flex-wrap items-center gap-2">
    @if ($registro)
        @if ($esUsuarioEmpresa)
            <x-wire-button href="{{ route('tramitadores_show', $registro) }}" emerald xs>
                Ver
            </x-wire-button>
        @endif

        @permiso('tramitadores.validar')
            @if (! $esUsuarioEmpresa)
                <x-wire-button href="{{ route('tramitadores_edit', $registro) }}" blue xs>
                    Validar
                </x-wire-button>
            @endif
        @endpermiso

        @if ($puedeDarDeBaja)
            <form action="{{ route('tramitadores_baja', $registro) }}" method="POST"
                data-tramitador-baja
                data-tramitador-nombre="{{ $registro->nombre_tramitador ?: 'este tramitador' }}"
                data-tramitador-empresa="{{ $registro->nombre_empresa ?: 'esta empresa' }}"
                data-tramites-pendientes="{{ (int) ($registro->tramites_pendientes ?? 0) }}">
                @csrf
                <x-wire-button type="submit" red xs>Dar de baja</x-wire-button>
            </form>
        @endif
    @endif
</div>
