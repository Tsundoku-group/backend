<?php

namespace App\Tests\Service;

use App\Constant\UserErrorMessagesConstant;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailService;
use App\Service\RegisterService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegisterServiceTest extends TestCase
{
    private $entityManager;
    private $userRepository;
    private $passwordHasher;
    private $tokenGenerator;
    private $mailService;
    private $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $this->mailService = $this->createMock(MailService::class);

        $this->service = new RegisterService(
            $this->entityManager,
            $this->userRepository,
            $this->passwordHasher,
            $this->tokenGenerator,
            $this->mailService
        );
    }

    public function testRegisterUserSuccess(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->userRepository->method('findOneUserByEmail')->willReturn(null);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $this->tokenGenerator->method('generateToken')->willReturn('sample-token');

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->mailService->expects($this->once())->method('sendMail');

        $response = $this->service->registerUser($email, $password);

        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
    }

    public function testRegisterUserEmailAlreadyInUse(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->userRepository->method('findOneUserByEmail')->willReturn([
            'id' => 1,
            'email' => $email,
        ]);

        $response = $this->service->registerUser($email, $password);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Email already in use', $response['error']);
        $this->assertEquals(409, $response['status']);
    }

    public function testConfirmUserSuccess(): void
    {
        $token = 'sample-token';
        $user = $this->createMock(User::class);

        $user->method('getEmail')->willReturn('user@example.com');

        $this->userRepository->method('findOneByRegistrationToken')->willReturn($user);
        $user->expects($this->once())->method('setTokenRegistration')->with(null);
        $user->expects($this->once())->method('setVerified')->with(true);

        $this->entityManager->expects($this->once())->method('flush');
        $this->mailService->expects($this->once())->method('sendMail');

        $this->service->confirmUser($token);
    }

    public function testConfirmUserTokenNotFound(): void
    {
        $token = 'invalid-token';

        $this->userRepository->method('findOneByRegistrationToken')->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(UserErrorMessagesConstant::USER_NOT_FOUND);
        $this->expectExceptionCode(404);

        $this->service->confirmUser($token);
    }

    public function testResendConfirmationEmailSuccess(): void
    {
        $email = 'test@example.com';
        $user = $this->createMock(User::class);

        $user->method('getEmail')->willReturn($email);
        $user->method('getTokenRegistration')->willReturn('sample-token');
        $user->method('getTokenRegistrationLifetime')->willReturn(new \DateTime());

        $this->userRepository->method('findOneUserByEmailAndIsVerified')->willReturn($user);
        $this->tokenGenerator->method('generateToken')->willReturn('new-token');

        $user->expects($this->once())->method('setTokenRegistration')->with('new-token');
        $user->expects($this->once())->method('setTokenRegistrationLifetime');

        $this->entityManager->expects($this->once())->method('flush');
        $this->mailService->expects($this->once())->method('sendMail');

        $this->service->resendConfirmationEmail($email);
    }

    public function testResendConfirmationEmailUserNotFound(): void
    {
        $email = 'test@example.com';

        $this->userRepository->method('findOneUserByEmailAndIsVerified')->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found');
        $this->expectExceptionCode(404);

        $this->service->resendConfirmationEmail($email);
    }
}