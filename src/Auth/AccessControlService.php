<?php

declare(strict_types=1);

namespace App\Auth;

class AccessControlService
{
    /** @var array<int, string> */
    private array $protectedPaths = ['/profile'];

    /**
     * @param array<string, mixed>|null $user
     * @return array{allowed: bool, redirect_to: string|null}
     */
    public function evaluate(?array $user, string $path): array
    {
        if (in_array($path, $this->protectedPaths, true) && $user === null) {
            return [
                'allowed' => false,
                'redirect_to' => '/login',
            ];
        }

        return [
            'allowed' => true,
            'redirect_to' => null,
        ];
    }
}