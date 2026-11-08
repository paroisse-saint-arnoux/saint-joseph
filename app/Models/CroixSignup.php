<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/** Coordonnées laissées sur la page /croix pour recevoir le lien de vote (copie locale de ce qui part dans Brevo). */
#[Table('croix_signups')]
#[Fillable(['visitor_id', 'email', 'first_name', 'last_name', 'phone', 'consent_sms', 'source', 'brevo_synced_at'])]
class CroixSignup extends Model
{
    protected function casts(): array
    {
        return ['consent_sms' => 'boolean', 'brevo_synced_at' => 'datetime'];
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
