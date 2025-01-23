<?php

namespace App\Repository;

use App\Entity\Follower;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Follower>
 */
class FollowerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Follower::class);
    }

    public function findFollowersWithPagination(int $profileId, int $limit, int $offset): array
    {
        $followers = $this->createQueryBuilder('f')
            ->select('f.id AS friendshipId,
                  follower.id AS followerId, 
                  follower.firstName AS followerFirstName, 
                  follower.lastName AS followerLastName, 
                  follower.username AS followerUsername')
            ->join('f.follower', 'follower')
            ->where('f.following = :profileId')
            ->setParameter('profileId', $profileId)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getArrayResult();

        $result = [];

        foreach ($followers as $follower) {
            $result[] = [
                'friendshipId' => $follower['friendshipId'],
                'follower' => [
                    'followerId' => $follower['followerId'],
                    'followerFirstname' => $follower['followerFirstName'],
                    'followerLastname' => $follower['followerLastName'],
                    'followerUsername' => $follower['followerUsername'],
                ],
            ];
        }

        return $result;
    }

    public function findFollowedWithPagination(int $profileId, int $limit, int $offset): array
    {
        $followed = $this->createQueryBuilder('f')
            ->select('f.id AS friendshipId,
                  following.id AS followingId, following.firstName AS followingFirstName, following.lastName AS followingLastName, following.username AS followingUsername')
            ->join('f.following', 'following')
            ->where('f.follower = :profileId')
            ->setParameter('profileId', $profileId)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getArrayResult();

        $result = [];

        foreach ($followed as $follow) {
            $result[] = [
                'friendshipId' => $follow['friendshipId'],
                'followingId' => $follow['followingId'],
                'following' => [
                    'followingId' => $follow['followingId'],
                    'followingFirstname' => $follow['followingFirstName'],
                    'followingLastname' => $follow['followingLastName'],
                    'followingUsername' => $follow['followingUsername'],
                ],
            ];
        }

        return $result;
    }

    public function countFollowers(int $profileId): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.following = :profileId')
            ->setParameter('profileId', $profileId);

        try {
            return (int) $result->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        }
    }
}
