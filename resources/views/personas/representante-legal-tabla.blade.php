@php
    $natural = $responsable?->persona?->natural;
    $nombre = trim(implode(' ', array_filter([
        $natural?->nombres,
        $natural?->apellido_paterno,
        $natural?->apellido_materno,
    ])));
@endphp

@if ($responsable && $nombre !== '')
    <div class="min-w-0 leading-tight">
        <div class="font-semibold text-slate-800 whitespace-normal">{{ $nombre }}</div>
        <div class="mt-1 text-xs text-slate-500">CI: {{ $natural?->ci ?: '-' }}</div>
    </div>
@else
    <span class="text-slate-500">Sin representante legal</span>
@endif
