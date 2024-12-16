<?php

namespace App\Service;

use DateTime;
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
        $books = [];
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');
        $startIndex = 0;
        $resultsPerPage = 40;

        while (count($books) < $maxResults) {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
                'query' => [
                    'q' => '2024 latest new newest recent recently',
                    'subject' => 'fiction',
                    'orderBy' => 'newest',
                    'printType' => 'books',
                    'maxResults' => $resultsPerPage,
                    'startIndex' => $startIndex,
                    'langRestrict' => 'fr',
                    'key' => $this->apiKey,
                ],
            ]);

            $data = $response->toArray();

            foreach ($data['items'] as $item) {
                $volumeInfo = $item['volumeInfo'];
                $publishedDate = isset($volumeInfo['publishedDate']) ? new DateTime($volumeInfo['publishedDate']) : null;

                if ($publishedDate && $publishedDate > $currentDate) {
                    continue;
                }

                $books[] = [
                    'title' => $volumeInfo['title'] ?? 'Titre indisponible',
                    'authors' => $volumeInfo['authors'] ?? ['Auteur(s) indisponible(s)'],
                    'publishedDate' => $volumeInfo['publishedDate'] ?? 'Date de publication indisponible',
                    'description' => $volumeInfo['description'] ?? 'Description indisponible',
                    'categories' => $volumeInfo['categories'] ?? ['Catégorie(s) indisponible(s)'],
                    'thumbnail' => $volumeInfo['imageLinks']['thumbnail'] ?? 'https://via.placeholder.com/100x130.png?text=Image+indisponible',
                ];

                if (count($books) >= $maxResults) {
                    break;
                }
            }

            $startIndex += $resultsPerPage;

            if (count($data['items']) < $resultsPerPage) {
                break;
            }
        }

        return array_slice($books, 0, $maxResults);
    }
}
