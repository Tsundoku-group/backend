<?php

namespace App\Controller;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Post\CreatePostDTO;
use App\DTO\Post\DeletePostDTO;
use App\DTO\Post\UpdatePostDTO;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Service\PostService;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/v1/post')]
class PostController extends AbstractController
{

    public function __construct(
        private readonly PostService $postService,
        private readonly PostRepository $postRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

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

    #[Route('/{profileId}/articles', methods: ['GET'])]
    public function getArticlesByProfile(int $profileId): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['id' => $profileId]);

        if (!$profile) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $articles = $this->postRepository->findArticlesByProfile($profile->getId());

            return new JsonResponse([
                'articles' => $articles,
            ], 200);
        } catch (Exception $e) {
            var_dump($e->getMessage());
            return new JsonResponse(['error' => ErrorMessagesConstant::UNAUTHORIZED_ACCESS], 401);
        }
    }

    #[Route('', methods: ['POST'])]
    public function createPost(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        var_dump($data);
        $dto = new CreatePostDTO(
            $data['type'] ?? 'post',
            $data['title'] ?? '',
            $data['content'] ?? '',
            $data['authorId'] ?? 1,
            $data['groupId'] ?? 1,
            $data['status'] ?? 'brouillon',
            $data['visibility'] ?? 'private'
        );

        if (!isset(
            $dto->authorId,
            $dto->groupId,
            $dto->title,
            $dto->content,
            $dto->type,
            $dto->status,
            $dto->visibility
        )) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }

        try {
            $post = $this->postService->createPost(
                $dto->authorId,
                $dto->groupId,
                $dto->title,
                $dto->content,
                $dto->type,
                $dto->status,
                $dto->visibility
            );

            return new JsonResponse(['message' => 'Post créé avec succès.', 'postId' => $post->getId()], 201);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{postId}', methods: ['PUT'])]
    public function updatePost(int $postId, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => ErrorMessagesConstant::POST_NOT_FOUND], 404);
        }

        $data = json_decode($request->getContent(), true);

        $editorId = $data['authorId'] ?? $data['editorId'] ?? null;
        if (!$editorId) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INVALID_DATA], 400);
        }
        $editor = $this->profileRepository->find($editorId);
        if (!$editor) {
            return new JsonResponse(['error' => ErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            // CustomSelect update status
            if (
                isset($data['status']) &&
                !isset($data['title']) &&
                !isset($data['content']) &&
                !isset($data['visibility'])
            ) {
                $post->setStatus($data['status']);
                $entityManager->persist($post);
                $entityManager->flush();
                return new JsonResponse(['message' => 'Statut mis à jour avec succès.'], 200);
            } else {
                $title = array_key_exists('title', $data) ? $data['title'] : $post->getTitle();
                $content = array_key_exists('content', $data) ? $data['content'] : $post->getContent();
                $status = array_key_exists('status', $data) ? $data['status'] : $post->getStatus();
                $visibility = array_key_exists('visibility', $data) ? $data['visibility'] : $post->getVisibility();

                $dto = new UpdatePostDTO($title, $content, $status, $visibility);
                $this->postService->updatePost($post, $dto, $editor);
                return new JsonResponse(['message' => 'Post mis à jour avec succès.']);
            }
        } catch (\Exception $e) {
            error_log("Error updating post: " . $e->getMessage());
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

    #[Route('/{postId}', methods: ['GET'])]
    public function getPost(int $postId): JsonResponse
    {
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => ErrorMessagesConstant::POST_NOT_FOUND], 404);
        }

        try {
            $postData = $this->postService->formatPost($post);
            return new JsonResponse($postData, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => ErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
