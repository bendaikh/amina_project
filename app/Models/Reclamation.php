<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Reclamation extends Model
{
    public const STATUTS = [
        'nouvelle',
        'en_analyse',
        'action_en_cours',
        'attente_client',
        'resolue',
        'cloturee',
        'rejetee',
    ];

    public const ORIGINES = [
        'commande',
        'bl',
        'facture',
        'retour',
        'vente_locale',
        'vente_export',
        'article',
        'client',
        'directe',
    ];

    public const PRIORITES = ['basse', 'normale', 'haute', 'urgente'];

    public const ACTIONS = [
        'remplacement',
        'retour',
        'avoir',
        'remboursement',
        'analyse_qualite',
        'correction_logistique',
        'correction_facturation',
        'information_client',
    ];

    protected $fillable = [
        'numero', 'date_reclamation', 'client_id', 'origine', 'document_origine',
        'commande_id', 'article_id', 'article_libelle', 'quantite', 'numero_lot',
        'motif', 'description', 'priorite', 'responsable', 'date_limite',
        'action_prevue', 'action_corrective', 'reponse', 'statut',
        'date_resolution', 'date_cloture', 'created_by',
    ];

    protected $casts = [
        'date_reclamation' => 'date',
        'date_limite' => 'date',
        'date_resolution' => 'date',
        'date_cloture' => 'date',
        'quantite' => 'decimal:3',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function notesCredit(): HasMany
    {
        return $this->hasMany(NoteCredit::class);
    }

    public function piecesJointes(): MorphMany
    {
        return $this->morphMany(PieceJointe::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function nextNumero(): string
    {
        $year = now()->format('Y');
        $prefix = "REC-{$year}-";
        $last = static::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('numero')
            ->value('numero');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
