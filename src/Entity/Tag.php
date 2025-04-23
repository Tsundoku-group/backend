<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tag')]
class Tag
{
    /**
     * @var int|null Set by Doctrine
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Tag $parentTag = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parentTag')]
    private Collection $children;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Taggable::class, mappedBy: 'tag')]
    private Collection $taggables;

    public function __construct(string $name, ?Tag $parentTag = null)
    {
        $this->name = $name;
        $this->slug = strtolower(str_replace(' ', '-', $name));
        $this->parentTag = $parentTag;
        $this->createdAt = new DateTimeImmutable();
        $this->children = new ArrayCollection();
        $this->taggables = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->slug = strtolower(str_replace(' ', '-', $name));

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getParentTag(): ?Tag
    {
        return $this->parentTag;
    }

    public function setParentTag(?Tag $parentTag): self
    {
        $this->parentTag = $parentTag;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getTaggables(): Collection
    {
        return $this->taggables;
    }
}
