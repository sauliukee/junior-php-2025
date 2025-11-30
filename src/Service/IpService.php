<?php

namespace App\Service;

use App\Entity\IpAddress;
use App\Entity\BlacklistedIp;
use App\Repository\IpAddressRepository;
use App\Repository\BlacklistedIpRepository;
use Doctrine\ORM\EntityManagerInterface;

class IpService
{
    public function __construct(
        private IpAddressRepository $ipAddressRepository,
        private BlacklistedIpRepository $blacklistedIpRepository,
        private IpstackClient $ipstackClient,
        private EntityManagerInterface $em,
    ) {
    }

    public function getIpInfo(string $ip): IpAddress
    {
        $this->assertValidIp($ip);

        # 1) blacklist check
        if ($this->blacklistedIpRepository->findOneBy(['ip' => $ip])) {
            throw new \RuntimeException('IP is blacklisted');
        }

        # 2) ieškome DB
        $ipEntity = $this->ipAddressRepository->findOneBy(['ip' => $ip]);

        $now = new \DateTimeImmutable();
        $oneDayAgo = $now->modify('-1 day');

        # 3) jei yra ir ne senesnis nei 1 diena – grąžinam cached
        if ($ipEntity !== null && $ipEntity->getUpdatedAt() >= $oneDayAgo) {
            return $ipEntity;
        }

        # 4) reikia naujų duomenų iš ipstack
        $data = $this->ipstackClient->fetchIpData($ip);

        if ($ipEntity === null) {
            $ipEntity = new IpAddress();
            $ipEntity->setIp($ip);
            $this->em->persist($ipEntity);
        }

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
     * Bulk IP info – bonus endpointui.
     * Grąžina masyvą su success/error per IP.
     */
    public function getIpInfoBulk(array $ips): array
    {
        $results = [];

        foreach ($ips as $ip) {
            # saugumo sumetimais – tipas string
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

    public function addToBlacklist(string $ip): void
    {
        $this->assertValidIp($ip);

        if ($this->blacklistedIpRepository->findOneBy(['ip' => $ip])) {
            return; 
        }

        $blacklisted = new BlacklistedIp();
        $blacklisted->setIp($ip);

        $ipEntity = $this->ipAddressRepository->findOneBy(['ip' => $ip]);
        if ($ipEntity) {
            $blacklisted->setIpAddress($ipEntity);
        }

        $this->em->persist($blacklisted);
        $this->em->flush();
    }

    /**
     * Bulk add to blacklist.
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
     * Bulk remove from blacklist.
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

    private function assertValidIp(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
        }
    }
}
