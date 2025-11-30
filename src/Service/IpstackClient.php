<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpstackClient
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $baseUrl;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, string $baseUrl)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function fetchIpData(string $ip): array
    {
        $response = $this->httpClient->request('GET', sprintf('%s/%s', $this->baseUrl, $ip), [
            'query' => [
                'access_key' => $this->apiKey,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to fetch IP data from ipstack');
        }

        $data = $response->toArray(false);

        if (isset($data['error'])) {
            throw new \RuntimeException($data['error']['info'] ?? 'ipstack error');
        }

        return $data;
    }
}
