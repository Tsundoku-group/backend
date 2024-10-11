<?php
// src/Security/CustomAuthenticator.php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomAuthenticator extends AbstractAuthenticator
{
    private $JWTManager;
    private $passwordHasher;

    public function __construct(JWTTokenManagerInterface $JWTManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->JWTManager = $JWTManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'api_login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);

        return new Passport(
            new UserBadge($data['email']),
            new PasswordCredentials($data['password'])
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $jwt = $this->JWTManager->create($user);

        return new JsonResponse([
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'isVerified' => $user->isVerified(),
            'token' => $jwt,
            'refresh_token' => $this->JWTManager->create($user),
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
    }
}