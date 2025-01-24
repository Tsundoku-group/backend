<?php

namespace App\Validator\Constraints;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CaptchaValidator
{
    public function __construct(
        private readonly string     $googleRecaptchaSecret,
        private HttpClientInterface $httpClient
    )
    {
    }

    public function verifyCaptcha(string $captchaToken): bool
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $response = $this->httpClient->request('POST', $url, [
            'body' => [
                'secret' => $this->googleRecaptchaSecret,
                'response' => $captchaToken,
            ],
        ]);

        $responseData = $response->toArray();

        return $responseData['success'] ?? false;
    }
}
