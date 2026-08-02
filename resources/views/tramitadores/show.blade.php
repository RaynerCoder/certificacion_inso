<x-admin-layout title="Detalle del tramitador | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Tramitadores', 'href' => route('tramitadores_index')],
    ['name' => 'Detalle', 'href' => '#'],
]">
    @php
        $natural = $tramitador->persona?->natural;
        $nombre = trim(implode(' ', array_filter([
            $natural?->nombres,
            $natural?->apellido_paterno,
            $natural?->apellido_materno,
        ]))) ?: 'Sin nombre';
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Tramitador</p>
                    <h1 class="text-xl font-semibold text-slate-900">{{ $nombre }}</h1>
                    <p class="mt-1 text-sm text-slate-600">CI: {{ $natural?->ci }}{{ $natural?->complemento ? '-' . $natural->complemento : '' }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                    {{ str_replace('_', ' ', $tramitador->estado ?: 'SIN ESTADO') }}
                </span>
            </div>

            <dl class="mt-6 grid gap-5 border-t border-slate-100 pt-6 md:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Correo</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $tramitador->persona?->correo ?: 'Sin correo' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cuenta de acceso</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $tramitador->persona?->id_usuario ? 'Cuenta existente' : 'Pendiente de habilitación por INSO' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de registro</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $tramitador->fecha_registro ?: 'Sin fecha' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de baja</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $tramitador->fecha_baja ?: 'No aplica' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Carta de autorización</dt>
                    <dd class="mt-1">
                        @if ($tramitador->url_respaldo)
                            <a href="{{ route('tramitadores_carta', $tramitador) }}" class="text-sm font-semibold text-teal-700 hover:underline">Descargar PDF</a>
                        @else
                            <span class="text-sm text-slate-500">Sin documento</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('tramitadores_index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver</a>
        </div>
    </div>
</x-admin-layout>
