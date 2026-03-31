<?php

namespace Tests\Unit\Auth;

use App\Auth\RegistrationService;
use App\Database\Connection;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class RegistrationTask00001Test extends TestCase
{
    private RegistrationService $service;
    private $mockPdo;
    private $mockStmt;
    private Connection $mockConnection;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock PDO and Statement
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);
        
        // Set the mock PDO instance
        Connection::setInstance($this->mockPdo);
        
        // Create service with real connection (using mock PDO)
        $this->mockConnection = Connection::fromEnvironment();
        $this->service = new RegistrationService($this->mockConnection);
    }

    protected function tearDown(): void
    {
        Connection::resetInstance();
        parent::tearDown();
    }

    public function test_registration_service_should_create_account_when_payload_is_valid(): void
    {
        // Setup: No existing user
        $this->mockPdo->method('prepare')->willReturn($this->mockStmt);
        $this->mockStmt->method('execute')->willReturn(true);
        $this->mockStmt->method('fetch')->willReturn(false); // No existing user
        $this->mockPdo->method('lastInsertId')->willReturn('1');

        $payload = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'full_name' => 'Test User'
        ];

        $result = $this->service->register($payload);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('testuser', $result['data']['username']);
        $this->assertEquals('test@example.com', $result['data']['email']);
        $this->assertEquals('Test User', $result['data']['full_name']);
    }

    public function test_registration_service_should_reject_registration_when_email_already_exists(): void
    {
        // Setup: Username check returns false, Email check returns true (exists)
        $usernameStmt = $this->createMock(PDOStatement::class);
        $usernameStmt->method('execute')->willReturn(true);
        $usernameStmt->method('fetch')->willReturn(false); // Username not exists

        $emailStmt = $this->createMock(PDOStatement::class);
        $emailStmt->method('execute')->willReturn(true);
        $emailStmt->method('fetch')->willReturn(['user_id' => 1]); // Email exists

        $this->mockPdo->method('prepare')
            ->willReturnCallback(function($sql) use ($usernameStmt, $emailStmt) {
                if (str_contains($sql, 'username')) {
                    return $usernameStmt;
                }
                return $emailStmt;
            });

        $payload = [
            'username' => 'newuser',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'full_name' => 'New User'
        ];

        $result = $this->service->register($payload);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Email', $result['error']);
    }

    public function test_registration_service_should_reject_registration_when_username_already_exists(): void
    {
        // Setup: Username check returns true (exists)
        $this->mockPdo->method('prepare')->willReturn($this->mockStmt);
        $this->mockStmt->method('execute')->willReturn(true);
        $this->mockStmt->method('fetch')->willReturn(['user_id' => 1]); // Username exists

        $payload = [
            'username' => 'existinguser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'full_name' => 'New User'
        ];

        $result = $this->service->register($payload);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Username', $result['error']);
    }

    public function test_registration_service_should_reject_registration_when_required_fields_are_missing_or_invalid(): void
    {
        // Test missing username
        $result = $this->service->register([
            'email' => 'test@example.com',
            'password' => 'password123',
            'full_name' => 'Test User'
        ]);
        $this->assertFalse($result['success']);
        $this->assertEquals('username', $result['field']);

        // Test invalid email format
        $result = $this->service->register([
            'username' => 'testuser',
            'email' => 'invalid-email',
            'password' => 'password123',
            'full_name' => 'Test User'
        ]);
        $this->assertFalse($result['success']);
        $this->assertEquals('email', $result['field']);

        // Test password too short
        $result = $this->service->register([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '123',
            'full_name' => 'Test User'
        ]);
        $this->assertFalse($result['success']);
        $this->assertEquals('password', $result['field']);

        // Test missing full_name
        $result = $this->service->register([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'full_name' => ''
        ]);
        $this->assertFalse($result['success']);
        $this->assertEquals('full_name', $result['field']);
    }
}
