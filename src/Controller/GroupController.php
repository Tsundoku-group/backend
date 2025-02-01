<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\Repository\ProfileRepository;
use App\Service\GroupService;
use App\Repository\GroupRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/group')]
class GroupController extends AbstractController
{
    public function __construct(
        private readonly GroupService $groupService,
        private readonly ProfileRepository $profileRepository,
        private readonly GroupRepository $groupRepository
    ) {}

    #[Route('/create', methods: ['POST'])]
    public function createGroup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['profileId'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $creator = $this->profileRepository->find($data['profileId']);
        if (!$creator) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $group = $this->groupService->createGroup(
                $data['name'],
                $data['description'] ?? null,
                $creator,
                $data['visibility']
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
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
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
        if (!isset($data['name'], $data['visibility'], $data['profileId'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $this->groupService->updateGroup(
                $group,
                $data['profileId'],
                $data['name'],
                $data['description'] ?? null,
                $data['visibility']
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
        if (!isset($data['profileId'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $this->groupService->deleteGroup($group, $data['profileId']);

            return new JsonResponse(['message' => 'Groupe supprimé avec succès']);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}