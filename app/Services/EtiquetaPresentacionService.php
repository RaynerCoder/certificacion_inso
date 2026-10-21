<?php

namespace App\Services;

use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class EtiquetaPresentacionService
{
    /**
     * Guarda la etiqueta junto con las demás imágenes de presentación del producto.
     */
    public function guardar(UploadedFile $archivo, Producto $producto): string
    {
        $ruta = $archivo->store($this->directorio($producto), 'public');

        if (! is_string($ruta) || $ruta === '') {
            throw new RuntimeException('No se pudo guardar la etiqueta de la presentación.');
        }

        return $ruta;
    }

    /**
     * Mueve una etiqueta antigua a la estructura organizada y devuelve su nueva ruta.
     */
    public function organizarExistente(Producto $producto, Presentacion $presentacion): ?string
    {
        $rutaActual = $this->normalizarRuta($presentacion->url_etiqueta);

        if (! $rutaActual) {
            return null;
        }

        $directorio = $this->directorio($producto);

        if (str_replace('\\', '/', dirname($rutaActual)) === $directorio) {
            return $rutaActual;
        }

        if (! Storage::disk('public')->exists($rutaActual)) {
            return $rutaActual;
        }

        $nombre = basename($rutaActual);
        $rutaNueva = $directorio.'/'.$nombre;

        if (Storage::disk('public')->exists($rutaNueva)) {
            $extension = pathinfo($nombre, PATHINFO_EXTENSION);
            $rutaNueva = $directorio.'/'.Str::uuid()->toString()
                .($extension !== '' ? '.'.strtolower($extension) : '');
        }

        if (! Storage::disk('public')->move($rutaActual, $rutaNueva)) {
            throw new RuntimeException('No se pudo organizar la etiqueta de la presentación #'.$presentacion->id.'.');
        }

        $directorioAnterior = str_replace('\\', '/', dirname($rutaActual));
        if ($directorioAnterior !== '.'
            && $directorioAnterior !== $directorio
            && Storage::disk('public')->allFiles($directorioAnterior) === []) {
            Storage::disk('public')->deleteDirectory($directorioAnterior);
        }

        return $rutaNueva;
    }

    public function directorio(Producto $producto): string
    {
        $producto->loadMissing('importadorPersona.empresa');

        $empresa = $producto->importadorPersona?->empresa;
        $propietario = $empresa
            ? 'empresas-productos/'.$empresa->id
            : 'personas/'.$producto->id_importador_persona;

        return $propietario
            .'/productos/'.$producto->id
            .'/presentaciones';
    }

    private function normalizarRuta(?string $ruta): ?string
    {
        $ruta = trim(str_replace('\\', '/', (string) $ruta));

        if ($ruta === '') {
            return null;
        }

        $ruta = ltrim($ruta, '/');

        return str_starts_with($ruta, 'storage/')
            ? substr($ruta, strlen('storage/'))
            : $ruta;
    }
}
