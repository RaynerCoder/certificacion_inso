<x-admin-layout title="Detalle de Persona | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Personas', 'href' => route('personas_index')],
    ['name' => 'Detalle', 'href' => route('personas_show', $persona)],
]">

    @php
        // Datos base de la ficha. Se calculan aqui para que el HTML quede limpio y facil de ajustar.
        $empresa = $persona->empresa;
        $natural = $persona->natural;
        $esEmpresa = (bool) $empresa;

        $nombreNatural = trim(implode(' ', array_filter([
            $natural?->nombres,
            $natural?->apellido_paterno,
            $natural?->apellido_materno,
            $natural?->apellido_casado,
        ])));

        $nombrePrincipal = $esEmpresa
            ? ($empresa?->razon_social ?: 'Empresa sin razon social')
            : ($nombreNatural ?: 'Persona sin nombre registrado');

        $tipoRegistro = $esEmpresa ? 'Empresa' : 'Persona natural';
        $documentoEtiqueta = $esEmpresa ? 'NIT' : 'CI';
        $documentoPrincipal = $esEmpresa
            ? ($persona->nit ?: 'Sin NIT')
            : ($natural?->ci ?: ($persona->nit ?: 'Sin CI'));

        // El genero se guarda como valor numerico: 1 = masculino, 0 = femenino.
        $generoNaturalTexto = match ((string) ($natural?->genero ?? '')) {
            '1' => 'Masculino',
            '0' => 'Femenino',
            default => $natural?->genero ? (string) $natural->genero : 'Sin genero',
        };

        $telefonoPrincipal = $persona->telefonos->first()?->numero ?: 'Sin telefono';
        $rolesCuenta = $persona->usuario?->roles?->pluck('name')->filter()->join(', ') ?: 'Sin rol asignado';
        $estadoCuenta = $persona->usuario ? ($persona->usuario->estado ? 'ACTIVO' : 'INACTIVO') : 'SIN CUENTA';

        // Agrupa certificados por id para no duplicar un tramite si la persona figura en dos roles.
        $tramitesAgrupados = collect();

        foreach ($persona->certificadosComoBeneficiario as $certificado) {
            $tramitesAgrupados->put($certificado->id, [
                'certificado' => $certificado,
                'roles' => collect(['Beneficiario']),
            ]);
        }

        foreach ($persona->certificadosComoTramitador as $certificado) {
            $item = $tramitesAgrupados->get($certificado->id, [
                'certificado' => $certificado,
                'roles' => collect(),
            ]);

            $item['roles']->push('Tramitador');
            $tramitesAgrupados->put($certificado->id, $item);
        }

        $tramitesVinculados = $tramitesAgrupados
            ->values()
            ->sortByDesc(fn ($item) => $item['certificado']->fecha_inicio?->timestamp ?? $item['certificado']->created_at?->timestamp ?? 0)
            ->values();

        // Cada registro de tramite se muestra en fila propia para no mezclar productos.
        $filasTramites = $tramitesVinculados->flatMap(function ($item) {
            $certificado = $item['certificado'];
            $roles = $item['roles']->unique()->join(' / ');

            if ($certificado->registros->isEmpty()) {
                return collect([[
                    'certificado' => $certificado,
                    'roles' => $roles,
                    'registro' => null,
                ]]);
            }

            return $certificado->registros->map(fn ($registro) => [
                'certificado' => $certificado,
                'roles' => $roles,
                'registro' => $registro,
            ]);
        });

        // Cada producto se abre por registro o presentacion para mostrar relaciones completas.
        $filasProductos = $persona->productos->flatMap(function ($producto) {
            if ($producto->registros->isNotEmpty()) {
                return $producto->registros->map(fn ($registro) => [
                    'producto' => $producto,
                    'registro' => $registro,
                    'presentacion' => $registro->presentacion,
                ]);
            }

            if ($producto->presentaciones->isNotEmpty()) {
                return $producto->presentaciones->map(fn ($presentacion) => [
                    'producto' => $producto,
                    'registro' => null,
                    'presentacion' => $presentacion,
                ]);
            }

            return collect([[
                'producto' => $producto,
                'registro' => null,
                'presentacion' => null,
            ]]);
        });

        // Helper para colorear estados igual en toda la pantalla.
        $claseEstado = function (?string $estado) {
            return match ($estado) {
                'ACTIVO', 'APROBADO', 'COMPLETADO' => 'persona-o3-status persona-o3-status-ok',
                'EN_REVISION', 'PENDIENTE', 'BORRADOR' => 'persona-o3-status persona-o3-status-info',
                'OBSERVADO' => 'persona-o3-status persona-o3-status-warn',
                'INACTIVO', 'RECHAZADO', 'ANULADO', 'VENCIDO' => 'persona-o3-status persona-o3-status-danger',
                default => 'persona-o3-status persona-o3-status-neutral',
            };
        };

        // Helper para fechas nulas o parseables.
        $fechaCorta = function ($fecha) {
            if (! $fecha) {
                return 'Sin fecha';
            }

            return $fecha instanceof \Carbon\CarbonInterface
                ? $fecha->format('d/m/Y')
                : \Carbon\Carbon::parse($fecha)->format('d/m/Y');
        };

        // Helper para documentos guardados como URL o ruta storage.
        $urlDocumento = function (?string $ruta) {
            if (! $ruta) {
                return null;
            }

            return \Illuminate\Support\Str::startsWith($ruta, ['http://', 'https://'])
                ? $ruta
                : asset(\Illuminate\Support\Str::startsWith($ruta, 'storage/') ? $ruta : 'storage/' . $ruta);
        };
    @endphp

    <style>
        .persona-o3-shell {
            color: #0f172a;
        }

        .persona-o3-page {
            padding: 0;
        }

        .persona-o3-card {
            background: transparent;
            padding: 0;
        }

        .persona-o3-profile {
            align-items: center;
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 10px;
            display: grid;
            gap: 16px;
            grid-template-columns: auto minmax(0, 1fr) auto;
            margin-bottom: 14px;
            padding: 18px 20px;
        }

        .persona-o3-avatar {
            align-items: center;
            background: #059669;
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            font-size: 1.6rem;
            height: 72px;
            justify-content: center;
            width: 72px;
        }

        .persona-o3-name {
            color: #0f172a;
            font-size: 1.28rem;
            font-weight: 950;
            line-height: 1.12;
            text-transform: uppercase;
        }

        .persona-o3-kind {
            align-items: center;
            color: #334155;
            display: flex;
            flex-wrap: wrap;
            font-size: 0.79rem;
            font-weight: 850;
            gap: 12px;
            margin-top: 7px;
        }

        .persona-o3-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 13px;
        }

        .persona-o3-btn {
            align-items: center;
            border-radius: 7px;
            display: inline-flex;
            font-size: 0.73rem;
            font-weight: 900;
            gap: 7px;
            min-height: 34px;
            padding: 0 12px;
            white-space: nowrap;
        }

        .persona-o3-btn-primary {
            background: #ecfdf5;
            border: 1px solid #86efac;
            color: #047857;
        }

        .persona-o3-btn-soft {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
        }

        .persona-o3-status {
            align-items: center;
            border: 1px solid;
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.68rem;
            font-weight: 950;
            gap: 6px;
            line-height: 1;
            padding: 6px 9px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .persona-o3-status::before {
            background: currentColor;
            border-radius: 999px;
            content: "";
            height: 6px;
            width: 6px;
        }

        .persona-o3-status-ok {
            background: #dff8ed;
            border-color: #b8ead5;
            color: #047857;
        }

        .persona-o3-status-info {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .persona-o3-status-warn {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .persona-o3-status-danger {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .persona-o3-status-neutral {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #475569;
        }

        .persona-o3-label {
            color: #64748b;
            display: block;
            font-size: 0.68rem;
            font-weight: 950;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .persona-o3-value {
            color: #0f172a;
            display: block;
            font-size: 0.77rem;
            font-weight: 820;
            line-height: 1.35;
            margin-top: 3px;
            overflow-wrap: anywhere;
        }

        .persona-o3-section {
            background: #ffffff;
            border: 1px solid #dbe4ee;
            border-radius: 10px;
            margin-top: 14px;
            padding: 0 16px 16px;
        }

        .persona-o3-section-title {
            align-items: center;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 9px 9px 0 0;
            color: #0f172a;
            display: flex;
            font-size: 0.76rem;
            font-weight: 950;
            gap: 9px;
            letter-spacing: 0;
            margin: 0 -16px 14px;
            padding: 10px 16px;
            text-transform: uppercase;
        }

        .persona-o3-section-title::after {
            content: none;
        }

        .persona-o3-section-title i {
            align-items: center;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.82);
            border-radius: 7px;
            color: currentColor;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 0.9rem;
            height: 24px;
            justify-content: center;
            width: 24px;
        }

        .persona-o3-section-natural .persona-o3-section-title {
            background: #f1fbf5;
            color: #047857;
        }

        .persona-o3-section-persona .persona-o3-section-title {
            background: #f3f8ff;
            color: #2563eb;
        }

        .persona-o3-section-empresa .persona-o3-section-title {
            background: #faf7ff;
            color: #6d5bd0;
        }

        .persona-o3-section-account .persona-o3-section-title {
            background: #f1fbfa;
            color: #0f766e;
        }

        .persona-o3-section-contact .persona-o3-section-title {
            background: #fff8f0;
            color: #b45309;
        }

        .persona-o3-section-activity .persona-o3-section-title {
            background: #f0fbfd;
            color: #0e7490;
        }

        .persona-o3-section-relations .persona-o3-section-title {
            background: #f1f5f9;
            color: #334155;
        }

        .persona-o3-section-responsable .persona-o3-section-title {
            background: #f5f6ff;
            color: #4f46e5;
        }

        .persona-o3-section-responsables .persona-o3-section-title {
            background: #fff5fa;
            color: #be185d;
        }

        .persona-o3-section-productos .persona-o3-section-title {
            background: #fffaf0;
            color: #a16207;
        }

        .persona-o3-section-tramites .persona-o3-section-title {
            background: #f2f9ff;
            color: #0369a1;
        }

        .persona-o3-fields {
            display: grid;
            gap: 6px 28px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .persona-o3-field {
            align-items: baseline;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(130px, 155px) minmax(0, 1fr);
            min-height: 32px;
            padding: 6px 0;
        }

        .persona-o3-account {
            display: grid;
            gap: 6px 28px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .persona-o3-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .persona-o3-chip {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #dbe4ee;
            border-radius: 999px;
            color: #334155;
            display: inline-flex;
            font-size: 0.72rem;
            font-weight: 850;
            gap: 6px;
            padding: 5px 9px;
        }

        .persona-o3-responsable-records {
            display: grid;
            gap: 14px;
        }

        .persona-o3-responsable-record {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-left: 4px solid #0f766e;
            border-radius: 10px;
            overflow: hidden;
        }

        .persona-o3-responsable-record-head {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .persona-o3-responsable-person {
            align-items: center;
            display: flex;
            gap: 10px;
            min-width: 0;
        }

        .persona-o3-responsable-index {
            align-items: center;
            background: #0f766e;
            border-radius: 999px;
            color: #ffffff;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 0.75rem;
            font-weight: 950;
            height: 26px;
            justify-content: center;
            width: 26px;
        }

        .persona-o3-responsable-name {
            color: #0f172a;
            display: block;
            font-size: 0.83rem;
            font-weight: 950;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .persona-o3-responsable-meta {
            color: #64748b;
            display: block;
            font-size: 0.69rem;
            font-weight: 750;
            margin-top: 3px;
        }

        .persona-o3-responsable-panels {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .persona-o3-responsable-panel {
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            min-height: 132px;
            padding: 13px 15px;
        }

        .persona-o3-responsable-panel:nth-child(3n) {
            border-right: 0;
        }

        .persona-o3-responsable-panel:nth-last-child(-n + 3) {
            border-bottom: 0;
        }

        .persona-o3-responsable-panel-title {
            color: #0f766e;
            font-size: 0.68rem;
            font-weight: 950;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .persona-o3-responsable-data {
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(105px, 36%) minmax(0, 1fr);
            margin-top: 7px;
        }

        .persona-o3-responsable-data:first-of-type {
            margin-top: 0;
        }

        .persona-o3-responsable-data span:first-child {
            color: #475569;
            font-size: 0.74rem;
            font-weight: 750;
        }

        .persona-o3-responsable-data span:last-child {
            color: #0f172a;
            font-size: 0.74rem;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .persona-o3-responsable-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .persona-o3-responsable-tag {
            background: #ecfdf5;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            color: #047857;
            display: inline-flex;
            font-size: 0.7rem;
            font-weight: 850;
            gap: 6px;
            padding: 6px 9px;
        }

        .persona-o3-responsable-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 750;
            padding: 10px;
        }

        .persona-o3-table-wrap {
            margin-top: 4px;
            overflow-x: auto;
        }

        .persona-o3-table {
            border-collapse: collapse;
            min-width: 100%;
            width: 100%;
        }

        .persona-o3-table th {
            border-bottom: 1px solid #dbe4ee;
            color: #334155;
            font-size: 0.69rem;
            font-weight: 950;
            padding: 9px 10px;
            text-align: left;
            white-space: nowrap;
        }

        .persona-o3-table td {
            border-bottom: 1px solid #eef2f7;
            color: #0f172a;
            font-size: 0.75rem;
            line-height: 1.35;
            padding: 9px 10px;
            vertical-align: top;
        }

        .persona-o3-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .persona-o3-strong {
            font-weight: 950;
        }

        .persona-o3-muted {
            color: #64748b;
            display: block;
            font-size: 0.69rem;
            font-weight: 650;
            margin-top: 3px;
        }

        .persona-o3-link {
            color: #047857;
            font-size: 0.72rem;
            font-weight: 900;
        }

        .persona-o3-empty {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 10px;
        }

        @media (max-width: 1180px) {
            .persona-o3-profile,
            .persona-o3-fields,
            .persona-o3-account {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 760px) {
            .persona-o3-field {
                grid-template-columns: 1fr;
                gap: 3px;
            }

            .persona-o3-actions {
                display: grid;
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="persona-o3-shell">
        <div class="persona-o3-page">
            <div class="persona-o3-card">
                {{-- Encabezado de identidad: avatar, nombre, tipo y estado. --}}
                <section class="persona-o3-profile">
                    <div class="persona-o3-avatar">
                        <i class="fa-solid {{ $esEmpresa ? 'fa-building' : 'fa-user' }}"></i>
                    </div>

                    <div>
                        <h2 class="persona-o3-name">{{ $nombrePrincipal }}</h2>
                        <div class="persona-o3-kind">
                            <span><i class="fa-solid {{ $esEmpresa ? 'fa-building' : 'fa-id-card' }}"></i> {{ $tipoRegistro }}</span>
                            <span>{{ $documentoEtiqueta }}: {{ $documentoPrincipal }}</span>
                        </div>

                        <div class="persona-o3-actions">
                            <a href="{{ route('personas_edit', $persona) }}" class="persona-o3-btn persona-o3-btn-primary">
                                <i class="fa-solid fa-pen"></i>
                                Editar
                            </a>
                            <a href="#tramites-vinculados" class="persona-o3-btn persona-o3-btn-soft">
                                <i class="fa-regular fa-file-lines"></i>
                                Ver tramites
                            </a>
                            <a href="{{ route('personas_index') }}" class="persona-o3-btn persona-o3-btn-soft">
                                <i class="fa-solid fa-arrow-left"></i>
                                Volver
                            </a>
                        </div>
                    </div>

                    <div>
                        <span class="{{ $claseEstado($persona->estado) }}">{{ $persona->estado ?? 'Sin estado' }}</span>
                    </div>
                </section>

                @if ($esEmpresa)
                    {{-- Tabla relacionada: empresas. --}}
                    <section class="persona-o3-section persona-o3-section-empresa">
                        <h3 class="persona-o3-section-title">
                            <i class="fa-regular fa-building"></i>
                            Datos de empresa
                        </h3>

                        <div class="persona-o3-fields">
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Razon social</span>
                                <span class="persona-o3-value">{{ $empresa?->razon_social ?: 'Sin razon social' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Matricula</span>
                                <span class="persona-o3-value">{{ $empresa?->matricula ?: 'Sin matricula' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Tipo de empresa</span>
                                <span class="persona-o3-value">{{ $empresa?->tipoEmpresa?->descripcion ?? 'Sin tipo de empresa' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Latitud</span>
                                <span class="persona-o3-value">{{ $empresa?->latitud ?: 'Sin latitud' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Longitud</span>
                                <span class="persona-o3-value">{{ $empresa?->longitud ?: 'Sin longitud' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Estado empresa</span>
                                <span class="persona-o3-value"><span class="{{ $claseEstado($empresa?->estado) }}">{{ $empresa?->estado ?? 'Sin estado' }}</span></span>
                            </div>
                        </div>
                    </section>
                @else
                    {{-- Tabla relacionada: naturals. Cada campo se muestra por separado como esta guardado. --}}
                    <section class="persona-o3-section persona-o3-section-natural">
                        <h3 class="persona-o3-section-title">
                            <i class="fa-regular fa-id-card"></i>
                            Datos de persona natural
                        </h3>

                        <div class="persona-o3-fields">
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Nombres</span>
                                <span class="persona-o3-value">{{ $natural?->nombres ?: 'Sin nombres' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Apellido paterno</span>
                                <span class="persona-o3-value">{{ $natural?->apellido_paterno ?: 'Sin apellido paterno' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Apellido materno</span>
                                <span class="persona-o3-value">{{ $natural?->apellido_materno ?: 'Sin apellido materno' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Apellido casado</span>
                                <span class="persona-o3-value">{{ $natural?->apellido_casado ?: 'Sin apellido casado' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">CI</span>
                                <span class="persona-o3-value">{{ $natural?->ci ?: 'Sin CI' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Complemento</span>
                                <span class="persona-o3-value">{{ $natural?->complemento ?: 'Sin complemento' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Expedido</span>
                                <span class="persona-o3-value">{{ $natural?->expedido ?: 'Sin expedido' }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Nacimiento</span>
                                <span class="persona-o3-value">{{ $fechaCorta($natural?->fecha_nacimiento) }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Genero</span>
                                <span class="persona-o3-value">{{ $generoNaturalTexto }}</span>
                            </div>
                            <div class="persona-o3-field">
                                <span class="persona-o3-label">Ocupacion</span>
                                <span class="persona-o3-value">{{ $natural?->ocupacion ?: 'Sin ocupacion' }}</span>
                            </div>
                        </div>
                    </section>
                @endif

                {{-- Tabla principal: personas. No se mezclan telefonos ni rubros porque son relaciones aparte. --}}
                <section class="persona-o3-section persona-o3-section-persona">
                    <h3 class="persona-o3-section-title">
                        <i class="fa-regular fa-address-card"></i>
                        Datos de persona
                    </h3>

                    <div class="persona-o3-fields">
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">NIT</span>
                            <span class="persona-o3-value">{{ $persona->nit ?: 'Sin NIT' }}</span>
                        </div>
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">Correo</span>
                            <span class="persona-o3-value">{{ $persona->correo ?: 'Sin correo' }}</span>
                        </div>
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">Territorio</span>
                            <span class="persona-o3-value">{{ $persona->territorio?->nombre ?? 'Sin territorio' }}</span>
                        </div>
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">Domicilio</span>
                            <span class="persona-o3-value">{{ $persona->domicilio ?: 'Sin domicilio' }}</span>
                        </div>
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">Estado persona</span>
                            <span class="persona-o3-value"><span class="{{ $claseEstado($persona->estado) }}">{{ $persona->estado ?? 'Sin estado' }}</span></span>
                        </div>
                    </div>
                </section>

                {{-- Cuenta de acceso al sistema. --}}
                <section class="persona-o3-section persona-o3-section-account">
                    <h3 class="persona-o3-section-title">
                        <i class="fa-regular fa-user"></i>
                        Cuenta de acceso al sistema
                    </h3>

                    <div class="persona-o3-account">
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">Usuario</span>
                            <span class="persona-o3-value">{{ $persona->usuario?->name ?? 'Sin usuario vinculado' }}</span>
                        </div>
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">Correo de acceso</span>
                            <span class="persona-o3-value">{{ $persona->usuario?->email ?? 'Sin correo de acceso' }}</span>
                        </div>
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">Rol</span>
                            <span class="persona-o3-value">{{ $rolesCuenta }}</span>
                        </div>
                        <div class="persona-o3-field">
                            <span class="persona-o3-label">Estado de cuenta</span>
                            <span class="persona-o3-value"><span class="{{ $claseEstado($estadoCuenta) }}">{{ $estadoCuenta }}</span></span>
                        </div>
                    </div>
                </section>

                {{-- Relacion: telefonos. Para empresa se muestran antes de responsables, como dato directo de contacto. --}}
                <section class="persona-o3-section persona-o3-section-contact">
                    <h3 class="persona-o3-section-title">
                        <i class="fa-solid fa-phone"></i>
                        {{ $esEmpresa ? 'Telefonos de la empresa' : 'Telefonos de la persona natural' }}
                    </h3>

                    <div class="persona-o3-table-wrap">
                        <table class="persona-o3-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Numero</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($persona->telefonos as $telefono)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $telefono->numero ?: 'Sin numero' }}</td>
                                        <td><span class="{{ $claseEstado($telefono->estado) }}">{{ $telefono->estado ?? 'Sin estado' }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">{{ $esEmpresa ? 'Sin telefonos registrados para esta empresa.' : 'Sin telefonos registrados para esta persona natural.' }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                @if ($esEmpresa)
                    {{-- Responsables vinculados a la empresa: se muestra solo cuando la persona base tiene datos de empresa. --}}
                    <section class="persona-o3-section persona-o3-section-responsables">
                        <h3 class="persona-o3-section-title">
                            <i class="fa-solid fa-users"></i>
                            Responsables / representantes de la empresa
                        </h3>

                        <div class="persona-o3-responsable-records">
                            @forelse ($empresa?->responsables ?? [] as $responsable)
                                @php
                                    $personaResponsable = $responsable->persona;
                                    $naturalResponsable = $personaResponsable?->natural;
                                    $nombreResponsableFila = trim(implode(' ', array_filter([
                                        $naturalResponsable?->nombres,
                                        $naturalResponsable?->apellido_paterno,
                                        $naturalResponsable?->apellido_materno,
                                    ]))) ?: 'Sin nombre';
                                    $generoResponsableTexto = match ((string) ($naturalResponsable?->genero ?? '')) {
                                        '1' => 'Masculino',
                                        '0' => 'Femenino',
                                        default => $naturalResponsable?->genero ? (string) $naturalResponsable->genero : 'Sin genero',
                                    };
                                    $telefonosResponsable = $personaResponsable?->telefonos ?? collect();
                                    $rubrosResponsable = $personaResponsable?->rubros ?? collect();
                                    $respaldoResponsable = $urlDocumento($responsable->url_respaldo);
                                @endphp

                                {{-- Expediente del responsable: usa bloques amplios para evitar una tabla demasiado comprimida. --}}
                                <article class="persona-o3-responsable-record">
                                    <header class="persona-o3-responsable-record-head">
                                        <div class="persona-o3-responsable-person">
                                            <span class="persona-o3-responsable-index">{{ $loop->iteration }}</span>
                                            <span>
                                                <span class="persona-o3-responsable-name">{{ $nombreResponsableFila }}</span>
                                                <span class="persona-o3-responsable-meta">
                                                    EXISTENTE | ID persona: {{ $personaResponsable?->id ?? 'Sin ID' }}
                                                </span>
                                            </span>
                                        </div>

                                        <span class="{{ $claseEstado($responsable->estado) }}">
                                            {{ $responsable->estado ?? 'Sin estado' }}
                                        </span>
                                    </header>

                                    <div class="persona-o3-responsable-panels">
                                        <div class="persona-o3-responsable-panel">
                                            <h4 class="persona-o3-responsable-panel-title">Identificacion</h4>
                                            <div class="persona-o3-responsable-data"><span>CI</span><span>{{ $naturalResponsable?->ci ?: 'Sin CI' }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>NIT</span><span>{{ $personaResponsable?->nit ?: 'Sin NIT' }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>Complemento</span><span>{{ $naturalResponsable?->complemento ?: 'Sin dato' }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>Expedido</span><span>{{ $naturalResponsable?->expedido ?: 'Sin expedido' }}</span></div>
                                        </div>

                                        <div class="persona-o3-responsable-panel">
                                            <h4 class="persona-o3-responsable-panel-title">Contacto</h4>
                                            <div class="persona-o3-responsable-data"><span>Correo</span><span>{{ $personaResponsable?->correo ?: 'Sin correo' }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>Domicilio</span><span>{{ $personaResponsable?->domicilio ?: 'Sin domicilio' }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>Territorio</span><span>{{ $personaResponsable?->territorio?->nombre ?? 'Sin territorio' }}</span></div>
                                        </div>

                                        <div class="persona-o3-responsable-panel">
                                            <h4 class="persona-o3-responsable-panel-title">Datos personales</h4>
                                            <div class="persona-o3-responsable-data"><span>Nacimiento</span><span>{{ $fechaCorta($naturalResponsable?->fecha_nacimiento) }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>Genero</span><span>{{ $generoResponsableTexto }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>Ocupacion</span><span>{{ $naturalResponsable?->ocupacion ?: 'Sin ocupacion' }}</span></div>
                                        </div>

                                        <div class="persona-o3-responsable-panel">
                                            <h4 class="persona-o3-responsable-panel-title">Rol y respaldo</h4>
                                            <div class="persona-o3-responsable-data"><span>Rol</span><span>{{ $responsable->rol?->name ?: 'Sin rol' }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>Registro</span><span>{{ $fechaCorta($responsable->fecha_registro) }}</span></div>
                                            <div class="persona-o3-responsable-data"><span>Baja</span><span>{{ $fechaCorta($responsable->fecha_baja) }}</span></div>
                                            <div class="persona-o3-responsable-data">
                                                <span>Respaldo</span>
                                                <span>
                                                    @if ($respaldoResponsable)
                                                        <a href="{{ $respaldoResponsable }}" target="_blank" class="persona-o3-link">Ver PDF</a>
                                                    @else
                                                        Sin respaldo
                                                    @endif
                                                </span>
                                            </div>
                                        </div>

                                        <div class="persona-o3-responsable-panel">
                                            <h4 class="persona-o3-responsable-panel-title">Telefonos</h4>
                                            @if ($telefonosResponsable->isNotEmpty())
                                                <div class="persona-o3-responsable-tags">
                                                    @foreach ($telefonosResponsable as $telefono)
                                                        <span class="persona-o3-responsable-tag">
                                                            {{ $loop->iteration }}. {{ $telefono->numero ?: 'Sin numero' }}
                                                            {{ $telefono->estado ? ' ' . $telefono->estado : '' }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="persona-o3-responsable-empty">Sin registros</div>
                                            @endif
                                        </div>

                                        <div class="persona-o3-responsable-panel">
                                            <h4 class="persona-o3-responsable-panel-title">Rubros</h4>
                                            @if ($rubrosResponsable->isNotEmpty())
                                                <div class="persona-o3-responsable-tags">
                                                    @foreach ($rubrosResponsable as $rubro)
                                                        <span class="persona-o3-responsable-tag">
                                                            {{ $loop->iteration }}. {{ $rubro->nombre ?: 'Sin nombre' }}
                                                            {{ $rubro->estado ? ' ' . $rubro->estado : '' }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="persona-o3-responsable-empty">Sin registros</div>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="persona-o3-empty">Esta empresa no tiene responsables registrados.</div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if (! $esEmpresa)
                    {{-- Relacion: rubros. En el formulario solo se registran para persona natural. --}}
                    <section class="persona-o3-section persona-o3-section-activity">
                        <h3 class="persona-o3-section-title">
                            <i class="fa-solid fa-tags"></i>
                            Rubros de la persona natural
                        </h3>

                        <div class="persona-o3-table-wrap">
                            <table class="persona-o3-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Rubro</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($persona->rubros as $rubro)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $rubro->nombre ?: 'Sin nombre' }}</td>
                                            <td><span class="{{ $claseEstado($rubro->estado) }}">{{ $rubro->estado ?? 'Sin estado' }}</span></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3">Sin rubros registrados para esta persona natural.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                {{-- Productos, registros y presentaciones relacionados. --}}
                <section class="persona-o3-section persona-o3-section-productos">
                    <h3 class="persona-o3-section-title">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        Productos, registros y presentaciones asociadas
                    </h3>

                    <div class="persona-o3-table-wrap">
                        <table class="persona-o3-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Tipo / fabricante</th>
                                    <th>Ingredientes</th>
                                    <th>Registro</th>
                                    <th>Presentacion</th>
                                    <th>Aduana</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($filasProductos as $filaProducto)
                                    @php
                                        $producto = $filaProducto['producto'];
                                        $registro = $filaProducto['registro'];
                                        $presentacion = $filaProducto['presentacion'];
                                        $ingredientesTexto = $producto->ingredientes
                                            ->map(fn ($ingrediente) => ($ingrediente->nombre ?: 'Sin nombre') . ' (' . ($ingrediente->pivot?->porcentaje ?? '0') . '%)')
                                            ->join(', ');
                                        $aduanasTexto = $producto->aduanas
                                            ->map(fn ($aduana) => ($aduana->codigo_solicitud ?: $aduana->codigo_cotizacion ?: 'Sin codigo') . ($aduana->estado ? ' - ' . $aduana->estado : ''))
                                            ->join(' / ');
                                        $etiquetaUrl = $urlDocumento($presentacion?->url_etiqueta);
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="persona-o3-strong">{{ $producto->nombre_comercial ?: 'Sin nombre comercial' }}</span>
                                            <span class="persona-o3-muted">Codigo: {{ $producto->codigo ?: 'Sin codigo' }}</span>
                                            <span class="persona-o3-muted">Cientifico: {{ $producto->nombre_cientifico ?: 'Sin dato' }}</span>
                                            <span class="persona-o3-muted">Pais: {{ $producto->territorio?->nombre ?? 'Sin pais' }}</span>
                                        </td>
                                        <td>
                                            {{ $producto->tipoProducto?->descripcion ?? 'Sin tipo' }}
                                            <span class="persona-o3-muted">Fabricante: {{ $producto->fabricante?->nombre ?? 'Sin fabricante' }}</span>
                                            <span class="persona-o3-muted">Clasificacion: {{ $producto->clasificacion ?: 'Sin clasificacion' }}</span>
                                        </td>
                                        <td>{{ $ingredientesTexto ?: 'Sin ingredientes' }}</td>
                                        <td>
                                            @if ($registro)
                                                <span class="persona-o3-strong">{{ $registro->codigo_autorizacion ?: 'Sin codigo' }}</span>
                                                <span class="persona-o3-muted">{{ $registro->cantidad ?: '0' }} {{ $registro->unidad ?: '' }}</span>
                                                <span class="persona-o3-muted">Vigencia: {{ $fechaCorta($registro->fecha_vigencia) }}</span>
                                            @else
                                                Sin registro
                                            @endif
                                        </td>
                                        <td>
                                            @if ($presentacion)
                                                {{ $presentacion->cantidad ?: '0' }} {{ $presentacion->unidad ?: '' }}
                                                <span class="persona-o3-muted">{{ $presentacion->descripcion ?: 'Sin descripcion' }}</span>
                                                @if ($etiquetaUrl)
                                                    <a href="{{ $etiquetaUrl }}" target="_blank" class="persona-o3-link">Ver etiqueta PDF</a>
                                                @endif
                                            @else
                                                Sin presentacion
                                            @endif
                                        </td>
                                        <td>{{ $aduanasTexto ?: 'Sin datos de aduana' }}</td>
                                        <td><span class="{{ $claseEstado($producto->estado) }}">{{ $producto->estado ?? 'Sin estado' }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">No existen productos asociados a esta persona como importador.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- Tramites vinculados. --}}
                <section id="tramites-vinculados" class="persona-o3-section persona-o3-section-tramites">
                    <h3 class="persona-o3-section-title">
                        <i class="fa-solid fa-file-signature"></i>
                        Tramites vinculados
                    </h3>

                    <div class="persona-o3-table-wrap">
                        <table class="persona-o3-table">
                            <thead>
                                <tr>
                                    <th>Codigo tramite</th>
                                    <th>Tipo de tramite</th>
                                    <th>Participacion</th>
                                    <th>Producto</th>
                                    <th>Registro</th>
                                    <th>Presentacion</th>
                                    <th>Estado actual</th>
                                    <th>Ultima actualizacion</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($filasTramites as $filaTramite)
                                    @php
                                        $certificado = $filaTramite['certificado'];
                                        $registro = $filaTramite['registro'];
                                        $producto = $registro?->producto;
                                        $presentacion = $registro?->presentacion;
                                    @endphp
                                    <tr>
                                        <td class="persona-o3-strong">
                                            {{ $certificado->codigo ?: 'Sin codigo' }}
                                            <span class="persona-o3-muted">Inicio: {{ $fechaCorta($certificado->fecha_inicio) }}</span>
                                        </td>
                                        <td>{{ $certificado->tipoCertificado?->nombre ?? 'Sin tipo' }}</td>
                                        <td>{{ $filaTramite['roles'] }}</td>
                                        <td>
                                            {{ $producto?->nombre_comercial ?? 'Sin producto asociado' }}
                                            @if ($producto)
                                                <span class="persona-o3-muted">{{ $producto->codigo ?: 'Sin codigo de producto' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $registro?->codigo_autorizacion ?? 'Sin registro' }}
                                            @if ($registro)
                                                <span class="persona-o3-muted">{{ $registro->cantidad ?: '0' }} {{ $registro->unidad ?: '' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($presentacion)
                                                {{ $presentacion->cantidad ?: '0' }} {{ $presentacion->unidad ?: '' }}
                                                <span class="persona-o3-muted">{{ $presentacion->descripcion ?: 'Sin descripcion' }}</span>
                                            @else
                                                Sin presentacion
                                            @endif
                                        </td>
                                        <td><span class="{{ $claseEstado($certificado->estado) }}">{{ $certificado->estado ?? 'Sin estado' }}</span></td>
                                        <td>{{ $certificado->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                        <td>
                                            <a href="{{ route('certificados_show', ['certificado' => $certificado, 'bandeja' => 'todos']) }}"
                                                class="persona-o3-btn persona-o3-btn-soft">
                                                <i class="fa-regular fa-eye"></i>
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">No existen tramites vinculados a esta persona.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-admin-layout>
