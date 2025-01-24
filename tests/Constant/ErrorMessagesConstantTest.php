<?php

namespace App\Tests\Constant;

use App\Constant\ErrorMessagesConstant;
use PHPUnit\Framework\TestCase;

class ErrorMessagesConstantTest extends TestCase
{
    public function testErrorMessagesConstants(): void
    {
        $this->assertEquals('User not found', ErrorMessagesConstant::USER_NOT_FOUND);
        $this->assertEquals('Profile not found', ErrorMessagesConstant::PROFILE_NOT_FOUND);
        $this->assertEquals('Internal Server Error', ErrorMessagesConstant::INTERNAL_SERVER_ERROR);
        $this->assertEquals('Invalid data', ErrorMessagesConstant::INVALID_DATA);
        $this->assertEquals('Invalid input', ErrorMessagesConstant::INVALID_INPUT);
        $this->assertEquals('Token not found', ErrorMessagesConstant::TOKEN_NOT_FOUND);
        $this->assertEquals('Invalid token', ErrorMessagesConstant::INVALID_TOKEN);
        $this->assertEquals('Token expired', ErrorMessagesConstant::TOKEN_EXPIRED);
        $this->assertEquals('Email already in use', ErrorMessagesConstant::EMAIL_ALREADY_IN_USE);
        $this->assertEquals('Unauthorized access', ErrorMessagesConstant::UNAUTHORIZED_ACCESS);
        $this->assertEquals('Access denied', ErrorMessagesConstant::FORBIDDEN);
    }
}