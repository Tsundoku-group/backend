<?php

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\CaptchaValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CaptchaValidatorTest extends TestCase
{
    public function testVerifyCaptchaSuccess(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('toArray')->willReturn(['success' => true]);
        $httpClient->method('request')->willReturn($response);

        $captchaValidator = new CaptchaValidator('fake-secret', $httpClient);
        $result = $captchaValidator->verifyCaptcha('fake-captcha-token');

        $this->assertTrue($result, 'Captcha verification should return true.');
    }

    public function testVerifyCaptchaFailure(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('toArray')->willReturn(['success' => false]);
        $httpClient->method('request')->willReturn($response);

        $captchaValidator = new CaptchaValidator('fake-secret', $httpClient);
        $result = $captchaValidator->verifyCaptcha('fake-captcha-token');

        $this->assertFalse($result, 'Captcha verification should return false.');
    }

    public function testVerifyCaptchaInvalidResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $response->method('toArray')->willReturn([]);
        $httpClient->method('request')->willReturn($response);

        $captchaValidator = new CaptchaValidator('fake-secret', $httpClient);
        $result = $captchaValidator->verifyCaptcha('fake-captcha-token');

        $this->assertFalse($result, 'Captcha verification should return false when "success" key is missing.');
    }
}