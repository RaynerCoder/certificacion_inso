<x-admin-layout title="Validar tramitador | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Tramitadores', 'href' => route('tramitadores_index')],
    ['name' => 'Validar', 'href' => '#'],
]">
    @php
        $persona = $tramitador->persona;
        $natural = $persona?->natural;
        $nombre = trim(implode(' ', array_filter([
            $natural?->nombres,
            $natural?->apellido_paterno,
            $natural?->apellido_materno,
        ]))) ?: 'Sin nombre';
        $iniciales = collect([$natural?->nombres, $natural?->apellido_paterno])
            ->filter()
            ->map(fn ($parte) => mb_substr(trim($parte), 0, 1))
            ->implode('');
        $expedido = $natural?->expedido
            ? ($natural->expedido . ' - ' . (\App\Models\Natural::EXPEDIDOS[$natural->expedido] ?? $natural->expedido))
            : 'Sin dato';
        $genero = (string) $natural?->genero === '1'
            ? 'Masculino'
            : ((string) $natural?->genero === '0' ? 'Femenino' : 'Sin dato');
        $ocupacion = $natural?->ocupacionCob?->descripcion_ocupacion ?: 'Sin dato';
        $ubicacion = $persona?->territorio
            ? trim(($persona->territorio->ambito?->nombre ? $persona->territorio->ambito->nombre . ': ' : '') . $persona->territorio->nombre)
            : 'Sin dato';
        $fecha = static fn ($valor) => $valor
            ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y')
            : 'Sin dato';
        $fechaHora = static fn ($valor) => $valor
            ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y H:i')
            : 'Sin dato';
        $estado = $tramitador->estado ?: 'SIN_ESTADO';
        $claseEstado = match ($estado) {
            'ACTIVO' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'RECHAZADO' => 'border-red-200 bg-red-50 text-red-700',
            default => 'border-blue-200 bg-blue-50 text-blue-700',
        };
    @endphp

    <style>
        .validacion-tramitador-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 640px) {
            .validacion-tramitador-empresa,
            .validacion-tramitador-general,
            .validacion-tramitador-personal {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1180px) {
            .validacion-tramitador-layout {
                grid-template-columns: minmax(0, 1fr) 22rem;
                align-items: start;
            }

            .validacion-tramitador-panel {
                position: sticky;
                top: 1rem;
                align-self: start;
            }

            .validacion-tramitador-empresa {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .validacion-tramitador-general,
            .validacion-tramitador-personal {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>

    <form action="{{ route('tramitadores_update', $tramitador) }}" method="POST"
        class="w-full min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @csrf
        @method('PUT')

        <header class="flex min-w-0 flex-col gap-3 border-b border-slate-200 px-4 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="min-w-0">
                <h1 class="text-xl font-semibold text-slate-900">Validar tramitador</h1>
            </div>
            <span class="max-w-full self-start rounded-lg border px-3 py-1.5 text-center text-xs font-bold {{ $claseEstado }} sm:self-center">
                {{ str_replace('_', ' ', $estado) }}
            </span>
        </header>

        <div class="validacion-tramitador-layout min-w-0 gap-5 p-4 sm:p-6">
            <div class="min-w-0 overflow-hidden rounded-xl border border-slate-200">
                <section class="flex min-w-0 flex-col gap-4 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:p-6">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xl font-semibold uppercase text-slate-700">
                        {{ $iniciales ?: 'ST' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="break-words text-base font-bold text-slate-900 sm:text-lg">{{ $nombre }}</h2>
                        <div class="mt-3 grid min-w-0 grid-cols-1 gap-3 text-sm text-slate-600 md:grid-cols-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <svg class="h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M7 9h4M7 13h2M14 10h3M14 14h3"></path>
                                </svg>
                                <span class="shrink-0">CI:</span>
                                <strong class="break-all font-medium text-slate-800">{{ $natural?->ci ?: 'Sin dato' }}</strong>
                            </div>
                            <div class="flex min-w-0 items-center gap-2">
                                <svg class="h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="m4 7 8 6 8-6"></path>
                                </svg>
                                <span class="shrink-0">Correo:</span>
                                <strong class="break-all font-medium text-slate-800">{{ $persona?->correo ?: 'Sin dato' }}</strong>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-200 p-4 sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-base font-semibold text-slate-900">Solicitud que está revisando</h2>
                        <span class="self-start rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 sm:self-center">
                            Empresa solicitante
                        </span>
                    </div>
                    <dl class="validacion-tramitador-empresa mt-4 grid min-w-0 grid-cols-1 gap-5">
                        <div class="min-w-0 lg:border-r lg:border-slate-200 lg:pr-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Empresa de esta solicitud</dt>
                            <dd class="mt-1.5 break-words text-sm font-medium text-slate-900">{{ $tramitador->empresa?->razon_social ?: 'Sin empresa' }}</dd>
                        </div>
                        <div class="min-w-0 lg:border-r lg:border-slate-200 lg:pr-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de registro</dt>
                            <dd class="mt-1.5 text-sm text-slate-900">{{ $fechaHora($tramitador->fecha_registro) }}</dd>
                        </div>
                        <div class="min-w-0 lg:border-r lg:border-slate-200 lg:pr-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de baja</dt>
                            <dd class="mt-1.5 text-sm text-slate-900">{{ $tramitador->fecha_baja ? $fecha($tramitador->fecha_baja) : 'No aplica' }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Documento de respaldo o autorización</dt>
                            <dd class="mt-1.5">
                                @if ($tramitador->url_respaldo)
                                    <a href="{{ route('tramitadores_carta', $tramitador) }}"
                                        class="inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-teal-500 hover:text-teal-700">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M6 2h9l5 5v15H6z"></path>
                                            <path d="M14 2v6h6M9 13h6M9 17h6"></path>
                                        </svg>
                                        <span class="truncate">Ver documento PDF</span>
                                    </a>
                                @else
                                    <span class="text-sm text-slate-500">Sin documento</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-6 border-t border-slate-200 pt-5">
                        <h3 class="text-sm font-semibold text-slate-900">Empresas donde ya está habilitado</h3>

                        <div class="mt-3 space-y-2">
                            @forelse ($empresasHabilitadas as $relacionHabilitada)
                                <div class="flex min-w-0 flex-col gap-2 rounded-lg border border-emerald-200 bg-emerald-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-semibold text-slate-900">
                                            {{ $relacionHabilitada->empresa?->razon_social ?: 'Sin empresa' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Habilitado desde {{ $fechaHora($relacionHabilitada->fecha_registro) }}
                                        </p>
                                    </div>
                                    <span class="self-start rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 sm:self-center">
                                        Habilitado
                                    </span>
                                </div>
                            @empty
                                <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                    No está habilitado como tramitador para otra empresa.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-200 p-4 sm:p-6">
                    <h2 class="text-base font-semibold text-slate-900">Información general</h2>
                    <dl class="validacion-tramitador-general mt-4 grid min-w-0 grid-cols-1 gap-5">
                        @foreach ([
                            'Domicilio' => $persona?->domicilio,
                            'NIT' => $persona?->nit ?: 'No corresponde',
                            'Ubicación' => $ubicacion,
                        ] as $etiqueta => $valor)
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold text-slate-500">{{ $etiqueta }}</dt>
                                <dd class="mt-1 break-words text-sm text-slate-900">{{ filled($valor) ? $valor : 'Sin dato' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="border-t border-slate-200 p-4 sm:p-6">
                    <h2 class="text-base font-semibold text-slate-900">Datos personales</h2>
                    <dl class="validacion-tramitador-personal mt-4 grid min-w-0 grid-cols-1 gap-x-8 gap-y-5">
                        @foreach ([
                            'Nombres' => $natural?->nombres,
                            'Apellido paterno' => $natural?->apellido_paterno,
                            'Apellido materno' => $natural?->apellido_materno,
                            'Apellido de casado' => $natural?->apellido_casado,
                            'Complemento' => $natural?->complemento,
                            'Expedido' => $expedido,
                            'Fecha de nacimiento' => $natural?->fecha_nacimiento ? $fecha($natural->fecha_nacimiento) : null,
                            'Género' => $genero,
                            'Ocupación' => $ocupacion,
                        ] as $etiqueta => $valor)
                            <div class="min-w-0">
                                <dt class="text-xs font-semibold text-slate-500">{{ $etiqueta }}</dt>
                                <dd class="mt-1 break-words text-sm text-slate-900">{{ filled($valor) ? $valor : 'Sin dato' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="border-t border-slate-200">
                    <div class="grid min-w-0 grid-cols-1 border-b border-slate-200 lg:grid-cols-[16rem_minmax(0,1fr)]">
                        <h2 class="flex items-center gap-3 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-700 sm:px-6">
                            <svg class="h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.62a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.28-1.28a2 2 0 0 1 2.11-.45c.84.29 1.72.5 2.62.62A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            Teléfonos
                        </h2>
                        <div class="flex min-w-0 flex-wrap items-center gap-2 px-4 py-4 sm:px-6">
                            @forelse ($persona?->telefonos ?? collect() as $telefono)
                                <span class="inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5">
                                    <span class="break-all text-sm text-slate-900">{{ $telefono->numero }}</span>
                                    <span class="inline-flex shrink-0 items-center gap-1.5 text-xs font-medium text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        {{ ucfirst(mb_strtolower($telefono->estado ?: 'ACTIVO')) }}
                                    </span>
                                </span>
                            @empty
                                <span class="text-sm text-slate-500">Sin teléfonos registrados.</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="grid min-w-0 grid-cols-1 lg:grid-cols-[16rem_minmax(0,1fr)]">
                        <h2 class="flex items-center gap-3 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-700 sm:px-6">
                            <svg class="h-5 w-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M4 20V10h4v10M10 20V4h4v16M16 20v-7h4v7M2 20h20"></path>
                            </svg>
                            Actividad económica
                        </h2>
                        <div class="min-w-0 space-y-2 px-4 py-4 sm:px-6">
                            @forelse ($persona?->rubros ?? collect() as $rubro)
                                <p class="break-words text-sm text-slate-900">
                                    {{ $rubro->codigo_caeb }}{{ $rubro->codigo_caeb ? ' - ' : '' }}{{ $rubro->nombre }}
                                </p>
                            @empty
                                <span class="text-sm text-slate-500">Sin rubros registrados.</span>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>

            <aside class="validacion-tramitador-panel min-w-0">
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-4 sm:p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Decisión de validación</h2>

                        <div class="mt-6">
                            <x-wire-native-select label="Estado" id="form_estado" name="form_estado">
                                <option value="PENDIENTE_VALIDACION" @selected(old('form_estado', $tramitador->estado) === 'PENDIENTE_VALIDACION')>
                                    Pendiente de validación
                                </option>
                                <option value="ACTIVO" @selected(old('form_estado', $tramitador->estado) === 'ACTIVO')>Activo</option>
                                <option value="RECHAZADO" @selected(old('form_estado', $tramitador->estado) === 'RECHAZADO')>Rechazado</option>
                            </x-wire-native-select>
                            @error('form_estado')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div
                            class="mt-5 border-t border-slate-200 pt-5"
                            x-data="{ cambiarPassword: {{ old('form_cambiar_password') ? 'true' : 'false' }} }"
                        >
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="form_cambiar_password"
                                    value="1"
                                    x-model="cambiarPassword"
                                    @checked(old('form_cambiar_password'))
                                    class="mt-1 rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-500"
                                >
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">Asignar nueva contraseña</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                                        Si no marca esta opción, se conservará la contraseña actual.
                                    </span>
                                </span>
                            </label>

                            <div x-cloak x-show="cambiarPassword" class="mt-4 space-y-4">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-slate-700">Nueva contraseña</label>
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autocomplete="new-password"
                                        x-bind:disabled="!cambiarPassword"
                                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    >
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmar contraseña</label>
                                    <input
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        x-bind:disabled="!cambiarPassword"
                                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                    >
                                </div>

                                <p class="text-xs leading-5 text-slate-500">Debe tener al menos 8 caracteres.</p>
                            </div>
                        </div>

                        <p class="mt-5 text-sm leading-6 text-slate-500">
                            Revise la identidad y el respaldo antes de guardar.
                        </p>

                        @if ($tramitador->usuarioValidacion)
                            <dl class="mt-5 border-t border-slate-200 pt-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Validado por</dt>
                                <dd class="mt-1 break-words text-sm font-medium text-slate-900">
                                    {{ $tramitador->usuarioValidacion->nombreCompleto() }}
                                </dd>
                            </dl>
                        @endif

                        @if ($tramitador->fecha_baja && $tramitador->usuarioBaja)
                            <dl class="mt-5 border-t border-slate-200 pt-5">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dado de baja por</dt>
                                <dd class="mt-1 break-words text-sm font-medium text-slate-900">
                                    {{ $tramitador->usuarioBaja->nombreCompleto() }}
                                </dd>
                            </dl>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-3 border-t border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                        <x-wire-button href="{{ route('tramitadores_index') }}" class="w-full justify-center" secondary>Cancelar</x-wire-button>
                        <x-wire-button type="submit" class="w-full justify-center" emerald>Guardar</x-wire-button>
                    </div>
                </section>
            </aside>
        </div>
    </form>
</x-admin-layout>
