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
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
