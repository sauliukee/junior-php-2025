<?php

namespace App\Entity;

use App\Repository\BlacklistedIpRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\IpAddress;

#[ORM\Entity(repositoryClass: BlacklistedIpRepository::class)]
class BlacklistedIp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    private ?string $ip = null;

    #[ORM\ManyToOne(targetEntity: IpAddress::class)]
    private ?IpAddress $ipAddress = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIpAddress(): ?IpAddress
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?IpAddress $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }
}
