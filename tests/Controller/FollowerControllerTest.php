<?php

namespace App\Tests\Controller;

use App\Controller\FollowerController;
use App\Constant\ErrorMessagesConstant;
use App\Service\FollowerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FollowerControllerTest extends TestCase
{
    private $followerService;
    private $controller;

    protected function setUp(): void
    {
        $this->followerService = $this->createMock(FollowerService::class);
        $this->controller = new FollowerController($this->followerService);
    }

    public function testGetFollowersPaginatedSuccess(): void
    {
        $profileId = 1;
        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'limit=10&offset=0']);

        $followers = [
            ['id' => 1, 'username' => 'user1', 'firstName' => 'John', 'lastName' => 'Doe'],
            ['id' => 2, 'username' => 'user2', 'firstName' => 'Jane', 'lastName' => 'Doe'],
        ];

        $this->followerService->method('getFollowersPaginated')
            ->with($profileId, $request)
            ->willReturn(['followers' => $followers, 'status' => 200]);

        $response = $this->controller->getFollowersPaginated($profileId, $request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('followers', $responseData);
        $this->assertEquals($followers, $responseData['followers']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetFollowersPaginatedNotFound(): void
    {
        $profileId = 1;
        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'limit=10&offset=0']);

        $this->followerService->method('getFollowersPaginated')
            ->with($profileId, $request)
            ->willReturn(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404]);

        $response = $this->controller->getFollowersPaginated($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFollowProfileSuccess(): void
    {
        $profileId = 1;
        $request = new Request([], [], [], [], [], [], json_encode(['followingId' => 2]));

        $this->followerService->method('followProfile')
            ->with($profileId, $request)
            ->willReturn(['message' => 'Successfully followed', 'status' => 201]);

        $response = $this->controller->followProfile($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testFollowProfileError(): void
    {
        $profileId = 1;
        $request = new Request([], [], [], [], [], [], json_encode(['followingId' => 2]));

        $this->followerService->method('followProfile')
            ->with($profileId, $request)
            ->willReturn(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404]);

        $response = $this->controller->followProfile($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUnfollowProfileSuccess(): void
    {
        $profileId = 1;
        $request = new Request([], [], [], [], [], [], json_encode(['followingId' => 2]));

        $this->followerService->method('unfollowProfile')
            ->with($profileId, $request)
            ->willReturn(['message' => 'Successfully unfollowed', 'status' => 200]);

        $response = $this->controller->unfollowProfile($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUnfollowProfileError(): void
    {
        $profileId = 1;
        $request = new Request([], [], [], [], [], [], json_encode(['followingId' => 2]));

        $this->followerService->method('unfollowProfile')
            ->with($profileId, $request)
            ->willReturn(['error' => 'Unfollow failed', 'status' => 400]);

        $response = $this->controller->unfollowProfile($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }
}