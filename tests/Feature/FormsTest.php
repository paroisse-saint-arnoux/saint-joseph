<?php

namespace Tests\Feature;

use App\Mail\ContactMail;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormsTest extends TestCase
{
    use RefreshDatabase;

    private function oldTimestamp(): string
    {
        $this->travel(-10)->seconds();
        $t = SpamGuard::timestamp();
        $this->travelBack();

        return $t;
    }

    public function test_newsletter_subscribes_contact_to_brevo_list(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);

        $this->postJson('/newsletter', [
            'first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'marie@example.com', '_t' => $this->oldTimestamp(),
        ])->assertOk()->assertJsonPath('ok', true);

        Http::assertSent(fn ($request) => $request['email'] === 'marie@example.com'
            && $request['attributes'] === ['PRENOM' => 'Marie', 'NOM' => 'Martin']
            && $request['listIds'] === [2]
            && $request->hasHeader('api-key', 'test-key'));
    }

    public function test_newsletter_rejects_bots(): void
    {
        Http::fake();

        // Pot de miel rempli
        $this->postJson('/newsletter', [
            'first_name' => 'Bot', 'last_name' => 'Bot', 'email' => 'bot@example.com', 'website' => 'spam', '_t' => $this->oldTimestamp(),
        ])->assertStatus(422);

        // Formulaire envoyé trop vite
        $this->postJson('/newsletter', [
            'first_name' => 'Bot', 'last_name' => 'Bot', 'email' => 'bot@example.com', '_t' => SpamGuard::timestamp(),
        ])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_contact_form_sends_email_to_team(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Jean Dupont', 'email' => 'jean@example.com', 'subject' => 'Bénévolat', 'message' => 'Je souhaite aider.', '_t' => $this->oldTimestamp(),
        ])->assertRedirect('/contact')->assertSessionHas('contact-sent');

        Mail::assertSent(ContactMail::class, fn (ContactMail $mail) => $mail->hasTo(config('services.contact.email'))
            && $mail->hasReplyTo('jean@example.com'));
    }

    public function test_contact_form_blocks_honeypot(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Spam', 'website' => 'http://spam', '_t' => $this->oldTimestamp(),
        ])->assertSessionHasErrors('message');

        Mail::assertNothingSent();
    }
}
