<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\CredentialValidationService;
use PHPUnit\Framework\TestCase;

class ValidationTask00016Test extends TestCase
{
    public function test_credential_validation_service_should_fail_when_password_policy_is_violated(): void
    {
        $service = new CredentialValidationService();

        $result = $service->validate([
            'username' => 'alice',
            'password' => 'short',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    public function test_credential_validation_service_should_validate_only_username_and_password_when_other_fields_are_present(): void
    {
        $service = new CredentialValidationService();

        $result = $service->validate([
            'username' => 'alice',
            'password' => 'ValidPass1',
            'email' => 'invalid-email-format',
            'display_name' => 'Alice Nguyen',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }
}