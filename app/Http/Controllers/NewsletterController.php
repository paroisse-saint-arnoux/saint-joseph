<?php

namespace App\Http\Controllers;

use App\Services\Brevo;
use App\Support\SpamGuard;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request, Brevo $brevo)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190'],
        ]);

        // Pas de Turnstile sur ce petit formulaire présent sur chaque page : honeypot + délai suffisent.
        $ok = SpamGuard::passes($request, withTurnstile: false)
            && $brevo->subscribe($data['email'], $data['first_name'], $data['last_name']);

        $message = $ok
            ? 'Merci ! Vous êtes inscrit(e) à notre lettre d\'information.'
            : 'L\'inscription n\'a pas pu aboutir. Merci de réessayer un peu plus tard.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
        }

        return back()->with('newsletter', ['ok' => $ok, 'message' => $message]);
    }
}
