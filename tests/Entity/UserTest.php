<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserInitialization(): void
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function testPassword(): void
    {
        $user = new User();
        $user->setPassword('password');
        $this->assertEquals('password', $user->getPassword());
    }

    public function testTokenRegistration(): void
    {
        $user = new User();
        $user->setTokenRegistration('123456');
        $this->assertEquals('123456', $user->getTokenRegistration());
    }

    public function testTokenValidation(): void
    {
        $user = new User();
        $this->assertTrue($user->isTokenValid());
    }

    public function testAddProfile(): void
    {
        $user = new User();
        $profile = new Profile();
        $user->addProfile($profile);

        $this->assertCount(1, $user->getProfiles());
        $this->assertSame($user, $profile->getUser());
    }

    public function testRemoveProfile(): void
    {
        $user = new User();
        $profile = new Profile();
        $user->addProfile($profile);

        $user->removeProfile($profile);
        $this->assertCount(0, $user->getProfiles());
    }

    public function testRolesWithProfiles(): void
    {
        $user = new User();
        $profile = new Profile();
        $profile->setRole('ROLE_ADMIN');

        $user->addProfile($profile);

        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testDefaultRoleWhenNoProfile(): void
    {
        $user = new User();
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }
}