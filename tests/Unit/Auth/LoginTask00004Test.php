<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\Infrastructure\InMemoryUserRepository;
use App\Auth\LoginService;
use PHPUnit\Framework\TestCase;

class LoginTask00004Test extends TestCase
{
    public function test_login_service_should_authenticate_user_when_username_and_password_are_valid(): void
    {
        $users = new InMemoryUserRepository([
            [
                'id' => 1,
                'username' => 'alice',
                'email' => 'alice@example.com',
                'password_hash' => password_hash('ValidPass1', PASSWORD_DEFAULT),
            ],
        ]);
        $service = new LoginService($users);

        $result = $service->login([
            'identifier' => 'alice',
            'password' => 'ValidPass1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['errors']);
        $this->assertSame(1, $result['user']['id']);
    }

    public function test_login_service_should_authenticate_user_when_email_and_password_are_valid(): void
    {
        $users = new InMemoryUserRepository([
            [
                'id' => 1,
                'username' => 'alice',
                'email' => 'alice@example.com',
                'password_hash' => password_hash('ValidPass1', PASSWORD_DEFAULT),
            ],
        ]);
        $service = new LoginService($users);

        $result = $service->login([
            'identifier' => 'alice@example.com',
            'password' => 'ValidPass1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['errors']);
        $this->assertSame('alice', $result['user']['username']);
    }

    public function test_login_service_should_reject_authentication_when_credentials_are_invalid(): void
    {
        $users = new InMemoryUserRepository([
            [
                'id' => 1,
                'username' => 'alice',
                'email' => 'alice@example.com',
                'password_hash' => password_hash('ValidPass1', PASSWORD_DEFAULT),
            ],
        ]);
        $service = new LoginService($users);

        $result = $service->login([
            'identifier' => 'alice',
            'password' => 'WrongPass1',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('credentials', $result['errors']);
    }
}