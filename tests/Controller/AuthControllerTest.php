<?php

namespace App\Tests\Controller;

use App\Controller\AuthController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends TestCase
{
    private $entityManager;
    private $userRepository;
    private $passwordHasher;
    private $jwtManager;
    private $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(EntityRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);


        $this->entityManager->method('getRepository')
            ->willReturn($this->userRepository);
    }

    public function testLoginSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isVerified')->willReturn(true);

        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(true);
        $this->jwtManager->method('create')->willReturn('valid-jwt-token');

        $controller = new AuthController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]));

        $response = $controller->login($request, $this->passwordHasher, $this->entityManager, $this->jwtManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $expectedResponse = [
            'token' => 'valid-jwt-token',
            'isVerified' => true,
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedResponse), $response->getContent());
    }

    public function testLoginInvalidCredentials(): void
    {
        $user = $this->createMock(User::class);

        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(false);

        $controller = new AuthController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]));

        $response = $controller->login($request, $this->passwordHasher, $this->entityManager, $this->jwtManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());

        $expectedResponse = [
            'error' => 'Invalid credentials',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedResponse), $response->getContent());
    }

    public function testLoginUserNotFound(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $controller = new AuthController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]));

        $response = $controller->login($request, $this->passwordHasher, $this->entityManager, $this->jwtManager);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());

        $expectedResponse = [
            'error' => 'Invalid credentials',
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedResponse), $response->getContent());
    }
}