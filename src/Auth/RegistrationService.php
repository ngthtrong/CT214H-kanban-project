<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Contracts\UserRepositoryInterface;
use App\Auth\Infrastructure\InMemoryUserRepository;

class RegistrationService
{
    private UserRepositoryInterface $users;

    public function __construct(?UserRepositoryInterface $users = null)
    {
        $this->users = $users ?? new InMemoryUserRepository();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success: bool, errors: array<string, string>, user?: array<string, mixed>}
     */
    public function register(array $payload): array
    {
        $errors = $this->validate($payload);

        $username = trim((string) ($payload['username'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));

        if ($username !== '' && $this->users->usernameExists($username)) {
            $errors['username'] = 'Username already exists.';
        }

        if ($email !== '' && $this->users->emailExists($email)) {
            $errors['email'] = 'Email already exists.';
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $user = [
            'id' => $this->users->nextId(),
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash((string) $payload['password'], PASSWORD_DEFAULT),
        ];

        $this->users->save($user);

        return [
            'success' => true,
            'errors' => [],
            'user' => $user,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function validate(array $payload): array
    {
        $errors = [];

        $username = trim((string) ($payload['username'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email is invalid.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (!$this->isPasswordValid($password)) {
            $errors['password'] = 'Password must be at least 8 chars and include upper/lower case.';
        }

        return $errors;
    }

    private function isPasswordValid(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1;
    }
}
