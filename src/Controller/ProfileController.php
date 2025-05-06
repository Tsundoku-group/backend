<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
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

#[Route('/api/v1/profiles')]
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
                return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($profileData);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/all', name: 'get_profiles', methods: ['GET'])]
    public function getAllUserProfiles(int $id): JsonResponse
    {
        try {
            $getUserProfiles = $this->profileService->getUserProfiles($id);

            if (isset($getUserProfiles['error'])) {
                return new JsonResponse(['error' => $getUserProfiles['error']], $getUserProfiles['status']);
            }

            return new JsonResponse($getUserProfiles);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create_profile', methods: ['POST'])]
    public function createNewProfile(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => UserErrorMessagesConstant::USER_NOT_FOUND], Response::HTTP_UNAUTHORIZED);
        }

        $dto = new ProfileDTO($data);

        try {
            $createNewUserProfile = $this->profileService->createProfile((array) $dto, $user);

            if (isset($createNewUserProfile['error'])) {
                return new JsonResponse(['error' => $createNewUserProfile['error']], $createNewUserProfile['status']);
            }

            return new JsonResponse($createNewUserProfile, Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{profileId}', name: 'profile_edit', methods: ['PUT'])]
    public function update(Request $request, int $profileId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        $dto = new ProfileDTO($data);

        try {
            $updatedProfile = $this->profileService->updateProfile($profileId, (array) $dto);

            if (isset($updatedProfile['error'])) {
                return new JsonResponse(['error' => $updatedProfile['error']], $updatedProfile['status']);
            }

            return new JsonResponse($updatedProfile, Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{profileId}', name: 'delete_profile', methods: ['DELETE'])]
    public function delete(int $profileId): JsonResponse
    {
        try {
            $deleteUserProfile = $this->profileService->deleteProfile($profileId);

            if (!$deleteUserProfile) {
                return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse(null, 204);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/active', name: 'get_active_profile', methods: ['GET'])]
    public function getActiveUserProfile(int $id): JsonResponse
    {
        try {
            $fetchActiveProfile = $this->profileService->getActiveProfile($id);
            if (!$fetchActiveProfile) {
                return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($fetchActiveProfile);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/active', name: 'set_active_profile', methods: ['POST'])]
    public function setActiveUserProfile(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['id']) || !isset($data['profileId'])) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], Response::HTTP_NOT_FOUND);
        }

        try {
            $dto = new SetActiveProfileDTO($data);
            $setActiveProfile = $this->profileService->setActiveProfile($dto);

            if (isset($setActiveProfile['error'])) {
                return new JsonResponse(['error' => $setActiveProfile['error']], $setActiveProfile['status']);
            }

            return new JsonResponse($setActiveProfile);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/update/status', name: 'update_status', methods: ['PUT'])]
    public function updateUserProfileStatus(Request $request, int $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = new UpdateProfileStatusDTO($data);

        try {
            $updateProfileStatus = $this->profileService->updateProfileStatus($dto, $id);
            if (!$updateProfileStatus) {
                return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($updateProfileStatus);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'profile_search', methods: ['GET'])]
    public function searchProfile(Request $request): JsonResponse
    {
        $search = $request->query->get('search', '');
        $page = max((int) $request->query->get('page', '1'), 1);
        $limit = max((int) $request->query->get('limit', '10'), 10);

        try {
            $profiles = $this->profileService->searchProfile($search, $page, $limit);

            return new JsonResponse(['profiles' => $profiles], Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
