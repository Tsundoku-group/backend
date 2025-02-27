<?php

namespace App\Repository;

use App\Entity\Notification;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findOlderThan(DateTimeImmutable $dateLimit): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.createdAt < :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->getQuery()
            ->getResult();
    }
}
