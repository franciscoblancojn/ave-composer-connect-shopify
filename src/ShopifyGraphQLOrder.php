<?php

namespace franciscoblancojn\AveConnectShopify;

use function franciscoblancojn\validator\FValidator;


class ShopifyGraphQLOrder
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

    function normalizeOrderId($order_id)
    {
        // Si ya viene en formato GID, lo retornamos tal cual
        if (str_starts_with($order_id, 'gid://shopify/Order/')) {
            return $order_id;
        }

        // Si solo viene el número, lo formateamos
        return "gid://shopify/Order/{$order_id}";
    }

    public function validatorNote()
    {
        return FValidator("order.note")->isObject([
            "order" => FValidator("order")->isObject([
                "id" => FValidator("id")
                    ->isRequired("El ID es obligatorio")
                    ->isString("El ID debe ser un string"),
                'note' => FValidator('note')
                    ->isRequired('La nota es obligatoria')
                    ->isString('La nota debe ser texto'),
            ], "El order debe ser un objeto válido")
        ]);
    }
    public function addNote(array $data): array
    {
        $this->validatorNote()->validate($data);
        // 1️⃣ Mutación para crear nota
        $mutationNote = <<<GRAPHQL
            mutation UpdateOrderNote(\$input: OrderInput!) {
                orderUpdate(input: \$input) {
                    order {
                        id
                        name
                        note
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $data['order']['id'] = $this->normalizeOrderId($data['order']['id']);

        $response = $this->client->query($mutationNote, [
            'input' => $data['order'],
        ]);
        return $response;
    }





    public function validatorTimelineComment()
    {
        return FValidator("order.note")->isObject([
            "order" => FValidator("order")->isObject([
                "id" => FValidator("id")
                    ->isRequired("El ID es obligatorio")
                    ->isString("El ID debe ser un string"),
                'message' => FValidator('note')
                    ->isRequired('La nota es obligatoria')
                    ->isString('La nota debe ser texto'),
            ], "El order debe ser un objeto válido")
        ]);
    }
    public function addTimelineComment(array $data): array
    {
        $this->validatorTimelineComment()->validate($data);
        // 1️⃣ Mutación para crear timeline comment
        $mutationNote = <<<GRAPHQL
            mutation AddTimelineComment(\$input: TimelineCommentCreateInput!) {
                timelineCommentCreate(input: \$input) {
                    timelineComment {
                        id
                        message
                        createdAt
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $data['order']['subjectId'] = $this->normalizeOrderId($data['order']['id']);

        $response = $this->client->query($mutationNote, [
            'input' => $data['order'],
        ]);
        return $response;
    }
    
}
