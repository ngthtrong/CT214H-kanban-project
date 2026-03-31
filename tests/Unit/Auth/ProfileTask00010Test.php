<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\ProfileService;
use PHPUnit\Framework\TestCase;

class ProfileTask00010Test extends TestCase
{
    public function test_profile_service_should_return_profile_when_authenticated_user_requests_profile(): void
    {
        $service = new ProfileService();

        $result = $service->getProfile([
            'id' => 1,
            'username' => 'alice',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('alice', $result['profile']['username']);
    }

    public function test_profile_service_should_update_profile_when_username_and_password_are_valid(): void
    {
        $service = new ProfileService();

        $result = $service->updateProfile([
            'id' => 1,
            'username' => 'alice',
        ], [
            'username' => 'alice.new',
            'password' => 'ValidPass1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('alice.new', $result['profile']['username']);
        $this->assertArrayHasKey('password_hash', $result['profile']);
    }
}