<?php

namespace App\Services;

use App\Models\Certificado;
use App\Models\DescargaCertificado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DescargaCertificadoService
{
    /**
     * Devuelve el avance de descargas del usuario para mostrarlo en las pantallas.
     */
    public function resumen(Certificado $certificado, User $usuario): array
    {
        $certificado->loadMissing('tipoCertificado');

        $limite = $this->limiteConfigurado($certificado);
        $realizadas = DescargaCertificado::query()
            ->where('id_certificado', $certificado->id)
            ->where('id_usuario', $usuario->id)
            ->count();

        return [
            'limite' => $limite,
            'realizadas' => $realizadas,
            'restantes' => $limite === null ? null : max(0, $limite - $realizadas),
            'disponible' => $limite === null || $realizadas < $limite,
        ];
    }

    /**
     * Registra una descarga sin permitir que dos solicitudes simultáneas superen el límite.
     */
    public function registrar(Certificado $certificado, User $usuario): void
    {
        DB::transaction(function () use ($certificado, $usuario) {
            $certificadoBloqueado = Certificado::query()
                ->with('tipoCertificado')
                ->lockForUpdate()
                ->findOrFail($certificado->id);

            $limite = $this->limiteConfigurado($certificadoBloqueado);
            $realizadas = DescargaCertificado::query()
                ->where('id_certificado', $certificadoBloqueado->id)
                ->where('id_usuario', $usuario->id)
                ->count();

            if ($limite !== null && $realizadas >= $limite) {
                throw new RuntimeException('Alcanzó el límite de descargas permitido para este certificado.');
            }

            DescargaCertificado::create([
                'id_certificado' => $certificadoBloqueado->id,
                'id_usuario' => $usuario->id,
                'fecha_descarga' => now(),
            ]);
        });
    }

    /**
     * Busca primero el almacenamiento privado y mantiene compatibilidad con documentos anteriores.
     */
    public function rutaDocumento(Certificado $certificado): ?string
    {
        $ruta = $this->normalizarRuta($certificado->url_documento);

        if ($ruta === null) {
            return null;
        }

        if (Storage::disk('local')->exists($ruta)) {
            return Storage::disk('local')->path($ruta);
        }

        if (Storage::disk('public')->exists($ruta)) {
            return Storage::disk('public')->path($ruta);
        }

        $rutaPublicaAnterior = public_path('storage/'.$ruta);

        return File::exists($rutaPublicaAnterior) ? $rutaPublicaAnterior : null;
    }

    public function nombreDocumento(Certificado $certificado): string
    {
        $codigo = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $certificado->codigo);

        return 'certificado_'.trim($codigo, '_').'.pdf';
    }

    private function limiteConfigurado(Certificado $certificado): ?int
    {
        $limite = $certificado->tipoCertificado?->limite_descargas;

        return $limite === null ? null : max(0, (int) $limite);
    }

    private function normalizarRuta(?string $ruta): ?string
    {
        $ruta = trim((string) $ruta);

        // Una URL externa no puede someterse al control local de descargas.
        if ($ruta === '' || preg_match('#^https?://#i', $ruta)) {
            return null;
        }

        $ruta = str_replace('\\', '/', ltrim($ruta, '/'));

        return str_starts_with($ruta, 'storage/')
            ? substr($ruta, strlen('storage/'))
            : $ruta;
    }
}
