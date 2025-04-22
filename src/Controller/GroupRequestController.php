<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Enum\RequestStatusEnum;
use App\Repository\GroupRepository;
use App\Repository\GroupRequestRepository;
use App\Repository\ProfileRepository;
use App\Security\Voter\Group\GroupRoleVoter;
use App\Service\GroupRequestService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/group/request')]
class GroupRequestController extends AbstractController
{
    public function __construct(
        private readonly GroupRequestService $groupRequestService,
        private readonly GroupRequestRepository $groupRequestRepository,
        private readonly GroupRepository $groupRepository,
        private readonly ProfileRepository $profileRepository,
    ) {
    }

    #[Route('/{groupId}', name: 'group_request', methods: ['GET'])]
    public function getAllRequestsForOneGroup(int $groupId): JsonResponse
    {
        try {
            $group = $this->groupRepository->find($groupId);

            if (!$group) {
                return new JsonResponse(['error' => "Ce groupe n'existe pas"], 404);
            }

            $this->denyAccessUnlessGranted(GroupRoleVoter::MANAGE_MEMBERS, $group);

            $requests = $this->groupRequestService->getPendingRequestsForGroup($groupId);

            return new JsonResponse([
                'groupId' => $groupId,
                'pendingRequests' => array_map(fn ($request) => [
                    'requestId' => $request->getId(),
                    'profile' => [
                        'id' => $request->getProfile()->getId(),
                        'username' => $request->getProfile()->getUsername(),
                    ],
                    'createdAt' => $request->getCreatedAt(),
                ], $requests),
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/join', methods: ['POST'])]
    public function requestToJoinGroup(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $profileId = $data['profileId'] ?? null;

        if (!$profileId) {
            return new JsonResponse(['error' => 'Profile ID manquant.'], 400);
        }

        $group = $this->groupRepository->find($id);
        $profile = $this->profileRepository->find($profileId);

        if (!$group || !$profile) {
            return new JsonResponse(['error' => 'Groupe ou Profil non trouvé.'], 404);
        }

        try {
            $this->groupRequestService->requestToJoinGroup($group, $profile);

            return new JsonResponse(['message' => 'Demande envoyée avec succès.'], 201);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{requestId}', methods: ['POST'])]
    public function updateRequestStatus(int $requestId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $profileId = $data['profileId'] ?? null;
        $groupId = $data['groupId'] ?? null;
        $status = $data['status'] ?? null;
        $action = $data['action'] ?? null;

        if (!in_array($action, ['approve', 'deny'], true)) {
            return new JsonResponse(['error' => 'Action invalide'], 400);
        }

        if (!$profileId || !$groupId || !$status) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], 403);
        }

        $groupRequest = $this->groupRequestRepository->find($groupId);

        if (!$groupRequest) {
            return new JsonResponse(['error' => 'Demande non trouvée'], 404);
        }

        $group = $groupRequest->getGroup();

        $this->denyAccessUnlessGranted('manage_members', $group);

        $newStatus = ('approve' === $action) ? RequestStatusEnum::ACCEPTED : RequestStatusEnum::DENIED;

        if (!isset($validActions[$action])) {
            return new JsonResponse(['error' => 'Action invalide'], 400);
        }

        try {
            $this->groupRequestService->updateRequestStatus($groupRequest, $newStatus);

            return new JsonResponse(['message' => "Demande $action avec succès"], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
