<?php

namespace franciscoblancojn\AveConnectShopify;

/**
 * Class ShopifyOrder
 *
 * Maneja las operaciones relacionadas con las órdenes en Shopify a través de la API.
 * Permite obtener, crear, actualizar y eliminar órdenes.
 *
 * @package franciscoblancojn\AveConnectShopify
 */
class ShopifyOrder
{
    /**
     * Cliente HTTP para interactuar con la API de Shopify.
     *
     * @var ShopifyHttpClient
     */
    private ShopifyHttpClient $client;

    /**
     * Constructor de la clase ShopifyOrder.
     *
     * @param ShopifyHttpClient $client Cliente HTTP para hacer las solicitudes a la API de Shopify.
     */
    public function __construct(ShopifyHttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Obtiene la lista de órdenes desde la API de Shopify.
     *
     * @return array|string Respuesta de la API con la lista de órdenes.
     */
    public function get()
    {
        return $this->client->get("orders.json");
    }

    /**
     * Crea una nueva orden en Shopify.
     *
     * @param array $data Datos de la orden a crear (ej. line_items, customer, billing_address, etc.).
     * @return array|string Respuesta de la API con los detalles de la orden creada.
     */
    public function post(array $data)
    {
        return $this->client->post("orders.json", $data);
    }

    /**
     * Actualiza una orden existente en Shopify.
     *
     * @param string $id ID de la orden que se desea actualizar.
     * @param array $data Datos a actualizar en la orden.
     * @return array|string Respuesta de la API con la orden actualizada.
     */
    public function put(string $id, array $data)
    {
        return $this->client->put("orders/$id.json", $data);
    }

    /**
     * Elimina una orden en Shopify.
     *
     * @param string $id ID de la orden que se desea eliminar.
     * @return array|string Respuesta de la API confirmando la eliminación.
     */
    public function delete(string $id)
    {
        return $this->client->delete("orders/$id.json");
    }
}
