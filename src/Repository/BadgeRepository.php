<?php

namespace App\Repository;

use App\Entity\Badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Badge>
 */
class BadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Badge::class);
    }

    public function findBadgesByChallengeProfile(int $profileId): array
    {
        return $this->createQueryBuilder('b')
            ->select('b.id', 'b.awardedAt', 'c.name as challengeName', 'c.type as challengeType')
            ->join('b.challengeProfile', 'cp')
            ->join('cp.challenge', 'c')
            ->join('cp.profile', 'p')
            ->where('p.id = :profileId')
            ->setParameter('profileId', $profileId)
            ->getQuery()
            ->getResult();
    }
}
