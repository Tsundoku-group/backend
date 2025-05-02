<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class FallbackController
{
    #[Route('/', name: 'app_main', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse(['message' => 'API root']);
    }
}