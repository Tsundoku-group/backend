<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController {
    private $googleBooksService;

    public function __construct(GoogleBooksService $googleBooksService) {
        $this->googleBooksService = $googleBooksService;
    }

    #[Route('/api/latest-releases', name: 'latest_releases')]
    public function getLatestReleases(): JsonResponse {
        $latestReleases = $this->googleBooksService->getLatestReleases(40);
        return $this->json($latestReleases);
    }
}