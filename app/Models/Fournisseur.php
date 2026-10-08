<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    use HasFactory;

    protected $table = 'fournisseurs';

    protected $fillable = [
        'ice',
        'eori',
        'nom',
        'email',
        'telephone',
        'adresse',
        'pays',
        'categorie',
        'devise',
        'solde_actuel',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'solde_actuel' => 'decimal:2',
    ];

    public function reglements()
    {
        return $this->hasMany(Reglement::class);
    }
}
