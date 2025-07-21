<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EstafetaCotizadorService
{
    protected $clientId;
    protected $clientSecret;
    protected $baseUrlToken;
    protected $baseUrlCotizador;


    public function __construct()
    {
        $this->clientId         = Setting::value('apikey_estafeta');
        $this->clientSecret     = Setting::value('client_secret_estafeta');
        $this->baseUrlToken     = 'https://apiqa.estafeta.com:8443/auth/oauth/v2/token'; //'https://apiqa.estafeta.com:8443/auth/oauth/v2/token';
        $this->baseUrlCotizador = 'https://wscotizadorqa.estafeta.com/Cotizacion/rest/Cotizador/Cotizacion'; //'https://wscotizadorqa.estafeta.com/Cotizacion/rest/Cotizador/Cotizacion';
    }

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
}
