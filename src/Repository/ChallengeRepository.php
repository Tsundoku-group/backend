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

    public function findActiveChallengesByProfile(int $profileId): array
    {
        return $this->findChallengesByProfileAndStatus($profileId, [
            ChallengeStatusEnum::PENDING->value,
            ChallengeStatusEnum::ONGOING->value,
        ]);
    }

    public function findInactiveChallengesByProfile(int $profileId): array
    {
        return $this->findChallengesByProfileAndStatus($profileId, [
            ChallengeStatusEnum::SUCCESS->value,
            ChallengeStatusEnum::FAILED->value,
            ChallengeStatusEnum::CANCELED->value,
        ]);
    }

    public function findChallengesByProfileAndStatus(
        int $profileId,
        array $statuses,
        int $offset = 0,
        int $limit = 0,
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.challengeProfiles', 'cp')
            ->andWhere('cp.profile = :profile')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('profile', $profileId)
            ->setParameter('statuses', $statuses)
            ->orderBy('c.startAt', 'ASC')
            ->setFirstResult($offset);

        if ($limit !== 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findActiveChallengesByProfilePaginated(
        int $profileId,
        int $offset = 0,
        int $limit = 5
    ): array {
        return $this->findChallengesByProfileAndStatus($profileId, [
            ChallengeStatusEnum::PENDING->value,
            ChallengeStatusEnum::ONGOING->value,
        ], $offset, $limit);
    }

    public function findInactiveChallengesByProfilePaginated(
        int $profileId,
        int $offset = 0,
        int $limit = 5
    ): array {
        return $this->findChallengesByProfileAndStatus($profileId, [
            ChallengeStatusEnum::SUCCESS->value,
            ChallengeStatusEnum::FAILED->value,
            ChallengeStatusEnum::CANCELED->value,
        ], $offset, $limit);
    }

    public function countChallengesByProfileAndStatus(int $profileId, array $statuses): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->innerJoin('c.challengeProfiles', 'cp')
            ->andWhere('cp.profile = :profile')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('profile', $profileId)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
