<?php

namespace App\Repository;

use App\Document\Comment;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Exception;

class CommentRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $classMetadata)
    {
        parent::__construct($dm, $uow, $classMetadata);
    }

    public function save(Comment $comment): void
    {
        $this->dm->persist($comment);
        $this->dm->flush();
    }

    public function findCommentById(string $commentId): ?Comment
    {
        return $this->dm->getRepository(Comment::class)->find($commentId);
    }

    public function getMainComments(string $postId, int $limit = 20): array
    {
        $queryBuilder = $this->createQueryBuilder()
            ->field('postId')->equals($postId)
            ->field('parentId')->equals(null)
            ->sort('_id', 'desc')
            ->limit($limit);

        try {
            return $queryBuilder->getQuery()->execute()->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    public function findCommentWithChildren(string $commentId): ?array
    {
        $comments = $this->dm->getRepository(Comment::class)
            ->createQueryBuilder()
            ->field('$or')->equals([
                ['_id' => $commentId],
                ['parentId' => $commentId],
            ])
            ->sort('createdAt', 'asc');

        try {
            return $comments->getQuery()->execute()->toArray();
        } catch (Exception $e) {
            return null;
        }
    }

    public function countCommentsForPost(string $postId): int
    {
        try {
            return $this->createQueryBuilder()
                ->field('postId')->equals($postId)
                ->count()
                ->getQuery()
                ->execute();
        } catch (Exception $e) {
            return 0;
        }
    }
}
