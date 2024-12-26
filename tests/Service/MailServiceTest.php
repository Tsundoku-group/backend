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
    private MailerInterface $mailer;
    private MailService $mailService;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);

        $this->mailService = new MailService($this->mailer);

        $_ENV['MAILER_EMAIL'] = 'no-reply@example.com';
    }

    public function testSendMailSuccess(): void
    {
        $email = 'test@example.com';
        $subject = 'Test Subject';
        $body = 'Hello {{ name }}!';
        $context = ['name' => 'John'];

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $emailMessage) use ($email, $subject, $body) {
                return $emailMessage->getTo()[0]->getAddress() === $email
                    && $emailMessage->getSubject() === $subject
                    && $emailMessage->getHtmlBody() === 'Hello John!';
            }));

        $this->mailService->sendMail($email, $subject, $body, $context);
    }

    public function testSendMailThrowsException(): void
    {
        $email = 'test@example.com';
        $subject = 'Test Subject';
        $body = 'Hello {{ name }}!';
        $context = ['name' => 'John'];

        $this->mailer->method('send')->willThrowException(
            $this->createMock(TransportExceptionInterface::class)
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send email');

        $this->mailService->sendMail($email, $subject, $body, $context);
    }

    public function testSendMailReplacesContextVariables(): void
    {
        $email = 'test@example.com';
        $subject = 'Test Subject';
        $body = 'Hello {{ name }}! Your code is {{ code }}.';
        $context = ['name' => 'John', 'code' => '12345'];

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $emailMessage) {
                return $emailMessage->getHtmlBody() === 'Hello John! Your code is 12345.';
            }));

        $this->mailService->sendMail($email, $subject, $body, $context);
    }
}