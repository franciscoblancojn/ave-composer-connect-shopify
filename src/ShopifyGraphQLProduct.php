<?php

namespace franciscoblancojn\AveConnectShopify;

use function franciscoblancojn\validator\FValidator;

/**
 * Clase de servicio para gestionar productos en Shopify mediante la API GraphQL.
 * 
 * Proporciona métodos para:
 * - Listar productos con filtros.
 * - Crear nuevos productos.
 * - Actualizar productos existentes.
 * - Eliminar productos.
 * 
 * Utiliza un cliente `ShopifyGraphQLClient` para enviar queries y mutations.
 */
class ShopifyGraphQLProduct
{
    /**
     * Cliente HTTP para interactuar con la API de Shopify.
     *
     * @var ShopifyGraphQLClient
     */
    private ShopifyGraphQLClient $client;

    /**
     * Constructor de la clase.
     *
     * @param ShopifyGraphQLClient $client Cliente configurado con token y shop URL.
     */
    public function __construct(ShopifyGraphQLClient $client)
    {
        $this->client = $client;
    }

    /**
     * Obtiene una lista de productos desde Shopify.
     * 
     * Ejecuta una query GraphQL para traer productos con sus variantes.
     *
     * @param array $filters Filtros opcionales para la query (ej. ['first' => 10]).
     * 
     * @return array Respuesta de la API de Shopify con productos y variantes.
     */
    public function get(array $filters = ['first' => 10])
    {
        $query = <<<GRAPHQL
            query getProducts(\$first: Int!) {
                products(first: \$first) {
                    edges {
                    node {
                        id
                        title
                        descriptionHtml
                        vendor
                        productType
                        createdAt
                        handle
                        updatedAt
                        publishedAt
                        status
                        tags
                        variants(first: 20) {
                        edges {
                            node {
                            id
                            title
                            price
                            position
                            inventoryPolicy
                            compareAtPrice
                            sku
                            barcode
                            createdAt
                            updatedAt
                            taxable
                            inventoryItem {
                                id
                                tracked
                            }
                            }
                        }
                        }
                        options {
                        id
                        name
                        values
                        }
                        images(first: 10) {
                        edges {
                            node {
                            id
                            src: url
                            }
                        }
                        }
                        featuredImage {
                        id
                        src: url
                        }
                    }
                    }
                }
            }  
        GRAPHQL;

        $result = $this->client->query($query, $filters);

        // Transformar para que se parezca a REST
        $products = [];
        foreach ($result['products']['edges'] as $edge) {
            $node = $edge['node'];

            $products[] = [
                "id" => str_replace("gid://shopify/Product/", "", $node['id']),
                "title" => $node['title'],
                "descriptionHtml" => $node['descriptionHtml'],
                "vendor" => $node['vendor'],
                "productType" => $node['productType'],
                "created_at" => $node['createdAt'],
                "handle" => $node['handle'],
                "updated_at" => $node['updatedAt'],
                "published_at" => $node['publishedAt'],
                "template_suffix" => null, // no existe en GraphQL
                "published_scope" => "web", // puedes setear fijo
                "tags" => implode(",", $node['tags']),
                "status" => strtolower($node['status']),
                "admin_graphql_api_id" => $node['id'],
                "variants" => array_map(fn($v) => [
                    "id" => str_replace("gid://shopify/ProductVariant/", "", $v['node']['id']),
                    "title" => $v['node']['title'],
                    "price" => $v['node']['price'],
                    "sku" => $v['node']['sku'],
                    "barcode" => $v['node']['barcode'],
                    "weight" => $v['node']['weight'],
                    "weight_unit" => $v['node']['weightUnit'],
                    "inventory_item_id" => str_replace("gid://shopify/InventoryItem/", "", $v['node']['inventoryItem']['id']),
                    "inventoryQuantity" => $v['node']['inventoryItem']['inventoryLevels']['edges'][0]['node']['available'] ?? 0,
                ], $node['variants']['edges']),
                "options" => $node['options'],
                "images" => array_map(fn($i) => ["id" => $i['node']['id'], "src" => $i['node']['src']], $node['images']['edges']),
                "image" => $node['featuredImage'] ? ["id" => $node['featuredImage']['id'], "src" => $node['featuredImage']['src']] : null
            ];
        }

        return ["products" => $products];
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
                    ->isEnum(['ACTIVE', 'ARCHIVED', 'DRAFT'], 'Status debe ser active, archived o draft'),

                'tags' => FValidator('tags')
                    ->isString('Los tags deben ser un string separado por comas'),

                'published_scope' => FValidator('published_scope')
                    ->isEnum(['web', 'global'], 'published_scope debe ser web o global'),

                // Variantes
                'variants' => FValidator('variants')
                    // ->isRequired('Las variantes son obligatorias')
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
                            'compareAtPrice' => FValidator('variant.compareAtPrice')
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
                            'inventoryQuantity' => FValidator('variant.inventoryQuantity')
                                ->isNumber('inventoryQuantity debe ser un número')
                                ->isMin(0, 'inventoryQuantity debe ser positivo'),
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
                            'altText' => FValidator('image.altText')
                                ->isString('El texto altTexternativo debe ser texto'),
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
                        'altText' => FValidator('main_image.altText')
                            ->isString('El texto altTexternativo debe ser texto'),
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
     * Crea un nuevo producto en Shopify mediante una mutation GraphQL.
     *
     * @param array $data Datos del producto a crear (estructura validada con validatorPost).
     * 
     * @return array Respuesta de la API de Shopify, incluyendo el producto creado o errores.
     */
    public function post(array $data): array
    {
        $this->validatorPost()->validate($data);
        // 1️⃣ Mutación para crear el producto base
        $mutationProduct = <<<GRAPHQL
            mutation productCreate(\$input: ProductInput!) {
                productCreate(input: \$input) {
                    product {
                        id
                        title
                        status
                        handle
                        options {
                            id
                            name
                            position
                            optionValues {
                                id
                                name
                                hasVariants
                            }
                        }
                        variants(first: 1) {
                            nodes {
                                id
                            }
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $productOptions = null;
        if ($data['product']['options']) {
            $productOptions = [];
            foreach ($data['product']['options'] as $keyOption => $option) {
                $optionData = [
                    "name" => $option['name'],
                    'values' => [
                        [
                            "name" => "_"
                        ]
                    ]
                ];
                $productOptions[] = $optionData;
            }
        }
        // Construimos el input para ProductInput
        $productInput = [
            'title' => $data['product']['title'] ?? null,
            'descriptionHtml' => $data['product']['body_html'] ?? null,
            'vendor' => $data['product']['vendor'] ?? null,
            'productType' => $data['product']['product_type'] ?? null,
            'handle' => $data['product']['handle'] ?? null,
            'tags' => $data['product']['tags'] ?? null,
            'status' => $data['product']['status'] ?? null,
            'productOptions' => $productOptions,
        ];

        $response = $this->client->query($mutationProduct, [
            'input' => $productInput,
        ]);
        // Si hay error en la creación del producto, devolvemos
        if (!empty($response['productCreate']['userErrors'])) {
            return $response;
        }
        $product = $response['productCreate']['product'] ?? null;
        $productId = $product['id'] ?? null;
        $variantIdBase = $product['variants']['nodes'][0]['id'] ?? null;




        // 2️⃣ Crear imágenes si existen
        $images = array_merge($data['product']['image'] ? [$data['product']['image']] : [], $data['product']['images'] ?? []);
        $imagesResult = [];
        if ($productId && !empty($images)) {
            $mutationImage = <<<GRAPHQL
                mutation productCreateMedia(\$media: [CreateMediaInput!]!, \$productId: ID!) {
                    productCreateMedia(media: \$media, productId: \$productId) {
                        media {
                            id
                            alt
                            mediaContentType
                            status
                        }
                        mediaUserErrors {
                            field
                            message
                        }
                        product {
                            id
                            title
                        }
                    }
                }
            GRAPHQL;
            $imagesSends = [];
            foreach ($images as $key => $image) {
                $imagesSends[] = [
                    "alt" => $image['alt'],
                    "mediaContentType" => "IMAGE",
                    "originalSource" => $image['src'],
                ];
            }
            $imagesResult = $this->client->query($mutationImage, [
                "productId" => $productId,
                "media" => $imagesSends
            ]);
            if ($imagesResult && $imagesResult['productCreateMedia'] && $imagesResult['productCreateMedia']['media']) {
                $imagesResult = $imagesResult['productCreateMedia']['media'];
            }
            $response['imagesResult'] = $imagesResult;
        }
        $variants = [];
        // 3️⃣ Crear variantes si existen
        if ($productId && !empty($data['product']['variants'])) {
            $variantsBulk = [];
            foreach ($data['product']['variants'] as $variant) {
                $variantData = [
                    'inventoryItem' => [
                        // 'title' => $variant['title'],
                        'sku' => $variant['sku'],
                    ],
                    'price' => (float)($variant['price'] ?? 0.00),
                    'compareAtPrice' => (float)($variant['compare_at_price'] ?? 0.00),
                ];
                $options = [];
                if (!empty($variant['option1'])) {
                    $options[] = [
                        "name" => $variant['option1'],
                        "optionName" => $productOptions[0]['name'],
                    ];
                }
                if (!empty($variant['option2'])) {
                    $options[] = [
                        "name" => $variant['option2'],
                        "optionName" => $productOptions[1]['name'] ?? 'Option2',
                    ];
                }
                if (!empty($variant['option3'])) {
                    $options[] = [
                        "name" => $variant['option3'],
                        "optionName" => $productOptions[2]['name'] ?? 'Option3',
                    ];
                }

                $imageId = null;
                if ($data['product']['image']) {
                    $imageId = $imagesResult[0]['id'];
                }
                foreach ($imagesResult as $key => $img) {
                    if ($img['alt'] == $variant['sku']) {
                        $imageId = $img['id'];
                    }
                }
                if ($imageId) {
                    $variantData['mediaId'] = $imageId;
                }
                $variantData['optionValues'] = $options;
                $variantsBulk[] = $variantData;
            }

            $mutationVariantsBulk = <<<GRAPHQL
                mutation ProductVariantsCreate(\$productId: ID!, \$variants: [ProductVariantsBulkInput!]!) {
                    productVariantsBulkCreate(productId: \$productId, variants: \$variants) {
                        productVariants {
                            id
                            title
                            inventoryItem {
                                sku
                            }
                            selectedOptions {
                                name
                                value
                            }
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }
            GRAPHQL;

            $variants = $this->client->query($mutationVariantsBulk, [
                'productId' => $productId, // ejemplo: "gid://shopify/Product/1234567890"
                'variants'  => $variantsBulk,
            ]);
            $variants['variantsBulk'] = $variantsBulk;
            if ($variants && $variants['productVariantsBulkCreate'] && $variants['productVariantsBulkCreate']['productVariants']) {
                $variantsResult = [];
                foreach ($variants['productVariantsBulkCreate']['productVariants'] as $key => $value) {
                    $v = $value;
                    $v['sku'] = $value['inventoryItem']['sku'];
                    $variantsResult[] = $v;
                }
                $variants = $variantsResult;
            }
        }
        $response['variants'] = $variants;


        $mutationDeleteValidation = <<<GRAPHQL
            mutation bulkDeleteProductVariants(\$productId: ID!, \$variantsIds: [ID!]!) {
                productVariantsBulkDelete(productId: \$productId, variantsIds: \$variantsIds) {
                    product {
                        id
                        title
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;
        $responseValidationDeleted = $this->client->query($mutationDeleteValidation, [
            "productId" => $productId,
            "variantsIds" => [
                $variantIdBase
            ]
        ]);
        $response['responseValidationDeleted'] = $responseValidationDeleted;

        return $response;
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
                    ->isEnum(['ACTIVE', 'ARCHIVED', 'DRAFT'], 'Status debe ser active, archived o draft'),

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
     * @param string $id ID global del producto (formato gid://shopify/Product/123...).
     * @param array $data Datos del producto a actualizar (estructura validada con validatorPut).
     * 
     * @return array Respuesta de la API de Shopify, incluyendo el producto actualizado o errores.
     */
    public function put(array $data): array
    {
        $this->validatorPut()->validate($data);

        // 1️⃣ Mutación para actualizar información base del producto
        $mutationUpdate = <<<GRAPHQL
            mutation productUpdate(\$input: ProductInput!) {
                productUpdate(input: \$input) {
                    product {
                        id
                        title
                        descriptionHtml
                        vendor
                        productType
                        tags
                        status
                        options {
                            id
                            name
                            optionValues {
                                id
                                name
                            }
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $productInput = [
            "id"            => $data["product"]['id'],
            "title"         => $data["product"]["title"] ?? null,
            "descriptionHtml" => $data["product"]["descriptionHtml"] ?? null,
            "vendor"        => $data["product"]["vendor"] ?? null,
            "productType"   => $data["product"]["productType"] ?? null,
            "tags"          => $data["product"]["tags"] ?? null,
            "status"        => $data["product"]["status"] ?? null,
            // "options"       => $data["product"]["options"] ?? null,
            // "metafields"    => $data["product"]["metafields"] ?? null,
        ];

        $response = $this->client->query($mutationUpdate, [
            "input" => $productInput
        ]);

        // 2️⃣ Actualizar imágenes (si las hay)
        $images = array_merge($data['product']['image'] ? [$data['product']['image']] : [], $data['product']['images'] ?? []);
        $imagesResult = [];
        if (!empty($images)) {
            $mutationImage = <<<GRAPHQL
                mutation productCreateMedia(\$media: [CreateMediaInput!]!, \$productId: ID!) {
                    productCreateMedia(media: \$media, productId: \$productId) {
                        media {
                            id
                            alt
                            mediaContentType
                            status
                        }
                        mediaUserErrors {
                            field
                            message
                        }
                        product {
                            id
                            title
                        }
                    }
                }
            GRAPHQL;

            $imagesSends = [];
            foreach ($images as $image) {
                $imagesSends[] = [
                    "alt"               => $image["alt"] ?? null,
                    "mediaContentType"  => "IMAGE",
                    "originalSource"    => $image["src"],
                ];
            }

            $imagesResult = $this->client->query($mutationImage, [
                "productId" => $data['product']['id'],
                "media"     => $imagesSends,
            ]);
            $response["imagesSends"] = $imagesSends;
            $response["imagesResult"] = $imagesResult;
        }

        // 3️⃣ Actualizar variantes (si las hay)
        if (!empty($data["product"]["variants"])) {
            $mutationVariants = <<<GRAPHQL
                mutation ProductVariantsBulkUpdate(\$productId: ID!, \$variants: [ProductVariantsBulkInput!]!) {
                    productVariantsBulkCreate(productId: \$productId, variants: \$variants) {
                        productVariants {
                            id
                            title
                            inventoryItem {
                                sku
                            }
                            selectedOptions {
                                name
                                value
                            }
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }
            GRAPHQL;

            $variantsBulk = [];
            foreach ($data["product"]["variants"] as $variant) {
                $variantData = [
                    "inventoryItem" => [
                        "sku" => $variant["sku"] ?? null,
                    ],
                    "price"         => (float)($variant["price"] ?? 0.00),
                    "compareAtPrice" => (float)($variant["compareAtPrice"] ?? 0.00),
                ];

                if (!empty($variant["optionValues"])) {
                    $variantData["optionValues"] = $variant["optionValues"];
                }

                if (!empty($variant["image_id"])) {
                    $variantData["mediaId"] = $variant["image_id"];
                }

                $variantsBulk[] = $variantData;
            }

            $variantsResult = $this->client->query($mutationVariants, [
                "productId" => $data['product']['id'],
                "variants"  => $variantsBulk,
            ]);

            $response["variantsResult"] = $variantsResult;
        }

        return $response;
    }

    /**
     * Elimina un producto de Shopify.
     *
     * @param string $id ID global del producto (formato gid://...).
     * 
     * @return array Respuesta de la API de Shopify con el ID eliminado o errores.
     */
    public function delete(string $id): array
    {
        $mutation = <<<GRAPHQL
            mutation productDelete(\$input: ProductDeleteInput!) {
                productDelete(input: \$input) {
                    deletedProductId
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        return $this->client->query($mutation, [
            'input' => ['id' => $id],
        ]);
    }
}
