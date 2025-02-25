<?php

namespace App\Tests\Entity;

use App\Entity\Profile;
use App\Entity\React;
use App\Enum\ReactTypeEnum;
use App\Enum\ResourceTypeEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ReactTest extends TestCase
{
    private Profile $actor;
    private Profile $receiver;
    private string $resourceId;
    private ResourceTypeEnum $resourceType;
    private ReactTypeEnum $reactType;
    private React $react;

    protected function setUp(): void
    {
        $this->actor = $this->createMock(Profile::class);
        $this->receiver = $this->createMock(Profile::class);
        $this->resourceId = Uuid::uuid4()->toString();
        $this->resourceType = ResourceTypeEnum::POST;
        $this->reactType = ReactTypeEnum::LIKE;

        $this->react = new React($this->actor, $this->receiver, $this->resourceId, $this->resourceType, $this->reactType);
    }

    public function testReactInitialization(): void
    {
        $this->assertInstanceOf(React::class, $this->react);
        $this->assertSame($this->actor, $this->react->getActor());
        $this->assertSame($this->receiver, $this->react->getReceiver());
        $this->assertSame($this->resourceId, $this->react->getResourceId());
        $this->assertSame($this->resourceType, $this->react->getResourceType());
        $this->assertSame($this->reactType, $this->react->getType());
        $this->assertInstanceOf(DateTimeImmutable::class, $this->react->getCreatedAt());
        $this->assertNull($this->react->getUpdatedAt());
    }

    public function testSetAndGetId(): void
    {
        $id = Uuid::uuid4()->toString();
        $this->react->setId($id);
        $this->assertSame($id, $this->react->getId());
    }

    public function testSetAndGetActor(): void
    {
        $newActor = $this->createMock(Profile::class);
        $this->react->setActor($newActor);
        $this->assertSame($newActor, $this->react->getActor());
    }

    public function testSetAndGetReceiver(): void
    {
        $newReceiver = $this->createMock(Profile::class);
        $this->react->setReceiver($newReceiver);
        $this->assertSame($newReceiver, $this->react->getReceiver());
    }

    public function testSetAndGetType(): void
    {
        $newType = ReactTypeEnum::SAD; // Utilisation d'une valeur existante
        $this->react->setType($newType);
        $this->assertSame($newType, $this->react->getType());
    }

    public function testSetAndGetResourceType(): void
    {
        $newResourceType = ResourceTypeEnum::COMMENT;
        $this->react->setResourceType($newResourceType);
        $this->assertSame($newResourceType, $this->react->getResourceType());
    }

    public function testSetAndGetResourceId(): void
    {
        $newResourceId = Uuid::uuid4()->toString();
        $this->react->setResourceId($newResourceId);
        $this->assertSame($newResourceId, $this->react->getResourceId());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $newDate = new DateTimeImmutable();
        $this->react->setCreatedAt($newDate);
        $this->assertSame($newDate, $this->react->getCreatedAt());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $newUpdatedAt = new DateTimeImmutable();
        $this->react->setUpdatedAt($newUpdatedAt);
        $this->assertSame($newUpdatedAt, $this->react->getUpdatedAt());
    }
}