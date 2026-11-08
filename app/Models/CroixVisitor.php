<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/** Navigateur venu sur la page /croix (ou directement sur le lien de vote), identifié par un cookie. */
#[Table('croix_visitors')]
#[Fillable(['token', 'source', 'visits', 'visited_at', 'signed_up_at', 'vote_clicked_at'])]
#[Hidden(['token'])]
class CroixVisitor extends Model
{
    protected function casts(): array
    {
        return ['visited_at' => 'datetime', 'signed_up_at' => 'datetime', 'vote_clicked_at' => 'datetime'];
    }
}
