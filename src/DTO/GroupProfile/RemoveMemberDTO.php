<?php

namespace App\DTO\GroupProfile;

use Symfony\Component\Validator\Constraints as Assert;

class RemoveMemberDTO
{
    #[Assert\NotBlank(message: "L'ID du groupe est requis.")]
    #[Assert\Type(type: 'integer', message: "L'ID du groupe doit être un entier.")]
    public int $groupId;

    #[Assert\NotBlank(message: "L'ID du profil est requis.")]
    #[Assert\Type(type: 'integer', message: "L'ID du profil doit être un entier.")]
    public int $profileId;

    #[Assert\NotBlank(message: "L'ID de l'admin est requis.")]
    #[Assert\Type(type: 'integer', message: "L'ID de l'admin doit être un entier.")]
    public int $adminId;

    public function __construct(int $groupId, int $profileId, int $adminId)
    {
        $this->groupId = $groupId;
        $this->profileId = $profileId;
        $this->adminId = $adminId;
    }
}
