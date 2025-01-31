<?php

namespace App\Entity;

use App\Enum\GroupRoleEnum;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'group_profile')]
class GroupProfile
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Group $group;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $profile;

    #[ORM\Column(type: "group_role", enumType: GroupRoleEnum::class)]
    private GroupRoleEnum $role;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $joinAt;

    #[ORM\Column(type: "boolean")]
    private bool $isBanned = false;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $updatedAt;

    public function __construct(Group $group, Profile $profile, GroupRoleEnum $role = GroupRoleEnum::MEMBER)
    {
        $this->group = $group;
        $this->profile = $profile;
        $this->role = $role;
        $this->joinAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getRole(): GroupRoleEnum
    {
        return $this->role;
    }

    public function setRole(GroupRoleEnum $role): void
    {
        $this->role = $role;
        $this->markAsUpdated();
    }

    public function getJoinAt(): DateTimeImmutable
    {
        return $this->joinAt;
    }

    public function setJoinAt(DateTimeImmutable $joinAt): void
    {
        $this->joinAt = $joinAt;
        $this->markAsUpdated();
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setBanned(bool $banned): void
    {
        $this->isBanned = $banned;
        $this->markAsUpdated();
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}