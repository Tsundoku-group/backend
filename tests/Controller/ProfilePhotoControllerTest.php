<?php

namespace App\Tests\Controller;

use App\Controller\ProfilePhotoController;
use App\Entity\Profile;
use App\Entity\ProfilePhoto;
use App\Repository\ProfileRepository;
use App\Service\ProfilePhotoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProfilePhotoControllerTest extends TestCase
{
    private $profileRepository;
    private $profilePhotoService;
    private $entityManager;
    private $container;

    protected function setUp(): void
    {
        $this->profileRepository = $this->createMock(ProfileRepository::class);
        $this->profilePhotoService = $this->createMock(ProfilePhotoService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $profilePhotoRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $this->entityManager->method('getRepository')
            ->willReturnMap([[ProfilePhoto::class, $profilePhotoRepo]]);
    }

    public function testUploadProfilePhotoSuccess(): void
    {
        $profile = $this->createMock(Profile::class);
        $profilePhoto = $this->createMock(ProfilePhoto::class);

        $this->profileRepository->method('findProfileById')->willReturn($profile);
        $this->entityManager->getRepository(ProfilePhoto::class)
            ->method('findOneBy')->willReturn(null);
        $this->profilePhotoService->method('addPhotoToProfile')->willReturn(true);

        $controller = new ProfilePhotoController(
            $this->profileRepository,
            $this->profilePhotoService,
            $this->entityManager
        );

        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'id' => 1,
            'profileId' => 1,
            'url' => 'https://example.com/photo.jpg',
            'type' => 'profile',
        ]));

        $response = $controller->uploadProfilePhoto($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUploadProfilePhotoInvalidData(): void
    {
        $controller = new ProfilePhotoController(
            $this->profileRepository,
            $this->profilePhotoService,
            $this->entityManager
        );

        $request = new Request([], [], [], [], [], [], json_encode([
            'id' => 1,
            'profileId' => 1,
            'url' => 'invalid-url',
            'type' => 'profile',
        ]));

        $response = $controller->uploadProfilePhoto($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(['error' => "L'URL is not valid."], json_decode($response->getContent(), true));
    }
}