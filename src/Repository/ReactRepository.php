<?php

namespace App\Repository;

use App\Entity\React;
use App\Enum\ResourceTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, React::class);
    }

    public function hasUserLikedPost(int $profileId, int $postId): bool
    {
        return $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.actor = :profileId')
                ->andWhere('r.resourceId = :postId')
                ->andWhere('r.resourceType = :resourceType')
                ->setParameter('profileId', $profileId)
                ->setParameter('postId', $postId)
                ->setParameter('resourceType', ResourceTypeEnum::POST)
                ->getQuery()
                ->getSingleScalarResult() > 0;
    }

    public function hasUserLikedComment(int $profileId, string $commentId): bool
    {
        return (bool) $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.actor = :profileId')
                ->andWhere('r.resourceId = :commentId')
                ->andWhere('r.resourceType = :resourceType')
                ->setParameter('profileId', $profileId)
                ->setParameter('commentId', $commentId)
                ->setParameter('resourceType', ResourceTypeEnum::COMMENT)
                ->getQuery()
                ->getSingleScalarResult() > 0;
    }
}
