<?php

namespace App\Tests\Entity;

use App\Entity\Follower;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class FollowerTest extends TestCase
{
    public function testFollowerInitialization(): void
    {
        $follower = new Follower();
        $this->assertInstanceOf(Follower::class, $follower);
        $this->assertInstanceOf(\DateTimeImmutable::class, $follower->getCreatedAt());
    }

    public function testSetAndGetFollower(): void
    {
        $followerEntity = new Follower();
        $profile = new Profile();
        $profile->setUsername('FollowerProfile');

        $followerEntity->setFollower($profile);

        $this->assertSame($profile, $followerEntity->getFollower());
        $this->assertEquals('FollowerProfile', $followerEntity->getFollower()->getUsername());
    }

    public function testSetAndGetFollowing(): void
    {
        $followerEntity = new Follower();
        $profile = new Profile();
        $profile->setUsername('FollowingProfile');

        $followerEntity->setFollowing($profile);

        $this->assertSame($profile, $followerEntity->getFollowing());
        $this->assertEquals('FollowingProfile', $followerEntity->getFollowing()->getUsername());
    }

    public function testCreatedAtIsImmutable(): void
    {
        $follower = new Follower();
        $createdAt = $follower->getCreatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertEqualsWithDelta(new \DateTimeImmutable(), $createdAt, 1, 'Creation date should be set at instantiation');
    }
}