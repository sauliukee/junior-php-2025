<?php

namespace App\Tests;

use App\Service\IpstackClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseWebTestCase extends WebTestCase
{
    /**
     * Šis paruošimo metodas turi būti iškviečiamas po createClient()
     * ir prieš atliekant pirmą HTTP užklausą teste.
     */

    protected function prepareEnvironment(): void
    {
        # Kernel jau bus užbootintas per createClient()
        $container = static::getContainer();

        # Išvalom DB lenteles
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $connection = $em->getConnection();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['blacklisted_ip', 'ip_address'] as $table) {
            $connection->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        # Mock'inam IpstackClient, kad testai nelįstų į tikrą API
        $mock = $this->createMock(IpstackClient::class);

        $mock->method('fetchIpData')->willReturn([
            'country_name' => 'MockCountry',
            'city'         => 'MockCity',
            'latitude'     => 1.1,
            'longitude'    => 2.2,
        ]);

        $container->set(IpstackClient::class, $mock);
    }
}
