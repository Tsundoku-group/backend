<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\Document\Comment;
use App\Enum\NotificationTypeEnum;
use App\Enum\ResourceTypeEnum;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Repository\ReactRepository;
use App\Service\Redis\RedisNotificationService;
use App\Validator\Constraints\ProfileValidator;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private ProfileValidator $profileValidator,
        private PostRepository $postRepository,
        private DocumentManager $dm,
        private ProfileRepository $profileRepository,
        private RedisNotificationService $redisNotificationService,
        private ReactRepository $reactRepository,
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
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    public function getCommentChildren(string $commentId, string $profileId): JsonResponse
    {
        try {
            $childComments = $this->commentRepository->findChildrenByParentId($commentId);

            if (empty($childComments)) {
                return new JsonResponse([], 200);
            }

            $author = $this->profileRepository->findProfileById($childComments[0]->getAuthorId());
            if (!$author) {
                return new JsonResponse([], 200);
            }

            $formattedComments = array_map(fn ($comment) => [
                '_id' => (string) $comment->getId(),
                'postId' => (string) $comment->getPostId(),
                'parentId' => (string) $comment->getParentId(),
                'content' => $comment->getContent(),
                'authorId' => $comment->getAuthorId(),
                'authorFirstName' => $author['firstName'],
                'authorLastName' => $author['lastName'],
                'createdAt' => $comment->getCreatedAt(),
                'children' => [],
                'hasLiked' => $this->reactRepository->hasUserLikedComment($profileId, (string) $comment->getId()),
            ], $childComments);

            return new JsonResponse($formattedComments, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    public function getCommentsForPost(string $postId, string $profileId, int $limit = 5): JsonResponse
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

            $author = $this->profileValidator->validateProfile($findPost->getAuthor()->getId());

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
                'hasLiked' => $this->reactRepository->hasUserLikedComment($profileId, $comment->getId()),
            ], $comments);

            return new JsonResponse(['comments' => $formattedComments], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    public function addCommentToPost(string $postId, string $authorId, string $content): JsonResponse
    {
        try {
            $post = $this->postRepository->find($postId);
            if (!$post) {
                return new JsonResponse(['error' => 'Post not found'], 404);
            }

            $author = $this->profileRepository->find($authorId);
            if (!$author) {
                return new JsonResponse(['error' => 'Author not found'], 404);
            }

            $receiver = $post->getAuthor();

            $comment = new Comment($postId, $authorId, $content);
            $this->dm->persist($comment);
            $this->dm->flush();

            $this->redisNotificationService->addNotificationToCache(
                receiverId: $receiver->getId(),
                actorId: $author->getId(),
                notificationTypeEnum: NotificationTypeEnum::COMMENT->value,
                resourceId: $postId,
                resourceTypeEnum: ResourceTypeEnum::POST->value,
            );

            return new JsonResponse([
                'message' => 'Comment successfully added to post',
                'comment' => [
                    'id' => $comment->getId(),
                    'content' => $comment->getContent(),
                    'postId' => $comment->getPostId(),
                    'parent' => null,
                    'createdAt' => $comment->getCreatedAt(),
                    'author' => [
                        'id' => $author->getId(),
                        'firstname' => $author->getFirstName(),
                        'lastname' => $author->getLastName(),
                        'username' => $author->getUsername(),
                    ],
                ],
            ], 201);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }

    public function editComment(string $commentId, string $authorId, string $content): JsonResponse
    {
        $comment = $this->commentRepository->findCommentById($commentId);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], 404);
        }

        if ($comment->getAuthorId() !== $authorId) {
            return new JsonResponse(['error' => 'Author id does not match'], 400);
        }
        try {
            $comment->setContent($content);
            $comment->setUpdatedAt(new DateTime());

            $this->dm->flush();

            return new JsonResponse([
                'message' => 'Commentaire mis à jour avec succès',
                'comment' => [
                    'id' => $comment->getId(),
                    'content' => $comment->getContent(),
                    'updatedAt' => $comment->getUpdatedAt()->format('Y-m-d H:i:s'),
                ],
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], JsonResponse::HTTP_FORBIDDEN);
        }
    }

    public function deleteComment(Comment $comment): JsonResponse
    {
        $this->dm->remove($comment);
        $this->dm->flush();

        return new JsonResponse(['message' => 'Comment deleted successfully'], 200);
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
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR, 'details' => $e->getMessage()], 500);
        }
    }
}
