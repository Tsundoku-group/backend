<?php

namespace App\Tests\Controller;

use App\Controller\ResetPasswordController;
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

class ResetPasswordControllerTest extends TestCase
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

    public function testForgotPasswordSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');

        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->tokenGenerator->method('generateToken')->willReturn('sample-reset-token');
        $this->mailService->expects($this->once())->method('sendMail');

        $this->entityManager->expects($this->once())->method('flush');

        $controller = new ResetPasswordController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode(['email' => 'test@example.com']));

        $response = $controller->forgotPassword(
            $request,
            $this->entityManager,
            $this->tokenGenerator,
            $this->mailService
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testForgotPasswordUserNotFound(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $controller = new ResetPasswordController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode(['email' => 'test@example.com']));

        $response = $controller->forgotPassword($request, $this->entityManager, $this->tokenGenerator, $this->mailService);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(['error' => 'User not found'], json_decode($response->getContent(), true));
    }

    public function testResetPasswordSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getResetPwdToken')->willReturn('valid-token');
        $user->method('getResetPwdTokenLifetime')->willReturn((new \DateTime())->modify('+1 hour'));

        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $this->mailService->expects($this->once())->method('sendMail');

        $this->entityManager->expects($this->once())->method('flush');

        $controller = new ResetPasswordController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'token' => 'valid-token',
            'password' => 'new-password'
        ]));

        $response = $controller->resetPassword(
            $request,
            $this->entityManager,
            $this->passwordHasher,
            $this->mailService
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['success' => 'Password has been reset successfully'], json_decode($response->getContent(), true));
    }

    public function testResetPasswordInvalidToken(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $controller = new ResetPasswordController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'token' => 'invalid-token',
            'password' => 'new-password'
        ]));

        $response = $controller->resetPassword($request, $this->entityManager, $this->passwordHasher, $this->mailService);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(['error' => 'Invalid token'], json_decode($response->getContent(), true));
    }

    public function testResetPasswordExpiredToken(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getResetPwdToken')->willReturn('valid-token');
        $user->method('getResetPwdTokenLifetime')->willReturn((new \DateTime())->modify('-1 hour'));

        $this->userRepository->method('findOneBy')->willReturn($user);

        $controller = new ResetPasswordController();
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'token' => 'valid-token',
            'password' => 'new-password'
        ]));

        $response = $controller->resetPassword($request, $this->entityManager, $this->passwordHasher, $this->mailService);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(['error' => 'Token expired'], json_decode($response->getContent(), true));
    }
}