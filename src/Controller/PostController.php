<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Post\CreatePostDTO;
use App\DTO\Post\DeletePostDTO;
use App\DTO\Post\UpdatePostDTO;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Service\PostService;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/post')]
class PostController extends AbstractController
{
    public function __construct(
        private readonly PostService $postService,
        private readonly PostRepository $postRepository,
        private readonly ProfileRepository $profileRepository,
    ) {
    }

    #[Route('/{profileId}/recent', methods: ['GET'])]
    public function getRecentPosts(string $profileId): JsonResponse
    {
        try {
            $posts = $this->postService->getRecentPosts(10, $profileId);

            return new JsonResponse(['posts' => $posts], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{profileId}/older', methods: ['GET'])]
    public function getOlderPosts(string $profileId, Request $request): JsonResponse
    {
        $page = max((int) $request->query->get('page', 1), 1);
        $limit = max((int) $request->query->get('limit', 10), 10);

        try {
            $posts = $this->postService->getOlderPosts($page, $limit, $profileId);
            $totalPosts = $this->postRepository->countTotalPosts();
            $remainingPosts = $totalPosts - ($page * $limit);
            $nextPage = $remainingPosts > 0 ? $page + 1 : null;

            return new JsonResponse([
                'posts' => $posts,
                'nextPage' => $nextPage,
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/articles', methods: ['GET'])]
    public function getArticlesByUser(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => ErrorMessagesConstant::USER_NOT_FOUND], 401);
        }

        try {
            $articles = $this->postRepository->findArticlesByUser($user->getId());

            return new JsonResponse([
                'articles' => $articles,
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::UNAUTHORIZED_ACCESS], 401);
        }
    }

    #[Route('', methods: ['POST'])]
    public function createPost(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreatePostDTO(
            $data['title'] ?? '',
            $data['content'] ?? '',
            $data['authorId'] ?? 0,
            $data['groupId'] ?? 0,
            $data['visibility'] ?? 'private'
        );

        if (!isset($dto->authorId, $dto->groupId, $dto->visibility, $dto->title, $dto->content)) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $post = $this->postService->createPost($dto->authorId, $dto->groupId, $dto->visibility, $dto->title, $dto->content);

            return new JsonResponse(['message' => 'Post créé avec succès', 'postId' => $post->getId()], 201);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{postId}', methods: ['PUT'])]
    public function updatePost(int $postId, Request $request): JsonResponse
    {
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => ErrorMessagesConstant::POST_NOT_FOUND], 404);
        }

        $data = json_decode($request->getContent(), true);

        $dto = new UpdatePostDTO(
            $data['title'] ?? '',
            $data['content'] ?? '',
            $data['visibility'] ?? 'private'
        );

        $author = $this->profileRepository->find($data['authorId']);
        if (!$author) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $this->postService->updatePost($post, $dto, $author);

            return new JsonResponse(['message' => 'Post mis à jour avec succès']);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{postId}', methods: ['DELETE'])]
    public function deletePost(int $postId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['editorId'])) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        $dto = new DeletePostDTO($data['editorId'], $postId);

        $editor = $this->profileRepository->find($dto->editorId);
        if (!$editor) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $this->postService->deletePost($dto->postId, $editor);

            return new JsonResponse(['message' => 'Post supprimé avec succès.']);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
