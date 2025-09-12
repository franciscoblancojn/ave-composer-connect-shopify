<?php

namespace franciscoblancojn\AveConnectShopify;

use function franciscoblancojn\validator\FValidator;

class ShopifyVariation
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



    public function validatorPut()
    {
        return FValidator("variant.put")->isObject([
            "variant" => FValidator("variant")->isObject([
                "id" => FValidator("id")
                    ->isRequired("El ID es obligatorio")
                    ->isString("El ID debe ser un string"),

                "title" => FValidator("title")
                    ->isString("El título debe ser texto"),
                "image_id" => FValidator("image_id")
                    ->isNumber("El Id de Imagen debe ser numero"),

            ], "El variacion debe ser un objeto válido")
        ]);
    }


    public function put(string $id, array $data): array
    {
        $this->validatorPut()->validate($data);
        return $this->client->put("variants/$id.json", $data);
    }
}
