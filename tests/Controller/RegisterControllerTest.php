<?php

namespace App\Tests\Controller;

use App\Controller\RegisterController;
use App\Service\RegisterService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegisterControllerTest extends TestCase
{
    private $registerService;

    protected function setUp(): void
    {
        $this->registerService = $this->createMock(RegisterService::class);
    }

    private function createController(): RegisterController
    {
        return new RegisterController($this->registerService);
    }

    public function testRegisterSuccess(): void
    {
        $this->registerService->method('registerUser')->willReturn([
            'message' => 'User registered successfully',
            'status' => 201
        ]);

        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]));

        $response = $controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('User registered successfully', $responseData['message']);
    }

    public function testRegisterInvalidData(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([]));

        $response = $controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid data', $responseData['error']);
    }


    public function testConfirmInvalidToken(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => '']);

        $response = $controller->confirm($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid token', $responseData['error']);
    }

    public function testResendConfirmationEmailSuccess(): void
    {
        $this->registerService->expects($this->once())->method('resendConfirmationEmail');

        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([
            'email' => 'test@example.com',
        ]));

        $response = $controller->resendConfirmationEmail($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Confirmation email resent successfully', $responseData['success']);
    }

    public function testResendConfirmationEmailInvalidData(): void
    {
        $controller = $this->createController();

        $request = new Request([], [], [], [], [], [], json_encode([]));

        $response = $controller->resendConfirmationEmail($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid data', $responseData['error']);
    }
}