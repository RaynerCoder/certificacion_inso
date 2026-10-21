<?php

namespace App\Console\Commands;

use App\Models\Presentacion;
use App\Services\EtiquetaPresentacionService;
use Illuminate\Console\Command;

class OrganizarEtiquetasPresentaciones extends Command
{
    protected $signature = 'productos:organizar-etiquetas';

    protected $description = 'Organiza las imágenes en empresas-productos por empresa, producto y presentación';

    public function handle(EtiquetaPresentacionService $etiquetas): int
    {
        $movidas = 0;
        $sinArchivo = 0;

        Presentacion::withTrashed()
            ->with([
                'producto' => fn ($consulta) => $consulta
                    ->withTrashed()
                    ->with('importadorPersona.empresa'),
            ])
            ->whereNotNull('url_etiqueta')
            ->where('url_etiqueta', '<>', '')
            ->chunkById(100, function ($presentaciones) use ($etiquetas, &$movidas, &$sinArchivo) {
                foreach ($presentaciones as $presentacion) {
                    $producto = $presentacion->producto;

                    if (! $producto) {
                        $sinArchivo++;

                        continue;
                    }

                    $rutaAnterior = $presentacion->url_etiqueta;
                    $rutaNueva = $etiquetas->organizarExistente($producto, $presentacion);

                    if ($rutaNueva !== $rutaAnterior) {
                        $presentacion->update(['url_etiqueta' => $rutaNueva]);
                        $movidas++;
                    } elseif (! $rutaNueva) {
                        $sinArchivo++;
                    }
                }
            });

        $this->info("Etiquetas organizadas: {$movidas}.");

        if ($sinArchivo > 0) {
            $this->warn("Presentaciones sin archivo disponible: {$sinArchivo}.");
        }

        return self::SUCCESS;
    }
}
