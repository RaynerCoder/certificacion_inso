<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductoController;
use Tests\TestCase;

class ProductoCrudEstructuraTest extends TestCase
{
    public function test_el_crud_de_productos_tiene_todos_sus_metodos_publicos(): void
    {
        foreach (['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'] as $metodo) {
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
}
