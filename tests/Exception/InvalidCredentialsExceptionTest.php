<?php

namespace App\Tests\Exception;

use App\Exception\InvalidCredentialsException;
use PHPUnit\Framework\TestCase;

class InvalidCredentialsExceptionTest extends TestCase
{
    public function testDefaultReason(): void
    {
        $exception = new InvalidCredentialsException();

        $this->assertInstanceOf(InvalidCredentialsException::class, $exception);
        $this->assertEquals('Invalid credentials', $exception->getReason());
        $this->assertEquals('Invalid credentials', $exception->getMessage());
        $this->assertEquals('Invalid credentials', $exception->getMessageKey());
    }

    public function testCustomReason(): void
    {
        $customReason = 'Custom invalid credentials reason';
        $exception = new InvalidCredentialsException($customReason);

        $this->assertInstanceOf(InvalidCredentialsException::class, $exception);
        $this->assertEquals($customReason, $exception->getReason());
        $this->assertEquals($customReason, $exception->getMessage());
        $this->assertEquals($customReason, $exception->getMessageKey());
    }
}