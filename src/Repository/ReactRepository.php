<?php

namespace App\Repository;

use App\Entity\React;
use App\Enum\ResourceTypeEnum;
use App\Service\Redis\RedisReactService;
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
}
