<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\PostErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\DTO\Comment\GetCommentDTO;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Service\CommentService;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/comments')]
class CommentController extends AbstractController
{
    public function __construct(
        private readonly CommentService $commentService,
        private readonly CommentRepository $commentRepository,
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
            return $this->json(['error' => 'Argument non valide : ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => 'Erreur de serveur interne', 'détails' => $e->getMessage()], 500);
        }
    }

    #[Route('/post/{postId}', methods: ['GET'])]
    public function getCommentsForPost(int $postId, Request $request): JsonResponse
    {
        try {
            $profileId = (int) $request->query->get('profileId');

            if (!$profileId) {
                return new JsonResponse(['error' => 'Le paramètre profileId est requis.'], 400);
            }

            $dto = new GetCommentDTO($postId);

            $post = $this->postRepository->findPostWithGroupById($dto->postId);
            if (!$post) {
                return $this->json(['error' => PostErrorMessagesConstant::POST_NOT_FOUND], 404);
            }

            return $this->commentService->getCommentsForPost($dto->postId, $profileId);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/{commentId}/children', methods: ['GET'])]
    public function getCommentWithChildren(string $commentId, Request $request): JsonResponse
    {
        $profileId = (int) $request->query->get('profileId');

        if (!$profileId) {
            return new JsonResponse(['error' => 'Le paramètre profileId est requis.'], 400);
        }

        try {
            return $this->commentService->getCommentChildren($commentId, $profileId);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('', name: 'add_comment_to_post', methods: ['POST'])]
    public function addCommentToPost(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['postId'], $data['authorId'], $data['content'])) {
            return $this->json(['error' => GenericErrorMessagesConstant::MISSING_PARAMETERS], 400);
        }

        $post = $this->postRepository->findPostWithGroupById($data['postId']);
        if (!$post) {
            return $this->json(['error' => PostErrorMessagesConstant::POST_NOT_FOUND], 404);
        }

        $author = $this->profileRepository->findProfileById($data['authorId']);
        if (!$author) {
            return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            return $this->commentService->addCommentToPost($data['postId'], $data['authorId'], $data['content']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/{commentId}', name: 'update_comment', methods: ['PUT'])]
    public function updateComment(string $commentId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['authorId']) || empty($data['content'])) {
            return $this->json(['error' => GenericErrorMessagesConstant::MISSING_PARAMETERS], 400);
        }

        $author = $this->profileRepository->findProfileById($data['authorId']);
        if (!$author) {
            return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            return $this->commentService->editComment($commentId, $data['authorId'], $data['content']);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/{commentId}', name: 'delete_comment', methods: ['DELETE'])]
    public function deleteComment(string $commentId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $authorId = $data['authorId'];

            $author = $this->profileRepository->findProfileById($data['authorId']);
            if (!$author) {
                return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
            }

            $comment = $this->commentRepository->find($commentId);

            if (!$comment) {
                return $this->json(['error' => 'commentaire non trouvé'], 404);
            }

            if ($comment->getAuthorId() !== (string) $authorId) {
                return $this->json(['error' => 'Action non autorisée'], 403);
            }

            $this->commentService->deleteComment($comment);

            return $this->json(['message' => 'Commentaire supprimé avec succès'], 200);
        } catch (Exception $e) {
            return $this->json(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/replies', name: 'reply_to_comment', methods: ['POST'])]
    public function replyToComment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['postId'], $data['authorId'], $data['content'], $data['parentId'])) {
            return $this->json(['error' => GenericErrorMessagesConstant::MISSING_PARAMETERS], 400);
        }

        $post = $this->postRepository->findPostWithGroupById((int) $data['postId']);
        if (!$post) {
            return $this->json(['error' => PostErrorMessagesConstant::POST_NOT_FOUND], 404);
        }

        $author = $this->profileRepository->findProfileById((int) $data['authorId']);
        if (!$author) {
            return $this->json(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        $parentComment = $this->commentRepository->find($data['parentId']);
        if (!$parentComment) {
            return $this->json(['error' => "Le commentaire du parent n'a pas été trouvé"], 404);
        }

        try {
            return $this->commentService->replyToComment(
                (int) $data['postId'],
                (int) $data['authorId'],
                $data['content'],
                $data['parentId']
            );
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid argument: ' . $e->getMessage()], 400);
        } catch (Exception $e) {
            return $this->json(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }
}
