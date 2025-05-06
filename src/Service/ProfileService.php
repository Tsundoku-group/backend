<?php

namespace App\Service;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use App\DTO\Profile\SetActiveProfileDTO;
use App\DTO\Profile\UpdateProfileStatusDTO;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\FollowerRepository;
use App\Repository\FriendshipRepository;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;

readonly class ProfileService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProfileRepository $profileRepository,
        private UserRepository $userRepository,
        private FriendshipRepository $friendshipRepository,
        private FollowerRepository $followerRepository,
    ) {
    }

    public function getProfileWithStats(int $profileId): ?array
    {
        $profile = $this->profileRepository->findProfileById($profileId);
        if (!$profile) {
            return null;
        }

        try {
            return array_merge(
                $profile,
                [
                    'friendsCount' => $this->friendshipRepository->countFriends($profileId),
                    'followersCount' => $this->followerRepository->countFollowers($profileId),
                    'lastTwoFriends' => $this->friendshipRepository->findLastTwoFriendsWithPhotos($profileId),
                ]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    public function getUserProfiles(int $userId): array
    {
        $profiles = $this->profileRepository->findUserProfiles($userId);

        if (empty($profiles)) {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return ['error' => UserErrorMessagesConstant::USER_NOT_FOUND, 'status' => 404];
            }

            return [
                'email' => $user->getEmail(),
                'hasProfiles' => false,
            ];
        }

        return ['profiles' => $profiles];
    }

    public function createProfile(array $data, User $user): array
    {
        if (empty($data['username'])) {
            return ['error' => 'Username is required', 'status' => 400];
        }

        $existingProfile = $this->profileRepository->findProfileByUsername($data['username']);
        if ($existingProfile) {
            return ['error' => "Le nom d'utilisateur est déjà pris", 'status' => 400];
        }

        if ($this->profileRepository->count(['user' => $user]) >= 5) {
            return ['error' => 'Vous ne pouvez pas avoir plus de 5 profils', 'status' => 400];
        }

        try {
            $profile = new Profile();
            $profile->setUsername($data['username']);
            $profile->setFirstName($data['firstName'] ?? null);
            $profile->setLastName($data['lastName'] ?? null);
            $profile->setType($data['type'] ?? 'lecteur');
            $profile->setBio($data['bio'] ?? null);
            $profile->setBirthday(isset($data['birthday']) ? new DateTime($data['birthday']) : null);
            $profile->setPhoneNumber($data['phoneNumber'] ?? null);
            $profile->setUser($user);

            $this->entityManager->persist($profile);
            $this->entityManager->flush();

            return [
                'id' => $profile->getId(),
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'username' => $profile->getUsername(),
                'status' => 'offline',
            ];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function updateProfile(int $profileId, array $data): ?array
    {
        $profile = $this->profileRepository->find($profileId);
        if (!$profile) {
            return null;
        }

        $existingUsernameProfile = $this->profileRepository->findProfileByUsername($data['username']);
        if ($existingUsernameProfile) {
            return ['error' => "Le nom d'utilisateur est déjà pris", 'status' => 400];
        }

        try {
            $profile->setFirstName($data['firstName'] ?? $profile->getFirstName());
            $profile->setLastName($data['lastName'] ?? $profile->getLastName());
            $profile->setUsername($data['username'] ?? $profile->getUsername());
            $profile->setBirthday(isset($data['birthday']) ? new DateTime($data['birthday']) : $profile->getBirthday());
            $profile->setGender($data['gender'] ?? $profile->getGender());
            $profile->setPhoneNumber($data['phoneNumber'] ?? $profile->getPhoneNumber());
            $profile->setBio($data['bio'] ?? $profile->getBio());
            $profile->setFacebook($data['facebook'] ?? $profile->getFacebook());
            $profile->setInstagram($data['instagram'] ?? $profile->getInstagram());
            $profile->setX($data['x'] ?? $profile->getX());

            $this->entityManager->flush();

            return ['success' => 'Profile updated successfully'];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function deleteProfile(int $profileId): bool
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            return false;
        }

        try {
            $this->entityManager->remove($profile);
            $this->entityManager->flush();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getActiveProfile(int $userId): ?array
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return null;
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
                    return null;
                }
            }

            return [
                'activeProfile' => [
                    'id' => $activeProfile->getId(),
                    'username' => $activeProfile->getUsername(),
                ],
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    public function setActiveProfile(SetActiveProfileDTO $dto): ?array
    {
        $user = $this->userRepository->find($dto->userId);
        if (!$user) {
            return ['error' => UserErrorMessagesConstant::USER_NOT_FOUND, 'status' => 404];
        }

        $profile = $this->profileRepository->find($dto->profileId);
        if (!$profile || $user !== $profile->getUser()) {
            return ['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 400];
        }

        if ($profile->getActiveProfile()) {
            return ['message' => 'Ce profil est déjà actif'];
        }

        try {
            $profiles = $this->profileRepository->findBy(['user' => $user]);
            foreach ($profiles as $p) {
                $p->setActiveProfile(false);
            }

            $profile->setActiveProfile(true);
            $this->entityManager->flush();

            return [
                'id' => $profile->getId(),
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'username' => $profile->getUsername(),
                'status' => $profile->getStatus(),
            ];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function updateProfileStatus(UpdateProfileStatusDTO $dto, int $profileId): ?array
    {
        $profile = $this->profileRepository->find($profileId);
        if (!$profile) {
            return null;
        }

        try {
            $profile->setStatus($dto->status);
            $this->entityManager->flush();

            return ['success' => true, 'newStatus' => $profile->getStatus()];
        } catch (Exception $e) {
            return ['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function searchProfile(string $search, int $page = 1, int $limit = 20): ?array
    {
        $offset = ($page - 1) * $limit;
        $profiles = $this->profileRepository->findProfiles($search,  $limit, $offset);

        try {
            return $profiles;
        } catch (Exception $e) {
            return null;
        }
    }
}
