<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\Infrastructure\InMemoryUserRepository;
use App\Auth\RegistrationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RegistrationTask00001Test extends TestCase
{
    public function test_registration_service_should_create_account_when_payload_is_valid(): void
    {
        $service = new RegistrationService(new InMemoryUserRepository());

        $result = $service->register([
            'username' => 'alice',
            'email' => 'Alice@example.com',
            'password' => 'ValidPass1',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['errors']);
        $this->assertArrayHasKey('user', $result);
        $this->assertSame('alice', $result['user']['username']);
        $this->assertSame('alice@example.com', $result['user']['email']);
        $this->assertNotSame('ValidPass1', $result['user']['password_hash']);
    }

    public function test_registration_service_should_reject_registration_when_email_already_exists(): void
    {
        $users = new InMemoryUserRepository([
            [
                'id' => 1,
                'username' => 'bob',
                'email' => 'bob@example.com',
                'password_hash' => 'hash',
            ],
        ]);
        $service = new RegistrationService($users);

        $result = $service->register([
            'username' => 'charlie',
            'email' => 'bob@example.com',
            'password' => 'ValidPass1',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Email already exists.', $result['errors']['email']);
    }

    public function test_registration_service_should_reject_registration_when_username_already_exists(): void
    {
        $users = new InMemoryUserRepository([
            [
                'id' => 1,
                'username' => 'bob',
                'email' => 'bob@example.com',
                'password_hash' => 'hash',
            ],
        ]);
        $service = new RegistrationService($users);

        $result = $service->register([
            'username' => 'bob',
            'email' => 'charlie@example.com',
            'password' => 'ValidPass1',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Username already exists.', $result['errors']['username']);
    }

    #[DataProvider('invalidPayloadProvider')]
    public function test_registration_service_should_reject_registration_when_required_fields_are_missing_or_invalid(
        array $payload,
        string $errorKey
    ): void {
        $service = new RegistrationService(new InMemoryUserRepository());

        $result = $service->register($payload);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey($errorKey, $result['errors']);
    }

    /**
     * @return array<string, array{payload: array<string, string>, errorKey: string}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'missing username' => [
                'payload' => [
                    'username' => '',
                    'email' => 'alice@example.com',
                    'password' => 'ValidPass1',
                ],
                'errorKey' => 'username',
            ],
            'invalid email format' => [
                'payload' => [
                    'username' => 'alice',
                    'email' => 'alice-at-example.com',
                    'password' => 'ValidPass1',
                ],
                'errorKey' => 'email',
            ],
            'password missing uppercase' => [
                'payload' => [
                    'username' => 'alice',
                    'email' => 'alice@example.com',
                    'password' => 'lowercase1',
                ],
                'errorKey' => 'password',
            ],
        ];
    }
}
