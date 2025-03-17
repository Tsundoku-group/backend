<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Group;
use App\Entity\GroupProfile;
use App\Repository\GroupProfileRepository;
use App\Repository\GroupRepository;
use App\Security\Voter\Group\GroupRoleVoter;
use App\Validator\Constraints\ProfileValidator;
use App\ValueObject\Group\GroupRole;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class GroupProfileService
{
    public function __construct(
        private GroupRepository $groupRepository,
        private GroupProfileRepository $groupProfileRepository,
        private GroupRequestService $groupRequestService,
        private EntityManagerInterface $entityManager,
        private ProfileValidator $profileValidator,
    ) {
    }

    public function joinGroup(int $groupId, int $profileId, string $role): JsonResponse
    {
        $group = $this->groupRepository->find($groupId);

        if (!$group) {
            return new JsonResponse(['error' => ErrorMessagesConstant::GROUP_NOT_FOUND], 404);
        }

        $profile = $this->profileValidator->validateProfile($profileId);

        $existingGroupProfile = $this->groupProfileRepository->findOneGroupProfile($groupId, $profileId);

        if ($existingGroupProfile) {
            return new JsonResponse(['error' => ErrorMessagesConstant::USER_ALREADY_IN_GROUP], 400);
        }
        try {
            if ('public' === $group->getVisibility()) {
                $groupProfile = new GroupProfile($group, $profile, GroupRoleVoter::fromString($role));
                $groupProfile->markAsUpdated();

                $this->entityManager->persist($groupProfile);
                $this->entityManager->flush();

                return new JsonResponse(['message' => 'Membre ajouté au groupe avec succès.'], 201);
            }

            $this->groupRequestService->requestToJoinGroup($group, $profile);

            return new JsonResponse(['message' => 'Demande envoyée avec succès.'], 201);
        } catch (Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    public function updateMemberRole(int $groupId, int $profileId, string $role, int $adminId): void
    {
        $groupProfile = $this->groupProfileRepository->findOneGroupProfile($groupId, $profileId);

        if (!$groupProfile) {
            throw new NotFoundHttpException(ErrorMessagesConstant::USER_NOT_IN_GROUP);
        }

        $this->ensureUserIsAdminOfGroup($groupProfile->getGroup(), $adminId);

        $newRole = GroupRole::fromString($role);

        if ($groupProfile->getRole()->equals($newRole)) {
            throw new RuntimeException(ErrorMessagesConstant::USER_ALREADY_HAS_ROLE);
        }

        $groupProfile->setRole($newRole);
        $groupProfile->markAsUpdated();
        $this->entityManager->flush();
    }

    public function removeMember(int $groupId, int $profileId, int $adminId): void
    {
        $groupProfile = $this->groupProfileRepository->findOneGroupProfile($groupId, $profileId);

        if (!$groupProfile) {
            throw new NotFoundHttpException(ErrorMessagesConstant::USER_NOT_IN_GROUP);
        }

        $this->ensureUserIsAdminOfGroup($groupProfile->getGroup(), $adminId);

        $this->entityManager->remove($groupProfile);
        $this->entityManager->flush();
    }

    private function ensureUserIsAdminOfGroup(Group $group, int $profileId): void
    {
        $groupProfile = $this->groupProfileRepository->findOneGroupProfile($group->getId(), $profileId);

        if (!$groupProfile || !$groupProfile->isAdmin()) {
            throw new AccessDeniedHttpException(ErrorMessagesConstant::ACCESS_DENIED);
        }
    }
}
