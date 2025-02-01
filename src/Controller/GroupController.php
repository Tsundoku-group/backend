<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Group\CreateGroupDTO;
use App\DTO\Group\DeleteGroupDTO;
use App\DTO\Group\UpdateGroupDTO;
use App\Service\GroupService;
use App\Repository\GroupRepository;
use App\Validator\Constraints\ProfileValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/group')]
class GroupController extends AbstractController
{
    public function __construct(
        private readonly GroupService $groupService,
        private readonly GroupRepository $groupRepository,
        private readonly ProfileValidator $profileValidator,
    ) {}

    #[Route('/create', methods: ['POST'])]
    public function createGroup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreateGroupDTO($data['name'], $data['description'] ?? null, $data['visibility'], $data['profileId']);

        if (!isset($dto->name, $dto->description)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $creator = $this->profileValidator->validateProfile($dto->profileId);

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
                    'visibility' => $group->getVisibility()->getValue(),
                    'createdAt' => $group->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ], 201);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{id}/edit', methods: ['PUT'])]
    public function updateGroup(int $id, Request $request): JsonResponse
    {
        $group = $this->groupRepository->find($id);
        if (!$group) {
            return new JsonResponse(['error' => 'Groupe introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $dto = new UpdateGroupDTO($data['name'], $data['visibility'], $data['profileId'], $data['description'], $group->getId());
        if (!isset($dto->name, $dto->description, $dto->profileId)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }
        $creator = $this->profileValidator->validateProfile($dto->profileId);

        try {
            $this->groupService->updateGroup(
                $group,
                $creator->getId(),
                $dto->name,
                $dto->description,
                $dto->visibility
            );

            return new JsonResponse(['message' => 'Groupe mis à jour avec succès']);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
    #[Route('/{id}/delete', methods: ['DELETE'])]
    public function deleteGroup(int $id, Request $request): JsonResponse
    {
        $group = $this->groupRepository->find($id);
        if (!$group) {
            return new JsonResponse(['error' => 'Groupe introuvable'], 404);
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
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}