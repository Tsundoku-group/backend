<?php

namespace App\Tests\Controller;

use App\Controller\ProfileController;
use App\DTO\Profile\ProfileDTO;
use App\Service\ProfileService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProfileControllerTest extends TestCase
{
    private $profileService;

    protected function setUp(): void
    {
        $this->profileService = $this->createMock(ProfileService::class);
    }

    public function testShowProfileNotFound(): void
    {
        $this->profileService->method('getProfileWithStats')->willReturn(null);

        $controller = new ProfileController($this->profileService);

        $response = $controller->show(999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Profil non trouvé']),
            $response->getContent()
        );
    }

    public function testCreateProfileSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'newuser',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'type' => 'lecteur',  // Assure-toi que ce champ est bien là
            'phoneNumber' => '0612233435',
            'birthday' => '2000-01-01',
            'bio' => 'New user bio',
        ]));

        $this->profileService->method('createProfile')->willReturn([
            'message' => 'Profile created successfully'
        ]);

        $controller = $this->getMockBuilder(ProfileController::class)
            ->setConstructorArgs([$this->profileService])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn(new \App\Entity\User());

        $response = $controller->createNewProfile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Profile created successfully', $responseData['message']);
    }

    public function testDeleteProfileSuccess(): void
    {
        $this->profileService->method('deleteProfile')->willReturn(true);

        $controller = new ProfileController($this->profileService);

        $response = $controller->delete(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testDeleteProfileNotFound(): void
    {
        $this->profileService->method('deleteProfile')->willReturn(false);

        $controller = new ProfileController($this->profileService);

        $response = $controller->delete(999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Profil non trouvé']),
            $response->getContent()
        );
    }

    public function testGetActiveProfileSuccess(): void
    {
        $this->profileService->method('getActiveProfile')->willReturn([
            'id' => 1,
            'username' => 'johndoe',
        ]);

        $controller = new ProfileController($this->profileService);

        $response = $controller->getActiveUserProfile(1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'id' => 1,
                'username' => 'johndoe',
            ]),
            $response->getContent()
        );
    }

    public function testGetActiveProfileNotFound(): void
    {
        $this->profileService->method('getActiveProfile')->willReturn(null);

        $controller = new ProfileController($this->profileService);

        $response = $controller->getActiveUserProfile(999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Profil non trouvé']),
            $response->getContent()
        );
    }

    public function testSetActiveProfileSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'id' => 1,
            'profileId' => 1,
        ]));

        $this->profileService->method('setActiveProfile')->willReturn([
            'message' => 'Profile set as active'
        ]);

        $controller = new ProfileController($this->profileService);

        $response = $controller->setActiveUserProfile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Profile set as active']),
            $response->getContent()
        );
    }

    public function testSetActiveProfileNotFound(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'id' => 999,
            'profileId' => 999,
        ]));

        $this->profileService->method('setActiveProfile')->willReturn([
            'error' => 'Profile not found',
            'status' => 404
        ]);

        $controller = new ProfileController($this->profileService);

        $response = $controller->setActiveUserProfile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Profile not found']),
            $response->getContent()
        );
    }

    public function testUpdateProfileSuccess(): void
    {
        $dto = new ProfileDTO([
            'firstName' => 'Updated John',
            'lastName' => 'Updated Doe',
            'username' => 'updateduser',
            'phoneNumber' => '0612233435',
            'birthday' => '1990-01-01',
            'bio' => 'Updated bio',
            'type' => 'auteur',
        ]);

        $this->profileService->method('updateProfile')->willReturn([
            'message' => 'Profile updated successfully'
        ]);

        $controller = new ProfileController($this->profileService);

        // 👉 Corrige l'accès au container pour AbstractController
        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $controller->setContainer($container);

        $response = $controller->update($dto, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Profile updated successfully']),
            $response->getContent()
        );
    }
}