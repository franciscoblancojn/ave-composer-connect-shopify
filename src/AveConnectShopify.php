<?php

namespace franciscoblancojn\AveConnectShopify;

use franciscoblancojn\ShopifyHttpClient\ShopifyHttpClient;
use franciscoblancojn\ShopifyOrder\ShopifyOrder;
use franciscoblancojn\ShopifyProduct\ShopifyProduct;

class AveConnectShopify
{
    public ShopifyProduct $product;
    public ShopifyOrder $order;

    public function __construct(string $shop, string $token, $version = '2025-01')
    {
        $client = new ShopifyHttpClient($shop, $token, $version);
        $this->product = new ShopifyProduct($client);
        $this->order = new ShopifyOrder($client);
    }
}
