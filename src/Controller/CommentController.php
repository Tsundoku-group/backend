<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Comment\CreateCommentDTO;
use App\DTO\Comment\GetCommentDTO;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Service\CommentService;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/comment')]
class CommentController extends AbstractController
{
    public function __construct(
        private readonly CommentService $commentService,
        private readonly PostRepository $postRepository,
        private readonly ProfileRepository $profileRepository,
    ) {
    }

    #[Route('/{commentId}', name: 'get_comment_by_id', methods: ['GET'])]
    public function getOneCommentById(string $commentId): JsonResponse
    {
        try {
            return $this->commentService->getCommentById($commentId);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/{postId}/comments', methods: ['GET'])]
    public function getCommentsForPost(string $postId): JsonResponse
    {
        try {
            $dto = new GetCommentDTO($postId);

            $post = $this->postRepository->findPostWithGroupById($dto->postId);
            if (!$post) {
                return $this->json(['error' => ErrorMessagesConstant::POST_NOT_FOUND], 404);
            }

            return $this->commentService->getCommentsForPost($dto->postId);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/{commentId}/children', methods: ['GET'])]
    public function getCommentWithChildren(string $commentId): JsonResponse
    {
        try {
            return $this->commentService->getCommentChildren($commentId);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/add', name: 'add_comment', methods: ['POST'])]
    public function addComment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreateCommentDTO(
            $data['postId'] ?? '',
            $data['authorId'] ?? '',
            $data['content'] ?? '',
            $data['parentId'] ?? null
        );

        if (!isset($dto->post, $dto->authorId, $dto->content)) {
            return $this->json(['error' => 'Missing parameters'], 400);
        }

        $post = $this->postRepository->findPostWithGroupById($dto->postId);
        if (!$post) {
            return $this->json(['error' => ErrorMessagesConstant::POST_NOT_FOUND], 404);
        }

        $findAuthor = $this->profileRepository->findOneBy((array) $dto->authorId);
        if (!$findAuthor) {
            return $this->json(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        $postId = (string) $post->getId();

        try {
            $comment = $this->commentService->addComment(
                $postId,
                $findAuthor->getId(),
                $dto->content,
                isset($dto->parentId) ? (string) $dto->parentId : null,
            );

            return new JsonResponse($comment);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }
}
