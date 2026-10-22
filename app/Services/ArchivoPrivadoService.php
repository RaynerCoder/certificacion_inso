<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ArchivoPrivadoService
{
    public const DISCO = 'local';

    public function guardarSubido(UploadedFile $archivo, string $ruta): string
    {
        $ruta = $this->normalizar($ruta);
        $contenido = File::get($archivo->getRealPath());

        return $this->guardarContenido($ruta, $contenido);
    }

    public function guardarContenido(string $ruta, string $contenido): string
    {
        $ruta = $this->normalizar($ruta);

        if (! Storage::disk(self::DISCO)->put($ruta, $contenido)) {
            throw new RuntimeException('No se pudo guardar el archivo en el almacenamiento privado.');
        }

        return $ruta;
    }

    /**
     * Mueve un archivo histórico desde local, public o public/storage hacia la
     * ruta privada indicada. Solo borra el origen después de verificar el destino.
     */
    public function moverAPrivado(?string $rutaActual, string $rutaNueva): ?string
    {
        $rutaActual = $this->normalizarNullable($rutaActual);
        $rutaNueva = $this->normalizar($rutaNueva);

        if ($rutaActual === null) {
            return null;
        }

        if ($rutaActual === $rutaNueva && Storage::disk(self::DISCO)->exists($rutaNueva)) {
            $this->eliminarCopiasPublicas($rutaActual);

            return $rutaNueva;
        }

        $origen = $this->resolverOrigen($rutaActual);

        if ($origen === null) {
            return $rutaActual;
        }

        $contenido = $origen['contenido'];
        $this->guardarContenido($rutaNueva, $contenido);

        if (! Storage::disk(self::DISCO)->exists($rutaNueva)
            || Storage::disk(self::DISCO)->size($rutaNueva) !== strlen($contenido)) {
            throw new RuntimeException('El archivo se copió, pero no pudo verificarse en su nueva ubicación.');
        }

        if ($origen['disco'] === self::DISCO && $rutaActual !== $rutaNueva) {
            Storage::disk(self::DISCO)->delete($rutaActual);
        }

        $this->eliminarCopiasPublicas($rutaActual);

        return $rutaNueva;
    }

    public function eliminar(?string $ruta): void
    {
        $ruta = $this->normalizarNullable($ruta);

        if ($ruta === null) {
            return;
        }

        Storage::disk(self::DISCO)->delete($ruta);
        $this->eliminarCopiasPublicas($ruta);
    }

    public function existe(?string $ruta): bool
    {
        $ruta = $this->normalizarNullable($ruta);

        return $ruta !== null && (
            Storage::disk(self::DISCO)->exists($ruta)
            || Storage::disk('public')->exists($ruta)
            || File::exists(public_path('storage/'.$ruta))
        );
    }

    public function rutaAbsoluta(?string $ruta): ?string
    {
        $ruta = $this->normalizarNullable($ruta);

        if ($ruta === null) {
            return null;
        }

        if (Storage::disk(self::DISCO)->exists($ruta)) {
            return Storage::disk(self::DISCO)->path($ruta);
        }

        if (Storage::disk('public')->exists($ruta)) {
            return Storage::disk('public')->path($ruta);
        }

        $rutaPublica = public_path('storage/'.$ruta);

        return File::exists($rutaPublica) ? $rutaPublica : null;
    }

    public function respuesta(?string $ruta, ?string $nombre = null, array $cabeceras = []): Response
    {
        $ruta = $this->normalizarNullable($ruta);

        abort_unless($ruta !== null, 404);

        if (Storage::disk(self::DISCO)->exists($ruta)) {
            return Storage::disk(self::DISCO)->response($ruta, $nombre ?: basename($ruta), $cabeceras);
        }

        if (Storage::disk('public')->exists($ruta)) {
            return Storage::disk('public')->response($ruta, $nombre ?: basename($ruta), $cabeceras);
        }

        $rutaPublica = public_path('storage/'.$ruta);
        if (File::exists($rutaPublica)) {
            return response()->file($rutaPublica, array_merge([
                'Content-Disposition' => 'inline; filename="'.($nombre ?: basename($ruta)).'"',
            ], $cabeceras));
        }

        abort(404);
    }

    public function normalizarNullable(?string $ruta): ?string
    {
        $ruta = trim(str_replace('\\', '/', (string) $ruta));

        if ($ruta === '' || preg_match('#^https?://#i', $ruta)) {
            return null;
        }

        $ruta = ltrim($ruta, '/');

        // El disco local ya apunta a storage/app/private. En instalaciones
        // históricas algunas rutas se guardaron incluyendo esa raíz completa;
        // se elimina para evitar búsquedas duplicadas como private/private/....
        foreach ([
            'storage/app/private/',
            'storage/app/public/',
            'public/storage/',
            'app/private/',
            'app/public/',
        ] as $raiz) {
            $posicion = strpos($ruta, $raiz);

            if ($posicion !== false) {
                return ltrim(substr($ruta, $posicion + strlen($raiz)), '/');
            }
        }

        if (str_starts_with($ruta, 'private/')) {
            return substr($ruta, strlen('private/'));
        }

        return str_starts_with($ruta, 'storage/')
            ? substr($ruta, strlen('storage/'))
            : $ruta;
    }

    private function normalizar(string $ruta): string
    {
        $ruta = $this->normalizarNullable($ruta);

        if ($ruta === null || str_contains($ruta, '../')) {
            throw new RuntimeException('La ruta del archivo no es válida.');
        }

        return $ruta;
    }

    private function resolverOrigen(string $ruta): ?array
    {
        if (Storage::disk(self::DISCO)->exists($ruta)) {
            return [
                'disco' => self::DISCO,
                'contenido' => Storage::disk(self::DISCO)->get($ruta),
            ];
        }

        if (Storage::disk('public')->exists($ruta)) {
            return [
                'disco' => 'public',
                'contenido' => Storage::disk('public')->get($ruta),
            ];
        }

        $rutaPublica = public_path('storage/'.$ruta);

        return File::exists($rutaPublica)
            ? ['disco' => 'public_path', 'contenido' => File::get($rutaPublica)]
            : null;
    }

    private function eliminarCopiasPublicas(string $ruta): void
    {
        if (Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }

        $rutaPublica = public_path('storage/'.$ruta);
        if (File::exists($rutaPublica)) {
            File::delete($rutaPublica);
        }
    }
}
