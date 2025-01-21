<?php

namespace App\Tests\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\ConversationRepository;
use PHPUnit\Framework\TestCase;

class ConversationRepositoryTest extends TestCase
{
    private $conversationRepository;

    protected function setUp(): void
    {
        $this->conversationRepository = $this->createMock(ConversationRepository::class);

        $this->conversationRepository->method('findConversationsByUserOrderedByLastMessage')
            ->willReturn([
                $this->createMock(Conversation::class)
            ]);

        $this->conversationRepository->method('findOneByParticipants')
            ->willReturn($this->createMock(Conversation::class));

        $this->conversationRepository->method('findArchivedConversationsByUserId')
            ->willReturn([
                $this->createMock(Conversation::class)
            ]);
    }

    public function testFindConversationsByUserOrderedByLastMessage()
    {
        $user = new User();
        $user->setEmail('testuser@example.com');

        $result = $this->conversationRepository->findConversationsByUserOrderedByLastMessage($user, 1, 10);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Conversation::class, $result[0]);
    }

    public function testFindOneByParticipants()
    {
        $user1 = new User();
        $user1->setEmail('user1@example.com');
        $user2 = new User();
        $user2->setEmail('user2@example.com');

        $result = $this->conversationRepository->findOneByParticipants([$user1, $user2]);

        $this->assertInstanceOf(Conversation::class, $result);
    }

    public function testFindArchivedConversationsByUserId()
    {
        $user = new User();
        $user->setEmail('archiveduser@example.com');
        $user->setId(1);
        $result = $this->conversationRepository->findArchivedConversationsByUserId($user->getId());

        $this->assertCount(1, $result);
    }
}