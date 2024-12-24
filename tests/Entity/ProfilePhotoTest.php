<?php

namespace App\Tests\Entity;

use App\Entity\ProfilePhoto;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class ProfilePhotoTest extends TestCase
{
    public function testInitialization(): void
    {
        $profilePhoto = new ProfilePhoto();

        $this->assertInstanceOf(\DateTimeImmutable::class, $profilePhoto->getCreatedAt());
        $this->assertFalse($profilePhoto->isActive());
    }

    public function testSetAndGetUrl(): void
    {
        $profilePhoto = new ProfilePhoto();
        $url = 'https://example.com/photo.jpg';

        $profilePhoto->setUrl($url);

        $this->assertEquals($url, $profilePhoto->getUrl());
    }

    public function testSetAndGetType(): void
    {
        $profilePhoto = new ProfilePhoto();

        $profilePhoto->setType(ProfilePhoto::TYPE_PROFILE);
        $this->assertEquals(ProfilePhoto::TYPE_PROFILE, $profilePhoto->getType());

        $profilePhoto->setType(ProfilePhoto::TYPE_COVER);
        $this->assertEquals(ProfilePhoto::TYPE_COVER, $profilePhoto->getType());
    }

    public function testSetTypeThrowsExceptionOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type invalide');

        $profilePhoto = new ProfilePhoto();
        $profilePhoto->setType('invalid_type');
    }

    public function testActivateAndDeactivate(): void
    {
        $profilePhoto = new ProfilePhoto();

        $profilePhoto->activate();
        $this->assertTrue($profilePhoto->isActive());

        $profilePhoto->deactivate();
        $this->assertFalse($profilePhoto->isActive());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $profilePhoto = new ProfilePhoto();
        $date = new \DateTimeImmutable('2024-12-24 10:00:00');

        $profilePhoto->setCreatedAt($date);

        $this->assertSame($date, $profilePhoto->getCreatedAt());
    }

    public function testSetAndGetProfile(): void
    {
        $profilePhoto = new ProfilePhoto();
        $profile = $this->createMock(Profile::class);

        $profilePhoto->setProfile($profile);

        $this->assertSame($profile, $profilePhoto->getProfile());
    }

    public function testToString(): void
    {
        $profilePhoto = new ProfilePhoto();
        $url = 'https://example.com/photo.jpg';

        $profilePhoto->setUrl($url);

        $this->assertEquals($url, (string) $profilePhoto);
    }
}