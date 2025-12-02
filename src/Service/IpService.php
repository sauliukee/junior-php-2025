<?php

namespace App\Service;

use App\Entity\IpAddress;
use App\Entity\BlacklistedIp;
use App\Repository\IpAddressRepository;
use App\Repository\BlacklistedIpRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Servisas, kuris sukelia visą IP logiką į vieną vietą.
 *
 * Čia darau:
 *  - IP informacijos gavimą su paprastu cache (1 dienos galiojimas),
 *  - Blacklist tikrinimą ir valdymą,
 *  - bulk operacijas (kelios IP vienu metu).
 *
 * Controlleriai kviečia šitą servisą, kad nereiktų kartoti logikos.
 */
class IpService
{
    public function __construct(
        private IpAddressRepository $ipAddressRepository,
        private BlacklistedIpRepository $blacklistedIpRepository,
        private IpstackClient $ipstackClient,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * Grąžina informaciją apie IP iš lokalaus cache arba ipstack.
     *
     * Logika:
     *  - pirmiausia patikrinam IP formatą,
     *  - tada tikrinam, ar IP nėra blackliste,
     *  - jei duomenys DB ir ne senesni nei 1 diena – gražinam cache,
     *  - kitu atveju kviečiam ipstack, atnaujinam/įrašom ir gražinam.
     *
     * @param string $ip IPv4 arba IPv6 adresas.
     *
     * @return IpAddress IP duomenų entity iš DB.
     *
     * @throws \InvalidArgumentException jei IP formatas neteisingas.
     * @throws \RuntimeException         jei IP yra blackliste arba ipstack grąžina klaidą.
     */
    public function getIpInfo(string $ip): IpAddress
    {
        $this->assertValidIp($ip);

        // 1) Pirma stabdom, jei IP yra blackliste
        if ($this->blacklistedIpRepository->findOneBy(['ip' => $ip])) {
            throw new \RuntimeException('IP is blacklisted');
        }

        // 2) Bandom rasti cache (DB įrašą)
        $ipEntity = $this->ipAddressRepository->findOneBy(['ip' => $ip]);

        $now = new \DateTimeImmutable();
        $oneDayAgo = $now->modify('-1 day');

        // 3) Jei turim įrašą ir jis ne senesnis nei 1 diena – tiesiog grąžinam
        if ($ipEntity !== null && $ipEntity->getUpdatedAt() >= $oneDayAgo) {
            return $ipEntity;
        }

        // 4) Reikia naujų duomenų – kviečiam ipstack
        $data = $this->ipstackClient->fetchIpData($ip);

        // Jei DB nėra šito IP, sukuriam naują entity
        if ($ipEntity === null) {
            $ipEntity = new IpAddress();
            $ipEntity->setIp($ip);
            $this->em->persist($ipEntity);
        }

        // Atnaujinam laukus pagal ipstack atsakymą
        $ipEntity
            ->setCountry($data['country_name'] ?? null)
            ->setCity($data['city'] ?? null)
            ->setLatitude($data['latitude'] ?? null)
            ->setLongitude($data['longitude'] ?? null)
            ->setUpdatedAt($now);

        $this->em->flush();

        return $ipEntity;
    }

    /**
     * Bulk versija – leidžia gauti info keliems IP vienu metu.
     *
     * Kiekvienam IP kviečia getIpInfo(), bet klaidų nemeta:
     * jei įvyksta klaida, grąžina success=false ir error žinutę.
     *
     * @param string[] $ips IP adresų sąrašas.
     *
     * @return array Masyvas su rezultatais per IP (success/error).
     */
    public function getIpInfoBulk(array $ips): array
    {
        $results = [];

        foreach ($ips as $ip) {
            // Saugumo sumetimais – paverčiam į string
            $ip = (string) $ip;

            try {
                $entity = $this->getIpInfo($ip);

                $results[] = [
                    'ip'        => $entity->getIp(),
                    'country'   => $entity->getCountry(),
                    'city'      => $entity->getCity(),
                    'latitude'  => $entity->getLatitude(),
                    'longitude' => $entity->getLongitude(),
                    'updatedAt' => $entity->getUpdatedAt()->format(DATE_ATOM),
                    'success'   => true,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'ip'      => $ip,
                    'success' => false,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Ištrina vieno IP cache įrašą iš DB.
     *
     * ipstack čia nekviečiam – tiesiog pašalinam lokalius duomenis.
     *
     * @param string $ip IP adresas, kurio cache norim ištrinti.
     *
     * @throws \InvalidArgumentException jei IP formatas neteisingas.
     * @throws \RuntimeException         jei IP DB nerastas.
     */
    public function deleteIp(string $ip): void
    {
        $this->assertValidIp($ip);

        $ipEntity = $this->ipAddressRepository->findOneBy(['ip' => $ip]);

        if (!$ipEntity) {
            throw new \RuntimeException('IP not found');
        }

        $this->em->remove($ipEntity);
        $this->em->flush();
    }

    /**
     * Prideda IP adresą į blacklistą.
     *
     * Jei IP jau yra blackliste – nieko nedarom.
     * Jei DB turim IpAddress įrašą, susiejam juos tarpusavyje (patogiau vėliau debuginti).
     *
     * @param string $ip IP adresas, kurį norim užblokuoti.
     *
     * @throws \InvalidArgumentException jei IP formatas neteisingas.
     */
    public function addToBlacklist(string $ip): void
    {
        $this->assertValidIp($ip);

        // Jei IP jau yra blackliste, kartoti nereikia
        if ($this->blacklistedIpRepository->findOneBy(['ip' => $ip])) {
            return;
        }

        $blacklisted = new BlacklistedIp();
        $blacklisted->setIp($ip);

        // Jei IP jau turime cache lentelėje – pririšam entity
        $ipEntity = $this->ipAddressRepository->findOneBy(['ip' => $ip]);
        if ($ipEntity) {
            $blacklisted->setIpAddress($ipEntity);
        }

        $this->em->persist($blacklisted);
        $this->em->flush();
    }

    /**
     * Bulk versija blacklist – keli IP vienu metu.
     *
     * Nekelia exception į viršų – kaip ir kitur, grąžina success/error per IP.
     *
     * @param string[] $ips IP adresai, kuriuos norim užblokuoti.
     *
     * @return array IP rezultatai su success/error.
     */
    public function addToBlacklistBulk(array $ips): array
    {
        $results = [];

        foreach ($ips as $ip) {
            $ip = (string) $ip;

            try {
                $this->addToBlacklist($ip);
                $results[] = [
                    'ip'      => $ip,
                    'success' => true,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'ip'      => $ip,
                    'success' => false,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Pašalina vieną IP iš juodojo sąrašo.
     *
     * @param string $ip IP adresas, kurį norim išimti iš blacklist.
     *
     * @throws \InvalidArgumentException jei IP formatas neteisingas.
     * @throws \RuntimeException         jei šitas IP apskritai nėra blackliste.
     */
    public function removeFromBlacklist(string $ip): void
    {
        $this->assertValidIp($ip);

        $entity = $this->blacklistedIpRepository->findOneBy(['ip' => $ip]);

        if (!$entity) {
            throw new \RuntimeException('IP is not in blacklist');
        }

        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * Bulk versija removeFromBlacklist() metodui.
     *
     * Vėlgi – čia exception’ų nekeliam į viršų, o grąžinam statusą per IP.
     *
     * @param string[] $ips IP adresai, kuriuos norim išimti iš blacklist.
     *
     * @return array IP success/error rezultatai.
     */
    public function removeFromBlacklistBulk(array $ips): array
    {
        $results = [];

        foreach ($ips as $ip) {
            $ip = (string) $ip;

            try {
                $this->removeFromBlacklist($ip);
                $results[] = [
                    'ip'      => $ip,
                    'success' => true,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'ip'      => $ip,
                    'success' => false,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Patikrina, ar IP adresas sintaksiškai teisingas.
     *
     * Visos public šitos klasės funkcijos naudoja tą pačią validaciją.
     *
     * @param string $ip IP adresas, kurį tikrinam.
     *
     * @throws \InvalidArgumentException jei IP nėra validus IPv4/IPv6.
     */
    private function assertValidIp(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
        }
    }
}
