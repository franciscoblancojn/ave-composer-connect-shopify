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
     * Genera el validador para los datos de creación de una orden (POST).
     * 
     * Define las reglas de validación para todos los campos requeridos y opcionales
     * al crear una nueva orden, incluyendo información del cliente, direcciones, 
     * artículos de línea, pagos y opciones adicionales.
     *
     * @return mixed Instancia del validador configurado con todas las reglas de validación.
     * 
     * Campos validados:
     * - customer: Información del cliente (nombre, email, teléfono)
     * - billing_address: Dirección de facturación (opcional si es igual a shipping_address)
     * - shipping_address: Dirección de envío (obligatoria si se requiere envío físico)
     * - email: Correo electrónico del cliente (obligatorio, formato válido)
     * - phone: Número de teléfono del cliente (opcional, formato válido)
     * - line_items: Artículos de la orden (obligatorio, mínimo 1)
     *     - variant_id: ID de la variante del producto (obligatorio)
     *     - quantity: Cantidad solicitada (obligatorio, entero > 0)
     *     - price: Precio unitario (opcional, se puede calcular automáticamente)
     * - shipping_lines: Métodos de envío seleccionados (opcional)
     * - discount_codes: Códigos de descuento aplicados (opcional)
     * - note: Nota interna de la orden (opcional)
     * - tags: Etiquetas asociadas a la orden (opcional)
     * - transactions: Información de pagos (opcional si es pago manual)
     */

    public function validatorPost()
    {
        return FValidator('orderPost')->isObject([
            'order' => FValidator('order')->isObject([
                'note' => FValidator('note')
                    ->isString('La Nota debe ser texto'),
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
                

                // Datos del direccion de envio
                'shipping_address' => FValidator('shipping_address')
                    ->isObject([
                        'address1' => FValidator('shipping_address.address1')->isString('Direccion debe ser texto'),
                        'address2' => FValidator('shipping_address.address2')->isString('Direccion 2 debe ser texto'),
                        'city' => FValidator('shipping_address.city')->isString('Ciudad debe ser texto'),
                        'countryCode' => FValidator('shipping_address.countryCode')->isString('Codigo de pais debe ser texto'),
                        'firstName' => FValidator('shipping_address.firstName')->isString('Nombre debe ser texto'),
                        'lastName' => FValidator('shipping_address.lastName')->isString('Apellido debe ser texto'),
                        'phone' => FValidator('shipping_address.phone')->isString('Telefono debe ser texto'),
                        'zip' => FValidator('shipping_address.zip')->isString('Zip debe ser texto'),
                    ], 'customer debe ser un objeto válido'),


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
     * Genera el validador para los datos de actualizacion de una orden (PUT).
     * 
     * Define las reglas de validación para todos los campos requeridos y opcionales
     * al actualizar una orden.
     *
     * @return mixed Instancia del validador configurado con todas las reglas de validación.
     * 
     * Campos validados:
     * - customer: Información del cliente (nombre, email, teléfono)
     * - shipping_address: Dirección de envío (obligatoria si se requiere envío físico)
     * - email: Correo electrónico del cliente (obligatorio, formato válido)
     * - note: Nota interna de la orden (opcional)
     */

    public function validatorPut()
    {
        return FValidator('orderPut')->isObject([
            'input' => FValidator('order')->isObject([
                'note' => FValidator('note')
                    ->isString('La Nota debe ser texto'),
                // Cliente
                'email' => FValidator('email')
                    ->isEmail('El email debe ser válido'),

                // Datos del cliente anidado
                'customer' => FValidator('customer')
                    ->isObject([
                        'email'      => FValidator('customer.email')->isEmail('Email cliente inválido'),
                        'first_name' => FValidator('customer.first_name')->isString('Nombre debe ser texto'),
                        'last_name'  => FValidator('customer.last_name')->isString('Apellido debe ser texto'),
                        'phone'      => FValidator('customer.phone')->isString('Phone cliente debe ser texto'),
                    ], 'customer debe ser un objeto válido'),

                // Datos del direccion de envio
                'shipping_address' => FValidator('shipping_address')
                    ->isObject([
                        'address1' => FValidator('shipping_address.address1')->isString('Direccion debe ser texto'),
                        'address2' => FValidator('shipping_address.address2')->isString('Direccion 2 debe ser texto'),
                        'city' => FValidator('shipping_address.city')->isString('Ciudad debe ser texto'),
                        'countryCode' => FValidator('shipping_address.countryCode')->isString('Codigo de pais debe ser texto'),
                        'firstName' => FValidator('shipping_address.firstName')->isString('Nombre debe ser texto'),
                        'lastName' => FValidator('shipping_address.lastName')->isString('Apellido debe ser texto'),
                        'phone' => FValidator('shipping_address.phone')->isString('Telefono debe ser texto'),
                        'zip' => FValidator('shipping_address.zip')->isString('Zip debe ser texto'),
                    ], 'customer debe ser un objeto válido'),

            ], 'order debe ser un objeto válido'),
        ]);
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
        $this->validatorPut()->validate($data);
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
