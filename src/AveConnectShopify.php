<?php

namespace franciscoblancojn\AveConnectShopify;

/**
 * Class AveConnectShopify
 *
 * Clase principal para conectarse a la API de Shopify.
 * Inicializa los recursos principales como productos y órdenes,
 * usando un cliente HTTP configurado con la tienda, token y versión de API.
 *
 * @package franciscoblancojn\AveConnectShopify
 */
class AveConnectShopify
{
    /**
     * Manejo de productos de Shopify.
     *
     * @var ShopifyProduct
     */
    public ShopifyProduct $product;
    
    /**
     * Manejo de productos con GraphQL de Shopify.
     *
     * @var ShopifyGraphQLProduct
     */
    public ShopifyGraphQLProduct $productGraphQL;

    /**
     * Manejo de órdenes con GraphQL de Shopify.
     *
     * @var ShopifyGrahpQLOrder
     */
    public ShopifyGraphQLOrder $orderGraphQL;


    /**
     * Manejo de variation de Shopify.
     *
     * @var ShopifyVariation
     */
    public ShopifyVariation $variation;

    /**
     * Manejo de órdenes de Shopify.
     *
     * @var ShopifyOrder
     */
    public ShopifyOrder $order;

    /**
     * Manejo de transacciones de Shopify.
     *
     * @var ShopifyTransaction
     */
    public ShopifyTransaction $transaction;

    /**
     * Constructor de la clase AveConnectShopify.
     *
     * @param string $shop    El dominio de la tienda de Shopify (ejemplo: midominio.myshopify.com).
     * @param string $token   Token de acceso para la API de Shopify.
     * @param string $version Versión de la API de Shopify a utilizar. Por defecto '2025-07'.
     */
    public function __construct(string $shop, string $token, $version = '2025-07')
    {
        $client = new ShopifyHttpClient($shop, $token, $version);
        $this->product = new ShopifyProduct($client);
        $this->variation = new ShopifyVariation($client);
        $this->order = new ShopifyOrder($client);
        $this->transaction = new ShopifyTransaction($client);

        $clientGraphQL = new ShopifyGraphQLClient($shop, $token, $version);
        $this->productGraphQL = new ShopifyGraphQLProduct($clientGraphQL);
        $this->orderGraphQL = new ShopifyGraphQLOrder($clientGraphQL);
    }
}
