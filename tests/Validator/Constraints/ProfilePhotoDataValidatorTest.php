<?php

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\ProfilePhotoDataValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfilePhotoDataValidatorTest extends TestCase
{
    public function testValidateValidData(): void
    {
        $validator = new ProfilePhotoDataValidator();

        $data = [
            'id' => 1,
            'profileId' => 2,
            'type' => 'profile',
            'url' => 'https://example.com/photo.jpg',
        ];

        $result = $validator->validate($data);

        $this->assertNull($result, 'Validation should return null for valid data.');
    }

    public function testValidateMissingRequiredFields(): void
    {
        $validator = new ProfilePhotoDataValidator();

        $data = [
            'profileId' => 2,
            'type' => 'profile',
        ];

        $result = $validator->validate($data);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals(['error' => 'Données invalides'], json_decode($result->getContent(), true));
    }

    public function testValidateInvalidUrl(): void
    {
        $validator = new ProfilePhotoDataValidator();

        $data = [
            'id' => 1,
            'profileId' => 2,
            'type' => 'profile',
            'url' => 'invalid-url',
        ];

        $result = $validator->validate($data);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals(['error' => 'L\'URL n\'est pas valide'], json_decode($result->getContent(), true));
    }

    public function testValidateInvalidType(): void
    {
        $validator = new ProfilePhotoDataValidator();

        $data = [
            'id' => 1,
            'profileId' => 2,
            'type' => 'invalid-type',
            'url' => 'https://example.com/photo.jpg',
        ];

        $result = $validator->validate($data);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals(['error' => "Le type n'est pas valide"], json_decode($result->getContent(), true));
    }
}