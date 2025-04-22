<?php

namespace App\Service;

use App\Entity\Group;
use App\Entity\Taggable;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class TagService
{
    public function __construct(
        private TagRepository          $tagRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getAllTags(): array
    {
        $tags = $this->tagRepository->findAllTags();

        return array_map(fn ($tag) => [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'slug' => $tag->getSlug(),
            'parent' => $tag->getParentTag() ? [
                'id' => $tag->getParentTag()->getId(),
                'name' => $tag->getParentTag()->getName(),
                'slug' => $tag->getParentTag()->getSlug(),
            ] : null,
        ], $tags);
    }

    public function addTagToEntity(string $entityType, int $entityId, array $tagNames): void
    {
        if (empty($tagNames)) {
            return;
        }

        $entity = null;
        if ('group' === $entityType) {
            $entity = $this->entityManager->getRepository(Group::class)->find($entityId);
        }

        if (!$entity) {
            throw new Exception("L'entité de type '$entityType' avec l'ID $entityId n'existe pas.");
        }

        $tags = $this->tagRepository->findTagsByNames($tagNames);

        if (empty($tags)) {
            return;
        }

        foreach ($tags as $tag) {
            $existingTaggable = $this->entityManager->getRepository(Taggable::class)->findOneBy([
                'tag' => $tag,
                'taggableType' => $entityType,
                'taggableId' => $entityId,
            ]);

            if (!$existingTaggable) {
                $taggable = new Taggable($tag, $entityType, $entity->getId(), $entity);
                $this->entityManager->persist($taggable);
            }
        }

        $this->entityManager->flush();
    }

    public function removeTagsFromEntity(string $entityType, int $entityId, array $tagNames): void
    {
        if (empty($tagNames)) {
            return;
        }

        $entity = match ($entityType) {
            'group' => $this->entityManager->getRepository(Group::class)->find($entityId),
            default => null,
        };

        if (!$entity) {
            throw new Exception("L'entité $entityType avec l'ID $entityId n'existe pas.");
        }

        $tags = $this->tagRepository->findTagsByNames($tagNames);
        if (empty($tags)) {
            throw new Exception("Aucun des tags spécifiés n'existe.");
        }

        foreach ($tags as $tag) {
            $taggable = $this->entityManager->getRepository(Taggable::class)->findOneBy([
                'tag' => $tag,
                'taggableType' => $entityType,
                'taggableId' => $entityId,
            ]);

            if ($taggable) {
                $this->entityManager->remove($taggable);
            }
        }

        $this->entityManager->flush();
    }
}
