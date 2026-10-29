<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Mail\MagicLinkMail;
use App\Models\User;
use App\Support\MagicLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    private function sendAndGetUrl(User $user): string
    {
        Mail::fake();
        MagicLink::send($user);
        $url = null;
        Mail::assertSent(MagicLinkMail::class, function (MagicLinkMail $mail) use (&$url) {
            $url = $mail->url;

            return true;
        });

        return $url;
    }

    public function test_link_logs_in_once(): void
    {
        $user = User::factory()->create();
        $url = $this->sendAndGetUrl($user);

        // La page s'envoie seule en JS ; une simple ouverture sans JS (antivirus de messagerie) ne connecte pas.
        $this->get($url)->assertOk()->assertSee("getElementById('magic-link-form').submit()", false);
        $this->assertGuest();

        $this->post($url)->assertRedirect();
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $this->post($url)->assertRedirect(filament()->getLoginUrl());
        $this->assertGuest();
    }

    public function test_tampered_link_is_rejected(): void
    {
        $url = $this->sendAndGetUrl(User::factory()->create());

        $this->post($url.'x')->assertForbidden();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create();
        $url = $this->sendAndGetUrl($user);
        $user->update(['is_active' => false]);

        $this->post($url);
        $this->assertGuest();
    }

    public function test_admin_visit_renews_remember_cookie_for_400_days(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));
        $this->assertNotNull($cookie);
        $this->assertGreaterThan(now()->addDays(399)->timestamp, $cookie->getExpiresTime());
    }

    public function test_new_user_receives_an_invitation_link(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Marie', 'email' => 'marie@example.org', 'role' => 'editor', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        Mail::assertSent(MagicLinkMail::class, fn (MagicLinkMail $mail) => $mail->hasTo('marie@example.org') && $mail->invitation);
    }
}
