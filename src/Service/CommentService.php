<?php

namespace App\Service;

use App\Document\Comment;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Validator\Constraints\ProfileValidator;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private ProfileRepository $profileRepository,
        private ProfileValidator $profileValidator,
        private PostRepository $postRepository,
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
                'id' => $comment->getId(),
                'postId' => $comment->getPostId(),
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

            $formattedComments = array_map(fn ($comment) => [
                '_id' => (string) $comment->getId(),
                'postId' => (string) $comment->getPostId(),
                'parentId' => (string) $comment->getParentId(),
                'content' => $comment->getContent(),
                'authorId' => $comment->getAuthorId(),
                'createdAt' => $comment->getCreatedAt(),
                'children' => [],
            ], $childComments);

            return new JsonResponse($formattedComments, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function getCommentsForPost(string $postId, int $limit = 5): JsonResponse
    {
        try {
            $findPost = $this->postRepository->findOneBy(['id' => $postId]);
            if (!$findPost) {
                return new JsonResponse(['error' => 'Post not found'], 404);
            }

            if (!$postId) {
                return new JsonResponse(['error' => 'Post ID is required'], 400);
            }

            $comments = $this->commentRepository->getMainComments($postId, $limit);

            if (empty($comments)) {
                return new JsonResponse(['message' => 'No comments found'], 200);
            }

            $author = $this->profileRepository->findOneBy(['id' => $findPost->getAuthor()->getId()]);
            $this->profileValidator->validateProfile($author);

            $formattedComments = array_map(fn ($comment) => [
                'id' => (string) $comment->getId(),
                'postId' => (string) $comment->getPostId(),
                'parentId' => null,
                'content' => $comment->getContent(),
                'author' => [
                    'id' => (string) $author->getId(),
                    'firstname' => (string) $author->getFirstName(),
                    'lastname' => (string) $author->getLastName(),
                ],
                'createdAt' => $comment->getCreatedAt(),
                'replyCount' => $this->commentRepository->countChildrenByParentId($comment->getId()),
            ], $comments);

            return new JsonResponse(['comments' => $formattedComments], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function addCommentToPost(string $postId, string $authorId, string $content): JsonResponse
    {
        try {
            if (empty($postId) || empty($authorId) || empty($content)) {
                return new JsonResponse(['error' => 'Missing parameters: postId, authorId, and content are required'], 400);
            }

            $comment = new Comment($postId, $authorId, $content);
            $this->dm->persist($comment);
            $this->dm->flush();

            return new JsonResponse([
                'message' => 'Comment successfully added to post',
                'comment' => [
                    'id' => $comment->getId(),
                    'content' => $comment->getContent(),
                    'postId' => $comment->getPostId(),
                    'parent' => null,
                    'createdAt' => $comment->getCreatedAt(),
                ],
            ], 201);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }

    public function replyToComment(string $postId, string $authorId, string $content, string $parentId): JsonResponse
    {
        try {
            if (empty($postId) || empty($authorId) || empty($content) || empty($parentId)) {
                return new JsonResponse(['error' => 'Missing parameters: postId, authorId, content, and parentId are required'], 400);
            }

            $parentComment = $this->dm->getRepository(Comment::class)->find($parentId);
            if (!$parentComment) {
                return new JsonResponse(['error' => 'Parent comment not found'], 404);
            }

            $reply = new Comment($postId, $authorId, $content, $parentId);
            $this->dm->persist($reply);

            $parentComment->addChild($reply->getId());
            $this->dm->persist($parentComment);

            $this->dm->flush();

            return new JsonResponse([
                'message' => 'Reply successfully added',
                'comment' => [
                    'id' => $reply->getId(),
                    'content' => $reply->getContent(),
                    'postId' => $reply->getPostId(),
                    'parent' => (string) $reply->getParentId(),
                    'createdAt' => $reply->getCreatedAt(),
                ],
            ], 201);
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Internal server error', 'details' => $e->getMessage()], 500);
        }
    }
}
