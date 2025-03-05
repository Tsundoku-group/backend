<?php

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findPrivateGroups(
        ?string $search = null,
        ?string $tagName = null,
        string $sort = 'newest',
        int $limit = 20,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->select('g', 'COUNT(DISTINCT gp.profile) AS membersCount')
            ->leftJoin('g.groupProfiles', 'gp')
            ->leftJoin('g.taggables', 'tg')
            ->leftJoin('tg.tag', 't')
            ->where('g.visibility = :visibility')
            ->setParameter('visibility', 'private')
            ->groupBy('g.id, g.createdAt');

        $conditions = $qb->expr()->andX();

        if (!empty($search)) {
            $conditions->add(
                $qb->expr()->orX(
                    'LOWER(g.name) LIKE :search',
                    'LOWER(g.description) LIKE :search'
                )
            );
            $qb->setParameter('search', '%' . strtolower($search) . '%');
        }

        if (!empty($tagName)) {
            $tagsArray = array_map('trim', explode(',', strtolower($tagName)));
            if (!empty($tagsArray)) {
                $conditions->add($qb->expr()->in('LOWER(t.name)', ':tags'));
                $qb->setParameter('tags', $tagsArray);
            }
        }

        if ($conditions->count() > 0) {
            $qb->andWhere($conditions);
        }

        $sortOptions = [
            'members' => 'membersCount DESC',
            'newest' => 'g.createdAt DESC'
        ];
        $qb->orderBy(...explode(' ', $sortOptions[$sort] ?? 'g.createdAt DESC'));

        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        try {
            return $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
}
