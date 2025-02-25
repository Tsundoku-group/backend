<?php

namespace App\Tests\Entity;

use App\Entity\Group;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testInitialization(): void
    {
        $profileMock = $this->createMock(Profile::class);
        $group = new Group($profileMock);

        $group->setVisibility('public');

        $this->assertInstanceOf(\DateTimeInterface::class, $group->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $group->getUpdatedAt());

        $this->assertEquals('public', $group->getVisibility());

        $this->assertCount(0, $group->getGroupProfiles());
        $this->assertNull($group->getName());
        $this->assertNull($group->getDescription());
        $this->assertNull($group->getSlug());
    }

    public function testSetAndGetName(): void
    {
        $profileMock = $this->createMock(Profile::class);
        $group = new Group($profileMock);
        $name = 'Test Group';

        $group->setName($name);

        $this->assertEquals($name, $group->getName());
    }

    public function testSetAndGetDescription(): void
    {
        $profileMock = $this->createMock(Profile::class);
        $group = new Group($profileMock);
        $description = 'This is a test group.';

        $group->setDescription($description);

        $this->assertEquals($description, $group->getDescription());
    }

    public function testSetAndGetVisibility(): void
    {
        $profileMock = $this->createMock(Profile::class);
        $group = new Group($profileMock);

        $group->setVisibility('private');
        $this->assertEquals('private', $group->getVisibility());

        $group->setVisibility('public');
        $this->assertEquals('public', $group->getVisibility());
    }

    public function testSetAndGetSlug(): void
    {
        $profileMock = $this->createMock(Profile::class);
        $group = new Group($profileMock);
        $slug = 'test-group';

        $group->setSlug($slug);

        $this->assertEquals($slug, $group->getSlug());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $profileMock = $this->createMock(Profile::class);
        $group = new Group($profileMock);
        $date = new \DateTimeImmutable('2024-12-24 10:00:00');

        $group->setCreatedAt($date);

        $this->assertSame($date, $group->getCreatedAt());
    }
}