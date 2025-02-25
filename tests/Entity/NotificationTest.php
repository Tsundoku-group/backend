<?php

namespace App\Tests\Entity;

use App\Entity\Notification;
use App\Entity\Profile;
use App\Enum\NotificationTypeEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class NotificationTest extends TestCase
{
    private Profile $recipient;
    private Profile $profile;
    private NotificationTypeEnum $type;
    private UuidInterface $resourceId;
    private Notification $notification;

    protected function setUp(): void
    {
        $this->recipient = $this->createMock(Profile::class);
        $this->profile = $this->createMock(Profile::class);
        $this->type = NotificationTypeEnum::LIKE;
        $this->resourceId = Uuid::uuid4();

        $this->notification = new Notification($this->recipient, $this->profile, $this->type, $this->resourceId);
    }

    public function testNotificationInitialization(): void
    {
        $this->assertInstanceOf(Notification::class, $this->notification);
        $this->assertSame($this->recipient, $this->notification->getRecipient());
        $this->assertSame($this->profile, $this->notification->getProfile());
        $this->assertSame($this->type, $this->notification->getType());
        $this->assertSame($this->resourceId, $this->notification->getResourceId());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->notification->getCreatedAt());
        $this->assertFalse($this->notification->isRead());
        $this->assertNull($this->notification->getIsReadAt());
    }

    public function testSetAndGetId(): void
    {
        $newId = Uuid::uuid4();
        $this->notification->setId($newId);
        $this->assertSame($newId, $this->notification->getId());
    }

    public function testSetAndGetRecipient(): void
    {
        $newRecipient = $this->createMock(Profile::class);
        $this->notification->setRecipient($newRecipient);
        $this->assertSame($newRecipient, $this->notification->getRecipient());
    }

    public function testSetAndGetProfile(): void
    {
        $newProfile = $this->createMock(Profile::class);
        $this->notification->setProfile($newProfile);
        $this->assertSame($newProfile, $this->notification->getProfile());
    }

    public function testSetAndGetType(): void
    {
        $newType = NotificationTypeEnum::COMMENT;
        $this->notification->setType($newType);
        $this->assertSame($newType, $this->notification->getType());
    }

    public function testSetAndGetResourceId(): void
    {
        $newResourceId = Uuid::uuid4();
        $this->notification->setResourceId($newResourceId);
        $this->assertSame($newResourceId, $this->notification->getResourceId());

        $this->notification->setResourceId(null);
        $this->assertNull($this->notification->getResourceId());
    }

    public function testSetAndGetIsRead(): void
    {
        $this->assertFalse($this->notification->isRead());

        $this->notification->setIsRead(true);
        $this->assertTrue($this->notification->isRead());

        $this->notification->setIsRead(false);
        $this->assertFalse($this->notification->isRead());
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
}