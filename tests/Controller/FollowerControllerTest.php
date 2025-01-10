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
        $profile->method('getId')->willReturn(1);
        $profile->method('getUsername')->willReturn('testUsername');
        $profile->method('getFirstName')->willReturn('John');
        $profile->method('getLastName')->willReturn('Doe');

        $followers = [
            (new Follower())->setFollower($profile)->setFollowing($profile),
            (new Follower())->setFollower($profile)->setFollowing($profile),
        ];

        $this->profileRepository->method('find')->with($profileId)->willReturn($profile);

        $followerRepository = $this->createMock(FollowerRepository::class);
        $followerRepository->method('findFollowersWithPagination')->willReturn($followers);

        $this->entityManager->method('getRepository')->willReturn($followerRepository);

        $controller = new FollowerController($this->profileRepository, $this->entityManager);
        $request = new Request(['page' => 1, 'limit' => 10]);

        $response = $controller->getFollowersPaginated($profileId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertCount(2, $responseData['data']);
        $this->assertEquals('testUsername', $responseData['data'][0]['username']);
        $this->assertEquals('John', $responseData['data'][0]['firstName']);
        $this->assertEquals('Doe', $responseData['data'][0]['lastName']);
    }

    public function testFollowProfileSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'followerUsername' => 'follower',
            'followingUsername' => 'following',
        ]));

        $follower = $this->createMock(Profile::class);
        $following = $this->createMock(Profile::class);

        $this->profileRepository->method('findOneBy')->willReturnMap([
            [['username' => 'follower'], null, $follower],
            [['username' => 'following'], null, $following],
        ]);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Follower::class));
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new FollowerController($this->profileRepository, $this->entityManager);

        $response = $controller->followProfile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testUnfollowProfileSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'followerUsername' => 'follower',
            'followingUsername' => 'following',
        ]));

        $follower = $this->createMock(Profile::class);
        $following = $this->createMock(Profile::class);
        $followerEntity = new Follower();

        $this->profileRepository->method('findOneBy')->willReturnMap([
            [['username' => 'follower'], null, $follower],
            [['username' => 'following'], null, $following],
        ]);

        $this->entityManager->method('getRepository')->willReturnCallback(function ($class) use ($followerEntity) {
            if ($class === Follower::class) {
                $repository = $this->createMock(FollowerRepository::class);
                $repository->method('findOneBy')->willReturn($followerEntity);
                return $repository;
            }
            return null;
        });

        $this->entityManager->expects($this->once())->method('remove')->with($followerEntity);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new FollowerController($this->profileRepository, $this->entityManager);

        $response = $controller->unfollowProfile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}