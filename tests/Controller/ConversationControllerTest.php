<?php

namespace App\Tests\Controller;

use App\Controller\ConversationController;
use App\Repository\UserRepository;
use App\Service\ConversationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConversationControllerTest extends TestCase
{
    private $conversationService;
    private $userRepository;
    private $container;

    protected function setUp(): void
    {
        $this->conversationService = $this->createMock(ConversationService::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testCreateConversationSuccess(): void
    {
        $this->conversationService
            ->method('createConversation')
            ->willReturn(['message' => 'Conversation created', 'status' => Response::HTTP_CREATED]);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $controller->setContainer($this->container);

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'participants' => [1, 2],
        ]));

        $response = $controller->createConversation($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertStringContainsString('Conversation created', $response->getContent());
    }

    public function testGetAllConversationsSuccess(): void
    {
        $this->userRepository->method('findOneUserById')->willReturn([
            'id' => 1,
            'email' => 'test@example.com'
        ]);
        $this->conversationService->method('getAllConversationsWithLastMessages')->willReturn([
            'conversations' => [['id' => 1, 'lastMessage' => 'Test message']],
            'status' => Response::HTTP_OK
        ]);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $controller->setContainer($this->container);
        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'page=1&limit=20']);
        $response = $controller->getAllConversationsByProfileIdWithLastMessages(1, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Test message', $response->getContent());
    }

    public function testGetAllConversationsUserNotFound(): void
    {
        $this->userRepository->method('findOneUserById')->willReturn(null);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => 'page=1&limit=20']);
        $response = $controller->getAllConversationsByProfileIdWithLastMessages(999, $request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('User not found', $response->getContent());
    }

    public function testDeleteConversationSuccess(): void
    {
        $this->conversationService->method('deleteOneConversationById')->willReturn([
            'message' => 'Conversation deleted',
            'status' => Response::HTTP_OK
        ]);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $response = $controller->deleteConversationById(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Conversation deleted', $response->getContent());
    }

    public function testDeleteConversationNotFound(): void
    {
        $this->conversationService->method('deleteOneConversationById')->willReturn([
            'error' => 'Conversation not found',
            'status' => Response::HTTP_NOT_FOUND
        ]);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $response = $controller->deleteConversationById(999);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Conversation not found', $response->getContent());
    }

    public function testArchiveConversationSuccess(): void
    {
        $this->conversationService->method('archiveConversation')->willReturn([
            'message' => 'Conversation archived',
            'status' => Response::HTTP_OK
        ]);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $response = $controller->archiveConversation(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Conversation archived', $response->getContent());
    }

    public function testUnarchiveConversationSuccess(): void
    {
        $this->conversationService->method('unarchiveConversation')->willReturn([
            'message' => 'Conversation unarchived',
            'status' => Response::HTTP_OK
        ]);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $response = $controller->unarchiveConversation(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Conversation unarchived', $response->getContent());
    }

    public function testMuteConversationSuccess(): void
    {
        $this->conversationService->method('muteConversation')->willReturn([
            'message' => 'Conversation muted',
            'status' => Response::HTTP_OK
        ]);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $request = new Request([], [], [], [], [], [], json_encode(['duration' => 24]));
        $response = $controller->muteConversation(1, $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Conversation muted', $response->getContent());
    }

    public function testUnmuteConversationSuccess(): void
    {
        $this->conversationService->method('unmuteConversation')->willReturn([
            'message' => 'Conversation unmuted',
            'status' => Response::HTTP_OK
        ]);

        $controller = new ConversationController(
            $this->conversationService,
            $this->userRepository
        );

        $response = $controller->unmuteConversation(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Conversation unmuted', $response->getContent());
    }
}