@php
    $rutaGuardada = $comprobante ?? $pago->getRawOriginal('comprobante') ?? $pago->comprobante;
    $rutaComprobante = preg_replace('#^/?storage/#', '', (string) $rutaGuardada);
    $archivoDisponible = filled($rutaComprobante)
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($rutaComprobante);
@endphp

@if ($archivoDisponible)
    <a href="{{ route('pagos_comprobante', $pago) }}"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
        <i class="fa-regular fa-file-pdf"></i>
        Ver PDF
    </a>
@elseif (filled($rutaGuardada))
    <span class="inline-flex rounded-full border border-red-300 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
        Archivo no disponible
    </span>
@else
    <span class="inline-flex rounded-full border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
        Sin PDF
    </span>
@endif
