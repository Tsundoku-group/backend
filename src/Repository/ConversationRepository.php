<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findConversationsByUserOrderedByLastMessage(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p = :user')
            ->orderBy('c.lastMessageAt', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findOneByParticipants(array $participants): ?Conversation
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.id IN (:participants)')
            ->groupBy('c.id')
            ->having('COUNT(c.id) = :count')
            ->setParameter('participants', array_map(fn($participant) => $participant->getId(), $participants))
            ->setParameter('count', count($participants))
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findArchivedConversationsByUserId(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p') // jointure avec les participants
            ->andWhere('p.id = :userId') // condition sur l'ID de l'utilisateur
            ->andWhere('c.isArchived = :isArchived')
            ->setParameter('userId', $userId)
            ->setParameter('isArchived', true)
            ->getQuery()
            ->getResult();
    }
//    /**
//     * @return Conversation[] Returns an array of Conversation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Conversation
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
