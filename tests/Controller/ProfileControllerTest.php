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
    private $container;
    private $friendshipRepository;
    private $followerRepository;

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
            ->willReturn(null);

        $controller->setContainer($this->container);

        $response = $controller->show(999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'User not found']),
            $response->getContent()
        );
    }

    public function testShowUserNotFound(): void
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

        $response = $controller->getAllProfiles(999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'User not found']),
            $response->getContent()
        );
    }

    public function testCreateProfileSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'testUsername',
            'type' => 'lecteur',
            'birthday' => '2000-01-01',
            'gender' => 'male',
            'phoneNumber' => '123456789',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]));

        $user = $this->createMock(User::class);

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

        $controller->setContainer($this->container);

        $this->profileRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Profile::class));
        $this->entityManager->expects($this->once())->method('flush');

        $response = $controller->createProfile($request, $this->entityManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testUpdateProfileSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'username' => 'updatedUsername',
            'birthday' => '1990-01-01',
            'gender' => 'male',
            'phoneNumber' => '123456789',
        ]));

        $profile = $this->createMock(Profile::class);

        $profile->method('getFirstName')->willReturn('John');
        $profile->method('getLastName')->willReturn('Doe');
        $profile->method('getUsername')->willReturn('updatedUsername');
        $profile->method('getBirthday')->willReturn(new \DateTime('1990-01-01'));
        $profile->method('getGender')->willReturn('male');
        $profile->method('getPhoneNumber')->willReturn('123456789');

        $this->profileRepository->method('findOneBy')->willReturn(null); // Pas de doublon
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
    }
}