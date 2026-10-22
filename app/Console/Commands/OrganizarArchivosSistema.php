<?php

namespace App\Console\Commands;

use App\Models\CertificadoEmitido;
use App\Models\EvidenciaRequisito;
use App\Models\PagoOpenBcb;
use App\Models\PlantillaCertificado;
use App\Models\Presentacion;
use App\Models\Responsable;
use App\Services\ArchivoPrivadoService;
use App\Services\EtiquetaPresentacionService;
use App\Services\ExpedienteTramiteService;
use App\Services\PlantillaCertificadoStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OrganizarArchivosSistema extends Command
{
    protected $signature = 'archivos:organizar
        {--dry-run : Solo informa los cambios sin mover archivos ni actualizar datos}
        {--eliminar-huerfanos : Elimina archivos no referenciados que permanezcan en las carpetas anteriores}';

    protected $description = 'Centraliza los archivos en storage/app/private y los ordena por módulo e identificador';

    private int $organizados = 0;

    private int $sinArchivo = 0;

    private int $errores = 0;

    public function handle(
        ArchivoPrivadoService $archivos,
        PlantillaCertificadoStorageService $plantillas,
        EtiquetaPresentacionService $etiquetas,
        ExpedienteTramiteService $expedientes,
    ): int {
        $simulacion = (bool) $this->option('dry-run');

        $this->organizarPlantillas($archivos, $plantillas, $simulacion);
        $this->organizarEtiquetas($archivos, $etiquetas, $simulacion);
        $this->organizarRespaldos($archivos, $simulacion);
        $this->organizarEvidenciasRequisitos($archivos, $expedientes, $simulacion);
        $this->organizarCertificadosEmitidos($archivos, $simulacion);
        $this->organizarCodigosQr($archivos, $simulacion);

        if (! $simulacion) {
            $this->eliminarDirectoriosAnterioresVacios();

            if ($this->option('eliminar-huerfanos')) {
                $this->eliminarArchivosHuerfanosAnteriores();
            }
        }

        $prefijo = $simulacion ? 'Cambios previstos' : 'Archivos organizados';
        $this->info("{$prefijo}: {$this->organizados}.");

        if ($this->sinArchivo > 0) {
            $this->warn("Referencias sin archivo físico: {$this->sinArchivo}.");
        }

        if ($this->errores > 0) {
            $this->error("Registros con error: {$this->errores}.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function organizarPlantillas(
        ArchivoPrivadoService $archivos,
        PlantillaCertificadoStorageService $plantillas,
        bool $simulacion,
    ): void {
        PlantillaCertificado::withTrashed()
            ->whereNotNull('url_fondo')
            ->where('url_fondo', '<>', '')
            ->chunkById(100, function ($registros) use ($archivos, $plantillas, $simulacion) {
                foreach ($registros as $plantilla) {
                    $rutaActual = $archivos->normalizarNullable($plantilla->url_fondo);
                    $extension = strtolower(pathinfo((string) $rutaActual, PATHINFO_EXTENSION) ?: 'bin');
                    $rutaNueva = $plantillas->directorio($plantilla)
                        .'/'.$plantilla->id.'-plantilla.'.$extension;

                    $this->procesar(
                        'plantilla '.$plantilla->id,
                        $rutaActual,
                        $rutaNueva,
                        $archivos,
                        $simulacion,
                        fn (string $ruta) => $plantilla->update(['url_fondo' => $ruta]),
                    );
                }
            });
    }

    private function organizarEtiquetas(
        ArchivoPrivadoService $archivos,
        EtiquetaPresentacionService $etiquetas,
        bool $simulacion,
    ): void {
        Presentacion::withTrashed()
            ->with([
                'producto' => fn ($consulta) => $consulta
                    ->withTrashed()
                    ->with('importadorPersona.empresa'),
            ])
            ->whereNotNull('url_etiqueta')
            ->where('url_etiqueta', '<>', '')
            ->chunkById(100, function ($registros) use ($archivos, $etiquetas, $simulacion) {
                foreach ($registros as $presentacion) {
                    if (! $presentacion->producto) {
                        $this->sinArchivo++;
                        $this->warn("La presentación {$presentacion->id} no tiene un producto asociado.");
                        continue;
                    }

                    $rutaActual = $archivos->normalizarNullable($presentacion->url_etiqueta);
                    $extension = strtolower(pathinfo((string) $rutaActual, PATHINFO_EXTENSION) ?: 'bin');
                    $rutaNueva = $etiquetas->directorio($presentacion->producto)
                        .'/'.$presentacion->id.'-etiqueta.'.$extension;

                    $this->procesar(
                        'etiqueta de presentación '.$presentacion->id,
                        $rutaActual,
                        $rutaNueva,
                        $archivos,
                        $simulacion,
                        fn (string $ruta) => $presentacion->update(['url_etiqueta' => $ruta]),
                    );
                }
            });
    }

    private function organizarRespaldos(ArchivoPrivadoService $archivos, bool $simulacion): void
    {
        Responsable::withTrashed()
            ->with('rol')
            ->whereNotNull('url_respaldo')
            ->where('url_respaldo', '<>', '')
            ->chunkById(100, function ($registros) use ($archivos, $simulacion) {
                foreach ($registros as $responsable) {
                    $rutaActual = $archivos->normalizarNullable($responsable->url_respaldo);
                    $extension = strtolower(pathinfo((string) $rutaActual, PATHINFO_EXTENSION) ?: 'pdf');
                    $empresa = $responsable->id_empresa ?: 'pendientes';
                    $esTramitador = $responsable->rol?->slug === 'tramitador';
                    $rutaNueva = $esTramitador
                        ? "tramitadores/empresas/{$empresa}/{$responsable->id}-responsable-respaldo.{$extension}"
                        : "personas-empresas/empresas/{$empresa}/representantes-legales/{$responsable->id}-responsable-respaldo.{$extension}";

                    $this->procesar(
                        'respaldo de responsable '.$responsable->id,
                        $rutaActual,
                        $rutaNueva,
                        $archivos,
                        $simulacion,
                        fn (string $ruta) => $responsable->update(['url_respaldo' => $ruta]),
                    );
                }
            });
    }

    private function organizarCertificadosEmitidos(ArchivoPrivadoService $archivos, bool $simulacion): void
    {
        CertificadoEmitido::withTrashed()
            ->whereNotNull('url_documento')
            ->where('url_documento', '<>', '')
            ->chunkById(100, function ($registros) use ($archivos, $simulacion) {
                foreach ($registros as $emitido) {
                    $rutaActual = $archivos->normalizarNullable($emitido->url_documento);
                    $rutaNueva = str_replace('/certificados/emisiones/', '/certificados/emitidos/', (string) $rutaActual);

                    $this->procesar(
                        'certificado emitido '.$emitido->id,
                        $rutaActual,
                        $rutaNueva,
                        $archivos,
                        $simulacion,
                        fn (string $ruta) => $emitido->update(['url_documento' => $ruta]),
                    );
                }
            });
    }

    private function organizarEvidenciasRequisitos(
        ArchivoPrivadoService $archivos,
        ExpedienteTramiteService $expedientes,
        bool $simulacion,
    ): void
    {
        EvidenciaRequisito::withTrashed()
            ->with(['requisitoCertificado' => fn ($consulta) => $consulta->withTrashed()])
            ->whereNotNull('valor')
            ->where('valor', '<>', '')
            ->chunkById(100, function ($registros) use ($archivos, $expedientes, $simulacion) {
                foreach ($registros as $evidencia) {
                    $rutaActual = $archivos->normalizarNullable($evidencia->valor);

                    if (! $rutaActual || ! str_contains($rutaActual, '/') || ! preg_match('/\.[a-z0-9]{2,5}$/i', $rutaActual)) {
                        continue;
                    }

                    $requisito = $evidencia->requisitoCertificado;
                    if (! $requisito?->id_certificado) {
                        $this->sinArchivo++;
                        $this->warn("La evidencia {$evidencia->id} no tiene un trámite relacionado.");
                        continue;
                    }

                    $extension = strtolower(pathinfo($rutaActual, PATHINFO_EXTENSION) ?: 'bin');
                    $numeroRespaldo = EvidenciaRequisito::withTrashed()
                        ->where('id_requisito_certificado', $requisito->id)
                        ->where('id', '<=', $evidencia->id)
                        ->count();
                    $nombreArchivo = $expedientes->nombreDocumentoEvidencia(
                        $numeroRespaldo,
                    );
                    $rutaNueva = 'tramites/'.$requisito->id_certificado
                        .'/requisitos/'.$requisito->id
                        .'/'.$nombreArchivo.'.'.$extension;

                    $this->procesar(
                        'evidencia '.$evidencia->id,
                        $rutaActual,
                        $rutaNueva,
                        $archivos,
                        $simulacion,
                        fn (string $ruta) => $evidencia->update(['valor' => $ruta]),
                    );
                }
            });
    }

    private function organizarCodigosQr(ArchivoPrivadoService $archivos, bool $simulacion): void
    {
        PagoOpenBcb::withTrashed()
            ->whereNotNull('imagen_qr_path')
            ->where('imagen_qr_path', '<>', '')
            ->chunkById(100, function ($registros) use ($archivos, $simulacion) {
                foreach ($registros as $codigoQr) {
                    $rutaActual = $archivos->normalizarNullable($codigoQr->imagen_qr_path);
                    $extension = strtolower(pathinfo((string) $rutaActual, PATHINFO_EXTENSION) ?: 'jpg');
                    $identificador = Str::slug((string) ($codigoQr->id_qr ?: $codigoQr->id), '_');
                    $rutaNueva = "openbcb/qr/{$codigoQr->id_pago}-pago/{$identificador}-qr.{$extension}";

                    $this->procesar(
                        'QR OpenBCB '.$codigoQr->id,
                        $rutaActual,
                        $rutaNueva,
                        $archivos,
                        $simulacion,
                        fn (string $ruta) => $codigoQr->update(['imagen_qr_path' => $ruta]),
                    );
                }
            });
    }

    private function procesar(
        string $descripcion,
        ?string $rutaActual,
        string $rutaNueva,
        ArchivoPrivadoService $archivos,
        bool $simulacion,
        callable $actualizar,
    ): void {
        if (! $rutaActual) {
            return;
        }

        if ($rutaActual === $rutaNueva && Storage::disk(ArchivoPrivadoService::DISCO)->exists($rutaNueva)) {
            return;
        }

        if (! $archivos->existe($rutaActual)) {
            $this->sinArchivo++;
            $this->warn("No se encontró {$descripcion}: {$rutaActual}");
            return;
        }

        $this->organizados++;
        $this->line("- {$descripcion}: {$rutaActual} -> {$rutaNueva}");

        if ($simulacion) {
            return;
        }

        try {
            $resultado = $archivos->moverAPrivado($rutaActual, $rutaNueva);

            if ($resultado !== $rutaNueva) {
                throw new \RuntimeException('No se confirmó la nueva ubicación del archivo.');
            }

            $actualizar($rutaNueva);
        } catch (Throwable $exception) {
            $this->errores++;
            $this->error("No se pudo organizar {$descripcion}: {$exception->getMessage()}");
        }
    }

    private function eliminarDirectoriosAnterioresVacios(): void
    {
        $directorios = [
            'empresas-productos',
            'empresas-tramitadores',
            'plantillas/certificados',
            'catalogos-certificados',
            'documentos/plantillas_certificados',
            'productos/etiquetas',
            'productos/empresas',
            'productos/personas',
            'personas',
        ];

        foreach (['public', ArchivoPrivadoService::DISCO] as $disco) {
            foreach ($directorios as $directorio) {
                if (Storage::disk($disco)->allFiles($directorio) === []) {
                    Storage::disk($disco)->deleteDirectory($directorio);
                }
            }
        }

        $directoriosProductos = Storage::disk(ArchivoPrivadoService::DISCO)->allDirectories('productos');
        usort($directoriosProductos, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($directoriosProductos as $directorio) {
            if (Storage::disk(ArchivoPrivadoService::DISCO)->allFiles($directorio) === []) {
                Storage::disk(ArchivoPrivadoService::DISCO)->deleteDirectory($directorio);
            }
        }

        $directoriosPlantillas = Storage::disk(ArchivoPrivadoService::DISCO)->allDirectories('plantillas');
        usort($directoriosPlantillas, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($directoriosPlantillas as $directorio) {
            if (Storage::disk(ArchivoPrivadoService::DISCO)->allFiles($directorio) === []) {
                Storage::disk(ArchivoPrivadoService::DISCO)->deleteDirectory($directorio);
            }
        }
    }

    private function eliminarArchivosHuerfanosAnteriores(): void
    {
        $directoriosPublicos = [
            'empresas-productos',
            'empresas-tramitadores',
            'plantillas/certificados',
            'catalogos-certificados',
            'documentos/plantillas_certificados',
            'productos/etiquetas',
            'productos/empresas',
            'productos/personas',
            'personas',
            'openbcb/qr',
        ];
        $directoriosPrivadosAnteriores = [
            'empresas-tramitadores',
        ];
        $eliminados = 0;

        foreach ($directoriosPublicos as $directorio) {
            $eliminados += count(Storage::disk('public')->allFiles($directorio));
            Storage::disk('public')->deleteDirectory($directorio);
        }

        foreach ($directoriosPrivadosAnteriores as $directorio) {
            $eliminados += count(Storage::disk(ArchivoPrivadoService::DISCO)->allFiles($directorio));
            Storage::disk(ArchivoPrivadoService::DISCO)->deleteDirectory($directorio);
        }

        $this->info("Archivos huérfanos eliminados de carpetas anteriores: {$eliminados}.");
    }
}
