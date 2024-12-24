<?php

namespace App\Tests\Entity;

use App\Entity\Group;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testInitialization(): void
    {
        $group = new Group();

        $this->assertInstanceOf(\DateTimeInterface::class, $group->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $group->getUpdatedAt());
        $this->assertEquals('public', $group->getVisibility());
        $this->assertCount(0, $group->getProfiles());
        $this->assertNull($group->getName());
        $this->assertNull($group->getDescription());
        $this->assertNull($group->getSlug());
    }

    public function testSetAndGetName(): void
    {
        $group = new Group();
        $name = 'Test Group';

        $group->setName($name);

        $this->assertEquals($name, $group->getName());
    }

    public function testSetAndGetDescription(): void
    {
        $group = new Group();
        $description = 'This is a test group.';

        $group->setDescription($description);

        $this->assertEquals($description, $group->getDescription());
    }

    public function testSetAndGetVisibility(): void
    {
        $group = new Group();

        $group->setVisibility('private');
        $this->assertEquals('private', $group->getVisibility());

        $group->setVisibility('public');
        $this->assertEquals('public', $group->getVisibility());
    }

    public function testSetAndGetSlug(): void
    {
        $group = new Group();
        $slug = 'test-group';

        $group->setSlug($slug);

        $this->assertEquals($slug, $group->getSlug());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $group = new Group();
        $date = new \DateTimeImmutable('2024-12-24 10:00:00');

        $group->setCreatedAt($date);

        $this->assertSame($date, $group->getCreatedAt());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $group = new Group();
        $date = new \DateTimeImmutable('2024-12-24 10:00:00');

        $group->setUpdatedAt($date);

        $this->assertSame($date, $group->getUpdatedAt());
    }

    public function testAddAndRemoveProfiles(): void
    {
        $group = new Group();
        $profile1 = $this->createMock(Profile::class);
        $profile2 = $this->createMock(Profile::class);

        $group->addProfile($profile1);
        $group->addProfile($profile2);

        $this->assertCount(2, $group->getProfiles());
        $this->assertTrue($group->getProfiles()->contains($profile1));
        $this->assertTrue($group->getProfiles()->contains($profile2));

        $group->removeProfile($profile1);

        $this->assertCount(1, $group->getProfiles());
        $this->assertFalse($group->getProfiles()->contains($profile1));
    }
}