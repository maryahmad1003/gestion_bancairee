<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends BaseModel
{
    use HasFactory, SoftDeletes;

     const TYPE_DEPOT = 'depot';
    const TYPE_RETRAIT = 'retrait';
    const TYPE_VIREMENT = 'virement';
    const TYPE_FRAIS = 'frais';

    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_VALIDEE = 'validee';
    const STATUT_ANNULEE = 'annulee';

    protected $fillable = [
        'id',
        'compte_id',
        'montant',
        'type',
        'devise',
        'description',
        'status'
    ];


    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id', 'id');
    }

}
