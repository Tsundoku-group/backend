<?php

namespace App\Tests\Controller;

use App\Controller\MessageController;
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
use Symfony\Component\HttpFoundation\Response;

class MessageControllerTest extends TestCase
{
    private $entityManager;
    private $profileRepository;
    private $conversationRepository;
    private $userRepository;
    private $redisChatService;
    private $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->profileRepository = $this->createMock(ProfileRepository::class);
        $this->conversationRepository = $this->createMock(ConversationRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->redisChatService = $this->createMock(ConfRedisService::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Profile::class, $this->profileRepository],
            [Conversation::class, $this->conversationRepository],
            [User::class, $this->userRepository],
        ]);
    }

    public function testSendMessageSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('user@example.com');

        $profile = $this->createMock(Profile::class);
        $profile->method('getId')->willReturn(1);
        $profile->method('getUser')->willReturn($user);
        $profile->method('getUsername')->willReturn('username');

        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getId')->willReturn(1);

        $this->profileRepository->method('findOneBy')->willReturn($profile);
        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->conversationRepository->method('find')->willReturn($conversation);
        $this->conversationRepository->method('isUserParticipant')->willReturn(true);

        $this->redisChatService
            ->expects($this->once())
            ->method('addMessageToConversation')
            ->with(
                $this->equalTo(1),
                $this->callback(function ($messageData) {
                    return isset($messageData['sender_id']) && $messageData['sender_id'] === 1;
                })
            );

        $request = new Request([], [], [], [], [], [], json_encode([
            'userEmail' => 'user@example.com',
            'message' => 'Test message',
            'id' => 1
        ]));

        $controller = new MessageController(
            $this->redisChatService,
            $this->entityManager,
            $this->conversationRepository
        );
        $controller->setContainer($this->container);

        $response = $controller->sendMessage(1, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSendMessageBadRequest(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'message' => 'Test message'
        ]));

        $controller = new MessageController(
            $this->redisChatService,
            $this->entityManager,
            $this->conversationRepository
        );

        $response = $controller->sendMessage(1, $request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetMessagesSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('user@example.com');

        $controller = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->redisChatService, $this->entityManager, $this->conversationRepository])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);

        $conversation = $this->createMock(Conversation::class);

        $this->redisChatService->method('getMessagesFromConversation')->willReturn([
            ['id' => 1, 'content' => 'Test message', 'sender_id' => 1, 'sender_email' => 'user@example.com', 'sent_by' => 'username', 'sent_at' => '2023-12-01 12:00:00', 'isRead' => false]
        ]);

        $this->conversationRepository->method('find')->willReturn($conversation);

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'page=1&limit=20']);

        $response = $controller->getMessages(1, $request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMarkMessagesReadBadRequest(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([]));

        $controller = new MessageController(
            $this->redisChatService,
            $this->entityManager,
            $this->conversationRepository
        );

        $response = $controller->markMessagesRead(1, $request);

        $this->assertEquals(400, $response->getStatusCode());
    }
}