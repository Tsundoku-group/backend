<?php

namespace App\Tests\Service;

use App\DTO\Profile\SetActiveProfileDTO;
use App\DTO\Profile\UpdateProfileStatusDTO;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\FollowerRepository;
use App\Repository\FriendshipRepository;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use App\Service\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProfileServiceTest extends TestCase
{
    private $entityManager;
    private $profileRepository;
    private $userRepository;
    private $friendshipRepository;
    private $followerRepository;
    private $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->profileRepository = $this->createMock(ProfileRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->friendshipRepository = $this->createMock(FriendshipRepository::class);
        $this->followerRepository = $this->createMock(FollowerRepository::class);

        $this->service = new ProfileService(
            $this->entityManager,
            $this->profileRepository,
            $this->userRepository,
            $this->friendshipRepository,
            $this->followerRepository
        );
    }

    public function testCreateProfileSuccess(): void
    {
        $user = $this->createMock(User::class);
        $data = [
            'username' => 'newUser',
            'firstName' => 'John',
            'lastName' => '',
            'type' => 'lecteur',
            'phoneNumber' => '',
        ];

        $this->profileRepository->method('findProfileByUsername')->willReturn(null);
        $this->profileRepository->method('count')->willReturn(0);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Profile::class));
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createProfile($data, $user);

        $this->assertArrayHasKey('username', $result);
        $this->assertEquals('newUser', $result['username']);
    }

    public function testCreateProfileDuplicateUsername(): void
    {
        $user = $this->createMock(User::class);
        $data = ['username' => 'existingUser'];

        $this->profileRepository->method('findProfileByUsername')->willReturn(['id' => 1]);

        $result = $this->service->createProfile($data, $user);

        $this->assertEquals("Le nom d'utilisateur est déjà pris", $result['error']);
        $this->assertEquals(400, $result['status']);
    }

    public function testSetActiveProfileSuccess(): void
    {
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);
        $dto = new SetActiveProfileDTO(['userId' => 1, 'profileId' => 1]);

        $user->method('getId')->willReturn(1);
        $profile->method('getUser')->willReturn($user);
        $profile->method('getActiveProfile')->willReturn(null);
        $profile->method('getId')->willReturn(1);
        $profile->method('getUsername')->willReturn('newActiveUser');

        $this->userRepository->method('find')->willReturn($user);
        $this->profileRepository->method('find')->willReturn($profile);
        $this->profileRepository->method('findBy')->willReturn([$profile]);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->setActiveProfile($dto);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('newActiveUser', $result['username']);
    }

    public function testUpdateProfileStatusSuccess(): void
    {
        $profile = $this->createMock(Profile::class);
        $dto = new UpdateProfileStatusDTO(['status' => 'online']);

        $profile->expects($this->once())->method('setStatus')->with('online');
        $profile->method('getStatus')->willReturn('online');
        $this->profileRepository->method('find')->willReturn($profile);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->updateProfileStatus($dto, 1);

        $this->assertIsArray($result);
        $this->assertEquals('online', $result['newStatus']);
    }
}