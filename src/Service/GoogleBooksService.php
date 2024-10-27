<?php 

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleBooksService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function getLatestReleases(int $maxResults): array
    {
        $response = $this->httpClient->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
            'query' => [
                'q' => 'newest',
                'orderBy' => 'newest',
                'maxResults' => $maxResults,
                'key' => $this->apiKey,
            ],
        ]);

        $data = $response->toArray();

        $books = [];
        foreach ($data['items'] as $item) {
            $volumeInfo = $item['volumeInfo'];
            $books[] = [
                'title' => $volumeInfo['title'] ?? 'Titre indisponible',
                'authors' => $volumeInfo['authors'] ?? 'Auteur(s) indisponible(s)',
                'publishedDate' => $volumeInfo['publishedDate'] ?? 'Date de publication indisponible',
                'description' => $volumeInfo['description'] ?? 'Description indisponible',
                'categories' => $volumeInfo['categories'] ?? 'Catégorie(s) indisponible(s)',
                'thumbnail' => $volumeInfo['imageLinks']['thumbnail'] ?? 'https://via.placeholder.com/100x130.png?text=Image+indisponible',
            ];
        }

        return $books;
    }
}
