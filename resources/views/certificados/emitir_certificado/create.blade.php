<x-admin-layout title="Emitir Certificado | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Trámites', 'href' => route('seguimientos_index')],
    ['name' => 'Detalle', 'href' => route('certificados_show', ['certificado' => $certificado, 'bandeja' => request('bandeja', 'recibidas')])],
    ['name' => 'Emitir certificado', 'href' => route('certificados_emitir', $certificado)],
]">
    @php
        $puedeGestionarEmision = $puedeGestionarEmision ?? false;
        $vieneDeFinalizados = request('bandeja') === 'finalizados';
        $puedeEnviarSolicitante = $puedeGestionarEmision && $vieneDeFinalizados && $certificado->estado === 'EMITIDO';
        $abrirModalEmision = $errors->emisionCertificado->any();

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
        $medidasBasePlantilla = $papelPlantilla === 'OFICIO'
            ? ['ancho' => 816, 'alto' => 1248, 'css' => 'legal']
            : ['ancho' => 816, 'alto' => 1056, 'css' => 'letter'];

        if ($orientacionPlantilla === 'HORIZONTAL') {
            $medidasBasePlantilla = [
                'ancho' => $medidasBasePlantilla['alto'],
                'alto' => $medidasBasePlantilla['ancho'],
                'css' => $medidasBasePlantilla['css'],
            ];
        }

        $medidasPlantilla = [
            'ancho' => (int) ($plantillaCertificado?->ancho_lienzo_px ?: $medidasBasePlantilla['ancho']),
            'alto' => (int) ($plantillaCertificado?->alto_lienzo_px ?: $medidasBasePlantilla['alto']),
            'css' => $medidasBasePlantilla['css'],
        ];
        $ajusteFondoPlantilla = match (strtoupper((string) ($plantillaCertificado?->ajuste_fondo ?? 'ESTIRAR'))) {
            'CONTENER' => 'contain',
            'CUBRIR' => 'cover',
            default => 'fill',
        };

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

        $imprimirTransparente = (bool) ($plantillaCertificado?->imprimir_transparente ?? false);
        $fondoPlantilla = $imprimirTransparente
            ? null
            : ($resolverFondoImagen($plantillaCertificado?->url_fondo)
                ?: $resolverFondoImagen('documentos/plantillas_certificados/modelo_certificado.png'));
        $elementosPlantilla = $plantillaCertificado?->elementosActivos ?? collect();
        if ($elementosPlantilla->isEmpty()) {
            $elementosPlantilla = collect([
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_elemento' => 'certificado.codigo', 'texto_fijo' => null, 'posicion_x' => 670, 'posicion_y' => 205, 'ancho' => 150, 'alto' => 24, 'tamano_letra' => 12, 'alineacion' => 'CENTRO', 'negrita' => true],
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_elemento' => 'beneficiario.nombre', 'texto_fijo' => null, 'posicion_x' => 260, 'posicion_y' => 360, 'ancho' => 430, 'alto' => 34, 'tamano_letra' => 15, 'alineacion' => 'CENTRO', 'negrita' => true],
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_elemento' => 'producto.nombre_comercial', 'texto_fijo' => null, 'posicion_x' => 260, 'posicion_y' => 410, 'ancho' => 430, 'alto' => 26, 'tamano_letra' => 12, 'alineacion' => 'CENTRO', 'negrita' => false],
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_elemento' => 'registro.codigo', 'texto_fijo' => null, 'posicion_x' => 260, 'posicion_y' => 445, 'ancho' => 430, 'alto' => 24, 'tamano_letra' => 12, 'alineacion' => 'CENTRO', 'negrita' => false],
                (object) ['tipo_elemento' => 'CAMPO', 'codigo_elemento' => 'certificado.fecha_fin', 'texto_fijo' => null, 'posicion_x' => 565, 'posicion_y' => 675, 'ancho' => 170, 'alto' => 22, 'tamano_letra' => 11, 'alineacion' => 'CENTRO', 'negrita' => false],
            ]);
        }
        $valoresPlantillaConAlias = array_merge(
            $valoresPlantilla,
            collect($valoresPlantilla)
                ->mapWithKeys(fn ($valor, $clave) => [str_replace('.', '_', $clave) => $valor])
                ->all()
        );
        $resolverTextoPlantilla = function (?string $texto) use ($valoresPlantillaConAlias) {
            if (blank($texto)) {
                return '';
            }

            return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($coincidencia) use ($valoresPlantillaConAlias) {
                $clave = trim($coincidencia[1]);

                return $valoresPlantillaConAlias[$clave] ?? $coincidencia[0];
            }, $texto);
        };
        $filasTablaPlantilla = function ($elemento) use ($resolverTextoPlantilla) {
            $datos = json_decode((string) ($elemento->texto_fijo ?? ''), true);
            $filas = is_array($datos['filas'] ?? null) ? $datos['filas'] : [];

            if (!$filas) {
                $filas = [
                    collect($elemento->columnas ?? [])
                        ->map(fn ($columna) => '{{' . str_replace('.', '_', $columna->codigo_campo) . '}}')
                        ->values()
                        ->all(),
                ];
            }

            return collect($filas)
                ->map(fn ($fila) => collect($fila)
                    ->map(fn ($celda) => $resolverTextoPlantilla((string) $celda))
                    ->values()
                    ->all())
                ->values()
                ->all();
        };
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
            gap: 14px;
            grid-template-columns: minmax(250px, 0.72fr) minmax(520px, 1.28fr);
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
            padding: 12px;
        }

        .emit-definition {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr;
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
            font-size: 0.78rem;
            padding: 7px;
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
            padding: 10px;
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
            object-fit: {{ $ajusteFondoPlantilla }};
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
            background: transparent;
            border: 1px solid transparent;
            border-radius: 7px;
            box-sizing: border-box;
            color: #0f172a;
            display: block;
            line-height: 1.25;
            overflow: hidden;
            padding: 5px 7px;
            position: absolute;
            text-align: left;
            white-space: normal;
            z-index: 2;
        }

        .emit-template-field.is-texto {
            white-space: pre-wrap;
        }

        .emit-template-field.is-texto.is-justify {
            white-space: pre-line;
        }

        .emit-template-field.is-imagen {
            padding: 0;
        }

        .emit-template-field.is-imagen img {
            display: block;
            height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .emit-template-field.is-tabla {
            padding: 0;
        }

        .emit-template-field.is-qr {
            align-items: center;
            border: 1px dashed #0f766e;
            display: flex;
            justify-content: center;
            text-align: center;
        }

        .emit-template-field.is-qr img {
            display: block;
            height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .emit-template-word-table {
            border-collapse: collapse;
            font-size: inherit;
            height: 100%;
            table-layout: fixed;
            width: 100%;
        }

        .emit-template-word-table th,
        .emit-template-word-table td {
            border: 1px solid currentColor;
            line-height: 1.15;
            padding: 4px 6px;
            vertical-align: middle;
            word-break: break-word;
        }

        .emit-template-word-table th {
            font-weight: 900;
        }

        .emit-template-field.is-center {
            text-align: center;
        }

        .emit-template-field.is-right {
            text-align: right;
        }

        .emit-template-field.is-justify {
            display: block;
            text-align: justify;
        }

        .emit-modal-backdrop {
            align-items: center;
            background: rgba(15, 23, 42, 0.45);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: 18px;
            position: fixed;
            z-index: 60;
        }

        .emit-modal-backdrop[hidden] {
            display: none;
        }

        .emit-modal-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            max-width: 720px;
            overflow: hidden;
            width: min(100%, 720px);
        }

        .emit-modal-head {
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            padding: 16px 18px;
        }

        .emit-modal-title {
            font-size: 1rem;
            font-weight: 950;
        }

        .emit-modal-body {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 18px;
        }

        .emit-modal-field-full {
            grid-column: 1 / -1;
        }

        .emit-modal-actions {
            align-items: center;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
            padding: 14px 18px;
        }

        .emit-modal-close {
            color: #64748b;
            font-size: 1.05rem;
            line-height: 1;
        }

        @media (max-width: 1024px) {
            .emit-grid,
            .emit-definition,
            .emit-modal-body {
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
                    @if ($certificado->estado !== 'EMITIDO')
                        <button type="button" class="emit-btn emit-btn-primary" data-abrir-modal-emision>
                            <i class="fa-solid fa-file-circle-check"></i>
                            Emitir certificado
                        </button>
                    @elseif ($puedeEnviarSolicitante)
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

        <div class="emit-modal-backdrop" data-modal-emision @if (!$abrirModalEmision) hidden @endif>
            <form class="emit-modal-card" action="{{ route('certificados_emitir_guardar', $certificado) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="bandeja" value="{{ request('bandeja', 'finalizados') }}">

                <div class="emit-modal-head">
                    <div>
                        <h2 class="emit-modal-title">Datos finales del certificado</h2>
                        <p class="emit-subtitle">Complete la información que quedará registrada antes de enviar al solicitante.</p>
                    </div>
                    <button type="button" class="emit-modal-close" data-cerrar-modal-emision aria-label="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="emit-modal-body">
                    <div>
                        <x-wire-input id="form_fecha_inicio" label="Fecha de inicio" name="form_fecha_inicio" type="date"
                            value="{{ old('form_fecha_inicio', $certificado->fecha_inicio?->format('Y-m-d') ?: now()->format('Y-m-d')) }}" />
                        @error('form_fecha_inicio', 'emisionCertificado')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-wire-input id="form_vigencia_dias" label="Días de vigencia" name="form_vigencia_dias" type="number" min="1"
                            value="{{ old('form_vigencia_dias') }}" />
                        <p class="mt-1 text-xs text-slate-500">Solo se usa para calcular la fecha final.</p>
                        @error('form_vigencia_dias', 'emisionCertificado')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-wire-input id="form_fecha_fin" label="Fecha final" name="form_fecha_fin" type="date"
                            value="{{ old('form_fecha_fin', $certificado->fecha_fin?->format('Y-m-d') ?? '') }}" />
                        @error('form_fecha_fin', 'emisionCertificado')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="emit-modal-field-full flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input id="form_vigencia_indefinida" name="form_vigencia_indefinida" type="checkbox" value="1"
                            @checked(old('form_vigencia_indefinida'))>
                        Vigencia indefinida
                    </label>

                    <div class="emit-modal-field-full">
                        <x-wire-textarea label="Descripción final del certificado" name="form_descripcion"
                            placeholder="Ingrese la descripción final que acompañará la emisión.">{{ old('form_descripcion', $certificado->descripcion ?? '') }}</x-wire-textarea>
                        @error('form_descripcion', 'emisionCertificado')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="emit-modal-field-full text-sm text-slate-600">
                        @if ($certificado->url_documento)
                            <a class="mt-2 inline-flex text-xs font-bold text-emerald-700" href="{{ asset('storage/' . $certificado->url_documento) }}" target="_blank">
                                Ver documento actual
                            </a>
                        @else
                            El PDF se generará automáticamente con la plantilla activa al guardar la emisión.
                        @endif
                    </div>
                </div>

                <div class="emit-modal-actions">
                    <button type="button" class="emit-btn emit-btn-muted" data-cerrar-modal-emision>Cancelar</button>
                    <button type="submit" class="emit-btn emit-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar emisión
                    </button>
                </div>
            </form>
        </div>

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
                <div class="emit-template-stage {{ $imprimirTransparente ? 'is-transparent-print' : '' }}" id="certificado-imprimible"
                    style="--emit-page-width: {{ $medidasPlantilla['ancho'] }}px; --emit-page-height: {{ $medidasPlantilla['alto'] }}px; --emit-print-width: {{ $anchoImpresion }}; --emit-print-height: {{ $altoImpresion }};">
                    <div class="emit-template-page">
                        <div class="emit-template-background">
                            @if (!$imprimirTransparente && $fondoPlantilla)
                                <img src="{{ $fondoPlantilla }}" alt="Plantilla del certificado">
                            @elseif (!$imprimirTransparente)
                                <div class="emit-template-missing">
                                    Suba la plantilla como imagen PNG, JPG o WEBP para imprimir el certificado completo.
                                </div>
                            @endif
                        </div>

                        @foreach ($elementosPlantilla as $elementoPlantilla)
                            @php
                                $valorCampo = match ($elementoPlantilla->tipo_elemento) {
                                    'TEXTO' => $resolverTextoPlantilla($elementoPlantilla->texto_fijo),
                                    'IMAGEN' => $elementoPlantilla->texto_fijo,
                                    'TABLA' => 'TABLA',
                                    'QR' => filled($elementoPlantilla->texto_fijo)
                                        ? $elementoPlantilla->texto_fijo
                                        : ($valoresPlantilla[$elementoPlantilla->codigo_elemento] ?? 'QR'),
                                    default => $valoresPlantilla[$elementoPlantilla->codigo_elemento] ?? '',
                                };
                                $filasTabla = $elementoPlantilla->tipo_elemento === 'TABLA'
                                    ? $filasTablaPlantilla($elementoPlantilla)
                                    : [];
                                $esImagenQr = $elementoPlantilla->tipo_elemento === 'QR'
                                    && \Illuminate\Support\Str::startsWith((string) $valorCampo, 'data:image');
                                $alineacion = strtoupper((string) ($elementoPlantilla->alineacion ?? 'IZQUIERDA'));
                                $claseAlineacion = $alineacion === 'CENTRO'
                                    ? 'is-center'
                                    : ($alineacion === 'DERECHA' ? 'is-right' : ($alineacion === 'JUSTIFICADO' ? 'is-justify' : ''));
                                $claseTipoElemento = match ($elementoPlantilla->tipo_elemento) {
                                    'TEXTO', 'FIRMA' => 'is-texto',
                                    'IMAGEN' => 'is-imagen',
                                    'TABLA' => 'is-tabla',
                                    'QR' => 'is-qr',
                                    default => '',
                                };
                                $usaPaddingElemento = !in_array($elementoPlantilla->tipo_elemento, ['IMAGEN', 'TABLA', 'QR'], true);
                                $paddingXElemento = (float) data_get($elementoPlantilla, 'padding_x', $usaPaddingElemento ? 7 : 0);
                                $paddingYElemento = (float) data_get($elementoPlantilla, 'padding_y', $usaPaddingElemento ? 5 : 0);
                            @endphp

                            @if (filled($valorCampo))
                                <div class="emit-template-field {{ $claseTipoElemento }} {{ $claseAlineacion }}"
                                    style="
                                        left: {{ (float) $elementoPlantilla->posicion_x }}px;
                                        top: {{ (float) $elementoPlantilla->posicion_y }}px;
                                        width: {{ (float) $elementoPlantilla->ancho }}px;
                                        height: {{ (float) $elementoPlantilla->alto }}px;
                                        font-size: {{ (int) $elementoPlantilla->tamano_letra }}px;
                                        font-weight: {{ data_get($elementoPlantilla, 'negrita') ? 900 : 700 }};
                                        font-style: {{ data_get($elementoPlantilla, 'cursiva') ? 'italic' : 'normal' }};
                                        text-decoration: {{ data_get($elementoPlantilla, 'subrayado') ? 'underline' : 'none' }};
                                        color: {{ data_get($elementoPlantilla, 'color_texto', '#0f172a') ?: '#0f172a' }};
                                        font-family: '{{ data_get($elementoPlantilla, 'tipo_letra', 'Arial') ?: 'Arial' }}', Arial, sans-serif;
                                        line-height: {{ (float) data_get($elementoPlantilla, 'interlineado', 1.25) }};
                                        padding: {{ $paddingYElemento }}px {{ $paddingXElemento }}px;
                                        z-index: {{ (int) data_get($elementoPlantilla, 'z_index', 3) }};
                                    ">
                                    @if ($elementoPlantilla->tipo_elemento === 'IMAGEN')
                                        <img src="{{ $valorCampo }}" alt="Imagen del certificado" style="display:block; width:100%; height:100%; object-fit:contain;">
                                    @elseif ($esImagenQr)
                                        <img src="{{ $valorCampo }}" alt="QR del certificado" style="display:block; width:100%; height:100%; object-fit:contain;">
                                    @elseif ($elementoPlantilla->tipo_elemento === 'TABLA')
                                        <table class="emit-template-word-table">
                                            <thead>
                                                <tr>
                                                    @forelse ($elementoPlantilla->columnas as $columna)
                                                        <th style="width: {{ (float) ($columna->ancho ?: 25) }}%;">
                                                            {{ $columna->titulo_columna ?: $columna->codigo_campo }}
                                                        </th>
                                                    @empty
                                                        <th>Dato</th>
                                                    @endforelse
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($filasTabla as $filaTabla)
                                                    <tr>
                                                        @foreach ($filaTabla as $celdaTabla)
                                                            <td>{{ $celdaTabla }}</td>
                                                        @endforeach
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>Sin datos</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    @else
                                        {!! nl2br(e($valorCampo)) !!}
                                    @endif
                                </div>
                            @endif
                        @endforeach

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
                        object-fit: {{ $ajusteFondoPlantilla }};
                    }

                    .emit-template-field {
                        position: absolute;
                        z-index: 2;
                        display: block;
                        background: transparent;
                        border: 1px solid transparent;
                        border-radius: 7px;
                        box-sizing: border-box;
                        overflow: hidden;
                        padding: 5px 7px;
                        line-height: 1.25;
                        text-align: left;
                        white-space: normal;
                    }

                    .emit-template-field.is-texto {
                        white-space: pre-wrap;
                    }

                    .emit-template-field.is-texto.is-justify {
                        white-space: pre-line;
                    }

                    .emit-template-field.is-imagen {
                        padding: 0;
                    }

                    .emit-template-field.is-imagen img {
                        display: block;
                        width: 100%;
                        height: 100%;
                        object-fit: contain;
                    }

                    .emit-template-field.is-tabla {
                        padding: 0;
                    }

                    .emit-template-field.is-qr {
                        align-items: center;
                        border: 1px dashed #0f766e;
                        display: flex;
                        justify-content: center;
                        text-align: center;
                    }

                    .emit-template-field.is-qr img {
                        display: block;
                        height: 100%;
                        object-fit: contain;
                        width: 100%;
                    }

                    .emit-template-word-table {
                        border-collapse: collapse;
                        font-size: inherit;
                        height: 100%;
                        table-layout: fixed;
                        width: 100%;
                    }

                    .emit-template-word-table th,
                    .emit-template-word-table td {
                        border: 1px solid currentColor;
                        line-height: 1.15;
                        padding: 4px 6px;
                        vertical-align: middle;
                        word-break: break-word;
                    }

                    .emit-template-word-table th {
                        font-weight: 900;
                    }

                    .emit-template-field.is-center {
                        text-align: center;
                    }

                    .emit-template-field.is-right {
                        text-align: right;
                    }

                    .emit-template-field.is-justify {
                        text-align: justify;
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

            function prepararModalEmision() {
                const modal = document.querySelector('[data-modal-emision]');
                const botonAbrir = document.querySelector('[data-abrir-modal-emision]');
                const botonesCerrar = document.querySelectorAll('[data-cerrar-modal-emision]');
                const fechaInicio = document.getElementById('form_fecha_inicio');
                const diasVigencia = document.getElementById('form_vigencia_dias');
                const fechaFinal = document.getElementById('form_fecha_fin');
                const vigenciaIndefinida = document.getElementById('form_vigencia_indefinida');

                // Los días son un apoyo del formulario: la fecha final sigue siendo el valor que se guarda.
                const actualizarFechaFinal = () => {
                    if (!fechaFinal || !vigenciaIndefinida) {
                        return;
                    }

                    const esIndefinida = vigenciaIndefinida.checked;
                    fechaFinal.disabled = esIndefinida;

                    if (esIndefinida) {
                        fechaFinal.value = '';
                        return;
                    }

                    if (!fechaInicio?.value || !diasVigencia?.value) {
                        return;
                    }

                    const fecha = new Date(`${fechaInicio.value}T00:00:00`);
                    fecha.setDate(fecha.getDate() + Number(diasVigencia.value));
                    fechaFinal.value = fecha.toISOString().slice(0, 10);
                };

                if (!modal || !botonAbrir) {
                    return;
                }

                botonAbrir.addEventListener('click', () => {
                    modal.hidden = false;
                });

                botonesCerrar.forEach((boton) => {
                    boton.addEventListener('click', () => {
                        modal.hidden = true;
                    });
                });

                fechaInicio?.addEventListener('change', actualizarFechaFinal);
                diasVigencia?.addEventListener('input', actualizarFechaFinal);
                vigenciaIndefinida?.addEventListener('change', actualizarFechaFinal);
                actualizarFechaFinal();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    prepararImpresionCertificado();
                    prepararModalEmision();
                });
            } else {
                prepararImpresionCertificado();
                prepararModalEmision();
            }
        })();
    </script>
</x-admin-layout>

