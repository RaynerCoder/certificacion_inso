<?php

namespace App\Services;

use App\Models\PlantillaCertificado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PlantillaCertificadoStorageService
{
    public function guardar(UploadedFile $archivo, PlantillaCertificado $plantilla): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'bin');
        $nombre = 'plantilla_'.now()->format('Ymd_His').'_'.Str::random(12).'.'.$extension;
        $ruta = $archivo->storeAs($this->directorio($plantilla), $nombre, 'public');

        if (! is_string($ruta) || $ruta === '') {
            throw new RuntimeException('No se pudo guardar el archivo de la plantilla.');
        }

        $this->publicarCopia($ruta);

        return $ruta;
    }

    public function organizarExistente(PlantillaCertificado $plantilla): ?string
    {
        $rutaActual = $this->normalizarRuta($plantilla->url_fondo);

        if (! $rutaActual) {
            return null;
        }

        $directorio = $this->directorio($plantilla);

        if (str_replace('\\', '/', dirname($rutaActual)) === $directorio) {
            $this->publicarCopia($rutaActual);

            return $rutaActual;
        }

        if (! Storage::disk('public')->exists($rutaActual)) {
            return $rutaActual;
        }

        $rutaNueva = $directorio.'/'.basename($rutaActual);

        if (Storage::disk('public')->exists($rutaNueva)) {
            $extension = pathinfo($rutaActual, PATHINFO_EXTENSION);
            $rutaNueva = $directorio.'/'.Str::uuid()->toString()
                .($extension !== '' ? '.'.strtolower($extension) : '');
        }

        if (! Storage::disk('public')->move($rutaActual, $rutaNueva)) {
            throw new RuntimeException('No se pudo organizar el fondo de la plantilla #'.$plantilla->id.'.');
        }

        $rutaPublicaAnterior = public_path('storage/'.$rutaActual);
        if (File::exists($rutaPublicaAnterior)) {
            File::delete($rutaPublicaAnterior);
        }

        $this->publicarCopia($rutaNueva);

        return $rutaNueva;
    }

    public function directorio(PlantillaCertificado $plantilla): string
    {
        return 'plantillas/certificados/'.$plantilla->id;
    }

    /**
     * Conserva fuera de documentos los fondos antiguos que ya no tienen registro asociado.
     */
    public function archivarArchivoAnterior(string $rutaActual): string
    {
        $rutaActual = $this->normalizarRuta($rutaActual) ?: $rutaActual;
        $directorio = 'plantillas/certificados/archivo-historico';
        $rutaNueva = $directorio.'/'.basename($rutaActual);

        if (Storage::disk('public')->exists($rutaNueva)) {
            $extension = pathinfo($rutaActual, PATHINFO_EXTENSION);
            $rutaNueva = $directorio.'/'.Str::uuid()->toString()
                .($extension !== '' ? '.'.strtolower($extension) : '');
        }

        if (! Storage::disk('public')->move($rutaActual, $rutaNueva)) {
            throw new RuntimeException('No se pudo archivar la plantilla anterior '.$rutaActual.'.');
        }

        $rutaPublicaAnterior = public_path('storage/'.$rutaActual);
        if (File::exists($rutaPublicaAnterior)) {
            File::delete($rutaPublicaAnterior);
        }

        return $rutaNueva;
    }

    private function publicarCopia(string $ruta): void
    {
        $rutaStorage = Storage::disk('public')->path($ruta);
        $rutaPublica = public_path('storage/'.$ruta);

        File::ensureDirectoryExists(dirname($rutaPublica));

        if (! File::exists($rutaStorage) || ! File::copy($rutaStorage, $rutaPublica)) {
            throw new RuntimeException('La plantilla se guardó, pero no se pudo publicar para su vista previa.');
        }
    }

    private function normalizarRuta(?string $ruta): ?string
    {
        $ruta = ltrim(str_replace('\\', '/', trim((string) $ruta)), '/');

        if ($ruta === '') {
            return null;
        }

        return str_starts_with($ruta, 'storage/')
            ? substr($ruta, strlen('storage/'))
            : $ruta;
    }
}
