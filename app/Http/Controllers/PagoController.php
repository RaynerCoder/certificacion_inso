<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Pago;
use App\Models\RequisitoCertificado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
                'fecha_validacion' => now()->toDateString(),
            ]);

            $certificado->pagos()->attach($pago->id);
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pago $pago)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pago $pago)
    {
        //
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
