<?php

namespace franciscoblancojn\AveConnectShopify;

/**
 * Class ShopifyProduct
 *
 * Esta clase permite gestionar los productos en Shopify a través de la API REST.
 * Ofrece métodos para listar, crear, actualizar y eliminar productos.
 *
 * @package franciscoblancojn\AveConnectShopify
 */
class ShopifyProduct
{
    /**
     * Cliente HTTP para interactuar con la API de Shopify.
     *
     * @var ShopifyHttpClient
     */
    private ShopifyHttpClient $client;

    /**
     * Constructor de la clase ShopifyProduct.
     *
     * @param ShopifyHttpClient $client Cliente HTTP para realizar las solicitudes a la API de Shopify.
     */
    public function __construct(ShopifyHttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Obtiene una lista de productos de la tienda Shopify.
     *
     * @return array Respuesta de la API con la lista de productos.
     */
    public function get(): array
    {
        return $this->client->get("products.json");
    }

    /**
     * Crea un nuevo producto en Shopify.
     *
     * @param array $data Datos del producto a crear.
     * Ejemplo:
     * [
     *   'product' => [
     *      'title' => 'Nuevo producto',
     *      'body_html' => '<strong>Descripción del producto</strong>',
     *      'vendor' => 'MiMarca',
     *      'product_type' => 'Categoría',
     *      'variants' => [
     *          ['option1' => 'Default Title', 'price' => '19.99']
     *      ]
     *   ]
     * ]
     *
     * @return array Respuesta de la API con el producto creado.
     */
    public function post(array $data): array
    {
        return $this->client->post("products.json", $data);
    }

    /**
     * Actualiza un producto existente en Shopify.
     *
     * @param string $id ID del producto a actualizar.
     * @param array $data Datos a actualizar en el producto.
     * Ejemplo:
     * [
     *   'product' => [
     *      'id' => 123456789,
     *      'title' => 'Producto actualizado',
     *   ]
     * ]
     *
     * @return array Respuesta de la API con el producto actualizado.
     */
    public function put(string $id, array $data): array
    {
        return $this->client->put("products/$id.json", $data);
    }

    /**
     * Elimina un producto en Shopify.
     *
     * @param string $id ID del producto a eliminar.
     *
     * @return array Respuesta de la API después de eliminar el producto.
     */
    public function delete(string $id): array
    {
        return $this->client->delete("products/$id.json");
    }
}
