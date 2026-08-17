<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Pago;
use App\Models\Procedencia;
use App\Models\RequisitoCertificado;
use App\Services\GestionTramitadoresService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PagoController extends Controller
{
    public function __construct(
        private readonly GestionTramitadoresService $gestionTramitadores
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pagos.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'form_id_certificado' => ['required', 'exists:certificados,id'],
            'form_id_procedencia_pago' => ['required', 'exists:procedencias,id'],
            'form_tipo_pago' => ['required', Rule::in(array_keys(Pago::TIPOS_PAGOS))],
            'form_fecha_pago' => ['required', 'date'],
            'form_monto_pago' => ['required', 'numeric', 'min:0.01'],
            'form_factura_pago' => ['nullable', 'string', 'max:100'],
            'form_comprobante_pago' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'form_bandeja' => ['nullable', 'string', 'max:30'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor seleccionado en :attribute no existe.',
            'in' => 'Seleccione un valor valido para :attribute.',
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'min' => 'El campo :attribute debe ser mayor a cero.',
            'mimes' => 'El comprobante debe ser un archivo PDF.',
            'max' => 'El comprobante no debe superar los 10 MB.',
        ], [
            'form_id_certificado' => 'tramite',
            'form_id_procedencia_pago' => 'procedencia',
            'form_tipo_pago' => 'tipo de pago',
            'form_fecha_pago' => 'fecha de pago',
            'form_monto_pago' => 'monto',
            'form_factura_pago' => 'factura',
            'form_comprobante_pago' => 'comprobante de pago',
        ]);

        $certificado = Certificado::with('pagos')->findOrFail($datos['form_id_certificado']);

        if (!$certificado->requiereEvidencia('PAGO')) {
            return back()
                ->withErrors(['form_id_certificado' => 'Este tramite no tiene pago configurado como requisito.'])
                ->withInput();
        }

        if ($certificado->pagos->isNotEmpty()) {
            return back()
                ->withErrors(['form_id_certificado' => 'Este tramite ya tiene un pago registrado y no puede modificarse desde esta pantalla.'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $pago = Pago::create([
                'id_procedencia' => $datos['form_id_procedencia_pago'],
                'tipo_pago' => $datos['form_tipo_pago'],
                'fecha' => $datos['form_fecha_pago'],
                'comprobante' => $this->guardarComprobantePago($request, $certificado),
                'monto' => $datos['form_monto_pago'],
                'id_cliente' => $certificado->id_persona_beneficiario,
                'id_funcionario' => auth()->id(),
                'fecha_validacion' => now(),
                'factura' => filled($datos['form_factura_pago'] ?? null)
                    ? trim($datos['form_factura_pago'])
                    : null,
            ]);

            $certificado->pagos()->attach($pago->id, [
                'id_usuario_registro' => auth()->id(),
                'id_usuario_modificacion' => auth()->id(),
            ]);
            $this->registrarPagoComoEvidencia($certificado, $pago);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Pago registrado',
                'text' => 'El pago fue relacionado al tramite correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('certificados_show', [
                'certificado' => $certificado,
                'bandeja' => $datos['form_bandeja'] ?? request('bandeja', 'recibidas'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el pago. ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pago $pago)
    {
        $pago->loadMissing([
            'procedencia',
            'clientePersona.natural',
            'clientePersona.empresa',
            'funcionarioUsuario.funcionario.cargos',
        ]);
        $certificado = $pago->certificados()
            ->with([
                'tipoCertificado',
                'beneficiario.natural',
                'beneficiario.empresa',
            ])
            ->firstOrFail();

        $beneficiario = $certificado->beneficiario;
        $esEmpresa = filled($beneficiario?->empresa);
        $nombreBeneficiario = $esEmpresa
            ? ($beneficiario->empresa->razon_social ?: 'Empresa sin razón social')
            : trim(implode(' ', array_filter([
                $beneficiario?->natural?->nombres,
                $beneficiario?->natural?->apellido_paterno,
                $beneficiario?->natural?->apellido_materno,
            ])));
        $funcionario = $pago->funcionarioUsuario?->funcionario;
        $nombreFuncionario = $funcionario
            ? trim(implode(' ', array_filter([
                $funcionario->nombres,
                $funcionario->apellido_paterno,
                $funcionario->apellido_materno,
            ])))
            : null;
        $rutaComprobante = preg_replace('#^/?storage/#', '', (string) $pago->comprobante);

        return view('pagos.show', [
            'pago' => $pago,
            'certificado' => $certificado,
            'nombreBeneficiario' => $nombreBeneficiario ?: 'Sin nombre registrado',
            'tipoBeneficiario' => $esEmpresa ? 'Empresa' : 'Persona natural',
            'nombreFuncionario' => $nombreFuncionario ?: 'Sin funcionario registrado',
            'cargoFuncionario' => $funcionario?->cargos?->pluck('nombre')->filter()->implode(', ') ?: 'Sin cargo registrado',
            'tieneComprobanteDisponible' => filled($rutaComprobante)
                && Storage::disk('public')->exists($rutaComprobante),
        ]);
    }

    /**
     * Entrega el comprobante sin exponer directamente la carpeta de almacenamiento.
     */
    public function comprobante(Request $request, Pago $pago): BinaryFileResponse
    {
        $certificado = $pago->certificados()->firstOrFail();
        $usuario = $request->user();
        $puedeConsultarComoFuncionario = $usuario->puede([
            'pagos.ver',
            'pagos.validar',
            'certificados.ver',
            'seguimientos_tramite.ver',
            'seguimientos_tramite.registrados',
            'seguimientos_tramite.atender',
            'seguimientos_tramite.gestionar',
            'seguimientos_tramite.consulta_general',
        ]);

        abort_unless(
            $puedeConsultarComoFuncionario
                || $this->gestionTramitadores->usuarioPuedeConsultarTramite($usuario, $certificado),
            403,
            'No tiene permiso para consultar este comprobante.'
        );

        $ruta = preg_replace('#^/?storage/#', '', (string) $pago->comprobante);

        abort_if(blank($ruta) || !Storage::disk('public')->exists($ruta), 404, 'El comprobante no fue encontrado.');

        return response()->file(Storage::disk('public')->path($ruta), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($ruta) . '"',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pago $pago)
    {
        $pago->loadMissing([
            'clientePersona.natural',
            'clientePersona.empresa',
        ]);
        $certificado = $pago->certificados()
            ->with([
                'tipoCertificado',
                'beneficiario.natural',
                'beneficiario.empresa',
            ])
            ->firstOrFail();

        $beneficiario = $certificado->beneficiario;
        $esEmpresa = filled($beneficiario?->empresa);
        $nombreBeneficiario = $esEmpresa
            ? ($beneficiario->empresa->razon_social ?: 'Empresa sin razón social')
            : trim(implode(' ', array_filter([
                $beneficiario?->natural?->nombres,
                $beneficiario?->natural?->apellido_paterno,
                $beneficiario?->natural?->apellido_materno,
            ])));
        $rutaComprobante = preg_replace('#^/?storage/#', '', (string) $pago->comprobante);

        return view('pagos.edit', [
            'pago' => $pago,
            'certificado' => $certificado,
            'procedencias' => Procedencia::orderBy('codigo')->get(),
            'nombreBeneficiario' => $nombreBeneficiario ?: 'Sin nombre registrado',
            'tipoBeneficiario' => $esEmpresa ? 'Empresa' : 'Persona natural',
            'tieneComprobanteDisponible' => filled($rutaComprobante)
                && Storage::disk('public')->exists($rutaComprobante),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pago $pago)
    {
        $datos = $request->validate([
            'form_id_procedencia_pago' => ['required', 'exists:procedencias,id'],
            'form_tipo_pago' => ['required', Rule::in(array_keys(Pago::TIPOS_PAGOS))],
            'form_fecha_pago' => ['required', 'date'],
            'form_monto_pago' => ['required', 'numeric', 'min:0.01'],
            'form_factura_pago' => ['nullable', 'string', 'max:100'],
            'form_comprobante_pago' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'form_bandeja' => ['nullable', 'string', 'max:30'],
            'form_id_pago' => ['required', 'integer', Rule::in([$pago->id])],
            'form_return_to' => ['nullable', Rule::in(['pagos_index'])],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'El valor seleccionado en :attribute no existe.',
            'in' => 'Seleccione un valor valido para :attribute.',
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'min' => 'El campo :attribute debe ser mayor a cero.',
            'mimes' => 'El comprobante debe ser un archivo PDF.',
            'max' => 'El comprobante no debe superar los 10 MB.',
        ], [
            'form_id_procedencia_pago' => 'procedencia',
            'form_tipo_pago' => 'tipo de pago',
            'form_fecha_pago' => 'fecha de pago',
            'form_monto_pago' => 'monto',
            'form_factura_pago' => 'factura',
            'form_comprobante_pago' => 'comprobante de pago',
            'form_id_pago' => 'pago',
        ]);

        $certificado = $pago->certificados()->firstOrFail();

        try {
            DB::beginTransaction();

            $cambios = [
                'id_procedencia' => $datos['form_id_procedencia_pago'],
                'tipo_pago' => $datos['form_tipo_pago'],
                'fecha' => $datos['form_fecha_pago'],
                'monto' => $datos['form_monto_pago'],
                'factura' => filled($datos['form_factura_pago'] ?? null)
                    ? trim($datos['form_factura_pago'])
                    : null,
            ];

            // El comprobante actual se conserva cuando no se selecciona un PDF nuevo.
            if ($request->hasFile('form_comprobante_pago')) {
                $cambios['comprobante'] = $this->guardarComprobantePago($request, $certificado);
            }

            $pago->update($cambios);
            $this->registrarPagoComoEvidencia($certificado, $pago);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Pago actualizado',
                'text' => 'Los datos del pago fueron corregidos correctamente.',
                'icon' => 'success',
            ]);

            if (($datos['form_return_to'] ?? null) === 'pagos_index') {
                return redirect()->route('pagos_index');
            }

            return redirect()->route('certificados_show', [
                'certificado' => $certificado,
                'bandeja' => $datos['form_bandeja'] ?? 'recibidas',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo actualizar el pago. ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pago $pago)
    {
        //
    }

    // Guarda el comprobante PDF en storage publico y devuelve la ruta consumida por las vistas.
    private function guardarComprobantePago(Request $request, Certificado $certificado): ?string
    {
        if (!$request->hasFile('form_comprobante_pago')) {
            return null;
        }

        $archivo = $request->file('form_comprobante_pago');
        $nombre = 'comprobante_' . $certificado->id . '_' . now()->format('YmdHis') . '.pdf';
        $ruta = $archivo->storeAs('pagos/' . $certificado->id, $nombre, 'public');

        return 'storage/' . $ruta;
    }

    // Relaciona el pago con el requisito configurado como PAGO para que la revision lea el dato desde evidencias_requisitos.
    private function registrarPagoComoEvidencia(Certificado $certificado, Pago $pago): void
    {
        $requisitoPago = RequisitoCertificado::with('evidenciasRequisitos.tipoEvidencia')
            ->where('id_certificado', $certificado->id)
            ->whereHas('evidenciasRequisitos.tipoEvidencia', function ($query) {
                $query->where('codigo', 'PAGO');
            })
            ->first();

        if (!$requisitoPago) {
            return;
        }

        $evidenciaPago = $requisitoPago->evidenciasRequisitos->first(function ($evidencia) {
            return strtoupper((string) $evidencia->tipoEvidencia?->codigo) === 'PAGO';
        });

        if (!$evidenciaPago) {
            return;
        }

        $evidenciaPago->update([
            'valor' => (string) $pago->id,
            'estado' => 'REGISTRADO',
            'id_usuario_modificacion' => auth()->id(),
        ]);
    }
}
