<?php

namespace franciscoblancojn\AveConnectShopify;

use GuzzleHttp\Client;

class ShopifyGraphQLClient
{
    protected Client $client;
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

    public function __construct(string $shop, string $token, string $version = '2025-07')
    {
        $this->shop = $shop;
        $this->token = $token;
        $this->version = $version;

        $this->client = new Client([
            'base_uri' => "https://{$this->shop}.myshopify.com/admin/api/{$this->version}/",
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $this->token,
            ],
        ]);
    }

    public function query(string $query, array $variables = null)
    {
        $payload = ['query' => $query];
        if (!is_null($variables)) {
            $payload['variables'] = $variables;
        }

        $response = $this->client->post('graphql.json', [
            'json' => $payload
        ]);

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (isset($data['errors'])) {
            // Manejar errores
            throw new \Exception("Shopify GraphQL error: " . json_encode($data['errors']));
        }

        return $data['data'];
    }
}
