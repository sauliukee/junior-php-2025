<?php

namespace App\Controller;

use App\Service\IpService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controlleris, kuris valdo blacklist API endpoint'us.
 *
 * Čia sudėta logika:
 *  - pridėti vieną IP į blacklist,
 *  - išimti IP iš blacklist,
 *  - atlikti bulk operacijas su keliais IP vienu metu.
 */
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
            ),
            new OA\Response(
                response: 400,
                description: "Missing or invalid IP"
            )
        ]
    )]
    public function add(Request $request, IpService $service): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $ip = $payload['ip'] ?? null;

        if (!$ip) {
            return $this->json(['error' => 'Missing IP'], 400);
        }

        try {
            $service->addToBlacklist($ip);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

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
                description: "Invalid IP"
            ),
            new OA\Response(
                response: 404,
                description: "IP not found in blacklist"
            )
        ]
    )]
    public function remove(string $ip, IpService $service): JsonResponse
    {
        try {
            $service->removeFromBlacklist($ip);
            return $this->json(['message' => 'IP removed from blacklist']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/api/blacklist/bulk', name: 'api_blacklist_add_bulk', methods: ['POST'])]
    #[OA\Post(
        path: "/api/blacklist/bulk",
        summary: "Add multiple IPs to blacklist",
        tags: ["Blacklist"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "ips",
                        type: "array",
                        items: new OA\Items(type: "string"),
                        example: ["1.2.3.4", "8.8.8.8"],
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Bulk blacklist operation result"
            ),
            new OA\Response(
                response: 400,
                description: "Invalid request body"
            )
        ]
    )]
    public function addBulk(Request $request, IpService $service): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $ips = $payload['ips'] ?? null;

        if (!is_array($ips) || $ips === []) {
            return $this->json(['error' => 'Field "ips" must be a non-empty array'], 400);
        }

        $results = $service->addToBlacklistBulk($ips);

        return $this->json(['results' => $results]);
    }

    #[Route('/api/blacklist/bulk', name: 'api_blacklist_remove_bulk', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/blacklist/bulk",
        summary: "Remove multiple IPs from blacklist",
        tags: ["Blacklist"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "ips",
                        type: "array",
                        items: new OA\Items(type: "string"),
                        example: ["1.2.3.4", "8.8.8.8"],
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Bulk remove result"
            ),
            new OA\Response(
                response: 400,
                description: "Invalid request body"
            )
        ]
    )]
    public function removeBulk(Request $request, IpService $service): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $ips = $payload['ips'] ?? null;

        if (!is_array($ips) || $ips === []) {
            return $this->json(['error' => 'Field "ips" must be a non-empty array'], 400);
        }

        $results = $service->removeFromBlacklistBulk($ips);

        return $this->json(['results' => $results]);
    }
}
