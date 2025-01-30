<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailService;
use App\Service\UserService;
use App\Validator\Constraints\CaptchaValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserControllerTest extends TestCase
{
    private $entityManager;
    private $userRepository;
    private $mailService;
    private $captchaValidator;
    private $userService;
    private $tokenStorage;
    private $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->captchaValidator = $this->createMock(CaptchaValidator::class);
        $this->userService = $this->createMock(UserService::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function createController(): UserController
    {
        $controller = new UserController(
            $this->entityManager,
            $this->userRepository,
            $this->mailService,
            $this->captchaValidator,
            $this->userService
        );
        $controller->setContainer($this->container);

        return $controller;
    }

    public function testUpdateUser(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'updated@example.com',
            'password' => 'newpassword123',
        ]));

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        $this->entityManager->expects($this->once())->method('flush');

        $controller = $this->createController();
        $response = $controller->update($request, $user);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function mockAuthenticatedUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');

        $token = new UsernamePasswordToken($user, 'main', []);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($token);
    }
}