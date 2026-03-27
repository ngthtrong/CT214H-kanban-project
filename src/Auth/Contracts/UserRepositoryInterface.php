<?php

declare(strict_types=1);

namespace App\Auth\Contracts;

interface UserRepositoryInterface
{
    public function emailExists(string $email): bool;

    public function usernameExists(string $username): bool;

    public function nextId(): int;

    public function save(array $user): void;
}
