<?php

namespace App\Repository;

use App\Entity\Follower;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Follower>
 */
class FollowerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Follower::class);
    }

    public function findFollowersWithPagination(int $profileId, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $result = $this->createQueryBuilder('f')
            ->innerJoin('f.follower', 'p')
            ->where('f.following = :profileId')
            ->setParameter('profileId', $profileId)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        try {
            return $result->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }
}
