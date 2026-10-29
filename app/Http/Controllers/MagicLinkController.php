<?php

namespace App\Http\Controllers;

use App\Support\MagicLink;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MagicLinkController extends Controller
{
    /**
     * Page de confirmation : les antivirus des messageries ouvrent les liens
     * des emails, la connexion ne se fait donc qu'au clic sur le bouton (POST).
     */
    public function show(Request $request, string $token)
    {
        return view('auth.magic-link', [
            'valid' => MagicLink::isValid($token),
            'action' => $request->fullUrl(),
        ]);
    }

    public function login(Request $request, string $token)
    {
        $user = MagicLink::consume($token);

        if (! $user) {
            return redirect()->to(Filament::getLoginUrl())->with('magic-link-expired', true);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(Filament::getUrl());
    }
}
