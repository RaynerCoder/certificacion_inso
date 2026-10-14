<?php

namespace App\Http\Controllers;

use App\Models\CatalogoMedida;
use App\Models\Certificado;
use App\Models\ClasificacionQuimica;
use App\Models\ClasificacionToxicologica;
use App\Models\Fabricante;
use App\Models\Ingrediente;
use App\Models\IngredienteProducto;
use App\Models\Persona;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\Registro;
use App\Models\Responsable;
use App\Models\Territorio;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    /**
     * Muestra el listado de productos.
     */
    public function index()
    {
        return view('productos.index');
    }

    /**
     * Abre el formulario de registro. Si viene desde un trámite, primero verifica
     * que ese trámite tenga configurado el requisito PRODUCTO.
     */
    public function create(Request $request)
    {
        $tramiteOrigen = null;

        if ($request->filled('form_id_certificado')) {
            $certificado = Certificado::with('tipoCertificado')->findOrFail($request->integer('form_id_certificado'));

            if (!$certificado->requiereEvidencia('PRODUCTO')) {
                return redirect()
                    ->route('certificados_show', [
                        'certificado' => $certificado,
                        'bandeja' => $request->input('bandeja', 'recibidas'),
                    ])
                    ->with('error', 'Este tramite no tiene producto configurado como requisito.');
            }

            $tramiteOrigen = $certificado;
        }

        return view('productos.create', array_merge(
            $this->datosFormularioProducto(),
            compact('tramiteOrigen')
        ));
    }

    /**
     * Guarda el producto y todos sus datos relacionados en una sola transacción.
     * Si algo falla, no queda información guardada a medias.
     */
    public function store(Request $request)
    {
        $datos = $this->validarProducto($request);

        $productoCreado = DB::transaction(function () use ($request, $datos) {
            // Los catálogos creados desde los modales llegan como TEMP-* y aquí se convierten a IDs reales.
            $idFabricante = $this->resolverFabricante($request, $datos['form_id_fabricante']);
            $idClasificacionToxicologica = $this->resolverClasificacionToxicologica(
                $request,
                $datos['form_id_clasificacion_toxicologica']
            );
            $idClasificacionQuimica = $this->resolverClasificacionQuimica(
                $request,
                $datos['form_id_clasificacion_quimica'] ?? null
            );

            $producto = $this->crearProducto(
                $datos,
                $idFabricante,
                $idClasificacionToxicologica,
                $idClasificacionQuimica
            );

            $this->guardarIngredientes($request, $producto);

            // Se conserva la relación entre el índice temporal del formulario y el ID real de la presentación.
            $presentacionesCreadas = $this->guardarPresentaciones($request, $producto);
            $registrosCreados = $this->guardarRegistros($request, $producto, $presentacionesCreadas);

            // El trámite se relaciona con los registros creados, no directamente con el producto.
            $this->relacionarRegistrosConTramite(
                $datos['form_id_certificado'] ?? null,
                $registrosCreados
            );

            return $producto;
        });

        $urlRetorno = $this->urlRetornoSegura($datos['form_retorno'] ?? null);
        $urlDestino = $urlRetorno ?: route('productos_index');
        $mensaje = $urlRetorno
            ? 'Producto registrado correctamente y relacionado al tramite.'
            : 'Producto registrado correctamente.';

        // El formulario normal usa una petición en segundo plano para conservar los datos si hay errores.
        if ($request->expectsJson()) {
            session()->flash('success', $mensaje);

            return response()->json(['redirect' => $urlDestino]);
        }

        return $urlRetorno
            ? redirect()->to($urlRetorno)->with('success', $mensaje)
            : redirect()->route('productos_index')->with('success', $mensaje);
    }

    /**
     * Muestra toda la información relacionada con el producto en una sola pantalla.
     */
    public function show(Producto $producto)
    {
        $producto->load([
            'importadorPersona.natural',
            'importadorPersona.empresa.responsables.persona.natural',
            'importadorPersona.empresa.responsables.rol',
            'territorio',
            'fabricante',
            'clasificacionToxicologica',
            'clasificacionQuimica',
            'ingredientesProductos.ingrediente',
            'presentaciones.catalogoUnidad',
            'registros.catalogoUnidad',
            'registros.presentacion.catalogoUnidad',
            'registros.certificadosRegistros',
            'aduanas',
        ]);

        return view('productos.show', compact('producto'));
    }

    /**
     * Entrega la etiqueta desde el disco privado de la aplicación.
     * Así la imagen se puede ver aunque el servidor no tenga creado el enlace public/storage.
     */
    public function etiquetaPresentacion(Presentacion $presentacion)
    {
        $ruta = $presentacion->url_etiqueta;

        abort_unless($ruta && Storage::disk('public')->exists($ruta), 404);

        return Storage::disk('public')->response($ruta, basename($ruta), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Reutiliza el formulario de registro y lo carga con los datos guardados.
     */
    public function edit(Producto $producto)
    {
        $producto->load([
            'ingredientesProductos.ingrediente',
            'presentaciones.catalogoUnidad',
            'registros.catalogoUnidad',
            'registros.presentacion.catalogoUnidad',
            'registros.certificadosRegistros',
        ]);

        return view('productos.create', array_merge(
            $this->datosFormularioProducto($producto),
            $this->datosEdicionProducto($producto),
            compact('producto')
        ));
    }

    /**
     * Actualiza el producto y sincroniza las filas que pertenecen al formulario.
     * Todo se ejecuta dentro de una transacción para no dejar datos a medias.
     */
    public function update(Request $request, Producto $producto)
    {
        $datos = $this->validarProducto($request, $producto);

        DB::transaction(function () use ($request, $datos, $producto) {
            $idFabricante = $this->resolverFabricante($request, $datos['form_id_fabricante']);
            $idClasificacionToxicologica = $this->resolverClasificacionToxicologica(
                $request,
                $datos['form_id_clasificacion_toxicologica']
            );
            $idClasificacionQuimica = $this->resolverClasificacionQuimica(
                $request,
                $datos['form_id_clasificacion_quimica'] ?? null
            );

            $producto->update($this->datosPrincipalesProducto(
                $datos,
                $idFabricante,
                $idClasificacionToxicologica,
                $idClasificacionQuimica
            ));

            $this->sincronizarIngredientesProducto($request, $producto);

            [$presentaciones, $presentacionesConservadas] = $this->sincronizarPresentacionesProducto(
                $request,
                $producto
            );

            $this->sincronizarRegistrosProducto($request, $producto, $presentaciones);
            $this->retirarPresentacionesProducto($producto, $presentacionesConservadas);
        });

        $mensaje = 'Producto actualizado correctamente.';
        // Después de guardar se vuelve al listado para continuar administrando productos.
        $urlDestino = route('productos_index');

        if ($request->expectsJson()) {
            session()->flash('success', $mensaje);

            return response()->json(['redirect' => $urlDestino]);
        }

        return redirect()->to($urlDestino)->with('success', $mensaje);
    }

    /**
     * La eliminación es lógica. Si el producto ya participa en un trámite o en
     * una operación de aduana, se conserva para no perder el historial.
     */
    public function destroy(Producto $producto)
    {
        $relaciones = $this->relacionesQueImpidenEliminarProducto($producto);

        if ($relaciones !== []) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'El producto tiene ' . implode(' y ', $relaciones) . '.',
                'icon' => 'error',
            ]);

            return redirect()->route('productos_index');
        }

        DB::transaction(function () use ($producto) {
            $producto->ingredientesProductos->each(function (IngredienteProducto $relacion) {
                $relacion->update(['estado' => 'INACTIVO']);
                $relacion->delete();
            });

            $producto->registros->each(function (Registro $registro) {
                $registro->update(['estado' => 'INACTIVO']);
                $registro->delete();
            });

            $producto->presentaciones->each(function (Presentacion $presentacion) {
                $presentacion->update(['estado' => 'INACTIVO']);
                $presentacion->delete();
            });

            $producto->update(['estado' => 'INACTIVO']);
            $producto->delete();
        });

        session()->flash('swal', [
            'title' => 'Producto eliminado',
            'text' => 'El producto se eliminó correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('productos_index');
    }

    /**
     * Prepara los catálogos y las presentaciones que necesita el formulario de producto.
     *
     * Este método se usa tanto al crear como al editar. Por ejemplo, entrega fabricantes,
     * unidades y tipos de envase para que la vista solo los muestre, sin hacer consultas.
     * Al editar, también incluye las presentaciones inactivas del producto actual para no
     * ocultar información que ya está relacionada con él.
     */
    private function datosFormularioProducto(?Producto $producto = null): array
    {
        $presentacionesCatalogo = Presentacion::with(['producto', 'catalogoUnidad'])
            ->where(function ($consulta) use ($producto) {
                $consulta->where(function ($activas) {
                    $activas->where('estado', 'ACTIVO')
                        ->whereHas('producto', fn ($productoConsulta) => $productoConsulta->where('estado', 'ACTIVO'));
                });

                if ($producto) {
                    // En edición también se cargan las presentaciones inactivas del producto actual.
                    $consulta->orWhere('id_producto', $producto->id);
                }
            })
            ->orderByDesc('id')
            ->get();

        return [
            'tiposProducto' => Producto::TIPOS_PRODUCTO,
            'clasificacionesToxicologicas' => ClasificacionToxicologica::query()
                ->where('estado', 'ACTIVO')
                ->orderBy('codigo')
                ->get(),

            'fabricantes' => Fabricante::query()
                ->where('estado', 'ACTIVO')
                ->orderByRaw('CASE WHEN razon_social IS NULL OR razon_social = ? THEN 1 ELSE 0 END', [''])
                ->orderBy('razon_social')
                ->orderBy('nombre')
                ->get(),

            'territorios' => Territorio::query()
                ->where('estado', 'ACTIVO')
                ->where('id_ambito', 1)
                ->orderBy('nombre')
                ->get(),

            'importadores' => $this->importadoresDisponibles(),

            // Se muestra un solo ingrediente por nombre aunque existan registros históricos duplicados.
            'ingredientes' => Ingrediente::query()
                ->where('estado', 'ACTIVO')
                ->orderBy('id')
                ->get()
                ->unique(fn (Ingrediente $ingrediente) => mb_strtolower(trim((string) $ingrediente->nombre)))
                ->sortBy(fn (Ingrediente $ingrediente) => mb_strtolower(trim((string) $ingrediente->nombre)))
                ->values(),

            'clasificacionesQuimicas' => ClasificacionQuimica::query()
                ->where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get(),

            'catalogosMedidas' => CatalogoMedida::query()
                ->where('estado', 'ACTIVO')
                ->orderBy('tipo')
                ->orderBy('nombre')
                ->get(),

            'presentacionesCatalogo' => $presentacionesCatalogo,

            // Lee los tipos de envase ya usados en presentaciones.
            // No existe una tabla adicional: el valor se obtiene de presentaciones.tipo_envase.
            'tiposEnvases' => Presentacion::query()
                ->where('estado', 'ACTIVO')
                ->whereNotNull('tipo_envase')
                ->where('tipo_envase', '!=', '')
                ->select('tipo_envase')
                ->distinct()
                ->orderBy('tipo_envase')
                ->pluck('tipo_envase'),
        ];
    }

    /**
     * Carga los importadores junto con el dato que explica cómo está vinculada cada persona.
     * El ID que se envía al formulario no cambia: esta información solo mejora el selector.
     */
    private function importadoresDisponibles(): Collection
    {
        return Persona::query()
            ->with([
                'natural',
                'empresa.persona',
                'empresa.responsables.persona.natural',
                'empresa.responsables.rol',
                'responsabilidadesEmpresariales.empresa.persona',
                'responsabilidadesEmpresariales.rol',
            ])
            ->where('estado', 'ACTIVO')
            ->orderBy('id')
            ->get()
            ->each(function (Persona $persona) {
                // Atributo temporal para la vista; no se guarda en la tabla personas.
                $persona->setAttribute('detalle_importador', $this->detalleImportador($persona));
            });
    }

    /**
     * Resume qué representa una opción dentro del selector de importadores.
     */
    private function detalleImportador(Persona $persona): string
    {
        if ($persona->empresa) {
            return $this->detalleEmpresaImportadora($persona);
        }

        if ($persona->natural) {
            return $this->detallePersonaNaturalImportadora($persona);
        }

        return 'Persona sin clasificación';
    }

    /**
     * Una empresa se identifica con sus representantes legales activos.
     */
    private function detalleEmpresaImportadora(Persona $personaEmpresa): string
    {
        $empresa = $personaEmpresa->empresa;
        $representantes = $empresa->responsables
            ->filter(fn (Responsable $responsable) => $this->relacionEmpresarialActiva($responsable, $empresa))
            ->filter(fn (Responsable $responsable) => $responsable->rol?->slug === 'solicitante')
            ->map(fn (Responsable $responsable) => $this->nombrePersonaImportador($responsable->persona))
            ->filter()
            ->unique()
            ->values();

        if ($representantes->isEmpty()) {
            return 'Empresa · Sin representante legal activo';
        }

        $etiqueta = $representantes->count() === 1 ? 'Representante legal' : 'Representantes legales';

        return 'Empresa · '.$etiqueta.': '.$representantes->implode(', ');
    }

    /**
     * Para una persona natural muestra cada empresa donde tiene una autorización vigente.
     */
    private function detallePersonaNaturalImportadora(Persona $persona): string
    {
        $detalles = $persona->responsabilidadesEmpresariales
            ->filter(fn (Responsable $responsable) => $this->relacionEmpresarialActiva($responsable))
            ->groupBy('id_empresa')
            ->map(function (Collection $relaciones) {
                $empresa = $relaciones->first()?->empresa;
                $roles = $relaciones->pluck('rol.slug')->filter()->unique();
                $tipoRelacion = match (true) {
                    $roles->contains('solicitante') && $roles->contains('tramitador') => 'Representante legal y tramitador autorizado',
                    $roles->contains('solicitante') => 'Representante legal',
                    default => 'Tramitador autorizado',
                };
                $nombreEmpresa = trim((string) $empresa?->razon_social) ?: 'Empresa #'.$relaciones->first()?->id_empresa;

                return $tipoRelacion.' de '.$nombreEmpresa;
            })
            ->values();

        return $detalles->isNotEmpty() ? $detalles->implode(' · ') : 'Persona natural';
    }

    /**
     * Una relación sirve para el selector solo si la relación, el rol, la persona y la empresa siguen activos.
     */
    private function relacionEmpresarialActiva(Responsable $responsable, $empresa = null): bool
    {
        $empresa ??= $responsable->empresa;
        $rol = $responsable->rol;
        $persona = $responsable->persona;

        return in_array((string) $responsable->estado, ['1', 'ACTIVO'], true)
            && $rol
            && in_array((string) $rol->estado, ['1', 'ACTIVO'], true)
            && in_array($rol->slug, ['solicitante', 'tramitador'], true)
            && $persona
            && in_array((string) $persona->estado, ['1', 'ACTIVO'], true)
            && (bool) $persona->natural
            && $empresa
            && in_array((string) $empresa->estado, ['1', 'ACTIVO'], true)
            && $empresa->persona
            && in_array((string) $empresa->persona->estado, ['1', 'ACTIVO'], true);
    }

    /**
     * Genera el nombre legible de una persona natural para mostrarlo debajo de una empresa.
     */
    private function nombrePersonaImportador(Persona $persona): string
    {
        $nombre = trim(implode(' ', array_filter([
            $persona->natural?->nombres,
            $persona->natural?->apellido_paterno,
            $persona->natural?->apellido_materno,
        ])));

        return $nombre ?: 'Persona #'.$persona->id;
    }

    /**
     * Convierte las relaciones guardadas al mismo formato de los arrays del formulario.
     */
    private function datosEdicionProducto(Producto $producto): array
    {
        $ingredientes = $producto->ingredientesProductos
            ->map(fn (IngredienteProducto $relacion) => [
                'id_ingrediente' => $relacion->id_ingrediente,
                'nombre' => $relacion->ingrediente?->nombre,
                'porcentaje' => $relacion->porcentaje,
                'estado' => $relacion->estado,
            ])
            ->values()
            ->all();

        $presentaciones = $producto->presentaciones
            ->values()
            ->map(fn (Presentacion $presentacion) => [
                'id_presentacion_origen' => $presentacion->id,
                'cantidad' => $presentacion->cantidad,
                'id_catalogo_unidad' => $presentacion->id_catalogo_unidad,
                'tipo_envase' => $presentacion->tipo_envase,
                'descripcion' => $presentacion->descripcion,
                'estado' => $presentacion->estado,
            ])
            ->all();

        $indicePorPresentacion = $producto->presentaciones
            ->values()
            ->mapWithKeys(fn (Presentacion $presentacion, int $indice) => [$presentacion->id => $indice]);

        $registros = $producto->registros
            ->filter(fn (Registro $registro) => $indicePorPresentacion->has($registro->id_presentacion))
            ->map(fn (Registro $registro) => [
                'id_registro' => $registro->id,
                'codigo_autorizacion' => $registro->codigo_autorizacion,
                'numero_lote' => $registro->numero_lote,
                'fecha_vigencia' => $registro->fecha_vigencia,
                'cantidad' => $registro->cantidad,
                'id_catalogo_unidad' => $registro->id_catalogo_unidad,
                'id_presentacion_temporal' => $indicePorPresentacion->get($registro->id_presentacion),
                'estado' => $registro->estado,
                'bloqueado_eliminacion' => $registro->certificadosRegistros->isNotEmpty(),
            ])
            ->values()
            ->all();

        return [
            'ingredientesIniciales' => $ingredientes,
            'presentacionesIniciales' => $presentaciones,
            'registrosIniciales' => $registros,
        ];
    }

    /**
     * Devuelve las relaciones externas que obligan a conservar el producto.
     */
    private function relacionesQueImpidenEliminarProducto(Producto $producto): array
    {
        $relaciones = [];

        if ($producto->aduanas()->exists()) {
            $relaciones[] = 'operaciones de aduana';
        }

        if ($producto->registros()->whereHas('certificadosRegistros')->exists()) {
            $relaciones[] = 'registros utilizados por certificados';
        }

        if ($producto->presentaciones()->whereHas('registros', function ($consulta) use ($producto) {
            $consulta->where('id_producto', '!=', $producto->id);
        })->exists()) {
            $relaciones[] = 'presentaciones utilizadas por otros productos';
        }

        return $relaciones;
    }

    /**
     * Crea solamente la fila principal de productos.
     */
    private function crearProducto(
        array $datos,
        int $idFabricante,
        int $idClasificacionToxicologica,
        ?int $idClasificacionQuimica
    ): Producto {
        return Producto::create($this->datosPrincipalesProducto(
            $datos,
            $idFabricante,
            $idClasificacionToxicologica,
            $idClasificacionQuimica
        ));
    }

    /**
     * Arma los datos principales una sola vez para crear y actualizar.
     */
    private function datosPrincipalesProducto(
        array $datos,
        int $idFabricante,
        int $idClasificacionToxicologica,
        ?int $idClasificacionQuimica
    ): array {
        return [
            'id_importador_persona' => $datos['form_id_importador_persona'],
            'id_territorio_pais' => $datos['form_id_territorio_pais'],
            'id_fabricante' => $idFabricante,
            'id_clasificacion_toxicologica' => $idClasificacionToxicologica,
            'codigo' => $datos['form_codigo'],
            'nombre_comercial' => $datos['form_nombre_comercial'],
            'tipo_producto' => $datos['form_tipo_producto'],
            'nombre_tecnico' => $datos['form_nombre_tecnico'] ?? null,
            'id_clasificacion_quimica' => $idClasificacionQuimica,
            'estado' => $datos['form_estado'],
        ];
    }

    /**
     * Guarda los ingredientes agregados en la tabla ingredientes_productos.
     */
    private function guardarIngredientes(Request $request, Producto $producto): void
    {
        foreach ($request->input('ingredientes_productos', []) as $ingredienteProducto) {
            $idIngrediente = $this->resolverIngrediente($ingredienteProducto);

            IngredienteProducto::create([
                'id_producto' => $producto->id,
                'id_ingrediente' => $idIngrediente,
                'porcentaje' => $ingredienteProducto['porcentaje'],
                'estado' => $ingredienteProducto['estado'] ?? 'ACTIVO',
            ]);
        }
    }

    /**
     * Guarda las presentaciones nuevas y reutiliza las que ya existen.
     * Devuelve un mapa índice temporal => ID real para relacionar luego los registros.
     */
    private function guardarPresentaciones(Request $request, Producto $producto): array
    {
        $presentacionesCreadas = [];
        $presentacionesUnicas = [];

        foreach ($request->input('presentaciones', []) as $indice => $presentacionDato) {
            $idPresentacionOrigen = $presentacionDato['id_presentacion_origen'] ?? null;

            // Si la presentación ya estaba registrada, se reutiliza su ID y no se crea otra fila.
            if ($idPresentacionOrigen) {
                $presentacionesCreadas[$indice] = (int) $idPresentacionOrigen;
                continue;
            }

            $clavePresentacion = $this->clavePresentacionProducto($presentacionDato);

            if (isset($presentacionesUnicas[$clavePresentacion])) {
                $presentacionesCreadas[$indice] = $presentacionesUnicas[$clavePresentacion];
                continue;
            }

            $archivoEtiqueta = $request->file("presentaciones.$indice.url_etiqueta");
            $rutaEtiqueta = $archivoEtiqueta
                ? $archivoEtiqueta->store('productos/etiquetas', 'public')
                : null;

            $presentacion = Presentacion::create([
                'id_producto' => $producto->id,
                'url_etiqueta' => $rutaEtiqueta,
                'cantidad' => $presentacionDato['cantidad'],
                'id_catalogo_unidad' => $this->resolverCatalogoUnidad(
                    $request,
                    $presentacionDato['id_catalogo_unidad']
                ),
                'descripcion' => $presentacionDato['descripcion'] ?? null,
                'tipo_envase' => $presentacionDato['tipo_envase'] ?? null,
                'estado' => $presentacionDato['estado'] ?? 'ACTIVO',
            ]);

            $presentacionesCreadas[$indice] = $presentacion->id;
            $presentacionesUnicas[$clavePresentacion] = $presentacion->id;
        }

        return $presentacionesCreadas;
    }

    /**
     * Guarda cada registro con su propia unidad y con la presentación que le corresponde.
     */
    private function guardarRegistros(
        Request $request,
        Producto $producto,
        array $presentacionesCreadas
    ): array {
        $registrosCreados = [];

        foreach ($request->input('registros', []) as $registroDato) {
            $indicePresentacion = $registroDato['id_presentacion_temporal'] ?? null;

            $registro = Registro::create([
                'id_producto' => $producto->id,
                'codigo_autorizacion' => $registroDato['codigo_autorizacion'] ?? null,
                'numero_lote' => $registroDato['numero_lote'] ?? null,
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

        return $registrosCreados;
    }

    /**
     * Actualiza la composición sin cambiar los IDs de las filas que ya existen.
     */
    private function sincronizarIngredientesProducto(Request $request, Producto $producto): void
    {
        $relacionesActuales = $producto->ingredientesProductos()
            ->get()
            ->keyBy(fn (IngredienteProducto $relacion) => (string) $relacion->id_ingrediente);

        $relacionesConservadas = [];

        foreach ($request->input('ingredientes_productos', []) as $datosIngrediente) {
            $idIngrediente = $this->resolverIngrediente($datosIngrediente);
            $relacion = $relacionesActuales->get((string) $idIngrediente);
            $datosRelacion = [
                'id_producto' => $producto->id,
                'id_ingrediente' => $idIngrediente,
                'porcentaje' => $datosIngrediente['porcentaje'],
                'estado' => $datosIngrediente['estado'] ?? 'ACTIVO',
            ];

            if ($relacion) {
                $relacion->update($datosRelacion);
            } else {
                $relacion = IngredienteProducto::create($datosRelacion);
            }

            $relacionesConservadas[] = $relacion->id;
        }

        $producto->ingredientesProductos()
            ->when($relacionesConservadas !== [], fn ($consulta) => $consulta->whereNotIn('id', $relacionesConservadas))
            ->get()
            ->each(function (IngredienteProducto $relacion) {
                $relacion->update(['estado' => 'INACTIVO']);
                $relacion->delete();
            });
    }

    /**
     * Actualiza las presentaciones existentes y crea las nuevas.
     * El primer valor devuelto relaciona el índice del formulario con el ID real.
     */
    private function sincronizarPresentacionesProducto(Request $request, Producto $producto): array
    {
        $presentacionesPorIndice = [];
        $presentacionesConservadas = [];
        $presentacionesUnicas = [];

        foreach ($request->input('presentaciones', []) as $indice => $datosPresentacion) {
            $idOrigen = $datosPresentacion['id_presentacion_origen'] ?? null;
            $presentacion = $idOrigen ? Presentacion::find($idOrigen) : null;

            // Una presentación del mismo producto se modifica; una externa solo se reutiliza.
            if ($presentacion) {
                if ((int) $presentacion->id_producto === (int) $producto->id) {
                    $archivoEtiqueta = $request->file("presentaciones.$indice.url_etiqueta");

                    $presentacion->update([
                        'url_etiqueta' => $archivoEtiqueta
                            ? $archivoEtiqueta->store('productos/etiquetas', 'public')
                            : $presentacion->url_etiqueta,
                        'cantidad' => $datosPresentacion['cantidad'],
                        'id_catalogo_unidad' => $this->resolverCatalogoUnidad(
                            $request,
                            $datosPresentacion['id_catalogo_unidad']
                        ),
                        'tipo_envase' => $datosPresentacion['tipo_envase'] ?? null,
                        'descripcion' => $datosPresentacion['descripcion'] ?? null,
                        'estado' => $datosPresentacion['estado'] ?? 'ACTIVO',
                    ]);

                    $presentacionesConservadas[] = $presentacion->id;
                }

                $presentacionesPorIndice[$indice] = $presentacion->id;
                continue;
            }

            $clave = $this->clavePresentacionProducto($datosPresentacion);

            if (isset($presentacionesUnicas[$clave])) {
                $presentacionesPorIndice[$indice] = $presentacionesUnicas[$clave];
                continue;
            }

            $archivoEtiqueta = $request->file("presentaciones.$indice.url_etiqueta");
            $nuevaPresentacion = Presentacion::create([
                'id_producto' => $producto->id,
                'url_etiqueta' => $archivoEtiqueta
                    ? $archivoEtiqueta->store('productos/etiquetas', 'public')
                    : null,
                'cantidad' => $datosPresentacion['cantidad'],
                'id_catalogo_unidad' => $this->resolverCatalogoUnidad(
                    $request,
                    $datosPresentacion['id_catalogo_unidad']
                ),
                'tipo_envase' => $datosPresentacion['tipo_envase'] ?? null,
                'descripcion' => $datosPresentacion['descripcion'] ?? null,
                'estado' => $datosPresentacion['estado'] ?? 'ACTIVO',
            ]);

            $presentacionesPorIndice[$indice] = $nuevaPresentacion->id;
            $presentacionesConservadas[] = $nuevaPresentacion->id;
            $presentacionesUnicas[$clave] = $nuevaPresentacion->id;
        }

        return [$presentacionesPorIndice, array_values(array_unique($presentacionesConservadas))];
    }

    /**
     * Conserva los IDs recibidos, actualiza sus datos y elimina únicamente las
     * filas retiradas que todavía no fueron usadas por un certificado.
     */
    private function sincronizarRegistrosProducto(
        Request $request,
        Producto $producto,
        array $presentacionesPorIndice
    ): void {
        $registrosConservados = [];

        foreach ($request->input('registros', []) as $datosRegistro) {
            $indicePresentacion = $datosRegistro['id_presentacion_temporal'] ?? null;
            $datosParaGuardar = [
                'id_producto' => $producto->id,
                'codigo_autorizacion' => $datosRegistro['codigo_autorizacion'] ?? null,
                'numero_lote' => $datosRegistro['numero_lote'] ?? null,
                'fecha_vigencia' => $datosRegistro['fecha_vigencia'] ?? null,
                'cantidad' => $datosRegistro['cantidad'] ?? null,
                'id_catalogo_unidad' => !empty($datosRegistro['id_catalogo_unidad'])
                    ? $this->resolverCatalogoUnidad($request, $datosRegistro['id_catalogo_unidad'])
                    : null,
                'id_presentacion' => $indicePresentacion !== null
                    ? ($presentacionesPorIndice[$indicePresentacion] ?? null)
                    : null,
                'estado' => $datosRegistro['estado'] ?? 'ACTIVO',
            ];

            $idRegistro = $datosRegistro['id_registro'] ?? null;
            $registro = $idRegistro
                ? $producto->registros()->whereKey($idRegistro)->first()
                : null;

            if ($registro) {
                $registro->update($datosParaGuardar);
            } else {
                $registro = Registro::create($datosParaGuardar);
            }

            $registrosConservados[] = $registro->id;
        }

        $registrosRetirados = $producto->registros()
            // Los registros antiguos sin presentación no aparecen en este formulario y se conservan sin cambios.
            ->whereHas('presentacion')
            ->when($registrosConservados !== [], fn ($consulta) => $consulta->whereNotIn('id', $registrosConservados))
            ->get();

        foreach ($registrosRetirados as $registro) {
            if ($registro->certificadosRegistros()->exists()) {
                throw ValidationException::withMessages([
                    'registros' => 'No puede quitar el registro ' . ($registro->codigo_autorizacion ?: '#' . $registro->id)
                        . ' porque ya está relacionado con un certificado.',
                ]);
            }

            $registro->update(['estado' => 'INACTIVO']);
            $registro->delete();
        }
    }

    /**
     * Retira las presentaciones que ya no aparecen en el formulario. Cuando una
     * todavía tiene registros asociados, se deja inactiva para conservar el historial.
     */
    private function retirarPresentacionesProducto(Producto $producto, array $presentacionesConservadas): void
    {
        $presentacionesRetiradas = $producto->presentaciones()
            ->when($presentacionesConservadas !== [], fn ($consulta) => $consulta->whereNotIn('id', $presentacionesConservadas))
            ->get();

        foreach ($presentacionesRetiradas as $presentacion) {
            if ($presentacion->registros()->exists()) {
                $presentacion->update(['estado' => 'INACTIVO']);
                continue;
            }

            $presentacion->update(['estado' => 'INACTIVO']);
            $presentacion->delete();
        }
    }

    /**
     * Relaciona con el trámite únicamente los registros creados en este guardado.
     */
    private function relacionarRegistrosConTramite($idCertificado, array $registrosCreados): void
    {
        if (!$idCertificado || empty($registrosCreados)) {
            return;
        }

        Certificado::find($idCertificado)
            ?->registros()
            ->syncWithoutDetaching($registrosCreados);
    }

    
    // Valida la información enviada antes de iniciar la transacción.
    private function validarProducto(Request $request, ?Producto $producto = null): array
    {
        $validador = Validator::make(
            $request->all(),
            [
                'form_codigo' => ['required', 'string', 'max:150'],
                'form_nombre_comercial' => ['required', 'string', 'max:255'],
                'form_tipo_producto' => ['required', Rule::in(array_keys(Producto::TIPOS_PRODUCTO))],
                'form_nombre_tecnico' => ['nullable', 'string', 'max:255'],
                'form_id_clasificacion_quimica' => ['nullable', 'string', 'max:50'],
                'form_id_importador_persona' => ['required', 'integer', 'exists:personas,id'],
                'form_id_territorio_pais' => ['required', 'integer', 'exists:territorios,id'],
                'form_id_fabricante' => ['required', 'string', 'max:50'],
                'form_id_clasificacion_toxicologica' => ['required', 'string', 'max:50'],
                'form_estado' => ['required', 'in:ACTIVO,INACTIVO'],

                // Datos temporales de catálogos que se pueden crear desde los modales.
                'form_fabricante_temporal_nombre' => ['nullable', 'string', 'max:255'],
                'form_fabricante_temporal_razon_social' => ['nullable', 'string', 'max:255'],
                'form_fabricante_temporal_descripcion' => ['nullable', 'string'],
                'form_clasificacion_toxicologica_temporal_codigo' => ['nullable', 'string', 'max:150'],
                'form_clasificacion_toxicologica_temporal_descripcion' => ['nullable', 'string', 'max:255'],
                'form_clasificacion_quimica_temporal_nombre' => ['nullable', 'string', 'max:255'],
                'form_clasificacion_quimica_temporal_descripcion' => ['nullable', 'string'],

                // Unidades nuevas creadas desde los select de presentación o registro.
                // Se mantienen separadas porque cada select conserva su propia selección.
                'form_presentacion_unidad_temporal_id' => ['nullable', 'string', 'max:50'],
                'form_presentacion_unidad_temporal_nombre' => ['nullable', 'string', 'max:255'],
                'form_presentacion_unidad_temporal_abreviatura' => ['nullable', 'string', 'max:50'],
                
                'form_registro_unidad_temporal_id' => ['nullable', 'string', 'max:50'],
                'form_registro_unidad_temporal_nombre' => ['nullable', 'string', 'max:255'],
                'form_registro_unidad_temporal_abreviatura' => ['nullable', 'string', 'max:50'],

                // Compatibilidad con la lista temporal compartida, por si otra vista ya la utiliza.
                'unidades_temporales' => ['nullable', 'array'],
                'unidades_temporales.*.nombre' => ['nullable', 'string', 'max:255'],
                'unidades_temporales.*.abreviatura' => ['nullable', 'string', 'max:50'],

                // Compatibilidad con formularios anteriores que usaban una sola unidad temporal.
                'form_unidad_temporal_id' => ['nullable', 'string', 'max:50'],
                'form_unidad_temporal_nombre' => ['nullable', 'string', 'max:255'],
                'form_unidad_temporal_abreviatura' => ['nullable', 'string', 'max:50'],

                'ingredientes_productos' => ['nullable', 'array'],
                'ingredientes_productos.*.id_ingrediente' => ['required_with:ingredientes_productos', 'string', 'max:50'],
                'ingredientes_productos.*.nombre' => ['nullable', 'string', 'max:255'],
                'ingredientes_productos.*.porcentaje' => ['required_with:ingredientes_productos', 'numeric', 'decimal:0,5', 'min:0', 'max:100'],
                'ingredientes_productos.*.estado' => ['nullable', 'in:ACTIVO,INACTIVO'],

                'presentaciones' => ['nullable', 'array'],
                'presentaciones.*.cantidad' => ['required_with:presentaciones', 'integer', 'min:1'],
                'presentaciones.*.id_catalogo_unidad' => ['required_with:presentaciones', 'string', 'max:50'],
                'presentaciones.*.descripcion' => ['nullable', 'string'],
                'presentaciones.*.estado' => ['nullable', 'in:ACTIVO,INACTIVO'],
                'presentaciones.*.id_presentacion_origen' => ['nullable', 'integer', 'exists:presentaciones,id'],
                'presentaciones.*.url_etiqueta' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'presentaciones.*.tipo_envase' => ['nullable', 'string', 'max:150'],
                

                'registros' => ['nullable', 'array'],
                'registros.*.id_registro' => ['nullable', 'integer', 'exists:registros,id'],
                'registros.*.codigo_autorizacion' => ['nullable', 'string', 'max:255'],
                'registros.*.numero_lote' => ['nullable', 'string', 'max:150'],
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
                'mimes' => 'La etiqueta debe ser una imagen JPG, PNG o WEBP.',
                'max' => 'El dato ingresado supera el limite permitido.',
                'min' => 'El valor ingresado es menor al permitido.',
                'form_tipo_producto.in' => 'Seleccione un tipo de producto valido.',
            ],
            [
                'form_codigo' => 'codigo',
                'form_nombre_comercial' => 'nombre comercial',
                'form_tipo_producto' => 'tipo de producto',
                'form_id_importador_persona' => 'importador',
                'form_id_territorio_pais' => 'pais',
                'form_id_fabricante' => 'fabricante',
                'form_id_clasificacion_toxicologica' => 'clasificación toxicológica',
                'ingredientes_productos.*.id_ingrediente' => 'ingrediente',
                'ingredientes_productos.*.porcentaje' => 'porcentaje',
                'presentaciones.*.cantidad' => 'cantidad',
                'presentaciones.*.id_catalogo_unidad' => 'unidad de presentación',
                'presentaciones.*.id_presentacion_origen' => 'presentacion registrada',
                'presentaciones.*.url_etiqueta' => 'imagen de la etiqueta',
                'registros.*.codigo_autorizacion' => 'codigo de autorizacion',
                'registros.*.numero_lote' => 'número de lote',
                'registros.*.id_catalogo_unidad' => 'unidad del registro',
                'registros.*.id_presentacion_temporal' => 'presentacion del registro',
                'form_id_certificado' => 'tramite',
            ]
        );

        // Los campos TEMP-* necesitan una revisión adicional porque todavía no tienen un ID de base de datos.
        $validador->after(function ($validador) use ($request, $producto) {
            $this->validarFabricanteTemporal($validador, $request);
            $this->validarClasificacionToxicologicaTemporal($validador, $request);
            $this->validarClasificacionQuimicaTemporal($validador, $request);
            $this->validarIngredientesTemporales($validador, $request);
            $this->validarPresentaciones($validador, $request);
            $this->validarRegistros($validador, $request);

            if ($producto) {
                $this->validarIdsEdicionProducto($validador, $request, $producto);
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

    /**
     * Revisa el fabricante cuando fue creado desde el modal.
     */
    private function validarFabricanteTemporal($validador, Request $request): void
    {
        $valor = $request->input('form_id_fabricante');

        if (!$request->filled('form_id_fabricante') || $this->esIdExistente($valor, Fabricante::class)) {
            return;
        }

        if (!$this->esTemporal($valor) || !$request->filled('form_fabricante_temporal_nombre')) {
            $validador->errors()->add('form_id_fabricante', 'Seleccione un fabricante valido o registre uno nuevo.');
            return;
        }

        $nombre = trim((string) $request->input('form_fabricante_temporal_nombre'));
        $razonSocial = trim((string) $request->input('form_fabricante_temporal_razon_social', ''));

        if (Fabricante::withTrashed()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($nombre)])
            ->exists()) {
            $validador->errors()->add(
                'form_id_fabricante',
                'Ya existe un fabricante con el mismo nombre. Selecciónelo del listado.'
            );
            return;
        }

        if ($razonSocial !== '' && Fabricante::withTrashed()
            ->whereRaw('LOWER(TRIM(razon_social)) = ?', [mb_strtolower($razonSocial)])
            ->exists()) {
            $validador->errors()->add(
                'form_id_fabricante',
                'Ya existe un fabricante con la misma razón social. Selecciónelo del listado.'
            );
        }
    }

    /**
     * Revisa la clasificación toxicológica cuando fue agregada desde el modal.
     */
    private function validarClasificacionToxicologicaTemporal($validador, Request $request): void
    {
        $valor = $request->input('form_id_clasificacion_toxicologica');

        if (!$request->filled('form_id_clasificacion_toxicologica') || $this->esIdExistente($valor, ClasificacionToxicologica::class)) {
            return;
        }

        if (
            !$this->esTemporal($valor) ||
            !$request->filled('form_clasificacion_toxicologica_temporal_codigo') ||
            !$request->filled('form_clasificacion_toxicologica_temporal_descripcion')
        ) {
            $validador->errors()->add(
                'form_id_clasificacion_toxicologica',
                'Para registrar una clasificación toxicológica debe ingresar el código y la descripción.'
            );
            return;
        }

        $codigo = trim((string) $request->input('form_clasificacion_toxicologica_temporal_codigo'));
        $descripcion = trim((string) $request->input('form_clasificacion_toxicologica_temporal_descripcion', ''));
        $combinacionExiste = ClasificacionToxicologica::withTrashed()
            ->whereRaw('LOWER(TRIM(codigo)) = ?', [mb_strtolower($codigo)])
            ->whereRaw('LOWER(TRIM(descripcion)) = ?', [mb_strtolower($descripcion)])
            ->exists();

        if ($combinacionExiste) {
            $validador->errors()->add(
                'form_id_clasificacion_toxicologica',
                'Ya existe una clasificación toxicológica con el mismo código y descripción. Selecciónela del listado.'
            );
        }
    }

    /**
     * Comprueba que una clasificación química TEMP-* tenga los datos mínimos para poder crearla.
     */
    private function validarClasificacionQuimicaTemporal($validador, Request $request): void
    {
        $valor = $request->input('form_id_clasificacion_quimica');

        if (!$request->filled('form_id_clasificacion_quimica') || $this->esIdExistente($valor, ClasificacionQuimica::class)) {
            return;
        }

        if (!$this->esTemporal($valor) || !$request->filled('form_clasificacion_quimica_temporal_nombre')) {
            $validador->errors()->add(
                'form_id_clasificacion_quimica',
                'Seleccione una clasificación química válida o registre una nueva.'
            );
            return;
        }

        $nombre = trim((string) $request->input('form_clasificacion_quimica_temporal_nombre'));
        $nombreYaRegistrado = ClasificacionQuimica::withTrashed()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($nombre)])
            ->exists();

        if ($nombreYaRegistrado) {
            $validador->errors()->add(
                'form_id_clasificacion_quimica',
                'Ya existe una clasificación química con ese nombre. Selecciónela del listado.'
            );
        }
    }

    /**
     * Comprueba que cada ingrediente agregado sea un registro existente o un TEMP-* completo.
     */
    private function validarIngredientesTemporales($validador, Request $request): void
    {
        $ingredientesAgregados = [];
        $nombresAgregados = [];
        $idsExistentes = collect($request->input('ingredientes_productos', []))
            ->pluck('id_ingrediente')
            ->filter(fn($idIngrediente) => ctype_digit((string) $idIngrediente))
            ->map(fn($idIngrediente) => (int) $idIngrediente)
            ->unique()
            ->values();
        $nombresExistentes = $idsExistentes->isEmpty()
            ? collect()
            : Ingrediente::withTrashed()
                ->whereIn('id', $idsExistentes)
                ->pluck('nombre', 'id');

        foreach ($request->input('ingredientes_productos', []) as $indice => $ingredienteProducto) {
            $idIngrediente = $ingredienteProducto['id_ingrediente'] ?? null;

            if (isset($ingredientesAgregados[(string) $idIngrediente])) {
                $validador->errors()->add(
                    "ingredientes_productos.$indice.id_ingrediente",
                    'El ingrediente está repetido en la composición del producto.'
                );
                continue;
            }

            $ingredientesAgregados[(string) $idIngrediente] = true;

            if (ctype_digit((string) $idIngrediente) && $nombresExistentes->has((int) $idIngrediente)) {
                $nombreNormalizado = mb_strtolower(trim((string) $nombresExistentes->get((int) $idIngrediente)));

                if (isset($nombresAgregados[$nombreNormalizado])) {
                    $validador->errors()->add(
                        "ingredientes_productos.$indice.id_ingrediente",
                        'El ingrediente está repetido en la composición del producto.'
                    );
                    continue;
                }

                $nombresAgregados[$nombreNormalizado] = true;
                continue;
            }

            $nombre = trim((string) ($ingredienteProducto['nombre'] ?? ''));

            if (!$this->esTemporal($idIngrediente) || $nombre === '') {
                $validador->errors()->add(
                    "ingredientes_productos.$indice.id_ingrediente",
                    'El ingrediente seleccionado no es válido. Selecciónelo nuevamente o registre uno nuevo.'
                );
                continue;
            }

            $nombreNormalizado = mb_strtolower($nombre);

            if (isset($nombresAgregados[$nombreNormalizado])) {
                $validador->errors()->add(
                    "ingredientes_productos.$indice.id_ingrediente",
                    'El ingrediente está repetido en la composición del producto.'
                );
                continue;
            }

            $nombresAgregados[$nombreNormalizado] = true;

            if (Ingrediente::withTrashed()
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreNormalizado])
                ->exists()) {
                $validador->errors()->add(
                    "ingredientes_productos.$indice.id_ingrediente",
                    'Ya existe un ingrediente con ese nombre. Selecciónelo del listado.'
                );
            }
        }
    }

    /**
     * Valida las presentaciones y la unidad que corresponde a cada una.
     */
    private function validarPresentaciones($validador, Request $request): void
    {
        foreach ($request->input('presentaciones', []) as $indice => $presentacionDato) {
            $idPresentacionOrigen = $presentacionDato['id_presentacion_origen'] ?? null;
            $idUnidad = $presentacionDato['id_catalogo_unidad'] ?? null;

            if (!$this->catalogoUnidadValido($request, $idUnidad)) {
                $validador->errors()->add(
                    "presentaciones.$indice.id_catalogo_unidad",
                    'Seleccione una unidad válida o registre una nueva.'
                );
            } elseif ($this->nombreUnidadYaRegistrado($request, $idUnidad)) {
                $validador->errors()->add(
                    "presentaciones.$indice.id_catalogo_unidad",
                    'Ya existe una unidad con ese nombre. Selecciónela de la lista.'
                );
            } elseif ($this->abreviaturaUnidadYaRegistrada($request, $idUnidad)) {
                $validador->errors()->add(
                    "presentaciones.$indice.id_catalogo_unidad",
                    'La abreviatura de la unidad ya está registrada.'
                );
            }

            if ($idPresentacionOrigen && !$this->presentacionPerteneceAlProductoSolicitado((int) $idPresentacionOrigen, $request)) {
                $validador->errors()->add(
                    "presentaciones.$indice.id_presentacion_origen",
                    'La presentacion seleccionada no pertenece al importador y producto actual.'
                );
            }
        }
    }

    /**
     * Valida la relación de cada registro con su presentación y su unidad.
     */
    private function validarRegistros($validador, Request $request): void
    {
        $presentaciones = $request->input('presentaciones', []);
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

            $idUnidad = $registroDato['id_catalogo_unidad'] ?? null;

            if ($idUnidad && !$this->catalogoUnidadValido($request, $idUnidad)) {
                $validador->errors()->add(
                    "registros.$indice.id_catalogo_unidad",
                    'Seleccione una unidad válida o registre una nueva.'
                );
            } elseif ($idUnidad && $this->nombreUnidadYaRegistrado($request, $idUnidad)) {
                $validador->errors()->add(
                    "registros.$indice.id_catalogo_unidad",
                    'Ya existe una unidad con ese nombre. Selecciónela de la lista.'
                );
            } elseif ($idUnidad && $this->abreviaturaUnidadYaRegistrada($request, $idUnidad)) {
                $validador->errors()->add(
                    "registros.$indice.id_catalogo_unidad",
                    'La abreviatura de la unidad ya está registrada.'
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

        // Para tipos que emiten un documento por lote, el lote es obligatorio:
        // esta misma fila será la que identifique el certificado a emitir.
        if ($request->filled('form_id_certificado')) {
            $certificado = Certificado::with('tipoCertificado')->find($request->integer('form_id_certificado'));

            if ($certificado?->tipoCertificado?->modo_emision === 'POR_LOTE') {
                foreach ($request->input('registros', []) as $indice => $registroDato) {
                    if (blank($registroDato['numero_lote'] ?? null)) {
                        $validador->errors()->add(
                            "registros.$indice.numero_lote",
                            'El número de lote es obligatorio porque este trámite emite un certificado por lote.'
                        );
                    }
                }
            }
        }
    }

    /**
     * Evita que un formulario manipulado intente actualizar registros de otro producto.
     */
    private function validarIdsEdicionProducto($validador, Request $request, Producto $producto): void
    {
        foreach ($request->input('registros', []) as $indice => $registroDato) {
            $idRegistro = $registroDato['id_registro'] ?? null;

            if (!$idRegistro) {
                continue;
            }

            $perteneceAlProducto = $producto->registros()->whereKey($idRegistro)->exists();

            if (!$perteneceAlProducto) {
                $validador->errors()->add(
                    "registros.$indice.id_registro",
                    'El registro seleccionado no pertenece al producto que está editando.'
                );
            }
        }
    }

    /**
     * Impide relacionar un producto con un trámite que no lo pidió o con otro beneficiario.
     */
    private function validarTramiteAdmiteProducto(int $idCertificado, int $idImportadorPersona): void
    {
        $certificado = Certificado::findOrFail($idCertificado);

        if (!$certificado->requiereEvidencia('PRODUCTO')) {
            throw ValidationException::withMessages([
                'form_id_certificado' => 'Este tramite no tiene producto configurado como requisito.',
            ]);
        }

        if ((int) $certificado->id_persona_beneficiario !== $idImportadorPersona) {
            throw ValidationException::withMessages([
                'form_id_importador_persona' => 'El producto debe registrarse para el beneficiario del tramite.',
            ]);
        }
    }

    /**
     * Una presentación registrada solo puede reutilizarse si pertenece al mismo importador y al mismo producto.
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

    /**
     * Arma una clave para no guardar dos veces la misma presentación dentro del formulario.
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

    /**
     * Arma una clave para detectar registros repetidos dentro de la misma presentación.
     */
    private function claveRegistroProducto(string|int $indicePresentacion, array $registroDato): string
    {
        return implode('|', [
            (string) $indicePresentacion,
            mb_strtolower(trim((string) ($registroDato['codigo_autorizacion'] ?? ''))),
            mb_strtolower(trim((string) ($registroDato['numero_lote'] ?? ''))),
            mb_strtolower(trim((string) ($registroDato['fecha_vigencia'] ?? ''))),
            mb_strtolower(trim((string) ($registroDato['cantidad'] ?? ''))),
            mb_strtolower(trim((string) ($registroDato['id_catalogo_unidad'] ?? ''))),
        ]);
    }

    /**
     * Solo permite volver a direcciones internas del sistema.
     */
    private function urlRetornoSegura(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        return str_starts_with($url, url('/')) || str_starts_with($url, '/')
            ? $url
            : null;
    }

    /**
     * Identifica los valores creados temporalmente desde los modales.
     */
    private function esTemporal($valor): bool
    {
        return is_string($valor) && str_starts_with($valor, 'TEMP-');
    }

    /**
     * Comprueba que el ID recibido exista realmente en el modelo indicado.
     */
    private function esIdExistente($valor, string $modelo): bool
    {
        return ctype_digit((string) $valor) && $modelo::query()->whereKey($valor)->exists();
    }

    /**
     * Devuelve el fabricante seleccionado o crea el que llegó desde el modal.
     */
    private function resolverFabricante(Request $request, string $valor): int
    {
        if (ctype_digit($valor)) {
            return (int) $valor;
        }

        $nombre = trim((string) $request->input('form_fabricante_temporal_nombre'));
        $fabricante = Fabricante::withTrashed()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($nombre)])
            ->first();

        if (!$fabricante) {
            $fabricante = Fabricante::create([
                'nombre' => $nombre,
                'razon_social' => filled($request->input('form_fabricante_temporal_razon_social'))
                    ? trim((string) $request->input('form_fabricante_temporal_razon_social'))
                    : null,
                'descripcion' => filled($request->input('form_fabricante_temporal_descripcion'))
                    ? trim((string) $request->input('form_fabricante_temporal_descripcion'))
                    : null,
                'estado' => 'ACTIVO',
            ]);
        } elseif ($fabricante->trashed()) {
            $fabricante->restore();
            $fabricante->estado = 'ACTIVO';
            $fabricante->save();
        }

        return $fabricante->id;
    }

    /**
     * Devuelve la clasificación toxicológica seleccionada o registra la temporal.
     */
    private function resolverClasificacionToxicologica(Request $request, string $valor): int
    {
        if (ctype_digit($valor)) {
            return (int) $valor;
        }

        $codigo = trim((string) $request->input('form_clasificacion_toxicologica_temporal_codigo'));
        $descripcion = trim((string) $request->input('form_clasificacion_toxicologica_temporal_descripcion'));

        $clasificacion = ClasificacionToxicologica::withTrashed()
            ->whereRaw('LOWER(TRIM(codigo)) = ?', [mb_strtolower($codigo)])
            ->whereRaw('LOWER(TRIM(descripcion)) = ?', [mb_strtolower($descripcion)])
            ->first();

        if (!$clasificacion) {
            $clasificacion = ClasificacionToxicologica::create([
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'estado' => 'ACTIVO',
            ]);
        } elseif ($clasificacion->trashed()) {
            $clasificacion->restore();
            $clasificacion->estado = 'ACTIVO';
            $clasificacion->save();
        }

        return $clasificacion->id;
    }

    /**
     * Devuelve la clasificación química seleccionada o registra la temporal.
     */
    private function resolverClasificacionQuimica(Request $request, $valor): ?int
    {
        if (blank($valor)) {
            return null;
        }

        if (ctype_digit((string) $valor)) {
            return (int) $valor;
        }

        $nombre = trim((string) $request->input('form_clasificacion_quimica_temporal_nombre'));
        $clasificacion = ClasificacionQuimica::withTrashed()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($nombre)])
            ->first();

        if (!$clasificacion) {
            $clasificacion = ClasificacionQuimica::create([
                'nombre' => $nombre,
                'descripcion' => $request->input('form_clasificacion_quimica_temporal_descripcion'),
                'estado' => 'ACTIVO',
            ]);
        } elseif ($clasificacion->trashed()) {
            $clasificacion->restore();
            $clasificacion->estado = 'ACTIVO';
            $clasificacion->save();
        }

        return $clasificacion->id;
    }

    /**
     * Obtiene los datos de una unidad TEMP-* creada desde el formulario.
     * La unidad puede haberse creado desde Presentación o desde Registro; el catálogo es el mismo,
     * pero cada select conserva de forma independiente la unidad que tiene seleccionada.
     */
    private function datosUnidadTemporal(Request $request, $valor): ?array
    {
        if (!$this->esTemporal((string) $valor)) {
            return null;
        }

        // Si la vista envía varias unidades temporales, se busca primero por su código TEMP-*.
        $unidad = $request->input("unidades_temporales.$valor");

        if (is_array($unidad)) {
            $nombre = trim((string) ($unidad['nombre'] ?? ''));
            $abreviatura = trim((string) ($unidad['abreviatura'] ?? ''));

            if ($nombre !== '') {
                return [
                    'nombre' => $nombre,
                    'abreviatura' => $abreviatura,
                ];
            }
        }

        // La vista actual guarda por separado la unidad creada desde cada select.
        $prefijos = [
            'form_presentacion_unidad_temporal',
            'form_registro_unidad_temporal',
        ];

        foreach ($prefijos as $prefijo) {
            $idTemporal = $request->input("{$prefijo}_id");

            if ((string) $idTemporal !== (string) $valor) {
                continue;
            }

            $nombre = trim((string) $request->input("{$prefijo}_nombre"));
            $abreviatura = trim((string) $request->input("{$prefijo}_abreviatura"));

            if ($nombre === '') {
                return null;
            }

            return [
                'nombre' => $nombre,
                'abreviatura' => $abreviatura,
            ];
        }

        // Se mantiene este caso para no romper formularios anteriores que usaban un solo TEMP-*.
        if ((string) $request->input('form_unidad_temporal_id') === (string) $valor) {
            $nombre = trim((string) $request->input('form_unidad_temporal_nombre'));
            $abreviatura = trim((string) $request->input('form_unidad_temporal_abreviatura'));

            if ($nombre !== '') {
                return [
                    'nombre' => $nombre,
                    'abreviatura' => $abreviatura,
                ];
            }
        }

        return null;
    }

    /**
     * Acepta una unidad existente del catálogo o una unidad TEMP-* válida.
     */
    private function catalogoUnidadValido(Request $request, $valor): bool
    {
        if (ctype_digit((string) $valor)) {
            return CatalogoMedida::query()
                ->whereKey($valor)
                ->where('tipo', 'unidad de medida')
                ->where('estado', 'ACTIVO')
                ->exists();
        }

        return $this->datosUnidadTemporal($request, $valor) !== null;
    }

    /**
     * Evita usar la misma abreviatura para unidades con nombres distintos.
     */
    private function abreviaturaUnidadYaRegistrada(Request $request, $valor): bool
    {
        $unidad = $this->datosUnidadTemporal($request, $valor);

        if (!$unidad || $unidad['abreviatura'] === '') {
            return false;
        }

        return CatalogoMedida::withTrashed()
            ->where('tipo', 'unidad de medida')
            ->whereRaw('LOWER(TRIM(abreviatura)) = ?', [mb_strtolower($unidad['abreviatura'])])
            ->whereRaw('LOWER(TRIM(nombre)) != ?', [mb_strtolower($unidad['nombre'])])
            ->exists();
    }

    /**
     * Detecta una unidad temporal cuyo nombre ya existe en el catálogo.
     * La abreviatura puede variar, pero el nombre sigue identificando una sola unidad.
     */
    private function nombreUnidadYaRegistrado(Request $request, $valor): bool
    {
        $unidad = $this->datosUnidadTemporal($request, $valor);

        if (!$unidad) {
            return false;
        }

        return CatalogoMedida::withTrashed()
            ->where('tipo', 'unidad de medida')
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($unidad['nombre'])])
            ->exists();
    }

    /**
     * Devuelve el ID real de una unidad. Si viene como TEMP-*, primero la registra en catalogos_medidas.
     */
    private function resolverCatalogoUnidad(Request $request, $valor): int
    {
        if (ctype_digit((string) $valor)) {
            return (int) $valor;
        }

        $datos = $this->datosUnidadTemporal($request, $valor);

        if (!$datos) {
            throw ValidationException::withMessages([
                'unidad' => 'La unidad seleccionada no es válida.',
            ]);
        }

        $unidad = CatalogoMedida::withTrashed()
            ->where('tipo', 'unidad de medida')
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($datos['nombre'])])
            ->first();

        if (!$unidad) {
            $unidad = CatalogoMedida::create([
                'nombre' => $datos['nombre'],
                'tipo' => 'unidad de medida',
                'abreviatura' => $datos['abreviatura'] !== '' ? $datos['abreviatura'] : null,
                'estado' => 'ACTIVO',
            ]);
        } elseif ($unidad->trashed()) {
            $unidad->restore();
            $unidad->estado = 'ACTIVO';
            $unidad->save();
        }

        return $unidad->id;
    }

    /**
     * Devuelve el ingrediente seleccionado o crea el que fue agregado temporalmente.
     */
    private function resolverIngrediente(array $ingredienteProducto): int
    {
        $valor = $ingredienteProducto['id_ingrediente'];

        if (ctype_digit((string) $valor)) {
            return (int) $valor;
        }

        $nombre = trim((string) $ingredienteProducto['nombre']);
        $ingrediente = Ingrediente::withTrashed()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($nombre)])
            ->first();

        if (!$ingrediente) {
            $ingrediente = Ingrediente::create([
                'nombre' => $nombre,
                'estado' => 'ACTIVO',
            ]);
        } elseif ($ingrediente->trashed()) {
            $ingrediente->restore();
            $ingrediente->estado = 'ACTIVO';
            $ingrediente->save();
        }

        return $ingrediente->id;
    }
}
