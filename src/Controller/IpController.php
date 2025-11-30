<?php

namespace App\Controller;

use App\Service\IpService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class IpController extends AbstractController
{
    #[Route('/api/ip/{ip}', name: 'api_ip_get', methods: ['GET'])]
    #[OA\Get(
        path: "/api/ip/{ip}",
        summary: "Get IP information",
        tags: ["IP"],
        parameters: [
            new OA\Parameter(
                name: "ip",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string"),
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "IP information",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "ip", type: "string", example: "8.8.8.8"),
                        new OA\Property(property: "country", type: "string", nullable: true),
                        new OA\Property(property: "city", type: "string", nullable: true),
                        new OA\Property(property: "latitude", type: "number", format: "float", nullable: true),
                        new OA\Property(property: "longitude", type: "number", format: "float", nullable: true),
                        new OA\Property(property: "updatedAt", type: "string", example: "2025-11-29T12:00:00+00:00"),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid IP or blacklisted",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "IP is blacklisted")
                    ]
                )
            )
        ]
    )]
    public function getIp(string $ip, IpService $ipService): JsonResponse
    {
        try {
            $ipEntity = $ipService->getIpInfo($ip);

            return $this->json([
                'ip'        => $ipEntity->getIp(),
                'country'   => $ipEntity->getCountry(),
                'city'      => $ipEntity->getCity(),
                'latitude'  => $ipEntity->getLatitude(),
                'longitude' => $ipEntity->getLongitude(),
                'updatedAt' => $ipEntity->getUpdatedAt()->format(DATE_ATOM),
            ]);
        } catch (\RuntimeException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                400
            );
        }
    }

        #[Route('/api/ip/{ip}', name: 'api_ip_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/ip/{ip}",
        summary: "Delete cached IP information",
        tags: ["IP"],
        parameters: [
            new OA\Parameter(
                name: "ip",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "IP deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "IP deleted")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "IP not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "IP not found")
                    ]
                )
            )
        ]
    )]
    public function deleteIp(string $ip, IpService $ipService): JsonResponse
    {
        try {
            $ipService->deleteIp($ip);
            return $this->json(['message' => 'IP deleted']);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

}
