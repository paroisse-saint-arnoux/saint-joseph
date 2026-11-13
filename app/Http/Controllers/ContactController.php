<?php

namespace App\Http\Controllers;

use App\Mail\ContactMail;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        // Le formulaire « Le clocher » de l'accueil (mécénat) envoie en AJAX et attend du JSON.
        if (! SpamGuard::passes($request)) {
            $error = 'Votre message n\'a pas pu être envoyé. Merci de réessayer.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $error], 422)
                : back()->withInput()->withErrors(['message' => $error]);
        }

        Mail::to(config('services.contact.email'))->send(new ContactMail($data));

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'Merci, votre message a bien été envoyé. Nous vous répondrons très vite.'])
            : redirect()->route('contact')->with('contact-sent', true);
    }
}
