<?php

declare(strict_types=1);

namespace App\Auth;

class AvatarPolicyService
{
    /**
     * @param array<string, mixed> $request
     * @return array{success: bool, status: int, message: string}
     */
    public function rejectUpload(array $request): array
    {
        return [
            'success' => false,
            'status' => 403,
            'message' => 'Avatar upload is not supported.',
        ];
    }
}