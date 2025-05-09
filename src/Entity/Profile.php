<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\Table(name: '`profile`')]
class Profile
{
    /**
     * @var int|null Set by Doctrine
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: GroupProfile::class, mappedBy: 'profile', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $groupProfiles;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'profiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'createdBy', fetch: 'LAZY')]
    private Collection $conversations;

    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'participants', fetch: 'LAZY')]
    private Collection $conversationsParticipants;

    #[ORM\OneToMany(targetEntity: ProfilePhoto::class, mappedBy: 'profile', cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    private Collection $profilePhotos;

    #[ORM\OneToMany(targetEntity: Friendship::class, mappedBy: 'requester', cascade: ['persist', 'remove'], fetch: 'LAZY')]
    private Collection $sentFriendships;

    #[ORM\OneToMany(targetEntity: Friendship::class, mappedBy: 'receiver', cascade: ['persist', 'remove'], fetch: 'LAZY')]
    private Collection $receivedFriendships;

    #[ORM\Column(length: 255)]
    private string $role = 'ROLE_USER';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTime $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagram = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $x = null;

    #[ORM\Column(length: 20, options: ['default' => 'offline'])]
    private string $status = 'offline';

    #[ORM\Column(type: 'string', length: 50)]
    private string $type = 'lecteur';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true, options: ['default' => false])]
    private bool $activeProfile = false;

    /**
     * @var Collection<int, BooksList>
     */
    #[ORM\OneToMany(targetEntity: BooksList::class, mappedBy: 'profile')]
    private Collection $booksLists;

    private const VALID_STATUSES = ['online', 'do_not_disturb', 'away', 'offline'];

    public function __construct()
    {
        $this->groupProfiles = new ArrayCollection();
        $this->profilePhotos = new ArrayCollection();
        $this->role = 'ROLE_USER';
        $this->activeProfile = false;
        $this->createdAt = new DateTimeImmutable();
        $this->conversations = new ArrayCollection();
        $this->conversationsParticipants = new ArrayCollection();
        $this->sentFriendships = new ArrayCollection();
        $this->receivedFriendships = new ArrayCollection();
        $this->booksLists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getProfilePhotos(): Collection
    {
        return $this->profilePhotos;
    }

    public function isActiveProfile(): bool
    {
        return $this->activeProfile;
    }

    public function activate(): void
    {
        $this->status = 'online';
        $this->activeProfile = true;
    }

    public function deactivate(): void
    {
        $this->status = 'offline';
        $this->activeProfile = false;
    }

    public function removeProfilePhoto(ProfilePhoto $profilePhoto): self
    {
        if ($this->profilePhotos->removeElement($profilePhoto)) {
            if ($profilePhoto->getProfile() === $this) {
                $profilePhoto->setProfile(null);
            }
        }

        return $this;
    }

    public function getActiveProfilePhoto(): ?ProfilePhoto
    {
        foreach ($this->profilePhotos as $photo) {
            if ($photo->isActive()) {
                return $photo;
            }
        }

        return null;
    }

    public function getGroups(): Collection
    {
        return $this->groupProfiles;
    }

    public function addGroup(Group $group): self
    {
        if (!$this->groupProfiles->contains($group)) {
            $this->groupProfiles[] = $group;
        }

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        $this->groupProfiles->removeElement($group);

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getBirthday(): DateTime
    {
        return $this->birthday ?? new DateTime();
    }

    public function setBirthday(?DateTime $birthday): self
    {
        if ($birthday instanceof DateTime) {
            $birthday = DateTime::createFromInterface($birthday);
        }
        $this->birthday = $birthday;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;

        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): self
    {
        $this->facebook = $facebook;

        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): self
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getX(): ?string
    {
        return $this->x;
    }

    public function setX(?string $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status value');
        }
        $this->status = $status;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getActiveProfile(): ?Profile
    {
        return $this->activeProfile ? $this : null;
    }

    public function setActiveProfile(bool $isActive): self
    {
        $this->activeProfile = $isActive;

        return $this;
    }

    public function getSentFriendships(): Collection
    {
        return $this->sentFriendships;
    }

    public function addSentFriendship(Friendship $friendship): self
    {
        if (!$this->sentFriendships->contains($friendship)) {
            $this->sentFriendships[] = $friendship;
            $friendship->setRequester($this);
        }

        return $this;
    }

    public function removeSentFriendship(Friendship $friendship): self
    {
        if ($this->sentFriendships->removeElement($friendship)) {
            if ($friendship->getRequester() === $this) {
                $friendship->setRequester(null);
            }
        }

        return $this;
    }

    public function getReceivedFriendships(): Collection
    {
        return $this->receivedFriendships;
    }

    public function addReceivedFriendship(Friendship $friendship): self
    {
        if (!$this->receivedFriendships->contains($friendship)) {
            $this->receivedFriendships[] = $friendship;
            $friendship->setReceiver($this);
        }

        return $this;
    }

    public function removeReceivedFriendship(Friendship $friendship): self
    {
        if ($this->receivedFriendships->removeElement($friendship)) {
            if ($friendship->getReceiver() === $this) {
                $friendship->setReceiver(null);
            }
        }

        return $this;
    }

    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): self
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations[] = $conversation;
            $conversation->setCreatedBy($this);
        }

        return $this;
    }

    public function removeConversation(Conversation $conversation): self
    {
        if ($this->conversations->removeElement($conversation)) {
            if ($conversation->getCreatedBy() === $this) {
                $conversation->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function getConversationsParticipants(): Collection
    {
        return $this->conversationsParticipants;
    }

    public function addConversationsParticipant(Conversation $conversation): self
    {
        if (!$this->conversationsParticipants->contains($conversation)) {
            $this->conversationsParticipants[] = $conversation;
        }

        return $this;
    }

    public function removeConversationsParticipant(Conversation $conversation): self
    {
        $this->conversationsParticipants->removeElement($conversation);

        return $this;
    }

    /**
     * @return Collection<int, BooksList>
     */
    public function getBooksLists(): Collection
    {
        return $this->booksLists;
    }

    public function addBooksList(BooksList $booksList): static
    {
        if (!$this->booksLists->contains($booksList)) {
            $this->booksLists->add($booksList);
            $booksList->setProfile($this);
        }

        return $this;
    }

    public function removeBooksList(BooksList $booksList): static
    {
        if ($this->booksLists->removeElement($booksList)) {
            // set the owning side to null (unless already changed)
            if ($booksList->getProfile() === $this) {
                $booksList->setProfile(null);
            }
        }

        return $this;
    }
}
