<?php

namespace App\Repository;

use App\Entity\Challenge;
use App\Enum\ChallengeStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Challenge>
 */
class ChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Challenge::class);
    }

    /**
     * @return Challenge[] Returns an array of Challenge objects
     */
    public function findActiveChallengesByProfile(int $profileId) : array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.challengeProfiles', 'cp')
            ->andWhere('cp.profile = :profile')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('profile', $profileId)
            ->setParameter('statuses', [
                ChallengeStatusEnum::PENDING->value,
                ChallengeStatusEnum::ONGOING->value,
            ])
            ->orderBy('c.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Challenge[] Returns an array of Challenge objects
     */
    public function findInactiveChallengesByProfile(int $profileId) : array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.challengeProfiles', 'cp')
            ->andWhere('cp.profile = :profile')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('profile', $profileId)
            ->setParameter('statuses', [
                ChallengeStatusEnum::SUCCESS->value,
                ChallengeStatusEnum::FAILED->value,
                ChallengeStatusEnum::CANCELED->value,
            ])
            ->orderBy('c.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
