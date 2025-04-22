<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findRecentPosts(int $limit = 10, ?int $groupId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($groupId !== null) {
            $qb->innerJoin('p.group', 'g')
                ->andWhere('g.id = :groupId')
                ->setParameter('groupId', $groupId);
        }

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    public function findOlderPosts(int $page, int $limit, int $groupId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.group', 'g')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $groupId)
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        try {
            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    public function findPaginatedArticlesByProfile(
        int    $profileId,
        int    $page = 1,
        int    $maxPerPage = 15,
        string $sortField = 'createdAt',
        string $sortOrder = 'DESC'
    ): array
    {
        $allowedSortFields = ['status', 'title', 'createdAt', 'updatedAt'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'createdAt';
        }
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('p')
            ->where('p.author = :profile')
            ->andWhere('p.type = :type')
            ->setParameter('profile', $profileId)
            ->setParameter('type', 'article')
            ->orderBy('p.' . $sortField, $sortOrder)
            ->setFirstResult(($page - 1) * $maxPerPage)
            ->setMaxResults($maxPerPage);

        $paginator = new Paginator($qb->getQuery(), true);
        $totalCount = count($paginator);

        $articles = [];
        foreach ($paginator as $article) {
            $articles[] = $article;
        }

        return [
            'articles' => $articles,
            'pagination' => [
                'currentPage' => $page,
                'limit' => $maxPerPage,
                'totalArticles' => $totalCount,
                'totalPages' => ceil($totalCount / $maxPerPage),
            ],
        ];
    }

    public function findPostWithGroupById(int $postId): ?Post
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.group', 'g')
            ->addSelect('g')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId);

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NoResultException) {
            return null;
        }
    }

    public function countTotalPosts(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
