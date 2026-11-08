<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Personne ayant répondu à la consultation, identifiée par son adresse email (non vérifiée). */
#[Table('consultation_participants')]
#[Fillable(['email', 'token', 'brevo_synced_at'])]
#[Hidden(['token'])]
class ConsultationParticipant extends Model
{
    protected function casts(): array
    {
        return ['brevo_synced_at' => 'datetime'];
    }

    /** @return HasMany<ConsultationVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(ConsultationVote::class, 'participant_id');
    }
}
