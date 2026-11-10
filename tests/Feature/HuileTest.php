<?php

namespace Tests\Feature;

use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HuileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.brevo.key' => 'brevo-key', 'services.brevo.huile_list_id' => 8]);
    }

    private function signup(array $data = [])
    {
        $this->travel(-10)->seconds();
        $t = SpamGuard::timestamp();
        $this->travel(10)->seconds();

        return $this->post('/huile', $data + ['first_name' => ' Marie ', 'last_name' => '', 'email' => 'Marie@Example.com', 'consent' => '1', '_t' => $t]);
    }

    public function test_the_page_is_public_but_not_indexed(): void
    {
        \App\Models\Setting::put('home', ['projet' => ['stats' => [['value' => '8736', 'unit' => 'm²', 'label' => 'Parcelle au Rouret']]]]);

        $this->get('/huile')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('noindex', false)
            ->assertSee('Parcelle au Rouret')->assertSee('7 novembre 2026')->assertSee('Moulin d')
            ->assertSee('Faire un don')
            ->assertSee('Recevez des nouvelles du projet')
            ->assertDontSee('SJO2');

        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/huile');
    }

    public function test_the_form_adds_the_contact_to_brevo_list_8(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);

        $this->signup()->assertRedirect(route('huile').'#nouvelles')->assertSessionHas('huile_saved');
        $this->followingRedirects()->get('/huile')->assertOk();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.brevo.com/v3/contacts'
            && $r['email'] === 'marie@example.com'
            && $r['listIds'] === [8]
            && $r['attributes'] === ['PRENOM' => 'Marie']);
    }

    public function test_the_form_requires_an_email_and_the_consent_and_stops_robots(): void
    {
        Http::fake();

        $this->signup(['email' => ''])->assertSessionHasErrors('email');
        $this->signup(['consent' => null])->assertSessionHasErrors('consent');
        $this->signup(['website' => 'spam'])->assertSessionHasErrors('email');
        Http::assertNothingSent();
    }

    public function test_a_brevo_failure_is_reported_to_the_visitor(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'down'], 500)]);

        $this->signup()->assertSessionHasErrors('email')->assertSessionMissing('huile_saved');
    }
}
