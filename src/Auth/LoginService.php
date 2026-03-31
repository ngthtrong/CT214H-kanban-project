<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Contracts\UserRepositoryInterface;

class LoginService
{
    private UserRepositoryInterface $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success: bool, errors: array<string, string>, user?: array<string, mixed>}
     */
    public function login(array $payload): array
    {
        $identifier = trim((string) ($payload['identifier'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($identifier === '' || $password === '') {
            return [
                'success' => false,
                'errors' => ['credentials' => 'Invalid credentials.'],
            ];
        }

        $user = $this->users->findByIdentifier($identifier);

        if ($user === null || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return [
                'success' => false,
                'errors' => ['credentials' => 'Invalid credentials.'],
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'user' => $user,
        ];
    }
}