<?php

namespace App\Http\Controllers;

use App\Models\TipoCertificado;
use App\Models\Requisito;
use App\Models\RequisitoTipoCertificado;
use App\Models\TipoEvidencia;
use App\Models\DependenciaRequisito;
use App\Models\Area;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TipoCertificadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('tipos_certificados.index');
    }

    /**
     * Registra únicamente los datos básicos enviados desde el modal del listado.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tipos_certificados', 'nombre'),
            ],
            'form_estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ], [
            'form_nombre.unique' => 'El nombre del tipo de certificado ya está registrado.',
        ], [
            'form_nombre' => 'nombre del tipo de certificado',
            'form_estado' => 'estado',
        ]);

        try {
            TipoCertificado::create([
                'nombre' => $this->mayuscula($datos['form_nombre']),
                'estado' => $datos['form_estado'],
            ]);

            session()->flash('swal', [
                'title' => 'Tipo de certificado creado',
                'text' => 'Los datos básicos se registraron correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('tipos_certificados_index');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'No se pudo registrar el tipo de certificado.')
                ->withInput()
                ->with('modal_tipo_certificado', 'crear');
        }
    }

    /**
     * Modifica nombre y estado sin tocar la configuración técnica del certificado.
     */
    public function actualizarDatosBasicos(Request $solicitud, TipoCertificado $tipoCertificado)
    {
        $datos = $solicitud->validate([
            'form_id_tipo_certificado' => [
                'required',
                'integer',
                Rule::in([$tipoCertificado->id]),
            ],
            'form_nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tipos_certificados', 'nombre')->ignore($tipoCertificado->id),
            ],
            'form_estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
        ], [
            'form_nombre.unique' => 'El nombre del tipo de certificado ya está registrado.',
        ], [
            'form_nombre' => 'nombre del tipo de certificado',
            'form_estado' => 'estado',
        ]);

        $tipoCertificado->update([
            'nombre' => $this->mayuscula($datos['form_nombre']),
            'estado' => $datos['form_estado'],
        ]);

        session()->flash('swal', [
            'title' => 'Datos actualizados',
            'text' => 'El nombre y el estado se actualizaron correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('tipos_certificados_index');
    }



    // Los nombres del catálogo se guardan con un formato uniforme.
    private function mayuscula(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }

        return mb_strtoupper(trim($texto), 'UTF-8');
    }

    // METODO PARA REGISTRAR REQUISITOS DE UN TIPO DE CERTIFICADO
    // Se crea requisitos nuevos si hace falta y llena la tabla pivote.
    private function guardarRequisitosDelTipoCertificado(TipoCertificado $tipoCertificado, array $requisitosAsignados): void
    {
        $requisitosProcesados = [];
        $codigosEvidencias = TipoEvidencia::query()
            ->whereIn('id', collect($requisitosAsignados)->pluck('id_tipo_evidencia')->filter()->unique())
            ->pluck('codigo', 'id');

        foreach ($requisitosAsignados as $item) {
            $esNuevo = (bool) ($item['nuevo'] ?? false);
            $descripcion = trim($item['descripcion'] ?? '');
            $estado = $item['estado'] ?? 'ACTIVO';
            $idTipoEvidencia = filled($item['id_tipo_evidencia'] ?? null)
                ? (int) $item['id_tipo_evidencia']
                : null;
            $idTipoCertificadoRequerido = filled($item['id_tipo_certificado_requerido'] ?? null)
                ? (int) $item['id_tipo_certificado_requerido']
                : null;
            $codigoTipoEvidencia = $idTipoEvidencia ? $codigosEvidencias->get($idTipoEvidencia) : null;
            $calificacionMinima = $codigoTipoEvidencia === 'CALIFICACION'
                ? (float) $item['calificacion_minima_aprobacion']
                : null;

            // Si el usuario creo un requisito nuevo, primero se guarda en requisitos.
            if ($esNuevo) {
                if ($descripcion === '') {
                    continue;
                }

                $requisito = Requisito::firstOrCreate(
                    ['descripcion' => $descripcion],
                    ['estado' => $estado]
                );
            } 
            else {
                $idRequisito = $item['id_requisito'] ?? null;

                if (!$idRequisito) {
                    continue;
                }

                $requisito = Requisito::find($idRequisito);
            }

            if (!$requisito || in_array($requisito->id, $requisitosProcesados, true)) {
                continue;
            }

            // Esta fila representa la relacion requisito + tipo certificado.
            $requisitoTipoCertificado = RequisitoTipoCertificado::create([
                'id_requisito' => $requisito->id,
                'id_tipo_certificado' => $tipoCertificado->id,
                'id_tipo_evidencia' => $idTipoEvidencia,
                'orden' => count($requisitosProcesados) + 1,
                'calificacion_minima_aprobacion' => $calificacionMinima,
                'estado' => $estado,
            ]);

            if ($idTipoCertificadoRequerido) {
                DependenciaRequisito::create([
                    'id_requisito_tipo_certificado' => $requisitoTipoCertificado->id,
                    'id_tipo_certificado_requerido' => $idTipoCertificadoRequerido,
                    'estado' => 'ACTIVO',
                ]);
            }

            $requisitosProcesados[] = $requisito->id;
        }
    }



    // METODO PARA VALIDAR DEPENDENCIAS DE CERTIFICADOS PREVIOS
    // Si el requisito pide un certificado vigente, tambien debe indicar que certificado se va a verificar.
    private function validarDependenciasDeCertificados(array $requisitosAsignados): void
    {
        $idsTiposEvidencias = collect($requisitosAsignados)
            ->pluck('id_tipo_evidencia')
            ->filter()
            ->unique()
            ->values();

        $tiposEvidencias = TipoEvidencia::query()
            ->whereIn('id', $idsTiposEvidencias)
            ->pluck('codigo', 'id');

        foreach ($requisitosAsignados as $item) {
            $idTipoEvidencia = $item['id_tipo_evidencia'] ?? null;
            $codigoTipoEvidencia = $idTipoEvidencia ? $tiposEvidencias->get((int) $idTipoEvidencia) : null;
            $idTipoCertificadoRequerido = $item['id_tipo_certificado_requerido'] ?? null;

            if (
                $codigoTipoEvidencia === 'CERTIFICADO' &&
                blank($idTipoCertificadoRequerido)
            ) {
                throw ValidationException::withMessages([
                    'requisitos_asignados' => 'Seleccione el certificado requerido para los requisitos que se cumplen con certificado vigente.',
                ]);
            }

            if (filled($idTipoCertificadoRequerido) && blank($idTipoEvidencia)) {
                throw ValidationException::withMessages([
                    'requisitos_asignados' => 'Seleccione el tipo de evidencia para los requisitos que dependen de un certificado previo.',
                ]);
            }

            if ($codigoTipoEvidencia === 'CALIFICACION' && blank($item['calificacion_minima_aprobacion'] ?? null)) {
                throw ValidationException::withMessages([
                    'requisitos_asignados' => 'Ingrese la calificación mínima de aprobación para los requisitos de tipo CALIFICACIÓN.',
                ]);
            }
        }
    }



    // METODO PARA EDITAR LOS REQUISITOS ASOCIADOS
    // Borra las relaciones actuales y vuelve a crear solo las que quedaron en pantalla.
    private function sincronizarTiposdeRequisitos(TipoCertificado $tipoCertificado, array $requisitosAsignados): void
    {
        // Elimina cada pivote como modelo para que Auditable registre id_usuario_eliminacion.
        $this->eliminarRequisitosDelTipoCertificado($tipoCertificado);

        // Reutiliza la misma logica del registro para requisitos existentes y nuevos.
        $this->guardarRequisitosDelTipoCertificado($tipoCertificado, $requisitosAsignados);
    }



    // METODO PARA ELIMINAR LOS REQUISITOS ASOCIADOS
    // Se usa foreach para disparar SoftDeletes y Auditable en cada fila pivote.
    private function eliminarRequisitosDelTipoCertificado(TipoCertificado $tipoCertificado): void
    {
        $tipoCertificado->tipoCertificadoRequisitos()
            ->get()
            ->each(function (RequisitoTipoCertificado $asignacion) {
                // Primero se eliminan las dependencias del requisito para no dejar certificados previos vigentes.
                $asignacion->dependenciasRequisitos()
                    ->get()
                    ->each(function (DependenciaRequisito $dependencia) {
                        $dependencia->update(['estado' => 'INACTIVO']);
                        $dependencia->delete();
                    });

                // Antes de eliminar se marca inactivo para que la base muestre que ya no esta vigente.
                $asignacion->update(['estado' => 'INACTIVO']);

                // Luego se elimina logicamente para llenar deleted_at e id_usuario_eliminacion.
                $asignacion->delete();
            });
    }



    /**
     * Display the specified resource.
     */
    public function show(TipoCertificado $tipoCertificado)
    {
        // Carga el area responsable para mostrarla junto al certificado base.
        $tipoCertificado->load('area');

        // Arma el arbol completo de requisitos.
        // Si un requisito pide otro certificado, tambien carga los requisitos internos de ese certificado.
        $arbolRequisitos = $this->armarArbolRequisitosTipoCertificado($tipoCertificado);

        return view('tipos_certificados.show', compact(
            'tipoCertificado',
            'arbolRequisitos'
        ));
    }



    // METODO PARA ARMAR EL ARBOL DE REQUISITOS DE UN TIPO DE CERTIFICADO
    // Devuelve un arreglo listo para la vista show:
    // - Requisitos normales quedan como tipo "requisito".
    // - Certificados previos quedan como tipo "certificado".
    // - Si el certificado previo tiene sus propios requisitos, se agregan en "hijos".
    // - Evita ciclos para que un certificado no se llame a si mismo de forma infinita.
    private function armarArbolRequisitosTipoCertificado(TipoCertificado $tipoCertificado, array $certificadosVisitados = []): array
    {
        // Guarda los certificados ya recorridos para evitar ciclos de dependencias.
        $certificadosVisitados[] = $tipoCertificado->id;

        // Carga las relaciones necesarias para no consultar la base de datos dentro de cada vista.
        $tipoCertificado->loadMissing([
            'area',
            'tipoCertificadoRequisitos.requisito',
            'tipoCertificadoRequisitos.tipoEvidencia',
            'tipoCertificadoRequisitos.dependenciasRequisitos.tipoCertificadoRequerido.area',
        ]);

        // Recorre solo requisitos activos asociados al tipo de certificado actual.
        $requisitos = $tipoCertificado->tipoCertificadoRequisitos
            ->where('estado', 'ACTIVO')
            ->values()
            ->map(function (RequisitoTipoCertificado $asignacion) use ($certificadosVisitados) {
                // Si existe dependencia activa, significa que este requisito pide un certificado previo.
                $dependencia = $asignacion->dependenciasRequisitos
                    ->where('estado', 'ACTIVO')
                    ->first();

                $tipoCertificadoRequerido = $dependencia?->tipoCertificadoRequerido;

                // Verifica si el certificado previo ya fue recorrido para no repetirlo indefinidamente.
                $tieneCiclo = $tipoCertificadoRequerido
                    ? in_array($tipoCertificadoRequerido->id, $certificadosVisitados, true)
                    : false;

                // Nodo base que la vista usa para pintar una fila del arbol.
                $nodo = [
                    'nombre' => $tipoCertificadoRequerido?->nombre
                        ?: ($asignacion->requisito?->descripcion ?? 'Sin requisito'),
                    'tipo' => $tipoCertificadoRequerido ? 'certificado' : 'requisito',
                    'evidencia_codigo' => $asignacion->tipoEvidencia?->codigo ?? 'SIN_EVIDENCIA',
                    'evidencia_nombre' => $asignacion->tipoEvidencia?->nombre ?? 'Sin evidencia',
                    'calificacion_minima_aprobacion' => $asignacion->calificacion_minima_aprobacion,
                    'estado' => $asignacion->estado ?? 'ACTIVO',
                    'area' => $tipoCertificadoRequerido?->area?->nombre,
                    'hijos' => [],
                    'tiene_ciclo' => $tieneCiclo,
                ];

                // Si el requisito pide otro certificado, agrega como hijos los requisitos de ese certificado.
                if ($tipoCertificadoRequerido && !$tieneCiclo) {
                    $arbolHijo = $this->armarArbolRequisitosTipoCertificado(
                        $tipoCertificadoRequerido,
                        $certificadosVisitados
                    );

                    $nodo['hijos'] = $arbolHijo['requisitos'];
                }

                return $nodo;
            })
            ->all();

        return [
            'nombre' => $tipoCertificado->nombre,
            'area' => $tipoCertificado->area?->nombre,
            'limite_descargas' => $tipoCertificado->limite_descargas,
            'estado' => $tipoCertificado->estado,
            'requisitos' => $requisitos,
        ];
    }

    /**
     * Abre la configuración técnica sin permitir cambios en nombre o estado.
     */
    public function configurar(TipoCertificado $tipoCertificado)
    {
        // Carga los requisitos ya asignados para pintar la relacion actual en la vista edit.
        $tipoCertificado->load(
            'tipoCertificadoRequisitos.requisito',
            'tipoCertificadoRequisitos.dependenciasRequisitos.tipoCertificadoRequerido'
        );

        // Solo mostramos requisitos activos
        $requisitos = Requisito::query()
            ->where('estado', 'ACTIVO')
            ->orderBy('descripcion')
            ->get();

        // Tipos de evidencias activos para editar como se cumplira cada requisito.
        $tiposEvidencias = TipoEvidencia::query()
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        // No permite que un tipo de certificado se pida a si mismo como certificado previo.
        $tiposCertificadosRequeridos = TipoCertificado::query()
            ->with([
                'tipoCertificadoRequisitos.requisito',
                'tipoCertificadoRequisitos.tipoEvidencia',
                'tipoCertificadoRequisitos.dependenciasRequisitos.tipoCertificadoRequerido',
            ])
            ->where('estado', 'ACTIVO')
            ->where('id', '!=', $tipoCertificado->id)
            ->orderBy('nombre')
            ->get();

        // Areas activas para actualizar la unidad responsable del tipo de certificado.
        $areas = Area::query()
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get();

        // Funcionarios receptores disponibles, agrupados por el area de sus cargos activos.
        $funcionariosReceptores = Funcionario::query()
            ->with([
                'usuario',
                'cargos' => fn ($query) => $query
                    ->where('cargos.estado', 1)
                    ->whereNull('funcionarios_cargos.deleted_at')
                    ->orderBy('cargos.nombre'),
            ])
            ->whereHas('usuario', fn ($query) => $query->where('estado', 1))
            ->whereHas('cargos', fn ($query) => $query
                ->where('cargos.estado', 1)
                ->whereNull('funcionarios_cargos.deleted_at'))
            ->get()
            ->flatMap(function (Funcionario $funcionario) {
                $nombre = collect([
                    $funcionario->nombres,
                    $funcionario->apellido_paterno,
                    $funcionario->apellido_materno,
                ])->filter()->implode(' ');

                return $funcionario->cargos
                    ->groupBy('id_area')
                    ->map(fn ($cargos, $idArea) => [
                        'id' => (string) $funcionario->id,
                        'id_area' => (string) $idArea,
                        'nombre' => $nombre,
                        'cargo' => $cargos->pluck('nombre')->filter()->unique()->implode(', '),
                    ]);
            })
            ->sortBy(fn ($item) => $item['nombre'].' '.$item['cargo'])
            ->values();

        // Estructura lista para que el JavaScript reutilice la misma tabla dinamica del create.
        $requisitosAsignados = $tipoCertificado->tipoCertificadoRequisitos
            ->map(function ($asignacion) {
                $dependencia = $asignacion->dependenciasRequisitos->first();

                return [
                    'id' => (string) $asignacion->id_requisito,
                    'descripcion' => $asignacion->requisito?->descripcion ?? 'Requisito no encontrado',
                    'id_tipo_evidencia' => $asignacion->id_tipo_evidencia ? (string) $asignacion->id_tipo_evidencia : '',
                    'calificacion_minima_aprobacion' => $asignacion->calificacion_minima_aprobacion,
                    'orden' => $asignacion->orden,
                    'id_tipo_certificado_requerido' => $dependencia?->id_tipo_certificado_requerido
                        ? (string) $dependencia->id_tipo_certificado_requerido
                        : '',
                    'nombre_certificado_requerido' => $dependencia?->tipoCertificadoRequerido?->nombre ?? '',
                    'estado' => $asignacion->estado ?? 'ACTIVO',
                    'es_certificado_previo' => filled($dependencia?->id_tipo_certificado_requerido),
                    'nuevo' => false,
                ];
            })
            ->values();

        return view('tipos_certificados.edit', compact(
            'tipoCertificado',
            'requisitos',
            'tiposEvidencias',
            'tiposCertificadosRequeridos',
            'areas',
            'funcionariosReceptores',
            'requisitosAsignados'
        ));
    }

    /**
     * Guarda los datos operativos, costo, requisitos y evidencias del tipo de certificado.
     */
    public function actualizarConfiguracion(Request $solicitud, TipoCertificado $tipoCertificado)
    {
        // Admite la coma decimal usada habitualmente al registrar importes en bolivianos.
        if ($solicitud->filled('form_costo')) {
            $solicitud->merge([
                'form_costo' => str_replace(',', '.', trim((string) $solicitud->input('form_costo'))),
            ]);
        }

        $datos = $solicitud->validate([
            'form_id_area' => ['required', 'integer', 'exists:areas,id'],
            'form_id_funcionario_receptor_inicial' => ['required', 'integer', 'exists:funcionarios,id'],
            'form_limite_descargas' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'form_costo' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'form_modo_emision' => ['required', Rule::in(array_keys(TipoCertificado::MODOS_EMISION))],
            'form_vigencia_unidad' => ['required', Rule::in(array_keys(TipoCertificado::UNIDADES_VIGENCIA))],
            'form_vigencia_cantidad' => [
                'nullable',
                'integer',
                'min:1',
                'max:9999',
                'required_unless:form_vigencia_unidad,INDEFINIDA',
                'prohibited_if:form_vigencia_unidad,INDEFINIDA',
            ],
            'form_prefijo_numero_certificado' => ['nullable', 'string', 'max:100'],
            'form_siguiente_correlativo' => ['required', 'integer', 'min:1', 'max:999999999'],
            'form_qr_habilitado' => ['sometimes', 'boolean'],
            'form_qr_titulo' => ['nullable', 'string', 'max:120'],
            'form_qr_campos' => ['nullable', 'array', 'max:30', 'required_if:form_qr_habilitado,1'],
            'form_qr_campos.*.campo' => ['required', 'distinct', Rule::in(array_keys(TipoCertificado::CAMPOS_QR))],
            'form_qr_campos.*.etiqueta' => ['required', 'string', 'max:100'],
            'requisitos_asignados' => ['nullable', 'array'],
            'requisitos_asignados.*.id_requisito' => ['nullable', 'integer', 'exists:requisitos,id'],
            'requisitos_asignados.*.id_tipo_evidencia' => ['required', 'integer', 'exists:tipos_evidencias,id'],
            'requisitos_asignados.*.calificacion_minima_aprobacion' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'requisitos_asignados.*.id_tipo_certificado_requerido' => ['nullable', 'integer', 'exists:tipos_certificados,id'],
            'requisitos_asignados.*.descripcion' => ['nullable', 'string'],
            'requisitos_asignados.*.estado' => ['nullable', 'string', 'max:50'],
            'requisitos_asignados.*.nuevo' => ['nullable', 'boolean'],
        ], [
            'form_qr_campos.required_if' => 'Agregue al menos un dato público para habilitar la verificación QR.',
            'form_qr_campos.*.campo.distinct' => 'No repita el mismo campo en los datos del QR.',
            'form_qr_campos.*.etiqueta.required' => 'Escriba una etiqueta para cada dato público del QR.',
            'requisitos_asignados.*.id_tipo_evidencia.required' => 'Seleccione el tipo de evidencia para este requisito.',
            'requisitos_asignados.*.id_tipo_evidencia.exists' => 'Seleccione un tipo de evidencia válido para este requisito.',
            'form_vigencia_cantidad.required_unless' => 'Ingrese la cantidad de la vigencia.',
            'form_vigencia_cantidad.prohibited_if' => 'No ingrese una cantidad cuando la vigencia es indefinida.',
        ], [
            'form_id_area' => 'area responsable',
            'form_id_funcionario_receptor_inicial' => 'funcionario receptor inicial',
            'form_limite_descargas' => 'límite de descargas',
            'form_costo' => 'costo',
            'form_modo_emision' => 'forma de emisión',
            'form_vigencia_unidad' => 'unidad de vigencia',
            'form_vigencia_cantidad' => 'cantidad de vigencia',
            'form_prefijo_numero_certificado' => 'prefijo del número de certificado',
            'form_siguiente_correlativo' => 'siguiente correlativo',
        ]);

        $funcionarioPerteneceAlArea = Funcionario::query()
            ->whereKey($datos['form_id_funcionario_receptor_inicial'])
            ->whereHas('usuario', fn ($query) => $query->where('estado', 1))
            ->whereHas('cargos', fn ($query) => $query
                ->where('cargos.id_area', $datos['form_id_area'])
                ->where('cargos.estado', 1)
                ->whereNull('funcionarios_cargos.deleted_at'))
            ->exists();

        if (! $funcionarioPerteneceAlArea) {
            throw ValidationException::withMessages([
                'form_id_funcionario_receptor_inicial' => 'Seleccione un funcionario activo que pertenezca al área indicada.',
            ]);
        }

        $this->validarDependenciasDeCertificados($solicitud->input('requisitos_asignados', []));

        try {
            DB::beginTransaction();

            // Nombre y estado se administran desde el modal y no forman parte de esta operación.
            $configuracionQr = $solicitud->has('form_qr_habilitado') ? [
                'configuracion_qr' => [
                    'habilitado' => $solicitud->boolean('form_qr_habilitado'),
                    'titulo' => trim($datos['form_qr_titulo'] ?? '') ?: 'Verificación de certificado',
                    'campos' => array_values(array_map(fn ($fila) => [
                        'campo' => $fila['campo'],
                        'etiqueta' => trim($fila['etiqueta']),
                    ], $datos['form_qr_campos'] ?? [])),
                ],
            ] : [];

            $tipoCertificado->update([
                ...$configuracionQr,
                'id_area' => $datos['form_id_area'],
                'id_funcionario_receptor_inicial' => $datos['form_id_funcionario_receptor_inicial'],
                'limite_descargas' => $datos['form_limite_descargas'] ?? null,
                'costo' => $datos['form_costo'] ?? null,
                'modo_emision' => $datos['form_modo_emision'],
                'vigencia' => TipoCertificado::valorVigencia(
                    $datos['form_vigencia_cantidad'] ?? null,
                    $datos['form_vigencia_unidad']
                ),
                'prefijo_numero_certificado' => $datos['form_prefijo_numero_certificado'] ?? null,
                'siguiente_correlativo' => $datos['form_siguiente_correlativo'],
            ]);

            // Reemplaza los requisitos asociados por la nueva seleccion del usuario.
            $this->sincronizarTiposdeRequisitos(
                $tipoCertificado,
                $solicitud->input('requisitos_asignados', [])
            );

            DB::commit();
            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'La configuración del tipo de certificado se actualizó correctamente.',
                'icon'  => 'success',
            ]);
            return redirect()->route('tipos_certificados_index');

        } 
        catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'No se pudo actualizar la configuración del tipo de certificado.')
                ->withInput()
                ->with('tipo_certificado_configurar', $tipoCertificado->id);
        }
    }
}
