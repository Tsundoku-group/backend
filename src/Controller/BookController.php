<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    private $googleBooksService;

    public function __construct(GoogleBooksService $googleBooksService)
    {
        $this->googleBooksService = $googleBooksService;
    }

    #[Route('/api/v1/latest-releases', name: 'latest_releases', methods: ['GET'])]
    public function getLatestReleases(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 40);

        if ($limit > 40) {
            return $this->json([
                'error' => "Google Books API doesn't allow fetching more than 40 books at a time.",
            ], Response::HTTP_BAD_REQUEST);
        }

        $latestReleases = $this->googleBooksService->getLatestReleases($limit);

        return $this->json($latestReleases, Response::HTTP_OK);
    }
}
