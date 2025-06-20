<?php

namespace App\Service;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\GroupErrorMessagesConstant;
use App\Constant\PostErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\DTO\Post\UpdatePostDTO;
use App\Entity\Post;
use App\Entity\Profile;
use App\Enum\PostTypeEnum;
use App\Repository\CommentRepository;
use App\Repository\GroupRepository;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Repository\ReactRepository;
use App\Security\Voter\Post\PostStatusVoter;
use App\Security\Voter\Post\PostVisibilityVoter;
use App\Validator\Constraints\ProfileValidator;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
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
        private CommentRepository $commentRepository,
        private GroupRepository $groupRepository,
        private EntityManagerInterface $entityManager,
        private PostRepository $postRepository,
        private SluggerInterface $slugger,
        private AuthorizationCheckerInterface $authorizationChecker,
        private ReactRepository $reactRepository,
    ) {
    }

    public function getRecentPosts(PostTypeEnum $type, int $limit, int $profileId, ?int $groupId = null): array
    {
        $posts = $this->postRepository->findRecentPosts($type, $limit, $groupId);

        return array_map(fn ($post) => [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'slug' => $post->getSlug(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'visibility' => $post->getVisibility(),
            'author' => [
                'id' => $post->getAuthor()->getId(),
                'lastname' => $post->getAuthor()->getLastname(),
                'firstname' => $post->getAuthor()->getFirstname(),
                'username' => $post->getAuthor()->getUsername(),
            ],
            'commentsCount' => $this->commentRepository->countCommentsForPost($post->getId()),
            'hasLiked' => $this->reactRepository->hasUserLikedPost($profileId, $post->getId()),
            'likesCount' => $this->reactRepository->countLikesForPost($post->getId()),
        ], $posts);
    }

    public function getOlderPosts(int $page, int $limit, int $profileId, int $groupId, PostTypeEnum $type): array
    {
        $posts = $this->postRepository->findOlderPosts($page, $limit, $groupId, $type);

        return array_map(fn ($post) => [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => substr($post->getContent(), 0, 300),
            'slug' => $post->getSlug(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'visibility' => $post->getVisibility(),
            'author' => [
                'id' => $post->getAuthor()->getId(),
                'lastname' => $post->getAuthor()->getLastname(),
                'firstname' => $post->getAuthor()->getFirstname(),
                'username' => $post->getAuthor()->getUsername(),
            ],
            'commentsCount' => $this->commentRepository->countCommentsForPost($post->getId()),
            'hasLiked' => $this->reactRepository->hasUserLikedPost($profileId, $post->getId()),
            'likesCount' => $this->reactRepository->countLikesForPost($post->getId()),
        ], $posts);
    }

    public function createPost(
        int $authorId,
        int $groupId,
        string $title,
        string $content,
        PostTypeEnum $type,
        string $status,
        string $visibility,
    ): Post {
        $author = $this->profileRepository->find($authorId);
        if (!$author) {
            throw new RuntimeException(ProfileErrorMessagesConstant::PROFILE_NOT_FOUND);
        }

        $this->profileValidator->validateProfile($authorId);

        $group = $this->groupRepository->find($groupId);
        if (!$group) {
            throw new RuntimeException(GroupErrorMessagesConstant::GROUP_NOT_FOUND);
        }

        if ('public' === $visibility && 'public' !== $group->getVisibility() && $type === PostTypeEnum::POST) {
            $publicGroup = $this->groupRepository->findOneBy(['name' => 'Fil d’actualité', 'visibility' => 'public']);
            if (!$publicGroup || $group->getId() !== $publicGroup->getId()) {
                throw new RuntimeException(PostErrorMessagesConstant::CANNOT_POST_PUBLIC_IN_PRIVATE_GROUP);
            }
        }

        if (!$this->authorizationChecker->isGranted('post_content', $group)) {
            throw new RuntimeException(SecurityErrorMessagesConstant::ACCESS_DENIED);
        }

        $this->entityManager->beginTransaction();
        try {
            $post = new Post();
            $post->setTitle($title);
            $post->setContent($content);
            $post->setType($type);
            $post->setStatus($status);
            $post->setVisibility($visibility);
            $post->setSlug($this->slugger->slug($title)->lower());
            $post->setCreatedAt(new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')));
            $post->setUpdatedAt(new DateTime('now', new DateTimeZone('Europe/Paris')));
            $post->setGroup($group);
            $post->setAuthor($author);

            $this->entityManager->persist($post);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $post;
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw new RuntimeException(GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePost(Post $post, UpdatePostDTO $dto, Profile $editor): void
    {
        if ($post->getAuthor()->getId() !== $editor->getId()) {
            throw new RuntimeException(SecurityErrorMessagesConstant::ACCESS_DENIED);
        }

        if (!$this->authorizationChecker->isGranted(PostStatusVoter::EDIT_POST, $post)) {
            throw new RuntimeException(SecurityErrorMessagesConstant::ACCESS_DENIED);
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
                throw new RuntimeException(SecurityErrorMessagesConstant::ACCESS_DENIED);
            }
            $post->setVisibility($dto->visibility);
            $changesMade = true;
        }

        if (!$changesMade) {
            return;
        }

        $post->setUpdatedAt(new DateTime('now', new DateTimeZone('Europe/Paris')));

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new RuntimeException(GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR);
        }
    }

    public function deletePost(int $postId, Profile $editor): void
    {
        $post = $this->postRepository->findPostWithGroupById($postId);
        if (!$post) {
            throw new RuntimeException(PostErrorMessagesConstant::POST_NOT_FOUND);
        }

        if ($post->getAuthor()->getId() !== $editor->getId()) {
            throw new RuntimeException(SecurityErrorMessagesConstant::ACCESS_DENIED);
        }

        if (!$this->authorizationChecker->isGranted('delete_post', $post)) {
            throw new RuntimeException(SecurityErrorMessagesConstant::ACCESS_DENIED);
        }

        try {
            $this->entityManager->remove($post);
            $this->entityManager->flush();
        } catch (Exception $e) {
            throw new RuntimeException(GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR . $e->getMessage());
        }
    }

    public function formatPost(Post $post): array
    {
        return [
            'id' => $post->getId(),
            'type' => $post->getType(),
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'slug' => $post->getSlug(),
            'visibility' => $post->getVisibility(),
            'status' => $post->getStatus(),
            'createdAt' => $post->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $post->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
