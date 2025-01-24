<?php

namespace App\Validator\Constraints;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProfilePhotoDataValidator
{
    public function validate(array $data): ?JsonResponse
    {
        if (!isset($data['id'], $data['profileId'], $data['type'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('url', $data)) {
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                return new JsonResponse(['error' => 'L\'URL is not valid.'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (!in_array($data['type'], ['profile', 'cover'])) {
            return new JsonResponse(['error' => 'Type is not valid.'], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}
