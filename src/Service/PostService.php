<?php

namespace App\Service;

use App\Constant\ErrorMessagesConstant;
use App\DTO\Post\UpdatePostDTO;
use App\Entity\Post;
use App\Entity\Profile;
use App\Repository\GroupProfileRepository;
use App\Repository\GroupRepository;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Security\Voter\Post\PostStatusVoter;
use App\Security\Voter\Post\PostVisibilityVoter;
use App\Validator\Constraints\ProfileValidator;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class PostService
{
    public function __construct(
        private ProfileValidator $profileValidator,
        private ProfileRepository $profileRepository,
        private GroupProfileRepository $groupProfileRepository,
        private GroupRepository $groupRepository,
        private EntityManagerInterface $entityManager,
        private PostRepository $postRepository,
        private SluggerInterface $slugger,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function getRecentPosts(int $limit = 10): array
    {
        $posts = $this->postRepository->findRecentPosts($limit);

        return array_map(fn ($post) => [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'slug' => $post->getSlug(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'visibility' => $post->getVisibility(),
            'author' => [
                'id' => $post->getAuthor()->getId(),
            ],
        ], $posts);
    }

    public function getOlderPosts(int $page, int $limit): array
    {
        $posts = $this->postRepository->findOlderPosts($page, $limit);

        return array_map(fn ($post) => [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => substr($post->getContent(), 0, 300),
            'slug' => $post->getSlug(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'visibility' => $post->getVisibility(),
            'author' => [
                'id' => $post->getAuthor()->getId(),
            ],
        ], $posts);
    }

    public function createPost(int $authorId, int $groupId, string $visibility, string $title, string $content): Post
    {
        $author = $this->profileRepository->find($authorId);
        if (!$author) {
            throw new RuntimeException(ErrorMessagesConstant::PROFILE_NOT_FOUND);
        }
        $this->profileValidator->validateProfile($authorId);

        $group = $this->groupRepository->find($groupId);
        if (!$group) {
            throw new RuntimeException(ErrorMessagesConstant::GROUP_NOT_FOUND);
        }

        if (!$this->groupProfileRepository->findOneBy(['group' => $group, 'profile' => $author])) {
            throw new RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
        }

        if ('public' === $visibility && 'public' !== $group->getVisibility()) {
            throw new RuntimeException(ErrorMessagesConstant::CANNOT_POST_PUBLIC_IN_PRIVATE_GROUP);
        }

        if (!$this->authorizationChecker->isGranted('post_content', $group)) {
            throw new RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
        }

        $this->entityManager->beginTransaction();
        try {
            $post = new Post();
            $post->setTitle($title);
            $post->setContent($content);
            $post->setVisibility($visibility);
            $post->setSlug($this->slugger->slug($title)->lower());
            $post->setCreatedAt(new DateTimeImmutable());
            $post->setGroup($group);
            $post->setAuthor($author);

            $this->entityManager->persist($post);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $post;
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw new RuntimeException(ErrorMessagesConstant::INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePost(Post $post, UpdatePostDTO $dto, Profile $editor): void
    {
        if ($post->getAuthor()->getId() !== $editor->getId()) {
            throw new RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
        }

        if (!$this->authorizationChecker->isGranted(PostStatusVoter::EDIT_POST, $post)) {
            throw new RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
        }

        $changesMade = false;

        if (!empty($dto->title) && $dto->title !== $post->getTitle()) {
            $post->setTitle($dto->title);
            $post->setSlug($this->slugger->slug($dto->title)->lower());
            $changesMade = true;
        }

        if (!empty($dto->content) && $dto->content !== $post->getContent()) {
            $post->setContent($dto->content);
            $changesMade = true;
        }

        if (!empty($dto->visibility) && $dto->visibility !== $post->getVisibility()) {
            if (!$this->authorizationChecker->isGranted(PostVisibilityVoter::CHANGE_VISIBILITY, $post)) {
                throw new RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
            }
            $post->setVisibility($dto->visibility);
            $changesMade = true;
        }

        if (!$changesMade) {
            return;
        }

        $post->setUpdatedAt(new DateTime());

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new RuntimeException(ErrorMessagesConstant::INTERNAL_SERVER_ERROR);
        }
    }

    public function deletePost(int $postId, Profile $editor): void
    {
        $post = $this->postRepository->findPostWithGroupById($postId);
        if (!$post) {
            throw new RuntimeException(ErrorMessagesConstant::POST_NOT_FOUND);
        }

        if ($post->getAuthor()->getId() !== $editor->getId()) {
            throw new RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
        }

        if (!$this->authorizationChecker->isGranted('delete_post', $post)) {
            throw new RuntimeException(ErrorMessagesConstant::ACCESS_DENIED);
        }

        try {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new RuntimeException(ErrorMessagesConstant::INTERNAL_SERVER_ERROR . $e->getMessage());
        }
    }
}
