<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_privacy_policy_page_is_available(): void
    {
        $this->get('/kebijakan-privasi')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Legal/Privacy')
                ->has('page.title')
                ->has('page.sections'));
    }

    public function test_faq_page_is_available(): void
    {
        $this->get('/faq')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Legal/Faq')
                ->has('page.title')
                ->has('page.groups'));
    }
}
