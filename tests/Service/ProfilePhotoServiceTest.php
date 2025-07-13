<?php

namespace App\Tests\Service;

use App\Entity\Profile;
use App\Entity\ProfilePhoto;
use App\Service\ProfilePhotoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class ProfilePhotoServiceTest extends TestCase
{
    private $entityManager;
    private $profilePhotoService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->profilePhotoService = new ProfilePhotoService($this->entityManager);
    }

    public function testAddPhotoToProfileSuccess(): void
    {
        $profile = $this->createMock(Profile::class);
        $profilePhotoCollection = $this->createMock(\Doctrine\Common\Collections\ArrayCollection::class);

        $profile->method('getProfilePhotos')->willReturn($profilePhotoCollection);
        $profilePhotoCollection->method('isEmpty')->willReturn(true);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->profilePhotoService->addPhotoToProfile($profile, 'http://example.com/photo.jpg', 'profile');
        $this->assertTrue($result);
    }

    public function testAddPhotoToProfileDeactivateExisting(): void
    {
        $profile = $this->createMock(Profile::class);
        $existingPhoto = $this->createMock(ProfilePhoto::class);

        $profilePhotos = new ArrayCollection([$existingPhoto]);

        $profile->method('getProfilePhotos')->willReturn($profilePhotos);

        // Simuler le type attendu pour satisfaire la condition dans le service
        $existingPhoto->method('getType')->willReturn('profile');

        // Vérifier que la photo existante est désactivée
        $existingPhoto->expects($this->once())->method('deactivate');

        // Attendre deux persist : 1 pour désactiver l'existant, 1 pour la nouvelle photo
        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->profilePhotoService->addPhotoToProfile($profile, 'http://example.com/photo.jpg', 'profile');
        $this->assertTrue($result);
    }

    public function testAddPhotoToProfileFail(): void
    {
        $profile = $this->createMock(Profile::class);
        $profilePhotoCollection = $this->createMock(\Doctrine\Common\Collections\ArrayCollection::class);

        $profile->method('getProfilePhotos')->willReturn($profilePhotoCollection);
        $profilePhotoCollection->method('isEmpty')->willReturn(true);

        $this->entityManager->method('flush')->willThrowException(new \Exception());

        $result = $this->profilePhotoService->addPhotoToProfile($profile, 'http://example.com/photo.jpg', 'profile');
        $this->assertFalse($result);
    }

    public function testDeletePhotoFromProfileSuccess(): void
    {
        $profile = $this->createMock(Profile::class);
        $photo1 = $this->createMock(ProfilePhoto::class);
        $photo2 = $this->createMock(ProfilePhoto::class);

        $photo1->method('getUrl')->willReturn('http://example.com/photo1.jpg');
        $photo1->method('getType')->willReturn('profile');
        $photo2->method('getUrl')->willReturn('http://example.com/photo2.jpg');
        $photo2->method('getType')->willReturn('cover');

        $profile->method('getProfilePhotos')->willReturn(new ArrayCollection([$photo1, $photo2]));

        $this->entityManager->expects($this->once())->method('remove')->with($photo1);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->profilePhotoService->deletePhotoFromProfile($profile, 'http://example.com/photo1.jpg', 'profile');
        $this->assertTrue($result);
    }

    public function testDeletePhotoFromProfileFail(): void
    {
        $profile = $this->createMock(Profile::class);
        $photo = $this->createMock(ProfilePhoto::class);

        $photo->method('getUrl')->willReturn('http://example.com/photo1.jpg');
        $photo->method('getType')->willReturn('profile');

        $profile->method('getProfilePhotos')->willReturn(new ArrayCollection([$photo]));

        $this->entityManager->method('flush')->willThrowException(new \Exception());

        $result = $this->profilePhotoService->deletePhotoFromProfile($profile, 'http://example.com/photo1.jpg', 'profile');
        $this->assertFalse($result);
    }

    public function testSetActivateProfilePhotoSuccess(): void
    {
        $profile = $this->createMock(Profile::class);
        $photo = $this->createMock(ProfilePhoto::class);

        $photo->method('getUrl')->willReturn('http://example.com/photo1.jpg');
        $photo->method('getType')->willReturn('profile');
        $photo->method('isActive')->willReturn(false);

        $profile->method('getProfilePhotos')->willReturn(new ArrayCollection([$photo]));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->profilePhotoService->setActivateProfilePhoto($profile, 'http://example.com/photo1.jpg', 'profile');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Photo activée avec succès', $result['message']);
    }

    public function testSetActivateProfilePhotoFail(): void
    {
        $profile = $this->createMock(Profile::class);

        $profile->method('getProfilePhotos')->willReturn(new ArrayCollection([]));

        $result = $this->profilePhotoService->setActivateProfilePhoto($profile, 'http://example.com/photo1.jpg', 'profile');

        $this->assertEquals('error', $result['status']);
        $this->assertEquals("Aucune photo de profil n'a été trouvée", $result['message']);
    }
}