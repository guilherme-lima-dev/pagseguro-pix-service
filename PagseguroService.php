<?php


namespace App\Services;


use GuzzleHttp\Client;

class PagseguroService
{
    private string $url;
    private string $ssl_key;
    private string $cert;
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();

        $this->url = "https://secure.api.pagseguro.com";
        $this->ssl_key = base_path().'/keys/VivaBank_Sand.key';
        $this->cert = base_path().'/keys/VivaBank_Sand.pem';

        if(env('APP_ENV') === 'local'){
            $this->url = "https://secure.sandbox.api.pagseguro.com";
            $this->ssl_key = base_path().'/keys/sandbox/VivaBank_Sand.key';
            $this->cert = base_path().'/keys/sandbox/VivaBank_Sand.pem';
        }

        $this->registerWebHook();
    }

    public function auth()
    {
        $client_id = env('PAGSEGURO_CLIENT_ID');
        $client_secret = env('PAGSEGURO_CLIENT_SECRET');

        $url = $this->url."/pix/oauth2";

        $response = $this->client->request(
            'POST',
            $url,
            [
                'ssl_key' => $this->ssl_key,
                'cert' => $this->cert,
                'auth' => [$client_id, $client_secret],
                'json' => [
                    "grant_type" => "client_credentials",
                    "scope" => "pix.read pix.write cob.read cob.write webhook.write webhook.read"
                ]
            ]
        );

        $arrayResponse = json_decode($response->getBody(), true);
        if(array_key_exists('access_token', $arrayResponse))
            return $arrayResponse['access_token'];

        return false;
    }

    public function getPayment($txId, $token): array
    {

        $response = $this->client->request(
            'GET',
            "$this->url/instant-payments/cob/$txId?revisao=0",
            [
                'ssl_key' => $this->ssl_key,
                'cert' => $this->cert,
                'headers' => [
                    'Authorization' => "Bearer $token"
                ]
            ]
        );
        return json_decode($response->getBody(), true);
    }

    private function registerWebHook()
    {
        $chavePix = env('PAGSEGURO_CHAVE_PIX');
        $url = $this->url."/instant-payments/webhook/$chavePix";
        $token = $this->auth();

        $this->client->request(
            'PUT',
            $url,
            [
                'ssl_key' => $this->ssl_key,
                'cert' => $this->cert,
                'headers' => [
                    'Authorization' => "Bearer $token"
                ],
                'json' => [
                    "webhookUrl" => env('APP_URL')."/api/pix-notification"
                ]
            ]
        );
    }

}
