<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'taggable')]
class Taggable
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Tag::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tag $tag;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $taggableType;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $taggableId;


    #[ORM\ManyToOne(targetEntity: Group::class, inversedBy: 'taggables')]
    #[ORM\JoinColumn(name: 'taggable_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Group $group = null;

    public function __construct(Tag $tag, string $taggableType, int $taggableId, ?Group $group = null)
    {
        $this->tag = $tag;
        $this->taggableType = $taggableType;
        $this->taggableId = $taggableId;
        $this->group = $group;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }

    public function getTaggableType(): string
    {
        return $this->taggableType;
    }

    public function getTaggableId(): int
    {
        return $this->taggableId;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }
}