<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChildSafetyWebTest extends TestCase
{
    public function test_child_safety_policy_is_publicly_accessible(): void
    {
        $this
            ->get('/child-safety')
            ->assertOk()
            ->assertSee('Standard di sicurezza dei minori')
            ->assertSee('SpotOn')
            ->assertSee('abuso e lo sfruttamento sessuale di minori')
            ->assertSee('CSAM')
            ->assertSee('Segnalazioni')
            ->assertSee('privacy@spotonapp.cloud')
            ->assertDontSee('/login');
    }

    public function test_child_safety_policy_uses_the_configured_contact(): void
    {
        config()->set('spoton.child_safety.contact_email', 'safety@example.test');

        $this
            ->get('/child-safety')
            ->assertOk()
            ->assertSee('safety@example.test');
    }
}
