<?php

namespace App\Http\Controllers;

use App\Models\OilOrder;
use App\Models\OilProduct;
use App\Support\Croix;
use App\Support\OilOrders;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Page /commande-huile : réservation de bouteilles d'huile d'olive (5 au plus par format, dans la limite du stock),
 * à retirer et régler à la kermesse. Commande en base + liste Brevo 9 + e-mail récapitulatif. Non référencée.
 */
class OilOrderController extends Controller
{
    public function show(Request $request)
    {
        $order = ($id = $request->session()->get('oil_order_id')) ? OilOrder::with('items')->find($id) : null;

        return response()->view('commande-huile', [
            'products' => OilProduct::ordered()->get(),
            'settings' => OilOrders::settings(),
            'order' => $request->boolean('nouvelle') ? null : $order,
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function store(Request $request)
    {
        $back = fn () => redirect()->to(route('huile.order').'#commande');

        if (! OilOrders::isOpen()) {
            return $back();
        }

        // Nouvelle réservation : la confirmation de la précédente laisse place au formulaire (et à ses erreurs éventuelles).
        $request->session()->forget('oil_order_id');

        $products = OilProduct::ordered()->get();
        $anchor = route('huile.order').'#commande';
        try {
            $data = $request->validate([
                'quantities' => ['nullable', 'array'],
                'quantities.*' => ['nullable', 'integer', 'min:0', 'max:'.OilProduct::MAX_PER_ORDER],
                'first_name' => ['required', 'string', 'max:80'],
                'last_name' => ['required', 'string', 'max:80'],
                'phone' => ['required', 'string', 'max:40'],
                'email' => ['required', 'email', 'max:190'],
            ], [
                'quantities.*.max' => 'Pas plus de '.OilProduct::MAX_PER_ORDER.' bouteilles de chaque format par commande.',
                'quantities.*.integer' => 'Merci d\'indiquer un nombre de bouteilles.',
                'quantities.*.min' => 'Merci d\'indiquer un nombre de bouteilles.',
                'first_name.required' => 'Merci d\'indiquer votre prénom.',
                'last_name.required' => 'Merci d\'indiquer votre nom.',
                'phone.required' => 'Merci d\'indiquer votre numéro de téléphone.',
                'email.required' => 'Merci d\'indiquer votre adresse e-mail.',
                'email.email' => 'Cette adresse e-mail ne semble pas complète (exemple : prenom.nom@gmail.com).',
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo($anchor);
        }

        // Seuls les formats existants comptent.
        $quantities = collect($data['quantities'] ?? [])
            ->only($products->pluck('id')->all())
            ->map(fn ($q) => (int) $q)
            ->filter()
            ->all();

        $errors = [];
        if (! $quantities) {
            $errors['quantities'] = 'Choisissez au moins une bouteille.';
        }
        if (! ($phone = Croix::normalizePhone($data['phone']))) {
            $errors['phone'] = 'Ce numéro de téléphone ne semble pas valide (exemple : 06 12 34 56 78).';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors)->redirectTo($anchor);
        }

        if (! SpamGuard::passes($request, withTurnstile: false)) {
            return $back()->withInput()->withErrors(['email' => 'Merci de réessayer dans quelques secondes.']);
        }

        $clean = fn (string $v) => mb_substr(trim(preg_replace('/\s+/u', ' ', $v)), 0, 80);
        $customer = [
            'first_name' => $clean($data['first_name']),
            'last_name' => $clean($data['last_name']),
            'email' => mb_strtolower(trim($data['email'])),
            'phone' => $phone,
        ];
        try {
            $order = OilOrders::place($customer, $quantities);
        } catch (ValidationException $e) {
            // Pas assez de bouteilles : on garde quand même le contact dans Brevo (liste 9), pour le prévenir
            // quand d'autres bouteilles seront remises en stock, et on l'invite à nous contacter.
            dispatch(fn () => OilOrders::addToBrevo($customer['email'], $customer['first_name'], $customer['last_name'], $customer['phone']))
                ->afterResponse();

            return $back()->withInput()->withErrors($e->errors())->with('oil_stock_short', true);
        }

        $request->session()->put('oil_order_id', $order->id);

        return $back();
    }
}
