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

        if (! SpamGuard::passes($request)) {
            return back()->withInput()->withErrors(['message' => 'Votre message n\'a pas pu être envoyé. Merci de réessayer.']);
        }

        Mail::to(config('services.contact.email'))->send(new ContactMail($data));

        return redirect()->route('contact')->with('contact-sent', true);
    }
}
