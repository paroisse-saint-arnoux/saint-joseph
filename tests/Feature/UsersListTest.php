<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Tests\TestCase;

class UsersListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_visits_record_the_last_connection_at_most_every_five_minutes(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $first = $admin->refresh()->last_seen_at;
        $this->assertNotNull($first);

        $this->travel(2)->minutes();
        $this->get('/admin');
        $this->assertTrue($first->equalTo($admin->refresh()->last_seen_at));

        $this->travel(4)->minutes();
        $this->get('/admin');
        $this->assertTrue($admin->refresh()->last_seen_at->gt($first));
    }

    public function test_claude_connector_use_is_recorded_and_shown(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'name' => 'Admin']);
        $editor = User::factory()->create(['name' => 'Marie']);

        Passport::actingAs($editor, ['mcp:use']);
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertOk();
        $this->assertNotNull($editor->refresh()->last_mcp_at);

        // Plus de jeton actif (Passport::actingAs n'en crée pas en base) : utilisé, mais déconnecté.
        $this->actingAs($admin);
        Livewire::test(ListUsers::class)
            ->assertSee(['Dernière connexion', 'Claude', 'Déconnecté', 'Jamais']);
    }
}
