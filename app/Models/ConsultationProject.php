<?php

namespace App\Models;

use App\Support\Images;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Projet finaliste du concours d'architecture, soumis à la consultation des paroissiens. */
#[Table('consultation_projects')]
#[Fillable(['position', 'name', 'architect', 'agency', 'architect_title', 'city', 'architect_photo', 'model_photo', 'is_active'])]
class ConsultationProject extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'position' => 'integer'];
    }

    /** @return HasMany<ConsultationVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(ConsultationVote::class, 'project_id');
    }

    /** Projets proposés au vote, dans l'ordre de l'exposition. */
    public static function ordered(): Builder
    {
        return static::query()->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    public function architectPhotoUrl(): ?string
    {
        return Images::url($this->architect_photo);
    }

    public function modelPhotoUrl(): ?string
    {
        return Images::url($this->model_photo);
    }
}
