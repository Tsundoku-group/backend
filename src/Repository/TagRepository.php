<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findAllTags(): array
    {
        $allTags = $this->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC');
        try {
            return $allTags->getQuery()->getResult();
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }

    public function findTagsByNames(array $tagNames): array
    {
        $findTags = $this->createQueryBuilder('t')
            ->where('LOWER(t.name) IN (:names)')
            ->setParameter('names', array_map('strtolower', $tagNames));

        try {
            return $findTags->getQuery()->getResult();
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }
}
