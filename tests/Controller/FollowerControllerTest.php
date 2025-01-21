<?php

namespace App\Tests\Controller;

use App\Controller\FollowerController;
use App\Entity\Follower;
use App\Entity\Profile;
use App\Repository\FollowerRepository;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FollowerControllerTest extends TestCase
{
    private $entityManager;
    private $profileRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->profileRepository = $this->createMock(ProfileRepository::class);
    }

    public function testGetFollowersPaginatedSuccess(): void
    {
        $profileId = 1;

        $profile = $this->createMock(Profile::class);
        $this->profileRepository->method('find')->with($profileId)->willReturn($profile);

        $followers = [
            ['id' => 1, 'username' => 'follower1', 'firstName' => 'John', 'lastName' => 'Doe'],
            ['id' => 2, 'username' => 'follower2', 'firstName' => 'Jane', 'lastName' => 'Doe'],
        ];

        $followerRepository = $this->createMock(FollowerRepository::class);
        $followerRepository->method('findFollowersWithPagination')->willReturn($followers);

        $controller = new FollowerController($this->profileRepository, $this->entityManager, $followerRepository);
        $request = new Request([], ['page' => 1, 'limit' => 10]);

        $response = $controller->getFollowersPaginated($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData);
        $this->assertEquals('follower1', $responseData[0]['username']);
        $this->assertEquals('John', $responseData[0]['firstName']);
        $this->assertEquals('Doe', $responseData[0]['lastName']);
    }

    public function testFollowProfileSuccess(): void
    {
        $profileId = 1;
        $request = new Request([], [], [], [], [], [], json_encode([
            'followingId' => 2,
        ]));

        $follower = $this->createMock(Profile::class);
        $following = $this->createMock(Profile::class);

        $this->profileRepository->method('findOneBy')->willReturnMap([
            [['id' => $profileId], null, $follower],
            [['id' => 2], null, $following],
        ]);

        $followerRepository = $this->createMock(FollowerRepository::class);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Follower::class));
        $this->entityManager->expects($this->once())->method('flush');


        $controller = new FollowerController($this->profileRepository, $this->entityManager, $followerRepository);

        $response = $controller->followProfile($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testUnfollowProfileSuccess(): void
    {
        $profileId = 1;
        $followingId = 2;

        $request = new Request([], [], [], [], [], [], json_encode([
            'followerId' => $profileId,
            'followingId' => $followingId,
        ]));

        $follower = $this->createMock(Profile::class);
        $follower->method('getId')->willReturn($profileId);

        $following = $this->createMock(Profile::class);
        $following->method('getId')->willReturn($followingId);

        $followerEntity = $this->createMock(Follower::class);
        $followerEntity->method('getFollower')->willReturn($follower);
        $followerEntity->method('getFollowing')->willReturn($following);

        $this->profileRepository->method('findOneBy')->willReturnMap([
            [['id' => $profileId], null, $follower],
            [['id' => $followingId], null, $following],
        ]);

        $followerRepository = $this->createMock(FollowerRepository::class);
        $followerRepository->method('find')->willReturn($followerEntity);

        $this->entityManager->method('getRepository')->willReturnCallback(function ($class) use ($followerRepository) {
            if ($class === Follower::class) {
                return $followerRepository;
            }
            return null;
        });

        $this->entityManager->expects($this->once())->method('remove')->with($followerEntity);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new FollowerController($this->profileRepository, $this->entityManager, $followerRepository);

        $response = $controller->unfollowProfile(1, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}