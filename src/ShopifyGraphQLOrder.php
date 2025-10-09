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



}
