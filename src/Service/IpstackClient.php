<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Paprastas servisas, kuris wrap'ina ipstack.com API.
 *
 * Čia tvarkau tik:
 *  - URL sudėjimą,
 *  - HTTP užklausos siuntimą,
 *  - atsakymo pavertimą į paprastą PHP masyvą.
 *
 * Visi aukštesnio lygio dalykai (cache, blacklist, IP entity atnaujinimas)
 * laikomi IpService, o ne čia.
 */
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

    /**
     * Paimu duomenis apie IP iš ipstack API.
     *
     * Sėkmės atveju grąžinu json decode'intą masyvą (assoc array).
     * Struktūra tokia, kokią grąžina ipstack (country_name, city,
     * latitude, longitude ir t. t.).
     *
     * @param string $ip IP adresas, kurį norim patikrinti.
     *
     * @return array ipstack atsakymo duomenys.
     *
     * @throws \RuntimeException jei HTTP statusas ne 200
     *                           arba ipstack grąžina error payload.
     */
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


