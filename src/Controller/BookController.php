<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController {
    private $googleBooksService;

    public function __construct(GoogleBooksService $googleBooksService) {
        $this->googleBooksService = $googleBooksService;
    }

    #[Route('/api/latest-releases', name: 'latest_releases')]
    public function getLatestReleases(int $limit): JsonResponse {
        if ($limit > 40) {
            return $this->json([
                'error' => "Google Books API doesn't allow fetching more than 40 books at a time.",
            ], Response::HTTP_BAD_REQUEST);
        }
        $latestReleases = $this->googleBooksService->getLatestReleases($limit);
        return $this->json($latestReleases);
    } 
}
