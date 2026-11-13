<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Rgive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RgiveTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://don-nice.catholique.fr/api/campaign-widgets-data/test/';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.rgive.widget_url' => self::URL]);
        Setting::put('home', ['collecte' => ['raised' => 1400000, 'goal' => 6000000]]);
    }

    private function fakeRgive(int $total = 231260): void
    {
        Http::fake([self::URL => Http::response([
            'live_counter' => ['total_amount' => $total, 'total_contributors' => 54, 'progress' => 3],
            'latest_donors' => ['items' => [
                ['amount' => 50, 'currency' => 'EUR', 'firstname' => 'MICHEL', 'city' => 'LA GAUDE', 'type' => 'payment'],
                ['amount' => 5, 'currency' => 'EUR', 'firstname' => 'simone', 'city' => null, 'type' => 'payment'],
                ['amount' => 10, 'currency' => 'EUR', 'firstname' => 'Jean', 'city' => 'Nice', 'type' => 'payment'],
            ]],
        ])]);
    }

    public function test_the_home_page_shows_the_rgive_total_and_the_latest_donors(): void
    {
        $this->fakeRgive();

        $this->get('/')->assertOk()
            ->assertSee('data-raised="231260"', false)
            ->assertSee('231 260 €')
            ->assertSee('Ils viennent de bâtir')
            ->assertSeeInOrder(['Michel', ', de La Gaude,', 'a posé 5 pierres'])
            ->assertSeeInOrder(['Simone', 'a fait un don'])
            ->assertSeeInOrder(['Jean', 'a posé 1 pierre']);
    }

    public function test_the_huile_page_uses_the_rgive_total(): void
    {
        $this->fakeRgive();

        $this->get('/huile')->assertOk()->assertSee('231');
    }

    public function test_figures_are_cached_for_five_minutes(): void
    {
        $this->fakeRgive();
        $rgive = app(Rgive::class);

        $this->assertSame(231260, $rgive->raised());
        $this->assertSame(231260, $rgive->raised());
        Http::assertSentCount(1);

        $this->travel(6)->minutes();
        $rgive->raised();
        Http::assertSentCount(2);
    }

    public function test_the_last_known_figures_are_kept_when_rgive_fails(): void
    {
        $this->fakeRgive(250000);
        $this->assertSame(250000, app(Rgive::class)->raised());

        $this->travel(6)->minutes();
        Http::fake([self::URL => Http::response('Erreur', 500)]);

        $this->assertSame(250000, app(Rgive::class)->raised());
    }

    public function test_the_admin_amount_is_used_when_rgive_never_answered(): void
    {
        Http::fake([self::URL => Http::response(['unexpected' => true])]);

        $this->assertSame(1400000, app(Rgive::class)->raised());
        $this->get('/')->assertOk()->assertSee('data-raised="1400000"', false)->assertDontSee('Ils viennent de bâtir');
    }

    public function test_rgive_can_be_disabled(): void
    {
        config(['services.rgive.widget_url' => '']);
        Http::fake();

        $this->assertSame(1400000, app(Rgive::class)->raised());
        Http::assertNothingSent();
    }
}
