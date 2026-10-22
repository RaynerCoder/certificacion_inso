<?php

namespace Tests\Unit;

use App\Models\Empresa;
use App\Models\Persona;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Services\EtiquetaPresentacionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EtiquetaPresentacionServiceTest extends TestCase
{
    public function test_guarda_la_etiqueta_por_empresa_producto_y_presentacion(): void
    {
        Storage::fake('local');

        [$producto, $presentacion] = $this->modelosEmpresa();
        $archivo = UploadedFile::fake()->create('etiqueta.jpg', 10, 'image/jpeg');

        $ruta = app(EtiquetaPresentacionService::class)->guardar($archivo, $producto, $presentacion);

        $this->assertSame(
            'productos/34/presentaciones/56-etiqueta.jpg',
            $ruta,
        );
        Storage::disk('local')->assertExists($ruta);
    }

    public function test_mueve_una_etiqueta_anterior_a_la_estructura_organizada(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('productos/etiquetas/anterior.jpg', 'imagen');

        [$producto, $presentacion] = $this->modelosEmpresa();
        $presentacion->url_etiqueta = 'productos/etiquetas/anterior.jpg';

        $ruta = app(EtiquetaPresentacionService::class)
            ->organizarExistente($producto, $presentacion);

        $this->assertSame(
            'productos/34/presentaciones/56-etiqueta.jpg',
            $ruta
        );
        Storage::disk('public')->assertMissing('productos/etiquetas/anterior.jpg');
        Storage::disk('local')->assertExists($ruta);
    }

    private function modelosEmpresa(): array
    {
        $empresa = new Empresa;
        $empresa->id = 12;

        $persona = new Persona;
        $persona->id = 23;
        $persona->setRelation('empresa', $empresa);

        $producto = new Producto(['id_importador_persona' => $persona->id]);
        $producto->id = 34;
        $producto->setRelation('importadorPersona', $persona);

        $presentacion = new Presentacion(['id_producto' => $producto->id]);
        $presentacion->id = 56;

        return [$producto, $presentacion];
    }
}
