<?php

namespace App\Controller;

use App\Service\ReactService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/reacts')]
class ReactController extends AbstractController
{
    public function __construct(
        private readonly ReactService $reactService,
    ) {
    }

    #[Route('', name: 'toggle_react', methods: ['POST'])]
    public function toggleReaction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return $this->reactService->toggleReaction($data);
    }
}
