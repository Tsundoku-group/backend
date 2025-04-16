<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Group;
use App\Entity\GroupProfile;
use App\Entity\Profile;
use App\Enum\Group\GroupSortOptionEnum;
use App\Enum\RequestStatusEnum;
use App\Repository\GroupRepository;
use App\Repository\GroupRequestRepository;
use App\Repository\MarkRepository;
use App\Security\Voter\Group\GroupRoleVoter;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class GroupService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        private AuthorizationCheckerInterface $authorizationChecker,
        private GroupRepository $groupRepository,
        private GroupRequestRepository $groupRequestRepository,
        private TagService $tagService,
        private MarkRepository $markRepository,
    ) {
    }

    public function getPrivateGroups(
        string $search = '',
        ?string $tagName = null,
        GroupSortOptionEnum $sort = GroupSortOptionEnum::NEWEST,
        int $page = 1,
        int $limit = 20,
        ?int $profileId = null,
        bool $myGroups = false
    ): array {
        $offset = ($page - 1) * $limit;
        $privateGroups = $this->groupRepository->findPrivateGroups($search, $tagName, $sort->value, $limit, $offset);

        $userGroupIds = [];
        $userGroupRequests = [];
        if ($profileId) {
            $userGroups = $this->groupRepository->findGroupsByProfile($profileId);
            $userGroupIds = array_column($userGroups, 'id');

            $groupRequests = $this->groupRequestRepository->findPendingRequestsByProfile($profileId);
            foreach ($groupRequests as $request) {
                $userGroupRequests[$request->getGroup()->getId()] = $request->getStatus();
            }
        }

        $marksByGroupId = [];
        if ($profileId) {
            $marks = $this->markRepository->findBy([
                'profile' => $profileId,
                'targetType' => 'group'
            ]);

            foreach ($marks as $mark) {
                $marksByGroupId[(int)$mark->getTargetId()] = [
                    'isFavorite' => $mark->getIsFavorite(),
                    'isPinned' => $mark->getIsPinned(),
                    'rating'    => $mark->getRating(),
                ];
            }
        }

        $mappedGroups = array_map(function ($groupData) use ($userGroupIds, $userGroupRequests, $marksByGroupId) {
            if (is_array($groupData) && isset($groupData[0]) && is_object($groupData[0])) {
                $group = $groupData[0];
                $membersCount = $groupData['membersCount'] ?? 0;
            } elseif (is_object($groupData)) {
                $group = $groupData;
                $membersCount = 0;
            } else {
                $group = (object)$groupData;
                $membersCount = $groupData['membersCount'] ?? 0;
            }

            $groupId = $group->getId();

            if (in_array($groupId, $userGroupIds)) {
                $joinStatus = 'member';
            } elseif (array_key_exists($groupId, $userGroupRequests)) {
                $joinStatus = $userGroupRequests[$groupId];
            } else {
                $joinStatus = 'none';
            }

            $isFavorite = isset($marksByGroupId[$groupId]) ? $marksByGroupId[$groupId]['isFavorite'] : false;
            $isPinned   = isset($marksByGroupId[$groupId]) ? $marksByGroupId[$groupId]['isPinned'] : false;

            return [
                'id' => $group->getId(),
                'name' => $group->getName(),
                'membersCount' => $membersCount,
                'createdAt' => $group->getCreatedAt(),
                'visibility' => $group->getVisibility(),
                'slug' => $group->getSlug(),
                'joinStatus' => $joinStatus,
                'isFavorite' => $isFavorite,
                'isPinned'   => $isPinned,
                'tags' => array_map(fn ($taggable) => [
                    'name' => $taggable->getTag()->getName(),
                    'slug' => $taggable->getTag()->getSlug(),
                    'parent' => $taggable->getTag()->getParentTag() ? [
                        'name' => $taggable->getTag()->getParentTag()->getName(),
                        'slug' => $taggable->getTag()->getParentTag()->getSlug(),
                    ] : null,
                ], $group->getTaggables()->toArray()),
            ];
        }, $privateGroups);

        if ($profileId && $myGroups) {
            $mappedGroups = array_filter($mappedGroups, function ($group) {
                if ($group['joinStatus'] instanceof RequestStatusEnum) {
                    $status = strtolower(trim($group['joinStatus']->value));
                } else {
                    $status = strtolower(trim((string) $group['joinStatus']));
                }

                return in_array($status, ['member', 'pending'], true);
            });
            $mappedGroups = array_values($mappedGroups);
        }

        return $mappedGroups;
    }

    public function getPrivateGroupBySlug(string $slug): array
    {
        try {
            $oneGroupBySlug = $this->groupRepository->findOneBy(['slug' => $slug]);

            if (!$oneGroupBySlug) {
                throw new RuntimeException('Group not found');
            }

            return $this->formatGroupResult($oneGroupBySlug);
        } catch (Exception $e) {
            throw new RuntimeException('Group not found');
        }
    }

    public function getMembersByGroupId(int $groupId): array
    {
        $group = $this->groupRepository->find($groupId);

        if (!$group) {
            throw new RuntimeException('Group not found');
        }

        $groupProfiles = $group->getGroupProfiles();

        return array_map(function ($groupProfile) {
            $profile = $groupProfile->getProfile();
            $activePhotos = $profile->getProfilePhotos()->filter(fn ($photo) => $photo->isActive());

            return [
                'id' => $profile->getId(),
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'username' => $profile->getUsername(),
                'groupRole' => $groupProfile->getRole(),
                'joinAt' => $groupProfile->getJoinAt(),
                'imageUrl' => $activePhotos,
            ];
        }, $groupProfiles->toArray());
    }

    public function createGroup(string $name, ?string $description, Profile $creator, string $visibility, array $tagNames = []): Group
    {
        $this->entityManager->beginTransaction();
        try {
            $group = new Group($creator);
            $group->setName($name);
            $group->setDescription($description);
            $group->setSlug($this->slugger->slug($name)->lower());
            $group->setCreatedAt(new DateTimeImmutable());
            $group->setVisibility($visibility);

            $this->entityManager->persist($group);
            $this->entityManager->flush();
            $groupProfile = new GroupProfile($group, $creator, 'admin');
            $this->entityManager->persist($groupProfile);
            $this->entityManager->flush();
            $this->entityManager->refresh($group);

            if (!empty($tagNames)) {
                $this->tagService->addTagToEntity('group', $group->getId(), $tagNames);
            }

            $this->entityManager->commit();

            return $group;
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw new RuntimeException(ErrorMessagesConstant::INTERNAL_SERVER_ERROR . $e->getMessage());
        }
    }

    public function updateGroup(Group $group, ?string $name, ?string $description): void
    {
        $hasChanges = false;

        if ($group->getName() !== $name) {
            $group->setName($name);
            $group->setSlug($this->slugger->slug($name)->lower());
            $hasChanges = true;
        }

        if ($group->getDescription() !== $description) {
            $group->setDescription($description);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $group->setUpdatedAt(new DateTime());
            $this->entityManager->persist($group);
            $this->entityManager->flush();
        }
    }

    public function deleteGroup(Group $group): void
    {
        if (!$this->authorizationChecker->isGranted(GroupRoleVoter::DELETE_GROUP, $group)) {
            throw new RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
        }

        try {
            $this->entityManager->remove($group);
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new RuntimeException(ErrorMessagesConstant::INTERNAL_SERVER_ERROR . $e->getMessage());
        }
    }

    public function formatGroupResult(array|object $result): array
    {
        if (is_array($result)) {
            $group = $result[0] ?? $result;
            $membersCount = $result['membersCount'] ?? 0;
        } else {
            $group = $result;
            $membersCount = method_exists($group, 'getGroupProfiles')
                ? count($group->getGroupProfiles())
                : 0;
        }

        return [
            'id' => $group->getId(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
            'visibility' => $group->getVisibility(),
            'slug' => $group->getSlug(),
            'createdAt' => $group->getCreatedAt(),
            'updatedAt' => $group->getUpdatedAt(),
            'membersCount' => $membersCount,
            'createdBy' => [
                'id' => $group->getCreatedBy()->getId(),
                'username' => $group->getCreatedBy()->getUsername(),
            ],
            'membersPreview' => array_slice(array_map(fn($gp) => [
                'id' => $gp->getProfile()->getId(),
                'username' => $gp->getProfile()->getUsername(),
                'profilePhoto' => $gp->getProfile()->getActiveProfile()
            ], $group->getGroupProfiles()->toArray()), 0, 10),
            'tags' => array_map(fn ($taggable) => [
                'name' => $taggable->getTag()->getName(),
                'slug' => $taggable->getTag()->getSlug(),
                'parent' => $taggable->getTag()->getParentTag() ? [
                    'name' => $taggable->getTag()->getParentTag()->getName(),
                    'slug' => $taggable->getTag()->getParentTag()->getSlug(),
                ] : null,
            ], $group->getTaggables()->toArray()),
        ];
    }
}
