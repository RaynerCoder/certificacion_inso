<?php

namespace App\Console\Commands;

use App\Models\PlantillaCertificado;
use App\Services\PlantillaCertificadoStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OrganizarPlantillasCertificados extends Command
{
    protected $signature = 'certificados:organizar-plantillas';

    protected $description = 'Organiza los fondos en una carpeta propia por plantilla de certificado';

    public function handle(PlantillaCertificadoStorageService $archivos): int
    {
        $movidas = 0;
        $archivadas = 0;

        PlantillaCertificado::withTrashed()
            ->whereNotNull('url_fondo')
            ->where('url_fondo', '<>', '')
            ->chunkById(100, function ($plantillas) use ($archivos, &$movidas) {
                foreach ($plantillas as $plantilla) {
                    $rutaAnterior = $plantilla->url_fondo;
                    $rutaNueva = $archivos->organizarExistente($plantilla);

                    if ($rutaNueva !== $rutaAnterior) {
                        $plantilla->update(['url_fondo' => $rutaNueva]);
                        $movidas++;
                    }
                }
            });

        foreach (Storage::disk('public')->files('documentos/plantillas_certificados') as $rutaAnterior) {
            $archivos->archivarArchivoAnterior($rutaAnterior);
            $archivadas++;
        }

        if (Storage::disk('public')->allFiles('documentos/plantillas_certificados') === []) {
            Storage::disk('public')->deleteDirectory('documentos/plantillas_certificados');
        }

        $this->info("Plantillas organizadas: {$movidas}.");
        $this->info("Plantillas históricas conservadas: {$archivadas}.");

        return self::SUCCESS;
    }
}
