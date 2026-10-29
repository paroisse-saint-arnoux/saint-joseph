<?php

namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use App\Models\User;
use App\Support\MagicLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

        // L'ouverture du lien (ex. antivirus de la messagerie) ne connecte pas.
        $this->get($url)->assertOk()->assertSee('Me connecter');
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
}
