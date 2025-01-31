<?php

namespace App\Tests\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailService;
use App\Service\ResetPasswordService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ResetPasswordServiceTest extends TestCase
{
    private $entityManager;
    private $tokenGenerator;
    private $userRepository;
    private $passwordHasher;
    private $mailService;
    private $queryBuilder;
    private $query;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('update')->willReturnSelf();
        $this->queryBuilder->method('set')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
    }

    public function testRequestPasswordResetSuccess(): void
    {
        $email = 'test@example.com';
        $userData = ['id' => 1, 'email' => $email, 'lastPasswordResetRequest' => null];
        $this->userRepository->method('findOneUserByEmail')->willReturn($userData);
        $this->tokenGenerator->method('generateToken')->willReturn('reset-token');
        $this->mailService->expects($this->once())->method('sendMail');
        $this->query->method('execute')->willReturn(1);

        $service = new ResetPasswordService(
            $this->entityManager,
            $this->tokenGenerator,
            $this->userRepository,
            $this->passwordHasher,
            $this->mailService
        );

        $result = $service->requestPasswordReset($email);

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('resetToken', $result);
        $this->assertEquals('reset-token', $result['resetToken']);
    }

    public function testRequestPasswordResetTooManyRequests(): void
    {
        $email = 'test@example.com';
        $lastRequestTime = (new \DateTime())->modify('-5 minutes')->format('Y-m-d H:i:s');
        $userData = ['id' => 1, 'email' => $email, 'lastPasswordResetRequest' => $lastRequestTime];

        $this->userRepository->method('findOneUserByEmail')->willReturn($userData);

        $service = new ResetPasswordService(
            $this->entityManager,
            $this->tokenGenerator,
            $this->userRepository,
            $this->passwordHasher,
            $this->mailService
        );

        $result = $service->requestPasswordReset($email);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('You can only request a password reset once every 15 minutes.', $result['error']);
        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $result['status']);
    }

    public function testRequestPasswordResetUserNotFound(): void
    {
        $email = 'notfound@example.com';
        $this->userRepository->method('findOneUserByEmail')->willReturn(null);

        $service = new ResetPasswordService(
            $this->entityManager,
            $this->tokenGenerator,
            $this->userRepository,
            $this->passwordHasher,
            $this->mailService
        );

        $result = $service->requestPasswordReset($email);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(ErrorMessagesConstant::USER_NOT_FOUND, $result['error']);
        $this->assertEquals(JsonResponse::HTTP_NOT_FOUND, $result['status']);
    }

    public function testResetPasswordSuccess(): void
    {
        $token = 'valid-token';
        $newPassword = 'new-password';

        $user = $this->createMock(User::class);
        $user->method('getResetPwdToken')->willReturn($token);
        $user->method('getResetPwdTokenLifetime')->willReturn((new \DateTime())->modify('+1 hour'));
        $user->method('getEmail')->willReturn('test@example.com');

        $this->userRepository->method('findOneByResetPwdToken')->willReturn($user);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $this->mailService->expects($this->once())->method('sendMail');
        $this->query->method('execute')->willReturn(1);

        $service = new ResetPasswordService(
            $this->entityManager,
            $this->tokenGenerator,
            $this->userRepository,
            $this->passwordHasher,
            $this->mailService
        );

        $result = $service->resetPassword($token, $newPassword);

        $this->assertArrayHasKey('success', $result);
        $this->assertEquals('Password has been reset successfully', $result['success']);
    }

    public function testResetPasswordInvalidToken(): void
    {
        $token = 'invalid-token';
        $this->userRepository->method('findOneByResetPwdToken')->willReturn(null);

        $service = new ResetPasswordService(
            $this->entityManager,
            $this->tokenGenerator,
            $this->userRepository,
            $this->passwordHasher,
            $this->mailService
        );

        $result = $service->resetPassword($token, 'new-password');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(ErrorMessagesConstant::INVALID_TOKEN, $result['error']);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $result['status']);
    }

    public function testResetPasswordExpiredToken(): void
    {
        $token = 'expired-token';
        $user = $this->createMock(User::class);
        $user->method('getResetPwdTokenLifetime')->willReturn((new \DateTime())->modify('-1 hour'));

        $this->userRepository->method('findOneByResetPwdToken')->willReturn($user);

        $service = new ResetPasswordService(
            $this->entityManager,
            $this->tokenGenerator,
            $this->userRepository,
            $this->passwordHasher,
            $this->mailService
        );

        $result = $service->resetPassword($token, 'new-password');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(ErrorMessagesConstant::TOKEN_EXPIRED, $result['error']);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['status']);
    }
}