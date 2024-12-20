<?php

namespace App\Security;

use App\Entity\Profile;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Service\MailService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
    private EntityManagerInterface $entityManager;
    private MailService $mailService;

    public function __construct(
        JWTTokenManagerInterface $JWTManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
        EntityManagerInterface $entityManager,
        MailService $mailService,
    ) {
        $this->JWTManager = $JWTManager;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->entityManager = $entityManager;
        $this->mailService = $mailService;
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

        $activeProfile = $this->entityManager->getRepository(Profile::class)->findOneBy([
            'user' => $user,
            'activeProfile' => true
        ]);

        if (!$activeProfile) {
            $activeProfile = $this->entityManager->getRepository(Profile::class)->findOneBy([
                'user' => $user,
                'createdAt' => 'DESC'
            ]);
        }

        if (!$activeProfile) {
            return new JsonResponse(['error' => 'No profiles found for this user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $now = new DateTime();
        $accountUpdated = false;

        if (null !== $user->getAccountDeletionDate()) {
            if ($user->getAccountDeletionDate() > $now) {
                $user->setAccountDeletionDate(null);

                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $accountUpdated = true;

                $htmlContent = file_get_contents(__DIR__ . '/../Emails/reActivated_account_mail.html');
                $profiles = $user->getProfiles();

                foreach ($profiles as $profile) {
                    $firstName = $profile->getFirstName();
                    $lastName = $profile->getLastName();

                    $email = $user->getEmail();
                    $subject = 'Ré-activation de votre compte';
                    $htmlContent = str_replace(
                        ['{firstName}', '{lastName}'],
                        [$firstName, $lastName],
                        $htmlContent
                    );

                    try {
                        $this->mailService->sendMail(
                            $email,
                            $subject,
                            $htmlContent
                        );
                    } catch (Exception $e) {
                        error_log('Erreur lors de l\'envoi de l\'email de suppression : ' . $e->getMessage());
                    }
                }
            }
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

        $activeProfileData = [
            'id' => $activeProfile->getId(),
            'firstName' => $activeProfile->getFirstName(),
            'lastName' => $activeProfile->getLastName(),
            'username' => $activeProfile->getUsername(),
            'status' => $activeProfile->getStatus(),
        ];

        $response = [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'isVerified' => $user->isVerified(),
            'token' => $jwt,
            'refresh_token' => $refreshTokenString,
            'activeProfile' => $activeProfileData,
        ];

        if ($accountUpdated) {
            $response['message'] = 'Votre compte a été mis à jour et ne sera pas supprimé.';
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
    }
}
