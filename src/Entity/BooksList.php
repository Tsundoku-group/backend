<?php

namespace App\Entity;

use App\Enum\BooksListTypeEnum;
use App\Enum\VisibilityEnum;
use App\Repository\BooksListRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BooksListRepository::class)]
class BooksList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['public'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'booksLists')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['profile'])]
    private Profile $profile;

    #[ORM\Column(length: 255)]
    #[Groups(['public'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['public'])]
    private ?array $books = [];

    #[ORM\Column(type: 'string', length: 10, nullable: false, enumType: BooksListTypeEnum::class)]
    #[Groups(['public'])]
    private BooksListTypeEnum $type;

    #[ORM\Column(type: 'string', length: 10, nullable: false, enumType: VisibilityEnum::class)]
    #[Groups(['public'])]
    private VisibilityEnum $visibility;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['public'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['public'])]
    private ?DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBooks(): ?array
    {
        return $this->books;
    }

    public function setBooks(?array $books): static
    {
        $this->books = $books;

        return $this;
    }

    public function getType(): BooksListTypeEnum
    {
        return $this->type;
    }

    public function setType(BooksListTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
