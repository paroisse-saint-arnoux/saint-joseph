<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\ManageConsultation;
use App\Filament\Resources\ConsultationProjects\Pages\ListConsultationProjects;
use App\Models\ConsultationParticipant;
use App\Models\ConsultationProject;
use App\Models\ConsultationVote;
use App\Models\Setting;
use App\Models\User;
use App\Support\Consultation;
use App\Support\ConsultationStats;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    private function oldTimestamp(): string
    {
        $this->travel(-10)->seconds();
        $t = SpamGuard::timestamp();
        $this->travelBack();

        return $t;
    }

    private function identify(string $email = 'marie@example.com')
    {
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);

        return $this->post('/consultation', ['email' => $email, '_t' => $this->oldTimestamp()]);
    }

    public function test_the_five_finalists_are_seeded(): void
    {
        $this->assertSame(
            ['Benoît Andrier', 'Jean-Baptiste Duthilleul', 'Fabien Gauret', 'Hélène Houpert', 'Antoine Pélissier'],
            ConsultationProject::ordered()->pluck('architect')->all(),
        );
    }

    public function test_email_is_asked_first_then_first_project_is_shown(): void
    {
        $this->get('/consultation')->assertOk()->assertSee('votre adresse email');
        $first = ConsultationProject::ordered()->first();
        $this->get("/consultation/projet/{$first->id}")->assertRedirect('/consultation');

        // Retour vers le projet du QR code scanné.
        $this->identify(' Marie@Example.com ')->assertRedirect("/consultation/projet/{$first->id}");
        $this->assertDatabaseHas('consultation_participants', ['email' => 'marie@example.com']);

        $this->get("/consultation/projet/{$first->id}")->assertOk()
            ->assertSee($first->name)->assertSee($first->architect)
            ->assertSee('Je n\'aime pas du tout')->assertSee('J\'aime beaucoup')
            ->assertSee('marie@example.com');
    }

    public function test_participant_is_added_to_the_brevo_consultation_list(): void
    {
        $this->identify()->assertRedirect();

        Http::assertSent(fn ($request) => $request['email'] === 'marie@example.com'
            && $request['listIds'] === [6]
            && ! isset($request['attributes']));
        $this->assertNotNull(ConsultationParticipant::first()->brevo_synced_at);

        // Une seule inscription par personne.
        $this->post('/consultation/changer');
        $this->identify();
        Http::assertSentCount(1);
    }

    public function test_bots_and_invalid_emails_are_rejected(): void
    {
        Http::fake();
        $this->post('/consultation', ['email' => 'pas-un-email', '_t' => $this->oldTimestamp()])->assertSessionHasErrors('email');
        $this->post('/consultation', ['email' => 'bot@example.com', '_t' => SpamGuard::timestamp()])->assertSessionHasErrors('email');
        $this->assertSame(0, ConsultationParticipant::count());
        Http::assertNothingSent();
    }

    public function test_answers_are_saved_one_at_a_time(): void
    {
        $this->identify();
        $project = ConsultationProject::ordered()->first();
        $url = "/consultation/projet/{$project->id}";

        $this->postJson($url, ['overall' => 2])->assertOk()->assertJson(['ok' => true]);
        $this->postJson($url, ['aesthetic' => -1])->assertOk();
        $this->postJson($url, ['comment' => '  Très lumineux  '])->assertOk();
        $this->postJson($url, ['spiritual' => 5])->assertStatus(422);

        $vote = ConsultationVote::sole();
        $this->assertSame([2, -1, null, null, 'Très lumineux'], [$vote->overall, $vote->aesthetic, $vote->spiritual, $vote->convivial, $vote->comment]);

        // Modification : la réponse est remplacée, pas dupliquée.
        $this->postJson($url, ['overall' => 0])->assertOk();
        $this->assertSame(0, ConsultationVote::sole()->overall);

        // La page affiche la réponse et les critères.
        $this->get($url)->assertOk()->assertSee('Indifférent, pourquoi pas')->assertSee('Esthétique')->assertSee('Très lumineux');
    }

    public function test_continuous_saving_does_not_block_other_forms(): void
    {
        $this->identify();
        $project = ConsultationProject::ordered()->first();
        for ($i = 0; $i < 30; $i++) {
            $this->postJson("/consultation/projet/{$project->id}", ['overall' => $i % 5 - 2])->assertOk();
        }

        // Les autres formulaires (ici la newsletter, limite de 10) ont leur propre compteur : 422 (champs manquants), pas 429.
        $this->postJson('/newsletter')->assertStatus(422);
        // Plusieurs personnes peuvent s'identifier depuis le même Wi-Fi.
        for ($i = 0; $i < 25; $i++) {
            $this->post('/consultation/changer');
            $this->identify("personne{$i}@example.com")->assertRedirect();
        }
    }

    public function test_saving_requires_an_identified_person(): void
    {
        $project = ConsultationProject::ordered()->first();
        $this->postJson("/consultation/projet/{$project->id}", ['overall' => 1])->assertStatus(409);
        $this->assertSame(0, ConsultationVote::count());
    }

    public function test_returning_with_the_cookie_restores_the_person(): void
    {
        $participant = ConsultationParticipant::create(['email' => 'paul@example.com', 'token' => 'jeton-paul']);
        $project = ConsultationProject::ordered()->first();

        $this->withCookie(Consultation::COOKIE, 'jeton-paul')
            ->get("/consultation/projet/{$project->id}")
            ->assertOk()->assertSee('paul@example.com');
        $this->assertTrue($participant->is(ConsultationParticipant::first()));
    }

    public function test_start_goes_to_the_next_project_without_answer_then_to_thanks(): void
    {
        $this->identify();
        $projects = ConsultationProject::ordered()->get();
        $this->postJson("/consultation/projet/{$projects[0]->id}", ['overall' => 1]);
        $this->get('/consultation')->assertRedirect("/consultation/projet/{$projects[1]->id}");

        foreach ($projects as $project) {
            $this->postJson("/consultation/projet/{$project->id}", ['overall' => -1]);
        }
        $this->get('/consultation')->assertRedirect('/consultation/merci');
        $this->get('/consultation/merci')->assertOk()->assertSee('Merci pour votre avis')->assertSee('Plutôt pas');
    }

    public function test_closed_consultation_refuses_answers(): void
    {
        $this->identify();
        Setting::put('consultation', ['open' => false]);
        $project = ConsultationProject::ordered()->first();

        $this->get('/consultation')->assertOk()->assertSee('La consultation est terminée');
        $this->postJson("/consultation/projet/{$project->id}", ['overall' => 2])->assertStatus(409);
        $this->assertSame(0, ConsultationVote::count());
    }

    public function test_admin_page_shows_statistics_and_exports_excel(): void
    {
        $projects = ConsultationProject::ordered()->get();
        foreach (['a@example.com' => [2, 1], 'b@example.com' => [-2, 1]] as $email => [$first, $second]) {
            $p = ConsultationParticipant::create(['email' => $email, 'token' => $email]);
            ConsultationVote::create(['participant_id' => $p->id, 'project_id' => $projects[0]->id, 'overall' => $first, 'aesthetic' => 1, 'comment' => "Avis de {$email}"]);
            ConsultationVote::create(['participant_id' => $p->id, 'project_id' => $projects[1]->id, 'overall' => $second]);
        }

        $stats = ConsultationStats::projects()->keyBy(fn ($row) => $row['project']->id);
        $this->assertSame(0.0, (float) $stats[$projects[0]->id]['average']);
        $this->assertSame(50, $stats[$projects[0]->id]['favorable']);
        $this->assertSame(1, $stats[$projects[0]->id]['distribution'][-2]);
        $this->assertSame(['participants' => 2, 'voters' => 2, 'complete' => 0, 'votes' => 4, 'comments' => 2], ConsultationStats::totals());

        $this->actingAs(User::factory()->create(['role' => Role::Editor]));
        $this->get('/admin/consultation')->assertOk()->assertSee('Résultats par projet')->assertSee('Avis de a@example.com')->assertSee('<svg', false);

        Livewire::test(ManageConsultation::class)->callAction('export')->assertFileDownloaded('consultation-projets-'.now()->format('Y-m-d').'.xlsx');

        $reader = new Reader;
        $reader->open($path = ConsultationStats::export());
        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            $sheets[$sheet->getName()] = $rows;
        }
        $reader->close();
        unlink($path);

        $this->assertSame(['Synthèse', 'Réponses', 'Participants'], array_keys($sheets));
        $this->assertCount(6, $sheets['Synthèse']);
        $this->assertCount(5, $sheets['Réponses']);
        $this->assertSame('a@example.com', $sheets['Réponses'][1][1]);
        $this->assertSame('Avis de a@example.com', $sheets['Réponses'][1][9]);
    }

    public function test_admin_can_close_the_consultation_and_download_posters(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Editor]));

        Livewire::test(ManageConsultation::class)->callAction('toggle');
        $this->assertFalse(Consultation::isOpen());

        Livewire::test(ListConsultationProjects::class)->callAction('posters')
            ->assertFileDownloaded('affichettes-consultation.pdf');
    }
}
