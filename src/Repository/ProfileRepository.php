<?php

namespace App\Repository;

use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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

    public function findProfileByEmail(string $email): ?Profile
    {
        $result = $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u')
            ->where('u.email = :email')
            ->andWhere('p.activeProfile = :activeProfile')
            ->setParameter('email', $email)
            ->setParameter('activeProfile', true);

        try {
            return $result->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
