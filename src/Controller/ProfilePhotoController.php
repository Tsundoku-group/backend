<?php

namespace App\Controller;

use App\Entity\ProfilePhoto;
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
    private EntityManagerInterface $entityManager;

    public function __construct(ProfileRepository $profileRepository, ProfilePhotoService $profilePhotoService, EntityManagerInterface $entityManager)
    {
        $this->profileRepository = $profileRepository;
        $this->profilePhotoService = $profilePhotoService;
        $this->entityManager = $entityManager;
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

            $profile = $this->profileRepository->findProfileById($data['profileId']);

            if (!$profile) {
                return new JsonResponse(['error' => self::PROFILE_NOT_FOUND], Response::HTTP_BAD_REQUEST);
            }

            $existingPhoto = $this->entityManager->getRepository(ProfilePhoto::class)->findOneBy(['url' => $data['url']]);

            if ($existingPhoto) {
                throw new Exception('URL already exists');
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

            $profile = $this->profileRepository->findProfileById($data['profileId']);

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

    #[Route('/set-active-photo', name: 'set_active_photo', methods: 'PUT')]
    public function setActivePhotoProfile(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $testingPhotoData = $this->validateProfilePhotoData($data);
            if ($testingPhotoData) {
                return $testingPhotoData;
            }

            $profile = $this->profileRepository->findProfileById($data['profileId']);
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
        if (!isset($data['id'], $data['profileId'], $data['url'], $data['type'])) {
            return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            return new JsonResponse(['error' => 'L\'URL is not valid.'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($data['type'], ['profile', 'cover'])) {
            return new JsonResponse(['error' => 'Type is not valid.'], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}
