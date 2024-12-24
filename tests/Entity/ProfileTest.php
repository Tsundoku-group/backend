<?php

namespace App\Tests\Entity;

use App\Entity\Conversation;
use App\Entity\Friendship;
use App\Entity\Group;
use App\Entity\Profile;
use App\Entity\ProfilePhoto;
use App\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class ProfileTest extends TestCase
{
    public function testProfileInitialization(): void
    {
        $profile = new Profile();

        $this->assertNull($profile->getId());
        $this->assertInstanceOf(ArrayCollection::class, $profile->getProfilePhotos());
        $this->assertInstanceOf(ArrayCollection::class, $profile->getGroups());
        $this->assertEquals('ROLE_USER', $profile->getRole());
        $this->assertFalse($profile->isActiveProfile());
        $this->assertEquals('offline', $profile->getStatus());
    }

    public function testSetAndGetUsername(): void
    {
        $profile = new Profile();
        $profile->setUsername('test_user');

        $this->assertEquals('test_user', $profile->getUsername());
    }

    public function testSetAndGetRole(): void
    {
        $profile = new Profile();
        $profile->setRole('ROLE_ADMIN');

        $this->assertEquals('ROLE_ADMIN', $profile->getRole());
    }

    public function testActivateAndDeactivate(): void
    {
        $profile = new Profile();

        $profile->activate();
        $this->assertTrue($profile->isActiveProfile());
        $this->assertEquals('online', $profile->getStatus());

        $profile->deactivate();
        $this->assertFalse($profile->isActiveProfile());
        $this->assertEquals('offline', $profile->getStatus());
    }

    public function testAddAndRemoveGroup(): void
    {
        $profile = new Profile();
        $group = $this->createMock(Group::class);

        $profile->addGroup($group);
        $this->assertCount(1, $profile->getGroups());

        $profile->removeGroup($group);
        $this->assertCount(0, $profile->getGroups());
    }

    public function testAddAndRemoveProfilePhoto(): void
    {
        $profile = new Profile();
        $photo = $this->createMock(ProfilePhoto::class);

        $profile->getProfilePhotos()->add($photo);
        $this->assertCount(1, $profile->getProfilePhotos());

        $profile->removeProfilePhoto($photo);
        $this->assertCount(0, $profile->getProfilePhotos());
    }

    public function testGetActiveProfilePhoto(): void
    {
        $profile = new Profile();

        $photo1 = $this->createMock(ProfilePhoto::class);
        $photo1->method('isActive')->willReturn(false);

        $photo2 = $this->createMock(ProfilePhoto::class);
        $photo2->method('isActive')->willReturn(true);

        $profile->getProfilePhotos()->add($photo1);
        $profile->getProfilePhotos()->add($photo2);

        $this->assertSame($photo2, $profile->getActiveProfilePhoto());
    }

    public function testSetAndGetUser(): void
    {
        $profile = new Profile();
        $user = $this->createMock(User::class);

        $profile->setUser($user);
        $this->assertSame($user, $profile->getUser());
    }

    public function testFriendshipCollections(): void
    {
        $profile = new Profile();
        $friendship1 = $this->createMock(Friendship::class);
        $friendship2 = $this->createMock(Friendship::class);

        $profile->addSentFriendship($friendship1);
        $profile->addReceivedFriendship($friendship2);

        $this->assertCount(1, $profile->getSentFriendships());
        $this->assertCount(1, $profile->getReceivedFriendships());

        $profile->removeSentFriendship($friendship1);
        $profile->removeReceivedFriendship($friendship2);

        $this->assertCount(0, $profile->getSentFriendships());
        $this->assertCount(0, $profile->getReceivedFriendships());
    }

    public function testAddAndRemoveConversations(): void
    {
        $profile = new Profile();
        $conversation = $this->createMock(Conversation::class);

        $profile->addConversation($conversation);
        $this->assertCount(1, $profile->getConversations());
        $this->assertTrue($profile->getConversations()->contains($conversation));

        $profile->removeConversation($conversation);
        $this->assertCount(0, $profile->getConversations());
    }

    public function testAddAndRemoveConversationsParticipants(): void
    {
        $profile = new Profile();
        $conversation = $this->createMock(Conversation::class);

        $profile->addConversationsParticipant($conversation);
        $this->assertCount(1, $profile->getConversationsParticipants());
        $this->assertTrue($profile->getConversationsParticipants()->contains($conversation));

        $profile->removeConversationsParticipant($conversation);
        $this->assertCount(0, $profile->getConversationsParticipants());
    }
}
