<?php

namespace App\Tests\Entity;

use App\Entity\Conversation;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class ConversationTest extends TestCase
{
    public function testInitialization(): void
    {
        $conversation = new Conversation();

        $this->assertInstanceOf(\DateTimeInterface::class, $conversation->getCreatedAt());
        $this->assertFalse($conversation->getIsArchived());
        $this->assertFalse($conversation->getIsMuted());
        $this->assertNull($conversation->getMutedUntil());
        $this->assertNull($conversation->getLastMessageAt());
        $this->assertCount(0, $conversation->getParticipants());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $conversation = new Conversation();
        $date = new \DateTimeImmutable();

        $conversation->setCreatedAt($date);

        $this->assertSame($date, $conversation->getCreatedAt());
    }

    public function testSetAndGetCreatedBy(): void
    {
        $conversation = new Conversation();
        $profile = $this->createMock(Profile::class);

        $conversation->setCreatedBy($profile);

        $this->assertSame($profile, $conversation->getCreatedBy());
    }

    public function testAddParticipants(): void
    {
        $conversation = new Conversation();
        $participant1 = $this->createMock(Profile::class);
        $participant2 = $this->createMock(Profile::class);

        $conversation->addParticipant($participant1);
        $conversation->addParticipant($participant2);

        $this->assertCount(2, $conversation->getParticipants());
        $this->assertTrue($conversation->getParticipants()->contains($participant1));
        $this->assertTrue($conversation->getParticipants()->contains($participant2));
    }

    public function testSetAndGetIsArchived(): void
    {
        $conversation = new Conversation();

        $conversation->setIsArchived(true);
        $this->assertTrue($conversation->getIsArchived());

        $conversation->setIsArchived(false);
        $this->assertFalse($conversation->getIsArchived());
    }

    public function testSetAndGetIsMuted(): void
    {
        $conversation = new Conversation();

        $conversation->setIsMuted(true);
        $this->assertTrue($conversation->getIsMuted());

        $conversation->setIsMuted(false);
        $this->assertFalse($conversation->getIsMuted());
    }

    public function testSetAndGetMutedUntil(): void
    {
        $conversation = new Conversation();
        $date = new \DateTime();

        $conversation->setMutedUntil($date);

        $this->assertSame($date, $conversation->getMutedUntil());
    }

    public function testSetAndGetLastMessageAt(): void
    {
        $conversation = new Conversation();
        $date = new \DateTime();

        $conversation->setLastMessageAt($date);

        $this->assertSame($date, $conversation->getLastMessageAt());
    }
}