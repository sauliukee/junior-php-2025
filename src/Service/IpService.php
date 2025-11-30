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
        // 1) blacklist check
        if ($this->blacklistedIpRepository->findOneBy(['ip' => $ip])) {
            throw new \RuntimeException('IP is blacklisted');
        }

        // 2) ieškome DB
        $ipEntity = $this->ipAddressRepository->findOneBy(['ip' => $ip]);

        $now = new \DateTimeImmutable();
        $oneDayAgo = $now->modify('-1 day');

        // 3) jei yra ir ne senesnis nei 1 diena – grąžinam cached
        if ($ipEntity !== null && $ipEntity->getUpdatedAt() >= $oneDayAgo) {
            return $ipEntity;
        }

        // 4) reikia naujų duomenų iš ipstack
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

    public function deleteIp(string $ip): void
    {
        $ipEntity = $this->ipAddressRepository->findOneBy(['ip' => $ip]);

        if (!$ipEntity) {
            throw new \RuntimeException('IP not found');
        }

        $this->em->remove($ipEntity);
        $this->em->flush();
    }

    public function addToBlacklist(string $ip): void
    {
        if ($this->blacklistedIpRepository->findOneBy(['ip' => $ip])) {
            return; // jau yra – nieko nedarom
        }

        $blacklisted = new BlacklistedIp();
        $blacklisted->setIp($ip);

        $this->em->persist($blacklisted);
        $this->em->flush();
    }

    public function removeFromBlacklist(string $ip): void
    {
        $entity = $this->blacklistedIpRepository->findOneBy(['ip' => $ip]);

        if (!$entity) {
            throw new \RuntimeException('IP is not in blacklist');
        }

        $this->em->remove($entity);
        $this->em->flush();
    }
}
