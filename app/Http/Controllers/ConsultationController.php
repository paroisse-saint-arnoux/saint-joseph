<?php

namespace App\Http\Controllers;

use App\Models\ConsultationProject;
use App\Models\ConsultationVote;
use App\Support\Consultation;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Application mobile de consultation (ouverte par QR code dans l'exposition des maquettes) :
 * email d'abord, puis une page par projet. Chaque réponse est enregistrée aussitôt.
 */
class ConsultationController extends Controller
{
    /** Accueil : demande l'email, ou envoie directement au premier projet sans avis. */
    public function start(Request $request)
    {
        if (! Consultation::isOpen()) {
            return view('consultation.closed');
        }

        $participant = Consultation::participant($request);
        if (! $participant) {
            return view('consultation.email', ['projects' => ConsultationProject::ordered()->get()]);
        }

        $voted = $participant->votes()->whereNotNull('overall')->pluck('project_id');
        $next = ConsultationProject::ordered()->whereNotIn('id', $voted)->first();

        return $next ? redirect()->route('consultation.project', $next) : redirect()->route('consultation.thanks');
    }

    public function identify(Request $request)
    {
        $data = $request->validate(
            ['email' => ['required', 'email', 'max:190']],
            ['email.required' => 'Merci d\'indiquer votre adresse email.', 'email.email' => 'Cette adresse email ne semble pas complète (exemple : prenom.nom@gmail.com).'],
        );

        if (! SpamGuard::passes($request, withTurnstile: false)) {
            return back()->withInput()->withErrors(['email' => 'Merci de réessayer dans quelques secondes.']);
        }

        Consultation::identify($request, $data['email']);

        // Arrivée par le QR code d'une maquette : on revient sur ce projet.
        return redirect()->intended(route('consultation.start'));
    }

    public function show(Request $request, ConsultationProject $project)
    {
        abort_unless($project->is_active, 404);
        if (! Consultation::isOpen()) {
            return view('consultation.closed');
        }

        $participant = Consultation::participant($request);
        if (! $participant) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('consultation.start');
        }

        $projects = ConsultationProject::ordered()->get();
        $index = $projects->search(fn ($p) => $p->is($project));

        return view('consultation.project', [
            'participant' => $participant,
            'project' => $project,
            'projects' => $projects,
            'index' => $index,
            'previous' => $projects[$index - 1] ?? null,
            'next' => $projects[$index + 1] ?? null,
            'vote' => $participant->votes()->firstWhere('project_id', $project->id),
            'voted' => $participant->votes()->whereNotNull('overall')->pluck('project_id')->all(),
        ]);
    }

    /** Enregistrement continu : chaque clic (ou le commentaire, après une courte pause) envoie les champs modifiés. */
    public function save(Request $request, ConsultationProject $project)
    {
        abort_unless($project->is_active, 404);
        $participant = Consultation::participant($request);

        if (! $participant || ! Consultation::isOpen()) {
            $message = $participant ? 'La consultation est close.' : 'Merci d\'indiquer à nouveau votre adresse email.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $message, 'redirect' => route('consultation.start')], 409)
                : redirect()->route('consultation.start');
        }

        $scale = ['nullable', 'integer', Rule::in([-2, -1, 0, 1, 2])];
        $data = $request->validate([
            'overall' => $scale, 'aesthetic' => $scale, 'spiritual' => $scale, 'convivial' => $scale,
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        // Seuls les champs envoyés sont modifiés (le téléphone envoie une réponse à la fois).
        $fields = array_intersect_key($data, array_flip(array_keys($request->only(['overall', 'aesthetic', 'spiritual', 'convivial', 'comment']))));
        if (array_key_exists('comment', $fields)) {
            $fields['comment'] = filled($fields['comment']) ? trim($fields['comment']) : null;
        }

        $vote = ConsultationVote::firstOrNew(['participant_id' => $participant->id, 'project_id' => $project->id]);
        $vote->fill($fields)->save();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        // Sans JavaScript : retour sur la page (les critères apparaissent une fois l'avis donné).
        return $request->boolean('continue') && ($next = ConsultationProject::ordered()->where('position', '>', $project->position)->first())
            ? redirect()->route('consultation.project', $next)
            : redirect()->route('consultation.project', $project)->with('saved', true);
    }

    public function thanks(Request $request)
    {
        $participant = Consultation::participant($request);
        if (! $participant) {
            return redirect()->route('consultation.start');
        }

        return view('consultation.thanks', [
            'participant' => $participant,
            'projects' => ConsultationProject::ordered()->get(),
            'votes' => $participant->votes()->get()->keyBy('project_id'),
        ]);
    }

    /** « Ce n'est pas moi » : téléphone ou tablette partagé(e). */
    public function forget(Request $request)
    {
        Consultation::forget($request);

        return redirect()->route('consultation.start');
    }
}
