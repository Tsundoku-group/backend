<?php

namespace App\Tests\Controller;

use App\Controller\RegisterController;
use App\Entity\User;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegisterControllerTest extends TestCase
{
    private $entityManager;
    private $userRepository;
    private $passwordHasher;
    private $mailService;
    private $tokenGenerator;
    private $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(EntityRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->entityManager->method('getRepository')->willReturn($this->userRepository);
    }

    private function createController(): RegisterController
    {
        return new RegisterController(
            $this->entityManager,
            $this->passwordHasher,
            $this->tokenGenerator,
            $this->mailService
        );
    }

    public function testRegisterSuccess(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->tokenGenerator->method('generateToken')->willReturn('sample-token');
        $this->passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $this->mailService->expects($this->once())->method('sendMail');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $controller = $this->createController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]));

        $response = $controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRegisterEmailAlreadyUsed(): void
    {
        $existingUser = $this->createMock(User::class);

        $this->userRepository->method('findOneBy')->willReturn($existingUser);

        $controller = $this->createController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]));

        $response = $controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals(['error' => 'Email already in use'], json_decode($response->getContent(), true));
    }

    public function testRegisterInvalidData(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([]));

        $response = $controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(['error' => 'Invalid data'], json_decode($response->getContent(), true));
    }
}