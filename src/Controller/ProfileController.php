<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Profile\ProfileDTO;
use App\DTO\Profile\SetActiveProfileDTO;
use App\DTO\Profile\UpdateProfileStatusDTO;
use App\Entity\User;
use App\Service\ProfileService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {
    }

    #[Route('/{profileId}', name: 'profile_show', methods: ['GET'])]
    public function show(int $profileId): JsonResponse
    {
        try {
            $profileData = $this->profileService->getProfileWithStats($profileId);

            if (!$profileData) {
                return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
            }

            return new JsonResponse($profileData);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/all/{id}', name: 'get_profiles', methods: ['GET'])]
    public function getAllUserProfiles(int $id): JsonResponse
    {
        try {
            $getUserProfiles = $this->profileService->getUserProfiles($id);

            if (isset($getUserProfiles['error'])) {
                return new JsonResponse(['error' => $getUserProfiles['error']], $getUserProfiles['status']);
            }

            return new JsonResponse($getUserProfiles);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/new', name: 'create_profile', methods: ['POST'])]
    public function createNewProfile(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => ErrorMessagesConstant::USER_NOT_FOUND], 401);
        }

        $dto = new ProfileDTO($data);

        try {
            $createNewUserProfile = $this->profileService->createProfile((array) $dto, $user);

            if (isset($createNewUserProfile['error'])) {
                return new JsonResponse(['error' => $createNewUserProfile['error']], $createNewUserProfile['status']);
            }

            return new JsonResponse($createNewUserProfile, 201);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/edit', name: 'profile_edit', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        $dto = new ProfileDTO($data);

        try {
            $updatedProfile = $this->profileService->updateProfile($id, (array) $dto);

            if (isset($updatedProfile['error'])) {
                return new JsonResponse(['error' => $updatedProfile['error']], $updatedProfile['status']);
            }

            return new JsonResponse($updatedProfile, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}', name: 'delete_profile', methods: ['DELETE'])]
    public function delete(int $profileId): JsonResponse
    {
        try {
            $deleteUserProfile = $this->profileService->deleteProfile($profileId);

            if (!$deleteUserProfile) {
                return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
            }

            return new JsonResponse(null, 204);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/get-active/{id}', name: 'get_active_profile', methods: ['GET'])]
    public function getActiveUserProfile(int $id): JsonResponse
    {
        try {
            $fetchActiveProfile = $this->profileService->getActiveProfile($id);
            if (!$fetchActiveProfile) {
                return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
            }

            return new JsonResponse($fetchActiveProfile);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/set-active', name: 'set_active_profile', methods: ['POST'])]
    public function setActiveUserProfile(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['id']) || !isset($data['profileId'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $dto = new SetActiveProfileDTO($data);
            $setActiveProfile = $this->profileService->setActiveProfile($dto);

            if (isset($setActiveProfile['error'])) {
                return new JsonResponse(['error' => $setActiveProfile['error']], $setActiveProfile['status']);
            }

            return new JsonResponse($setActiveProfile);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/update-status/{id}', name: 'update_status', methods: ['PUT'])]
    public function updateUserProfileStatus(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new UpdateProfileStatusDTO($data);

        try {
            $updateProfileStatus = $this->profileService->updateProfileStatus($dto, $id);
            if (!$updateProfileStatus) {
                return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
            }

            return new JsonResponse($updateProfileStatus);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
