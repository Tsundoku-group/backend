<?php

namespace App\Tests\Controller;

use App\Controller\ConversationController;
use App\Entity\Conversation;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use App\Service\ConfRedisService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

class ConversationControllerTest extends TestCase
{
    private $entityManager;
    private $profileRepository;
    private $conversationRepository;
    private $userRepository;
    private $redisService;
    private $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->profileRepository = $this->createMock(ProfileRepository::class);
        $this->conversationRepository = $this->createMock(ConversationRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->redisService = $this->createMock(ConfRedisService::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Profile::class, $this->profileRepository],
            [Conversation::class, $this->conversationRepository],
            [User::class, $this->userRepository],
        ]);
    }

    public function testCreateConversationSuccess(): void
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getId')->willReturn(1);

        $this->profileRepository->method('findProfileByEmail')->willReturn($profile);
        $this->conversationRepository->method('findOneByParticipants')->willReturn(null);

        $conversation = new TestConversation();
        $conversation->setId(1);
        $conversation->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Conversation::class));
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new ConversationController(
            $this->entityManager,
            $this->profileRepository,
            $this->conversationRepository,
            $this->redisService
        );
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'participants' => [1, 2],
        ]));

        $response = $controller->createConversation($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testGetAllConversationsSuccess(): void
    {
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);
        $profile->method('getId')->willReturn(1);
        $profile->method('getUser')->willReturn($user);

        $participant = $this->createMock(Profile::class);
        $participant->method('getId')->willReturn(2);
        $participant->method('getUser')->willReturn($user);
        $participant->method('getUsername')->willReturn('testUser');

        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(1);
        $conversation->method('getCreatedAt')->willReturn(new \DateTimeImmutable());
        $conversation->method('getCreatedBy')->willReturn($profile);

        $conversation->method('getParticipants')->willReturn(new ArrayCollection([$participant]));

        $this->userRepository->method('find')->willReturn($user);
        $this->conversationRepository
            ->method('findConversationsByUserOrderedByLastMessage')
            ->willReturn([$conversation]);

        $this->redisService->method('getMessagesFromConversation')->willReturn([
            ['sent_at' => '2023-12-01 12:00:00'],
        ]);

        $controller = new ConversationController(
            $this->entityManager,
            $this->profileRepository,
            $this->conversationRepository,
            $this->redisService
        );
        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'page=1&limit=20']);

        $response = $controller->getAllConversationsWithLastMessages(1, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetAllConversationsUserNotFound(): void
    {
        $this->userRepository->method('find')->willReturn(null);

        $controller = new ConversationController(
            $this->entityManager,
            $this->profileRepository,
            $this->conversationRepository,
            $this->redisService
        );

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'page=1&limit=20']);
        $response = $controller->getAllConversationsWithLastMessages(999, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDeleteConversationSuccess(): void
    {
        $conversation = $this->createMock(Conversation::class);

        $this->conversationRepository->method('find')->willReturn($conversation);

        $this->entityManager->expects($this->once())->method('remove')->with($conversation);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = new ConversationController(
            $this->entityManager,
            $this->profileRepository,
            $this->conversationRepository,
            $this->redisService
        );

        $response = $controller->deleteConversationById(1);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDeleteConversationNotFound(): void
    {
        $this->conversationRepository->method('find')->willReturn(null);

        $controller = new ConversationController(
            $this->entityManager,
            $this->profileRepository,
            $this->conversationRepository,
            $this->redisService
        );

        $response = $controller->deleteConversationById(999);
        $this->assertEquals(404, $response->getStatusCode());
    }
}

class TestConversation extends Conversation
{
    public function setId(int $id): void
    {
        $reflection = new \ReflectionProperty(Conversation::class, 'id');
        $reflection->setValue($this, $id);
    }
}