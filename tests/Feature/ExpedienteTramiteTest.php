<?php

namespace Tests\Feature;

use App\Models\Certificado;
use App\Models\Pago;
use App\Models\RequisitoCertificado;
use App\Services\ExpedienteTramiteService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpedienteTramiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_guarda_archivos_por_id_de_tramite_sin_una_tabla_adicional(): void
    {
        $certificado = new Certificado();
        $certificado->id = 17;
        $requisito = new RequisitoCertificado();
        $requisito->id = 45;
        $requisito->id_certificado = 17;
        $servicio = app(ExpedienteTramiteService::class);

        $primero = $servicio->guardarArchivoSubido(
            $certificado,
            UploadedFile::fake()->create('solicitud-original.pdf', 10, 'application/pdf'),
            ExpedienteTramiteService::EVIDENCIA_REQUISITO,
            $requisito
        );
        $segundo = $servicio->guardarArchivoSubido(
            $certificado,
            UploadedFile::fake()->create('solicitud-corregida.pdf', 12, 'application/pdf'),
            ExpedienteTramiteService::EVIDENCIA_REQUISITO,
            $requisito
        );

        $this->assertMatchesRegularExpression(
            '#^tramites/17/requisitos/45/[0-9a-f-]{36}__solicitud-original\.pdf$#',
            $primero
        );
        $this->assertMatchesRegularExpression(
            '#^tramites/17/requisitos/45/[0-9a-f-]{36}__solicitud-corregida\.pdf$#',
            $segundo
        );
        Storage::disk('local')->assertExists($primero);
        Storage::disk('local')->assertExists($segundo);
    }

    public function test_organiza_pagos_y_certificados_en_el_mismo_tramite(): void
    {
        $certificado = new Certificado();
        $certificado->id = 23;
        $pago = new Pago();
        $pago->id = 9;
        $servicio = app(ExpedienteTramiteService::class);

        $comprobante = $servicio->guardarArchivoSubido(
            $certificado,
            UploadedFile::fake()->create('comprobante.pdf', 8, 'application/pdf'),
            ExpedienteTramiteService::COMPROBANTE_PAGO,
            pago: $pago
        );
        $emitido = $servicio->guardarContenido(
            $certificado,
            '%PDF-1.4 documento de prueba',
            ExpedienteTramiteService::CERTIFICADO_EMITIDO,
            'pdf',
            'certificado-final.pdf'
        );

        $this->assertMatchesRegularExpression(
            '#^tramites/23/pagos/9/[0-9a-f-]{36}__comprobante\.pdf$#',
            $comprobante
        );
        $this->assertMatchesRegularExpression(
            '#^tramites/23/certificados/emisiones/[0-9a-f-]{36}__certificado-final\.pdf$#',
            $emitido
        );
        Storage::disk('local')->assertExists($comprobante);
        Storage::disk('local')->assertExists($emitido);
    }

    public function test_la_evidencia_se_entrega_mediante_una_ruta_protegida(): void
    {
        $this->assertNotNull(app('router')->getRoutes()->getByName('certificados_evidencias_descargar'));
    }
}
