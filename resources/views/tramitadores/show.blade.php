<x-admin-layout title="Detalle del tramitador | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Tramitadores', 'href' => route('tramitadores_index')],
    ['name' => 'Detalle', 'href' => '#'],
]">
    {{-- Reutiliza el mismo control de documentos usado al registrar responsables y tramitadores. --}}
    @include('personas.create.estilos')

    <style>
        /* Mantiene el selector de PDF dentro del ancho disponible, incluso antes del corte móvil. */
        .detalle-tramitador-pdf .responsable-modal-pdf {
            width: 100%;
            min-width: 0;
            height: auto;
            min-height: 42px;
            flex-wrap: wrap;
        }

        .detalle-tramitador-pdf .responsable-modal-pdf-info {
            flex: 1 1 14rem;
            max-width: 100%;
        }

        .detalle-tramitador-pdf .responsable-modal-pdf-info > div {
            min-width: 0;
        }

        .detalle-tramitador-pdf .responsable-modal-pdf-info strong {
            max-width: min(100%, 28rem);
        }

        @media (max-width: 640px) {
            .detalle-tramitador-pdf .responsable-modal-pdf-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .detalle-tramitador-pdf .responsable-modal-pdf-button {
                flex: 1 1 auto;
            }
        }
    </style>

    @php
        $natural = $tramitador->persona?->natural;
        $nombre = trim(implode(' ', array_filter([
            $natural?->nombres,
            $natural?->apellido_paterno,
            $natural?->apellido_materno,
        ]))) ?: 'Sin nombre';
        $estado = (string) ($tramitador->estado ?: 'SIN_ESTADO');
        $estadoTexto = match ($estado) {
            'ACTIVO', '1' => 'Activo',
            'PENDIENTE_VALIDACION' => 'Pendiente de validación',
            'INACTIVO' => 'Inactivo',
            'RECHAZADO' => 'Rechazado',
            default => str_replace('_', ' ', $estado),
        };
        $claseEstado = match ($estado) {
            'ACTIVO', '1' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'PENDIENTE_VALIDACION' => 'border-blue-200 bg-blue-50 text-blue-700',
            'RECHAZADO' => 'border-red-200 bg-red-50 text-red-700',
            default => 'border-slate-200 bg-slate-100 text-slate-700',
        };
        $fecha = static fn ($valor) => $valor
            ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y')
            : 'No aplica';
        $fechaHora = static fn ($valor) => $valor
            ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y H:i')
            : 'No aplica';
    @endphp

    <div class="w-full min-w-0 max-w-none space-y-4 sm:space-y-5">
        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <header class="flex min-w-0 flex-col gap-4 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div class="min-w-0">
                    <p class="text-sm text-slate-500">Tramitador</p>
                    <h1 class="break-words text-xl font-semibold text-slate-900">{{ $nombre }}</h1>
                </div>
                <span class="max-w-full self-start rounded-full border px-3 py-1.5 text-center text-xs font-bold {{ $claseEstado }} sm:self-center">
                    {{ $estadoTexto }}
                </span>
            </header>

            <section class="border-t border-slate-200 p-4 sm:p-6">
                <h2 class="text-base font-semibold text-slate-900">Información del tramitador</h2>
                <dl class="mt-4 grid min-w-0 grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Documento de identidad</dt>
                        <dd class="mt-1.5 break-words text-sm font-medium text-slate-900">
                            {{ $natural?->ci ?: 'Sin dato' }}{{ $natural?->complemento ? '-' . $natural->complemento : '' }}
                        </dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Correo electrónico</dt>
                        <dd class="mt-1.5 break-all text-sm font-medium text-slate-900">{{ $tramitador->persona?->correo ?: 'Sin dato' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="border-t border-slate-200 p-4 sm:p-6">
                <h2 class="text-base font-semibold text-slate-900">Relación con la empresa</h2>
                <dl class="mt-4 grid min-w-0 grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Empresa</dt>
                        <dd class="mt-1.5 break-words text-sm font-medium text-slate-900">{{ $tramitador->empresa?->razon_social ?: 'Sin empresa' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de registro</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">{{ $fechaHora($tramitador->fecha_registro) }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de baja</dt>
                        <dd class="mt-1.5 text-sm text-slate-900">{{ $fecha($tramitador->fecha_baja) }}</dd>
                    </div>
                    @if ($tramitador->fecha_baja && $tramitador->usuarioBaja)
                        <div class="min-w-0">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dado de baja por</dt>
                            <dd class="mt-1.5 break-words text-sm text-slate-900">{{ $tramitador->usuarioBaja->nombreCompleto() }}</dd>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Documento de respaldo o autorización</dt>
                        <dd class="mt-1.5">
                            @if ($tramitador->url_respaldo)
                                <a href="{{ route('tramitadores_carta', $tramitador) }}"
                                    class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-teal-500 hover:text-teal-700">
                                    Ver documento PDF
                                </a>
                            @else
                                <span class="text-sm text-slate-500">Sin documento</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>
        </article>

        @if ($puedeSolicitarNuevamente)
            <section class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm sm:p-6">
                <h2 class="text-base font-semibold text-slate-900">Solicitar nuevamente como tramitador</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Adjunte un nuevo documento de respaldo o autorización.
                </p>

                <form action="{{ route('tramitadores_solicitar_nuevamente', $tramitador) }}" method="POST" enctype="multipart/form-data"
                    class="detalle-tramitador-pdf mt-5 min-w-0 space-y-4">
                    @csrf
                    <div class="min-w-0">
                        <label for="form_url_respaldo" class="mb-1 block text-sm font-semibold text-slate-700">
                            Nuevo documento de respaldo o autorización
                        </label>
                        <input id="form_url_respaldo" name="form_url_respaldo" type="file" accept=".pdf,application/pdf" required class="sr-only">

                        <div id="nuevoRespaldoControl" class="responsable-modal-pdf">
                            <div class="responsable-modal-pdf-info">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <strong id="nuevoRespaldoNombre">Sin PDF seleccionado</strong>
                                    <span id="nuevoRespaldoEstado">Seleccione un documento PDF.</span>
                                </div>
                            </div>
                            <div class="responsable-modal-pdf-actions">
                                <label for="form_url_respaldo" class="responsable-modal-pdf-button is-select">
                                    <i class="fa-solid fa-upload" aria-hidden="true"></i><span>Seleccionar</span>
                                </label>
                                <button type="button" id="verNuevoRespaldo" class="responsable-modal-pdf-button is-view" disabled>
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i><span>Ver</span>
                                </button>
                                <button type="button" id="quitarNuevoRespaldo" class="responsable-modal-pdf-button is-remove" disabled>
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i><span>Quitar</span>
                                </button>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Formato PDF, máximo 5 MB.</p>
                        @error('form_url_respaldo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex justify-end">
                        <x-wire-button type="submit" class="w-full justify-center sm:w-auto" blue>
                            Enviar nueva solicitud
                        </x-wire-button>
                    </div>
                </form>
            </section>
        @endif

        <div class="flex justify-end">
            <a href="{{ route('tramitadores_index') }}" class="w-full rounded-lg border border-slate-300 px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">Volver</a>
        </div>
    </div>

    @if ($puedeSolicitarNuevamente)
        @push('js')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const input = document.getElementById('form_url_respaldo');
                    const nombre = document.getElementById('nuevoRespaldoNombre');
                    const estado = document.getElementById('nuevoRespaldoEstado');
                    const ver = document.getElementById('verNuevoRespaldo');
                    const quitar = document.getElementById('quitarNuevoRespaldo');
                    let urlTemporal = null;

                    const actualizarControl = (archivo = null, mensaje = 'Seleccione un documento PDF.') => {
                        nombre.textContent = archivo?.name || 'Sin PDF seleccionado';
                        estado.textContent = mensaje;
                        ver.disabled = !archivo;
                        quitar.disabled = !archivo;
                    };

                    input.addEventListener('change', () => {
                        const archivo = input.files[0];

                        if (!archivo) {
                            actualizarControl();
                            return;
                        }

                        const esPdf = archivo.type === 'application/pdf' || archivo.name.toLowerCase().endsWith('.pdf');
                        if (!esPdf) {
                            input.value = '';
                            actualizarControl(null, 'Solo se permiten archivos PDF.');
                            return;
                        }

                        if (urlTemporal) URL.revokeObjectURL(urlTemporal);
                        urlTemporal = URL.createObjectURL(archivo);
                        actualizarControl(archivo, 'Documento listo para enviar.');
                    });

                    ver.addEventListener('click', () => {
                        if (urlTemporal) window.open(urlTemporal, '_blank');
                    });

                    quitar.addEventListener('click', () => {
                        input.value = '';
                        if (urlTemporal) URL.revokeObjectURL(urlTemporal);
                        urlTemporal = null;
                        actualizarControl();
                    });
                });
            </script>
        @endpush
    @endif
</x-admin-layout>
