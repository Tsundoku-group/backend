<?php

namespace App\Controller;

use App\Service\MarkService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/marks')]
class MarkController extends AbstractController
{
    private MarkService $markService;

    public function __construct(MarkService $markService)
    {
        $this->markService = $markService;
    }

    #[Route('', name: 'get_marks', methods: ['GET'])]
    public function getMarks(Request $request): JsonResponse
    {
        $targetId = $request->query->get('targetId');
        $targetType = $request->query->get('targetType');

        if (empty($targetId) || empty($targetType)) {
            return new JsonResponse(['error' => 'Les paramètres targetId et targetType sont requis.'], 400);
        }

        try {
            $marks = $this->markService->getMarksByTarget((int)$targetId, $targetType);
            return new JsonResponse(['marks' => $marks], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('', name: 'create_mark', methods: ['POST'])]
    public function createMark(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['profileId']) || empty($data['targetId']) || empty($data['targetType'])) {
            return new JsonResponse(['error' => 'Les champs profileId, targetId et targetType sont obligatoires.'], 400);
        }

        try {
            $mark = $this->markService->createOrUpdateMark(
                (int)$data['profileId'],
                (int)$data['targetId'],
                $data['targetType'],
                isset($data['rating']) ? (float)$data['rating'] : null,
                isset($data['isPinned']) && (bool)$data['isPinned'],
                isset($data['isFavorite']) && (bool)$data['isFavorite']
            );

            return new JsonResponse([
                'message' => 'Mark créé avec succès.',
                'mark' => [
                    'id' => $mark->getId(),
                    'rating' => $mark->getRating(),
                    'isPinned' => $mark->getIsPinned(),
                    'isFavorite' => $mark->getIsFavorite(),
                    'targetId' => $mark->getTargetId(),
                    'targetType' => $mark->getTargetType(),
                    'createdAt' => $mark->getCreatedAt()->format('c'),
                ]
            ], 201);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'delete_mark', methods: ['DELETE'])]
    public function deleteMark(int $id): JsonResponse
    {
        try {
            $this->markService->deleteMark($id);
            return new JsonResponse(['message' => 'Mark supprimé avec succès.'], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}