<?php

namespace App\Http\Controllers;

use App\Models\TipoCertificado;
use App\Models\Requisito;
use App\Models\RequisitoTipoCertificado;
use App\Models\TipoEvidencia;
use App\Models\DependenciaRequisito;
use App\Models\Area;
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
        $tiposCertificados = TipoCertificado::all();
        return view('tipos_certificados.index', compact('tiposCertificados'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Solo mostramos requisitos activos para asociarlos al tipo de certificado.
        $requisitos = Requisito::query()
            ->where('estado', 'ACTIVO')
            ->orderBy('descripcion')
            ->get();

        // Tipos de evidencias activos para definir como se cumplira cada requisito.
        $tiposEvidencias = TipoEvidencia::query()
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        // Tipos de certificados activos que pueden ser exigidos como certificados previos.
        $tiposCertificadosRequeridos = TipoCertificado::query()
            ->with([
                'tipoCertificadoRequisitos.requisito',
                'tipoCertificadoRequisitos.tipoEvidencia',
                'tipoCertificadoRequisitos.dependenciasRequisitos.tipoCertificadoRequerido',
            ])
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        // Areas activas para definir que unidad atendera este tipo de certificado.
        $areas = Area::query()
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get();

        return view('tipos_certificados.create', compact(
            'requisitos',
            'tiposEvidencias',
            'tiposCertificadosRequeridos',
            'areas'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $solicitud)
    {
        $datos = $solicitud->validate([
            'form_nombre' => ['required','string','max:255',
                Rule::unique('tipos_certificados', 'nombre'),],
            'form_id_area' => ['required', 'integer', 'exists:areas,id'],
            'form_estado' => 'string|max:50',
            'requisitos_asignados' => ['nullable', 'array'],
            'requisitos_asignados.*.id_requisito' => ['nullable', 'integer', 'exists:requisitos,id'],
            'requisitos_asignados.*.id_tipo_evidencia' => ['nullable', 'integer', 'exists:tipos_evidencias,id'],
            'requisitos_asignados.*.id_tipo_certificado_requerido' => ['nullable', 'integer', 'exists:tipos_certificados,id'],
            'requisitos_asignados.*.descripcion' => ['nullable', 'string'],
            'requisitos_asignados.*.estado' => ['nullable', 'string', 'max:50'],
            'requisitos_asignados.*.nuevo' => ['nullable', 'boolean'],
        ], [
            'form_nombre.unique' => 'El nombre del tipo de certificado ya está registrado.',
        ], [
            'form_nombre' => 'nombre del tipo de certificado',
            'form_id_area' => 'area responsable',
            'form_estado' => 'estado',
        ]);

        $this->validarDependenciasDeCertificados($solicitud->input('requisitos_asignados', []));

        try {
            DB::beginTransaction();

            // Primero se registra el tipo de certificado
            $tipoCertificado = TipoCertificado::create([
                'nombre' => $this->mayuscula($datos['form_nombre']),
                'id_area' => $datos['form_id_area'],
                'estado' => $datos['form_estado'],
            ]);

            // Luego se registran los requisitos nuevos y la relacion en requisitos_tipos_certificados.
            $this->guardarRequisitosDelTipoCertificado(
                $tipoCertificado,
                $solicitud->input('requisitos_asignados', [])
            );

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El tipo de certificado se registro correctamente.',
                'icon'  => 'success',
            ]);

            return redirect()->route('tipos_certificados_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el tipo de certificado.')
                ->withInput()
                ->with('modal_tipo_certificado', 'crear');
        }
    }



    // FUNCION PARA CONVERTIR TEXTO A MAYUSCULAS
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
            'estado' => $tipoCertificado->estado,
            'requisitos' => $requisitos,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoCertificado $tipoCertificado)
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

        // Estructura lista para que el JavaScript reutilice la misma tabla dinamica del create.
        $requisitosAsignados = $tipoCertificado->tipoCertificadoRequisitos
            ->map(function ($asignacion) {
                $dependencia = $asignacion->dependenciasRequisitos->first();

                return [
                    'id' => (string) $asignacion->id_requisito,
                    'descripcion' => $asignacion->requisito?->descripcion ?? 'Requisito no encontrado',
                    'id_tipo_evidencia' => $asignacion->id_tipo_evidencia ? (string) $asignacion->id_tipo_evidencia : '',
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
            'requisitosAsignados'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $solicitud, TipoCertificado $tipoCertificado)
    {
        $datos = $solicitud->validate(['form_nombre' => ['required','string','max:255',
                Rule::unique('tipos_certificados', 'nombre')
                    ->ignore($tipoCertificado->id),
            ],
            'form_id_area' => ['required', 'integer', 'exists:areas,id'],
            'form_estado' => 'string|max:50',
            'requisitos_asignados' => ['nullable', 'array'],
            'requisitos_asignados.*.id_requisito' => ['nullable', 'integer', 'exists:requisitos,id'],
            'requisitos_asignados.*.id_tipo_evidencia' => ['nullable', 'integer', 'exists:tipos_evidencias,id'],
            'requisitos_asignados.*.id_tipo_certificado_requerido' => ['nullable', 'integer', 'exists:tipos_certificados,id'],
            'requisitos_asignados.*.descripcion' => ['nullable', 'string'],
            'requisitos_asignados.*.estado' => ['nullable', 'string', 'max:50'],
            'requisitos_asignados.*.nuevo' => ['nullable', 'boolean'],
        ], [
            'form_nombre.unique' => 'El nombre del tipo de certificado ya está registrado.',
        ], [
            'form_nombre' => 'nombre del tipo de certificado',
            'form_id_area' => 'area responsable',
            'form_estado' => 'estado',
        ]);

        $this->validarDependenciasDeCertificados($solicitud->input('requisitos_asignados', []));

        try {
            DB::beginTransaction();

            // Actualiza los datos principales del tipo de certificado.
            $tipoCertificado->update([
                'nombre' => $this->mayuscula($datos['form_nombre']),
                'id_area' => $datos['form_id_area'],
                'estado' => $datos['form_estado'],
            ]);

            // Reemplaza los requisitos asociados por la nueva seleccion del usuario.
            $this->sincronizarTiposdeRequisitos(
                $tipoCertificado,
                $solicitud->input('requisitos_asignados', [])
            );

            DB::commit();
            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El tipo de certificado y sus requisitos se actualizaron correctamente.',
                'icon'  => 'success',
            ]);
            return redirect()->route('tipos_certificados_index');

        } 
        catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'No se pudo actualizar el tipo de certificado.')
                ->withInput()
                ->with('tipo_certificado_editar', $tipoCertificado->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoCertificado $tipoCertificado)
    {
        try {
            DB::beginTransaction();

            // No se elimina el tipo de certificado si ya fue usado en certificados
            if ($tipoCertificado->certificados()->exists()) {
                DB::rollBack();

                session()->flash('swal', [
                    'title' => 'No se puede eliminar',
                    'text'  => 'Este tipo de certificado ya tiene certificados relacionados.',
                    'icon'  => 'error',
                ]);

                return redirect()->route('tipos_certificados_index');
            }

            // Primero se elimina cada pivote como modelo para registrar el usuario que elimino.
            $this->eliminarRequisitosDelTipoCertificado($tipoCertificado);

            // Antes de eliminar el tipo de certificado se marca inactivo para que el registro no quede como ACTIVO.
            $tipoCertificado->update(['estado' => 'INACTIVO']);

            // Luego se elimina el tipo de certificado usando SoftDeletes.
            $tipoCertificado->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text'  => 'El tipo de certificado se elimino correctamente.',
                'icon'  => 'success',
            ]);

            return redirect()->route('tipos_certificados_index');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('tipos_certificados_index')
                ->with('error', 'No se pudo eliminar el tipo de certificado.');
        }
    }
}
