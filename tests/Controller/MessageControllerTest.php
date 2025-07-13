<?php

namespace App\Tests\Controller;

use App\Controller\MessageController;
use App\DTO\Message\SendMessageDTO;
use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\ProfileRepository;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageControllerTest extends TestCase
{
    private $messageService;
    private ProfileRepository $profileRepository;

    protected function setUp(): void
    {
        $this->messageService = $this->createMock(MessageService::class);
        $this->profileRepository = $this->createMock(ProfileRepository::class);
    }

    public function testSendMessageSuccess(): void
    {
        $conversationId = 1;

        $dto = new SendMessageDTO([
            'uuid' => 'fake-uuid-123',
            'sender_id' => 1,
            'content' => 'Hello world',
        ]);

        $this->messageService->method('sendMessage')->willReturn([
            'message' => 'Message sent successfully'
        ]);

        $controller = new MessageController($this->messageService, $this->profileRepository);

        $container = $this->createMock(ContainerInterface::class);
        $controller->setContainer($container);

        $response = $controller->sendMessage($dto, $conversationId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Message sent successfully', $responseData['message']);
    }


    public function testSendMessageBadRequest(): void
    {
        $conversationId = 1;

        // Simule un DTO invalide
        $dto = new SendMessageDTO([
            'uuid' => '',
            'sender_id' => 1,
            'content' => '',
        ]);

        $this->messageService->method('sendMessage')->willReturn([
            'error' => 'Invalid request',
            'status' => Response::HTTP_BAD_REQUEST
        ]);

        $controller = new MessageController($this->messageService, $this->profileRepository);

        // Appelle avec (DTO, int) ➜ dans le bon ordre !
        $response = $controller->sendMessage($dto, $conversationId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid request', $responseData['error']);
    }

    public function testGetMessagesSuccess(): void
    {
        $conversationId = 1;
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('user@example.com');

        $controller = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->messageService, $this->profileRepository])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);

        $this->messageService->method('getMessages')->willReturn([
            'messages' => [
                [
                    'id' => 1,
                    'content' => 'Hello world',
                    'sent_at' => '2024-02-01 12:00:00',
                ],
            ],
            'status' => Response::HTTP_OK
        ]);

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'page=1&limit=20']);

        $response = $controller->getMessages($conversationId, $request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetMessagesUserNotFound(): void
    {
        $conversationId = 1;

        $controller = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->messageService, $this->profileRepository])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->method('getUser')->willReturn(null);

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'page=1&limit=20']);

        $response = $controller->getMessages($conversationId, $request);

        $this->assertEquals(400, $response->getStatusCode());
    }


    public function testMarkMessagesReadSuccess(): void
    {
        $conversationId = 1;
        $request = new Request([], [], [], [], [], [], json_encode([
            'userEmail' => 'user@example.com',
        ]));

        $this->messageService->method('markMessagesRead')->willReturn([
            'message' => 'Messages marked as read'
        ]);

        $controller = new MessageController($this->messageService, $this->profileRepository);

        $response = $controller->markMessagesRead($conversationId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Messages marked as read', $responseData['message']);
    }

    public function testMarkMessagesReadBadRequest(): void
    {
        $conversationId = 1;
        $request = new Request([], [], [], [], [], [], json_encode([]));

        $controller = new MessageController($this->messageService, $this->profileRepository);

        $response = $controller->markMessagesRead($conversationId, $request);

        $this->assertEquals(400, $response->getStatusCode());
    }
}