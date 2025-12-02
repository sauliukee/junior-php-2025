<?php

namespace App\Tests;

use App\Service\IpstackClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Bazinė web testų klasė, kuri paruošia švarią aplinką kiekvienam testui.
 *
 * Darbai, kuriuos atlieka ši klasė:
 *  - užbootinti Symfony kernel'į per WebTestCase,
 *  - prieš kiekvieną testą išvalyti IP susijusias lenteles,
 *  - pakeisti tikrą IpstackClient į mock, kad testai nekviestų išorinio API.
 */
abstract class BaseWebTestCase extends WebTestCase
{
    /**
     * Paruošia švarią testinę aplinką.
     *
     * Turi būti kviečiama po static::createClient().
     *
     * Žingsniai:
     *  1. TRUNCATE'inamos lentelės, susijusios su IP entity ir blacklist,
     *     kad nebūtų state leak tarp testų.
     *  2. Laikinai išjungiami foreign key check'ai per TRUNCATE.
     *  3. Į DI container'į įregistruojamas mock IpstackClient, kad visi
     *     testai naudotų fake duomenis vietoje tikro ipstack API.
     */
    protected function prepareEnvironment(): void
    {
        // Kernel jau būna užbootintas per createClient()
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $connection = $em->getConnection();

        // Išvalom lenteles, kur saugom IP entity ir blacklist įrašus
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['blacklisted_ip', 'ip_address'] as $table) {
            $connection->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        // Mock'inam IpstackClient, kad testai nesiųstų realių HTTP užklausų
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
