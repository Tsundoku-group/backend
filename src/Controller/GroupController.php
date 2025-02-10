<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Group\CreateGroupDTO;
use App\DTO\Group\DeleteGroupDTO;
use App\DTO\Group\UpdateGroupDTO;
use App\Repository\GroupRepository;
use App\Security\Voter\Group\GroupRoleVoter;
use App\Service\GroupService;
use App\Validator\Constraints\ProfileValidator;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/api/v1/group')]
class GroupController extends AbstractController
{
    public function __construct(
        private readonly GroupService $groupService,
        private readonly GroupRepository $groupRepository,
        private readonly ProfileValidator $profileValidator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function createGroup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreateGroupDTO($data['name'], $data['description'] ?? null, $data['visibility'], $data['profileId']);

        if (!isset($dto->name, $dto->description)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $creator = $this->profileValidator->validateProfile($dto->profileId);

        if ('public' === $dto->visibility && $this->groupRepository->findOneBy(['visibility' => 'public'])) {
            throw new RuntimeException(ErrorMessagesConstant::ONLY_ONE_PUBLIC_GROUP_ALLOWED);
        }

        try {
            $group = $this->groupService->createGroup(
                $dto->name,
                $dto->description,
                $creator,
                $dto->visibility
            );

            return new JsonResponse([
                'message' => 'Groupe créé avec succès',
                'group' => [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'slug' => $group->getSlug(),
                    'visibility' => $group->getVisibility(),
                    'createdAt' => $group->getCreatedAt()->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function updateGroup(int $id, Request $request): JsonResponse
    {
        $group = $this->groupRepository->find($id);
        if (!$group) {
            return new JsonResponse(['error' => 'Groupe introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $dto = new UpdateGroupDTO($group->getId(), $data['name'], $data['description'], $data['profileId']);

        if (!isset($dto->profileId, $dto->name, $dto->description)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }
        $this->profileValidator->validateProfile($dto->profileId);

        if (!$this->authorizationChecker->isGranted(GroupRoleVoter::MANAGE_MEMBERS, $group) || !$this->authorizationChecker->isGranted(GroupRoleVoter::MANAGE_MEMBERS, $group)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::ACCESS_DENIED], 403);
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
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteGroup(int $id, Request $request): JsonResponse
    {
        $group = $this->groupRepository->find($id);
        if (!$group) {
            return new JsonResponse(['error' => 'Groupe introuvable'], 404);
        }

        if ('public' === $group->getVisibility()) {
            return new JsonResponse(['error' => 'Vous ne pouvez pas supprimer ce groupe'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $dto = new DeleteGroupDTO($data['profileId'], $group->getId());
        if (!isset($dto->profileId, $dto->groupId)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $creator = $this->profileValidator->validateProfile($dto->profileId);
        try {
            $this->groupService->deleteGroup($group, $creator->getId());

            return new JsonResponse(['message' => 'Groupe supprimé avec succès']);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
