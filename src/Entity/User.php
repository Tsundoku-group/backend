<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(targetEntity: Profile::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $profiles;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tokenRegistration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $tokenRegistrationLifetime = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetPwdToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $resetPwdTokenLifetime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastPasswordResetRequest = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $accountDeletionDate = null;

    public function __construct()
    {
        $this->profiles = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now');
        $this->isVerified = false;
        $this->tokenRegistrationLifetime = (new DateTime('now'))->add(new DateInterval('P1D'));
        $this->resetPwdTokenLifetime = (new DateTime('now'))->add(new DateInterval('PT1H'));
        $this->lastPasswordResetRequest = (new DateTime('now'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isTokenValid(): bool
    {
        return $this->tokenRegistrationLifetime > new DateTime();
    }

    public function isDeletable(): bool
    {
        return $this->accountDeletionDate && $this->accountDeletionDate <= new DateTime();
    }

    public function getTokenRegistration(): ?string
    {
        return $this->tokenRegistration;
    }

    public function setTokenRegistration(?string $tokenRegistration): static
    {
        $this->tokenRegistration = $tokenRegistration;

        return $this;
    }

    public function getTokenRegistrationLifetime(): ?DateTimeInterface
    {
        return $this->tokenRegistrationLifetime;
    }

    public function setTokenRegistrationLifetime(DateTimeInterface $tokenRegistrationLifetime): static
    {
        $this->tokenRegistrationLifetime = $tokenRegistrationLifetime;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getRoles(): array
    {
        if ($this->profiles->isEmpty()) {
            return ['ROLE_USER'];
        }

        $roles = [];
        foreach ($this->profiles as $profile) {
            $roles[] = $profile->getRole();
        }

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    public function getProfiles(): Collection
    {
        return $this->profiles;
    }

    public function addProfile(Profile $profile): self
    {
        if (!$this->profiles->contains($profile)) {
            $this->profiles->add($profile);
            $profile->setUser($this);
        }

        return $this;
    }

    public function removeProfile(Profile $profile): self
    {
        if ($this->profiles->removeElement($profile)) {
            if ($profile->getUser() === $this) {
                $profile->setUser(null);
            }
        }

        return $this;
    }

    public function getResetPwdToken(): ?string
    {
        return $this->resetPwdToken;
    }

    public function setResetPwdToken(?string $resetPwdToken): static
    {
        $this->resetPwdToken = $resetPwdToken;

        return $this;
    }

    public function getResetPwdTokenLifetime(): ?DateTimeInterface
    {
        return $this->resetPwdTokenLifetime;
    }

    public function setResetPwdTokenLifetime(?DateTimeInterface $resetPwdTokenLifetime): static
    {
        $this->resetPwdTokenLifetime = $resetPwdTokenLifetime;

        return $this;
    }

    public function getLastPasswordResetRequest(): ?DateTimeInterface
    {
        return $this->lastPasswordResetRequest;
    }

    public function setLastPasswordResetRequest(?DateTimeInterface $lastPasswordResetRequest): static
    {
        $this->lastPasswordResetRequest = $lastPasswordResetRequest;

        return $this;
    }

    public function getAccountDeletionDate(): ?DateTimeInterface
    {
        return $this->accountDeletionDate;
    }

    public function setAccountDeletionDate(?DateTimeInterface $accountDeletionDate): static
    {
        $this->accountDeletionDate = $accountDeletionDate;

        return $this;
    }
}
