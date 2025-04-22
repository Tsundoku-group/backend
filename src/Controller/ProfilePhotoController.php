<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
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

#[Route('/api/v1/profile/photo')]
class ProfilePhotoController extends AbstractController
{
    public function __construct(
        private readonly ProfileRepository         $profileRepository,
        private readonly ProfilePhotoService       $profilePhotoService,
        private readonly ProfilePhotoRepository    $profilePhotoRepository,
        private readonly ProfilePhotoDataValidator $profilePhotoDataValidator,
    )
    {
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
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
        }

        if (null === $profile->getUser()) {
            return new JsonResponse(['error' => "Le profil n'a pas d'utilisateur associé"], Response::HTTP_BAD_REQUEST);
        }

        if ($profile->getUser()->getId() !== (int)$data['id']) {
            return new JsonResponse(['error' => "Le profil n'appartient pas à cet utilisateur"], Response::HTTP_BAD_REQUEST);
        }

        $existingPhoto = $this->profilePhotoRepository->findPhotoByUrlAndType($dto->url, $dto->type);
        if ($existingPhoto) {
            return new JsonResponse(['error' => 'Cette photo existe déjà avec le type spécifié'], Response::HTTP_CONFLICT);
        }

        try {
            $addPhoto = $this->profilePhotoService->addPhotoToProfile($profile, $dto->url, $dto->type);

            if (!$addPhoto) {
                return new JsonResponse(['error' => 'Impossible à télécharger'], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => 'Photo de profil téléchargée'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
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

        $profile = $this->profileRepository->findProfileByIdAndUserId((int)$data['profileId'], (int)$data['id']);

        if (!$profile) {
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
        }

        try {
            $removePhoto = $this->profilePhotoService->deletePhotoFromProfile($profile, $data['url'], $data['type']);

            if (!$removePhoto) {
                return new JsonResponse(['error' => 'Impossible à supprimer'], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => 'Suppression de la photo de profil'], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{profileId}/active', name: 'get_active_photo', methods: ['GET'])]
    public function getProfileWithPhoto(int $profileId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => UserErrorMessagesConstant::USER_NOT_FOUND], Response::HTTP_UNAUTHORIZED);
        }

        $userId = $user->getId();

        if (!$profileId) {
            return new JsonResponse(['error' => 'ID de profil non valide'], Response::HTTP_BAD_REQUEST);
        }

        $profile = $this->profileRepository->findProfileWithPhotos($profileId, $userId);

        if (!$profile) {
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        if ($profile->getUser()->getId() !== $userId) {
            return new JsonResponse(['error' => 'Accès refusé à ce profil'], Response::HTTP_FORBIDDEN);
        }

        $photos = $profile->getProfilePhotos();

        if ($photos->isEmpty()) {
            return new JsonResponse(['error' => 'Aucune photo trouvée pour ce profil'], Response::HTTP_NOT_FOUND);
        }

        try {
            $activePhotos = $profile->getProfilePhotos()->filter(fn($photo) => $photo->isActive());

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
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
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

        $profile = $this->profileRepository->findProfileByIdAndUserId((int)$data['profileId'], (int)$data['id']);
        if (!$profile) {
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
        }

        try {
            $activePhoto = $this->profilePhotoService->setActivateProfilePhoto($profile, $data['url'], $data['type']);

            if ('error' === $activePhoto['status']) {
                return new JsonResponse(['error' => $activePhoto['message']], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => $activePhoto['message']], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
