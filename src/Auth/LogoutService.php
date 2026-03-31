<?php

declare(strict_types=1);

namespace App\Auth;

class LogoutService
{
    /**
     * @param array<string, mixed> $context
     * @return array{success: bool, session_terminated: bool}
     */
    public function logout(array $context): array
    {
        $sessionTerminated = isset($context['user_id'], $context['session_id']);

        return [
            'success' => $sessionTerminated,
            'session_terminated' => $sessionTerminated,
        ];
    }
}