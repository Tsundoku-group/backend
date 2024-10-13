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

        return $response->toArray();
    }
}
