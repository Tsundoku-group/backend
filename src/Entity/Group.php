<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use App\ValueObject\Group\GroupVisibility;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToMany(targetEntity: GroupProfile::class, mappedBy: 'group', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $groupProfiles;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $createdBy;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 10, nullable: false)]
    private ?string $visibility;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $updatedAt = null;

    public function __construct(Profile $createdBy, GroupVisibility $visibility)
    {
        $this->groupProfiles = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTime();
        $this->createdBy = $createdBy;
        $this->visibility = $visibility->getValue();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Collection|GroupProfile[]
     */
    public function getGroupProfiles(): Collection
    {
        return $this->groupProfiles;
    }

    public function addGroupProfile(GroupProfile $groupProfile): self
    {
        if (!$this->groupProfiles->contains($groupProfile)) {
            $this->groupProfiles[] = $groupProfile;
        }

        return $this;
    }

    public function removeGroupProfile(GroupProfile $groupProfile): self
    {
        $this->groupProfiles->removeElement($groupProfile);

        return $this;
    }

    public function getCreatedBy(): Profile
    {
        return $this->createdBy;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getVisibility(): GroupVisibility
    {
        return GroupVisibility::fromString($this->visibility);
    }

    public function setVisibility(GroupVisibility $visibility): self
    {
        $this->visibility = $visibility->getValue();

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isPublic(): bool
    {
        return GroupVisibility::PUBLIC === $this->visibility;
    }

    public function isPrivate(): bool
    {
        return GroupVisibility::PRIVATE === $this->visibility;
    }

    public function isFeedGroup(): bool
    {
        return 'Fil d’actualité' === $this->name;
    }

    public function isMember(Profile $profile): bool
    {
        foreach ($this->groupProfiles as $groupProfile) {
            if ($groupProfile->getProfile()->getId() === $profile->getId()) {
                return true;
            }
        }

        return false;
    }
}
