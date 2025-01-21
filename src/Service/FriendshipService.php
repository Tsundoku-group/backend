<?php

namespace App\Service;

use App\Repository\FriendshipRepository;

class FriendshipService
{
    private FriendshipRepository $friendshipRepository;

    public function __construct(FriendshipRepository $friendshipRepository)
    {
        $this->friendshipRepository = $friendshipRepository;
    }

    public function getSuggestionsFriendsByProfile(int $profileId, int $limit, int $offset): array
    {
        return $this->friendshipRepository->getAllProfilesWithCommonFriends($profileId, $limit, $offset);
    }
}
