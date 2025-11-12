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
     * Cliente HTTP para interactuar con la API de Shopify.
     *
     * @var ShopifyGraphQLMetafield
     */
    private ShopifyGraphQLMetafield $metafield;

    public function __construct(ShopifyGraphQLClient $client)
    {
        $this->client = $client;
        $this->metafield = new ShopifyGraphQLMetafield($client);
    }

    function normalizeOrderId($order_id)
    {
        $value = preg_replace('/\D/', '', $order_id);
        // Si solo viene el número, lo formateamos
        return "gid://shopify/Order/{$value}";
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
            isset($response['orderCancel']['userErrors']) &&
            count($response['orderCancel']['userErrors']) > 0
        ) {
            throw new \Exception(
                "Shopify API error: " . json_encode($response['orderCancel']['userErrors'])
            );
        }

        return $response ?? [];
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
        // save metafield
        if ($data['order']['id']) {
            $this->sync($data['order']['id'],"completed","Sincronizado correctamente");
        }
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



    public function closeOrder(string $orderId): array
    {
        $mutation = <<<GRAPHQL
            mutation OrderClose(\$input: OrderCloseInput!) {
                orderClose(input: \$input) {
                    order {
                        id
                        closedAt
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $variables = [
            'input' => [
                'id' => $this->normalizeOrderId($orderId),
            ],
        ];

        $response = $this->client->query($mutation, $variables);
        return $response;
    }
    public function openOrder(string $orderId): array
    {
        $mutation = <<<GRAPHQL
            mutation OrderOpen(\$input: OrderOpenInput!) {
                orderOpen(input: \$input) {
                    order {
                        id
                        closedAt
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $variables = [
            'input' => [
                'id' => $this->normalizeOrderId($orderId),
            ],
        ];

        $response = $this->client->query($mutation, $variables);
        return $response;
    }
    public function getLocationId(): ?string
    {
        $query = <<<GRAPHQL
            query getLocations {
                locations(first: 1) {
                    nodes {
                        id
                        name
                    }
                }
            }
        GRAPHQL;

        $response = $this->client->query($query);

        return $response['locations']['nodes'][0]['id'] ?? null;
    }
    public function getFulfillmentOrderId(string $orderId): ?string
    {
        $query = <<<GRAPHQL
            query getFulfillmentOrders(\$orderId: ID!) {
                order(id: \$orderId) {
                    id
                    name
                    fulfillmentOrders(first: 10) {
                        nodes {
                            id
                            status
                            assignedLocation {
                                location {
                                    id
                                    name
                                }
                            }
                            lineItems(first: 10) {
                                nodes {
                                    id
                                    remainingQuantity
                                    totalQuantity
                                    lineItem {
                                        title
                                        fulfillableQuantity
                                        requiresShipping
                                    }
                                }
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        // 🔹 Ejecutamos la query
        $response = $this->client->query($query, [
            'orderId' => $this->normalizeOrderId($orderId),
        ]);

        // 🔹 Validaciones
        if (empty($response['order'])) {
            throw new \Exception("No se encontró la orden con ID {$orderId} en Shopify.");
        }

        $fulfillmentOrders = $response['order']['fulfillmentOrders']['nodes'] ?? [];

        if (empty($fulfillmentOrders)) {
            throw new \Exception("La orden {$orderId} no tiene fulfillment orders (posiblemente no requiere envío o aún no se asignó ubicación).");
        }

        // 🔹 Devuelve el primero (puedes ajustar esto si necesitas otro criterio)
        return $fulfillmentOrders[0]['id'] ?? null;
    }

    public function fulfillOrder(string $orderId, ?string $trackingNumber = null, ?string $trackingUrl = null): array
    {
        $fulfillmentOrderId = $this->getFulfillmentOrderId($orderId);
        if (!$fulfillmentOrderId) {
            throw new \Exception("No se encontró el Fulfillment Order para la orden {$orderId}");
        }
        
        $mutation = <<<GRAPHQL
            mutation FulfillmentCreateV2(\$fulfillment: FulfillmentV2Input!) {
                fulfillmentCreateV2(fulfillment: \$fulfillment) {
                    fulfillment {
                        id
                        status
                        trackingInfo {
                            number
                            url
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GRAPHQL;

        $variables = [
            'fulfillment' => [
                'trackingInfo' => [
                    'number' => $trackingNumber,
                    'url' => $trackingUrl,
                ],
                'lineItemsByFulfillmentOrder' => [
                    [
                        'fulfillmentOrderId' => $fulfillmentOrderId,
                    ],
                ],
            ],
        ];

        return $this->client->query($mutation, $variables);
    }
    public function sync(string $id,string $status,string $message)
    {
        $this->metafield->set([
            "ownerId" => $this->normalizeOrderId($id),
            "value" => [
                "status" => $status,
                "message" => $message
            ]
        ]);
    }
}
