<?php

namespace App\Repository;

use App\Entity\Notification;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function fetchNotificationsFromDatabase(string $receiverId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $notificationsFromDB = $this->createQueryBuilder('n')
            ->where('n.receiver = :receiverId')
            ->andWhere('n.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('receiverId', $receiverId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('n.createdAt', 'DESC');

        try {
            return $notificationsFromDB->getQuery()->getResult();
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }
}
