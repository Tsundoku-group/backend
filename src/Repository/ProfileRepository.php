<?php

namespace App\Repository;

use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Profile>
 */
class ProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profile::class);
    }

    public function findUserProfiles(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id, p.username, p.activeProfile')
            ->where('p.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt')
            ->getQuery()
            ->getArrayResult();
    }

    public function findProfileById(int $profileId): ?Profile
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.id = :profileId')
            ->setParameter('profileId', $profileId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
