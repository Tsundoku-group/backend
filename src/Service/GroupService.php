<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Group;
use App\Entity\GroupProfile;
use App\Entity\Profile;
use App\Repository\GroupRepository;
use App\ValueObject\GroupRole;
use App\Repository\GroupProfileRepository;
use App\ValueObject\GroupVisibility;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class GroupService
{
    public function __construct(
        private GroupProfileRepository  $groupProfileRepository,
        private GroupRepository $groupRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface       $slugger
    )
    {
    }

    public function createGroup(string $name, ?string $description, Profile $creator, string $visibility): Group
    {
        $slug = $this->slugger->slug($name)->lower();

        $visibilityObject = $visibility ? GroupVisibility::fromString($visibility) : GroupVisibility::private();

        if ($visibilityObject->isPublic() && !$visibilityObject->isUniquePublicGroup($slug)) {
            throw new \RuntimeException(ErrorMessagesConstant::ONLY_ONE_PUBLIC_GROUP_ALLOWED);
        }

        if ($visibilityObject->isPublic() && $this->groupRepository->findOneBy(['visibility' => 'public'])) {
            throw new \RuntimeException(ErrorMessagesConstant::ONLY_ONE_PUBLIC_GROUP_ALLOWED);
        }

        $this->entityManager->beginTransaction();
        try {
            $group = new Group($creator, $visibilityObject);
            $group->setName($name);
            $group->setDescription($description);
            $group->setSlug($this->slugger->slug($name)->lower());
            $group->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($group);
            $this->entityManager->flush();

            $groupProfile = new GroupProfile($group, $creator, GroupRole::fromString('admin'));
            $this->entityManager->persist($groupProfile);
            $this->entityManager->flush();

            $this->entityManager->commit();
            return $group;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw new \RuntimeException(ErrorMessagesConstant::INTERNAL_SERVER_ERROR . $e->getMessage());
        }
    }

    public function updateGroup(Group $group, int $profileId, string $name, ?string $description, string $visibility): void
    {
        $visibilityObject = GroupVisibility::fromString($visibility);

        $this->ensureUserIsAdminOfGroup($group, $profileId);

        $hasChanges = false;

        if ($group->getName() !== $name) {
            $group->setName($name);
            $hasChanges = true;
        }

        if ($group->getDescription() !== $description) {
            $group->setDescription($description);
            $hasChanges = true;
        }

        if ($group->getVisibility()->getValue() !== $visibilityObject->getValue()) {
            $group->setVisibility($visibilityObject);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $group->setUpdatedAt(new \DateTime());
        }

        $this->entityManager->persist($group);
        $this->entityManager->flush();
    }

    public function deleteGroup(Group $group, int $profileId): void
    {
        $this->ensureUserIsAdminOfGroup($group, $profileId);

        try {
            $this->entityManager->remove($group);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException(ErrorMessagesConstant::INTERNAL_SERVER_ERROR . $e->getMessage());
        }
    }

    private function ensureUserIsAdminOfGroup(Group $group, int $profileId): void
    {
        $groupProfile = $this->groupProfileRepository->findOneBy(['group' => $group, 'profile' => $profileId]);

        if (!$groupProfile) {
            throw new \RuntimeException(ErrorMessagesConstant::USER_NOT_IN_GROUP);
        }

        if (!$groupProfile->getRole()->isAdmin()) {
            throw new \RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
        }
    }
}