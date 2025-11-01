<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

class CompteBancaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comptes_bancaires';

    protected $fillable = [
        'id',
        'client_id',
        'numero_compte',
        'type_compte',
        'devise',
        'solde_initial',
        'solde',
        'decouvert_autorise',
        'date_ouverture',
        'statut',
        'est_bloque',
        'est_archive',
        'motif_archivage',
        'date_debut_blocage',
        'date_fin_blocage',
        'verification_code',
        'verification_expires_at',
        'verification_used',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'solde_initial' => 'decimal:2',
        'decouvert_autorise' => 'decimal:2',
        'date_ouverture' => 'datetime',
        'date_debut_blocage' => 'datetime',
        'date_fin_blocage' => 'datetime',
        'verification_expires_at' => 'datetime',
        'est_bloque' => 'boolean',
        'est_archive' => 'boolean',
        'verification_used' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    protected function getEstBloqueAttribute(): bool
    {
        return $this->date_debut_blocage && $this->date_fin_blocage &&
               now()->between($this->date_debut_blocage, $this->date_fin_blocage);
    }

    public function bloquer(int $dureeJours, string $motif): bool
    {
        $this->date_debut_blocage = now();
        $this->date_fin_blocage = now()->addDays($dureeJours);
        $this->statut = 'bloque';
        $this->motif_archivage = $motif;

        return $this->save();
    }
}
