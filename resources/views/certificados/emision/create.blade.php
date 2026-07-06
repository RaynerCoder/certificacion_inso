<x-admin-layout title="Emitir Certificado | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Trámites', 'href' => route('seguimientos_index')],
    ['name' => 'Detalle', 'href' => route('certificados_show', ['certificado' => $certificado, 'bandeja' => request('bandeja', 'recibidas')])],
    ['name' => 'Emitir certificado', 'href' => route('certificados_emitir', $certificado)],
]">
    @php
        // Nombre visible: razon social para empresa o nombre completo para persona natural.
        $nombrePersona = function ($persona) {
            if (!$persona) {
                return 'Sin persona';
            }

            if ($persona->empresa) {
                return $persona->empresa->razon_social ?: 'Sin razón social';
            }

            if ($persona->natural) {
                return trim(implode(' ', array_filter([
                    $persona->natural->nombres,
                    $persona->natural->apellido_paterno,
                    $persona->natural->apellido_materno,
                ]))) ?: 'Sin nombre';
            }

            return 'Persona #' . $persona->id;
        };

        // Identificación visible según el tipo de persona.
        $identificacionPersona = function ($persona) {
            if (!$persona) {
                return 'Sin dato';
            }

            if ($persona->empresa) {
                return $persona->nit ?: 'Sin NIT';
            }

            return $persona->natural?->ci ?: ($persona->nit ?: 'Sin CI/NIT');
        };

        // Nombre completo del funcionario que revisó o registró.
        $nombreFuncionario = function ($usuario, string $fallback = 'Sin funcionario') {
            $usuario?->loadMissing('funcionario');
            $funcionario = $usuario?->funcionario;

            if (!$funcionario) {
                return $usuario?->email ?: $fallback;
            }

            return trim(implode(' ', array_filter([
                $funcionario->nombres,
                $funcionario->apellido_paterno,
                $funcionario->apellido_materno,
            ]))) ?: $fallback;
        };

        // Cargo del funcionario, si existe.
        $cargoFuncionario = function ($usuario) {
            $usuario?->loadMissing('funcionario.cargos');

            return $usuario?->funcionario?->cargos?->pluck('nombre')->filter()->implode(', ') ?: 'Sin cargo';
        };

        $estadoClase = \App\Models\Certificado::claseEstadoCertificado($certificado->estado);
        $estadoTexto = \App\Models\Certificado::textoEstadoCertificado($certificado->estado);
        $ultimoRevisor = $certificado->certificadoRequisitos
            ->pluck('usuarioRevisor')
            ->filter()
            ->last();
        $registrosPorProducto = $certificado->registros->groupBy(
            fn ($registro) => $registro->producto?->id ? 'producto_' . $registro->producto->id : 'registro_' . $registro->id,
        );
    @endphp

    <style>
        .emit-shell {
            color: #0f172a;
            display: grid;
            gap: 16px;
        }

        .emit-header {
            align-items: flex-start;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
        }

        .emit-title {
            font-size: 1.35rem;
            font-weight: 950;
            letter-spacing: 0;
        }

        .emit-subtitle {
            color: #64748b;
            font-size: 0.86rem;
            font-weight: 700;
            margin-top: 4px;
        }

        .emit-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .emit-btn {
            align-items: center;
            border-radius: 7px;
            display: inline-flex;
            font-size: 0.82rem;
            font-weight: 900;
            gap: 8px;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            white-space: nowrap;
        }

        .emit-btn-primary {
            background: #059669;
            border: 1px solid #047857;
            color: #ffffff;
        }

        .emit-btn-muted {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .emit-btn-info {
            background: #0f766e;
            border: 1px solid #115e59;
            color: #ffffff;
        }

        .emit-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr);
        }

        .emit-section {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            overflow: hidden;
        }

        .emit-section-head {
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            min-height: 46px;
            padding: 0 16px;
        }

        .emit-section-head i {
            color: #059669;
        }

        .emit-section-title {
            font-size: 0.92rem;
            font-weight: 950;
        }

        .emit-section-body {
            padding: 16px;
        }

        .emit-definition {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .emit-field {
            border-left: 3px solid #d1fae5;
            padding-left: 10px;
        }

        .emit-field dt {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .emit-field dd {
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 800;
            margin-top: 3px;
        }

        .emit-chip {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.72rem;
            font-weight: 950;
            gap: 6px;
            padding: 5px 10px;
        }

        .emit-table {
            border-collapse: collapse;
            width: 100%;
        }

        .emit-table th,
        .emit-table td {
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.82rem;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        .emit-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 950;
            text-transform: uppercase;
        }

        .emit-preview {
            background: #f8fafc;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            padding: 14px;
        }

        .emit-paper {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
            min-height: 520px;
            padding: 26px;
        }

        .emit-paper-head {
            align-items: center;
            border-bottom: 2px solid #047857;
            display: flex;
            gap: 12px;
            padding-bottom: 14px;
        }

        .emit-logo {
            align-items: center;
            border: 2px solid #10b981;
            border-radius: 8px;
            color: #047857;
            display: inline-flex;
            font-size: 0.78rem;
            font-weight: 950;
            height: 46px;
            justify-content: center;
            width: 46px;
        }

        .emit-paper-title {
            font-size: 1.05rem;
            font-weight: 950;
            text-transform: uppercase;
        }

        .emit-paper-subtitle {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .emit-paper-content {
            display: grid;
            gap: 14px;
            margin-top: 22px;
        }

        .emit-paper-line {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .emit-paper-line span {
            color: #64748b;
            display: block;
            font-size: 0.7rem;
            font-weight: 950;
            text-transform: uppercase;
        }

        .emit-paper-line strong {
            display: block;
            font-size: 0.94rem;
            margin-top: 4px;
        }

        .emit-paper-foot {
            align-items: end;
            display: flex;
            justify-content: space-between;
            margin-top: 48px;
        }

        .emit-qr {
            align-items: center;
            border: 1px dashed #94a3b8;
            border-radius: 6px;
            color: #64748b;
            display: inline-flex;
            font-size: 0.72rem;
            font-weight: 900;
            height: 72px;
            justify-content: center;
            width: 72px;
        }

        .emit-signature {
            border-top: 1px solid #0f172a;
            font-size: 0.75rem;
            font-weight: 900;
            padding-top: 8px;
            text-align: center;
            width: 180px;
        }

        @media (max-width: 1024px) {
            .emit-grid,
            .emit-definition {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="emit-shell">
        <header class="emit-header">
            <div>
                <h1 class="emit-title">Emitir certificado</h1>
                <p class="emit-subtitle">Revise la información aprobada antes de registrar la emisión.</p>
            </div>

            <div class="emit-actions">
                <a href="{{ route('certificados_show', ['certificado' => $certificado, 'bandeja' => request('bandeja', 'recibidas')]) }}"
                    class="emit-btn emit-btn-muted">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver al trámite
                </a>

                @if ($certificado->estado !== 'EMITIDO')
                    <form action="{{ route('certificados_emitir_guardar', $certificado) }}" method="POST">
                        @csrf
                        <button type="submit" class="emit-btn emit-btn-primary">
                            <i class="fa-solid fa-file-circle-check"></i>
                            Emitir certificado
                        </button>
                    </form>
                @else
                    <form action="{{ route('certificados_enviar_solicitante', $certificado) }}" method="POST">
                        @csrf
                        <button type="submit" class="emit-btn emit-btn-info">
                            <i class="fa-solid fa-paper-plane"></i>
                            Enviar al solicitante
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <div class="emit-grid">
            <div class="space-y-4">
                <section class="emit-section">
                    <div class="emit-section-head">
                        <i class="fa-regular fa-file-lines"></i>
                        <h2 class="emit-section-title">Datos del trámite</h2>
                    </div>
                    <div class="emit-section-body">
                        <dl class="emit-definition">
                            <div class="emit-field">
                                <dt>Código</dt>
                                <dd>{{ $certificado->codigo ?: 'Sin código' }}</dd>
                            </div>
                            <div class="emit-field">
                                <dt>Tipo de certificado</dt>
                                <dd>{{ $certificado->tipoCertificado?->nombre ?? 'Sin tipo' }}</dd>
                            </div>
                            <div class="emit-field">
                                <dt>Estado</dt>
                                <dd>
                                    <span class="emit-chip {{ $estadoClase }}">
                                        <i class="{{ \App\Models\Certificado::iconoEstadoCertificado($certificado->estado) }}"></i>
                                        {{ $estadoTexto }}
                                    </span>
                                </dd>
                            </div>
                            <div class="emit-field">
                                <dt>Revisor</dt>
                                <dd>
                                    {{ $nombreFuncionario($ultimoRevisor, 'Sin revisor') }}
                                    <span class="block text-xs font-semibold text-slate-500">
                                        {{ $cargoFuncionario($ultimoRevisor) }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section class="emit-section">
                    <div class="emit-section-head">
                        <i class="fa-solid fa-users"></i>
                        <h2 class="emit-section-title">Personas vinculadas</h2>
                    </div>
                    <div class="emit-section-body">
                        <dl class="emit-definition">
                            <div class="emit-field">
                                <dt>Beneficiario</dt>
                                <dd>{{ $nombrePersona($certificado->beneficiario) }}</dd>
                            </div>
                            <div class="emit-field">
                                <dt>CI / NIT beneficiario</dt>
                                <dd>{{ $identificacionPersona($certificado->beneficiario) }}</dd>
                            </div>
                            <div class="emit-field">
                                <dt>Tramitador</dt>
                                <dd>{{ $nombrePersona($certificado->tramitador) }}</dd>
                            </div>
                            <div class="emit-field">
                                <dt>CI / NIT tramitador</dt>
                                <dd>{{ $identificacionPersona($certificado->tramitador) }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section class="emit-section">
                    <div class="emit-section-head">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <h2 class="emit-section-title">Productos y registros</h2>
                    </div>
                    <div class="emit-section-body">
                        <table class="emit-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Fabricante</th>
                                    <th>Registro</th>
                                    <th>Presentación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($registrosPorProducto as $grupoRegistros)
                                    @foreach ($grupoRegistros as $registro)
                                        <tr>
                                            <td>{{ $registro->producto?->nombre_comercial ?? 'Sin producto' }}</td>
                                            <td>{{ $registro->producto?->fabricante?->nombre ?? 'Sin fabricante' }}</td>
                                            <td>
                                                <strong>{{ $registro->codigo_autorizacion ?? 'Sin registro' }}</strong>
                                                <span class="block text-xs text-slate-500">
                                                    {{ $registro->producto?->tipoProducto?->descripcion ?? 'Sin tipo' }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ trim(($registro->presentacion?->cantidad ?? '') . ' ' . ($registro->presentacion?->unidad ?? '')) ?: 'Sin cantidad' }}
                                                <span class="block text-xs text-slate-500">
                                                    {{ $registro->presentacion?->descripcion ?? 'Sin descripción' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Este trámite no tiene productos asociados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="emit-section">
                    <div class="emit-section-head">
                        <i class="fa-solid fa-list-check"></i>
                        <h2 class="emit-section-title">Requisitos aprobados</h2>
                    </div>
                    <div class="emit-section-body">
                        <table class="emit-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Requisito</th>
                                    <th>Estado</th>
                                    <th>Revisor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($certificado->certificadoRequisitos as $requisitoCertificado)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado' }}</td>
                                        <td>
                                            <span class="emit-chip bg-emerald-100 text-emerald-700">
                                                <i class="fa-solid fa-check"></i>
                                                Cumple
                                            </span>
                                        </td>
                                        <td>
                                            {{ $nombreFuncionario($requisitoCertificado->usuarioRevisor, 'Sin revisor') }}
                                            <span class="block text-xs font-semibold text-slate-500">
                                                {{ $cargoFuncionario($requisitoCertificado->usuarioRevisor) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="emit-preview">
                <div class="emit-paper">
                    <div class="emit-paper-head">
                        <span class="emit-logo">INSO</span>
                        <div>
                            <h2 class="emit-paper-title">{{ $certificado->tipoCertificado?->nombre ?? 'Certificado' }}</h2>
                            <p class="emit-paper-subtitle">Vista previa con la información aprobada del trámite</p>
                        </div>
                    </div>

                    <div class="emit-paper-content">
                        <div class="emit-paper-line">
                            <span>Código</span>
                            <strong>{{ $certificado->codigo ?: 'Sin código' }}</strong>
                        </div>
                        <div class="emit-paper-line">
                            <span>Beneficiario</span>
                            <strong>{{ $nombrePersona($certificado->beneficiario) }}</strong>
                        </div>
                        <div class="emit-paper-line">
                            <span>Tramitador</span>
                            <strong>{{ $nombrePersona($certificado->tramitador) }}</strong>
                        </div>
                        <div class="emit-paper-line">
                            <span>Producto principal</span>
                            <strong>{{ $certificado->registros->first()?->producto?->nombre_comercial ?? 'Sin producto asociado' }}</strong>
                        </div>
                        <div class="emit-paper-line">
                            <span>Fecha de emisión</span>
                            <strong>{{ now()->format('d/m/Y') }}</strong>
                        </div>
                    </div>

                    <div class="emit-paper-foot">
                        <span class="emit-qr">QR</span>
                        <span class="emit-signature">Firma autorizada</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-admin-layout>
