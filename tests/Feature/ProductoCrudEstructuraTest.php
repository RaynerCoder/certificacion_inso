<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductoController;
use App\Models\Presentacion;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class ProductoCrudEstructuraTest extends TestCase
{
    public function test_el_crud_de_productos_tiene_todos_sus_metodos_publicos(): void
    {
        foreach (['index', 'create', 'store', 'show', 'etiquetaPresentacion', 'edit', 'update', 'destroy'] as $metodo) {
            $this->assertTrue(
                method_exists(ProductoController::class, $metodo),
                "Falta el método {$metodo} en ProductoController."
            );
        }
    }

    public function test_las_rutas_de_productos_apuntan_a_metodos_existentes(): void
    {
        foreach ([
            'productos_index' => 'index',
            'productos_create' => 'create',
            'productos_store' => 'store',
            'productos_presentaciones_etiqueta' => 'etiquetaPresentacion',
            'productos_show' => 'show',
            'productos_edit' => 'edit',
            'productos_update' => 'update',
            'productos_destroy' => 'destroy',
        ] as $nombreRuta => $metodo) {
            $ruta = app('router')->getRoutes()->getByName($nombreRuta);

            $this->assertNotNull($ruta, "No existe la ruta {$nombreRuta}.");
            $this->assertStringEndsWith("@{$metodo}", $ruta->getActionName());
        }
    }

    public function test_existen_las_vistas_de_listado_formulario_y_detalle(): void
    {
        $this->assertTrue(view()->exists('productos.index'));
        $this->assertTrue(view()->exists('productos.create'));
        $this->assertTrue(view()->exists('productos.show'));
    }

    public function test_el_porcentaje_de_ingrediente_admite_concentraciones_pequenas(): void
    {
        $validador = Validator::make(
            ['porcentaje' => '0.005'],
            ['porcentaje' => ['required', 'numeric', 'decimal:0,5', 'min:0', 'max:100']]
        );

        $this->assertTrue($validador->passes());
    }

    public function test_las_cantidades_de_presentacion_y_registro_aceptan_coma_decimal(): void
    {
        $request = Request::create('/productos', 'POST', [
            'presentaciones' => [[
                'cantidad' => '0,25',
            ]],
            'registros' => [[
                'cantidad' => '12,75',
            ]],
        ]);
        $metodo = new ReflectionMethod(ProductoController::class, 'normalizarCantidadesPresentaciones');

        $metodo->invoke(app(ProductoController::class), $request);

        $cantidad = $request->input('presentaciones.0.cantidad');
        $cantidadRegistro = $request->input('registros.0.cantidad');
        $validador = Validator::make(
            [
                'cantidad' => $cantidad,
                'cantidad_registro' => $cantidadRegistro,
            ],
            [
                'cantidad' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
                'cantidad_registro' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            ]
        );

        $this->assertSame('0.25', $cantidad);
        $this->assertSame('12.75', $cantidadRegistro);
        $this->assertTrue($validador->passes());
    }

    public function test_la_cantidad_vacia_del_registro_se_normaliza_como_nula(): void
    {
        $request = Request::create('/productos', 'POST', [
            'registros' => [[
                'cantidad' => '',
            ]],
        ]);
        $metodo = new ReflectionMethod(ProductoController::class, 'normalizarCantidadesPresentaciones');

        $metodo->invoke(app(ProductoController::class), $request);

        $this->assertNull($request->input('registros.0.cantidad'));
    }

    public function test_un_ingrediente_temporal_con_nombre_es_valido(): void
    {
        // Esta tabla mínima permite probar la misma comparación que se usa al guardar el producto.
        Schema::create('ingredientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->softDeletes();
        });

        $request = Request::create('/productos', 'POST', [
            'ingredientes_productos' => [[
                'id_ingrediente' => 'TEMP-INGREDIENTE-1',
                'nombre' => 'Permetrina',
                'porcentaje' => '12.5',
                'estado' => 'ACTIVO',
            ]],
        ]);
        $validador = Validator::make($request->all(), []);
        $metodo = new ReflectionMethod(ProductoController::class, 'validarIngredientesTemporales');

        $metodo->invoke(app(ProductoController::class), $validador, $request);

        $this->assertFalse($validador->errors()->has('ingredientes_productos.0.id_ingrediente'));

        DB::table('ingredientes')->insert(['nombre' => 'PERMETRINA']);

        $requestRepetido = Request::create('/productos', 'POST', [
            'ingredientes_productos' => [[
                'id_ingrediente' => 'TEMP-INGREDIENTE-2',
                'nombre' => '  permetrina  ',
                'porcentaje' => '10',
                'estado' => 'ACTIVO',
            ]],
        ]);
        $validadorRepetido = Validator::make($requestRepetido->all(), []);

        $metodo->invoke(app(ProductoController::class), $validadorRepetido, $requestRepetido);

        $this->assertTrue($validadorRepetido->errors()->has('ingredientes_productos.0.id_ingrediente'));
    }

    public function test_la_fila_nueva_envia_el_nombre_del_ingrediente(): void
    {
        $vista = file_get_contents(resource_path('views/productos/create.blade.php'));

        $this->assertStringContainsString(
            'name="ingredientes_productos[${productoIngredienteIndice}][nombre]"',
            $vista
        );
    }

    public function test_los_errores_de_guardado_se_devuelven_sin_recargar_el_formulario(): void
    {
        $respuesta = $this
            ->withoutMiddleware()
            ->postJson(route('productos_store'), []);

        $respuesta
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'form_codigo',
                'form_nombre_comercial',
                'form_tipo_producto',
            ]);
    }

    public function test_la_etiqueta_se_entrega_sin_depender_de_public_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('productos/etiquetas/ejemplo.jpg', 'imagen-prueba');

        $presentacion = new Presentacion([
            'url_etiqueta' => 'productos/etiquetas/ejemplo.jpg',
        ]);

        $respuesta = app(ProductoController::class)->etiquetaPresentacion($presentacion);

        $this->assertSame(200, $respuesta->getStatusCode());
        $this->assertStringContainsString('image/jpeg', (string) $respuesta->headers->get('content-type'));
    }

    public function test_el_detalle_del_tramite_lee_las_presentaciones_actuales_del_producto(): void
    {
        $vista = file_get_contents(resource_path('views/certificados/detalle_tramite/productos.blade.php'));

        $this->assertStringContainsString('$producto?->presentaciones ?? collect()', $vista);
        $this->assertStringContainsString('$producto?->registros ?? collect()', $vista);
        $this->assertStringContainsString("route('productos_presentaciones_etiqueta', \$presentacion)", $vista);
        $this->assertStringContainsString('Presentación {{ $loop->iteration }}', $vista);
        $this->assertStringContainsString('Registros asociados', $vista);
    }

    public function test_el_tramite_no_permite_vincular_manualmente_un_registro_existente(): void
    {
        $vista = file_get_contents(resource_path('views/certificados/detalle_tramite/productos.blade.php'));

        $this->assertNull(app('router')->getRoutes()->getByName('certificados_registros_existentes_vincular'));
        $this->assertStringNotContainsString('Vincular registro existente', $vista);
        $this->assertStringNotContainsString('Vincular al trámite', $vista);
    }
}
