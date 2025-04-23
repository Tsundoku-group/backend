<?php

namespace App\Validator\Constraints;

use App\Constant\ProfileErrorMessagesConstant;
use App\Entity\Profile;
use App\Repository\ProfileRepository;
use RuntimeException;

readonly class ProfileValidator
{
    public function __construct(private ProfileRepository $profileRepository)
    {
    }

    public function validateProfile(int $profileId): Profile
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            throw new RuntimeException(ProfileErrorMessagesConstant::PROFILE_NOT_FOUND);
        }

        return $profile;
    }
}
