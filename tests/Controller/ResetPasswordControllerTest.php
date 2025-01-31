<?php

namespace App\Tests\Controller;

use App\Controller\ResetPasswordController;
use App\Service\ResetPasswordService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ResetPasswordControllerTest extends TestCase
{
    private $resetPasswordService;

    protected function setUp(): void
    {
        $this->resetPasswordService = $this->createMock(ResetPasswordService::class);
    }

    public function testForgotPasswordSuccess(): void
    {
        $this->resetPasswordService
            ->method('requestPasswordReset')
            ->with('test@example.com')
            ->willReturn(['resetToken' => 'sample-reset-token']);

        $controller = new ResetPasswordController($this->resetPasswordService);

        $request = new Request([], [], [], [], [], [], json_encode(['email' => 'test@example.com']));

        $response = $controller->forgotPassword($request, $this->resetPasswordService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $decodedResponse);
        $this->assertTrue($decodedResponse['success']);
    }

    public function testForgotPasswordUserNotFound(): void
    {
        $this->resetPasswordService
            ->method('requestPasswordReset')
            ->willReturn(['error' => 'User not found', 'status' => 404]);

        $controller = new ResetPasswordController($this->resetPasswordService);

        $request = new Request([], [], [], [], [], [], json_encode(['email' => 'unknown@example.com']));

        $response = $controller->forgotPassword($request, $this->resetPasswordService);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $decodedResponse);
        $this->assertEquals('User not found', $decodedResponse['error']);
    }

    public function testResetPasswordSuccess(): void
    {
        $this->resetPasswordService
            ->method('resetPassword')
            ->with('valid-token', 'new-password')
            ->willReturn(['success' => 'Password has been reset successfully']);

        $controller = new ResetPasswordController($this->resetPasswordService);

        $request = new Request([], [], [], [], [], [], json_encode([
            'token' => 'valid-token',
            'password' => 'new-password'
        ]));

        $response = $controller->resetPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $decodedResponse);
        $this->assertEquals('Password has been reset successfully', $decodedResponse['success']);
    }

    public function testResetPasswordInvalidToken(): void
    {
        $this->resetPasswordService
            ->method('resetPassword')
            ->willReturn(['error' => 'Invalid token', 'status' => 404]);

        $controller = new ResetPasswordController($this->resetPasswordService);

        $request = new Request([], [], [], [], [], [], json_encode([
            'token' => 'invalid-token',
            'password' => 'new-password'
        ]));

        $response = $controller->resetPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $decodedResponse);
        $this->assertEquals('Invalid token', $decodedResponse['error']);
    }

    public function testResetPasswordExpiredToken(): void
    {
        $this->resetPasswordService
            ->method('resetPassword')
            ->willReturn(['error' => 'Token expired', 'status' => 400]);

        $controller = new ResetPasswordController($this->resetPasswordService);

        $request = new Request([], [], [], [], [], [], json_encode([
            'token' => 'valid-token',
            'password' => 'new-password'
        ]));

        $response = $controller->resetPassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $decodedResponse);
        $this->assertEquals('Token expired', $decodedResponse['error']);
    }
}