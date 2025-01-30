<?php

namespace App\Tests\Controller;

use App\Controller\MessageController;
use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageControllerTest extends TestCase
{
    private $entityManager;
    private $conversationRepository;
    private $messageService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->conversationRepository = $this->createMock(ConversationRepository::class);
        $this->messageService = $this->createMock(MessageService::class);
    }

    public function testSendMessageSuccess(): void
    {
        $conversationId = 1;
        $request = new Request([], [], [], [], [], [], json_encode([
            'userEmail' => 'user@example.com',
            'message' => 'Hello world',
        ]));

        $this->messageService->method('sendMessage')->willReturn([
            'message' => 'Message sent successfully'
        ]);

        $controller = new MessageController($this->messageService);

        $response = $controller->sendMessage($conversationId, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Message sent successfully', $responseData['message']);
    }


    public function testSendMessageBadRequest(): void
    {
        $conversationId = 1;
        $request = new Request([], [], [], [], [], [], json_encode([]));

        $this->messageService->method('sendMessage')->willReturn([
            'error' => 'Invalid request',
            'status' => Response::HTTP_BAD_REQUEST
        ]);

        $controller = new MessageController($this->messageService);

        $response = $controller->sendMessage($conversationId, $request);

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
            ->setConstructorArgs([$this->messageService])
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

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetMessagesUserNotFound(): void
    {
        $conversationId = 1;

        $controller = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->messageService])
            ->onlyMethods(['getUser'])
            ->getMock();

        $controller->method('getUser')->willReturn(null);

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'page=1&limit=20']);

        $response = $controller->getMessages($conversationId, $request);

        $this->assertEquals(404, $response->getStatusCode());
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

        $controller = new MessageController($this->messageService);

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

        $controller = new MessageController($this->messageService);

        $response = $controller->markMessagesRead($conversationId, $request);

        $this->assertEquals(400, $response->getStatusCode());
    }
}