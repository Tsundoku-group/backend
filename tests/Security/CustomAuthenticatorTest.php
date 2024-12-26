<?php

namespace App\Tests\Security;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Entity\Profile;
use App\Service\MailService;
use App\Security\CustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CustomAuthenticatorTest extends TestCase
{
    private $JWTManager;
    private $refreshTokenGenerator;
    private $refreshTokenManager;
    private $entityManager;
    private $mailService;
    private $customAuthenticator;

    protected function setUp(): void
    {
        $this->JWTManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->refreshTokenGenerator = $this->createMock(RefreshTokenGeneratorInterface::class);
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mailService = $this->createMock(MailService::class);

        $this->customAuthenticator = new CustomAuthenticator(
            $this->JWTManager,
            $this->refreshTokenGenerator,
            $this->refreshTokenManager,
            $this->entityManager,
            $this->mailService
        );
    }

    public function testAuthenticateValidCredentials(): void
    {
        $data = json_encode(['email' => 'user@example.com', 'password' => 'password123']);
        $request = Request::create('/api_login', 'POST', [], [], [], [], $data);

        $passport = $this->customAuthenticator->authenticate($request);

        $this->assertNotNull($passport);
        $this->assertInstanceOf('Symfony\Component\Security\Http\Authenticator\Passport\Passport', $passport);
    }

    public function testAuthenticateMissingEmail(): void
    {
        $data = json_encode(['password' => 'password123']);
        $request = Request::create('/api_login', 'POST', [], [], [], [], $data);

        $this->expectException(AuthenticationException::class);
        $this->customAuthenticator->authenticate($request);
    }

    public function testAuthenticateInvalidJson(): void
    {
        $data = 'Invalid JSON';
        $request = Request::create('/api_login', 'POST', [], [], [], [], $data);

        $this->expectException(AuthenticationException::class);
        $this->customAuthenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('user@example.com');
        $user->method('getId')->willReturn(1);

        $activeProfile = $this->createMock(Profile::class);
        $activeProfile->method('getId')->willReturn(123);
        $activeProfile->method('getFirstName')->willReturn('John');
        $activeProfile->method('getLastName')->willReturn('Doe');
        $activeProfile->method('getUsername')->willReturn('johndoe');
        $activeProfile->method('getStatus')->willReturn('active');

        $this->entityManager->method('getRepository')->willReturn($this->createMock(\Doctrine\ORM\EntityRepository::class));
        $this->entityManager->getRepository(Profile::class)->method('findOneBy')->willReturn($activeProfile);

        $this->JWTManager->method('create')->willReturn('jwt_token');

        $refreshToken = $this->createMock(RefreshToken::class);
        $refreshToken->method('getRefreshToken')->willReturn('mock_refresh_token');

        $this->refreshTokenGenerator->method('createForUserWithTtl')->willReturn($refreshToken);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $request = new Request();
        $response = $this->customAuthenticator->onAuthenticationSuccess($request, $token, 'api_login');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey('token', json_decode($response->getContent(), true));
    }

    public function testOnAuthenticationFailure(): void
    {
        $exception = $this->createMock(AuthenticationException::class);
        $request = new Request();

        $response = $this->customAuthenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}