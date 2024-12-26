<?php

namespace App\Tests\Controller;

use App\Controller\BookController;
use App\Service\GoogleBooksService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BookControllerTest extends TestCase
{
    private $googleBooksService;
    private $container;

    protected function setUp(): void
    {
        $this->googleBooksService = $this->createMock(GoogleBooksService::class);

        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testGetLatestReleasesSuccess(): void
    {
        $mockResponse = [
            ['title' => 'Book 1', 'author' => 'Author 1'],
            ['title' => 'Book 2', 'author' => 'Author 2'],
        ];
        $this->googleBooksService->method('getLatestReleases')->willReturn($mockResponse);

        $controller = new BookController($this->googleBooksService);
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'limit=2']);

        $response = $controller->getLatestReleases($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($mockResponse, json_decode($response->getContent(), true));
    }

    public function testGetLatestReleasesLimitExceeded(): void
    {
        $controller = new BookController($this->googleBooksService);
        $controller->setContainer($this->container);

        $request = new Request(['limit' => 50]);

        $response = $controller->getLatestReleases($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(
            ['error' => "Google Books API doesn't allow fetching more than 40 books at a time."],
            json_decode($response->getContent(), true)
        );
    }
    public function testGetLatestReleasesDefaultLimit(): void
    {
        $mockResponse = array_fill(0, 40, ['title' => 'Book', 'author' => 'Author']);
        $this->googleBooksService->method('getLatestReleases')->willReturn($mockResponse);

        $controller = new BookController($this->googleBooksService);
        $controller->setContainer($this->container);

        $request = new Request();

        $response = $controller->getLatestReleases($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(40, json_decode($response->getContent(), true));
    }
}