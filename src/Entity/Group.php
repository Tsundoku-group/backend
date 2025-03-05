<?php

namespace App\Entity;

use App\Repository\GroupRepository;
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
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Taggable::class, mappedBy: 'group', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $taggables;

    public function __construct(Profile $createdBy)
    {
        $this->groupProfiles = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTime();
        $this->createdBy = $createdBy;
        $this->taggables = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
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

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTaggables(): Collection
    {
        return $this->taggables;
    }

    public function addTag(Tag $tag): self
    {
        $taggable = new Taggable($tag, 'group', $this->id);
        if (!$this->taggables->contains($taggable)) {
            $this->taggables->add($taggable);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        foreach ($this->taggables as $taggable) {
            if ($taggable->getTag() === $tag) {
                $this->taggables->removeElement($taggable);
                break;
            }
        }
        return $this;
    }

    public function getTags(): array
    {
        return $this->taggables->map(fn (Taggable $taggable) => $taggable->getTag())->toArray();
    }
}