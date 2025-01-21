<?php

namespace App\Tests\Service;

use App\Service\MailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use RuntimeException;

class MailServiceTest extends TestCase
{
    private $mailer;
    private MailService $mailService;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->mailService = new MailService($this->mailer);

        $_ENV['MAILER_EMAIL'] = 'no-reply@example.com';
    }

    public function testSendMailSuccess(): void
    {
        $recipientEmail = 'test@example.com';
        $subject = 'Test Subject';
        $body = 'Hello {{ name }}!';
        $context = ['name' => 'John'];
        $expectedBody = 'Hello John!';

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($recipientEmail, $subject, $expectedBody) {
                return $email->getTo()[0]->getAddress() === $recipientEmail
                    && $email->getSubject() === $subject
                    && $email->getHtmlBody() === $expectedBody;
            }));

        $this->mailService->sendMail($recipientEmail, $subject, $body, $context);
    }

    public function testSendMailThrowsException(): void
    {
        $recipientEmail = 'test@example.com';
        $subject = 'Test Subject';
        $body = 'Hello {{ name }}!';
        $context = ['name' => 'John'];

        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send email');

        $this->mailService->sendMail($recipientEmail, $subject, $body, $context);
    }

    public function testSendMailReplacesContextVariables(): void
    {
        $recipientEmail = 'test@example.com';
        $subject = 'Test Subject';
        $body = 'Hello {{ name }}! Your code is {{ code }}.';
        $context = ['name' => 'John', 'code' => '12345'];
        $expectedBody = 'Hello John! Your code is 12345.';

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($expectedBody) {
                return $email->getHtmlBody() === $expectedBody;
            }));

        $this->mailService->sendMail($recipientEmail, $subject, $body, $context);
    }
}