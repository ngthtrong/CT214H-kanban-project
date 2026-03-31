<?php

declare(strict_types=1);

namespace App\Auth\Contracts;

interface UserRepositoryInterface
{
    public function emailExists(string $email): bool;

    public function usernameExists(string $username): bool;

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdentifier(string $identifier): ?array;

    public function nextId(): int;

    public function save(array $user): void;
}
