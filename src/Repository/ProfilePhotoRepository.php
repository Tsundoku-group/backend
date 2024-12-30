<?php

namespace App\Repository;

use App\Entity\ProfilePhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProfilePhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfilePhoto::class);
    }

    public function findActiveProfilePhotosByProfileId(int $profileId): array
    {
        return $this->createQueryBuilder('pp')
            ->innerJoin('pp.profile', 'p')
            ->where('p.id = :profileId')
            ->andWhere('pp.isActive = true')
            ->setParameter('profileId', $profileId)
            ->select('pp.id, pp.url, pp.type, pp.isActive')
            ->getQuery()
            ->getArrayResult();
    }
}