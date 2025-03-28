<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Group;
use App\Entity\GroupProfile;
use App\Repository\GroupProfileRepository;
use App\Repository\GroupRepository;
use App\Security\Voter\Group\GroupRoleVoter;
use App\Validator\Constraints\ProfileValidator;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class GroupProfileService
{
    public function __construct(
        private GroupRepository $groupRepository,
        private GroupProfileRepository $groupProfileRepository,
        private EntityManagerInterface $entityManager,
        private ProfileValidator $profileValidator,
    ) {
    }

    public function joinGroup(int $groupId, int $profileId, string $role): GroupProfile
    {
        $group = $this->groupRepository->find($groupId);

        if (!$group) {
            throw new NotFoundHttpException(ErrorMessagesConstant::GROUP_NOT_FOUND);
        }

        $profile = $this->profileValidator->validateProfile($profileId);

        $existingGroupProfile = $this->groupProfileRepository->findOneGroupProfile($groupId, $profileId);

        if ($existingGroupProfile) {
            throw new RuntimeException(ErrorMessagesConstant::USER_ALREADY_IN_GROUP);
        }

        $groupProfile = new GroupProfile($group, $profile, GroupRoleVoter::fromString($role));
        $groupProfile->markAsUpdated();

        $this->entityManager->persist($groupProfile);
        $this->entityManager->flush();

        return $groupProfile;
    }

    public function updateMemberRole(int $groupId, int $profileId, string $role, int $adminId): void
    {
        $groupProfile = $this->groupProfileRepository->findOneGroupProfile($groupId, $profileId);

        if (!$groupProfile) {
            throw new NotFoundHttpException(ErrorMessagesConstant::USER_NOT_IN_GROUP);
        }

        $this->ensureUserIsAdminOfGroup($groupProfile->getGroup(), $adminId);

        $newRole = GroupRoleVoter::fromString($role);

        if ($groupProfile->getRole() === $newRole) {
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
