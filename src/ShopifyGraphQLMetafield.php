<?php

namespace franciscoblancojn\AveConnectShopify;

use function franciscoblancojn\validator\FValidator;


class ShopifyGraphQLMetafield
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

    public function validatorSet()
    {
        return FValidator("metafield")->isObject([
            'ownerId' => FValidator('ownerId')
                ->isRequired('El ownerId es obligatorio')
                ->isString('El ownerId debe ser texto'),
            'value' => FValidator('value')
                ->isRequired('El value es obligatorio')
                ->isObject('El value debe ser texto'),

        ], "El metafield debe ser un objeto válido");
    }
    public function set(array $data): array
    {
        // $this->validatorSet()->validate($data);
        $mutationProduct = <<<GRAPHQL
            mutation metafieldsSet(\$metafields: [MetafieldsSetInput!]!) {
                metafieldsSet(metafields: \$metafields) {
                    metafields {
                        id
                        namespace
                        key
                        value
                        type
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $dataInput = [
            'metafields' => [
                [
                    "ownerId" => $data['ownerId'],
                    "namespace" => $data['namespace'] ?? AveConnectShopifyConst::$namespace,
                    "key" => $data['key'] ?? AveConnectShopifyConst::$key,
                    "type" => "json",
                    "value" => json_encode($data['value']),
                ]
            ]
        ];

        $response = $this->client->query($mutationProduct, [
            'input' => $dataInput,
        ]);

        return $response;
    }
}
