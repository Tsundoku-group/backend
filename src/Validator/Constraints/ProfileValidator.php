<?php

namespace App\Validator\Constraints;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Profile;
use App\Repository\ProfileRepository;

readonly class ProfileValidator
{
    public function __construct(private ProfileRepository $profileRepository) {}

    public function validateProfile(int $profileId): Profile
    {
        $profile = $this->profileRepository->find($profileId);

        if (!$profile) {
            throw new \RuntimeException(ErrorMessagesConstant::PROFILE_NOT_FOUND);
        }

        return $profile;
    }
}