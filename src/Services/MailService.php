<?php

namespace App\Services;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailService
{
    public function __construct(private readonly MailerInterface $mailerInterface)
    {
    }

    public function sendMail(string $email, string $subject, string $body, array $context = []): void
    {
        foreach ($context as $key => $value) {
            $body = str_replace("{{ $key }}", $value, $body);
        }

        $email = (new Email())
            ->from(new Address($_ENV['MAILER_EMAIL']))
            ->to($email)
            ->subject($subject)
            ->html($body);

        try {
            $this->mailerInterface->send($email);
        }  catch (TransportExceptionInterface $e) {
            throw new \RuntimeException(sprintf('Failed to send email: %s', $e->getMessage()), 0, $e);
        }
    }
}