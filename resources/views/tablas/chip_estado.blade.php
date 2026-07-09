@php
    $valor = $texto ?? $estado ?? 'Sin estado';

    if (is_numeric($valor)) {
        $valor = (string) $valor === '1' ? 'Activo' : 'Inactivo';
    }

    $textoChip = ucfirst(strtolower(str_replace('_', ' ', (string) $valor)));

    $claseChip = $clase ?? match (strtoupper((string) ($estado ?? $valor))) {
        '1', 'ACTIVO', 'APROBADO', 'FINALIZADO' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        '0', 'INACTIVO', 'RECHAZADO', 'ANULADO' => 'border-rose-200 bg-rose-50 text-rose-700',
        'PENDIENTE', 'PENDIENTE DE REVISION', 'OBSERVADO' => 'border-amber-200 bg-amber-50 text-amber-700',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };
@endphp

<span class="tabla-chip inline-flex max-w-[130px] items-center justify-center rounded-full border px-2.5 py-1 text-[11px] font-bold leading-none whitespace-nowrap overflow-hidden text-ellipsis {{ $claseChip }}">
    {{ $textoChip }}
</span>
