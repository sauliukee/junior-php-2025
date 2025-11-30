<?php

namespace App\Tests\Controller;

use App\Tests\BaseWebTestCase;

class ApiHealthTest extends BaseWebTestCase
{
    public function testHealthEndpointIsOk(): void
    {
        $client = static::createClient();
        $this->prepareEnvironment();

        $client->request('GET', '/api/health');

        self::assertResponseIsSuccessful();
        self::assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertIsArray($data);
        self::assertArrayHasKey('status', $data);
        self::assertSame('ok', $data['status']);
    }
}
