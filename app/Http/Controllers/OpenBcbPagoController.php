<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\PagoOpenBcb;
use App\Models\Certificado;

use App\Services\OpenBcbGateway;
use App\Services\GestionTramitadoresService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OpenBcbPagoController extends Controller
{
    /*
    OpenBcbGateway
    → OpenBcbMockService cuando OPENBCB_MODE=mock
    → OpenBcbService cuando OPENBCB_MODE=real
    */



    // $openBcbGateway: comunica el sistema con el simulador o con OpenBCB.
    // $gestionTramitadores: comprueba si el usuario puede consultar ese trámite.
    public function __construct(
        private OpenBcbGateway $openBcbGateway, 
        private GestionTramitadoresService $gestionTramitadores) {

    }



    // Abre la pantalla donde el solicitante revisará el pago antes de generar el QR.
    public function mostrarPago(Request $solicitud, Certificado $certificado)
    {
        // Comprobamos que el usuario tenga permiso para consultar este trámite.
        $puedeConsultarTramite = $this->gestionTramitadores
            ->usuarioPuedeConsultarTramite(
                $solicitud->user(),
                $certificado
            );

        if (!$puedeConsultarTramite) {
            abort(403, 'No tiene permiso para consultar el pago de este trámite.');
        }

        // El pago solo corresponde a trámites que tengan la evidencia PAGO.
        $tramiteRequierePago = $certificado->requiereEvidencia('PAGO');

        if (!$tramiteRequierePago) {
            abort(404, 'Este trámite no tiene un pago configurado.');
        }

        // Cargamos la información que aparecerá en la pantalla de pago.
        $certificado->load([
            'tipoCertificado',
            'beneficiario',
            'tramitador',
            'pagos.codigosQrOpenBcb.ordenes',
        ]);

        // Busca el pago QR más reciente relacionado con este trámite.
        $pago = $certificado->pagos
            ->where('tipo_pago', 'QR')
            ->sortByDesc('id')
            ->first();

        if (! $pago) {
            abort(404, 'Este trámite no tiene un pago QR registrado.');
        }

        // Recupera el último QR para no generar otro innecesariamente.
        $ultimoQrOpenBcb = $pago->codigosQrOpenBcb
            ->sortByDesc('id')
            ->first();

        // Abre la pantalla de pago después de registrar el trámite.
        return view('seguimientos_certificados.nuevo_tramite.pagos.index', [
            'certificado' => $certificado,
            'pago' => $pago,
            'ultimoQrOpenBcb' => $ultimoQrOpenBcb,
        ]);
    }



    // Genera un código QR para un pago registrado en nuestro sistema.
    public function generarQr(Request $solicitud, Pago $pago)
    {
        // Impide generar el QR de un trámite ajeno.
        $this->validarAccesoAlPago($solicitud, $pago);

        // No se genera otro QR cuando el pago ya fue confirmado.
        if ($pago->fecha_validacion) {
            return response()->json([
                'finalizado' => false,
                'mensaje' => 'Este pago ya fue confirmado.',
            ], 422);
        }

        // Busca el QR más reciente generado para este pago.
        $ultimoQrOpenBcb = $pago->codigosQrOpenBcb()->latest('id')->first();

        // La solicitud enviada a OpenBCB configura una vigencia de 24 horas.
        $qrContinuaVigente = $ultimoQrOpenBcb
            && $ultimoQrOpenBcb->created_at
            && $ultimoQrOpenBcb->created_at->copy()->addDay()->isFuture();

        if ($qrContinuaVigente) {
            return response()->json([
                'finalizado' => true,
                'mensaje' => 'Este pago ya tiene un código QR vigente.',
                'datos' => [
                    'idPagoOpenBcb' => $ultimoQrOpenBcb->id,
                    'idPago' => $pago->id,
                    'idQr' => $ultimoQrOpenBcb->id_qr,
                    'qrExistente' => true,
                ],
            ]);
        }        

        // Solo los pagos registrados como QR pueden utilizar OpenBCB.
        if ($pago->tipo_pago !== 'QR') {
            return response()->json([
                'finalizado' => false,
                'mensaje' => 'El pago seleccionado no utiliza el tipo de pago QR.',
            ], 422);
        }

        // OpenBCB necesita un importe mayor a cero.
        if ((float) $pago->monto <= 0) {
            return response()->json([
                'finalizado' => false,
                'mensaje' => 'El monto del pago debe ser mayor a cero.',
            ], 422);
        }
        
        // Preparamos el cuerpo solicitado por POST /v1/qr
        // Datos que enviaremos a OpenBCB para generar el QR
        $datosParaGenerarQr = [

            // Nombre de la institución que recibirá el pago
            'titularDestinatario' => config('services.openbcb.qr.titular_destinatario'),

            // NIT de la institución que recibirá el pago
            'ciNitDestinatario' => config('services.openbcb.qr.ci_nit_destinatario'),

            // Código financiero asignado a la institución
            'eif' => config('services.openbcb.qr.eif'),

            // Cuenta donde se recibirá el dinero
            'cuentaDestino' => config('services.openbcb.qr.cuenta_destino'),

            // No se dividirá el pago entre varias cuentas
            // Este valor debe confirmarse con BCB antes del modo real
            'cuentaDestinoDistribucion' => new \stdClass(),

            // Moneda del pago: BOB significa bolivianos
            'codMoneda' => config('services.openbcb.qr.cod_moneda', 'BOB'),

            // Monto registrado en nuestro pago
            'importe' => (float) $pago->monto,

            // Descripción que identificará el pago
            'glosa' => 'Pago Certificador INSO Nro. ' . $pago->id,

            // El QR vencerá 24 horas después de generarse
            'fechaVencimiento' => now()->addDay()->format('Y-m-d H:i:s'),

            // El QR podrá utilizarse una sola vez
            'unicoUso' => true,

            // Código de servicio configurado para OpenBCB
            'codigoServicio' => config('services.openbcb.qr.codigo_servicio', '0'),

            // OpenBCB devolverá esta información al consultar el QR
            'metaData' => [
                'idPago' => $pago->id,
            ],
        ];

        // El Gateway utiliza el simulador porque OPENBCB_MODE está en mock.
        $respuesta = $this->openBcbGateway->generarQr($datosParaGenerarQr);

        // No se guarda información cuando OpenBCB informa que la operación falló.
        if (! data_get($respuesta, 'finalizado', false)) {
            return response()->json([
                'finalizado' => false,
                'mensaje' => data_get(
                    $respuesta,
                    'mensaje',
                    'OpenBCB no pudo generar el código QR.'
                ),
                'errores' => data_get($respuesta, 'errores', []),
            ], 502);
        }

        // Obtenemos datos.idQr de la respuesta de OpenBCB.
        $idQr = data_get($respuesta, 'datos.idQr');

        // No guardamos el registro si la respuesta no contiene idQr.
        if (!$idQr) {
            return response()->json([
                'finalizado' => false,
                'mensaje' => 'OpenBCB no devolvió el identificador del QR.',
            ], 502);
        }

        // Guarda como JPG la imagen Base64 devuelta por OpenBCB.
        $imagenQrPath = $this->guardarImagenQr($pago->id, $idQr, data_get($respuesta, 'datos.imagenQr'));

        // Guardamos la relación entre el pago y el QR generado.
        $pagoOpenBcb = PagoOpenBcb::create([
            'id_pago' => $pago->id,
            'id_qr' => $idQr,
            'imagen_qr_path' => $imagenQrPath,
        ]);

        // Respuesta temporal para comprobar el funcionamiento.
        return response()->json([
            'finalizado' => true,
            'mensaje' => 'QR generado correctamente.',
            'datos' => [
                'idPagoOpenBcb' => $pagoOpenBcb->id,
                'idPago' => $pago->id,
                'idQr' => $pagoOpenBcb->id_qr,
            ],
        ]);
    }



    // Consulta en OpenBCB las operaciones realizadas con un QR.
    public function consultarQr(Request $solicitud, PagoOpenBcb $pagoOpenBcb) 
    {
        $pago = $pagoOpenBcb->pago;

        if (! $pago) {
            abort(404, 'No se encontró el pago relacionado con este QR.');
        }

        // Impide consultar el QR de un trámite ajeno.
        $this->validarAccesoAlPago($solicitud, $pago);

        // No es posible consultar OpenBCB si todavía no tenemos su idQr.
        if (!$pagoOpenBcb->id_qr) {
            return response()->json([
                'finalizado' => false,
                'mensaje' => 'Este registro todavía no tiene un identificador de QR.',
            ], 422);
        }

        // En modo mock consulta el simulador; en modo real consulta GET /v1/qr/{idQR}.
        $respuesta = $this->openBcbGateway->consultarQr($pagoOpenBcb->id_qr);

        // Verificamos que OpenBCB haya procesado correctamente la consulta.
        if (!data_get($respuesta, 'finalizado', false)) {
            return response()->json([
                'finalizado' => false,
                'mensaje' => data_get(
                    $respuesta,
                    'mensaje',
                    'No se pudo consultar el QR en OpenBCB.'
                ),
            ], 502);
        }

        // OpenBCB devuelve dentro de "ordenes" los pagos realizados con el QR.
        $ordenes = data_get($respuesta, 'datos.ordenes', []);

        foreach ($ordenes as $orden) {
            $idOrdenDestinatario = data_get($orden, 'idOrdenDestinatario');

            // Sin este identificador no podemos reconocer ni actualizar la operación.
            if (!$idOrdenDestinatario) {
                continue;
            }

            // Buscamos dentro de este QR la operación identificada por OpenBCB.
            // Si ya existe actualizamos sus datos; si no existe la registramos.
            $pagoOpenBcb->ordenes()->updateOrCreate(
                [
                    'id_orden_destinatario' => $idOrdenDestinatario,
                ],
                [
                    'cod_moneda' => data_get($orden, 'codMoneda'),
                    'importe' => data_get($orden, 'importe'),
                    'fecha' => data_get($orden, 'fecha'),
                    'ci_nit_originante' => data_get($orden, 'ciNitOriginante'),
                    'nombre_originante' => data_get($orden, 'nombreOriginante'),
                    'cuenta_origen' => data_get($orden, 'cuentaOrigen'),
                    'eif_origen' => data_get($orden, 'eifOrigen'),
                    'estado' => data_get($orden, 'estado'),
                ]
            );
        }

        return response()->json([
            'finalizado' => true,
            'mensaje' => data_get($respuesta, 'mensaje'),
            'datos' => [
                'idQr' => data_get($respuesta, 'datos.idQr'),
                'ordenes' => $pagoOpenBcb->ordenes()->get(),
            ],
        ]);
    }



    // Muestra la imagen del QR solamente al usuario relacionado con el pago.
    public function mostrarImagenQr(
        Request $solicitud,
        PagoOpenBcb $pagoOpenBcb
    ) {
        $pago = $pagoOpenBcb->pago;

        if (!$pago) {
            abort(404, 'No se encontró el pago relacionado con este QR.');
        }

        // Verifica que el pago pertenezca al usuario autenticado.
        $this->validarAccesoAlPago($solicitud, $pago);

        $rutaImagen = $pagoOpenBcb->imagen_qr_path;

        $disco = Storage::disk('local')->exists((string) $rutaImagen) ? 'local' : 'public';

        if (blank($rutaImagen) || ! Storage::disk($disco)->exists($rutaImagen)) {
            abort(404, 'No se encontró la imagen del código QR.');
        }

        // Devuelve el archivo guardado sin exponer directamente storage.
        return Storage::disk($disco)->response(
            $rutaImagen,
            null,
            [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }

    

    // Comprueba que el pago pertenezca a un trámite que el usuario puede consultar.
    private function validarAccesoAlPago(Request $solicitud, Pago $pago): Certificado 
    {
        $certificado = $pago->certificados()->first();

        if (! $certificado) {
            abort(404, 'El pago no está relacionado con ningún trámite.');
        }

        $puedeConsultarTramite = $this->gestionTramitadores
            ->usuarioPuedeConsultarTramite(
                $solicitud->user(),
                $certificado
            );

        if (! $puedeConsultarTramite) {
            abort(403, 'No tiene permiso para administrar este pago.');
        }

        return $certificado;
    }



    // Convierte la imagen Base64 de OpenBCB en un archivo JPG.
    private function guardarImagenQr(int $idPago, string $idQr, ?string $imagenQrBase64): ?string {
        // En simulación puede no existir una imagen.
        if (blank($imagenQrBase64)) {
            return null;
        }

        // Quita el prefijo si la respuesta incluye "data:image/...;base64,".
        $base64SinPrefijo = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/','', $imagenQrBase64);

        // Convierte el texto Base64 en el contenido real de la imagen.
        $contenidoImagen = base64_decode($base64SinPrefijo, true);

        if ($contenidoImagen === false) {
            throw new \RuntimeException(
                'OpenBCB devolvió una imagen QR inválida.'
            );
        }

        // Evita caracteres no permitidos en el nombre del archivo.
        $nombreArchivo = preg_replace('/[^A-Za-z0-9_-]/', '_', $idQr);

        $imagenQrPath = 'openbcb/qr/'.$idPago.'-pago/'.$nombreArchivo.'-qr.jpg';

        $imagenGuardada = Storage::disk('local')->put($imagenQrPath, $contenidoImagen);

        if (! $imagenGuardada) {
            throw new \RuntimeException(
                'No se pudo guardar la imagen del código QR.'
            );
        }

        return $imagenQrPath;
    }



}
