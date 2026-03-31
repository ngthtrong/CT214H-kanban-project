<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\AccessControlService;
use PHPUnit\Framework\TestCase;

class AccessControlTask00022Test extends TestCase
{
    public function test_access_control_service_should_redirect_guest_when_guest_accesses_profile_page(): void
    {
        $service = new AccessControlService();

        $result = $service->evaluate(null, '/profile');

        $this->assertFalse($result['allowed']);
        $this->assertSame('/login', $result['redirect_to']);
    }

    public function test_access_control_service_should_allow_access_when_authenticated_user_accesses_protected_page(): void
    {
        $service = new AccessControlService();

        $result = $service->evaluate([
            'id' => 1,
            'role' => 'authenticated',
        ], '/profile');

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['redirect_to']);
    }
}