<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Example Unit Test Suite
 * 
 * Copy this file as template for new test suites.
 * Located in: tests/Unit/ExampleTest.php
 */
class ExampleTest extends TestCase
{
    /**
     * Test basic arithmetic to verify PHPUnit works
     */
    public function testBasicAddition(): void
    {
        $result = 1 + 1;
        $this->assertEquals(2, $result);
    }

    /**
     * Test that setup runs before each test
     */
    public function setUp(): void
    {
        parent::setUp();
        // Initialize test fixtures, mock objects, etc.
    }

    /**
     * Test that teardown runs after each test
     */
    public function tearDown(): void
    {
        // Clean up test data, close connections, etc.
        parent::tearDown();
    }

    /**
     * ✅ GOOD: Descriptive test name following convention
     * test{Component}Should{Behavior}When{Condition}
     */
    public function testUserAuthenticationShouldPassWhenCredentialsAreValid(): void
    {
        // Arrange
        $email = 'test@example.com';
        $password = 'correct_password';

        // Act
        $isAuthenticated = $this->authenticate($email, $password);

        // Assert
        $this->assertTrue($isAuthenticated);
    }

    /**
     * ❌ BAD: Vague test name
     * public function testAuth() {}
     */

    /**
     * Test data provider example
     * 
     * Runs testWithDifferentValues 3 times with different data
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidEmailProvider')]
    public function testValidateEmailShouldFailForInvalidEmails(string $email): void
    {
        $this->assertFalse($this->isValidEmail($email));
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'missing_at_symbol' => ['invalid-email.com'],
            'missing_domain' => ['user@'],
            'empty_string' => [''],
        ];
    }

    /**
     * Helper method: authenticate user
     */
    private function authenticate(string $email, string $password): bool
    {
        // PLACEHOLDER: Implement actual authentication logic
        return $email === 'test@example.com' && $password === 'correct_password';
    }

    /**
     * Helper method: validate email
     */
    private function isValidEmail(string $email): bool
    {
        // PLACEHOLDER: Implement actual email validation
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
