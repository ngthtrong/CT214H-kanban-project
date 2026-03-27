<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Contracts\UserRepositoryInterface;

class RegistrationService
{
    private UserRepositoryInterface $users;
    /** @var callable(string): string|false */
    private $passwordHasher;

    /**
     * @param callable(string): string|false|null $passwordHasher
     */
    public function __construct(UserRepositoryInterface $users, ?callable $passwordHasher = null)
    {
        $this->users = $users;
        $this->passwordHasher = $passwordHasher ?? static fn (string $password): string|false => password_hash($password, PASSWORD_DEFAULT);
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

        $passwordHash = ($this->passwordHasher)((string) $payload['password']);

        if ($passwordHash === false) {
            return [
                'success' => false,
                'errors' => [
                    'password' => 'Unable to hash password at this time.',
                ],
            ];
        }

        $user = [
            'id' => $this->users->nextId(),
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
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
