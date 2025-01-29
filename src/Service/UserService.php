<?php

namespace App\Service;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    public function scheduleAccountDeletion(int $id): ?DateTime
    {
        $deletionDate = new DateTime('+30 days');

        try {
            $updatedRows = $this->entityManager->createQueryBuilder()
                ->update('App\Entity\User', 'u')
                ->set('u.accountDeletionDate', ':deletionDate')
                ->where('u.id = :id')
                ->setParameter('deletionDate', $deletionDate)
                ->setParameter('id', $id)
                ->getQuery()
                ->execute();

            if (0 === $updatedRows) {
                return null;
            }

            return $deletionDate;
        } catch (Exception $e) {
            return null;
        }
    }
}
