<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\GroupErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\DTO\Group\CreateGroupDTO;
use App\DTO\Group\DeleteGroupDTO;
use App\DTO\Group\UpdateGroupDTO;
use App\Enum\Group\GroupSortOptionEnum;
use App\Repository\GroupRepository;
use App\Repository\PostRepository;
use App\Security\Voter\Group\GroupRoleVoter;
use App\Service\GroupService;
use App\Service\PostService;
use App\Validator\Constraints\ProfileValidator;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/api/v1/groups')]
class GroupController extends AbstractController
{
    public function __construct(
        private readonly GroupService $groupService,
        private readonly GroupRepository $groupRepository,
        private readonly ProfileValidator $profileValidator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly PostService $postService,
        private readonly PostRepository $postRepository,
    ) {
    }

    #[Route('/{groupId}/posts/recent', name: 'get_recent_posts', methods: ['GET'])]
    public function getRecentPostsByGroupId(Request $request, int $groupId): JsonResponse
    {
        $profileId = (int) $request->query->get('profileId');

        if (!$profileId) {
            return new JsonResponse(['error' => 'Le paramètre profileId est requis.'], 400);
        }

        $group = $this->groupRepository->find($groupId);
        if (!$group) {
            return new JsonResponse(['error' => GroupErrorMessagesConstant::GROUP_NOT_FOUND], 400);
        }

        try {
            $posts = $this->postService->getRecentPosts(10, $profileId, $groupId);

            return new JsonResponse(['posts' => $posts], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{groupId}/posts/older', name: 'get_oldest_posts', methods: ['GET'])]
    public function getOlderPostsByGroupId(Request $request, int $groupId): JsonResponse
    {
        $profileId = (int) $request->query->get('profileId');

        if (!$profileId) {
            return new JsonResponse(['error' => 'Le paramètre profileId est requis.'], 400);
        }

        $group = $this->groupRepository->find($groupId);
        if (!$group) {
            return new JsonResponse(['error' => GroupErrorMessagesConstant::GROUP_NOT_FOUND], 400);
        }

        $page = max((int) $request->query->get('page', '1'), 1);
        $limit = max((int) $request->query->get('limit', '10'), 10);

        try {
            $posts = $this->postService->getOlderPosts($page, $limit, $profileId, $groupId);
            $totalPosts = $this->postRepository->countTotalPosts();
            $remainingPosts = $totalPosts - ($page * $limit);
            $nextPage = $remainingPosts > 0 ? $page + 1 : null;

            return new JsonResponse([
                'posts' => $posts,
                'nextPage' => $nextPage,
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('', name: 'group_private', methods: ['GET'])]
    public function getAllPrivateGroups(Request $request): JsonResponse
    {
        $search = $request->query->get('search', '');
        $tagName = $request->query->get('tagName', '');
        $sortParam = $request->query->get('sort', GroupSortOptionEnum::NEWEST->value);
        $page = max((int) $request->query->get('page', '1'), 1);
        $limit = max((int) $request->query->get('limit', '10'), 10);
        $profileId = (int) $request->query->get('profileId');
        $myGroups = filter_var($request->query->get('myGroups', 'false'), FILTER_VALIDATE_BOOLEAN);

        $sort = GroupSortOptionEnum::tryFrom($sortParam) ?? GroupSortOptionEnum::NEWEST;

        $tagFilter = !empty($tagName) ? $tagName : null;

        try {
            $privateGroups = $this->groupService->getPrivateGroups($search, $tagFilter, $sort, $page, $limit, $profileId, $myGroups);

            return new JsonResponse(['groups' => $privateGroups], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{slug}', name: 'private_group_slug', methods: ['GET'])]
    public function getPrivateGroupsBySlug(string $slug): JsonResponse
    {
        try {
            $groupBySlug = $this->groupService->getPrivateGroupBySlug($slug);

            return new JsonResponse($groupBySlug, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{groupId}/members', name: 'group_members', methods: ['GET'])]
    public function getGroupMembers(int $groupId): JsonResponse
    {
        try {
            $membersByGroupId = $this->groupService->getMembersByGroupId($groupId);

            return new JsonResponse($membersByGroupId, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('', methods: ['POST'])]
    public function createGroup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreateGroupDTO($data['name'], $data['description'] ?? null, $data['visibility'], $data['profileId']);
        $tagNames = $data['tags'] ?? [];
        if (!isset($dto->name, $dto->description)) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], 400);
        }

        $creator = $this->profileValidator->validateProfile($dto->profileId);

        if ('public' === $dto->visibility && $this->groupRepository->findOneBy(['visibility' => 'public'])) {
            throw new RuntimeException(GroupErrorMessagesConstant::ONLY_ONE_PUBLIC_GROUP_ALLOWED);
        }

        try {
            $group = $this->groupService->createGroup(
                $dto->name,
                $dto->description,
                $creator,
                $dto->visibility,
                $tagNames
            );

            return new JsonResponse([
                'message' => 'Groupe créé avec succès',
                'group' => [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'slug' => $group->getSlug(),
                    'visibility' => $group->getVisibility(),
                    'createdAt' => $group->getCreatedAt(),
                    'tags' => array_map(fn ($taggable) => $taggable->getTag()->getName(), $group->getTaggables()->toArray()),
                ],
            ], 201);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{groupId}', methods: ['PUT'])]
    public function updateGroup(int $groupId, Request $request): JsonResponse
    {
        $group = $this->groupRepository->find($groupId);
        if (!$group) {
            return new JsonResponse(['error' => GroupErrorMessagesConstant::GROUP_NOT_FOUND], 404);
        }

        $data = json_decode($request->getContent(), true);
        $dto = new UpdateGroupDTO($group->getId(), $data['name'], $data['description'], $data['profileId']);

        if (!isset($dto->profileId, $dto->name, $dto->description)) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], 400);
        }
        $this->profileValidator->validateProfile($dto->profileId);

        if (!$this->authorizationChecker->isGranted(GroupRoleVoter::MANAGE_MEMBERS, $group)) {
            return new JsonResponse(['error' => SecurityErrorMessagesConstant::ACCESS_DENIED], 403);
        }

        try {
            $this->groupService->updateGroup(
                $group,
                $dto->name,
                $dto->description,
            );

            return new JsonResponse(['message' => 'Groupe mis à jour avec succès']);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{groupId}', methods: ['DELETE'])]
    public function deleteGroup(int $groupId, Request $request): JsonResponse
    {
        $group = $this->groupRepository->find($groupId);
        if (!$group) {
            return new JsonResponse(['error' => GroupErrorMessagesConstant::GROUP_NOT_FOUND], 404);
        }

        if ('public' === $group->getVisibility()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas supprimer ce groupe'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $dto = new DeleteGroupDTO($data['profileId'], $group->getId());
        if (!isset($dto->profileId, $dto->groupId)) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], 400);
        }

        $creator = $this->profileValidator->validateProfile($dto->profileId);
        try {
            $this->groupService->deleteGroup($group);

            return new JsonResponse(['message' => 'Groupe supprimé avec succès']);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
