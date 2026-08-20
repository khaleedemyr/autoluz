<?php

namespace Tests\Feature;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_start_a_support_conversation(): void
    {
        $this->postJson('/dukungan', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'body' => 'Halo, status pesanan saya bagaimana?',
        ])
            ->assertOk()
            ->assertJsonPath('conversation.status', 'open')
            ->assertJsonPath('message.body', 'Halo, status pesanan saya bagaimana?');

        $this->assertDatabaseHas('support_conversations', [
            'visitor_email' => 'budi@example.com',
            'status' => 'open',
        ]);
    }

    public function test_guest_must_provide_identity_for_first_message(): void
    {
        $this->postJson('/dukungan', [
            'body' => 'Halo',
        ])->assertStatus(422);
    }

    public function test_admin_can_reply_to_support_chat(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $conversation = SupportConversation::query()->create([
            'visitor_name' => 'Budi',
            'visitor_email' => 'budi@example.com',
            'status' => 'open',
            'session_id' => 'test-session',
        ]);

        SupportMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => SupportMessage::SENDER_VISITOR,
            'body' => 'Halo admin',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.support.store', $conversation), [
                'body' => 'Siap, kami cek dulu.',
            ])
            ->assertOk()
            ->assertJsonPath('message.body', 'Siap, kami cek dulu.');
    }
}
