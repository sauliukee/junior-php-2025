<?php

namespace App\Tests\Controller;

use App\Tests\BaseWebTestCase;

class IpBlacklistTest extends BaseWebTestCase
{
    public function testBlacklistedIpIsBlockedOnGet(): void
    {
        $client = static::createClient();
        $this->prepareEnvironment();

        $ip = '1.1.1.1';

        # 1) Pridedam į blacklist
        $client->request(
            'POST',
            '/api/blacklist',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ip' => $ip])
        );

        $this->assertResponseIsSuccessful();

        # 2) Bandome gauti info – turi grąžinti 403
        $client->request('GET', '/api/ip/' . $ip);

        $this->assertSame(403, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('IP is blacklisted', $client->getResponse()->getContent());
    }
}
