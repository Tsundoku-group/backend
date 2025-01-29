<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findAllUsersByUsername(): array
    {
        $findAllUsers = $this->createQueryBuilder('u')
            ->select('p.username')
            ->join('u.profiles', 'p');

        try {
            return $findAllUsers->getQuery()->getSingleColumnResult();
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }

    public function findOneUserById(int $id): ?array
    {
        $findUser = $this->createQueryBuilder('u')
            ->select('u.id, u.email, u.accountDeletionDate')
            ->where('u.id = :id')
            ->setParameter('id', $id);

        try {
            return $findUser->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }

    public function findUserProfileById(int $id): array
    {
        $findUserProfile = $this->createQueryBuilder('u')
            ->select('u.id, u.email, p.username, p.firstName, p.lastName, p.birthday, p.bio')
            ->join('u.profiles', 'p')
            ->where('u.id = :id')
            ->setParameter('id', $id);

        try {
            return $findUserProfile->getQuery()->getArrayResult();
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }
}

