<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PieceJointe extends Model
{
    protected $table = 'pieces_jointes';

    protected $fillable = [
        'attachable_type', 'attachable_id', 'type', 'nom_fichier',
        'chemin', 'mime', 'taille', 'uploaded_by',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
