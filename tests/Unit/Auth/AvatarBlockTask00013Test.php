<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\AvatarPolicyService;
use PHPUnit\Framework\TestCase;

class AvatarBlockTask00013Test extends TestCase
{
    public function test_avatar_policy_service_should_reject_upload_when_avatar_upload_request_is_sent(): void
    {
        $service = new AvatarPolicyService();

        $result = $service->rejectUpload([
            'user_id' => 1,
            'file_name' => 'avatar.png',
            'mime_type' => 'image/png',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(403, $result['status']);
    }
}