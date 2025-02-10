<?php

namespace App\Entity;

use App\Security\Voter\Group\GroupRoleVoter;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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

    #[ORM\Column(type: 'string', length: 10, nullable: false)]
    private string $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $joinAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $updatedAt = null;

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(Group $group, Profile $profile, string $role, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->group = $group;
        $this->profile = $profile;
        $this->role = $role;
        $this->joinAt = new DateTimeImmutable();
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        if (!$this->authorizationChecker->isGranted(GroupRoleVoter::MANAGE_MEMBERS, $this->group)) {
            throw new \RuntimeException("Access denied.");
        }
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

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    #[ORM\PreUpdate]
    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTime();
    }
}