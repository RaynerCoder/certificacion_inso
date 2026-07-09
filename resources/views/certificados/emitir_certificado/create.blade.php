<x-admin-layout title="Emitir Certificado | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Trámites', 'href' => route('seguimientos_index')],
    ['name' => 'Detalle', 'href' => route('certificados_show', ['certificado' => $certificado, 'bandeja' => request('bandeja', 'recibidas')])],
    ['name' => 'Emitir certificado', 'href' => route('certificados_emitir', $certificado)],
]">
    @php
        $puedeGestionarEmision = $puedeGestionarEmision ?? false;

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
        $papelPlantilla = strtoupper($plantillaCertificado?->tamano_papel ?? 'CARTA');
        $orientacionPlantilla = strtoupper($plantillaCertificado?->orientacion ?? 'VERTICAL');
        $medidasPlantilla = $papelPlantilla === 'OFICIO'
            ? ['ancho' => 816, 'alto' => 1248, 'css' => 'legal']
            : ['ancho' => 816, 'alto' => 1056, 'css' => 'letter'];

        if ($orientacionPlantilla === 'HORIZONTAL') {
            $medidasPlantilla = [
                'ancho' => $medidasPlantilla['alto'],
                'alto' => $medidasPlantilla['ancho'],
                'css' => $medidasPlantilla['css'],
            ];
        }

        $anchoImpresion = '8.5in';
        $altoImpresion = $papelPlantilla === 'OFICIO' ? '14in' : '11in';

        if ($orientacionPlantilla === 'HORIZONTAL') {
            [$anchoImpresion, $altoImpresion] = [$altoImpresion, $anchoImpresion];
        }

        $resolverFondoImagen = function (?string $ruta) {
            if (blank($ruta)) {
                return null;
            }

            $rutaLimpia = ltrim($ruta, '/');
            $esUrl = \Illuminate\Support\Str::startsWith($rutaLimpia, ['http://', 'https://']);
            $esImagen = \Illuminate\Support\Str::endsWith(strtolower($rutaLimpia), ['.jpg', '.jpeg', '.png', '.webp']);

            if ($esUrl) {
                return $esImagen ? $rutaLimpia : null;
            }

            $rutaStorage = \Illuminate\Support\Str::startsWith($rutaLimpia, 'storage/')
                ? substr($rutaLimpia, strlen('storage/'))
                : $rutaLimpia;

            if ($esImagen && \Illuminate\Support\Facades\File::exists(public_path('storage/' . $rutaStorage))) {
                return asset('storage/' . $rutaStorage);
            }

            if (\Illuminate\Support\Str::endsWith(strtolower($rutaStorage), '.pdf')) {
                $rutaImagen = preg_replace('/\.pdf$/i', '.png', $rutaStorage);

                if ($rutaImagen && \Illuminate\Support\Facades\File::exists(public_path('storage/' . $rutaImagen))) {
                    return asset('storage/' . $rutaImagen);
                }
            }

            return null;
        };

        $fondoPlantilla = $resolverFondoImagen($plantillaCertificado?->url_fondo)
            ?: $resolverFondoImagen('documentos/plantillas_certificados/modelo_certificado.png');
        $elementosPlantilla = $plantillaCertificado?->elementosActivos ?? collect();
        if ($elementosPlantilla->isEmpty()) {
            $elementosPlantilla = collect([
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_campo' => 'certificado.codigo', 'texto_fijo' => null, 'posicion_x' => 670, 'posicion_y' => 205, 'ancho' => 150, 'alto' => 24, 'tamano_letra' => 12, 'alineacion' => 'CENTRO', 'negrita' => true],
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_campo' => 'beneficiario.nombre', 'texto_fijo' => null, 'posicion_x' => 260, 'posicion_y' => 360, 'ancho' => 430, 'alto' => 34, 'tamano_letra' => 15, 'alineacion' => 'CENTRO', 'negrita' => true],
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_campo' => 'producto.nombre_comercial', 'texto_fijo' => null, 'posicion_x' => 260, 'posicion_y' => 410, 'ancho' => 430, 'alto' => 26, 'tamano_letra' => 12, 'alineacion' => 'CENTRO', 'negrita' => false],
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_campo' => 'registro.codigo', 'texto_fijo' => null, 'posicion_x' => 260, 'posicion_y' => 445, 'ancho' => 430, 'alto' => 24, 'tamano_letra' => 12, 'alineacion' => 'CENTRO', 'negrita' => false],
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_campo' => 'certificado.fecha_fin', 'texto_fijo' => null, 'posicion_x' => 565, 'posicion_y' => 675, 'ancho' => 170, 'alto' => 22, 'tamano_letra' => 11, 'alineacion' => 'CENTRO', 'negrita' => false],
            ]);
        }
        $modoPrueba = request('modo') === 'prueba';
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

        .emit-btn-print {
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #9a3412;
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

        .emit-template-stage {
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: auto;
            padding: 12px;
        }

        .emit-template-page {
            background: #ffffff;
            height: var(--emit-page-height);
            margin: 0 auto;
            overflow: hidden;
            position: relative;
            width: var(--emit-page-width);
        }

        .emit-template-background,
        .emit-template-background img {
            border: 0;
            height: 100%;
            inset: 0;
            position: absolute;
            width: 100%;
        }

        .emit-template-background img {
            display: block;
            object-fit: cover;
        }

        .emit-template-missing {
            align-items: center;
            background: #f8fafc;
            border: 1px dashed #94a3b8;
            color: #475569;
            display: grid;
            font-size: 0.9rem;
            font-weight: 800;
            height: 100%;
            justify-items: center;
            padding: 28px;
            text-align: center;
        }

        .emit-template-field {
            align-items: center;
            color: #0f172a;
            display: flex;
            line-height: 1.25;
            overflow: hidden;
            padding: 2px 4px;
            position: absolute;
            white-space: normal;
            z-index: 2;
        }

        .emit-template-field.is-center {
            justify-content: center;
            text-align: center;
        }

        .emit-template-field.is-right {
            justify-content: flex-end;
            text-align: right;
        }

        .emit-template-watermark {
            color: rgba(220, 38, 38, 0.16);
            font-size: 84px;
            font-weight: 950;
            left: 50%;
            letter-spacing: 8px;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            z-index: 3;
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

        @media print {
            @page {
                margin: 0;
                size: {{ $medidasPlantilla['css'] }} {{ $orientacionPlantilla === 'HORIZONTAL' ? 'landscape' : 'portrait' }};
            }

            html,
            body {
                height: var(--emit-print-height);
                margin: 0 !important;
                overflow: hidden !important;
                padding: 0 !important;
                width: var(--emit-print-width);
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            body * {
                visibility: hidden;
            }

            #certificado-imprimible,
            #certificado-imprimible * {
                visibility: visible;
            }

            #certificado-imprimible {
                break-after: avoid;
                break-before: avoid;
                break-inside: avoid;
                display: block;
                height: var(--emit-print-height);
                left: 0;
                overflow: hidden;
                page-break-after: avoid;
                page-break-before: avoid;
                page-break-inside: avoid;
                position: fixed;
                top: 0;
                width: var(--emit-print-width);
            }

            .emit-paper {
                border: none;
                box-shadow: none;
            }

            .emit-template-stage {
                background: transparent;
                border: none;
                height: var(--emit-print-height);
                overflow: hidden;
                padding: 0;
                width: var(--emit-print-width);
            }

            .emit-template-page {
                break-after: avoid;
                break-before: avoid;
                break-inside: avoid;
                height: var(--emit-print-height);
                margin: 0;
                overflow: hidden;
                page-break-after: avoid;
                page-break-before: avoid;
                page-break-inside: avoid;
                width: var(--emit-print-width);
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

                <button type="button" class="emit-btn emit-btn-print" data-imprimir-certificado>
                    <i class="fa-solid fa-print"></i>
                    Imprimir certificado
                </button>

                @if ($puedeGestionarEmision)
                    <a href="{{ route('certificados_emitir', ['certificado' => $certificado, 'modo' => $modoPrueba ? null : 'prueba']) }}"
                        class="emit-btn emit-btn-muted">
                        <i class="fa-solid fa-vial"></i>
                        {{ $modoPrueba ? 'Quitar prueba' : 'Prueba' }}
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
                                                {{ trim(($registro->presentacion?->cantidad ?? '') . ' ' . ($registro->presentacion?->catalogoUnidad?->nombre ?? '')) ?: 'Sin cantidad' }}
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
                <div class="emit-template-stage" id="certificado-imprimible"
                    style="--emit-page-width: {{ $medidasPlantilla['ancho'] }}px; --emit-page-height: {{ $medidasPlantilla['alto'] }}px; --emit-print-width: {{ $anchoImpresion }}; --emit-print-height: {{ $altoImpresion }};">
                    <div class="emit-template-page">
                        <div class="emit-template-background">
                            @if ($fondoPlantilla)
                                <img src="{{ $fondoPlantilla }}" alt="Plantilla del certificado">
                            @else
                                <div class="emit-template-missing">
                                    Suba la plantilla como imagen PNG, JPG o WEBP para imprimir el certificado completo.
                                </div>
                            @endif
                        </div>

                        @foreach ($elementosPlantilla as $elementoPlantilla)
                            @php
                                $valorCampo = $elementoPlantilla->tipo_elemento === 'TEXTO'
                                    ? $elementoPlantilla->texto_fijo
                                    : ($valoresPlantilla[$elementoPlantilla->codigo_campo] ?? '');
                                $alineacion = strtoupper((string) ($elementoPlantilla->alineacion ?? 'IZQUIERDA'));
                                $claseAlineacion = $alineacion === 'CENTRO'
                                    ? 'is-center'
                                    : ($alineacion === 'DERECHA' ? 'is-right' : '');
                            @endphp

                            @if (filled($valorCampo))
                                <div class="emit-template-field {{ $claseAlineacion }}"
                                    style="
                                        left: {{ (float) $elementoPlantilla->posicion_x }}px;
                                        top: {{ (float) $elementoPlantilla->posicion_y }}px;
                                        width: {{ (float) $elementoPlantilla->ancho }}px;
                                        min-height: {{ (float) $elementoPlantilla->alto }}px;
                                        font-size: {{ (int) $elementoPlantilla->tamano_letra }}px;
                                        font-weight: {{ $elementoPlantilla->negrita ? 900 : 700 }};
                                        font-style: {{ $elementoPlantilla->cursiva ? 'italic' : 'normal' }};
                                        text-decoration: {{ $elementoPlantilla->subrayado ? 'underline' : 'none' }};
                                        color: {{ $elementoPlantilla->color_texto ?: '#0f172a' }};
                                    ">
                                    {{ $valorCampo }}
                                </div>
                            @endif
                        @endforeach

                        @if ($modoPrueba)
                            <div class="emit-template-watermark">PRUEBA</div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
    <script>
        (function () {
            function estilosImpresionCertificado() {
                return `
                    @page {
                        margin: 0;
                        size: {{ $medidasPlantilla['css'] }} {{ $orientacionPlantilla === 'HORIZONTAL' ? 'landscape' : 'portrait' }};
                    }

                    html,
                    body {
                        width: {{ $anchoImpresion }};
                        height: {{ $altoImpresion }};
                        margin: 0;
                        padding: 0;
                        overflow: hidden;
                        background: #ffffff;
                    }

                    *,
                    *::before,
                    *::after {
                        box-sizing: border-box;
                    }

                    #certificado-imprimible,
                    .emit-template-stage,
                    .emit-template-page {
                        width: {{ $anchoImpresion }};
                        height: {{ $altoImpresion }};
                        margin: 0;
                        padding: 0;
                        overflow: hidden;
                        border: 0;
                        background: #ffffff;
                    }

                    .emit-template-page {
                        position: relative;
                    }

                    .emit-template-background,
                    .emit-template-background img {
                        position: absolute;
                        inset: 0;
                        width: 100%;
                        height: 100%;
                        border: 0;
                    }

                    .emit-template-background img {
                        display: block;
                        object-fit: cover;
                    }

                    .emit-template-field {
                        position: absolute;
                        z-index: 2;
                        display: flex;
                        align-items: center;
                        overflow: hidden;
                        padding: 0;
                        line-height: 1.15;
                        white-space: normal;
                    }

                    .emit-template-field.is-center {
                        justify-content: center;
                        text-align: center;
                    }

                    .emit-template-field.is-right {
                        justify-content: flex-end;
                        text-align: right;
                    }

                    .emit-template-watermark {
                        position: absolute;
                        z-index: 3;
                        left: 50%;
                        top: 50%;
                        transform: translate(-50%, -50%) rotate(-25deg);
                        color: rgba(220, 38, 38, 0.16);
                        font-size: 84px;
                        font-weight: 950;
                        letter-spacing: 8px;
                    }
                `;
            }

            function imprimirCuandoCargue(frame) {
                const documento = frame.contentDocument;
                const imagenes = Array.from(documento.images || []);
                const cargas = imagenes.map((imagen) => {
                    if (imagen.complete) {
                        return Promise.resolve();
                    }

                    return new Promise((resolve) => {
                        imagen.onload = resolve;
                        imagen.onerror = resolve;
                    });
                });

                Promise.all(cargas).then(() => {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                });
            }

            function prepararImpresionCertificado() {
                const botonImprimir = document.querySelector('[data-imprimir-certificado]');
                const certificado = document.getElementById('certificado-imprimible');

                if (!botonImprimir || !certificado) {
                    return;
                }

                botonImprimir.addEventListener('click', function () {
                    document.getElementById('certificado-print-frame')?.remove();

                    const frame = document.createElement('iframe');
                    frame.id = 'certificado-print-frame';
                    frame.style.position = 'fixed';
                    frame.style.right = '0';
                    frame.style.bottom = '0';
                    frame.style.width = '0';
                    frame.style.height = '0';
                    frame.style.border = '0';
                    frame.style.visibility = 'hidden';

                    document.body.appendChild(frame);

                    const documento = frame.contentDocument;
                    documento.open();
                    documento.close();

                    const meta = documento.createElement('meta');
                    meta.setAttribute('charset', 'utf-8');
                    documento.head.appendChild(meta);

                    const titulo = documento.createElement('title');
                    titulo.textContent = 'Imprimir certificado';
                    documento.head.appendChild(titulo);

                    const estilos = documento.createElement('style');
                    estilos.textContent = estilosImpresionCertificado();
                    documento.head.appendChild(estilos);

                    documento.body.appendChild(certificado.cloneNode(true));
                    imprimirCuandoCargue(frame);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', prepararImpresionCertificado);
            } else {
                prepararImpresionCertificado();
            }
        })();
    </script>
</x-admin-layout>

