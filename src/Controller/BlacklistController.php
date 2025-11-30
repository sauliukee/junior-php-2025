<?php

namespace App\Controller;

use App\Service\IpService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class BlacklistController extends AbstractController
{
    #[Route('/api/blacklist', name: 'api_blacklist_add', methods: ['POST'])]
    #[OA\Post(
        path: "/api/blacklist",
        summary: "Add IP to blacklist",
        tags: ["Blacklist"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "ip", type: "string", example: "1.2.3.4")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "IP blacklisted"
            )
        ]
    )]
    public function add(Request $request, IpService $service): JsonResponse
    {
        $ip = json_decode($request->getContent(), true)['ip'] ?? null;

        if (!$ip) {
            return $this->json(['error' => 'Missing IP'], 400);
        }

        $service->addToBlacklist($ip);

        return $this->json(['message' => 'IP blacklisted']);
    }

    #[Route('/api/blacklist/{ip}', name: 'api_blacklist_remove', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/blacklist/{ip}",
        summary: "Remove IP from blacklist",
        tags: ["Blacklist"],
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
                description: "IP removed from blacklist"
            ),
            new OA\Response(
                response: 400,
                description: "IP not found in blacklist"
            )
        ]
    )]
    public function remove(string $ip, IpService $service): JsonResponse
    {
        try {
            $service->removeFromBlacklist($ip);
            return $this->json(['message' => 'IP removed from blacklist']);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
