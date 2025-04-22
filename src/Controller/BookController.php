<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/book')]
class BookController extends AbstractController
{
    private $googleBooksService;

    public function __construct(GoogleBooksService $googleBooksService)
    {
        $this->googleBooksService = $googleBooksService;
    }

    #[Route('/latest/releases', name: 'latest_releases', methods: ['GET'])]
    public function getLatestReleases(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 40);

        if ($limit > 40) {
            return $this->json([
                'error' => "L'API Google Books ne permet pas de récupérer plus de 40 livres à la fois.",
            ], Response::HTTP_BAD_REQUEST);
        }

        $latestReleases = $this->googleBooksService->getLatestReleases($limit);

        return $this->json($latestReleases, Response::HTTP_OK);
    }
}
