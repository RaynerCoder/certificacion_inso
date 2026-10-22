<?php

namespace App\Services;

use App\Models\Presentacion;
use App\Models\Producto;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class EtiquetaPresentacionService
{
    public function __construct(private readonly ArchivoPrivadoService $archivos) {}

    public function guardar(UploadedFile $archivo, Producto $producto, Presentacion $presentacion): string
    {
        if (! $presentacion->id) {
            throw new RuntimeException('La presentación debe existir antes de guardar su etiqueta.');
        }

        $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'bin');
        $ruta = $this->directorio($producto).'/'.$presentacion->id.'-etiqueta.'.$extension;
        $rutaAnterior = $presentacion->url_etiqueta;

        $this->archivos->guardarSubido($archivo, $ruta);

        if ($rutaAnterior && $this->archivos->normalizarNullable($rutaAnterior) !== $ruta) {
            $this->archivos->eliminar($rutaAnterior);
        }

        return $ruta;
    }

    public function organizarExistente(Producto $producto, Presentacion $presentacion): ?string
    {
        if (! $presentacion->id) {
            throw new RuntimeException('La presentación debe existir antes de organizar su etiqueta.');
        }

        $rutaActual = $this->archivos->normalizarNullable($presentacion->url_etiqueta);

        if (! $rutaActual) {
            return null;
        }

        $extension = strtolower(pathinfo($rutaActual, PATHINFO_EXTENSION) ?: 'bin');
        $rutaNueva = $this->directorio($producto).'/'.$presentacion->id.'-etiqueta.'.$extension;

        return $this->archivos->moverAPrivado($rutaActual, $rutaNueva);
    }

    public function directorio(Producto $producto): string
    {
        if (! $producto->id) {
            throw new RuntimeException('El producto debe existir antes de guardar etiquetas.');
        }

        return 'productos/'.$producto->id.'/presentaciones';
    }
}
