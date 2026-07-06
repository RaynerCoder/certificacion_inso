<x-admin-layout title="Detalle Responsable | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Responsables de Empresas', 'href' => route('responsables_index')],
    ['name' => 'Detalle'],
]">

    @php
        // Datos de empresa y persona responsable cargados desde el controlador.
        $empresa = $responsable->empresa;
        $personaEmpresa = $empresa?->persona;
        $persona = $responsable->persona;
        $natural = $persona?->natural;

        $nombreResponsable = trim(implode(' ', array_filter([
            $natural?->nombres,
            $natural?->apellido_paterno,
            $natural?->apellido_materno,
            $natural?->apellido_casado,
        ]))) ?: 'Responsable sin nombre';

        $genero = match ((string) ($natural?->genero ?? '')) {
            '1' => 'Masculino',
            '0' => 'Femenino',
            default => $natural?->genero ?: 'Sin dato',
        };

        $urlRespaldo = null;
        if ($responsable->url_respaldo) {
            $urlRespaldo = \Illuminate\Support\Str::startsWith($responsable->url_respaldo, ['http://', 'https://'])
                ? $responsable->url_respaldo
                : asset('storage/' . $responsable->url_respaldo);
        }
    @endphp

    <style>
        .resp-detail-section {
            border: 1px solid #dbe4ee;
            border-radius: 10px;
            background: #ffffff;
            overflow: hidden;
        }

        .resp-detail-title {
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 11px 14px;
            color: #0f172a;
            font-size: 12px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .resp-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 28px;
            padding: 15px;
        }

        .resp-detail-field {
            display: grid;
            grid-template-columns: minmax(120px, 34%) minmax(0, 1fr);
            gap: 10px;
            align-items: baseline;
        }

        .resp-detail-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 850;
        }

        .resp-detail-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: 850;
            overflow-wrap: anywhere;
        }

        .resp-detail-pill {
            display: inline-flex;
            border-radius: 999px;
            border: 1px solid #bbf7d0;
            background: #ecfdf5;
            color: #047857;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 950;
        }

        .resp-detail-action {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            padding: 0 16px;
            font-size: 14px;
            font-weight: 850;
            text-decoration: none;
        }

        .resp-detail-action.is-edit {
            border: 1px solid #0d9488;
            background: #0d9488;
            color: #ffffff;
        }

        .resp-detail-action.is-edit:hover {
            background: #0f766e;
        }

        .resp-detail-action.is-cancel {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
        }

        .resp-detail-action.is-cancel:hover {
            background: #f3f4f6;
        }

        @media (max-width: 900px) {
            .resp-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="space-y-4">
        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-xl font-black uppercase tracking-tight text-slate-900">
                    {{ $nombreResponsable }}
                </h1>
                <p class="mt-1 text-sm font-semibold text-slate-500">
                    Responsable de {{ $empresa?->razon_social ?: 'empresa sin nombre' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('responsables_edit', $responsable) }}" class="resp-detail-action is-edit">
                    Editar
                </a>
                <a href="{{ route('responsables_index') }}" class="resp-detail-action is-cancel">
                    Volver
                </a>
            </div>
        </div>

        <section class="resp-detail-section">
            <h2 class="resp-detail-title">
                <i class="fa-solid fa-building"></i>
                Empresa donde es responsable
            </h2>

            <div class="resp-detail-grid">
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Razon social</span>
                    <span class="resp-detail-value">{{ $empresa?->razon_social ?: 'Sin razon social' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">NIT</span>
                    <span class="resp-detail-value">{{ $personaEmpresa?->nit ?: 'Sin NIT' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Tipo empresa</span>
                    <span class="resp-detail-value">{{ $empresa?->tipoEmpresa?->descripcion ?: 'Sin tipo' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Matricula</span>
                    <span class="resp-detail-value">{{ $empresa?->matricula ?: 'Sin matricula' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Correo empresa</span>
                    <span class="resp-detail-value">{{ $personaEmpresa?->correo ?: 'Sin correo' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Territorio</span>
                    <span class="resp-detail-value">{{ $personaEmpresa?->territorio?->nombre ?: 'Sin territorio' }}</span>
                </div>
            </div>
        </section>

        <section class="resp-detail-section">
            <h2 class="resp-detail-title">
                <i class="fa-solid fa-user-tie"></i>
                Datos de la persona responsable
            </h2>

            <div class="resp-detail-grid">
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Nombre completo</span>
                    <span class="resp-detail-value">{{ $nombreResponsable }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">CI</span>
                    <span class="resp-detail-value">{{ $natural?->ci ?: 'Sin CI' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Complemento</span>
                    <span class="resp-detail-value">{{ $natural?->complemento ?: 'Sin dato' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Expedido</span>
                    <span class="resp-detail-value">{{ $natural?->expedido ?: 'Sin dato' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Correo</span>
                    <span class="resp-detail-value">{{ $persona?->correo ?: 'Sin correo' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Telefono</span>
                    <span class="resp-detail-value">{{ $persona?->telefonos?->first()?->numero ?: 'Sin telefono' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Genero</span>
                    <span class="resp-detail-value">{{ $genero }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Ocupacion</span>
                    <span class="resp-detail-value">{{ $natural?->ocupacion ?: 'Sin ocupacion' }}</span>
                </div>
            </div>
        </section>

        <section class="resp-detail-section">
            <h2 class="resp-detail-title">
                <i class="fa-solid fa-id-card-clip"></i>
                Asignacion como responsable
            </h2>

            <div class="resp-detail-grid">
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Rol</span>
                    <span class="resp-detail-value">{{ $responsable->rol?->name ?: 'Sin rol' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Estado</span>
                    <span class="resp-detail-value"><span class="resp-detail-pill">{{ $responsable->estado ?: 'SIN ESTADO' }}</span></span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Fecha registro</span>
                    <span class="resp-detail-value">{{ $responsable->fecha_registro ?: 'Sin fecha' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Fecha baja</span>
                    <span class="resp-detail-value">{{ $responsable->fecha_baja ?: 'Sin fecha' }}</span>
                </div>
                <div class="resp-detail-field">
                    <span class="resp-detail-label">Respaldo PDF</span>
                    <span class="resp-detail-value">
                        @if ($urlRespaldo)
                            <a href="{{ $urlRespaldo }}" target="_blank" class="font-black text-emerald-700 hover:text-emerald-900">
                                Ver PDF
                            </a>
                        @else
                            Sin respaldo
                        @endif
                    </span>
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
