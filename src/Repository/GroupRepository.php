<?php

namespace App\Repository;

use App\Entity\Group;
use App\Enum\Group\GroupSortOptionEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

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
        string $sort = GroupSortOptionEnum::NEWEST->value,
        int $limit = 20,
        int $offset = 0,
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->select('g', 'COUNT(DISTINCT gp.profile) AS membersCount')
            ->leftJoin('g.groupProfiles', 'gp')
            ->leftJoin('g.taggables', 'tg')
            ->leftJoin('tg.tag', 't')
            ->leftJoin('g.posts', 'p');

        if ($sort === GroupSortOptionEnum::ACTIVE->value) {
            $qb->addSelect('COALESCE(COUNT(p.id), 0) AS activityScore'); // 🔥 Ici, alias explicite
            $qb->addSelect('COALESCE(MAX(p.createdAt), g.createdAt) AS lastPostDate'); // 🔥 Alias explicite
        } else {
            $qb->addSelect('0 AS activityScore');
            $qb->addSelect('g.createdAt AS lastPostDate');
        }

        $qb->where('g.visibility = :visibility')
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

        if ($sort === GroupSortOptionEnum::ACTIVE->value) {
            $qb->having('COALESCE(COUNT(p.id), 0) >= 0');
        }

        if ($sort === GroupSortOptionEnum::ACTIVE->value) {
            $qb->orderBy('activityScore', 'DESC')
                ->addOrderBy('lastPostDate', 'DESC');
        } else {
            $sortQuery = GroupSortOptionEnum::getSortQuery($sort);
            $qb->orderBy(...explode(' ', $sortQuery));
        }

        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        try {
            return $qb->getQuery()->getResult();
        } catch (Exception $e) {
            return [];
        }
    }
}
