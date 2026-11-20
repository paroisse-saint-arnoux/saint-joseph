<?php

namespace Tests\Feature;

use App\Enums\OilOrderStatus;
use App\Enums\Role;
use App\Filament\Resources\OilOrders\Pages\ManageOilOrders;
use App\Mail\OilOrderMail;
use App\Models\OilOrder;
use App\Models\OilProduct;
use App\Models\Setting;
use App\Models\User;
use App\Support\OilOrders;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OilOrderTest extends TestCase
{
    use RefreshDatabase;

    private OilProduct $small;

    private OilProduct $medium;

    private OilProduct $large;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.brevo.key' => 'brevo-key', 'services.brevo.huile_order_list_id' => 9]);
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);
        Mail::fake();
        [$this->small, $this->medium, $this->large] = OilProduct::ordered()->get()->all();
    }

    private function order(array $quantities, array $data = [])
    {
        $this->travel(-10)->seconds();
        $t = SpamGuard::timestamp();
        $this->travel(10)->seconds();

        return $this->post('/commande-huile', $data + [
            'quantities' => $quantities,
            'first_name' => ' Marie ', 'last_name' => 'Martin', 'phone' => '06 12 34 56 78', 'email' => 'Marie@Example.com', '_t' => $t,
        ]);
    }

    public function test_the_stock_set_by_the_migration(): void
    {
        $this->assertSame([['250 mL', 1700, 50], ['500 mL', 3000, 100], ['750 mL', 4000, 20]],
            OilProduct::ordered()->get()->map(fn ($p) => [$p->label, $p->price_cents, $p->stock])->all());
    }

    public function test_the_page_is_public_but_not_indexed(): void
    {
        $this->get('/commande-huile')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('noindex', false)
            ->assertSee('250 mL')->assertSee("17\u{00A0}€")->assertSee("30\u{00A0}€")->assertSee("40\u{00A0}€")
            ->assertSee('kermesse du dimanche 29 novembre 2026, salle Charvet')
            ->assertSee('Réserver mes bouteilles')
            ->assertDontSee('SJO2');

        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/commande-huile');
    }

    public function test_an_order_is_saved_the_stock_decreases_and_an_email_is_sent(): void
    {
        $this->order([$this->small->id => 2, $this->large->id => 1, $this->medium->id => 0])
            ->assertRedirect(route('huile.order').'#commande')->assertSessionHasNoErrors();

        $order = OilOrder::with('items')->sole();
        $this->assertSame('H-001', $order->number);
        $this->assertSame('Marie', $order->first_name);
        $this->assertSame('marie@example.com', $order->email);
        $this->assertSame('+33612345678', $order->phone);
        $this->assertSame('06 12 34 56 78', $order->phoneForDisplay());
        $this->assertSame(2 * 1700 + 4000, $order->total_cents);
        $this->assertSame(OilOrderStatus::Reserved, $order->status);
        $this->assertSame('2 × 250 mL, 1 × 750 mL', $order->summary());

        $this->assertSame(48, $this->small->fresh()->stock);
        $this->assertSame(100, $this->medium->fresh()->stock);
        $this->assertSame(19, $this->large->fresh()->stock);

        Mail::assertSent(OilOrderMail::class, fn (OilOrderMail $mail) => $mail->hasTo('marie@example.com') && $mail->order->is($order));
        $this->assertNotNull($order->fresh()->confirmation_sent_at);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.brevo.com/v3/contacts'
            && $r['email'] === 'marie@example.com' && $r['listIds'] === [9]
            && $r['attributes'] === ['PRENOM' => 'Marie', 'NOM' => 'Martin', 'SMS' => '+33612345678']);
        $this->assertNotNull($order->fresh()->brevo_synced_at);

        // La confirmation reste affichée, avec le numéro de commande.
        $this->get('/commande-huile')->assertOk()->assertSee('Merci Marie')->assertSee('H-001')->assertSee("74\u{00A0}€");
        $this->get('/commande-huile?nouvelle=1')->assertOk()->assertDontSee('H-001');
    }

    public function test_the_email_contains_the_recap_and_the_number(): void
    {
        $order = OilOrders::place(['first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'm@example.com', 'phone' => '+33612345678'], [$this->medium->id => 3]);

        (new OilOrderMail($order->load('items')))
            ->assertHasSubject('Votre réservation d\'huile d\'olive n° H-001')
            ->assertSeeInHtml('H-001')->assertSeeInHtml('500 mL')->assertSeeInHtml('90')
            ->assertSeeInHtml('salle Charvet');
    }

    public function test_at_most_five_bottles_of_each_size_and_at_least_one(): void
    {
        $this->order([$this->small->id => 6])->assertSessionHasErrors('quantities.'.$this->small->id);
        $this->order([$this->small->id => 0])->assertSessionHasErrors('quantities');
        $this->order([$this->small->id => -1])->assertSessionHasErrors('quantities.'.$this->small->id);
        $this->assertSame(0, OilOrder::count());
        Mail::assertNothingSent();
    }

    public function test_the_stock_limits_the_order(): void
    {
        $this->large->update(['stock' => 2]);

        $this->order([$this->large->id => 3])->assertSessionHasErrors('quantities.'.$this->large->id);
        $this->assertSame(2, $this->large->fresh()->stock);
        $this->assertSame(0, OilOrder::count());

        $this->get('/commande-huile')->assertSee('Plus que 2');
        $this->large->update(['stock' => 0]);
        $this->get('/commande-huile')->assertSee('Épuisé');

        OilProduct::query()->update(['stock' => 0]);
        $this->get('/commande-huile')->assertSee('Toutes les bouteilles ont trouvé preneur')->assertDontSee('Réserver mes bouteilles');
    }

    public function test_contact_details_are_required_and_robots_stopped(): void
    {
        $q = [$this->small->id => 1];
        $this->order($q, ['first_name' => ''])->assertSessionHasErrors('first_name');
        $this->order($q, ['last_name' => ''])->assertSessionHasErrors('last_name');
        $this->order($q, ['phone' => ''])->assertSessionHasErrors('phone');
        $this->order($q, ['phone' => '12'])->assertSessionHasErrors('phone');
        $this->order($q, ['email' => 'nope'])->assertSessionHasErrors('email');
        $this->order($q, ['website' => 'spam'])->assertSessionHasErrors('email');
        $this->assertSame(0, OilOrder::count());
        $this->assertSame(50, $this->small->fresh()->stock);
    }

    public function test_closed_orders(): void
    {
        Setting::put('commande_huile', [...OilOrders::settings(), 'open' => false]);

        $this->get('/commande-huile')->assertOk()->assertSee('Les réservations sont closes')->assertDontSee('Réserver mes bouteilles');
        $this->get('/huile')->assertOk()->assertDontSee('/commande-huile');
        $this->order([$this->small->id => 1]);
        $this->assertSame(0, OilOrder::count());
    }

    public function test_the_huile_page_shows_the_harvest_and_links_to_the_order_page(): void
    {
        $this->get('/huile')->assertOk()
            ->assertSee('images/huile/recolte-01.webp')->assertSee('images/huile/recolte-13.webp')
            ->assertSee(route('huile.order'));
    }

    public function test_cancel_restocks_and_reopen_takes_the_bottles_again(): void
    {
        $order = OilOrders::place(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com', 'phone' => '+33612345678'], [$this->large->id => 5]);
        $this->assertSame(15, $this->large->fresh()->stock);
        // En stock = initial − retirées (les 5 à retirer sont encore là) ; après les commandes : 15.
        $row = OilOrders::stockSummary()->firstWhere('product.id', $this->large->id);
        $this->assertSame([20, 15, 5, 0], [$row['on_hand'], $row['stock'], $row['reserved'], $row['collected']]);

        OilOrders::cancel($order);
        $this->assertSame(OilOrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(20, $this->large->fresh()->stock);

        OilOrders::reopen($order->fresh());
        $this->assertSame(OilOrderStatus::Reserved, $order->fresh()->status);
        $this->assertSame(15, $this->large->fresh()->stock);

        OilOrders::collect($order->fresh());
        $this->assertSame(OilOrderStatus::Collected, $order->fresh()->status);
        $this->assertSame(15, $this->large->fresh()->stock);

        $summary = OilOrders::stockSummary()->firstWhere('product.id', $this->large->id);
        $this->assertSame([15, 15, 0, 5], [$summary['on_hand'], $summary['stock'], $summary['reserved'], $summary['collected']]);
    }

    public function test_the_admin_page_lists_orders_and_exports_them(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Editor]));
        $order = OilOrders::place(['first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'm@example.com', 'phone' => '+33612345678'], [$this->small->id => 1]);

        $this->get(ManageOilOrders::getUrl())->assertOk()->assertSee('Commandes d\'huile')->assertSee('H-001')->assertSee('Marie Martin');

        $other = OilOrders::place(['first_name' => 'Paul', 'last_name' => 'Durand', 'email' => 'p@example.com', 'phone' => '+33612345679'], [$this->medium->id => 2]);

        Livewire::test(ManageOilOrders::class)
            ->assertCanSeeTableRecords([$order, $other])
            ->assertTableColumnStateSet('bottles_'.$this->medium->id, 2, $other)
            ->assertTableColumnStateSet('bottles_'.$this->small->id, '·', $other)
            ->callTableAction('collect', $other)
            ->callTableAction('cancel', $order)
            ->callAction('stocks', ['add_'.$this->small->id => -42, 'add_'.$this->medium->id => 5, 'add_'.$this->large->id => 0]);

        $this->assertSame(OilOrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(OilOrderStatus::Collected, $other->fresh()->status);
        // 50 − 1 annulée remise en stock = 50 ; − 42 → 8. 100 − 2 retirées = 98 ; + 5 → 103.
        $this->assertSame([8, 103, 20], OilProduct::ordered()->pluck('stock')->all());

        // On ne peut pas enlever des bouteilles réservées : 3 à retirer sur 8 → 5 au plus.
        OilOrders::place(['first_name' => 'Léa', 'last_name' => 'Petit', 'email' => 'l@example.com', 'phone' => '+33612345670'], [$this->small->id => 3]);
        Livewire::test(ManageOilOrders::class)
            ->callAction('stocks', ['add_'.$this->small->id => -6, 'add_'.$this->medium->id => 0, 'add_'.$this->large->id => 0])
            ->assertHasActionErrors(['add_'.$this->small->id => 'min']);
        Livewire::test(ManageOilOrders::class)
            ->callAction('stocks', ['add_'.$this->small->id => -5, 'add_'.$this->medium->id => 0, 'add_'.$this->large->id => 0])
            ->assertHasNoActionErrors();
        $this->assertSame(0, $this->small->fresh()->stock);

        $path = OilOrders::export();
        $this->assertFileExists($path);
        $this->assertGreaterThan(1000, filesize($path));
        unlink($path);
    }
}
