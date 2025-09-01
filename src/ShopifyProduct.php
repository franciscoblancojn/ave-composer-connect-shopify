<?php

namespace franciscoblancojn\AveConnectShopify;

use function franciscoblancojn\validator\FValidator;

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
     * Genera el validador para los datos de creación de producto (POST).
     * 
     * Define las reglas de validación para todos los campos requeridos y opcionales
     * al crear un nuevo producto, incluyendo información básica, variantes, opciones e imágenes.
     *
     * @return mixed Instancia del validador configurado con todas las reglas de validación.
     * 
     * Campos validados:
     * - title: Título del producto (obligatorio, 1-255 caracteres)
     * - body_html: Descripción HTML del producto
     * - handle: Handle URL del producto (solo minúsculas, números y guiones)
     * - vendor: Proveedor/marca del producto
     * - status: Estado del producto (active, archived, draft)
     * - tags: Etiquetas del producto (string separado por comas)
     * - published_scope: Alcance de publicación (web, global)
     * - variants: Array de variantes del producto (obligatorio)
     * - options: Array de opciones del producto
     * - images: Array de imágenes del producto
     * - image: Imagen principal del producto
     */
    public function validatorPost()
    {
        return FValidator("product.post")->isObject([
            "product" => FValidator("product")->isObject([
                // Información básica del producto
                'title' => FValidator('title')
                    ->isRequired('El título del producto es obligatorio')
                    ->isString('El título debe ser texto')
                    ->isRegex('/^.{1,255}$/', 'El título debe tener entre 1 y 255 caracteres'),

                'body_html' => FValidator('body_html')
                    ->isString('La descripción debe ser texto'),

                'handle' => FValidator('handle')
                    ->isString('El handle debe ser texto')
                    ->isRegex('/^[a-z0-9-]*$/', 'El handle solo acepta minúsculas, números y guiones'),

                'vendor' => FValidator('vendor')
                    ->isString('El vendor debe ser texto'),

                'status' => FValidator('status')
                    ->isEnum(['active', 'archived', 'draft'], 'Status debe ser active, archived o draft'),

                'tags' => FValidator('tags')
                    ->isString('Los tags deben ser un string separado por comas'),

                'published_scope' => FValidator('published_scope')
                    ->isEnum(['web', 'global'], 'published_scope debe ser web o global'),

                // Variantes
                'variants' => FValidator('variants')
                    ->isRequired('Las variantes son obligatorias')
                    ->isArray(
                        FValidator()->isObject([
                            'title' => FValidator('variant.title')
                                ->isString('El título de variante debe ser texto'),
                            'price' => FValidator('variant.price')
                                ->isRequired('El precio es obligatorio')
                                ->isString('El precio debe ser texto')
                                ->isRegex('/^\d+\.?\d{0,2}$/', 'Precio con formato decimal válido'),
                            'position' => FValidator('variant.position')
                                ->isNumber('La posición debe ser un número')
                                ->isMin(1, 'La posición debe ser mayor a 0'),
                            'inventory_policy' => FValidator('variant.inventory_policy')
                                ->isEnum(['deny', 'continue'], 'inventory_policy debe ser deny o continue'),
                            'compare_at_price' => FValidator('variant.compare_at_price')
                                ->isString('El precio de comparación debe ser texto')
                                ->isRegex('/^\d+\.?\d{0,2}$/', 'Precio con formato decimal válido'),
                            'option1' => FValidator('variant.option1')
                                ->isString('option1 debe ser texto'),
                            'option2' => FValidator('variant.option2')
                                ->isString('option2 debe ser texto'),
                            'option3' => FValidator('variant.option3')
                                ->isString('option3 debe ser texto'),
                            'taxable' => FValidator('variant.taxable')
                                ->isBoolean('taxable debe ser booleano'),
                            'grams' => FValidator('variant.grams')
                                ->isNumber('Los gramos deben ser un número')
                                ->isMin(0, 'Los gramos deben ser positivos'),
                            'sku' => FValidator('variant.sku')
                                ->isString('El SKU debe ser texto'),
                            'weight' => FValidator('variant.weight')
                                ->isNumber('El peso debe ser un número')
                                ->isMin(0, 'El peso debe ser positivo'),
                            'weight_unit' => FValidator('variant.weight_unit')
                                ->isEnum(['g', 'kg', 'oz', 'lb'], 'Unidad de peso inválida'),
                            'inventory_quantity' => FValidator('variant.inventory_quantity')
                                ->isNumber('inventory_quantity debe ser un número')
                                ->isMin(0, 'inventory_quantity debe ser positivo'),
                            'image_id' => FValidator('variant.image_id')
                                ->isNumber('image_id debe ser un número')
                        ], 'Cada variante debe ser un objeto válido'),
                        'Las variantes deben ser un array'
                    ),

                // Opciones del producto
                'options' => FValidator('options')
                    ->isArray(
                        FValidator()->isObject([
                            'id' => FValidator('option.id')
                                ->isNumber('El ID debe ser un número'),
                            'product_id' => FValidator('option.product_id')
                                ->isNumber('product_id debe ser un número'),
                            'name' => FValidator('option.name')
                                ->isString('El nombre debe ser texto'),
                            'position' => FValidator('option.position')
                                ->isNumber('La posición debe ser un número')
                                ->isMin(1, 'La posición debe ser mayor a 0'),
                            'values' => FValidator('option.values')
                                ->isArray(
                                    FValidator()->isString('Cada valor debe ser texto'),
                                    'Los valores deben ser un array'
                                )
                        ], 'Cada opción debe ser un objeto válido'),
                        'Las opciones deben ser un array'
                    ),

                // Imágenes
                'images' => FValidator('images')
                    ->isArray(
                        FValidator()->isObject([
                            'alt' => FValidator('image.alt')
                                ->isString('El texto alternativo debe ser texto'),
                            'src' => FValidator('image.src')
                                ->isString('La URL debe ser texto')
                                ->isRegex('/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp)$/i', 'URL de imagen inválida'),
                            'variant_ids' => FValidator('image.variant_ids')
                                ->isArray(
                                    FValidator()->isNumber('Cada ID de variante debe ser un número'),
                                    'variant_ids debe ser un array'
                                )
                        ], 'Cada imagen debe ser un objeto válido'),
                        'Las imágenes deben ser un array'
                    ),

                // Imagen principal
                'image' => FValidator('image')
                    ->isObject([
                        'alt' => FValidator('main_image.alt')
                            ->isString('El texto alternativo debe ser texto'),
                        'src' => FValidator('main_image.src')
                            ->isString('La URL debe ser texto')
                            ->isRegex('/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp)$/i', 'URL de imagen inválida'),
                        'variant_ids' => FValidator('main_image.variant_ids')
                            ->isArray(
                                FValidator()->isNumber('Cada ID de variante debe ser un número'),
                                'variant_ids debe ser un array'
                            )
                    ], 'La imagen principal debe ser un objeto válido'),
            ], "El producto debe ser un objeto válido")
        ]);
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
        $this->validatorPost()->validate($data);
        return $this->client->post("products.json", $data);
    }


    /**
     * Genera el validador para los datos de actualización de producto (PUT).
     * 
     * Define las reglas de validación para los campos que pueden ser actualizados
     * en un producto existente. A diferencia del validador POST, este usa la estructura
     * de GraphQL de Shopify con campos como descriptionHtml y productType.
     *
     * @return mixed Instancia del validador configurado con las reglas de validación para actualización.
     * 
     * Campos validados:
     * - id: ID del producto (obligatorio)
     * - title: Título del producto
     * - descriptionHtml: Descripción HTML del producto
     * - vendor: Proveedor/marca del producto
     * - productType: Tipo/categoría del producto
     * - tags: Array de etiquetas del producto
     * - status: Estado del producto (ACTIVE, ARCHIVED, DRAFT)
     * - options: Array de opciones del producto con nombres y valores
     * - metafields: Array de metafields del producto
     */
    public function validatorPut()
    {
        return FValidator("product.put")->isObject([
            "product" => FValidator("product")->isObject([
                "id" => FValidator("id")
                    ->isRequired("El ID es obligatorio")
                    ->isString("El ID debe ser un string"),

                "title" => FValidator("title")
                    ->isString("El título debe ser texto"),

                "descriptionHtml" => FValidator("descriptionHtml")
                    ->isString("La descripción debe ser texto"),

                "vendor" => FValidator("vendor")
                    ->isString("El vendor debe ser texto"),

                "productType" => FValidator("productType")
                    ->isString("El tipo de producto debe ser texto"),

                "tags" => FValidator("tags")
                    ->isArray(
                        FValidator("tag")->isString("Cada tag debe ser un string"),
                        "Los tags deben ser un array de strings"
                    ),

                'status' => FValidator('status')
                    ->isEnum(['active', 'archived', 'draft'], 'Status debe ser active, archived o draft'),

                "options" => FValidator("options")
                    ->isArray(
                        FValidator("option")->isObject([
                            "name" => FValidator("name")->isRequired("El nombre de la opción es obligatorio")->isString("El nombre de la opción debe ser un string"),
                            "values" => FValidator("values")->isArray(
                                FValidator("value")->isString("Cada valor de opción debe ser un string"),
                                "Los valores deben ser un array de strings"
                            ),
                        ], "La opción debe ser un objeto válido"),
                        "Las opciones deben ser un array de objetos"
                    ),

                "metafields" => FValidator("metafields")
                    ->isArray(
                        FValidator("metafield")->isObject([
                            "namespace" => FValidator("namespace")->isString("El namespace debe ser un string"),
                            "key" => FValidator("key")->isString("El key debe ser un string"),
                            "value" => FValidator("value")->isString("El value debe ser un string"),
                            "type" => FValidator("type")->isString("El type debe ser un string válido"),
                        ], "El metafield debe ser un objeto válido"),
                        "Los metafields deben ser un array de objetos"
                    ),
            ], "El producto debe ser un objeto válido")
        ]);
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
        $this->validatorPut()->validate($data);
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
