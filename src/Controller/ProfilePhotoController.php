<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\ProfilePhoto\ProfilePhotoDTO;
use App\Entity\User;
use App\Repository\ProfilePhotoRepository;
use App\Repository\ProfileRepository;
use App\Service\ProfilePhotoService;
use App\Validator\Constraints\ProfilePhotoDataValidator;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/profile/photo')]
class ProfilePhotoController extends AbstractController
{
    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly ProfilePhotoService $profilePhotoService,
        private readonly ProfilePhotoRepository $profilePhotoRepository,
        private readonly ProfilePhotoDataValidator $profilePhotoDataValidator,
    ) {
    }

    #[Route('/upload', name: 'add_profile_photo', methods: 'POST')]
    public function uploadProfilePhoto(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $testingPhotoData = $this->profilePhotoDataValidator->validate($data);
        if ($testingPhotoData) {
            return $testingPhotoData;
        }

        $dto = new ProfilePhotoDTO($data);

        $profile = $this->profileRepository->findProfileWithPhotos($dto->profileId, $dto->userId);

        if (!$profile) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
        }

        if (null === $profile->getUser()) {
            return new JsonResponse(['error' => 'Profile has no associated user'], Response::HTTP_BAD_REQUEST);
        }

        if ($profile->getUser()->getId() !== (int) $data['id']) {
            return new JsonResponse(['error' => 'Profile does not belong to this user'], Response::HTTP_BAD_REQUEST);
        }

        $existingPhoto = $this->profilePhotoRepository->findPhotoByUrlAndType($dto->url, $dto->type);
        if ($existingPhoto) {
            return new JsonResponse(['error' => 'This photo already exists with the specified type'], Response::HTTP_CONFLICT);
        }

        try {
            $addPhoto = $this->profilePhotoService->addPhotoToProfile($profile, $dto->url, $dto->type);

            if (!$addPhoto) {
                return new JsonResponse(['error' => 'Impossible to upload'], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => 'Profile photo uploaded'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/remove', name: 'delete_profile_photo', methods: 'DELETE')]
    public function removeProfilePhoto(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $testingPhotoData = $this->profilePhotoDataValidator->validate($data);
        if ($testingPhotoData) {
            return $testingPhotoData;
        }

        $profile = $this->profileRepository->findProfileByIdAndUserId((int) $data['profileId'], (int) $data['id']);

        if (!$profile) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
        }

        try {
            $removePhoto = $this->profilePhotoService->deletePhotoFromProfile($profile, $data['url'], $data['type']);

            if (!$removePhoto) {
                return new JsonResponse(['error' => 'Impossible to delete'], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => 'Profile photo removed'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{profileId}/active', name: 'get_active_photo', methods: ['GET'])]
    public function getProfileWithPhoto(int $profileId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ErrorMessagesConstant::USER_NOT_FOUND], Response::HTTP_UNAUTHORIZED);
        }

        $userId = $user->getId();

        if (!$profileId) {
            return new JsonResponse(['error' => 'Invalid profile ID'], Response::HTTP_BAD_REQUEST);
        }

        $profile = $this->profileRepository->findProfileWithPhotos($profileId, $userId);

        if (!$profile) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        if ($profile->getUser()->getId() !== $userId) {
            return new JsonResponse(['error' => 'Access denied to this profile'], Response::HTTP_FORBIDDEN);
        }

        $photos = $profile->getProfilePhotos();

        if ($photos->isEmpty()) {
            return new JsonResponse(['error' => 'No photos found for this profile'], Response::HTTP_NOT_FOUND);
        }

        try {
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
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/active', name: 'set_active_photo', methods: 'PUT')]
    public function setActivePhotoProfile(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $testingPhotoData = $this->profilePhotoDataValidator->validate($data);
        if ($testingPhotoData) {
            return $testingPhotoData;
        }

        $profile = $this->profileRepository->findProfileByIdAndUserId((int) $data['profileId'], (int) $data['id']);
        if (!$profile) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
        }

        try {
            $activePhoto = $this->profilePhotoService->setActivateProfilePhoto($profile, $data['url'], $data['type']);

            if ('error' === $activePhoto['status']) {
                return new JsonResponse(['error' => $activePhoto['message']], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => $activePhoto['message']], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
