<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use App\Entity\Profile;
use App\Repository\UserRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserControllerTest extends TestCase
{
    private $entityManager;
    private $userRepository;
    private $mailService;
    private $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testGetAllUsers(): void
    {
        $user = new User();
        $profile = $this->createMock(Profile::class);
        $profile->method('getUsername')->willReturn('TestUser');
        $user->addProfile($profile);

        $this->userRepository->method('findAll')->willReturn([$user]);

        $controller = new UserController($this->entityManager, $this->userRepository, $this->mailService);
        $controller->setContainer($this->container);

        $response = $controller->getAll();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(['TestUser'], $responseData);
    }

    public function testNewUser(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]));

        $user = $this->createMock(User::class);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new UserController($this->entityManager, $this->userRepository, $this->mailService);
        $controller->setContainer($this->container);

        $response = $controller->new($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testShowUser(): void
    {
        $user = $this->createMock(User::class);
        $profile = $this->createMock(\App\Entity\Profile::class);
        $profile->method('getUserName')->willReturn('TestUser');

        $user->method('getProfiles')->willReturn(new ArrayCollection([$profile]));

        $this->userRepository->method('find')->willReturn($user);

        $controller = new UserController($this->entityManager, $this->userRepository, $this->mailService);
        $controller->setContainer($this->container);

        $response = $controller->show(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUpdateUser(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'updated@example.com',
            'password' => 'newpassword123',
        ]));

        $user = $this->createMock(User::class);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new UserController($this->entityManager, $this->userRepository, $this->mailService);
        $controller->setContainer($this->container);

        $response = $controller->update($request, $user);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testVerifyPassword(): void
    {
        $passwordHasher = $this->createMock(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        $passwordHasher->method('isPasswordValid')->willReturn(true);

        $request = new Request([], [], [], [], [], [], json_encode(['currentPassword' => 'password123']));

        $user = $this->createMock(User::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = new UsernamePasswordToken($user, 'credentials', ['ROLE_USER']);
        $tokenStorage->method('getToken')->willReturn($token);

        $this->container->method('get')->willReturnMap([
            ['security.token_storage', $tokenStorage]
        ]);

        $controller = $this->getMockBuilder(UserController::class)
            ->setConstructorArgs([$this->entityManager, $this->userRepository, $this->mailService])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $controller->setContainer($this->container);

        $response = $controller->verifyPassword($request, $passwordHasher);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRequestAccountDeletion(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getAccountDeletionDate')->willReturn(null);
        $user->method('getEmail')->willReturn('test@example.com');

        $this->userRepository->method('find')->willReturn($user);
        $this->entityManager->expects($this->once())->method('persist')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new UserController($this->entityManager, $this->userRepository, $this->mailService);
        $controller->setContainer($this->container);

        $response = $controller->requestAccountDeletion(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}