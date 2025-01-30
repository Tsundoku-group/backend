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
    )
    {
    }

    public function sendFriendRequest(int $profileId, int $friendId): array
    {
        $requesterProfileUser = $this->profileRepository->find($profileId);
        $receiverProfileUser = $this->profileRepository->find($friendId);

        if (!$requesterProfileUser || !$receiverProfileUser) {
            return ['error' => 'Requester or receiver not found.', 'status' => 404];
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

            return ['message' => 'Friend request sent'];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }


    public function acceptFriendRequest(int $friendshipId): array
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($friendshipId);

        if (!$friendship) {
            return ['error' => 'Friend request not found.', 'status' => 404];
        }

        if ($friendship->getStatus() !== Friendship::STATUS_PENDING) {
            return ['error' => 'Friend request already processed.', 'status' => 409];
        }

        try {
            $friendship->setStatus(Friendship::STATUS_ACCEPTED);
            $this->entityManager->flush();

            return ['message' => 'Friend request accepted.'];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function rejectFriendRequest(int $friendshipId): array
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($friendshipId);

        if (!$friendship) {
            return ['error' => 'Friend request not found.', 'status' => 404];
        }

        if ($friendship->getStatus() !== Friendship::STATUS_PENDING) {
            return ['error' => 'Friend request already processed.', 'status' => 409];
        }

        try {
            $friendship->setStatus(Friendship::STATUS_REJECTED);
            $this->entityManager->flush();

            return ['message' => 'Friend request rejected.'];
        } catch (Exception $e) {
            return ['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'status' => 500];
        }
    }

    public function removeFriend(int $friendshipId, $dto): array
    {
        $friendship = $this->entityManager->getRepository(Friendship::class)->find($friendshipId);

        if (!$friendship) {
            return ['error' => 'Friendship not found.', 'status' => 404];
        }

        if ($friendship->getStatus() !== Friendship::STATUS_ACCEPTED) {
            return ['error' => 'Friendship not accepted.', 'status' => 409];
        }

        if (
            ($friendship->getRequester()->getId() !== $dto->requesterId && $friendship->getReceiver()->getId() !== $dto->requesterId) ||
            ($friendship->getRequester()->getId() !== $dto->receiverId && $friendship->getReceiver()->getId() !== $dto->receiverId)
        ) {
            return ['error' => 'You are not authorized to remove this friendship.', 'status' => 403];
        }

        try {
            $this->entityManager->remove($friendship);
            $this->entityManager->flush();

            return ['message' => 'Friend removed.'];
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
                return ['message' => 'No friends found.', 'status' => 404];
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
                return ['message' => 'No friend requests found.', 'status' => 200];
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
        $statusMessage = $isInverse ? " (inverse)" : "";

        if (Friendship::STATUS_PENDING === $friendship->getStatus()) {
            return ['error' => "Request already sent. Status: pending" . $statusMessage, 'status' => 409];
        }

        if (Friendship::STATUS_REJECTED === $friendship->getStatus()) {
            $friendship->setStatus(Friendship::STATUS_PENDING);
            $this->entityManager->flush();

            return ['message' => "Friend request resent after rejection" . $statusMessage];
        }

        if (Friendship::STATUS_ACCEPTED === $friendship->getStatus()) {
            return ['error' => "Friendship already exists" . $statusMessage, 'status' => 409];
        }

        return ['error' => 'Unexpected status', 'status' => 500];
    }

    public function getSuggestionsFriendsByProfile(int $profileId, int $limit, int $offset): array
    {
        return $this->friendshipRepository->getAllProfilesWithCommonFriends($profileId, $limit, $offset);
    }
}
