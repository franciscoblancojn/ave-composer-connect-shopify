<?php

namespace franciscoblancojn\AveConnectShopify;

/**
 * ShopifyHttpClient
 *
 * Cliente HTTP para interactuar con la API de Shopify.
 * Maneja los métodos GET, POST, PUT y DELETE de forma genérica,
 * enviando el token de acceso en los headers.
 *
 * @package franciscoblancojn\AveConnectShopify
 */
class ShopifyHttpClient
{
    /**
     * Versión de la API de Shopify que se usará.
     * Ejemplo: "2025-07"
     *
     * @var string
     */
    private string $version = '2025-07';

    /**
     * Nombre de la tienda Shopify (ej: "mitienda.myshopify.com").
     *
     * @var string
     */
    private string $shop;

    /**
     * Token privado de acceso a la API de Shopify.
     *
     * @var string
     */
    private string $token;

    /**
     * Constructor del cliente HTTP.
     *
     * @param string $shop    Nombre de la tienda Shopify (ej: "mitienda.myshopify.com").
     * @param string $token   Token de acceso privado.
     * @param string $version Versión de la API de Shopify (default: "2025-07").
     */
    public function __construct(string $shop, string $token, $version = '2025-07')
    {
        $this->shop = $shop;
        $this->token = $token;
        $this->version = $version;
    }

    /**
     * Ejecuta una petición HTTP genérica a la API de Shopify.
     *
     * @param string $method   Método HTTP (GET, POST, PUT, DELETE).
     * @param string $endpoint Endpoint relativo de la API (ej: "products.json").
     * @param array  $data     Datos a enviar (para POST/PUT).
     *
     * @return array|null Respuesta de la API en formato array asociativo.
     *
     * @throws \Exception Si ocurre un error en la petición.
     */
    private function request(string $method, string $endpoint, array $data = [])
    {
        $url = "https://{$this->shop}/admin/api/{$this->version}/{$endpoint}";
        $headers = [
            "X-Shopify-Access-Token: {$this->token}",
            "Content-Type: application/json"
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Request error: " . $error);
        }

        return json_decode($response, true);
    }

    /**
     * Ejecuta una petición GET a la API de Shopify.
     *
     * @param string $endpoint Endpoint relativo de la API.
     *
     * @return array|null
     */
    public function get(string $endpoint)
    {
        return $this->request('GET', $endpoint);
    }

    /**
     * Ejecuta una petición POST a la API de Shopify.
     *
     * @param string $endpoint Endpoint relativo de la API.
     * @param array  $data     Datos a enviar en el body.
     *
     * @return array|null
     */
    public function post(string $endpoint, array $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Ejecuta una petición PUT a la API de Shopify.
     *
     * @param string $endpoint Endpoint relativo de la API.
     * @param array  $data     Datos a enviar en el body.
     *
     * @return array|null
     */
    public function put(string $endpoint, array $data = [])
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * Ejecuta una petición DELETE a la API de Shopify.
     *
     * @param string $endpoint Endpoint relativo de la API.
     *
     * @return array|null
     */
    public function delete(string $endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }
}
