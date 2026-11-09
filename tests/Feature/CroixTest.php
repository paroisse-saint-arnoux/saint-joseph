<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ManageBrevo;
use App\Filament\Pages\ManageCroix;
use App\Filament\Widgets\Campaigns;
use App\Models\CroixVisitor;
use App\Models\User;
use App\Support\Croix;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CroixTest extends TestCase
{
    use RefreshDatabase;

    private const BEFORE = '2026-11-08 18:00';

    private const OPEN = '2026-11-10 09:00';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.brevo.key' => 'brevo-key', 'services.brevo.croix_list_id' => 7]);
    }

    private function signup(array $data = [])
    {
        $this->travel(-10)->seconds();
        $t = SpamGuard::timestamp();
        $this->travel(10)->seconds();

        return $this->post('/croix', $data + [
            'first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'Marie@Example.com', 'phone' => '06 12 34 56 78',
            'consent_email' => '1', 'consent_sms' => '1', 'source' => 'affiche', '_t' => $t,
        ]);
    }

    public function test_before_the_vote_the_page_collects_contacts_and_sends_them_to_brevo(): void
    {
        $this->travelTo(Carbon::parse(self::BEFORE));
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);

        $this->get('/croix?utm_source=affiche&utm_medium=print&utm_campaign=croix')->assertOk()
            ->assertSee('Recevez le lien de vote')
            ->assertDontSee('Aller sur la page de vote sans')->assertDontSee('Scannez avec votre téléphone')
            ->assertDontSee('SJO2')
            ->assertDontSee('Saint-Joseph II');
        $this->assertDatabaseHas('croix_visitors', ['source' => 'affiche', 'visits' => 1]);

        $this->signup()->assertRedirect('/croix');
        $this->followingRedirects()->get('/croix')->assertOk();

        // Rien n'est gardé sur le site : seul le visiteur anonyme est marqué.
        $this->assertFalse(Schema::hasTable('croix_signups'));
        $this->assertNotNull(CroixVisitor::whereNotNull('signed_up_at')->first());

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.brevo.com/v3/contacts'
            && $r['email'] === 'marie@example.com'
            && $r['listIds'] === [7]
            && $r['attributes'] === ['PRENOM' => 'Marie', 'NOM' => 'Martin', 'SMS' => '+33612345678']
            && $r->header('api-key') === ['brevo-key']);
    }

    public function test_a_mobile_number_requires_the_sms_consent(): void
    {
        $this->travelTo(Carbon::parse(self::BEFORE));
        Http::fake();

        $this->signup(['consent_sms' => null])->assertSessionHasErrors('consent_sms');
        $this->signup(['consent_email' => null])->assertSessionHasErrors('consent_email');
        Http::assertNothingSent();

        $this->signup(['phone' => '', 'consent_sms' => null])->assertSessionHasNoErrors();
        Http::assertSent(fn (Request $r) => ! isset($r['attributes']['SMS']));
    }

    public function test_phone_numbers_are_sent_to_brevo_in_international_format(): void
    {
        $this->assertSame('+33612345678', Croix::normalizePhone('6.12.34.56.78'));
        $this->assertSame('+33612345678', Croix::normalizePhone('+33 (0)6 12 34 56 78'));
        $this->assertSame('+33612345678', Croix::normalizePhone('0033 6 12 34 56 78'));
        $this->assertSame('+590690123456', Croix::normalizePhone('0690 12 34 56')); // Guadeloupe
        $this->assertSame('+32470123456', Croix::normalizePhone('+32 470 12 34 56'));
        $this->assertNull(Croix::normalizePhone('06 12 34'));
        $this->assertNull(Croix::normalizePhone(''));

        $this->travelTo(Carbon::parse(self::BEFORE));
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);

        $this->signup(['phone' => '06 12 34'])->assertSessionHasErrors('phone');
        $this->signup(['phone' => '6 12 34 56 78'])->assertSessionHasNoErrors();
        Http::assertSent(fn (Request $r) => ($r['attributes']['SMS'] ?? null) === '+33612345678');
    }

    public function test_brevo_rejecting_the_phone_number_retries_without_it(): void
    {
        $this->travelTo(Carbon::parse(self::BEFORE));
        Http::fakeSequence('api.brevo.com/*')
            ->push(['code' => 'duplicate_parameter', 'message' => 'SMS is already associated with another Contact'], 400)
            ->push(['id' => 1], 201);

        $this->signup()->assertRedirect('/croix');

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => ! isset($r['attributes']['SMS']));
        $this->assertNotNull(CroixVisitor::whereNotNull('signed_up_at')->first());
    }

    public function test_before_the_vote_a_brevo_failure_is_shown_to_the_visitor(): void
    {
        $this->travelTo(Carbon::parse(self::BEFORE));
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Erreur'], 500)]);

        $this->signup(['phone' => ''])->assertSessionHasErrors('email');
        $this->assertNull(CroixVisitor::whereNotNull('signed_up_at')->first());
    }

    public function test_bots_and_link_previews_are_not_counted(): void
    {
        $this->withHeader('User-Agent', 'WhatsApp/2.23.20 A')->get('/croix')->assertOk();
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/croix')->assertOk();

        $this->assertSame(0, CroixVisitor::count());
    }

    public function test_during_the_vote_the_form_redirects_to_the_vote_site_and_clicks_are_counted(): void
    {
        $this->travelTo(Carbon::parse(self::OPEN));
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);

        $this->get('/croix')->assertOk()->assertSee('Aller sur la page de vote sans')->assertSee('Scannez avec votre téléphone')->assertSee('Bon à savoir avant de voter')->assertDontSee('Je vote maintenant')->assertSee(route('croix.vote'));

        $this->signup()->assertRedirect(route('croix.vote'));
        $this->get('/croix/vote?utm_source=brevo')->assertRedirect('https://vote.soscalvaires.org/');

        $funnel = Croix::funnel()['total'];
        $this->assertSame(1, $funnel['visitors']);
        $this->assertSame(1, $funnel['signups']);
        $this->assertSame(1, $funnel['votes']); // le lien direct, sans cookie, compte un nouveau navigateur
        $this->assertSame(1, $funnel['direct_votes']);
    }

    public function test_home_banner_is_shown_only_during_the_vote(): void
    {
        $this->travelTo(Carbon::parse(self::BEFORE));
        $this->get('/')->assertOk()->assertDontSee('top-banner');

        $this->travelTo(Carbon::parse(self::OPEN));
        $this->get('/')->assertOk()->assertSee('La Croix du Pape au Rouret ?')->assertSee('href="'.route('croix').'"', false);

        $this->travelTo(Carbon::parse('2026-12-01 08:00'));
        $this->get('/')->assertOk()->assertDontSee('top-banner');
        $this->get('/croix')->assertOk()->assertSee('Le vote est clos');
    }

    public function test_admin_pages_show_contest_and_brevo_statistics(): void
    {
        $this->travelTo(Carbon::parse(self::OPEN));
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        CroixVisitor::create(['token' => 'a', 'source' => 'messe', 'visits' => 3, 'visited_at' => now(), 'signed_up_at' => now()]);
        config(['services.matomo' => ['url' => 'https://matomo.test/', 'site_id' => 3, 'token' => 'secret']]);

        Http::fake([
            'api.brevo.com/v3/contacts/lists*' => Http::response(['lists' => [
                ['id' => 2, 'name' => 'Newsletter', 'uniqueSubscribers' => 120, 'totalBlacklisted' => 3],
                ['id' => 7, 'name' => 'Croix du Pape', 'uniqueSubscribers' => 40, 'totalBlacklisted' => 0],
            ], 'count' => 2]),
            'api.brevo.com/v3/contacts*' => Http::response(['contacts' => [
                ['email' => 'a@example.com', 'createdAt' => now()->subDays(2)->toIso8601String(), 'listIds' => [7]],
                ['email' => 'b@example.com', 'createdAt' => now()->subDays(20)->toIso8601String(), 'listIds' => [2, 7]],
            ], 'count' => 2]),
            'api.brevo.com/v3/emailCampaigns*' => Http::response(['campaigns' => [[
                'id' => 12, 'name' => 'Lettre de novembre', 'subject' => 'Des nouvelles du chantier', 'sentDate' => now()->subDays(3)->toIso8601String(),
                'statistics' => ['globalStats' => ['sent' => 100, 'delivered' => 98, 'uniqueViews' => 45, 'uniqueClicks' => 12, 'unsubscriptions' => 1]],
            ]]]),
            'api.brevo.com/v3/smtp/statistics/reports*' => Http::response(['reports' => [
                ['date' => now()->toDateString(), 'requests' => 9, 'delivered' => 9, 'uniqueOpens' => 4],
            ]]),
            'matomo.test/*' => fn (Request $r) => match ($r['method']) {
                'Referrers.getCampaigns' => Http::response([['label' => 'lettre de novembre', 'nb_visits' => 31, 'nb_actions' => 70]]),
                'MarketingCampaignsReporting.getSourceMedium' => Http::response([
                    ['label' => 'brevo - email', 'nb_visits' => 25, 'subtable' => [['label' => 'lettre de novembre', 'nb_visits' => 25]]],
                    ['label' => 'whatsapp - partage', 'nb_visits' => 6, 'subtable' => [['label' => 'Lettre de novembre', 'nb_visits' => 6]]],
                ]),
            },
        ]);

        $this->get('/admin/croix')->assertOk()->assertSee('Le vote est ouvert')->assertSee('messe')->assertDontSee('Liens à diffuser')->assertSee('contacts dans la liste Brevo');
        $this->get('/admin/brevo')->assertOk()
            ->assertSee('Croix du Pape')->assertSee('+2')     // 2 nouveaux contacts en 30 jours dans la liste 7
            ->assertSee('Lettre de novembre')->assertSee('45,9 %'); // 45 ouvertures / 98 délivrés

        Livewire::test(Campaigns::class)->assertSee('lettre de novembre')->assertSee('31')->assertSee('98 délivrés')
            ->assertSee('Sources : brevo (25) · whatsapp (6)')->assertDontSee('Marketing Campaigns Reporting');
        Livewire::test(ManageCroix::class)->callAction('settings', ['opens_at' => '2026-11-09 08:00', 'closes_at' => '2026-11-30 23:59', 'vote_url' => 'https://vote.soscalvaires.org/', 'home_banner' => false]);
        $this->assertFalse(Croix::showHomeBanner());
        Livewire::test(ManageBrevo::class)->callAction('refresh')->assertOk();
    }

    public function test_campaign_sources_need_the_matomo_extension(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        config(['services.matomo' => ['url' => 'https://matomo.test/', 'site_id' => 3, 'token' => 'secret'], 'services.brevo.key' => null]);
        Http::fake(fn (Request $r) => match ($r['method']) {
            'Referrers.getCampaigns' => Http::response([['label' => 'croix', 'nb_visits' => 24, 'nb_actions' => 40, 'subtable' => [['label' => 'com.google.android.gm', 'nb_visits' => 17]]]]),
            default => Http::response(['result' => 'error', 'message' => 'The plugin MarketingCampaignsReporting was not found.']),
        });
        Exceptions::fake();

        Livewire::test(Campaigns::class)->assertSee('croix')->assertSee('Marketing Campaigns Reporting')->assertDontSee('com.google.android.gm');
        Livewire::test(Campaigns::class);

        Exceptions::assertNothingReported();
        Http::assertSentCount(2); // une fois chacun : campagnes en cache, extension absente retenue
    }
}
