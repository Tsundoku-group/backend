<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\FollowerRepository;
use App\Repository\FriendshipRepository;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    private const INTERNAL_SERVER_ERROR = 'Internal Server Error';
    private const PROFILE_NOT_FOUND = 'Profile not found';
    private const USER_NOT_FOUND = 'User not found';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProfileRepository      $profileRepository,
        private readonly UserRepository         $userRepository,
        private readonly FriendshipRepository   $friendshipRepository,
        private readonly FollowerRepository     $followerRepository
    )
    {
    }

    #[Route('/{profileId}', name: 'profile_show', methods: ['GET'])]
    public function show(int $profileId): JsonResponse
    {
        $profile = $this->profileRepository->findProfileById($profileId);

        if (!$profile) {
            return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], 404);
        }

        try {
            $twoFriendsWithProfilePhotos = $this->friendshipRepository->findLastTwoFriendsWithPhotos($profileId);

            $profileData = array_merge(
                $profile,
                [
                    'friendsCount' => $this->friendshipRepository->countFriends($profileId),
                    'followersCount' => $this->followerRepository->countFollowers($profileId),
                    'lastTwoFriends' => $twoFriendsWithProfilePhotos,
                ]
            );

            return new JsonResponse($profileData);
        } catch (Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/all/{id}', name: 'get_profiles', methods: ['GET'])]
    public function getAllProfiles(int $id): JsonResponse
    {
        $profiles = $this->profileRepository->findUserProfiles($id);

        if (empty($profiles)) {
            $user = $this->userRepository->find($id);

            if (!$user) {
                return new JsonResponse(['error' => self::USER_NOT_FOUND], 404);
            }

            return new JsonResponse([
                'email' => $user->getEmail(),
                'hasProfiles' => false,
            ], 200);
        }

        try {
            return new JsonResponse([
                'profiles' => $profiles,
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/new', name: 'create_profile', methods: ['POST'])]
    public function createProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['username'])) {
            return new JsonResponse(['error' => 'Username is required'], Response::HTTP_BAD_REQUEST);
        }

        $existingUsernameProfile = $this->profileRepository->findOneBy(['username' => $data['username']]);
        if ($existingUsernameProfile) {
            return new JsonResponse(['error' => 'Le nom d\'utilisateur est déjà pris'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User is not authenticated or invalid'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $profileCount = $this->profileRepository->count(['user' => $user]);

            if ($profileCount >= 5) {
                return new JsonResponse(['error' => 'Vous ne pouvez pas avoir plus de 5 profils'], 400);
            }

            $profile = new Profile();
            $profile->setUsername($data['username']);
            $profile->setFirstName($data['firstName'] ?? null);
            $profile->setLastName($data['lastName'] ?? null);
            $profile->setType($data['type'] ?? 'lecteur');
            $profile->setBio($data['bio'] ?? null);

            $birthday = empty($data['birthday']) ? null : DateTime::createFromFormat('Y-m-d', $data['birthday']);

            $profile->setBirthday($birthday);
            $profile->setPhoneNumber($data['phoneNumber'] ?? null);
            $profile->setUser($user);

            $entityManager->persist($profile);
            $entityManager->flush();

            return new JsonResponse([
                'id' => $profile->getId(),
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'username' => $profile->getUsername(),
                'status' => 'offline',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/edit', name: 'profile_edit', methods: ['PUT'])]
    public function update(Request $request, Profile $profile): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], 404);
        }

        try {
            $existingUsernameProfile = $this->profileRepository->findOneBy(['username' => $data['username']]);
            if ($existingUsernameProfile) {
                return new JsonResponse(['error' => 'Le nom d\'utilisateur est déjà pris'], Response::HTTP_BAD_REQUEST);
            }

            $profile->setFirstName($data['firstName'] ?? $profile->getFirstName());
            $profile->setLastName($data['lastName'] ?? $profile->getLastName());
            $profile->setUsername($data['username'] ?? $profile->getUsername());
            $profile->setBirthday(new DateTime($data['birthday'] ?? $profile->getBirthday()));
            $profile->setGender($data['gender'] ?? $profile->getGender());
            $profile->setPhoneNumber($data['phoneNumber'] ?? $profile->getPhoneNumber());
            $profile->setBio($data['bio'] ?? $profile->getBio());
            $profile->setFacebook($data['facebook'] ?? $profile->getFacebook());
            $profile->setInstagram($data['instagram'] ?? $profile->getInstagram());
            $profile->setX($data['x'] ?? $profile->getX());
            $profile->setType('lecteur');

            $this->entityManager->flush();

            return $this->json($profile);
        } catch (Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}', name: 'delete_profile', methods: ['DELETE'])]
    public function delete(int $profileId): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['user' => $profileId]);

        if (!$profile) {
            return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], 404);
        }

        try {
            $this->entityManager->remove($profile);
            $this->entityManager->flush();

            return new JsonResponse(null, 204);
        } catch (Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/get-active/{id}', name: 'get_active_profile', methods: ['GET'])]
    public function getActiveProfile(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
        }

        try {
            $activeProfile = $this->profileRepository->findOneBy([
                'user' => $user,
                'activeProfile' => true,
            ]);

            if (!$activeProfile) {
                $activeProfile = $this->profileRepository->findOneBy(['user' => $user]);

                if ($activeProfile) {
                    $activeProfile->setActiveProfile(true);
                    $this->entityManager->flush();
                } else {
                    return new JsonResponse(['error' => 'Aucun profil trouvé pour cet utilisateur'], 404);
                }
            }

            return new JsonResponse([
                'activeProfile' => [
                    'id' => $activeProfile->getId(),
                    'username' => $activeProfile->getUsername(),
                ],
            ]);
        } catch (Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/set-active', name: 'set_active_profile', methods: ['POST'])]
    public function setActiveProfile(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id']) || !isset($data['profileId'])) {
            return new JsonResponse(['error' => 'Invalid request data'], 400);
        }

        $userId = $data['id'];
        $profileId = $data['profileId'];

        $user = $this->userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => self::USER_NOT_FOUND], 404);
        }

        $profile = $this->profileRepository->find($profileId);

        if (!$profile || $user !== $profile->getUser()) {
            return new JsonResponse(['error' => 'Profil invalide ou non associé à cet utilisateur'], 400);
        }

        if ($profile->getActiveProfile()) {
            return new JsonResponse(['message' => 'Ce profil est déjà actif']);
        }

        try {
            $profiles = $this->profileRepository->findBy(['user' => $user]);

            foreach ($profiles as $p) {
                $p->setActiveProfile(false);
            }

            $profile->setActiveProfile(true);
            $this->entityManager->flush();

            $profileData = [
                'id' => $profileId,
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'username' => $profile->getUsername(),
                'status' => $profile->getStatus(),
            ];

            return new JsonResponse($profileData, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/update-status/{id}', name: 'update_status', methods: ['PUT'])]
    public function updateProfileStatus(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        $profile = $this->profileRepository->find($id);

        if (!$profile) {
            return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], 404);
        }

        try {
            $profile->setStatus($newStatus);
            $this->entityManager->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => self::INTERNAL_SERVER_ERROR], 500);
        }

        return new JsonResponse(['success' => true, 'newStatus' => $profile->getStatus()]);
    }
}
