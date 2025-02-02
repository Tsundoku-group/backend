<?php

namespace App\Entity;

use App\ValueObject\GroupRole;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'group_profile')]
class GroupProfile
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'groupProfiles')]
    #[ORM\JoinColumn(nullable: false)]
    private Group $group;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Profile::class, inversedBy: 'groupProfiles')]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $profile;

    #[ORM\Column(type: "string", length: 10, nullable: false)]
    private string $role;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $joinAt;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $updatedAt;

    public function __construct(Group $group, Profile $profile, GroupRole $role)
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

    public function getRole(): GroupRole
    {
        return GroupRole::fromString($this->role);
    }

    public function setRole(GroupRole $role): void
    {
        $this->role = $role->getValue();
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

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    #[ORM\PreUpdate]
    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}