<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exportateur extends Model
{
    use HasFactory;

    protected $table = 'exportateurs';

    protected $fillable = [
        'nom',
        'nom_societe',
        'ref_foodex',
        'adresse',
        'telephone',
        'email',
        'web',
        'banque',
        'agence',
        'beneficiaire',
        'iban',
        'swift',
        'rib',
        'actif',
        'est_defaut',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'est_defaut' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function defaut(): ?self
    {
        return static::where('actif', true)->where('est_defaut', true)->orderBy('id')->first()
            ?: static::where('actif', true)->orderBy('id')->first();
    }
}
