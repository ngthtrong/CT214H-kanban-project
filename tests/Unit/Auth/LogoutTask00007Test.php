<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\LogoutService;
use PHPUnit\Framework\TestCase;

class LogoutTask00007Test extends TestCase
{
    public function test_logout_service_should_terminate_session_when_user_is_authenticated(): void
    {
        $service = new LogoutService();

        $result = $service->logout([
            'user_id' => 1,
            'session_id' => 'sess_123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['session_terminated']);
    }
}