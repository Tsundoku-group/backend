<?php

namespace App\Tests\Entity;

use App\Entity\Notification;
use App\Entity\Profile;
use App\Enum\NotificationTypeEnum;
use App\Enum\ResourceTypeEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class NotificationTest extends TestCase
{
    private Profile $recipient;
    private Profile $actor;
    private NotificationTypeEnum $type;
    private string $resourceId;
    private ResourceTypeEnum $resourceType;
    private Notification $notification;

    protected function setUp(): void
    {
        $this->recipient = $this->createMock(Profile::class);
        $this->actor = $this->createMock(Profile::class);
        $this->type = NotificationTypeEnum::LIKE;
        $this->resourceId = Uuid::uuid4()->toString();
        $this->resourceType = ResourceTypeEnum::POST;

        $this->notification = new Notification(
            $this->recipient,
            $this->actor,
            $this->type,
            $this->resourceId,
            $this->resourceType
        );
    }

    public function testNotificationInitialization(): void
    {
        $this->assertInstanceOf(Notification::class, $this->notification);
        $this->assertSame($this->recipient, $this->notification->getReceiver());
        $this->assertSame($this->actor, $this->notification->getActor());
        $this->assertSame($this->type, $this->notification->getNotificationType());
        $this->assertSame($this->resourceId, $this->notification->getResourceId());
        $this->assertSame($this->resourceType, $this->notification->getResourceType());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->notification->getCreatedAt());
        $this->assertFalse($this->notification->isRead());
        $this->assertNull($this->notification->getIsReadAt());
        $this->assertEquals(1, $this->notification->getActorCount());
    }

    public function testSetAndGetId(): void
    {
        $newId = Uuid::uuid4();
        $this->notification->setId($newId);
        $this->assertSame($newId, $this->notification->getId());
    }

    public function testSetAndGetReceiver(): void
    {
        $newReceiver = $this->createMock(Profile::class);
        $this->notification->setReceiver($newReceiver);
        $this->assertSame($newReceiver, $this->notification->getReceiver());
    }

    public function testSetAndGetActor(): void
    {
        $newActor = $this->createMock(Profile::class);
        $this->notification->setActor($newActor);
        $this->assertSame($newActor, $this->notification->getActor());
    }

    public function testSetAndGetNotificationType(): void
    {
        $newType = NotificationTypeEnum::COMMENT;
        $this->notification->setNotificationType($newType);
        $this->assertSame($newType, $this->notification->getNotificationType());
    }

    public function testSetAndGetResourceId(): void
    {
        $newResourceId = Uuid::uuid4()->toString();
        $this->notification->setResourceId($newResourceId);
        $this->assertSame($newResourceId, $this->notification->getResourceId());
    }

    public function testSetAndGetResourceType(): void
    {
        $newResourceType = ResourceTypeEnum::COMMENT;
        $this->notification->setResourceType($newResourceType);
        $this->assertSame($newResourceType, $this->notification->getResourceType());
    }

    public function testSetAndGetIsRead(): void
    {
        $this->assertFalse($this->notification->isRead());
        $this->notification->setIsRead(true);
        $this->assertTrue($this->notification->isRead());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $createdAt = new DateTimeImmutable();
        $this->notification->setCreatedAt($createdAt);
        $this->assertSame($createdAt, $this->notification->getCreatedAt());
    }

    public function testSetAndGetIsReadAt(): void
    {
        $this->assertNull($this->notification->getIsReadAt());
        $isReadAt = new DateTimeImmutable();
        $this->notification->setIsReadAt($isReadAt);
        $this->assertSame($isReadAt, $this->notification->getIsReadAt());
    }

    public function testMarkAsRead(): void
    {
        $this->assertFalse($this->notification->isRead());
        $this->assertNull($this->notification->getIsReadAt());

        $this->notification->markAsRead();

        $this->assertTrue($this->notification->isRead());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->notification->getIsReadAt());
    }

    public function testIncrementActorCount(): void
    {
        $this->assertEquals(1, $this->notification->getActorCount());
        $this->notification->incrementActorCount();
        $this->assertEquals(2, $this->notification->getActorCount());
    }
}