<?php

namespace App\Tests\Controller;

use App\Controller\FriendshipController;
use App\Service\FriendshipService;
use App\Constant\GenericErrorMessagesConstant;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FriendshipControllerTest extends TestCase
{
    private FriendshipService $friendshipService;
    private FriendshipController $controller;

    protected function setUp(): void
    {
        $this->friendshipService = $this->createMock(FriendshipService::class);
        $this->controller = new FriendshipController($this->friendshipService);
    }

    public function testSendFriendRequestSuccess(): void
    {
        $profileId = 1;
        $friendIdToRequest = 2;

        $request = new Request([], [], [], [], [], [], json_encode([
            'friendId' => $friendIdToRequest,
        ]));

        $this->friendshipService->method('sendFriendRequest')->willReturn([
            'message' => 'Friend request sent',
            'status' => 201
        ]);

        $response = $this->controller->sendFriendRequest($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals([
            'message' => 'Friend request sent',
            'status' => 201
        ], $responseData);
    }

    public function testAcceptFriendRequestSuccess(): void
    {
        $requestId = 1;

        $this->friendshipService->method('acceptFriendRequest')->willReturn([
            'message' => 'Friend request accepted',
            'status' => 200
        ]);

        $response = $this->controller->acceptFriendRequest($requestId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals([
            'message' => 'Friend request accepted',
            'status' => 200
        ], $responseData);
    }

    public function testRejectFriendRequestSuccess(): void
    {
        $requestId = 3;

        $this->friendshipService->method('rejectFriendRequest')->willReturn([
            'message' => 'Friend request rejected',
            'status' => 200
        ]);

        $response = $this->controller->rejectFriendRequest($requestId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals([
            'message' => 'Friend request rejected',
            'status' => 200
        ], $responseData);
    }

    public function testRemoveFriendSuccess(): void
    {
        $friendshipId = 4;

        $request = new Request([], [], [], [], [], [], json_encode([
            'requesterId' => 1,
            'receiverId' => 2
        ]));

        $this->friendshipService->method('removeFriend')->willReturn([
            'message' => 'Friend removed',
            'status' => 200
        ]);

        $response = $this->controller->removeFriend($friendshipId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals([
            'message' => 'Friend removed',
            'status' => 200
        ], $responseData);
    }
}