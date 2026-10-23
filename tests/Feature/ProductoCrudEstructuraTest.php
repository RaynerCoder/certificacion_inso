<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductoController;
use App\Models\ClasificacionToxicologica;
use App\Models\FraseReglamentaria;
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
        foreach ([
            'index',
            'create',
            'store',
            'show',
            'etiquetaPresentacion',
            'consultarFormulaQuimicaIngrediente',
            'confirmarFormulaQuimicaIngrediente',
            'edit',
            'update',
            'destroy',
        ] as $metodo) {
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
            'productos_ingredientes_consultar_formula_quimica' => 'consultarFormulaQuimicaIngrediente',
            'productos_ingredientes_confirmar_formula_quimica' => 'confirmarFormulaQuimicaIngrediente',
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

    public function test_el_formulario_consulta_y_confirma_la_formula_del_ingrediente(): void
    {
        $vista = file_get_contents(resource_path('views/productos/create.blade.php'));
        $seccion = file_get_contents(resource_path('views/productos/create/ingredientes.blade.php'));
        $controlador = file_get_contents(app_path('Http/Controllers/ProductoController.php'));
        $migracion = file_get_contents(
            database_path('migrations/2026_05_02_235415_create_ingredientes_table.php')
        );
        $inicioConfirmacion = strpos($controlador, 'public function confirmarFormulaQuimicaIngrediente');
        $finConfirmacion = strpos($controlador, 'public function edit(', $inicioConfirmacion);
        $metodoConfirmacion = substr($controlador, $inicioConfirmacion, $finConfirmacion - $inicioConfirmacion);

        $this->assertStringContainsString('Buscar fórmula química', $seccion);
        $this->assertStringContainsString('data-formula-quimica', $seccion);
        $this->assertStringContainsString('Fórmula química manual', $seccion);
        $this->assertStringContainsString('form_formula_quimica_manual', $seccion);
        $this->assertStringContainsString('agregarIngredienteProductoBoton', $seccion);
        $this->assertStringContainsString('formulaQuimicaValidaProducto', $vista);
        $this->assertStringContainsString('consultarFormulaQuimicaIngredienteProducto', $vista);
        $this->assertStringContainsString('confirmarFormulaQuimicaIngredienteProducto', $vista);
        $this->assertStringContainsString('usarFormulaQuimicaManualProducto', $vista);
        $this->assertStringContainsString('Se guardará únicamente al guardar el producto', $vista);
        $this->assertStringContainsString(
            'guardarConsultaFormulaTemporal($ingrediente, $formula, false, true',
            $metodoConfirmacion
        );
        $this->assertStringContainsString("'permite_formula_manual'", $metodoConfirmacion);
        $this->assertStringNotContainsString("->update(['formula_quimica'", $metodoConfirmacion);
        $this->assertStringContainsString('guardarFormulaQuimicaConfirmada', $controlador);
        $this->assertStringContainsString("string('formula_quimica', 255)", $migracion);
        $this->assertStringNotContainsString("formula_quimica', 255)->nullable()", $migracion);
    }

    public function test_la_formula_manual_se_normaliza_y_valida_antes_del_guardado_final(): void
    {
        $normalizar = new ReflectionMethod(ProductoController::class, 'normalizarFormulaQuimicaManual');
        $validar = new ReflectionMethod(ProductoController::class, 'esFormulaQuimicaManualValida');
        $controlador = app(ProductoController::class);

        $formula = $normalizar->invoke($controlador, ' C₁₈H₁₂Cl₂N₂O ');

        $this->assertSame('C18H12Cl2N2O', $formula);
        $this->assertTrue($validar->invoke($controlador, $formula));
        $this->assertFalse($validar->invoke($controlador, 'fórmula no verificada'));
    }

    public function test_la_observacion_toxicologica_de_muestra_se_guarda_como_texto_del_producto(): void
    {
        $modelo = new \App\Models\Producto();
        $formulario = file_get_contents(resource_path('views/productos/create/producto.blade.php'));
        $controlador = file_get_contents(app_path('Http/Controllers/ProductoController.php'));
        $migracion = file_get_contents(
            database_path('migrations/2026_05_02_222717_create_productos_table.php')
        );

        $this->assertContains('observacion_toxicologica_muestra', $modelo->getFillable());
        $this->assertStringContainsString('form_observacion_toxicologica_muestra', $formulario);
        $this->assertStringContainsString('DL-50 oral aguda > 2000 mg/kg', $formulario);
        $this->assertStringContainsString("'form_observacion_toxicologica_muestra' => ['nullable', 'string', 'max:2000']", $controlador);
        $this->assertStringContainsString("text('observacion_toxicologica_muestra')", $migracion);
    }

    public function test_el_nombre_tecnico_se_construye_con_los_ingredientes_agregados(): void
    {
        $request = Request::create('/productos', 'POST', [
            'ingredientes_productos' => [
                ['id_ingrediente' => 'TEMP-1', 'nombre' => 'Piraclostrobin'],
                ['id_ingrediente' => 'TEMP-2', 'nombre' => 'Boscalid'],
                ['id_ingrediente' => 'TEMP-3', 'nombre' => 'piraclostrobin'],
            ],
        ]);
        $metodo = new ReflectionMethod(ProductoController::class, 'nombreTecnicoDesdeIngredientes');

        $nombreTecnico = $metodo->invoke(app(ProductoController::class), $request);

        $this->assertSame('Piraclostrobin + Boscalid', $nombreTecnico);
    }

    public function test_el_campo_nombre_tecnico_es_de_solo_lectura_y_se_explica_al_usuario(): void
    {
        $vista = file_get_contents(resource_path('views/productos/create/producto.blade.php'));

        $this->assertStringContainsString('id="form_nombre_tecnico"', $vista);
        $this->assertStringContainsString('readonly', $vista);
        $this->assertStringContainsString('Se genera automáticamente con los ingredientes agregados', $vista);
    }

    public function test_las_nuevas_clasificaciones_toxicologicas_se_normalizan_a_mayusculas(): void
    {
        $clasificacion = new ClasificacionToxicologica([
            'codigo' => 'Categoría iii',
            'descripcion' => 'Poco peligroso',
        ]);

        $this->assertSame('CATEGORÍA III', $clasificacion->codigo);
        $this->assertSame('POCO PELIGROSO', $clasificacion->descripcion);
    }

    public function test_los_codigos_de_frases_reglamentarias_se_normalizan_y_son_unicos(): void
    {
        $frase = new FraseReglamentaria([
            'tipo' => 'riesgo',
            'codigo' => 'r20/21',
            'descripcion' => 'Nocivo por inhalación y por contacto con la piel.',
        ]);
        $migracion = file_get_contents(
            database_path('migrations/2026_10_22_000001_create_frases_reglamentarias_table.php')
        );

        $this->assertSame('RIESGO', $frase->tipo);
        $this->assertSame('R20/21', $frase->codigo);
        $this->assertStringContainsString("unique('codigo', 'frases_reglamentarias_codigo_unique')", $migracion);
    }

    public function test_el_formulario_de_producto_incluye_la_seccion_de_riesgos_y_seguridad(): void
    {
        $vista = file_get_contents(resource_path('views/productos/create.blade.php'));
        $seccion = file_get_contents(resource_path('views/productos/create/frases_reglamentarias.blade.php'));

        $this->assertStringContainsString("@include('productos.create.frases_reglamentarias')", $vista);
        $this->assertStringContainsString('name="frases_reglamentarias[]"', $seccion);
        $this->assertStringContainsString('Frases de riesgo', $seccion);
        $this->assertStringContainsString('Frases de seguridad', $seccion);
    }

    public function test_un_producto_tiene_una_relacion_muchos_a_muchos_con_frases_reglamentarias(): void
    {
        $this->assertTrue(method_exists(\App\Models\Producto::class, 'frasesReglamentarias'));
        $this->assertTrue(method_exists(\App\Models\Producto::class, 'frasesReglamentariasProductos'));
        $this->assertTrue(method_exists(FraseReglamentaria::class, 'productos'));
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

    public function test_el_tramite_permite_seleccionar_un_producto_registrado(): void
    {
        $vista = file_get_contents(resource_path('views/certificados/detalle_tramite/productos.blade.php'));

        $ruta = app('router')->getRoutes()->getByName('certificados_productos_vincular');

        $this->assertNotNull($ruta);
        $this->assertStringEndsWith('@vincularProductoExistente', $ruta->getActionName());
        $this->assertStringContainsString('Seleccionar producto registrado', $vista);
        $this->assertStringContainsString('Registrar nuevo producto', $vista);
    }
}
