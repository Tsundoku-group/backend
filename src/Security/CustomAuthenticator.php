<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\RefreshToken;
use DateTime;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class CustomAuthenticator extends AbstractAuthenticator
{
    private JWTTokenManagerInterface $JWTManager;
    private RefreshTokenGeneratorInterface $refreshTokenGenerator;
    private RefreshTokenManagerInterface $refreshTokenManager;

    public function __construct(
        JWTTokenManagerInterface       $JWTManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface   $refreshTokenManager
    )
    {
        $this->JWTManager = $JWTManager;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->refreshTokenManager = $refreshTokenManager;
    }

    public function supports(Request $request): ?bool
    {
        return 'api_login' === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $content = $request->getContent();

        if (!json_validate($content)) {
            throw new AuthenticationException('Invalid JSON provided');
        }

        $data = json_decode($content, true);

        if (empty($data['email']) || empty($data['password'])) {
            throw new AuthenticationException('Email or password missing');
        }

        return new Passport(
            new UserBadge($data['email']),
            new PasswordCredentials($data['password'])
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $jwt = $this->JWTManager->create($user);

        $refreshTokenEntity = $this->refreshTokenGenerator->createForUserWithTtl(
            $user,
            (new DateTime())->modify('+1 month')->getTimestamp()
        );

        if (!$refreshTokenEntity instanceof RefreshToken) {
            return new JsonResponse(['error' => 'Failed to generate a refresh token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->refreshTokenManager->save($refreshTokenEntity);
        $refreshTokenString = $refreshTokenEntity->getRefreshToken();

        return new JsonResponse([
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'isVerified' => $user->isVerified(),
            'token' => $jwt,
            'refresh_token' => $refreshTokenString,
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
    }
}
