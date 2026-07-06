@php
    // El modulo de tramitadores puede usar rutas propias cuando existan.
    // Mientras tanto, reutiliza las rutas de responsables porque la relacion se guarda en esa tabla.
    $registro = $tramitador ?? $responsable ?? null;
    $rutaVer = $registro && Route::has('tramitadores_show')
        ? route('tramitadores_show', $registro)
        : ($registro && Route::has('responsables_show') ? route('responsables_show', $registro) : '#');
    $rutaEditar = $registro && Route::has('tramitadores_edit')
        ? route('tramitadores_edit', $registro)
        : ($registro && Route::has('responsables_edit') ? route('responsables_edit', $registro) : '#');
@endphp

<div class="flex flex-wrap items-center gap-2">
    <a href="{{ $rutaVer }}"
        class="inline-flex min-h-8 items-center justify-center rounded-md border border-sky-200 bg-sky-50 px-3 text-xs font-bold text-sky-700 hover:bg-sky-100">
        Ver
    </a>

    <a href="{{ $rutaEditar }}"
        class="inline-flex min-h-8 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 px-3 text-xs font-bold text-emerald-700 hover:bg-emerald-100">
        Editar
    </a>
</div>
