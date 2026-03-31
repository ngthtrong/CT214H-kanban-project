<?php

declare(strict_types=1);

namespace App\Auth;

class CredentialValidationService
{
    /**
     * @param array<string, mixed> $payload
     * @return array{valid: bool, errors: array<string, string>}
     */
    public function validate(array $payload): array
    {
        $errors = [];

        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        }

        if (!$this->isPasswordValid($password)) {
            $errors['password'] = 'Password does not meet policy.';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    private function isPasswordValid(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/\d/', $password) === 1;
    }
}