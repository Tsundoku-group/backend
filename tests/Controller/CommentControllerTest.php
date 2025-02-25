<?php

namespace App\Tests\Controller;

use App\Controller\CommentController;
use App\Document\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\ProfileRepository;
use App\Service\CommentService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use InvalidArgumentException;

class CommentControllerTest extends TestCase
{
    private CommentController $controller;
    private $commentService;
    private $commentRepository;
    private $postRepository;
    private $profileRepository;

    protected function setUp(): void
    {
        $this->commentService = $this->createMock(CommentService::class);
        $this->commentRepository = $this->createMock(CommentRepository::class);
        $this->postRepository = $this->createMock(PostRepository::class);
        $this->profileRepository = $this->createMock(ProfileRepository::class);

        $this->controller = new CommentController(
            $this->commentService,
            $this->commentRepository,
            $this->postRepository,
            $this->profileRepository
        );
    }

    public function testGetOneCommentByIdSuccess()
    {
        $commentId = '12345';
        $expectedResponse = new JsonResponse(['comment' => 'test comment']);

        $this->commentService
            ->method('getCommentById')
            ->with($commentId)
            ->willReturn($expectedResponse);

        $response = $this->controller->getOneCommentById($commentId);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals($expectedResponse->getContent(), $response->getContent());
    }

    public function testGetOneCommentByIdWithInvalidArgument()
    {
        $this->commentService
            ->method('getCommentById')
            ->willThrowException(new InvalidArgumentException('Invalid ID'));

        $response = new JsonResponse(['error' => 'Invalid argument: Invalid ID'], 400);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAddCommentToPostWithMissingParameters()
    {
        $request = new Request([], [], [], [], [], [], json_encode([]));
        $response = new JsonResponse(['error' => 'Missing parameters'], 400);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testDeleteCommentWithUnauthorizedUser()
    {
        $request = new Request([], [], [], [], [], [], json_encode(['authorId' => 'user456']));
        $commentId = 'comment123';

        $commentMock = $this->createMock(Comment::class);
        $commentMock->method('getAuthorId')->willReturn('user123');

        $this->commentRepository
            ->method('find')
            ->with($commentId)
            ->willReturn($commentMock);

        $this->profileRepository
            ->method('findProfileById')
            ->with('user456')
            ->willReturn(['id' => 'user456']);

        $response = new JsonResponse(['error' => 'Unauthorized action'], 403);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testDeleteCommentWithCommentNotFound()
    {
        $request = new Request([], [], [], [], [], [], json_encode(['authorId' => 'user123']));
        $commentId = 'comment123';

        $this->commentRepository
            ->method('find')
            ->with($commentId)
            ->willReturn(null);

        $response = new JsonResponse(['error' => 'comment not found'], 404);
        $this->assertEquals(404, $response->getStatusCode());
    }
}