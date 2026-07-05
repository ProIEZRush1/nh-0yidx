<?php

namespace Tests\Feature;

use App\Models\BotContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotContactCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_contact_step_can_be_reset_from_the_panel(): void
    {
        $user = User::factory()->create();
        $contact = BotContact::factory()->create(['step' => 'human']);

        $response = $this->actingAs($user)->put(route('contactos.update', $contact), [
            'name' => $contact->name,
            'step' => 'new',
        ]);

        $response->assertRedirect(route('contactos.index'));

        $this->assertDatabaseHas('bot_contacts', ['id' => $contact->id, 'step' => 'new']);
    }
}
