<?php

namespace App\Services;

use App\Models\Certificado;
use App\Models\Pago;
use App\Models\RequisitoCertificado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ExpedienteTramiteService
{
    public const EVIDENCIA_REQUISITO = 'EVIDENCIA_REQUISITO';
    public const COMPROBANTE_PAGO = 'COMPROBANTE_PAGO';
    public const CERTIFICADO_EMITIDO = 'CERTIFICADO_EMITIDO';
    public const CERTIFICADO_ADJUNTO = 'CERTIFICADO_ADJUNTO';

    /**
     * Guarda el archivo directamente en la carpeta privada del trámite.
     * Cuando existe un registro relacionado, su ID se utiliza como nombre estable del archivo.
     */
    public function guardarArchivoSubido(
        Certificado $certificado,
        UploadedFile $archivo,
        string $categoria,
        ?RequisitoCertificado $requisitoCertificado = null,
        ?Pago $pago = null,
        ?string $nombreArchivo = null,
    ): string {
        $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'bin');
        $directorio = $this->directorio($certificado, $categoria, $requisitoCertificado, $pago);
        $nombreGuardado = filled($nombreArchivo)
            ? Str::slug($nombreArchivo).'.'.$extension
            : $this->nombreSeguro($archivo->getClientOriginalName(), $extension);
        $ruta = $archivo->storeAs($directorio, $nombreGuardado, 'local');

        if (! is_string($ruta) || $ruta === '') {
            throw new RuntimeException('No se pudo guardar el archivo en el expediente del trámite.');
        }

        return $ruta;
    }

    public function guardarContenido(
        Certificado $certificado,
        string $contenido,
        string $categoria,
        string $extension,
        string $nombreOriginal
    ): string {
        $extension = strtolower(ltrim($extension, '.'));
        $ruta = $this->directorio($certificado, $categoria)
            .'/'.$this->nombreSeguro($nombreOriginal, $extension);

        if (! Storage::disk('local')->put($ruta, $contenido)) {
            throw new RuntimeException('No se pudo generar el documento dentro del expediente del trámite.');
        }

        return $ruta;
    }

    public function nombreDocumentoEvidencia(int $numeroRespaldo): string
    {
        return 'respaldo-'.max(1, $numeroRespaldo);
    }

    /**
     * Resuelve tanto las rutas privadas nuevas como las rutas públicas anteriores.
     */
    public function rutaAbsoluta(?string $ruta): ?string
    {
        $rutaNormalizada = $this->normalizarRuta($ruta);

        if ($rutaNormalizada === null) {
            return null;
        }

        if (Storage::disk('local')->exists($rutaNormalizada)) {
            return Storage::disk('local')->path($rutaNormalizada);
        }

        if (Storage::disk('public')->exists($rutaNormalizada)) {
            return Storage::disk('public')->path($rutaNormalizada);
        }

        $rutaPublicaAnterior = public_path('storage/'.$rutaNormalizada);

        return File::exists($rutaPublicaAnterior) ? $rutaPublicaAnterior : null;
    }

    private function directorio(
        Certificado $certificado,
        string $categoria,
        ?RequisitoCertificado $requisitoCertificado = null,
        ?Pago $pago = null
    ): string {
        $raiz = 'tramites/'.$certificado->id;

        return match ($categoria) {
            self::EVIDENCIA_REQUISITO => $raiz.'/requisitos/'.$this->idRequerido($requisitoCertificado?->id, 'requisito'),
            self::COMPROBANTE_PAGO => $raiz.'/pagos/'.$this->idRequerido($pago?->id, 'pago'),
            self::CERTIFICADO_EMITIDO => $raiz.'/certificados/emitidos',
            self::CERTIFICADO_ADJUNTO => $raiz.'/certificados/adjuntos',
            default => throw new RuntimeException('La categoría del archivo del trámite no es válida.'),
        };
    }

    private function nombreSeguro(string $nombreOriginal, string $extension): string
    {
        $base = Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME));
        $base = $base !== '' ? mb_substr($base, 0, 100) : 'archivo';

        return Str::uuid()->toString().'__'.$base.'.'.$extension;
    }

    private function idRequerido(?int $id, string $tipo): int
    {
        if (! $id) {
            throw new RuntimeException('No se encontró el '.$tipo.' relacionado con el archivo.');
        }

        return $id;
    }

    private function normalizarRuta(?string $ruta): ?string
    {
        $ruta = trim((string) $ruta);

        if ($ruta === '' || preg_match('#^https?://#i', $ruta)) {
            return null;
        }

        $ruta = str_replace('\\', '/', ltrim($ruta, '/'));

        return str_starts_with($ruta, 'storage/')
            ? substr($ruta, strlen('storage/'))
            : $ruta;
    }
}
