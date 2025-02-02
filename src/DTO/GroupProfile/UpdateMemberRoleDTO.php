<?php

namespace App\DTO\GroupProfile;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateMemberRoleDTO
{
    #[Assert\NotBlank(message: "L'ID du groupe est requis.")]
    #[Assert\Type(type: "integer", message: "L'ID du groupe doit être un entier.")]
    public int $groupId;

    #[Assert\NotBlank(message: "L'ID du membre est requis.")]
    #[Assert\Type(type: "integer", message: "L'ID du membre doit être un entier.")]
    public int $memberId;

    #[Assert\NotBlank(message: "Le nouveau rôle est requis.")]
    #[Assert\Choice(choices: ['admin', 'member'], message: "Le rôle doit être 'admin' ou 'member'.")]
    public string $newRole;

    #[Assert\NotBlank(message: "L'ID de l'admin est requis.")]
    #[Assert\Type(type: "integer", message: "L'ID de l'admin doit être un entier.")]
    public int $adminId;

    public function __construct(int $groupId, int $memberId, string $newRole, int $adminId)
    {
        $this->groupId = $groupId;
        $this->memberId = $memberId;
        $this->newRole = $newRole;
        $this->adminId = $adminId;
    }
}