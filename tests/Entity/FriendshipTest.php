<?php

namespace App\Tests\Entity;

use App\Entity\Friendship;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class FriendshipTest extends TestCase
{
    public function testInitialization(): void
    {
        $friendship = new Friendship();

        $this->assertEquals(Friendship::STATUS_PENDING, $friendship->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $friendship->getCreatedAt());
        $this->assertNull($friendship->getUpdatedAt());
        $this->assertNull($friendship->getRequester());
        $this->assertNull($friendship->getReceiver());
        $this->assertNull($friendship->getFriendAt());
    }

    public function testSetAndGetStatus(): void
    {
        $friendship = new Friendship();
        $friendship->setStatus(Friendship::STATUS_ACCEPTED);

        $this->assertEquals(Friendship::STATUS_ACCEPTED, $friendship->getStatus());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $friendship = new Friendship();
        $date = new \DateTimeImmutable();

        $friendship->setCreatedAt($date);

        $this->assertSame($date, $friendship->getCreatedAt());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $friendship = new Friendship();
        $date = new \DateTime();

        $friendship->setUpdatedAt($date);

        $this->assertSame($date, $friendship->getUpdatedAt());
    }

    public function testSetAndGetRequester(): void
    {
        $friendship = new Friendship();
        $requester = $this->createMock(Profile::class);

        $friendship->setRequester($requester);

        $this->assertSame($requester, $friendship->getRequester());
    }

    public function testSetAndGetReceiver(): void
    {
        $friendship = new Friendship();
        $receiver = $this->createMock(Profile::class);

        $friendship->setReceiver($receiver);

        $this->assertSame($receiver, $friendship->getReceiver());
    }

    public function testSetAndGetFriendAt(): void
    {
        $friendship = new Friendship();
        $date = new \DateTime();

        $friendship->setFriendAt($date);

        $this->assertSame($date, $friendship->getFriendAt());
    }
}