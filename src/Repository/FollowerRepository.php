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
                      follower.id AS followerId, follower.firstName AS followerFirstName, follower.lastName AS followerLastName, follower.username AS followerUsername,
                      following.id AS followingId, following.firstName AS followingFirstName, following.lastName AS followingLastName, following.username AS followingUsername')
            ->join('f.follower', 'follower')
            ->join('f.following', 'following')
            ->where('f.following = :profileId OR f.follower = :profileId')
            ->setParameter('profileId', $profileId)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getArrayResult();

        try {
            $result = [];

            foreach ($followers as $follower) {
                $isFollower = ($follower['followerId'] === $profileId);

                $result[] = [
                    'friendshipId' => $follower['friendshipId'],
                    'followerId' => $follower['followerId'],
                    'followingId' => $follower['followingId'],
                    'following' => [
                        'followingId' => $isFollower ? $follower['followingId'] : $follower['followerId'],
                        'followingFirstname' => $isFollower ? $follower['followingFirstName'] : $follower['followerFirstName'],
                        'followingLastname' => $isFollower ? $follower['followingLastName'] : $follower['followerLastName'],
                        'followingUsername' => $isFollower ? $follower['followingUsername'] : $follower['followerUsername'],
                    ],
                ];
            }

            return $result;
        } catch (NoResultException $e) {
            return [];
        }
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

        try {
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
        } catch (NoResultException $e) {
            return [];
        }
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
