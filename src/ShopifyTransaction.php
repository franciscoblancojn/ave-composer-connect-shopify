<?php

namespace franciscoblancojn\AveConnectShopify;

use function franciscoblancojn\validator\FValidator;

/**
 * Clase para gestionar transacciones de Shopify.
 * 
 * Permite consultar y crear transacciones en pedidos de Shopify
 * mediante un cliente HTTP personalizado.
 */
class ShopifyTransaction
{
    /**
     * Cliente HTTP para interactuar con la API de Shopify.
     *
     * @var ShopifyHttpClient
     */
    private ShopifyHttpClient $client;
    
    /**
     * Constructor de la clase.
     *
     * @param ShopifyHttpClient $client Cliente HTTP para interactuar con Shopify.
     */
    public function __construct(ShopifyHttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Obtiene las transacciones de un pedido en Shopify.
     *
     * @param int $order_id ID del pedido en Shopify.
     * @return array Respuesta de la API de Shopify con las transacciones.
     */
    public function get(int $order_id): array
    {
        return $this->client->get("orders/$order_id/transactions.json");
    }
    
    /**
     * Devuelve un validador para los datos de creación de transacciones.
     *
     * Valida los campos obligatorios como `currency`, `amount` y `kind`.
     *
     * @return \franciscoblancojn\validator\FValidator Validador configurado para transacciones.
     */
    public function validatorPost()
    {
        return FValidator("transaction.post")->isObject([
            "transaction" => FValidator("transaction")->isObject([
                'currency' => FValidator('currency')
                    ->isRequired('La moneda es obligatoria')
                    ->isString('La moneda es obligatoria'),
                'amount' => FValidator('amount')
                    ->isRequired('El precio es obligatorio')
                    ->isString('El precio debe ser texto')
                    ->isRegex('/^\d+\.?\d{0,2}$/', 'Precio con formato decimal válido'),
                'kind' => FValidator('currency')
                    ->isRequired('kind es obligatorio')
                    ->isString('kind es obligatorio'),

            ], "La transacción debe ser un objeto válido")
        ]);
    }

    /**
     * Crea una transacción en un pedido de Shopify.
     *
     * @param int $order_id ID del pedido en Shopify.
     * @param array $data Datos de la transacción a enviar.
     * @return array Respuesta de la API de Shopify con la transacción creada.
     * 
     * @throws \Exception Si la validación de datos falla o la API devuelve un error.
     */
    public function post(int $order_id, array $data): array
    {
        $this->validatorPost()->validate($data);
        return $this->client->post("orders/$order_id/transactions.json", $data);
    }
}
