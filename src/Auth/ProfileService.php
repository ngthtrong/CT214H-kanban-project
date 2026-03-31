<?php

declare(strict_types=1);

namespace App\Auth;

class ProfileService
{
    /**
     * @param array<string, mixed> $user
     * @return array{success: bool, profile: array<string, mixed>}
     */
    public function getProfile(array $user): array
    {
        return [
            'success' => true,
            'profile' => [
                'id' => (int) ($user['id'] ?? 0),
                'username' => (string) ($user['username'] ?? ''),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $payload
     * @return array{success: bool, profile: array<string, mixed>}
     */
    public function updateProfile(array $user, array $payload): array
    {
        $updated = [
            'id' => (int) ($user['id'] ?? 0),
            'username' => trim((string) ($payload['username'] ?? $user['username'] ?? '')),
        ];

        $password = (string) ($payload['password'] ?? '');
        if ($password !== '') {
            $updated['password_hash'] = (string) password_hash($password, PASSWORD_DEFAULT);
        }

        return [
            'success' => true,
            'profile' => $updated,
        ];
    }
}