<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Réponses d'une personne sur un projet : avis général, trois critères (de -2 à +2) et commentaire. */
#[Table('consultation_votes')]
#[Fillable(['participant_id', 'project_id', 'overall', 'aesthetic', 'spiritual', 'convivial', 'comment'])]
class ConsultationVote extends Model
{
    protected function casts(): array
    {
        return ['overall' => 'integer', 'aesthetic' => 'integer', 'spiritual' => 'integer', 'convivial' => 'integer'];
    }

    /** @return BelongsTo<ConsultationParticipant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ConsultationParticipant::class, 'participant_id');
    }

    /** @return BelongsTo<ConsultationProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ConsultationProject::class, 'project_id');
    }
}
