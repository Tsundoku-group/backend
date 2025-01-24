<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use App\Entity\Profile;
use App\Repository\UserRepository;
use App\Service\MailService;
use App\Validator\Constraints\CaptchaValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserControllerTest extends TestCase
{
    private $entityManager;
    private $userRepository;
    private $mailService;
    private $captchaValidator;
    private $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->captchaValidator = $this->createMock(CaptchaValidator::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function createController(): UserController
    {
        $controller = new UserController(
            $this->entityManager,
            $this->userRepository,
            $this->mailService,
            $this->captchaValidator
        );
        $controller->setContainer($this->container);

        return $controller;
    }

    public function testGetAllUsers(): void
    {
        $user = new User();
        $profile = $this->createMock(Profile::class);
        $profile->method('getUsername')->willReturn('TestUser');
        $user->addProfile($profile);

        $this->userRepository->method('findAll')->willReturn([$user]);

        $controller = $this->createController();

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

        $controller = $this->createController();

        $response = $controller->new($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testShowUser(): void
    {
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);
        $profile->method('getUserName')->willReturn('TestUser');

        $user->method('getProfiles')->willReturn(new ArrayCollection([$profile]));

        $this->userRepository->method('find')->willReturn($user);

        $controller = $this->createController();

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

        $controller = $this->createController();

        $response = $controller->update($request, $user);

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

        $controller = $this->createController();

        $response = $controller->requestAccountDeletion(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}