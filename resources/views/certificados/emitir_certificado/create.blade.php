<x-admin-layout title="Emitir Certificado | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Trámites', 'href' => route('seguimientos_index')],
    ['name' => 'Detalle', 'href' => route('certificados_show', ['certificado' => $certificado, 'bandeja' => request('bandeja', 'recibidas')])],
    ['name' => 'Emitir certificado', 'href' => route('certificados_emitir', $certificado)],
]">
    @php
        $puedeGestionarEmision = $puedeGestionarEmision ?? false;
        $registroSeleccionado = $registroSeleccionado ?? null;
        $esEmisionPorLote = $certificado->tipoCertificado?->modo_emision === 'POR_LOTE';
        $emisionSeleccionada = $emisionSeleccionada ?? null;
        $documentoOficialDisponible = $emisionSeleccionada
            && in_array($emisionSeleccionada->estado, ['EMITIDO', 'VENCIDO'], true)
            && filled($emisionSeleccionada->url_documento);
        $esConsultaDocumento = $documentoOficialDisponible;
        $vieneDeFinalizados = request('bandeja') === 'finalizados';
        $puedeEnviarSolicitante = $puedeGestionarEmision
            && $vieneDeFinalizados
            && $emisionSeleccionada?->estado === 'EMITIDO';
        $anulacionDisponible = $anulacionDisponible ?? false;
        $puedeAnularEmision = $puedeGestionarEmision && $documentoOficialDisponible && $anulacionDisponible;
        $abrirModalEmision = $errors->emisionCertificado->any() && $datosVigenciaEmision['disponible'];
        $informacionPublicaQr = $informacionPublicaQr ?? [];
        $bandejaEmision = request('bandeja', 'recibidas');
        $volverATablaFinalizados = $bandejaEmision === 'finalizados';

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
        $ultimaRevisionTramite = $certificado->certificadoRequisitos
            ->flatMap(fn ($requisitoCertificado) => $requisitoCertificado->revisionesRequisitos)
            ->sortByDesc('id')
            ->first();
        $ultimoRevisor = $ultimaRevisionTramite?->usuarioRevisor;
        $registrosPorProducto = $certificado->registros->groupBy(
            fn ($registro) => $registro->producto?->id ? 'producto_' . $registro->producto->id : 'registro_' . $registro->id,
        );
        $filasLote = $certificado->certificadosRegistros
            ->filter(fn ($fila) => $fila->registro && filled($fila->registro->numero_lote));
        $puedeCrearEmisionSinVigencia = $esEmisionPorLote
            ? $registroSeleccionado && ! $registroSeleccionado->emision
            : $certificado->emisiones->filter(fn ($emision) => in_array($emision->estado, ['EMITIDO', 'VENCIDO'], true))->isEmpty();
        $puedeCrearEmision = $puedeCrearEmisionSinVigencia && $datosVigenciaEmision['disponible'];
        $papelPlantilla = strtoupper($plantillaCertificado?->tamano_papel ?? 'CARTA');
        $orientacionPlantilla = strtoupper($plantillaCertificado?->orientacion ?? 'VERTICAL');
        $medidasBasePlantilla = $papelPlantilla === 'OFICIO'
            ? ['ancho' => 816, 'alto' => 1344, 'css' => 'legal']
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

        $resolverFondoImagen = function (?string $ruta) use ($plantillaCertificado) {
            if (blank($ruta)) {
                return null;
            }

            $rutaLimpia = ltrim($ruta, '/');
            $esUrl = \Illuminate\Support\Str::startsWith($rutaLimpia, ['http://', 'https://']);
            $esImagen = \Illuminate\Support\Str::endsWith(strtolower($rutaLimpia), ['.jpg', '.jpeg', '.png', '.webp']);

            if ($esUrl) {
                return $esImagen ? $rutaLimpia : null;
            }

            if ($esImagen
                && $plantillaCertificado?->url_fondo
                && ltrim($plantillaCertificado->url_fondo, '/') === $rutaLimpia) {
                return route('certificados_plantillas_archivo', $plantillaCertificado);
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
                ?: $resolverFondoImagen('plantillas/certificados/modelo_certificado.png'));
        // Se conservan los índices del diseño Fabric aunque alguna capa esté inactiva.
        // Así una tabla posterior siempre se reconstruye con el dato que le corresponde.
        $elementosPlantilla = ($plantillaCertificado?->elementos ?? collect())->values();
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
        $estilosTablaPlantilla = function ($elemento) {
            $datos = json_decode((string) ($elemento->texto_fijo ?? ''), true);
            $estilos = is_array($datos['estilos'] ?? null) ? $datos['estilos'] : [];

            return collect($estilos)
                ->map(fn ($fila) => is_array($fila)
                    ? collect($fila)
                        ->map(fn ($estilo) => is_array($estilo) ? $estilo : [])
                        ->values()
                        ->all()
                    : [])
                ->values()
                ->all();
        };
        $elementosEmision = $elementosPlantilla
            ->filter(fn ($elemento) => ($elemento->estado ?? 'ACTIVO') === 'ACTIVO'
                && (bool) data_get($elemento->configuracion ?? [], 'visible', true))
            ->map(function ($elemento, $indice) use ($resolverTextoPlantilla, $filasTablaPlantilla, $estilosTablaPlantilla, $valoresPlantilla, $resolverFondoImagen) {
                $tipo = strtoupper((string) $elemento->tipo_elemento);
                $valor = match ($tipo) {
                    'TEXTO' => $resolverTextoPlantilla($elemento->texto_fijo),
                    'IMAGEN' => str_starts_with((string) $elemento->texto_fijo, 'data:image')
                        ? $elemento->texto_fijo
                        : $resolverFondoImagen($elemento->texto_fijo),
                    'QR' => $valoresPlantilla[$elemento->codigo_elemento] ?? '',
                    'TABLA', 'FORMA', 'LINEA' => null,
                    default => $valoresPlantilla[$elemento->codigo_elemento] ?? '',
                };

                return [
                    'id' => $elemento->id ?? null,
                    'indice_plantilla' => (int) $indice,
                    'tipo_elemento' => $tipo,
                    'codigo_elemento' => $elemento->codigo_elemento ?? null,
                    'texto_plantilla' => $elemento->texto_fijo ?? '',
                    'valor' => $valor,
                    'posicion_x' => (float) ($elemento->posicion_x ?? 0),
                    'posicion_y' => (float) ($elemento->posicion_y ?? 0),
                    'ancho' => (float) ($elemento->ancho ?? 1),
                    'alto' => (float) ($elemento->alto ?? 1),
                    'tamano_letra' => (float) ($elemento->tamano_letra ?? 11),
                    'alineacion' => $elemento->alineacion ?? 'IZQUIERDA',
                    'interlineado' => (float) ($elemento->interlineado ?? 1.25),
                    'negrita' => (bool) ($elemento->negrita ?? false),
                    'cursiva' => (bool) ($elemento->cursiva ?? false),
                    'subrayado' => (bool) ($elemento->subrayado ?? false),
                    'color_texto' => $elemento->color_texto ?? '#0f172a',
                    'tipo_letra' => $elemento->tipo_letra ?? 'Arial',
                    'z_index' => (int) ($elemento->z_index ?? 3),
                    'configuracion' => (array) ($elemento->configuracion ?? []),
                    'columnas' => collect($elemento->columnas ?? [])->map(fn ($columna) => [
                        'codigo_campo' => $columna->codigo_campo,
                        'titulo_columna' => $columna->titulo_columna,
                        'ancho' => (float) ($columna->ancho ?: 25),
                    ])->values()->all(),
                    'filas' => $tipo === 'TABLA' ? $filasTablaPlantilla($elemento) : [],
                    'estilos_celdas' => $tipo === 'TABLA' ? $estilosTablaPlantilla($elemento) : [],
                ];
            })
            ->values()
            ->all();
        $datosEmisionFabric = [
            'ancho' => $medidasPlantilla['ancho'],
            'alto' => $medidasPlantilla['alto'],
            'fondo' => $fondoPlantilla,
            'ajuste_fondo' => $plantillaCertificado?->ajuste_fondo ?? 'ESTIRAR',
            'requiere_qr_verificacion' => (bool) ($usaQrVerificacion ?? false),
            'elementos' => $elementosEmision,
            'diseno_fabric' => $plantillaCertificado?->diseno_fabric_json,
            'valores' => $valoresPlantillaConAlias,
        ];
    @endphp

    <script>
        window.plantillaCertificadoEmision = @json($datosEmisionFabric);
    </script>

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

        .emit-btn:disabled {
            cursor: wait;
            opacity: 0.55;
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

        .emit-btn-danger {
            background: #e11d48;
            border: 1px solid #be123c;
            color: #ffffff;
        }

        .emit-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr);
        }

        .emit-support {
            display: grid;
            gap: 14px;
            grid-row: 2;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .emit-support > .emit-section-wide {
            grid-column: 1 / -1;
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
            grid-row: 1;
            min-width: 0;
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
            overflow: visible;
            white-space: pre-wrap;
        }

        .emit-template-fabric-svg {
            display: block;
            height: 100%;
            inset: 0;
            position: absolute;
            width: 100%;
            z-index: 20;
        }

        .emit-template-fabric-svg svg {
            display: block;
            height: 100%;
            width: 100%;
        }

        .emit-template-fabric-canvas {
            display: none;
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
            box-sizing: border-box;
            border: 1px solid currentColor;
            line-height: 1.15;
            max-width: 0;
            overflow: hidden;
            overflow-wrap: anywhere;
            padding: 4px 6px;
            text-overflow: ellipsis;
            vertical-align: middle;
            white-space: normal;
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

        .emit-qr-summary {
            align-items: stretch;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(180px, .7fr) minmax(0, 1.3fr);
        }

        .emit-qr-note,
        .emit-qr-data {
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 12px;
        }

        .emit-qr-note {
            background: #ecfeff;
            color: #155e75;
            font-size: 12px;
            line-height: 1.45;
        }

        .emit-qr-image {
            background: #fff;
            border: 1px solid #a5f3fc;
            border-radius: 6px;
            display: block;
            height: 116px;
            margin: 12px auto 0;
            padding: 6px;
            width: 116px;
        }

        .emit-qr-image[hidden] {
            display: none;
        }

        .emit-qr-data {
            background: #ffffff;
        }

        .emit-qr-data h3 {
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            margin: 0 0 3px;
        }

        .emit-qr-data > p {
            color: #64748b;
            font-size: 11px;
            line-height: 1.35;
            margin: 0 0 8px;
        }

        .emit-qr-rows {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin: 0;
            overflow: hidden;
        }

        .emit-qr-row {
            display: grid;
            grid-template-columns: minmax(120px, .8fr) minmax(0, 1.2fr);
        }

        .emit-qr-row + .emit-qr-row {
            border-top: 1px solid #e2e8f0;
        }

        .emit-qr-row dt,
        .emit-qr-row dd {
            font-size: 11px;
            line-height: 1.35;
            margin: 0;
            overflow-wrap: anywhere;
            padding: 7px 8px;
        }

        .emit-qr-row dt {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
        }

        .emit-qr-row dd {
            color: #0f172a;
            font-weight: 600;
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
            .emit-support,
            .emit-definition,
            .emit-modal-body,
            .emit-qr-summary {
                grid-template-columns: 1fr;
            }

            .emit-support > .emit-section-wide {
                grid-column: auto;
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
                <h1 class="emit-title">{{ $esConsultaDocumento ? 'Certificado emitido' : 'Emitir certificado' }}</h1>
                <p class="emit-subtitle">
                    {{ $esConsultaDocumento
                        ? 'Consulte el PDF oficial, notifique al solicitante o anule el documento si corresponde.'
                        : 'Revise la información aprobada antes de registrar la emisión.' }}
                </p>
            </div>

            <div class="emit-actions">
                <a href="{{ $volverATablaFinalizados
                    ? route('seguimientos_finalizados')
                    : route('certificados_show', ['certificado' => $certificado, 'bandeja' => $bandejaEmision]) }}"
                    class="emit-btn emit-btn-muted">
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ $volverATablaFinalizados ? 'Volver a trámites finalizados' : 'Volver al trámite' }}
                </a>

                @if ($documentoOficialDisponible)
                    <a href="{{ route('certificados_emitidos_descargar', ['certificado' => $certificado, 'emision' => $emisionSeleccionada, 'visualizar' => 1]) }}"
                        class="emit-btn emit-btn-print" target="_blank" rel="noopener">
                        <i class="fa-solid fa-file-pdf"></i>
                        Ver PDF oficial
                    </a>
                    <a href="{{ route('certificados_emitidos_descargar', ['certificado' => $certificado, 'emision' => $emisionSeleccionada]) }}"
                        class="emit-btn emit-btn-muted">
                        <i class="fa-solid fa-download"></i>
                        Descargar PDF
                    </a>
                @else
                    <button type="button" class="emit-btn emit-btn-print" data-imprimir-certificado
                        data-vista-previa-certificado="1" data-requiere-render-certificado disabled>
                        <i class="fa-solid fa-print"></i>
                        Vista previa / Borrador
                    </button>
                @endif

                @if ($puedeGestionarEmision)
                    @if ($puedeCrearEmision)
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
                            <input type="hidden" name="form_id_certificado_emitido" value="{{ $emisionSeleccionada?->id }}">
                        </form>
                    @endif

                    @if ($puedeAnularEmision)
                        <button type="button" class="emit-btn emit-btn-danger" data-abrir-modal-anulacion>
                            <i class="fa-solid fa-ban"></i>
                            Anular certificado
                        </button>
                    @endif
                @endif
            </div>
        </header>

        @if ($puedeGestionarEmision && $puedeCrearEmisionSinVigencia && ! $datosVigenciaEmision['disponible'])
            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                {{ $datosVigenciaEmision['mensaje'] }}
            </div>
        @endif

        @if ($documentoOficialDisponible && $puedeGestionarEmision && ! $anulacionDisponible)
            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                La anulación estará disponible después de aplicar la actualización de base de datos de certificados emitidos.
            </div>
        @endif

        <div class="emit-modal-backdrop" data-modal-emision @if (!$abrirModalEmision) hidden @endif>
            <form class="emit-modal-card" action="{{ route('certificados_emitir_guardar', $certificado) }}" method="POST" enctype="multipart/form-data"
                data-requiere-qr-verificacion="{{ ($usaQrVerificacion ?? false) ? '1' : '0' }}"
                @if ($usaQrVerificacion ?? false) data-preparar-qr-url="{{ route('certificados_emitir_preparar_qr', $certificado) }}" @endif>
                @csrf
                <input type="hidden" name="form_bandeja" value="{{ request('bandeja', 'finalizados') }}">
                @if ($registroSeleccionado)
                    <input type="hidden" name="form_id_certificado_registro" value="{{ $registroSeleccionado->id }}">
                @endif
                <input type="hidden" name="form_codigo_verificacion" data-codigo-verificacion>
                <textarea name="form_documento_renderizado" data-documento-renderizado hidden></textarea>

                <div class="emit-modal-head">
                    <div>
                        <h2 class="emit-modal-title">Confirmar emisión del certificado</h2>
                        <p class="emit-subtitle">La vigencia se calcula automáticamente con la configuración del tipo de certificado.</p>
                    </div>
                    <button type="button" class="emit-modal-close" data-cerrar-modal-emision aria-label="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="emit-modal-body">
                    @if ($esEmisionPorLote)
                        <div class="emit-modal-field-full rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-2 text-sm text-cyan-900">
                            Se emitirá el certificado del lote <strong>{{ $registroSeleccionado?->registro?->numero_lote }}</strong>
                            · {{ $registroSeleccionado?->registro?->producto?->nombre_comercial ?: 'Sin producto' }}.
                        </div>
                    @endif

                    <div class="emit-modal-field-full grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Fecha de inicio</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $datosVigenciaEmision['fecha_inicio']?->format('d/m/Y') }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Vigencia configurada</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ $datosVigenciaEmision['texto'] }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Fecha final</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">
                                {{ $datosVigenciaEmision['es_indefinida'] ? 'Indefinida' : $datosVigenciaEmision['fecha_fin']?->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>

                    <div class="emit-modal-field-full text-sm text-slate-600">
                        @if ($documentoOficialDisponible)
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                                @if ($resumenDescargas['disponible'])
                                    <a class="inline-flex font-bold text-emerald-700"
                                        href="{{ route('certificados_emitidos_descargar', ['certificado' => $certificado, 'emision' => $emisionSeleccionada]) }}">
                                        <i class="fa-solid fa-download mr-1" aria-hidden="true"></i>
                                        Descargar documento actual
                                    </a>
                                @else
                                    <span class="font-bold text-rose-600">Límite de descargas alcanzado</span>
                                @endif

                                <span class="text-slate-500">
                                    {{ $resumenDescargas['limite'] === null
                                        ? $resumenDescargas['realizadas'].' descargas · Sin límite'
                                        : $resumenDescargas['realizadas'].' de '.$resumenDescargas['limite'].' descargas' }}
                                </span>
                            </div>
                        @else
                            El PDF se generará automáticamente con la plantilla activa al guardar la emisión.
                        @endif
                    </div>

                    <div class="emit-modal-field-full rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm leading-relaxed text-amber-900">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                            <div>
                                <p class="m-0 font-bold">¿Está seguro de emitir este certificado?</p>
                                <p class="mb-0 mt-1">Al confirmar se generará el documento oficial. Después no podrá modificar sus datos; si existe un error deberá anularlo y emitir un certificado nuevo.</p>
                            </div>
                        </div>
                    </div>

                    @if ($usaQrVerificacion ?? false)
                        <div class="emit-modal-field-full emit-qr-summary">
                            <div class="emit-qr-note">
                                <p class="m-0 font-bold">
                                    <i class="fa-solid fa-qrcode mr-1" aria-hidden="true"></i>
                                    QR de verificación
                                </p>
                                <p class="mb-0 mt-2">Use <strong>Vista previa QR</strong> para colocarlo en el certificado. El QR todavía no puede consultarse mientras no guarde la emisión.</p>
                                <img class="emit-qr-image" data-imagen-vista-qr alt="Vista previa del código QR" hidden>
                                <p class="mt-2 hidden font-semibold" data-estado-vista-qr></p>
                            </div>

                            <div class="emit-qr-data">
                                <h3>Información que se mostrará al escanear</h3>
                                <p>Revise estos datos antes de emitir. Esta misma información será pública cuando el certificado quede guardado.</p>
                                <dl class="emit-qr-rows">
                                    @foreach ($informacionPublicaQr as $filaQr)
                                        <div class="emit-qr-row">
                                            <dt>{{ $filaQr['etiqueta'] }}</dt>
                                            <dd>{{ $filaQr['valor'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="emit-modal-actions">
                    <button type="button" class="emit-btn emit-btn-muted" data-cerrar-modal-emision>Cancelar</button>
                    @if ($usaQrVerificacion ?? false)
                        <button type="button" class="emit-btn emit-btn-muted" data-preparar-vista-qr>
                            <i class="fa-solid fa-qrcode"></i>
                            Vista previa QR
                        </button>
                    @endif
                    <button type="submit" class="emit-btn emit-btn-primary" data-requiere-render-certificado disabled>
                        <i class="fa-solid fa-floppy-disk"></i>
                        Sí, emitir certificado
                    </button>
                </div>
            </form>
        </div>

        @if ($puedeAnularEmision)
            <div class="emit-modal-backdrop" data-modal-anulacion @if (!$errors->anulacionCertificado->any()) hidden @endif>
                <form class="emit-modal-card" action="{{ route('certificados_emitidos_anular', ['certificado' => $certificado, 'emision' => $emisionSeleccionada]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="bandeja" value="{{ request('bandeja', 'finalizados') }}">

                    <div class="emit-modal-head">
                        <div>
                            <h2 class="emit-modal-title">Anular certificado</h2>
                            <p class="emit-subtitle">N.º {{ $emisionSeleccionada->numero_certificado }} · Esta acción conserva el PDF, número y QR como historial.</p>
                        </div>
                        <button type="button" class="emit-modal-close" data-cerrar-modal-anulacion aria-label="Cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="emit-modal-body">
                        <div class="emit-modal-field-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                            El QR de este documento mostrará <strong>Anulado</strong>. Si necesita reemplazarlo, se emitirá un documento nuevo con otro número y QR.
                        </div>
                        <label class="emit-modal-field-full text-sm font-bold text-slate-700" for="form_motivo_anulacion">
                            Motivo de anulación
                            <textarea id="form_motivo_anulacion" name="form_motivo_anulacion" rows="4" required maxlength="1000"
                                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-800 focus:border-rose-500 focus:ring-rose-500">{{ old('form_motivo_anulacion') }}</textarea>
                            @error('form_motivo_anulacion', 'anulacionCertificado')
                                <span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <div class="emit-modal-actions">
                        <button type="button" class="emit-btn emit-btn-muted" data-cerrar-modal-anulacion>Cancelar</button>
                        <button type="submit" class="emit-btn emit-btn-danger">
                            <i class="fa-solid fa-ban"></i>
                            Confirmar anulación
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="emit-grid">
            <div class="emit-support">
                @if ($esEmisionPorLote)
                    <section class="emit-section">
                        <div class="emit-section-head">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <h2 class="emit-section-title">Lotes del trámite</h2>
                        </div>
                        <div class="p-3 space-y-2 text-sm">
                            @forelse ($filasLote as $filaLote)
                                @php
                                    $yaEmitido = (bool) $filaLote->emision;
                                    $fueAnulado = ! $yaEmitido && $filaLote->emisiones->contains('estado', 'ANULADO');
                                @endphp
                                <a class="block rounded-lg border px-3 py-2 {{ (int) $registroSeleccionado?->id === (int) $filaLote->id ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200' }}"
                                    href="{{ route('certificados_emitir', ['certificado' => $certificado, 'bandeja' => request('bandeja', 'finalizados'), 'registro' => $filaLote->id]) }}">
                                    <div class="font-semibold">Lote {{ $filaLote->registro->numero_lote }}</div>
                                    <div class="text-xs text-slate-600">{{ $filaLote->registro->producto?->nombre_comercial ?: 'Sin producto' }} · {{ $filaLote->registro->presentacion?->descripcion ?: 'Sin presentación' }}</div>
                                    <div class="mt-1 text-xs font-semibold {{ $yaEmitido ? 'text-emerald-700' : ($fueAnulado ? 'text-rose-700' : 'text-amber-700') }}">
                                        {{ $yaEmitido ? 'Emitido: '.$filaLote->emision->numero_certificado : ($fueAnulado ? 'Anulado · pendiente de reemisión' : 'Pendiente de emisión') }}
                                    </div>
                                    @if ($yaEmitido)
                                        <span class="mt-1 inline-block text-xs font-semibold text-emerald-700">Documento registrado</span>
                                    @endif
                                </a>
                            @empty
                                <p class="text-sm text-rose-600">No hay lotes válidos vinculados al trámite.</p>
                            @endforelse
                        </div>
                    </section>
                @endif
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
                                <dt>Último revisor</dt>
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

                <section class="emit-section emit-section-wide">
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
                                                    {{ $registro->producto?->clasificacionToxicologica?->descripcion ?? 'Sin clasificación toxicológica' }}
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

                <section class="emit-section emit-section-wide">
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
                                    @php
                                        $ultimaRevisionRequisito = $requisitoCertificado->revisionesRequisitos
                                            ->sortByDesc('id')
                                            ->first();
                                        $revisorRequisito = $ultimaRevisionRequisito?->usuarioRevisor;
                                    @endphp
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
                                            {{ $nombreFuncionario($revisorRequisito, 'Sin revisor') }}
                                            <span class="block text-xs font-semibold text-slate-500">
                                                {{ $cargoFuncionario($revisorRequisito) }}
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
                        <canvas class="emit-template-fabric-canvas" data-certificado-fabric-lienzo hidden
                            width="{{ $medidasPlantilla['ancho'] }}" height="{{ $medidasPlantilla['alto'] }}"></canvas>
                        <div class="emit-template-fabric-svg" data-certificado-fabric-svg hidden aria-hidden="true"></div>
                        <div data-certificado-html-fallback>
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
                                    'FORMA', 'LINEA' => 'ELEMENTO_VISUAL',
                                    'QR' => $valoresPlantilla[$elementoPlantilla->codigo_elemento] ?? '',
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
                                    'TEXTO', 'CAMPO', 'FIRMA' => 'is-texto',
                                    'IMAGEN' => 'is-imagen',
                                    'TABLA' => 'is-tabla',
                                    'QR' => 'is-qr',
                                    'FORMA' => 'is-forma',
                                    'LINEA' => 'is-linea',
                                    default => '',
                                };
                                $configuracionElemento = (array) ($elementoPlantilla->configuracion ?? []);
                                $configuracionTabla = (array) data_get($configuracionElemento, 'tabla', []);
                                $tablaCampoValor = data_get($configuracionTabla, 'tipo') === 'CAMPO_VALOR';
                                $tablaMostrarEncabezado = (bool) data_get($configuracionTabla, 'mostrar_encabezado', true);
                                $tablaEtiquetasNegrita = (bool) data_get($configuracionTabla, 'etiquetas_negrita', true);
                                $tablaSinBordes = data_get($configuracionTabla, 'bordes', 'TODOS') === 'NINGUNO';
                                $tablaBorde = (float) data_get($configuracionTabla, 'borde_ancho', 1).'px solid '.data_get($configuracionTabla, 'borde_color', '#0f172a');
                                $bordeEstiloElemento = match (data_get($configuracionElemento, 'borde_estilo', 'SOLIDO')) {
                                    'PUNTEADO' => 'dotted',
                                    'DISCONTINUO' => 'dashed',
                                    'DOBLE' => 'double',
                                    'NINGUNO' => 'none',
                                    default => 'solid',
                                };
                                // Fabric.js no suma relleno al contenido del Textbox. Añadirlo aquí reduce el área
                                // útil y recorta textos aunque las coordenadas guardadas sean correctas.
                                $paddingXElemento = 0;
                                $paddingYElemento = 0;
                            @endphp

                            @if (filled($valorCampo))
                                <div class="emit-template-field {{ $claseTipoElemento }} {{ $claseAlineacion }}"
                                    data-elemento-id="{{ $elementoPlantilla->id }}"
                                    style="
                                        left: {{ (float) $elementoPlantilla->posicion_x }}px;
                                        top: {{ (float) $elementoPlantilla->posicion_y }}px;
                                        width: {{ (float) $elementoPlantilla->ancho }}px;
                                        height: {{ (float) $elementoPlantilla->alto }}px;
                                        font-size: {{ (int) $elementoPlantilla->tamano_letra }}px;
                                        font-weight: {{ data_get($elementoPlantilla, 'negrita') ? 700 : 400 }};
                                        font-style: {{ data_get($elementoPlantilla, 'cursiva') ? 'italic' : 'normal' }};
                                        text-decoration: {{ data_get($elementoPlantilla, 'subrayado') ? 'underline' : 'none' }};
                                        color: {{ data_get($elementoPlantilla, 'color_texto', '#0f172a') ?: '#0f172a' }};
                                        font-family: '{{ data_get($elementoPlantilla, 'tipo_letra', 'Arial') ?: 'Arial' }}', Arial, sans-serif;
                                        line-height: {{ (float) data_get($elementoPlantilla, 'interlineado', 1.25) }};
                                        padding: {{ $paddingYElemento }}px {{ $paddingXElemento }}px;
                                        background: {{ $elementoPlantilla->tipo_elemento === 'FORMA' ? data_get($configuracionElemento, 'fondo_color', 'transparent') : 'transparent' }};
                                        border: {{ $elementoPlantilla->tipo_elemento === 'FORMA' && $bordeEstiloElemento !== 'none' ? (float) data_get($configuracionElemento, 'borde_ancho', 0).'px '.$bordeEstiloElemento.' '.data_get($configuracionElemento, 'borde_color', '#0f766e') : '0' }};
                                        border-radius: {{ $elementoPlantilla->tipo_elemento === 'FORMA' && data_get($configuracionElemento, 'forma') === 'ELIPSE' ? '50%' : (float) data_get($configuracionElemento, 'radio_borde', 0).'px' }};
                                        opacity: {{ (float) data_get($configuracionElemento, 'opacidad', 1) }};
                                        transform: rotate({{ (float) data_get($configuracionElemento, 'rotacion', 0) }}deg);
                                        transform-origin: center center;
                                        z-index: {{ (int) data_get($elementoPlantilla, 'z_index', 3) }};
                                    ">
                                    @if ($elementoPlantilla->tipo_elemento === 'IMAGEN')
                                        <img src="{{ $valorCampo }}" alt="Imagen del certificado" style="display:block; width:100%; height:100%; object-fit:contain;">
                                    @elseif ($esImagenQr)
                                        <img src="{{ $valorCampo }}" alt="QR del certificado" style="display:block; width:100%; height:100%; object-fit:contain;">
                                    @elseif ($elementoPlantilla->tipo_elemento === 'TABLA')
                                        <table class="emit-template-word-table">
                                            <colgroup>
                                                @foreach ($elementoPlantilla->columnas as $columna)
                                                    <col style="width: {{ (float) ($columna->ancho ?: 25) }}%;">
                                                @endforeach
                                            </colgroup>
                                            @if ($tablaMostrarEncabezado)
                                                <thead>
                                                    <tr>
                                                        @forelse ($elementoPlantilla->columnas as $columna)
                                                            <th style="border: {{ $tablaSinBordes ? '0' : $tablaBorde }};">
                                                                {{ $columna->titulo_columna ?: $columna->codigo_campo }}
                                                            </th>
                                                        @empty
                                                            <th>Dato</th>
                                                        @endforelse
                                                    </tr>
                                                </thead>
                                            @endif
                                            <tbody>
                                                @forelse ($filasTabla as $filaTabla)
                                                    <tr>
                                                        @foreach ($filaTabla as $celdaTabla)
                                                            <td style="
                                                                border: {{ $tablaSinBordes ? '0' : $tablaBorde }};
                                                                background: {{ $tablaCampoValor ? 'transparent' : data_get($configuracionTabla, 'celdas_fondo', '#ffffff') }};
                                                                font-weight: {{ data_get($elementoPlantilla, 'negrita') || ($tablaCampoValor && $tablaEtiquetasNegrita && $loop->iteration % 2 === 1) ? 700 : 400 }};
                                                                padding: {{ (float) data_get($configuracionTabla, 'padding_y', 4) }}px {{ (float) data_get($configuracionTabla, 'padding_x', 6) }}px;
                                                            ">{{ $celdaTabla }}</td>
                                                        @endforeach
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>Sin datos</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    @elseif ($elementoPlantilla->tipo_elemento === 'LINEA')
                                        <span style="display:block; position:absolute; left:0; right:0; top:50%; border-top: {{ max(1, (float) data_get($configuracionElemento, 'borde_ancho', 1)) }}px {{ $bordeEstiloElemento === 'none' ? 'solid' : $bordeEstiloElemento }} {{ data_get($configuracionElemento, 'borde_color', '#0f766e') }};"></span>
                                    @elseif ($elementoPlantilla->tipo_elemento === 'FORMA')
                                    @else
                                        {!! $elementoPlantilla instanceof \App\Models\PlantillaElemento && $elementoPlantilla->tipo_elemento === 'TEXTO'
                                            ? $elementoPlantilla->textoConEstilosResueltos($valoresPlantillaConAlias)
                                            : nl2br(e($valorCampo)) !!}
                                    @endif
                                </div>
                            @endif
                        @endforeach
                        </div>
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

                    .emit-template-fabric-canvas,
                    [data-certificado-html-fallback][hidden] {
                        display: none !important;
                    }

                    .emit-template-fabric-svg {
                        display: block;
                        height: 100%;
                        inset: 0;
                        position: absolute;
                        width: 100%;
                    }

                    .emit-template-fabric-svg svg {
                        display: block;
                        height: 100%;
                        width: 100%;
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
                        overflow: visible;
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
                        box-sizing: border-box;
                        border: 1px solid currentColor;
                        line-height: 1.15;
                        max-width: 0;
                        overflow: hidden;
                        overflow-wrap: anywhere;
                        padding: 4px 6px;
                        text-overflow: ellipsis;
                        vertical-align: middle;
                        white-space: normal;
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

                botonImprimir.addEventListener('click', async function () {
                    try {
                        await window.prepararVistaQrEmision?.();
                    } catch (error) {
                        window.alert(error?.message || 'No se pudo generar el QR para el borrador.');
                        return;
                    }
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

                    if (botonImprimir.dataset.vistaPreviaCertificado === '1') {
                        const marca = documento.createElement('div');
                        marca.textContent = 'BORRADOR';
                        marca.style.cssText = [
                            'color:rgba(190,24,93,.20)',
                            'font-family:Arial,sans-serif',
                            'font-size:72pt',
                            'font-weight:900',
                            'left:50%',
                            'pointer-events:none',
                            'position:fixed',
                            'top:50%',
                            'transform:translate(-50%,-50%) rotate(-28deg)',
                            'z-index:9999',
                        ].join(';');
                        documento.body.appendChild(marca);
                    }
                    imprimirCuandoCargue(frame);
                });
            }

            function prepararModalEmision() {
                const modal = document.querySelector('[data-modal-emision]');
                const botonAbrir = document.querySelector('[data-abrir-modal-emision]');
                const botonesCerrar = document.querySelectorAll('[data-cerrar-modal-emision]');
                const formulario = modal?.querySelector('form');
                const botonVistaQr = formulario?.querySelector('[data-preparar-vista-qr]');
                const estadoVistaQr = formulario?.querySelector('[data-estado-vista-qr]');
                const imagenVistaQr = formulario?.querySelector('[data-imagen-vista-qr]');

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

                let preparacionQrEnCurso = null;
                function prepararVistaQr() {
                    if (!preparacionQrEnCurso) {
                        preparacionQrEnCurso = generarVistaQr().finally(() => { preparacionQrEnCurso = null; });
                    }
                    return preparacionQrEnCurso;
                }

                async function generarVistaQr() {
                    if (formulario.dataset.requiereQrVerificacion !== '1') return;

                    const campoCodigo = formulario.querySelector('[data-codigo-verificacion]');
                    if (formulario.dataset.qrPreparado === '1' && campoCodigo?.value) return;

                    const respuesta = await fetch(formulario.dataset.prepararQrUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': formulario.querySelector('input[name="_token"]')?.value || '',
                        },
                    });
                    const contenido = await respuesta.json().catch(() => ({}));

                    if (!respuesta.ok || !contenido.codigo_verificacion || !contenido.imagen_qr) {
                        throw new Error(contenido.message || 'No se pudo preparar el código QR de verificación.');
                    }

                    if (typeof window.prepararQrCertificadoEmision !== 'function') {
                        throw new Error('No se pudo incorporar el código QR al documento. Recargue la página e intente nuevamente.');
                    }

                    await window.prepararQrCertificadoEmision(contenido.imagen_qr);
                    campoCodigo.value = contenido.codigo_verificacion;
                    formulario.dataset.qrPreparado = '1';

                    if (imagenVistaQr) {
                        imagenVistaQr.src = contenido.imagen_qr;
                        imagenVistaQr.hidden = false;
                    }

                    if (estadoVistaQr) {
                        estadoVistaQr.hidden = false;
                        estadoVistaQr.textContent = 'Vista previa preparada. Cierre este cuadro para revisar la posición del QR; al emitir se usará este mismo código.';
                    }
                }

                window.prepararVistaQrEmision = prepararVistaQr;
                const prepararAutomaticamente = () => {
                    if (typeof window.prepararQrCertificadoEmision !== 'function') return;
                    prepararVistaQr().catch(error => {
                        let aviso = document.querySelector('[data-error-qr-automatico]');
                        if (!aviso) {
                            aviso = document.createElement('p');
                            aviso.dataset.errorQrAutomatico = '1';
                            aviso.className = 'text-sm text-red-700';
                            document.querySelector('.emit-actions')?.after(aviso);
                        }
                        aviso.textContent = error?.message || 'No se pudo generar el QR. Intente nuevamente con Vista previa QR.';
                    });
                };
                document.addEventListener('certificado:lienzo-listo', prepararAutomaticamente, { once: true });
                prepararAutomaticamente();

                botonVistaQr?.addEventListener('click', async () => {
                    botonVistaQr.disabled = true;
                    try {
                        await prepararVistaQr();
                    } catch (error) {
                        window.alert(error?.message || 'No se pudo preparar la vista previa del QR.');
                    } finally {
                        botonVistaQr.disabled = false;
                    }
                });

                let preparandoEnvio = false;
                formulario?.addEventListener('submit', async (evento) => {
                    if (preparandoEnvio) return;

                    evento.preventDefault();
                    if (!formulario.reportValidity()) return;

                    preparandoEnvio = true;
                    const botones = formulario.querySelectorAll('button');
                    botones.forEach((boton) => { boton.disabled = true; });

                    try {
                        if (formulario.dataset.requiereQrVerificacion === '1') {
                            await prepararVistaQr();
                        }

                        HTMLFormElement.prototype.submit.call(formulario);
                    } catch (error) {
                        preparandoEnvio = false;
                        botones.forEach((boton) => { boton.disabled = false; });
                        window.alert(error?.message || 'No se pudo preparar la emisión. Intente nuevamente.');
                    }
                });
            }

            function prepararModalAnulacion() {
                const modal = document.querySelector('[data-modal-anulacion]');
                const botonAbrir = document.querySelector('[data-abrir-modal-anulacion]');
                const botonesCerrar = document.querySelectorAll('[data-cerrar-modal-anulacion]');

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
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    prepararImpresionCertificado();
                    prepararModalEmision();
                    prepararModalAnulacion();
                });
            } else {
                prepararImpresionCertificado();
                prepararModalEmision();
                prepararModalAnulacion();
            }
        })();
    </script>
</x-admin-layout>

