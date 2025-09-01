<?php

namespace franciscoblancojn\AveConnectShopify;

class ShopifyProduct
{
    private HttpClient $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    public function get()
    {
        return $this->client->get("products.json");
    }

    public function post(array $data)
    {
        return $this->client->post("products.json", $data);
    }

    public function put(string $id, array $data)
    {
        return $this->client->put("products/$id.json", $data);
    }

    public function delete(string $id)
    {
        return $this->client->delete("products/$id.json");
    }
}
