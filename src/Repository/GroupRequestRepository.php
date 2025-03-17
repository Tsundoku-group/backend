<?php

namespace App\Repository;

use App\Entity\GroupRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class GroupRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupRequest::class);
    }

    public function findPendingRequestsByProfile(string $profileId)
    {
        try {
            $pendingRequestByProfile = $this->createQueryBuilder('gr')
                ->innerJoin('gr.profile', 'p')
                ->andWhere('p.id = :profileId')
                ->andWhere('gr.status = :pendingStatus')
                ->setParameter('profileId', $profileId)
                ->setParameter('pendingStatus', 'pending');

            return $pendingRequestByProfile->getQuery()->getResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}