<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;

class RegistrationTask00001Test extends TestCase
{
    public function test_registration_service_should_create_account_when_payload_is_valid(): void
    {
        $this->fail(
            'TDD Red: can implement contract App\\Auth\\RegistrationService::register(array $payload): array ' .
            'to pass valid registration flow (create account successfully).'
        );
    }

    public function test_registration_service_should_reject_registration_when_email_already_exists(): void
    {
        $this->fail(
            'TDD Red: can implement duplicate email detection in registration flow (reject and return clear error).'
        );
    }

    public function test_registration_service_should_reject_registration_when_username_already_exists(): void
    {
        $this->fail(
            'TDD Red: can implement duplicate username detection in registration flow (reject and return clear error).'
        );
    }

    public function test_registration_service_should_reject_registration_when_required_fields_are_missing_or_invalid(): void
    {
        $this->fail(
            'TDD Red: can implement input validation for required fields and email format in registration flow.'
        );
    }
}
