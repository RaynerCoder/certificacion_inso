@if ($certificado)
    <div class="min-w-44">
        <span class="block font-semibold text-slate-800 dark:text-slate-100">{{ $certificado->codigo }}</span>
    </div>
@else
    <span class="text-slate-500">Sin trámite relacionado</span>
@endif
