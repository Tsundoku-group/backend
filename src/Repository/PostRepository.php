<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findRecentPosts(int $limit = 10): array
    {
        $recentPosts = $this->createQueryBuilder('p')
            ->where('p.visibility = :visibility')
            ->setParameter('visibility', 'public')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        try {
            return $recentPosts->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    public function findOlderPosts(int $page, int $limit): array
    {
        $olderPosts = $this->createQueryBuilder('p')
            ->where('p.visibility = :visibility')
            ->setParameter('visibility', 'public')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        try {
            return $olderPosts->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    public function findPostWithGroupById(int $postId): ?Post
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.group', 'g')
            ->addSelect('g')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId);

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NoResultException) {
            return null;
        }
    }
}
