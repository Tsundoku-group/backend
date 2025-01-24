<?php

namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidCredentialsException extends AuthenticationException
{
    private string $reason;

    public function __construct(string $reason = 'Invalid credentials')
    {
        $this->reason = $reason;
        parent::__construct($reason);
    }

    public function getMessageKey(): string
    {
        return $this->reason;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}