<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Friendship;
use App\Repository\FriendshipRepository;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class FriendshipService
{
    public function __construct(
        private FriendshipRepository $friendshipRepository,
        private EntityManagerInterface $entityManager,
        private ProfileRepository $profileRepository,
    ) {
    }

    public function sendFriendRequest(int $profileId, int $friendId): array
    {
        $requesterProfileUser = $this->profileRepository->find($profileId);
        $receiverProfileUser = $this->profileRepository->find($friendId);

        if (!$requesterProfileUser || !$receiverProfileUser) {
            return ['error' => "Le demandeur ou le destinataire n'a pas été trouvé", 'status' => 404];
        }

        $existingFriendship = $this->entityManager->getRepository(Friendship::class)->findOneBy([
            'requester' => $requesterProfileUser,
            'receiver' => $receiverProfileUser,
        ]);

        $existingInverseFriendship = $this->entityManager->getRepository(Friendship::class)->findOneBy([
            'requester' => $receiverProfileUser,
            'receiver' => $requesterProfileUser,
        ]);

        if ($existingFriendship) {
            return $this->handleExistingFriendship($existingFriendship);
        }

        if ($existingInverseFriendship) {
            return $this->handleExistingFriendship($existingInverseFriendship, true);
        }

        try {
            $friendship = new Friendship();
            $friendship->setRequester($requesterProfileUser);
            $friendship->setReceiver($receiverProfileUser);
            $friendship->setStatus(Friendship::STATUS_PENDING);

            $this->entityManager->persist($friendship);
            $this->entityManager->flush();

            return ['message' => "Demande d'amitié envoyée"];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function acceptFriendRequest(int $friendshipId): array
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($friendshipId);

        if (!$friendship) {
            return ['error' => "Demande d'ami non trouvée", 'status' => 404];
        }

        if (Friendship::STATUS_PENDING !== $friendship->getStatus()) {
            return ['error' => "Demande d'ami déjà traitée", 'status' => 409];
        }

        try {
            $friendship->setStatus(Friendship::STATUS_ACCEPTED);
            $this->entityManager->flush();

            return ['message' => "Demande d'amitié acceptée"];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function rejectFriendRequest(int $friendshipId): array
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($friendshipId);

        if (!$friendship) {
            return ['error' => "Demande d'ami non trouvée", 'status' => 404];
        }

        if (Friendship::STATUS_PENDING !== $friendship->getStatus()) {
            return ['error' => "Demande d'ami déjà traitée", 'status' => 409];
        }

        try {
            $friendship->setStatus(Friendship::STATUS_REJECTED);
            $this->entityManager->flush();

            return ['message' => "Demande d'ami rejetée"];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function removeFriend(int $friendshipId, $dto): array
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($friendshipId);

        if (!$friendship) {
            return ['error' => "L'amitié n'a pas été trouvée", 'status' => 404];
        }

        if (Friendship::STATUS_ACCEPTED !== $friendship->getStatus()) {
            return ['error' => "L'amitié n'est pas acceptée", 'status' => 409];
        }

        if (
            ($friendship->getRequester()->getId() !== $dto->requesterId && $friendship->getReceiver()->getId() !== $dto->requesterId)
            || ($friendship->getRequester()->getId() !== $dto->receiverId && $friendship->getReceiver()->getId() !== $dto->receiverId)
        ) {
            return ['error' => "Vous n'êtes pas autorisé à supprimer cette amitié", 'status' => 403];
        }

        try {
            $this->entityManager->remove($friendship);
            $this->entityManager->flush();

            return ['message' => 'Ami supprimé'];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function listFriends(int $profileId, $request): array
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            return ['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404];
        }

        try {
            $limit = max((int) $request->query->get('limit', 20), 1);
            $offset = max((int) $request->query->get('offset', 0), 0);

            $friendships = $this->friendshipRepository->findFriendshipUserProfileId($profileId, $limit, $offset);

            if (empty($friendships)) {
                return ['message' => "Aucun ami n'a été trouvé", 'status' => 404];
            }

            return $friendships;
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function listFriendRequests(int $profileId): array
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            return ['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND, 'status' => 404];
        }

        try {
            $friendRequests = $this->friendshipRepository->findBy([
                'receiver' => $profile,
                'status' => Friendship::STATUS_PENDING,
            ]);

            if (empty($friendRequests)) {
                return ['message' => "Aucune demande d'ami n'a été trouvée", 'status' => 200];
            }

            return array_map(function ($friendship) {
                $requesterProfile = $friendship->getRequester();
                $requesterUser = $requesterProfile->getUser();

                return [
                    'id' => $friendship->getId(),
                    'requester' => [
                        'id' => $requesterProfile->getId(),
                        'email' => $requesterUser?->getEmail(),
                        'username' => $requesterProfile->getUsername(),
                    ],
                    'status' => $friendship->getStatus(),
                    'createdAt' => $friendship->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $friendRequests);
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    private function handleExistingFriendship(Friendship $friendship, bool $isInverse = false): array
    {
        $statusMessage = $isInverse ? ' (inverse)' : '';

        if (Friendship::STATUS_PENDING === $friendship->getStatus()) {
            return ['error' => "Demande déjà envoyée. Statut : en attente" . $statusMessage, 'status' => 409];
        }

        if (Friendship::STATUS_REJECTED === $friendship->getStatus()) {
            $friendship->setStatus(Friendship::STATUS_PENDING);
            $this->entityManager->flush();

            return ['message' => "Demande d'ami renvoyée après avoir été rejetée" . $statusMessage];
        }

        if (Friendship::STATUS_ACCEPTED === $friendship->getStatus()) {
            return ['error' => "L'amitié existe déjà" . $statusMessage, 'status' => 409];
        }

        return ['error' => 'Statut inattendu', 'status' => 500];
    }

    public function getSuggestionsFriendsByProfile(int $profileId, int $limit, int $offset): array
    {
        return $this->friendshipRepository->getAllProfilesWithCommonFriends($profileId, $limit, $offset);
    }
}
