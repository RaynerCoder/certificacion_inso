@php
    $nombre = $persona->empresa?->razon_social ?: trim(implode(' ', array_filter([
        $persona->natural?->nombres,
        $persona->natural?->apellido_paterno,
        $persona->natural?->apellido_materno,
    ])));
    $esTramitador = $persona->responsabilidadesEmpresariales->contains(fn ($relacion) =>
        in_array((string) $relacion->estado, ['1', 'ACTIVO', 'PENDIENTE_VALIDACION'], true)
        && $relacion->rol?->slug === 'tramitador'
        && (string) $relacion->rol?->estado === '1');
    $tipo = $persona->empresa ? 'Empresa' : ($esTramitador ? 'Tramitador' : 'Persona natural');
@endphp
<div class="min-w-0 leading-tight">
    <div class="font-semibold text-slate-800 whitespace-normal">{{ $nombre ?: 'Sin nombre' }}</div>
    <div class="mt-1 text-xs text-slate-500">{{ $tipo }}</div>
</div>
