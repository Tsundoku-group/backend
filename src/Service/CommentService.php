<?php

namespace App\Service;

use App\Document\Comment;
use App\Repository\CommentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private DocumentManager $dm,
    ) {
    }

    public function getCommentById(string $commentId): JsonResponse
    {
        try {
            $comment = $this->commentRepository->findCommentById($commentId);

            if (!$comment) {
                return new JsonResponse(['error' => 'Comment not found'], 404);
            }

            return new JsonResponse([
                'id' => (string) $comment->getId(),
                'postId' => (string) $comment->getPostId(),
                'parentId' => $comment->getParentId() ? (string) $comment->getParentId() : null,
                'content' => $comment->getContent(),
                'authorId' => $comment->getAuthorId(),
                'createdAt' => $comment->getCreatedAt(),
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function getCommentChildren(string $commentId): JsonResponse
    {
        try {
            $childComments = $this->commentRepository->findChildrenByParentId($commentId);

            if (empty($childComments)) {
                return new JsonResponse([], 200);
            }

            $formattedComments = array_map(fn($comment) => [
                '_id' => (string) $comment->getId(),
                'postId' => (string) $comment->getPostId(),
                'parentId' => (string) $comment->getParentId(),
                'content' => $comment->getContent(),
                'authorId' => $comment->getAuthorId(),
                'createdAt' => $comment->getCreatedAt(),
                'children' => []
            ], $childComments);

            return new JsonResponse($formattedComments, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function getCommentsForPost(string $postId, int $limit = 5): JsonResponse
    {
        try {
            if (!$postId) {
                return new JsonResponse(['error' => 'Post ID is required'], 400);
            }

            $comments = $this->commentRepository->getMainComments($postId, $limit);

            if (empty($comments)) {
                return new JsonResponse(['message' => 'No comments found'], 200);
            }

            $formattedComments = array_map(fn ($comment) => [
                'id' => (string) $comment->getId(),
                'postId' => (string) $comment->getPostId(),
                'parentId' => null,
                'content' => $comment->getContent(),
                'authorId' => $comment->getAuthorId(),
                'createdAt' => $comment->getCreatedAt(),
                'replyCount' => $this->commentRepository->countChildrenByParentId($comment->getId()),
            ], $comments);

            return new JsonResponse(['comments' => $formattedComments], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function addComment(string $postId, string $authorId, string $content, ?string $parentId = null): JsonResponse
    {
        try {
            if (empty($postId) || empty($authorId) || empty($content)) {
                return new JsonResponse(['error' => 'Missing parameters: postId, authorId, and content are required'], 400);
            }

            $comment = new Comment($postId, $authorId, $content, $parentId);
            $this->dm->persist($comment);
            $this->dm->flush();

            if ($parentId) {
                $parentComment = $this->dm->getRepository(Comment::class)->find($parentId);
                if ($parentComment) {
                    $parentComment->addChild($comment->getId());
                    $this->dm->persist($parentComment);
                    $this->dm->flush();
                }
            }

            return new JsonResponse([
                'message' => 'Comment successfully added',
                'comment' => [
                    'id' => $comment->getId(),
                    'content' => $comment->getContent(),
                    'postId' => $comment->getPostId(),
                    'parent' => $comment->getParentId() ? (string) $comment->getParentId() : null,
                    'createdAt' => $comment->getCreatedAt(),
                ],
            ], 201);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }
}
