<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 *
 * @method Conversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conversation[]    findAll()
 * @method Conversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function findConversationsByProfileByLastMessage(Profile $profile, int $page, int $limit): array
    {
        $qd = $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->where('p = :profile')
            ->setParameter('profile', $profile)
            ->orderBy('c.lastMessageAt', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        try {
            return $qd->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    public function findOneByParticipants(array $participants): ?Conversation
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.id IN (:participants)')
            ->groupBy('c.id')
            ->having('COUNT(c.id) = :count')
            ->setParameter('participants', array_map(fn ($participant) => $participant->getId(), $participants))
            ->setParameter('count', count($participants))
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findArchivedConversationsByUserId(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->andWhere('p.id = :userId')
            ->andWhere('c.isArchived = :isArchived')
            ->setParameter('userId', $userId)
            ->setParameter('isArchived', true)
            ->getQuery()
            ->getResult();
    }

    public function isUserParticipant(int $conversationId, Profile $user): bool
    {
        $conversation = $this->find($conversationId);

        if (!$conversation) {
            return false;
        }

        return $conversation->getParticipants()->contains($user);
    }
}
