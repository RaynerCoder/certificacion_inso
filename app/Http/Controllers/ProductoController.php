<?php

namespace App\Http\Controllers;

use App\Models\CatalogoMedida;
use App\Models\ClasificacionProducto;
use App\Models\IngredienteProducto;
use App\Models\Certificado;
use App\Models\Fabricante;
use App\Models\Ingrediente;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\Registro;
use App\Models\TipoProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('productos.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->filled('form_id_certificado')) {
            $certificado = Certificado::findOrFail($request->integer('form_id_certificado'));

            if (!$certificado->requiereEvidencia('PRODUCTO')) {
                return redirect()
                    ->route('certificados_show', [
                        'certificado' => $certificado,
                        'bandeja' => $request->input('bandeja', 'recibidas'),
                    ])
                    ->with('error', 'Este tramite no tiene producto configurado como requisito.');
            }
        }

        return view('productos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $this->validarProducto($request);

        $productoCreado = DB::transaction(function () use ($request, $datos) {
            /*
             * Los select pueden traer un id real o un valor TEMP-* creado desde modal.
             * Aqui se resuelven antes de guardar productos para mantener las relaciones limpias.
             */
            $idFabricante = $this->resolverFabricante($request, $datos['form_id_fabricante']);
            $idTipoProducto = $this->resolverTipoProducto($request, $datos['form_id_tipo_producto']);
            $idClasificacion = $this->resolverClasificacionProducto($request, $datos['form_id_clasificacion_producto'] ?? null);

            // Primero se guarda productos porque las demas tablas dependen de su id.
            $producto = Producto::create([
                'id_importador_persona' => $datos['form_id_importador_persona'],
                'id_territorio_pais' => $datos['form_id_territorio_pais'],
                'id_fabricante' => $idFabricante,
                'id_tipo_producto' => $idTipoProducto,
                'codigo' => $datos['form_codigo'],
                'nombre_comercial' => $datos['form_nombre_comercial'],
                'nombre_cientifico' => $datos['form_nombre_cientifico'] ?? null,
                'id_clasificacion_producto' => $idClasificacion,
                'estado' => $datos['form_estado'],
            ]);

            // Tabla pivote ingredientes_productos: guarda cada ingrediente agregado al producto.
            foreach ($request->input('ingredientes_productos', []) as $ingredienteProducto) {
                $idIngrediente = $this->resolverIngrediente($ingredienteProducto);

                IngredienteProducto::create([
                    'id_producto' => $producto->id,
                    'id_ingrediente' => $idIngrediente,
                    'porcentaje' => $ingredienteProducto['porcentaje'],
                    'estado' => $ingredienteProducto['estado'] ?? 'ACTIVO',
                ]);
            }

            $presentacionesCreadas = [];
            $presentacionesUnicas = [];
            $registrosCreados = [];

            // Presentaciones: se guarda el PDF en storage/app/public/productos/etiquetas cuando existe.
            foreach ($request->input('presentaciones', []) as $indice => $presentacionDato) {
                $rutaEtiqueta = null;
                $archivoEtiqueta = $request->file("presentaciones.$indice.url_etiqueta");
                $idPresentacionOrigen = $presentacionDato['id_presentacion_origen'] ?? null;

                /*
                 * Si el usuario selecciono una presentacion ya registrada, no se crea otra fila igual:
                 * los registros nuevos apuntan directamente a esa presentacion existente.
                 */
                if ($idPresentacionOrigen) {
                    $presentacionesCreadas[$indice] = (int) $idPresentacionOrigen;
                    continue;
                }

                $clavePresentacion = $this->clavePresentacionProducto($presentacionDato);

                if (isset($presentacionesUnicas[$clavePresentacion])) {
                    $presentacionesCreadas[$indice] = $presentacionesUnicas[$clavePresentacion];
                    continue;
                }

                if ($archivoEtiqueta) {
                    $rutaEtiqueta = $archivoEtiqueta->store('productos/etiquetas', 'public');
                }

                $presentacion = Presentacion::create([
                    'id_producto' => $producto->id,
                    'url_etiqueta' => $rutaEtiqueta,
                    'cantidad' => $presentacionDato['cantidad'],
                    'id_catalogo_unidad' => $this->resolverCatalogoUnidad($request, $presentacionDato['id_catalogo_unidad']),
                    'descripcion' => $presentacionDato['descripcion'] ?? null,
                    'estado' => $presentacionDato['estado'] ?? 'ACTIVO',
                ]);

                $presentacionesCreadas[$indice] = $presentacion->id;
                $presentacionesUnicas[$clavePresentacion] = $presentacion->id;
            }

            // Registros: cada registro queda relacionado al producto y a la presentacion elegida.
            foreach ($request->input('registros', []) as $registroDato) {
                $indicePresentacion = $registroDato['id_presentacion_temporal'] ?? null;

                $registro = Registro::create([
                    'id_producto' => $producto->id,
                    'codigo_autorizacion' => $registroDato['codigo_autorizacion'] ?? null,
                    'fecha_vigencia' => $registroDato['fecha_vigencia'] ?? null,
                    'cantidad' => $registroDato['cantidad'] ?? null,
                    'id_catalogo_unidad' => !empty($registroDato['id_catalogo_unidad'])
                        ? $this->resolverCatalogoUnidad($request, $registroDato['id_catalogo_unidad'])
                        : null,
                    'id_presentacion' => $indicePresentacion !== null
                        ? ($presentacionesCreadas[$indicePresentacion] ?? null)
                        : null,
                    'estado' => $registroDato['estado'] ?? 'ACTIVO',
                ]);

                $registrosCreados[] = $registro->id;
            }

            /*
             * Si Producto fue abierto desde un tramite, se relacionan los registros creados
             * con certificados_registros. El tramite no apunta directo a productos.
             */
            if (!empty($datos['form_id_certificado']) && $registrosCreados) {
                Certificado::find($datos['form_id_certificado'])
                    ?->registros()
                    ->syncWithoutDetaching($registrosCreados);
            }

            return $producto;
        });

        /*
         * Cuando Producto se registra desde el flujo de tramite, no se redirige al listado
         * porque esa pantalla se esta mostrando dentro del formulario de solicitud.
         */
        if ($request->boolean('embebido')) {
            return redirect()
                ->route('productos_create', ['embebido' => 1])
                ->with('producto_creado_tramite', [
                    'id' => $productoCreado->id,
                    'codigo' => $productoCreado->codigo,
                    'nombre_comercial' => $productoCreado->nombre_comercial,
                    'estado' => $productoCreado->estado,
                ])
                ->with('success', 'Producto registrado correctamente. Puede volver al tramite para registrar la solicitud.');
        }

        $urlRetorno = $this->urlRetornoSegura($datos['form_retorno'] ?? null);

        return $urlRetorno
            ? redirect()->to($urlRetorno)->with('success', 'Producto registrado correctamente y relacionado al tramite.')
            : redirect()->route('productos_index')->with('success', 'Producto registrado correctamente.');
    }

    // Valida el formulario completo antes de guardar en base de datos.
    private function validarProducto(Request $request): array
    {
        $validador = Validator::make(
            $request->all(),
            [
                'form_codigo' => ['required', 'string', 'max:150'],
                'form_nombre_comercial' => ['required', 'string', 'max:255'],
                'form_nombre_cientifico' => ['nullable', 'string', 'max:255'],
                'form_id_clasificacion_producto' => ['nullable', 'string', 'max:50'],
                'form_id_importador_persona' => ['required', 'integer', 'exists:personas,id'],
                'form_id_territorio_pais' => ['required', 'integer', 'exists:territorios,id'],
                'form_id_fabricante' => ['required', 'string', 'max:50'],
                'form_id_tipo_producto' => ['required', 'string', 'max:50'],
                'form_estado' => ['required', 'in:ACTIVO,INACTIVO'],
                'form_fabricante_temporal_nombre' => ['nullable', 'string', 'max:255'],
                'form_fabricante_temporal_razon_social' => ['nullable', 'string', 'max:255'],
                'form_fabricante_temporal_descripcion' => ['nullable', 'string'],
                'form_tipo_producto_temporal_descripcion' => ['nullable', 'string', 'max:255'],
                'form_tipo_producto_temporal_codigo' => ['nullable', 'string', 'max:150'],
                'form_clasificacion_temporal_nombre' => ['nullable', 'string', 'max:255'],
                'form_clasificacion_temporal_descripcion' => ['nullable', 'string'],
                'form_unidad_temporal_id' => ['nullable', 'string', 'max:50'],
                'form_unidad_temporal_nombre' => ['nullable', 'string', 'max:255'],
                'form_unidad_temporal_abreviatura' => ['nullable', 'string', 'max:50'],

                'ingredientes_productos' => ['nullable', 'array'],
                'ingredientes_productos.*.id_ingrediente' => ['required_with:ingredientes_productos', 'string', 'max:50'],
                'ingredientes_productos.*.nombre' => ['nullable', 'string', 'max:255'],
                'ingredientes_productos.*.composicion' => ['nullable', 'string', 'max:255'],
                'ingredientes_productos.*.riesgo_salud' => ['nullable', 'string', 'max:255'],
                'ingredientes_productos.*.porcentaje' => ['required_with:ingredientes_productos', 'integer', 'min:0', 'max:100'],
                'ingredientes_productos.*.estado' => ['nullable', 'in:ACTIVO,INACTIVO'],

                'presentaciones' => ['nullable', 'array'],
                'presentaciones.*.cantidad' => ['required_with:presentaciones', 'integer', 'min:1'],
                'presentaciones.*.id_catalogo_unidad' => ['required_with:presentaciones', 'string', 'max:50'],
                'presentaciones.*.descripcion' => ['nullable', 'string'],
                'presentaciones.*.estado' => ['nullable', 'in:ACTIVO,INACTIVO'],
                'presentaciones.*.id_presentacion_origen' => ['nullable', 'integer', 'exists:presentaciones,id'],
                'presentaciones.*.url_etiqueta' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],

                'registros' => ['nullable', 'array'],
                'registros.*.codigo_autorizacion' => ['nullable', 'string', 'max:255'],
                'registros.*.fecha_vigencia' => ['nullable', 'date'],
                'registros.*.cantidad' => ['nullable', 'integer', 'min:1'],
                'registros.*.id_catalogo_unidad' => ['nullable', 'string', 'max:50'],
                'registros.*.id_presentacion_temporal' => ['required_with:registros', 'integer'],
                'registros.*.presentacion_texto' => ['nullable', 'string', 'max:255'],
                'registros.*.estado' => ['nullable', 'in:ACTIVO,INACTIVO'],
                'form_id_certificado' => ['nullable', 'integer', 'exists:certificados,id'],
                'form_bandeja' => ['nullable', 'string', 'max:30'],
                'form_retorno' => ['nullable', 'string', 'max:2048'],
            ],
            [
                'required' => 'Este campo es obligatorio.',
                'required_with' => 'Este dato es obligatorio cuando agrega esta fila.',
                'integer' => 'Debe ingresar un numero entero valido.',
                'exists' => 'El registro seleccionado no existe o ya no esta disponible.',
                'in' => 'Seleccione un estado valido.',
                'mimes' => 'El archivo debe ser un PDF.',
                'max' => 'El dato ingresado supera el limite permitido.',
                'min' => 'El valor ingresado es menor al permitido.',
            ],
            [
                'form_codigo' => 'codigo',
                'form_nombre_comercial' => 'nombre comercial',
                'form_id_importador_persona' => 'importador',
                'form_id_territorio_pais' => 'pais',
                'form_id_fabricante' => 'fabricante',
                'form_id_tipo_producto' => 'tipo de producto',
                'ingredientes_productos.*.id_ingrediente' => 'ingrediente',
                'ingredientes_productos.*.porcentaje' => 'porcentaje',
                'presentaciones.*.cantidad' => 'cantidad',
                'presentaciones.*.id_catalogo_unidad' => 'unidad',
                'presentaciones.*.id_presentacion_origen' => 'presentacion registrada',
                'presentaciones.*.url_etiqueta' => 'etiqueta PDF',
                'registros.*.codigo_autorizacion' => 'codigo de autorizacion',
                'registros.*.id_presentacion_temporal' => 'presentacion del registro',
                'form_id_certificado' => 'tramite',
            ],
        );

        /*
         * Validacion adicional para campos que pueden ser id numerico o TEMP-*.
         * Asi Laravel devuelve errores entendibles antes de entrar a la transaccion.
         */
        $validador->after(function ($validador) use ($request) {
            if ($request->filled('form_id_fabricante') && !$this->esIdExistente($request->input('form_id_fabricante'), Fabricante::class)) {
                if (!$this->esTemporal($request->input('form_id_fabricante')) || !$request->filled('form_fabricante_temporal_nombre')) {
                    $validador->errors()->add('form_id_fabricante', 'Seleccione un fabricante valido o registre uno nuevo.');
                }
            }

            foreach ($request->input('ingredientes_productos', []) as $ingredienteProducto) {
                $idIngrediente = $ingredienteProducto['id_ingrediente'] ?? null;

                if ($this->esIdExistente($idIngrediente, Ingrediente::class)) {
                    continue;
                }

                if (!$this->esTemporal($idIngrediente) || empty($ingredienteProducto['nombre'])) {
                    $validador->errors()->add('ingredientes_productos', 'Uno de los ingredientes no es valido.');
                }
            }

            if ($request->filled('form_id_tipo_producto') && !$this->esIdExistente($request->input('form_id_tipo_producto'), TipoProducto::class)) {
                if (!$this->esTemporal($request->input('form_id_tipo_producto')) || !$request->filled('form_tipo_producto_temporal_descripcion')) {
                    $validador->errors()->add('form_id_tipo_producto', 'Seleccione un tipo de producto valido o registre uno nuevo.');
                }
            }

            if ($request->filled('form_id_clasificacion_producto') && !$this->esIdExistente($request->input('form_id_clasificacion_producto'), ClasificacionProducto::class)) {
                if (!$this->esTemporal($request->input('form_id_clasificacion_producto')) || !$request->filled('form_clasificacion_temporal_nombre')) {
                    $validador->errors()->add('form_id_clasificacion_producto', 'Seleccione una clasificación válida o registre una nueva.');
                }
            }
            $presentaciones = $request->input('presentaciones', []);

            foreach ($presentaciones as $indice => $presentacionDato) {
                $archivoEtiqueta = $request->file("presentaciones.$indice.url_etiqueta");
                $idPresentacionOrigen = $presentacionDato['id_presentacion_origen'] ?? null;

                $idUnidadPresentacion = $presentacionDato['id_catalogo_unidad'] ?? null;
                if (!$this->catalogoUnidadValido($request, $idUnidadPresentacion)) {
                    $validador->errors()->add(
                        "presentaciones.$indice.id_catalogo_unidad",
                        'Seleccione una unidad válida o registre una nueva.'
                    );
                }

                if ($this->abreviaturaUnidadYaRegistrada($request, $idUnidadPresentacion)) {
                    $validador->errors()->add(
                        "presentaciones.$indice.id_catalogo_unidad",
                        'La abreviatura de la unidad ya está registrada.'
                    );
                }
                if (!$archivoEtiqueta && !$idPresentacionOrigen) {
                    $validador->errors()->add(
                        "presentaciones.$indice.url_etiqueta",
                        'Seleccione una etiqueta PDF o una presentacion registrada con etiqueta.'
                    );
                    continue;
                }

                if ($idPresentacionOrigen && !$this->presentacionPerteneceAlProductoSolicitado((int) $idPresentacionOrigen, $request)) {
                    $validador->errors()->add(
                        "presentaciones.$indice.id_presentacion_origen",
                        'La presentacion seleccionada no pertenece al importador y producto actual.'
                    );
                }
            }

            $registrosAgregados = [];
            if ($request->filled('form_id_certificado') && empty($request->input('registros', []))) {
                $validador->errors()->add(
                    'registros',
                    'Para relacionar el producto al tramite debe agregar al menos un registro.'
                );
            }

            foreach ($request->input('registros', []) as $indice => $registroDato) {
                $indicePresentacion = $registroDato['id_presentacion_temporal'] ?? null;

                if ($indicePresentacion === null || !array_key_exists($indicePresentacion, $presentaciones)) {
                    $validador->errors()->add(
                        "registros.$indice.id_presentacion_temporal",
                        'Seleccione una presentacion agregada para este registro.'
                    );

                    continue;
                }

                $idUnidadRegistro = $registroDato['id_catalogo_unidad'] ?? null;
                if ($idUnidadRegistro && !$this->catalogoUnidadValido($request, $idUnidadRegistro)) {
                    $validador->errors()->add(
                        "registros.$indice.id_catalogo_unidad",
                        'Seleccione una unidad válida o registre una nueva.'
                    );
                }
                $claveRegistro = $this->claveRegistroProducto($indicePresentacion, $registroDato);

                if (isset($registrosAgregados[$claveRegistro])) {
                    $validador->errors()->add(
                        "registros.$indice.codigo_autorizacion",
                        'Este registro ya fue agregado para la presentacion seleccionada.'
                    );
                    continue;
                }

                $registrosAgregados[$claveRegistro] = true;
            }

        });

        $datos = $validador->validate();

        if (!empty($datos['form_id_certificado'])) {
            $this->validarTramiteAdmiteProducto(
                (int) $datos['form_id_certificado'],
                (int) $datos['form_id_importador_persona']
            );
        }

        return $datos;
    }

    // Evita que un producto se relacione a un tramite que no lo pidio o a otro beneficiario.
    private function validarTramiteAdmiteProducto(int $idCertificado, int $idImportadorPersona): void
    {
        $certificado = Certificado::findOrFail($idCertificado);

        if (!$certificado->requiereEvidencia('PRODUCTO')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form_id_certificado' => 'Este tramite no tiene producto configurado como requisito.',
            ]);
        }

        if ((int) $certificado->id_persona_beneficiario !== $idImportadorPersona) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form_id_importador_persona' => 'El producto debe registrarse para el beneficiario del tramite.',
            ]);
        }
    }

    /*
     * Protege el selector de presentaciones: aunque el navegador sea manipulado,
     * solo se permite copiar presentaciones del mismo importador y del mismo producto.
     */
    private function presentacionPerteneceAlProductoSolicitado(int $idPresentacion, Request $request): bool
    {
        $presentacion = Presentacion::with('producto')->find($idPresentacion);
        $productoOrigen = $presentacion?->producto;

        if (!$productoOrigen || (string) $productoOrigen->id_importador_persona !== (string) $request->input('form_id_importador_persona')) {
            return false;
        }

        $codigoFormulario = trim((string) $request->input('form_codigo'));
        $nombreFormulario = mb_strtolower(trim((string) $request->input('form_nombre_comercial')));

        if ($codigoFormulario !== '') {
            return mb_strtolower((string) $productoOrigen->codigo) === mb_strtolower($codigoFormulario);
        }

        return $nombreFormulario !== ''
            && mb_strtolower((string) $productoOrigen->nombre_comercial) === $nombreFormulario;
    }

    /*
     * Genera una clave estable para evitar que dos presentaciones iguales del mismo formulario
     * se guarden duplicadas cuando varios registros usan la misma presentacion.
     */
    private function clavePresentacionProducto(array $presentacionDato): string
    {
        return implode('|', [
            mb_strtolower(trim((string) ($presentacionDato['cantidad'] ?? ''))),
            mb_strtolower(trim((string) ($presentacionDato['id_catalogo_unidad'] ?? ''))),
            mb_strtolower(trim((string) ($presentacionDato['descripcion'] ?? ''))),
            mb_strtolower(trim((string) ($presentacionDato['estado'] ?? 'ACTIVO'))),
        ]);
    }

    /*
     * Evita duplicar un mismo registro dentro de la misma presentacion.
     * Puede haber varios registros por presentacion, pero deben diferenciarse en sus datos.
     */
    private function claveRegistroProducto(string|int $indicePresentacion, array $registroDato): string
    {
        return implode('|', [
            (string) $indicePresentacion,
            mb_strtolower(trim((string) ($registroDato['codigo_autorizacion'] ?? ''))),
            mb_strtolower(trim((string) ($registroDato['fecha_vigencia'] ?? ''))),
            mb_strtolower(trim((string) ($registroDato['cantidad'] ?? ''))),
            mb_strtolower(trim((string) ($registroDato['id_catalogo_unidad'] ?? ''))),
        ]);
    }

    // Solo permite regresar a URLs internas del sistema para evitar redirecciones externas.
    private function urlRetornoSegura(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        return str_starts_with($url, url('/')) || str_starts_with($url, '/')
            ? $url
            : null;
    }

    // Reconoce las opciones temporales creadas desde los modales del formulario.
    private function esTemporal(?string $valor): bool
    {
        return is_string($valor) && str_starts_with($valor, 'TEMP-');
    }

    // Verifica que un id enviado desde un select exista realmente en su tabla.
    private function esIdExistente($valor, string $modelo): bool
    {
        return ctype_digit((string) $valor) && $modelo::query()->whereKey($valor)->exists();
    }

    // Devuelve el id de fabricante existente o crea el nuevo fabricante temporal.
    private function resolverFabricante(Request $request, string $valor): int
    {
        if (ctype_digit($valor)) {
            return (int) $valor;
        }

        $fabricante = Fabricante::firstOrCreate(
            ['nombre' => $request->input('form_fabricante_temporal_nombre')],
            [
                'razon_social' => $request->input('form_fabricante_temporal_razon_social'),
                'descripcion' => $request->input('form_fabricante_temporal_descripcion'),
                'estado' => 'ACTIVO',
            ],
        );

        return $fabricante->id;
    }

    // Devuelve el id de tipo existente o crea el nuevo tipo de producto temporal.
    private function resolverTipoProducto(Request $request, string $valor): int
    {
        if (ctype_digit($valor)) {
            return (int) $valor;
        }

        $codigo = $request->input('form_tipo_producto_temporal_codigo') ?: null;

        if ($codigo) {
            $tipoProducto = TipoProducto::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'descripcion' => $request->input('form_tipo_producto_temporal_descripcion'),
                    'estado' => 'ACTIVO',
                ],
            );

            return $tipoProducto->id;
        }

        $tipoProducto = TipoProducto::create([
            'descripcion' => $request->input('form_tipo_producto_temporal_descripcion'),
            'estado' => 'ACTIVO',
        ]);

        return $tipoProducto->id;
    }

    private function catalogoUnidadValido(Request $request, $valor): bool
    {
        if ($this->esIdExistente($valor, CatalogoMedida::class)) {
            return true;
        }

        return $this->esTemporal((string) $valor)
            && $request->input('form_unidad_temporal_id') === $valor
            && $request->filled('form_unidad_temporal_nombre');
    }

    private function abreviaturaUnidadYaRegistrada(Request $request, $valor): bool
    {
        if (
            !$this->esTemporal((string) $valor)
            || $request->input('form_unidad_temporal_id') !== $valor
        ) {
            return false;
        }

        $nombre = trim((string) $request->input('form_unidad_temporal_nombre'));
        $abreviatura = trim((string) $request->input('form_unidad_temporal_abreviatura'));

        if ($nombre === '' || $abreviatura === '') {
            return false;
        }

        return CatalogoMedida::withTrashed()
            ->where('abreviatura', $abreviatura)
            ->where('nombre', '!=', $nombre)
            ->exists();
    }

    private function resolverClasificacionProducto(Request $request, $valor): ?int
    {
        if (blank($valor)) {
            return null;
        }

        if (ctype_digit((string) $valor)) {
            return (int) $valor;
        }

        $clasificacion = ClasificacionProducto::withTrashed()->firstOrNew(
            ['nombre' => trim((string) $request->input('form_clasificacion_temporal_nombre'))],
        );

        if (!$clasificacion->exists) {
            $clasificacion->fill([
                'descripcion' => $request->input('form_clasificacion_temporal_descripcion'),
                'estado' => 'ACTIVO',
            ]);
            $clasificacion->save();
        } elseif ($clasificacion->trashed()) {
            $clasificacion->restore();
            $clasificacion->estado = 'ACTIVO';
            $clasificacion->save();
        }

        return $clasificacion->id;
    }

    private function resolverCatalogoUnidad(Request $request, $valor): int
    {
        if (ctype_digit((string) $valor)) {
            return (int) $valor;
        }

        $unidad = CatalogoMedida::withTrashed()->firstOrNew(
            ['nombre' => trim((string) $request->input('form_unidad_temporal_nombre'))],
        );

        if (!$unidad->exists) {
            $unidad->fill([
                'abreviatura' => $request->input('form_unidad_temporal_abreviatura'),
                'tipo' => 'unidad de medida',
                'estado' => 'ACTIVO',
            ]);
            $unidad->save();
        } elseif ($unidad->trashed()) {
            $unidad->restore();
            $unidad->estado = 'ACTIVO';
            $unidad->save();
        }

        return $unidad->id;
    }
    // Devuelve el id de ingrediente existente o crea el nuevo ingrediente temporal.
    private function resolverIngrediente(array $ingredienteProducto): int
    {
        $valor = $ingredienteProducto['id_ingrediente'];

        if (ctype_digit((string) $valor)) {
            return (int) $valor;
        }

        $ingrediente = Ingrediente::firstOrCreate(
            [
                'nombre' => $ingredienteProducto['nombre'],
                'composicion' => $ingredienteProducto['composicion'] ?? null,
            ],
            [
                'riesgo_salud' => $ingredienteProducto['riesgo_salud'] ?? null,
                'estado' => 'ACTIVO',
            ],
        );

        return $ingrediente->id;
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto)
    {
        //
    }
}

