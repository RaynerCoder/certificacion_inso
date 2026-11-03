<?php

namespace App\Services;

use App\Models\PlantillaCertificado;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class PlantillaCertificadoStorageService
{
    public function __construct(private readonly ArchivoPrivadoService $archivos) {}

    public function guardar(UploadedFile $archivo, PlantillaCertificado $plantilla): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'bin');
        $ruta = $this->directorio($plantilla).'/'.$plantilla->id.'-plantilla.'.$extension;
        $rutaAnterior = $plantilla->url_fondo;

        $this->archivos->guardarSubido($archivo, $ruta);

        if ($rutaAnterior && $this->normalizarRuta($rutaAnterior) !== $ruta) {
            $this->archivos->eliminar($rutaAnterior);
        }

        return $ruta;
    }

    public function organizarExistente(PlantillaCertificado $plantilla): ?string
    {
        $rutaActual = $this->normalizarRuta($plantilla->url_fondo);

        if (! $rutaActual) {
            return null;
        }

        $extension = strtolower(pathinfo($rutaActual, PATHINFO_EXTENSION) ?: 'bin');
        $rutaNueva = $this->directorio($plantilla).'/'.$plantilla->id.'-plantilla.'.$extension;

        return $this->archivos->moverAPrivado($rutaActual, $rutaNueva);
    }

    /**
     * Genera una copia física del fondo para que una plantilla reutilizada no
     * dependa del archivo de la plantilla de origen.
     */
    public function copiarFondo(PlantillaCertificado $origen, PlantillaCertificado $destino): ?string
    {
        $rutaOrigen = $origen->url_fondo;
        $archivoOrigen = $this->archivos->rutaAbsoluta($rutaOrigen);

        if (! $rutaOrigen || ! $archivoOrigen || ! File::exists($archivoOrigen)) {
            return null;
        }

        $extension = strtolower(pathinfo($rutaOrigen, PATHINFO_EXTENSION) ?: 'bin');
        $rutaDestino = $this->directorio($destino).'/'.$destino->id.'-plantilla.'.$extension;

        return $this->archivos->guardarContenido($rutaDestino, File::get($archivoOrigen));
    }

    public function directorio(PlantillaCertificado $plantilla): string
    {
        if (! $plantilla->id) {
            throw new RuntimeException('La plantilla debe existir antes de guardar su archivo de fondo.');
        }

        if (! $plantilla->id_tipo_certificado) {
            throw new RuntimeException('La plantilla debe estar asociada con un tipo de certificado.');
        }

        return 'plantillas/'.$plantilla->id_tipo_certificado.'-certificado';
    }

    private function normalizarRuta(?string $ruta): ?string
    {
        return $this->archivos->normalizarNullable($ruta);
    }
}
