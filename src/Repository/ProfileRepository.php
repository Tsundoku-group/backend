<?php

namespace App\Repository;

use App\Entity\Profile;
use DateTimeInterface;
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
        $result = $this->createQueryBuilder('p')
            ->select('p.id, p.username, p.activeProfile')
            ->where('p.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC');

        try {
            return $result->getQuery()->getArrayResult();
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }

    public function findProfileById(int $profileId): ?array
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.id = :profileId')
            ->setParameter('profileId', $profileId);

        try {
            $profile = $result->getQuery()->getOneOrNullResult();

            if (!$profile) {
                return null;
            }

            return [
                'id' => $profile->getId(),
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'username' => $profile->getUsername(),
                'birthday' => $profile->getBirthday()?->format('Y-m-d'),
                'gender' => $profile->getGender(),
                'phoneNumber' => $profile->getPhoneNumber(),
                'bio' => $profile->getBio(),
                'facebook' => $profile->getFacebook(),
                'instagram' => $profile->getInstagram(),
                'x' => $profile->getX(),
                'type' => $profile->getType(),
                'createdAt' => $profile->getCreatedAt()?->format(DateTimeInterface::ATOM),
            ];
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findProfileByIdAndUserId(int $profileId, int $userId): ?Profile
    {
        $result = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.id = :profileId')
            ->andWhere('u.id = :userId')
            ->setParameter('profileId', $profileId)
            ->setParameter('userId', $userId);
        try {
            return $result->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findProfileWithPhotos(int $profileId, int $userId): ?Profile
    {
        $result = $this->createQueryBuilder('p')
            ->leftJoin('p.profilePhotos', 'pp')
            ->addSelect('pp')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('p.id = :profileId')
            ->andWhere('u.id = :userId')
            ->setParameter('profileId', $profileId)
            ->setParameter('userId', $userId);

        try {
            return $result->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
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

    public function findProfileByUsername(string $username): ?array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id, p.username, p.firstName, p.lastName, p.bio, p.birthday, p.phoneNumber, p.type')
            ->where('p.username = :username')
            ->setParameter('username', $username);

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
