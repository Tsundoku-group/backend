<?php

namespace App\Controller;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\PostErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\DTO\Post\CreatePostDTO;
use App\DTO\Post\DeletePostDTO;
use App\DTO\Post\UpdatePostDTO;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Service\PostService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/post')]
class PostController extends AbstractController
{
    public function __construct(
        private readonly PostService $postService,
        private readonly PostRepository $postRepository,
        private readonly ProfileRepository $profileRepository,
    ) {
    }

    #[Route('/{profileId}/articles', methods: ['GET'])]
    public function getArticlesByProfile(int $profileId, Request $request): JsonResponse
    {
        $profile = $this->profileRepository->findOneBy(['id' => $profileId]);

        if (!$profile) {
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        $page = (int) $request->query->get('page', 1);
        $limit = 15;
        $sortField = $request->query->get('sortField', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'desc');

        try {
            $result = $this->postRepository->findPaginatedArticlesByProfile(
                $profile->getId(),
                $page,
                $limit,
                $sortField,
                $sortOrder
            );

            $formattedArticles = array_map(function ($article) {
                return $this->postService->formatPost($article);
            }, $result['articles']);

            $result['articles'] = $formattedArticles;

            return new JsonResponse($result, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS], 401);
        }
    }

    #[Route('', methods: ['POST'])]
    public function createPost(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new CreatePostDTO(
            $data['type'] ?? 'post',
            $data['title'] ?? '',
            $data['content'] ?? '',
            $data['authorId'] ?? 1,
            $data['groupId'] ?? 1,
            $data['status'] ?? 'brouillon',
            $data['visibility'] ?? 'private'
        );

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['error' => implode(', ', $errorMessages)], 400);
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

            return new JsonResponse([
                'message' => 'Post créé avec succès.',
                'postId' => $post->getId(),
            ], 201);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{postId}', methods: ['PUT'])]
    public function updatePost(
        int $postId,
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => PostErrorMessagesConstant::POST_NOT_FOUND], 404);
        }

        $data = json_decode($request->getContent(), true);

        $editorId = $data['authorId'] ?? $data['editorId'] ?? null;
        if (!$editorId) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], 400);
        }

        $editor = $this->profileRepository->find($editorId);
        if (!$editor) {
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            if (
                isset($data['status'])
                && !isset($data['title'])
                && !isset($data['content'])
                && !isset($data['visibility'])
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

                $errors = $validator->validate($dto);
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }

                    return new JsonResponse(['error' => implode(', ', $errorMessages)], 400);
                }

                $this->postService->updatePost($post, $dto, $editor);

                return new JsonResponse(['message' => 'Post mis à jour avec succès.']);
            }
        } catch (Exception $e) {
            error_log('Error updating post: ' . $e->getMessage());

            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{postId}', methods: ['DELETE'])]
    public function deletePost(int $postId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['editorId'])) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INVALID_DATA], 400);
        }

        $dto = new DeletePostDTO($data['editorId'], $postId);

        $editor = $this->profileRepository->find($dto->editorId);
        if (!$editor) {
            return new JsonResponse(['error' => ProfileErrorMessagesConstant::PROFILE_NOT_FOUND], 404);
        }

        try {
            $this->postService->deletePost($dto->postId, $editor);

            return new JsonResponse(['message' => 'Post supprimé avec succès.']);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }

    #[Route('/{postId}', methods: ['GET'])]
    public function getPost(int $postId): JsonResponse
    {
        $post = $this->postRepository->find($postId);
        if (!$post) {
            return new JsonResponse(['error' => PostErrorMessagesConstant::POST_NOT_FOUND], 404);
        }

        try {
            $postData = $this->postService->formatPost($post);

            return new JsonResponse($postData, 200);
        } catch (Exception $e) {
            return new JsonResponse(['error' => GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR], 500);
        }
    }
}
