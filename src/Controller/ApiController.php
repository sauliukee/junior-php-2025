<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Paprastas controlleris health-check endpoint'ui.
 *
 * Naudojamas patikrinti, ar API gyvas ir pasiekiamas.
 */
class ApiController extends AbstractController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    #[OA\Get(
        path: "/api/health",
        summary: "Health check",
        responses: [
            new OA\Response(
                response: 200,
                description: "API is healthy",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "ok")
                    ]
                )
            )
        ]
    )]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }
}
