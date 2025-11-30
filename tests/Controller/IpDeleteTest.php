<?php

namespace App\Tests\Controller;

use App\Tests\BaseWebTestCase;

class IpDeleteTest extends BaseWebTestCase
{
    public function testDeleteExistingIpRemovesFromCache(): void
    {
        $client = static::createClient();
        $this->prepareEnvironment();

        $ip = '8.8.4.4';

        # 1) Pirmas GET – sukuria įrašą DB (duomenis grąžins MOCK'as)
        $client->request('GET', '/api/ip/' . $ip);
        $this->assertResponseIsSuccessful();

        # 2) DELETE – ištrinam iš DB
        $client->request('DELETE', '/api/ip/' . $ip);
        $this->assertResponseIsSuccessful();

        # 3) Vėl GET – vėl turi būti 200 (vėl sukurs naują įrašą)
        $client->request('GET', '/api/ip/' . $ip);
        $this->assertResponseIsSuccessful();
    }
}
