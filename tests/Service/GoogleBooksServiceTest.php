<?php

namespace App\Tests\Service;

use App\Service\GoogleBooksService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GoogleBooksServiceTest extends TestCase
{
    private $httpClient;
    private $googleBooksService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->googleBooksService = new GoogleBooksService($this->httpClient, 'fake-api-key');
    }

    public function testGetLatestReleasesSuccess(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'items' => [
                [
                    'volumeInfo' => [
                        'title' => 'Test Book 1',
                        'authors' => ['Author 1'],
                        'publishedDate' => (new DateTime())->format('Y-m-d'),
                        'description' => 'Test Description 1',
                        'categories' => ['Fiction'],
                        'imageLinks' => ['thumbnail' => 'http://example.com/test1.jpg']
                    ],
                ],
                [
                    'volumeInfo' => [
                        'title' => 'Test Book 2',
                        'authors' => ['Author 2'],
                        'publishedDate' => (new DateTime())->format('Y-m-d'),
                        'description' => 'Test Description 2',
                        'categories' => ['Adventure'],
                        'imageLinks' => ['thumbnail' => 'http://example.com/test2.jpg']
                    ],
                ],
            ],
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->googleBooksService->getLatestReleases(2);

        $this->assertCount(2, $result);
        $this->assertEquals('Test Book 1', $result[0]['title']);
        $this->assertEquals('Test Book 2', $result[1]['title']);
        $this->assertEquals('http://example.com/test1.jpg', $result[0]['thumbnail']);
        $this->assertEquals('Fiction', $result[0]['categories'][0]);
    }

    public function testGetLatestReleasesLimitsResults(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'items' => array_fill(0, 50, [
                'volumeInfo' => [
                    'title' => 'Test Book',
                    'authors' => ['Author'],
                    'publishedDate' => '2023-01-01',
                    'description' => 'Test Description',
                    'categories' => ['Fiction'],
                    'imageLinks' => ['thumbnail' => 'http://example.com/test.jpg']
                ],
            ]),
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->googleBooksService->getLatestReleases(10);

        $this->assertCount(10, $result);
    }

    public function testGetLatestReleasesHandlesEmptyResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['items' => []]);

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->googleBooksService->getLatestReleases(10);

        $this->assertCount(0, $result);
    }

    public function testGetLatestReleasesIgnoresFutureDates(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'items' => [
                [
                    'volumeInfo' => [
                        'title' => 'Future Book',
                        'authors' => ['Author Future'],
                        'publishedDate' => (new DateTime('+1 year'))->format('Y-m-d'),
                        'description' => 'Future Description',
                        'categories' => ['Fiction'],
                        'imageLinks' => ['thumbnail' => 'http://example.com/future.jpg']
                    ],
                ],
                [
                    'volumeInfo' => [
                        'title' => 'Past Book',
                        'authors' => ['Author Past'],
                        'publishedDate' => (new DateTime('-1 year'))->format('Y-m-d'),
                        'description' => 'Past Description',
                        'categories' => ['History'],
                        'imageLinks' => ['thumbnail' => 'http://example.com/past.jpg']
                    ],
                ],
            ],
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->googleBooksService->getLatestReleases(2);

        $this->assertCount(1, $result);
        $this->assertEquals('Past Book', $result[0]['title']);
    }
}