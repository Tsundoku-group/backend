<?php

namespace App\Tests\Controller;

use App\Controller\FriendshipController;
use App\Entity\Friendship;
use App\Entity\Profile;
use App\Repository\FriendshipRepository;
use App\Repository\ProfileRepository;
use App\Service\FriendshipService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FriendshipControllerTest extends TestCase
{
    private $entityManager;
    private $profileRepository;
    private $friendshipRepository;
    private $friendshipService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->profileRepository = $this->createMock(ProfileRepository::class);
        $this->friendshipRepository = $this->createMock(FriendshipRepository::class);
        $this->friendshipService = $this->createMock(FriendshipService::class);
    }

    public function testSendFriendRequestSuccess(): void
    {
        $profileId = 1;
        $friendIdToRequest = 2;

        $request = new Request([], [], [], [], [], [], json_encode([
            'friendId' => $friendIdToRequest,
        ]));

        $requesterProfile = $this->createMock(Profile::class);
        $receiverProfile = $this->createMock(Profile::class);

        $this->profileRepository->method('findOneBy')->willReturnMap([
            [['id' => $profileId], null, $requesterProfile],
            [['id' => $friendIdToRequest], null, $receiverProfile],
        ]);

        $this->friendshipRepository->method('findOneBy')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Friendship::class));
        $this->entityManager->expects($this->once())
            ->method('flush');

        $controller = new FriendshipController(
            $this->entityManager,
            $this->profileRepository,
            $this->friendshipRepository,
            $this->friendshipService
        );

        $response = $controller->sendFriendRequest($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Friend request sent']),
            $response->getContent()
        );
    }

    public function testSendFriendRequestProfileNotFound(): void
    {
        $profileId = 1;
        $friendIdToRequest = 2;

        $request = new Request([], [], [], [], [], [], json_encode([
            'friendId' => $friendIdToRequest,
        ]));

        $this->profileRepository->method('findOneBy')->willReturn(null);

        $controller = new FriendshipController(
            $this->entityManager,
            $this->profileRepository,
            $this->friendshipRepository,
            $this->friendshipService
        );

        $response = $controller->sendFriendRequest($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode('Requester or receiver not found.'),
            $response->getContent()
        );
    }
}
