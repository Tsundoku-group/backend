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