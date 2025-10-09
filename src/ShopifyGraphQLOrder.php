<?php

namespace franciscoblancojn\AveConnectShopify;

use function franciscoblancojn\validator\FValidator;

class ShopifyGraphQLOrder
{
    private ShopifyGraphQLClient $client;

    public function __construct(ShopifyGraphQLClient $client)
    {
        $this->client = $client;
    }

    /**
     * Cancela una orden en Shopify vía API GraphQL
     *
     * @param string $orderId ID global de Shopify (ej: gid://shopify/Order/123456789)
     * @param string $reason Motivo de cancelación (CUSTOMER, DECLINED, FRAUD, INVENTORY, OTHER)
     * @param bool $refund Indica si debe intentar reembolsar (default false)
     *
     * @return array Respuesta de Shopify GraphQL
     * @throws \Exception
     */
    public function cancelOrder(string $orderId, string $reason, bool $refund = false, bool $restock = true): array
    {
        $validReasons = ['CUSTOMER', 'DECLINED', 'FRAUD', 'INVENTORY', 'OTHER'];
        if (!in_array($reason, $validReasons, true)) {
            throw new \InvalidArgumentException(
                "Reason inválido. Debe ser uno de: " . implode(', ', $validReasons)
            );
        }

        $mutation = <<<GRAPHQL
    mutation orderCancel(\$orderId: ID!, \$reason: OrderCancelReason!, \$refund: Boolean, \$restock: Boolean!) {
      orderCancel(orderId: \$orderId, reason: \$reason, refund: \$refund, restock: \$restock) {
        job {
          id
        }
        userErrors {
          field
          message
        }
      }
    }
    GRAPHQL;

        $variables = [
            'orderId' => "gid://shopify/Order/" . $orderId,
            'reason'  => $reason,
            'refund'  => $refund,
            'restock' => $restock,
        ];

        $response = $this->client->query($mutation, $variables);

        if (
            isset($response['data']['orderCancel']['userErrors']) &&
            count($response['data']['orderCancel']['userErrors']) > 0
        ) {
            throw new \Exception(
                "Shopify API error: " . json_encode($response['data']['orderCancel']['userErrors'])
            );
        }

        return $response ?? [];
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
