<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicCompliancePagesWebTest extends TestCase
{
    public function test_privacy_policy_is_publicly_accessible(): void
    {
        $this
            ->get('/privacy')
            ->assertOk()
            ->assertSee('Privacy Policy di SpotOn')
            ->assertSee('privacy@spotonapp.cloud');
    }

    public function test_account_deletion_instructions_are_publicly_accessible(): void
    {
        $this
            ->get('/delete-account')
            ->assertOk()
            ->assertSee('Eliminazione account SpotOn')
            ->assertSee('Elimina account');
    }
}
