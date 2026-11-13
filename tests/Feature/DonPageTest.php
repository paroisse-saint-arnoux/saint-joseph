<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ManageDon;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DonPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_shows_the_rgive_form_and_the_texts_from_the_migration(): void
    {
        $this->get('/don')->assertOk()
            ->assertSee('data-rgive-src="https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/?type=regular"', false)
            ->assertSee('Bâtir un lieu de foi, de rencontre et d&#039;espérance.', false)
            ->assertSee('Des infrastructures qui ne répondent plus aux besoins')
            ->assertSee('Claire, paroissienne et maman')
            ->assertSee('réduction d\'impôt de 60 %', false)
            ->assertSee('Puis-je bénéficier d&#039;une déduction fiscale ?', false)
            ->assertDontSee('data-don="bandeau-don"', false) // pas d'encart « Faire un don » en bas de page
            ->assertDontSee('SJO2');
    }

    public function test_the_texts_are_editable_in_the_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Editor]));

        Livewire::test(ManageDon::class)
            ->set('data.don.hero.title', 'Donnez pour Saint Joseph.')
            ->set('data.don.faq', [['question' => 'Une seule question ?', 'answer' => 'Oui.']])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('Donnez pour Saint Joseph.', Setting::get('don.hero.title'));
        $this->assertSame('Votre don, soutenu par la fiscalité', Setting::get('don.fiscal.eyebrow')); // le reste est conservé

        $this->get('/don')->assertOk()->assertSee('Donnez pour Saint Joseph.')
            ->assertSee('Une seule question ?')->assertDontSee('Mon don peut-il vraiment faire la différence');
    }
}
