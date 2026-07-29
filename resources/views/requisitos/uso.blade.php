<div class="contents" data-uso-requisito>
    <x-wire-button type="button" green xs
        data-ver-uso-requisito
        data-requisito="{{ $requisito->descripcion }}"
        data-cantidad="{{ $requisito->tipos_certificados_count }}">
        Ver Uso
    </x-wire-button>

    {{-- El modal reutiliza este contenido sin realizar una consulta adicional. --}}
    <template data-contenido-uso-requisito>
        @forelse ($requisito->tiposCertificados as $tipoCertificado)
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-2 last:border-b-0">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="shrink-0 text-xs font-semibold text-slate-500">
                        ID {{ $tipoCertificado->id }}
                    </span>
                    <p class="min-w-0 truncate text-sm font-semibold text-slate-800"
                        title="{{ $tipoCertificado->nombre }}">
                        {{ $tipoCertificado->nombre }}
                    </p>
                </div>

                <span @class([
                    'shrink-0 rounded-full border px-2.5 py-1 text-xs font-semibold',
                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $tipoCertificado->estado === 'ACTIVO',
                    'border-rose-200 bg-rose-50 text-rose-700' => $tipoCertificado->estado !== 'ACTIVO',
                ])>
                    {{ ucfirst(strtolower($tipoCertificado->estado)) }}
                </span>
            </div>
        @empty
            <div class="px-5 py-8 text-center">
                <p class="text-sm font-semibold text-slate-700">Este requisito no está incluido en ningún certificado.</p>
                <p class="mt-1 text-xs text-slate-500">Puede editarse sin afectar la configuración de un certificado.</p>
            </div>
        @endforelse
    </template>
</div>
