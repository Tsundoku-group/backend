<?php

namespace App\DTO\GroupProfile;

use Symfony\Component\Validator\Constraints as Assert;

class JoinGroupDTO
{
    #[Assert\NotBlank(message: "L'ID du groupe est requis.")]
    #[Assert\Type(type: "integer", message: "L'ID du groupe doit être un entier.")]
    public int $groupId;

    #[Assert\NotBlank(message: "L'ID du profil est requis.")]
    #[Assert\Type(type: "integer", message: "L'ID du profil doit être un entier.")]
    public int $profileId;

    #[Assert\NotBlank(message: "Le rôle est requis.")]
    #[Assert\Choice(choices: ['admin', 'member'], message: "Le rôle doit être 'admin' ou 'member'.")]
    public string $role;

    public function __construct(int $groupId, int $profileId, string $role)
    {
        $this->groupId = $groupId;
        $this->profileId = $profileId;
        $this->role = $role;
    }
}