<?php

namespace App\Entity;

use App\Repository\FollowerRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowerRepository::class)]
#[ORM\Table(name: "follower", indexes: [
    new ORM\Index(name: "idx_follower_id", columns: ["follower_id"]),
    new ORM\Index(name: "idx_following_id", columns: ["following_id"]),
    new ORM\Index(name: "idx_follower_following", columns: ["follower_id", "following_id"])
])]
class Follower
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(name: "follower_id", referencedColumnName: "id", nullable: false)]
    private ?Profile $follower = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(name: "following_id", referencedColumnName: "id", nullable: false)]
    private ?Profile $following = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollower(): ?Profile
    {
        return $this->follower;
    }

    public function setFollower(Profile $follower): self
    {
        $this->follower = $follower;

        return $this;
    }

    public function getFollowing(): ?Profile
    {
        return $this->following;
    }

    public function setFollowing(Profile $following): self
    {
        $this->following = $following;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}
