<?php

namespace App\Services;

class OpenBcbGateway
{
    /**
     * Genera el QR utilizando el servicio configurado.
     *
     * En modo "mock" utiliza nuestro simulador local.
     * En modo "real" envía la solicitud a OpenBCB.
     */
    public function generarQr(array $datos): array
    {
        //Mientras OpenBCB no esté disponible, trabajamos con OpenBcbMockService para continuar las pruebas.
        if ($this->usarSimulador()) {
            return app(OpenBcbMockService::class)->generarQr($datos);
        }

        //Cuando OPENBCB_MODE sea "real", esta parte utilizará el servicio que firma y envía la petición a OpenBCB.
        return app(OpenBcbService::class)->generarQr($datos);
    }


    // Consulta el estado y las órdenes relacionadas con un QR.
    public function consultarQr(string $idQr): array
    {
        // En modo mock consulta la respuesta preparada en el simulador.
        if ($this->usarSimulador()) {
            return app(OpenBcbMockService::class)->consultarQr($idQr);
        }

        // En modo real consulta GET /v1/qr/{idQR} en OpenBCB.
        return app(OpenBcbService::class)->consultarQr($idQr);
    }   


    //Revisa en la configuración si debemos utilizar el simulador.
    private function usarSimulador(): bool
    {
        /*
         * Este valor viene de:
         * OPENBCB_MODE=mock
         * que se encuentra en el archivo .env.
        */
        return config('services.openbcb.mode') === 'mock';
    }


 

}


/*
generarQr()   → POST /v1/qr
consultarQr() → GET /v1/qr/{idQR}
*/