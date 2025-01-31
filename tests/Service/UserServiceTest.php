<?php

namespace App\Tests\Service;

use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private $entityManager;
    private $queryBuilder;
    private $query;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
    }

    public function testScheduleAccountDeletionSuccess(): void
    {
        $userId = 1;
        $deletionDate = new \DateTime('+30 days');

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->queryBuilder->method('update')->willReturnSelf();
        $this->queryBuilder->method('set')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('execute')->willReturn(1);

        $userService = new UserService($this->entityManager);
        $result = $userService->scheduleAccountDeletion($userId);

        $this->assertInstanceOf(\DateTime::class, $result);
        $this->assertEquals($deletionDate->format('Y-m-d'), $result->format('Y-m-d'));
    }

    public function testScheduleAccountDeletionUserNotFound(): void
    {
        $userId = 1;

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->queryBuilder->method('update')->willReturnSelf();
        $this->queryBuilder->method('set')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('execute')->willReturn(0);

        $userService = new UserService($this->entityManager);
        $result = $userService->scheduleAccountDeletion($userId);

        $this->assertNull($result);
    }

    public function testScheduleAccountDeletionQueryException(): void
    {
        $userId = 1;

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);

        $this->queryBuilder->method('update')->willReturnSelf();
        $this->queryBuilder->method('set')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('execute')->willThrowException(new \Exception());

        $userService = new UserService($this->entityManager);
        $result = $userService->scheduleAccountDeletion($userId);

        $this->assertNull($result);
    }
}