<?php

namespace App\Tests\Constant;

use App\Constant\GenericErrorMessagesConstant;
use App\Constant\ProfileErrorMessagesConstant;
use App\Constant\SecurityErrorMessagesConstant;
use App\Constant\UserErrorMessagesConstant;
use PHPUnit\Framework\TestCase;

class ErrorMessagesConstantTest extends TestCase
{
    public function testErrorMessagesConstants(): void
    {
        $this->assertEquals('User not found', UserErrorMessagesConstant::USER_NOT_FOUND);
        $this->assertEquals('Profile not found', ProfileErrorMessagesConstant::PROFILE_NOT_FOUND);
        $this->assertEquals('Internal Server Error', GenericErrorMessagesConstant::INTERNAL_SERVER_ERROR);
        $this->assertEquals('Invalid data', GenericErrorMessagesConstant::INVALID_DATA);
        $this->assertEquals('Invalid input', GenericErrorMessagesConstant::INVALID_INPUT);
        $this->assertEquals('Token not found', SecurityErrorMessagesConstant::TOKEN_NOT_FOUND);
        $this->assertEquals('Invalid token', SecurityErrorMessagesConstant::INVALID_TOKEN);
        $this->assertEquals('Token expired', SecurityErrorMessagesConstant::TOKEN_EXPIRED);
        $this->assertEquals('Email already in use', UserErrorMessagesConstant::EMAIL_ALREADY_IN_USE);
        $this->assertEquals('Unauthorized access', SecurityErrorMessagesConstant::UNAUTHORIZED_ACCESS);
        $this->assertEquals('Access denied', SecurityErrorMessagesConstant::FORBIDDEN);
    }
}