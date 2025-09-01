<?php

namespace franciscoblancojn\ShopifyOrder;

use franciscoblancojn\ShopifyHttpClient\ShopifyHttpClient;

class ShopifyOrder
{
    private ShopifyHttpClient $client;

    public function __construct(ShopifyHttpClient $client)
    {
        $this->client = $client;
    }

    public function get()
    {
        return $this->client->get("orders.json");
    }

    public function post(array $data)
    {
        return $this->client->post("orders.json", $data);
    }

    public function put(string $id, array $data)
    {
        return $this->client->put("orders/$id.json", $data);
    }

    public function delete(string $id)
    {
        return $this->client->delete("orders/$id.json");
    }
}
