<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Entity\Group;
use App\Entity\GroupProfile;
use App\Entity\Profile;
use App\Repository\GroupRepository;
use App\Security\Voter\Group\GroupRoleVoter;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class GroupService
{
    public function __construct(
        private EntityManagerInterface        $entityManager,
        private SluggerInterface              $slugger,
        private AuthorizationCheckerInterface $authorizationChecker,
        private GroupRepository               $groupRepository,
        private TagService                    $tagService,
    )
    {
    }

    public function getPrivateGroups(string $search = '', ?string $tagName = null, string $sort = 'newest', int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        $privateGroups = $this->groupRepository->findPrivateGroups($search, $tagName, $sort, $limit, $offset);

        return array_map(fn($result) => $this->formatGroupResult($result), $privateGroups);
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
        $group = is_array($result) && isset($result[0]) ? $result[0] : $result;
        $membersCount = is_array($result) && isset($result['membersCount']) ? $result['membersCount'] : 0;

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
            'tags' => array_map(fn($taggable) => [
                'name' => $taggable->getTag()->getName(),
                'slug' => $taggable->getTag()->getSlug(),
                'parent' => $taggable->getTag()->getParentTag() ? [
                    'name' => $taggable->getTag()->getParentTag()->getName(),
                    'slug' => $taggable->getTag()->getParentTag()->getSlug(),
                ] : null
            ], $group->getTaggables()->toArray())
        ];
    }
}
