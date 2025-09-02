<?php

namespace franciscoblancojn\AveConnectShopify;

use function franciscoblancojn\validator\FValidator;

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
     * Validador para crear una orden en Shopify (REST API), según OrderCreateOrderInput.
     *
     * @return FValidator_Class
     */
    public function validatorPost()
    {
        return FValidator('orderPost')->isObject([
            'order' => FValidator('order')->isObject([
                // Cliente
                'email' => FValidator('email')
                    ->isRequired('El email de la orden es obligatorio')
                    ->isEmail('El email debe ser válido'),
                'phone' => FValidator('phone')
                    ->isString('El teléfono debe ser texto'),

                // Datos del cliente anidado
                'customer' => FValidator('customer')
                    ->isObject([
                        'email'      => FValidator('customer.email')->isEmail('Email cliente inválido'),
                        'first_name' => FValidator('customer.first_name')->isString('Nombre debe ser texto'),
                        'last_name'  => FValidator('customer.last_name')->isString('Apellido debe ser texto'),
                        'phone'      => FValidator('customer.phone')->isString('Phone cliente debe ser texto'),
                    ], 'customer debe ser un objeto válido'),

                // line_items obligatorios
                'line_items' => FValidator('line_items')
                    ->isRequired('Debe haber al menos 1 línea de ítem')
                    ->isArray(
                        FValidator('line_item')->isObject([
                            // Si es producto existente
                            'variant_id' => FValidator('variant_id')->isNumber('variant_id debe ser numérico'),
                            // Si es un nuevo producto
                            'title'   => FValidator('title')->isString('title debe ser texto'),
                            'price'   => FValidator('price')->isNumber('price debe ser numérico'),
                            'grams'   => FValidator('grams')->isNumber('grams debe ser numérico'),
                            'sku'     => FValidator('sku')->isString('sku debe ser texto'),
                            'quantity' => FValidator('quantity')
                                ->isRequired('quantity es obligatorio')
                                ->isNumber('quantity debe ser numérico'),

                            // tax_lines si están presentes
                            'tax_lines' => FValidator('tax_lines')
                                ->isArray(
                                    FValidator('tax_line')->isObject([
                                        'price' => FValidator('tax_line.price')->isNumber('price tax debe ser numérico'),
                                        'rate'  => FValidator('tax_line.rate')->isNumber('rate debe ser numérico'),
                                        'title' => FValidator('tax_line.title')->isString('title tax debe ser texto'),
                                    ], 'tax_line debe ser un objeto válido'),
                                    'tax_lines debe ser un array de objetos'
                                ),
                        ], 'Cada item debe ser un objeto válido'),
                        'line_items debe ser un array de objetos'
                    ),

                // transactions opcionales
                'transactions' => FValidator('transactions')
                    ->isArray(
                        FValidator('transaction')->isObject([
                            'kind'   => FValidator('transaction.kind')->isString('kind debe ser texto'),
                            'status' => FValidator('transaction.status')->isString('status debe ser texto'),
                            'amount' => FValidator('transaction.amount')->isNumber('amount debe ser numérico'),
                        ], 'transaction debe ser un objeto válido'),
                        'transactions debe ser un array de objetos'
                    ),

                'currency' => FValidator('currency')
                    ->isRequired('currency es obligatorio')
                    ->isString('currency debe ser texto')
                    ->isRegex('/^[A-Z]{3}$/', 'currency debe ser código ISO 4217 (3 letras)'),

            ], 'order debe ser un objeto válido'),
        ]);
    }

    /**
     * Crea una nueva orden en Shopify.
     *
     * @param array $data Datos de la orden a crear (ej. line_items, customer, billing_address, etc.).
     * @return array|string Respuesta de la API con los detalles de la orden creada.
     */
    public function post(array $data)
    {
        $this->validatorPost()->validate($data);
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
