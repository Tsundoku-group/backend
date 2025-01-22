<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProfilePhotoRepository;
use App\Repository\ProfileRepository;
use App\Service\ProfilePhotoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/profile-photo')]
class ProfilePhotoController extends AbstractController
{
    private const PROFILE_NOT_FOUND = 'Profile not found';
    private ProfileRepository $profileRepository;
    private ProfilePhotoService $profilePhotoService;
    private ProfilePhotoRepository $profilePhotoRepository;

    public function __construct(ProfileRepository $profileRepository, ProfilePhotoService $profilePhotoService, ProfilePhotoRepository $profilePhotoRepository)
    {
        $this->profileRepository = $profileRepository;
        $this->profilePhotoService = $profilePhotoService;
        $this->profilePhotoRepository = $profilePhotoRepository;
    }

    #[Route('/add-photo', name: 'add_profile_photo', methods: 'POST')]
    public function uploadProfilePhoto(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $testingPhotoData = $this->validateProfilePhotoData($data);
            if ($testingPhotoData) {
                return $testingPhotoData;
            }

            $profile = $this->profileRepository->findProfileWithPhotos((int) $data['profileId'], (int) $data['id']);

            if (!$profile) {
                return new JsonResponse(['error' => 'Profile not found'], Response::HTTP_BAD_REQUEST);
            }

            if (null === $profile->getUser()) {
                return new JsonResponse(['error' => 'Profile has no associated user'], Response::HTTP_BAD_REQUEST);
            }

            if ($profile->getUser()->getId() !== (int) $data['id']) {
                return new JsonResponse(['error' => 'Profile does not belong to this user'], Response::HTTP_BAD_REQUEST);
            }

            $existingPhoto = $this->profilePhotoRepository->findPhotoByUrlAndType($data['url'], $data['type']);
            if ($existingPhoto) {
                return new JsonResponse(['error' => 'This photo already exists with the specified type'], Response::HTTP_CONFLICT);
            }

            $addPhoto = $this->profilePhotoService->addPhotoToProfile($profile, $data['url'], $data['type']);

            if (!$addPhoto) {
                return new JsonResponse(['error' => 'Impossible to upload'], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => 'Profile photo uploaded'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/remove-photo', name: 'delete_profile_photo', methods: 'DELETE')]
    public function removeProfilePhoto(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $testingPhotoData = $this->validateProfilePhotoData($data);
            if ($testingPhotoData) {
                return $testingPhotoData;
            }

            $profile = $this->profileRepository->findProfileByIdAndUserId((int) $data['profileId'], (int) $data['id']);

            if (!$profile) {
                return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
            }

            $removePhoto = $this->profilePhotoService->deletePhotoFromProfile($profile, $data['url'], $data['type']);

            if (!$removePhoto) {
                return new JsonResponse(['error' => 'Impossible to delete'], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => 'Profile photo removed'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/get-active-photo/{profileId}', name: 'get_active_photo', methods: ['GET'])]
    public function getProfileWithPhoto(int $profileId): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return new JsonResponse(['error' => 'Unauthorized access'], Response::HTTP_UNAUTHORIZED);
            }

            $userId = $user->getId();

            if (!$profileId) {
                return new JsonResponse(['error' => 'Invalid profile ID'], Response::HTTP_BAD_REQUEST);
            }

            $profile = $this->profileRepository->findProfileWithPhotos($profileId, $userId);

            if (!$profile) {
                return new JsonResponse(['error' => 'Profile not found'], Response::HTTP_NOT_FOUND);
            }

            if ($profile->getUser()->getId() !== $userId) {
                return new JsonResponse(['error' => 'Access denied to this profile'], Response::HTTP_FORBIDDEN);
            }

            $photos = $profile->getProfilePhotos();

            if ($photos->isEmpty()) {
                return new JsonResponse(['error' => 'No photos found for this profile'], Response::HTTP_NOT_FOUND);
            }

            $activePhotos = $profile->getProfilePhotos()->filter(fn ($photo) => $photo->isActive());

            $result = [];
            foreach ($activePhotos as $photo) {
                $result[$photo->getType()] = [
                    'id' => $photo->getId(),
                    'url' => $photo->getUrl(),
                    'type' => $photo->getType(),
                ];
            }

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Unexpected error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/set-active-photo', name: 'set_active_photo', methods: 'PUT')]
    public function setActivePhotoProfile(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $testingPhotoData = $this->validateProfilePhotoData($data);
            if ($testingPhotoData) {
                return $testingPhotoData;
            }

            $profile = $this->profileRepository->findProfileByIdAndUserId((int) $data['profileId'], (int) $data['id']);
            if (!$profile) {
                return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
            }

            $activePhoto = $this->profilePhotoService->setActivateProfilePhoto($profile, $data['url'], $data['type']);

            if ('error' === $activePhoto['status']) {
                return new JsonResponse(['error' => $activePhoto['message']], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => $activePhoto['message']], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validateProfilePhotoData(array $data): ?JsonResponse
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
