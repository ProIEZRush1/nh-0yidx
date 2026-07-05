<?php

namespace Tests\Feature;

use App\Models\BotContact;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_requests_without_the_shared_token(): void
    {
        $response = $this->postJson('/api/wa/inbound', [
            'from' => '5215500000000',
            'text' => 'hola',
        ]);

        $response->assertStatus(401);
    }

    public function test_it_ignores_group_messages(): void
    {
        $response = $this->withHeaders(['x-gateway-token' => config('bot.gateway_token')])
            ->postJson('/api/wa/inbound', [
                'from' => '5215500000000',
                'text' => 'hola',
                'isGroup' => true,
            ]);

        $response->assertOk();
        $this->assertDatabaseCount('bot_contacts', 0);
    }

    public function test_an_inbound_message_creates_a_contact_and_triggers_a_bot_reply(): void
    {
        Http::fake(['*/send' => Http::response(['ok' => true], 200)]);

        Plan::factory()->create(['nombre' => 'Básico', 'activo' => true]);

        $response = $this->withHeaders(['x-gateway-token' => config('bot.gateway_token')])
            ->postJson('/api/wa/inbound', [
                'from' => '5215500000000',
                'fromName' => 'Cliente de prueba',
                'text' => 'hola',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('bot_contacts', [
            'phone' => '5215500000000',
            'name' => 'Cliente de prueba',
            'step' => 'choosing',
        ]);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/send')
                && $request['to'] === '5215500000000'
                && str_contains($request['text'], 'membresías');
        });
    }
}
