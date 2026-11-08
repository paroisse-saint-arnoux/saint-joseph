<?php

namespace App\Http\Controllers;

use App\Support\Croix;
use App\Support\SpamGuard;
use Illuminate\Http\Request;

/** Page /croix : concours S.O.S Calvaires « Que va devenir la Croix du Pape ? ». */
class CroixController extends Controller
{
    public function show(Request $request)
    {
        Croix::recordVisit($request);

        return view('croix', [
            'state' => Croix::state(),
            'source' => Croix::source($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s.+()-]{8,}$/'],
            'consent_email' => ['accepted'],
            'consent_sms' => [filled($request->input('phone')) ? 'accepted' : 'nullable'],
        ], [
            'email.required' => 'Merci d\'indiquer votre adresse e-mail.',
            'email.email' => 'Cette adresse e-mail ne semble pas complète (exemple : prenom.nom@gmail.com).',
            'phone.regex' => 'Ce numéro de mobile ne semble pas complet (exemple : 06 12 34 56 78).',
            'consent_email.accepted' => 'Merci de cocher la case pour recevoir le lien de vote par e-mail.',
            'consent_sms.accepted' => 'Cochez la case pour recevoir le SMS, ou laissez le numéro de mobile vide.',
        ]);

        if (! SpamGuard::passes($request, withTurnstile: false)) {
            return back()->withInput()->withErrors(['email' => 'Merci de réessayer dans quelques secondes.']);
        }

        Croix::signup($request, $data);

        // Pendant le vote : direct vers le site de vote (en passant par /croix/vote, qui compte le clic).
        if (Croix::state() === 'open') {
            return redirect()->route('croix.vote');
        }

        return redirect()->route('croix')->with('croix_saved', true);
    }

    /** Lien de vote suivi : compte le clic, puis renvoie vers le site de S.O.S Calvaires. À utiliser aussi dans les emails et SMS. */
    public function vote(Request $request)
    {
        Croix::recordVoteClick($request);

        return redirect()->away(Croix::voteUrl());
    }
}
