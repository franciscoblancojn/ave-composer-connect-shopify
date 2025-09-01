<?php

namespace franciscoblancojn\AveConnectShopify;

class HttpClient
{
    private string $version = '2025-01';
    private string $shop;
    private string $token;

    public function __construct(string $shop, string $token, $version = '2025-01')
    {
        $this->shop = $shop;
        $this->token = $token;
        $this->version = $version;
    }

    private function request(string $method, string $endpoint, array $data = [])
    {
        $url = "https://{$this->shop}/admin/api/{$this->version}/{$endpoint}";
        $headers = [
            "X-Shopify-Access-Token: {$this->token}",
            "Content-Type: application/json"
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Request error: " . $error);
        }

        return json_decode($response, true);
    }

    public function get(string $endpoint)
    {
        return $this->request('GET', $endpoint);
    }

    public function post(string $endpoint, array $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function put(string $endpoint, array $data = [])
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete(string $endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }
}
