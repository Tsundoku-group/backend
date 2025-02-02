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