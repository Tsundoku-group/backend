<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\GroupProfile\JoinGroupDTO;
use App\DTO\GroupProfile\RemoveMemberDTO;
use App\DTO\GroupProfile\UpdateMemberRoleDTO;
use App\Service\GroupProfileService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/group/profile')]
class GroupProfileController extends AbstractController
{
    public function __construct(
        private readonly GroupProfileService $groupProfileService,
    ) {
    }

    #[Route('/join', methods: ['POST'])]
    public function joinGroup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['groupId'], $data['profileId'], $data['role'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $dto = new JoinGroupDTO($data['groupId'], $data['profileId'], $data['role']);

        try {
            $groupProfile = $this->groupProfileService->joinGroup($dto->groupId, $dto->profileId, $dto->role);

            return new JsonResponse([
                'message' => 'Membre ajouté au groupe avec succès.',
                'groupProfile' => [
                    'groupId' => $groupProfile->getGroup()->getId(),
                    'profileId' => $groupProfile->getProfile()->getId(),
                    'role' => $groupProfile->getRole()->getValue(),
                ],
            ], 201);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/update/role', methods: ['PUT'])]
    public function updateMemberRole(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['groupId'], $data['memberId'], $data['newRole'], $data['adminId'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $dto = new UpdateMemberRoleDTO($data['groupId'], $data['memberId'], $data['newRole'], $data['adminId']);

        try {
            $this->groupProfileService->updateMemberRole($dto->groupId, $dto->memberId, $dto->newRole, $dto->adminId);

            return new JsonResponse(['message' => 'Rôle mis à jour avec succès.']);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/remove', methods: ['DELETE'])]
    public function removeMember(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['groupId'], $data['profileId'], $data['adminId'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $dto = new RemoveMemberDTO($data['groupId'], $data['profileId'], $data['adminId']);

        try {
            $this->groupProfileService->removeMember($dto->groupId, $dto->profileId, $dto->adminId);

            return new JsonResponse(['message' => 'Membre supprimé du groupe avec succès.']);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
