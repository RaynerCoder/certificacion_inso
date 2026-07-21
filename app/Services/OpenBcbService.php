<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenBcbService
{
    protected string $baseUrl;
    protected ?string $entityId;
    protected ?string $keyId;
    protected ?string $sharedSecret;
    protected ?string $accessToken;

    public function __construct()
    {
        /*
         * Leemos la configuración desde config/services.php.
         * Ese archivo toma los valores del .env.
         */
        $this->baseUrl = rtrim((string) config('services.openbcb.base_url'), '/');
        $this->entityId = config('services.openbcb.entity_id');
        $this->keyId = config('services.openbcb.key_id');
        $this->sharedSecret = config('services.openbcb.shared_secret');
        $this->accessToken = config('services.openbcb.access_token');

        if (!$this->baseUrl) {
            throw new RuntimeException('Falta OPENBCB_BASE_URL en el archivo .env.');
        }
    }

    /**
     * Prueba básica.
     *
     * Solo consulta:
     * https://openbcb.pruebas.bcb.gob.bo/api
     *
     * Esta prueba no usa firma.
     * Sirve únicamente para confirmar que Laravel llega al servicio.
     */
    public function probarServicioBase(): array
    {
        $response = Http::acceptJson()
            ->connectTimeout(config('services.openbcb.connect_timeout', 10))
            ->timeout(config('services.openbcb.timeout', 30))
            ->get($this->baseUrl);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenBCB respondió con HTTP ' . $response->status()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Verifica que existan las credenciales necesarias.
     */
    private function validarCredenciales(): void
    {
        $faltantes = [];

        if (!$this->entityId) {
            $faltantes[] = 'OPENBCB_ENTITY_ID';
        }

        if (!$this->keyId) {
            $faltantes[] = 'OPENBCB_KEY_ID';
        }

        if (!$this->sharedSecret) {
            $faltantes[] = 'OPENBCB_SHARED_SECRET';
        }

        if (!$this->accessToken) {
            $faltantes[] = 'OPENBCB_ACCESS_TOKEN';
        }

        if (!empty($faltantes)) {
            throw new RuntimeException(
                'Faltan variables OpenBCB en el .env: ' . implode(', ', $faltantes)
            );
        }
    }

    /**
     * Genera fecha GMT en formato requerido por OpenBCB.
     *
     * Ejemplo:
     * Tue, 15 Nov 1994 08:12:31 GMT
     */
    private function generarFechaGmt(): string
    {
        return gmdate('D, d M Y H:i:s') . ' GMT';
    }

    /**
     * Une la base URL con el endpoint.
     *
     * Base:
     * https://openbcb.pruebas.bcb.gob.bo/api
     *
     * Endpoint:
     * /v1/entidades/123
     *
     * Resultado:
     * https://openbcb.pruebas.bcb.gob.bo/api/v1/entidades/123
     */
    private function construirUrl(string $endpoint): string
    {
        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }

    /**
     * Obtiene la ruta que se firmará en métodos GET.
     *
     * Si la URL completa es:
     * https://openbcb.pruebas.bcb.gob.bo/api/v1/entidades/123
     *
     * La ruta para el digest debe ser:
     * /api/v1/entidades/123
     */
    private function obtenerRutaParaDigest(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return $query ? $path . '?' . $query : $path;
    }

    /**
     * Calcula X-Digest.
     *
     * GET:
     * Se calcula sobre la ruta.
     *
     * POST / PUT / PATCH:
     * Se calcula sobre el body JSON exacto.
     */
    private function calcularDigest(string $entrada): string
    {
        $hashBinario = hash('sha512', $entrada, true);

        return 'SHA-512=' . base64_encode($hashBinario);
    }

    /**
     * Calcula la firma HMAC-SHA256 usando el sharedSecret.
     *
     * Importante:
     * El sharedSecret se usa como texto plano UTF-8.
     * No se debe decodificar como Base64.
     */
    private function calcularHmac(string $signingString): string
    {
        $hmacBinario = hash_hmac(
            'sha256',
            $signingString,
            (string) $this->sharedSecret,
            true
        );

        return base64_encode($hmacBinario);
    }

    /**
     * Genera las cabeceras firmadas requeridas por OpenBCB:
     *
     * X-Entity-Id
     * X-Date
     * X-Digest
     * X-Signature
     */
    private function generarCabecerasFirmadas(
        string $metodo,
        string $url,
        string $bodyJson = ''
    ): array {
        $this->validarCredenciales();

        $metodo = strtoupper($metodo);

        /*
         * 1. Fecha de la petición.
         */
        $xDate = $this->generarFechaGmt();

        /*
         * 2. Para GET se firma la ruta.
         * Para POST/PUT/PATCH se firma el body JSON.
         */
        if (in_array($metodo, ['POST', 'PUT', 'PATCH'], true)) {
            $digestInput = $bodyJson;
        } else {
            $digestInput = $this->obtenerRutaParaDigest($url);
        }

        /*
         * 3. Digest SHA-512 en Base64.
         */
        $xDigest = $this->calcularDigest($digestInput);

        /*
         * 4. Signing string.
         *
         * El orden debe ser exactamente:
         * x-date
         * x-digest
         * x-entity-id
         *
         * No debe tener salto de línea al final.
         */
        $signingString = 'x-date: ' . $xDate . "\n" .
            'x-digest: ' . $xDigest . "\n" .
            'x-entity-id: ' . $this->entityId;

        /*
         * 5. Firma HMAC-SHA256.
         */
        $signature = $this->calcularHmac($signingString);

        /*
         * 6. Header final X-Signature.
         */
        $xSignature = 'keyid="' . $this->keyId . '", ' .
            'algorithm="HmacSHA256", ' .
            'headers="X-Date X-Digest X-Entity-Id", ' .
            'signature="' . $signature . '"';

        return [
            'X-Entity-Id' => $this->entityId,
            'X-Date' => $xDate,
            'X-Digest' => $xDigest,
            'X-Signature' => $xSignature,
        ];
    }

    /**
     * Cliente HTTP base.
     *
     * Envía el token como:
     * Authorization: Bearer TU_TOKEN
     */
    private function clienteBase()
    {
        $this->validarCredenciales();

        return Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken)
            ->connectTimeout(config('services.openbcb.connect_timeout', 10))
            ->timeout(config('services.openbcb.timeout', 30));
    }

    /**
     * Ejecuta una petición GET firmada.
     */
    private function getFirmado(string $endpoint): array
    {
        $url = $this->construirUrl($endpoint);

        $headers = $this->generarCabecerasFirmadas(
            'GET',
            $url
        );

        $response = $this->clienteBase()
            ->withHeaders($headers)
            ->get($url);

        if ($response->failed()) {
            Log::warning('OpenBCB respondió con error en GET firmado.', [
                'status' => $response->status(),
                'body' => $this->sanitizar($response->json() ?? []),
            ]);

            throw new RuntimeException(
                'OpenBCB respondió con HTTP ' . $response->status() .
                ' - ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Ejecuta una petición POST firmada.
     *
     * Se usa withBody() para que el JSON firmado sea exactamente
     * el mismo JSON que se manda a OpenBCB.
     */
    private function postFirmado(string $endpoint, array $datos): array
    {
        $url = $this->construirUrl($endpoint);

        $bodyJson = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($bodyJson === false) {
            throw new RuntimeException('No se pudo convertir el body a JSON.');
        }

        $headers = $this->generarCabecerasFirmadas(
            'POST',
            $url,
            $bodyJson
        );

        $response = $this->clienteBase()
            ->withHeaders($headers)
            ->withBody($bodyJson, 'application/json')
            ->post($url);

        if ($response->failed()) {
            Log::warning('OpenBCB respondió con error en POST firmado.', [
                'status' => $response->status(),
                'body' => $this->sanitizar($response->json() ?? []),
            ]);

            throw new RuntimeException(
                'OpenBCB respondió con HTTP ' . $response->status() .
                ' - ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Consulta los datos de la entidad.
     *
     * Endpoint:
     * GET /v1/entidades/{idEntidad}
     */
    public function obtenerEntidad(?string $idEntidad = null): array
    {
        $idEntidad = $idEntidad ?: $this->entityId;

        $endpoint = config('services.openbcb.endpoints.entidad');

        if (!$endpoint) {
            throw new RuntimeException(
                'Falta OPENBCB_ENTIDAD_ENDPOINT en el archivo .env.'
            );
        }

        $endpoint = str_replace(
            '{idEntidad}',
            rawurlencode((string) $idEntidad),
            $endpoint
        );

        return $this->getFirmado($endpoint);
    }

    /**
     * Genera un QR.
     *
     * Endpoint:
     * POST /v1/qr
     *
     * No lo uses todavía hasta que obtenerEntidad() funcione.
     */
    public function generarQr(array $datos): array
    {
        $endpoint = config('services.openbcb.endpoints.qr_crear');

        if (!$endpoint) {
            throw new RuntimeException(
                'Falta OPENBCB_QR_CREAR_ENDPOINT en el archivo .env.'
            );
        }

        return $this->postFirmado($endpoint, $datos);
    }

    /**
     * Consulta el estado de un QR.
     *
     * Endpoint:
     * GET /v1/qr/{idQR}
     */
    public function consultarQr(string $idQr): array
    {
        $endpoint = config('services.openbcb.endpoints.qr_consultar');

        if (!$endpoint) {
            throw new RuntimeException(
                'Falta OPENBCB_QR_CONSULTAR_ENDPOINT en el archivo .env.'
            );
        }

        $endpoint = str_replace(
            '{idQR}',
            rawurlencode($idQr),
            $endpoint
        );

        return $this->getFirmado($endpoint);
    }

    /**
     * Oculta datos sensibles antes de guardarlos en logs.
     */
    private function sanitizar(array $data): array
    {
        $camposSensibles = [
            'access_token',
            'accessToken',
            'token',
            'shared_secret',
            'sharedSecret',
            'secret',
            'authorization',
            'Authorization',
            'X-Signature',
        ];

        foreach ($camposSensibles as $campo) {
            if (array_key_exists($campo, $data)) {
                $data[$campo] = '[OCULTO]';
            }
        }

        return $data;
    }




    //
    private function putFirmado(string $endpoint, array $datos): array
    {
        $url = $this->construirUrl($endpoint);

        $bodyJson = json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($bodyJson === false) {
            throw new RuntimeException('No se pudo convertir el body a JSON.');
        }

        $headers = $this->generarCabecerasFirmadas(
            'PUT',
            $url,
            $bodyJson
        );

        $response = $this->clienteBase()
            ->withHeaders($headers)
            ->withBody($bodyJson, 'application/json')
            ->put($url);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenBCB respondió con HTTP ' . $response->status() .
                ' - ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }
    

    
    public function actualizarCuenta(string $cta, array $datos): array
    {
        $endpoint = config('services.openbcb.endpoints.cuenta_actualizar');

        if (!$endpoint) {
            throw new RuntimeException(
                'Falta OPENBCB_CUENTA_ACTUALIZAR_ENDPOINT en el archivo .env.'
            );
        }

        $endpoint = str_replace(
            '{cta}',
            rawurlencode($cta),
            $endpoint
        );

        return $this->putFirmado($endpoint, $datos);
    }    
}