<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure;

use App\Auth\Contracts\UserRepositoryInterface;

class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $users;

    /**
     * @param array<int, array<string, mixed>> $seedUsers
     */
    public function __construct(array $seedUsers = [])
    {
        $this->users = $seedUsers;
    }

    public function emailExists(string $email): bool
    {
        $normalizedEmail = strtolower($email);

        foreach ($this->users as $user) {
            if (strtolower((string) ($user['email'] ?? '')) === $normalizedEmail) {
                return true;
            }
        }

        return false;
    }

    public function usernameExists(string $username): bool
    {
        foreach ($this->users as $user) {
            if ((string) ($user['username'] ?? '') === $username) {
                return true;
            }
        }

        return false;
    }

    public function nextId(): int
    {
        if ($this->users === []) {
            return 1;
        }

        $ids = array_map(static fn (array $user): int => (int) ($user['id'] ?? 0), $this->users);

        return max($ids) + 1;
    }

    public function save(array $user): void
    {
        $this->users[] = $user;
    }
}
