<?php

namespace App\Repository;

use App\Entity\Friendship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Friendship>
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    public function findFriendshipUserProfileId(int $profileId, int $limit, int $offset): ?array
    {
        $friendships = $this->createQueryBuilder('f')
            ->select('f.id AS friendshipId, 
                      requester.id AS requesterId, requester.firstName AS requesterFirstName, requester.lastName AS requesterLastName, requester.username AS requesterUsername, 
                      receiver.id AS receiverId, receiver.firstName AS receiverFirstName, receiver.lastName AS receiverLastName, receiver.username AS receiverUsername')
            ->join('f.requester', 'requester')
            ->join('f.receiver', 'receiver')
            ->where('f.requester = :profileId OR f.receiver = :profileId')
            ->andWhere('f.status = :status')
            ->setParameter('profileId', $profileId)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getArrayResult();

        try {
            $result = [];
            foreach ($friendships as $friendship) {
                $isRequester = ($friendship['requesterId'] === $profileId);

                $result[] = [
                    'friendshipId' => $friendship['friendshipId'],
                    'requesterId' => $friendship['requesterId'],
                    'receiverId' => $friendship['receiverId'],
                    'friend' => [
                        'friendId' => $isRequester ? $friendship['receiverId'] : $friendship['requesterId'],
                        'firstname' => $isRequester ? $friendship['receiverFirstName'] : $friendship['requesterFirstName'],
                        'lastname' => $isRequester ? $friendship['receiverLastName'] : $friendship['requesterLastName'],
                        'username' => $isRequester ? $friendship['receiverUsername'] : $friendship['requesterUsername'],
                    ],
                ];
            }

            return $result;
        } catch (NonUniqueResultException $e) {
            return [];
        }
    }

    public function countFriends(int $profileId): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.status = :status')
            ->andWhere('f.requester = :profileId OR f.receiver = :profileId')
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->setParameter('profileId', $profileId);
        try {
            return (int) $result->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }
}
