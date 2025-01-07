<?php

namespace App\Repository;

use App\Entity\ProfilePhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class ProfilePhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfilePhoto::class);
    }

    public function findActiveProfilePhotosByProfileId(int $profileId): array
    {
        $result = $this->createQueryBuilder('pp')
            ->innerJoin('pp.profile', 'p')
            ->where('p.id = :profileId')
            ->andWhere('pp.isActive = true')
            ->setParameter('profileId', $profileId)
            ->select('pp.id, pp.url, pp.type, pp.isActive');

        try {
            return $result->getQuery()->getArrayResult();
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }

    public function findPhotoByUrlAndType(string $url, string $type): ?ProfilePhoto
    {
        $result = $this->createQueryBuilder('p')
            ->where('p.url = :url')
            ->andWhere('p.type = :type')
            ->setParameter('url', $url)
            ->setParameter('type', $type)
            ->setMaxResults(1);

        try {
            return $result->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}