<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\Exception\InvalidCredentialsException;
use App\Service\TagService;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('api/v1/tags')]
class TagController extends AbstractController
{
    public function __construct(
        private readonly TagService $tagService,
    )
    {

    }

    #[Route('', methods: ['GET'])]
    public function getAllTags(): JsonResponse
    {
        try {
            $tags = $this->tagService->getAllTags();

            return new JsonResponse(['tags' => $tags], 200);
        } catch (InvalidCredentialsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('', name: 'add_tags_to_entity', methods: ['POST'])]
    public function addTagsToEntity(Request $request): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        if (!isset($data['entityType'], $data['entityId'], $data['tags'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $this->tagService->addTagToEntity($data['entityType'], (int)$data['entityId'], $data['tags']);

            return new JsonResponse(['message' => 'Tags ajoutés avec succès.'], 200);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('', name: 'remove_tags', methods: ['DELETE'])]
    public function deleteTagsFromEntity(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['entityType'], $data['entityId'], $data['tags'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $this->tagService->removeTagsFromEntity($data['entityType'], (int)$data['entityId'], $data['tags']);

            return new JsonResponse(['message' => 'Tags supprimés']);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}