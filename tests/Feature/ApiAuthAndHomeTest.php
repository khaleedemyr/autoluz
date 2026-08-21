<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthAndHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_api_returns_payload(): void
    {
        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonStructure(['featured', 'popular', 'nav', 'locale']);
    }

    public function test_guest_can_register_and_fetch_me(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Andi Motor',
            'username' => 'andimotor',
            'email' => 'andi@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email', 'can_access_seller']]);

        $token = $response->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'andi@example.com');
    }

    public function test_login_returns_token(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_articles_index_is_public(): void
    {
        $this->getJson('/api/v1/articles')->assertOk();
    }

    public function test_legal_faq_is_public(): void
    {
        $this->getJson('/api/v1/legal/faq')
            ->assertOk()
            ->assertJsonStructure(['page']);
    }

    public function test_shop_cart_works_with_guest_token(): void
    {
        $this->withHeaders(['X-Cart-Token' => 'guest-token-1'])
            ->getJson('/api/v1/shop/cart')
            ->assertOk()
            ->assertJsonPath('cart.count', 0);
    }

    public function test_community_feed_is_public(): void
    {
        $this->getJson('/api/v1/community')->assertOk()->assertJsonStructure(['posts']);
    }

    public function test_events_and_brands_are_public(): void
    {
        $this->getJson('/api/v1/events')->assertOk();
        $this->getJson('/api/v1/brands')->assertOk();
        $this->getJson('/api/v1/credit')->assertOk()->assertJsonStructure(['result', 'defaults']);
    }

    public function test_seller_routes_require_auth(): void
    {
        $this->getJson('/api/v1/seller/dashboard')->assertUnauthorized();
    }
}
