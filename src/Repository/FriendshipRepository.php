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
    }

    public function getAllProfilesWithCommonFriends(int $profileId, int $limit, int $offset): array
    {
        $em = $this->getEntityManager();

        $friendsSubQb = $em->createQueryBuilder()
            ->select('IDENTITY(f.receiver) AS receiverId, IDENTITY(f.requester) AS requesterId')
            ->from('App\Entity\Friendship', 'f')
            ->where('(f.requester = :profileId OR f.receiver = :profileId) AND f.status = :status')
            ->setParameter('profileId', $profileId)
            ->setParameter('status', Friendship::STATUS_ACCEPTED);

        $friendsRelations = $friendsSubQb->getQuery()->getArrayResult();

        $friendIds = array_unique(
            array_merge(
                array_column($friendsRelations, 'receiverId'),
                array_column($friendsRelations, 'requesterId')
            )
        );

        $qb = $em->createQueryBuilder()
            ->select('p.id, p.lastName, p.firstName, p.username')
            ->from('App\Entity\Profile', 'p')
            ->where('p.id != :profileId')
            ->setParameter('profileId', $profileId);

        $profiles = $qb->getQuery()->getArrayResult();

        foreach ($profiles as &$profile) {
            $profileFriendsSubQb = $em->createQueryBuilder()
                ->select('IDENTITY(f.receiver) AS receiverId, IDENTITY(f.requester) AS requesterId')
                ->from('App\Entity\Friendship', 'f')
                ->where('(f.requester = :profileId OR f.receiver = :profileId) AND f.status = :status')
                ->setParameter('profileId', $profile['id'])
                ->setParameter('status', Friendship::STATUS_ACCEPTED)
                ->setMaxResults($limit)
                ->setFirstResult($offset);

            $profileFriends = $profileFriendsSubQb->getQuery()->getArrayResult();

            $profileFriendIds = array_unique(
                array_merge(
                    array_column($profileFriends, 'receiverId'),
                    array_column($profileFriends, 'requesterId')
                )
            );

            $commonFriends = array_intersect($friendIds, $profileFriendIds);
            $profile['commonFriendsCount'] = count($commonFriends);
        }

        $profilesWithCommonFriends = array_filter($profiles, fn ($p) => $p['commonFriendsCount'] > 0);
        $profilesWithoutCommonFriends = array_filter($profiles, fn ($p) => 0 === $p['commonFriendsCount']);

        return array_merge($profilesWithCommonFriends, $profilesWithoutCommonFriends);
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
