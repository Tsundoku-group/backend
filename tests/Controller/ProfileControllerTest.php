<?php

namespace App\Tests\Controller;

use App\Controller\ProfileController;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\FollowerRepository;
use App\Repository\FriendshipRepository;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProfileControllerTest extends TestCase
{
    private $entityManager;
    private $profileRepository;
    private $userRepository;
    private $friendshipRepository;
    private $followerRepository;
    private $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->profileRepository = $this->createMock(ProfileRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->friendshipRepository = $this->createMock(FriendshipRepository::class);
        $this->followerRepository = $this->createMock(FollowerRepository::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testShowProfileNotFound(): void
    {
        $this->profileRepository->method('findProfileById')->willReturn(null);

        $controller = new ProfileController(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );

        $controller->setContainer($this->container);

        $response = $controller->show(999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Profile not found']),
            $response->getContent()
        );
    }

    public function testCreateProfileSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'newuser',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'type' => 'lecteur',
            'phoneNumber' => '0612233435',
            'birthday' => '2000-01-01',
            'bio' => 'New user bio',
        ]));

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $this->profileRepository->method('findOneBy')->willReturn(null);
        $this->profileRepository->method('count')->willReturn(0);

        $controller = $this->getMockBuilder(ProfileController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->profileRepository,
                $this->userRepository,
                $this->friendshipRepository,
                $this->followerRepository,
            ])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Profile::class));
        $this->entityManager->expects($this->once())->method('flush');

        $controller->setContainer($this->container);

        $response = $controller->createProfile($request, $this->entityManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testCreateProfileDuplicateUsername(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'existinguser',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'type' => 'lecteur',
            'birthday' => '2000-01-01',
            'bio' => 'Duplicate username',
        ]));

        $existingProfile = $this->createMock(Profile::class);
        $this->profileRepository->method('findOneBy')->willReturn($existingProfile);

        $controller = new ProfileController(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );

        $controller->setContainer($this->container);

        $response = $controller->createProfile($request, $this->entityManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Le nom d\'utilisateur est déjà pris']),
            $response->getContent()
        );
    }

    public function testUpdateProfileSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'firstName' => 'Updated John',
            'lastName' => 'Updated Doe',
            'username' => 'updateduser',
            'gender' => 'masculin',
            'phoneNumber' => '0612233435',
            'birthday' => '1990-01-01',
            'bio' => 'Updated bio',
        ]));

        $profile = $this->createMock(Profile::class);
        $this->profileRepository->method('findOneBy')->willReturn(null);

        $this->entityManager->expects($this->once())->method('flush');

        $controller = new ProfileController(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );

        $controller->setContainer($this->container);

        $response = $controller->update($request, $profile);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testDeleteProfileSuccess(): void
    {
        $profile = $this->createMock(Profile::class);

        $this->profileRepository->method('findOneBy')->willReturn($profile);

        $this->entityManager->expects($this->once())->method('remove')->with($profile);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new ProfileController(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );

        $controller->setContainer($this->container);

        $response = $controller->delete(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testGetActiveProfileSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $activeProfile = $this->createMock(Profile::class);
        $activeProfile->method('getId')->willReturn(1);
        $activeProfile->method('getUsername')->willReturn('johndoe');

        $this->userRepository->method('find')->willReturn($user);
        $this->profileRepository->method('findOneBy')->willReturn($activeProfile);

        $controller = new ProfileController(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );
        $controller->setContainer($this->container);

        $response = $controller->getActiveProfile(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'activeProfile' => [
                    'id' => 1,
                    'username' => 'johndoe',
                ],
            ]),
            $response->getContent()
        );
    }

    public function testGetActiveProfileNotFound(): void
    {
        $this->userRepository->method('find')->willReturn(null);

        $controller = new ProfileController(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );
        $controller->setContainer($this->container);

        $response = $controller->getActiveProfile(999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'User not found']),
            $response->getContent()
        );
    }

    public function testSetActiveProfileSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $profile = $this->createMock(Profile::class);
        $profile->method('getId')->willReturn(1);
        $profile->method('getUser')->willReturn($user);

        $this->userRepository->method('find')->willReturn($user);
        $this->profileRepository->method('find')->willReturn($profile);

        $this->profileRepository->method('findBy')->willReturn([$profile]);

        $controller = new ProfileController(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'id' => 1,
            'profileId' => 1,
        ]));

        $response = $controller->setActiveProfile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testSetActiveProfileNotFound(): void
    {
        $this->userRepository->method('find')->willReturn(null);

        $controller = new ProfileController(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'id' => 999,
            'profileId' => 999,
        ]));

        $response = $controller->setActiveProfile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'User not found']),
            $response->getContent()
        );
    }
}