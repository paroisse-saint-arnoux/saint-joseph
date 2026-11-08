<?php

use App\Services\Brevo;
use App\Support\Croix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Décision du 08/11/2026 : les coordonnées laissées sur /croix ne sont gardées que dans Brevo, pas sur le site.
 * Avant de supprimer la copie locale, on envoie à Brevo les inscriptions qui n'y étaient pas encore.
 * Si Brevo en refuse une, la migration s'arrête (rien n'est supprimé) : relancer ./deploy.sh plus tard.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('croix_signups')) {
            return;
        }

        $brevo = app(Brevo::class);
        $list = (int) config('services.brevo.croix_list_id');

        foreach (DB::table('croix_signups')->whereNull('brevo_synced_at')->get() as $signup) {
            $attributes = array_filter(['PRENOM' => $signup->first_name, 'NOM' => $signup->last_name]);
            $phone = $signup->consent_sms ? Croix::normalizePhone($signup->phone) : null;

            $ok = ($phone && $brevo->addContact($signup->email, $list, $attributes + ['SMS' => $phone]))
                || $brevo->addContact($signup->email, $list, $attributes);

            if (! $ok) {
                throw new RuntimeException('Brevo ne répond pas : inscriptions de /croix non envoyées, table croix_signups conservée. Relancer ./deploy.sh plus tard.');
            }
        }

        Schema::drop('croix_signups');
    }
};
