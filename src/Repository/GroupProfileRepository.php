<?php

namespace App\Repository;

use App\Entity\GroupProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupProfile>
 */
class GroupProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupProfile::class);
    }

    public function findOneGroupProfile(int $groupId, int $profileId): ?GroupProfile
    {
        $groupProfile = $this->createQueryBuilder('gp')
            ->where('gp.group = :groupId')
            ->andWhere('gp.profile = :profileId')
            ->setParameter('groupId', $groupId)
            ->setParameter('profileId', $profileId);

        try {
            return $groupProfile->getQuery()->getOneOrNullResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function isProfileBanned(int $groupId, int $profileId): bool
    {
        $isProfileBanned = $this->createQueryBuilder('gp')
            ->select('gp.isBanned')
            ->where('gp.group = :groupId')
            ->andWhere('gp.profile = :profileId')
            ->setParameter('groupId', $groupId)
            ->setParameter('profileId', $profileId);

        try {
            return null !== (bool) $isProfileBanned->getQuery()->getOneOrNullResult();
        } catch (NoResultException $e) {
            return false;
        }
    }
}
