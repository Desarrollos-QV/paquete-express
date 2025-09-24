<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class EstafetaCotizadorService
{
    protected $clientId;
    protected $clientSecret;
    protected $baseUrlToken;
    protected $baseUrlCotizador;
    protected $baseUrlCreateLabel;
    protected $baseUrlTrackingItems;

    public function __construct()
    {
        $this->clientId             = Setting::value('apikey_estafeta');
        $this->clientSecret         = Setting::value('client_secret_estafeta');
        $this->baseUrlToken         = 'https://apiqa.estafeta.com:8443/auth/oauth/v2/token'; //'https://apiqa.estafeta.com:8443/auth/oauth/v2/token';
        $this->baseUrlCotizador     = 'https://wscotizadorqa.estafeta.com/Cotizacion/rest/Cotizador/Cotizacion'; //'https://wscotizadorqa.estafeta.com/Cotizacion/rest/Cotizador/Cotizacion';
        $this->baseUrlCreateLabel   = 'https://labelqa.estafeta.com/v1/wayBills/batch?outputType=FILE_PDF_SC&outputGroup=REQUEST&responseMode=SYNC_INLINE'; // 'https://labelqa.estafeta.com/RestLabel/wslabel.svc/Label';
        $this->baseUrlTrackingItems = 'https://csrestqa.estafeta.com/v1/WS_trackingItems/tracking-item-status';
    }

    /**
     * Summary of getAccessToken
     */
    protected function getAccessToken(): ?string
    {
        $cacheKey = 'estafeta_access_token_' . md5($this->clientId);

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $client = new Client(); // ['verify' => false]
            $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");
            try {
                $response = $client->post($this->baseUrlToken, [
                    'headers' => [
                        'Authorization' => 'Basic ' . $credentials,
                        'Content-Type'  => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'scope'     => 'execute'
                    ],
                ]);

                $body = json_decode($response->getBody(), true);
                Log::info("Token Creado : " . json_encode($body['access_token']));
                return $body['access_token'] ?? null;
            } catch (\Exception $e) {
                Log::error('Estafeta token error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Summary of cotizar
     * @param array $datos
     */
    public function cotizar(array $datos): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['error' => 'No se pudo obtener token de Estafeta'];
        }

        $client = new Client();

        Log::info('Access Token Generado....' . json_encode($token));
        Log::info('Data' . json_encode($datos));
        try {
            $response = $client->post($this->baseUrlCotizador, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'apiKey'        => $this->clientId
                ],
                'json' => $datos,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Estafeta cotización error: ' . $e->getMessage());
            return ['error' => 'Error al cotizar con Estafeta'];
        }
    }

    /**
     * Summary of generarGuia
     * @param array $payload
     * @return array{result: mixed, status: int|null}
     */
    public function generarGuia(array $payload): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::error('Estafeta: No se pudo obtener token para generar guía.');
            return null;
        }

        try {
            $client = new Client();
            $response = $client->post($this->baseUrlCreateLabel, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'apiKey'        => $this->clientId
                ],
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody(), true);

            // Verifica si el campo 'data' contiene el base64
            if (!empty($result['data'])) {
                $pdfBase64 = $result['data'];
                $pdfBinary = base64_decode($pdfBase64);

                $trackingCode = $result['labelPetitionResults'][0]['elements'][0]['trackingCode'];

                $fileName = 'guias/estafeta_' . $trackingCode . '.pdf';
                Storage::disk('local')->put($fileName, $pdfBinary);
                // Agrega ruta al array para referencia futura
                $result['local_pdf_path'] = storage_path('app/' . $fileName);
            }

            return [
                'status' => 200,
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Estafeta: Excepción al generar guía. ' . json_encode($e->getMessage()));
            return null;
        }
    }

    /**
     * Summary of rastrearGuia
     * @param string $trackingCode
     */
    public function rastrearGuia(string $trackingCode): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::error('Estafeta: No se pudo obtener token para rastrear guía.');
            return null;
        }
        Log::info('Access Token Generado....' . json_encode($token));
        try {
            $client = new Client();
            $response = $client->post($this->baseUrlTrackingItems, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'apiKey'        => $this->clientId
                ],
                'json' => [
                    'inputType' => 0,
                    'itemReference' => [
                        'clientNumber'  => env('ESTAFETA_CUSTOMER_NUMBER'),
                        'referenceCode' => []
                    ],
                    'itemsSearch' => [$trackingCode],
                    'searchType' => 1
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('Estafeta tracking response: ' . json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Log::error('Estafeta: Excepción al rastrear guía. ' . json_encode($e->getMessage()));
            return null;
        }
    }

    /**
     * Summary of getDefaultOrigin
     * @return array{address1: mixed, city: mixed, contactName: mixed, customerNumber: mixed, neighborhood: mixed, phoneNumber: mixed, state: mixed, zipCode: mixed}
     */
    public function getDefaultOrigin(): array
    {
        return [
            "zipCode"          => env("ESTAFETA_ORIGIN_ZIPCODE"),
            "roadName"         => env('ESTAFETA_ORIGIN_ROADNAME'), // Calle (sin número)
            "externalNum"      => env('ESTAFETA_ORIGIN_EXTERNALNUM'), // Solo número exterior
            "settlementName"   => env("ESTAFETA_ORIGIN_SETTLEMENTNAME"),
            "townshipName"     => env('ESTAFETA_ORIGIN_TOWNSHIPNAME'), // Este dato no está en tus variables, lo agregamos fijo
            "stateAbbName"     => env("ESTAFETA_ORIGIN_STATE"),
            "countryName"      => env('ESTAFETA_ORIGIN_COUNTRYNAME'),
            "addressReference" => env('ESTAFETA_ORIGIN_ADDRESSREFERENCE'), // Puedes agregar otra variable ENV si lo deseas
            "bUsedCode"        => false,
            "roadTypeAbbName"       => "string",
            "settlementTypeAbbName" => "string"
        ];
    }


    /**
     * Summary of mapServiceCodeToServiceTypeId
     * Mapeamos el ServiceCode Para obtener el serviceTypeId 
     * @param mixed $serviceCode
     * @return int
     */
    public function mapServiceCodeToServiceTypeId($serviceCode): int
    {
        return match ($serviceCode) {
            '30' => 70,  // 09:30
            '40' => 71,  // 11:30
            '50' => 72,  // Día siguiente
            '70' => 74,  // Terrestre
            '75' => 75,  // Terrestre 1 kg
            '60' => 73,  // 2 días
            default => 70, // Fallback seguro
        };
    }
}