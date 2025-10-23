<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'numero_transaction',
        'compte_bancaire_id',
        'compte_bancaire_destinataire_id',
        'type_transaction',
        'montant',
        'devise',
        'libelle',
        'description',
        'date_transaction',
        'statut',
        'reference_externe',
        'metadata',
        'est_archive',
        'date_archivage'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_transaction' => 'datetime',
        'date_archivage' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'est_archive' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::observe(\App\Observers\TransactionObserver::class);

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->numero_transaction)) {
                $model->numero_transaction = 'TXN-' . strtoupper(Str::random(12));
            }
        });
    }

    // Relations
    public function compteBancaire(): BelongsTo
    {
        return $this->belongsTo(CompteBancaire::class, 'compte_bancaire_id');
    }

    public function compteDestinataire(): BelongsTo
    {
        return $this->belongsTo(CompteBancaire::class, 'compte_bancaire_destinataire_id');
    }

    // Accessors
    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant, 2, ',', ' ') . ' ' . $this->devise;
    }

    public function getEstDebitAttribute(): bool
    {
        return in_array($this->type_transaction, ['debit', 'virement_emis']);
    }

    public function getEstCreditAttribute(): bool
    {
        return in_array($this->type_transaction, ['credit', 'virement_recus']);
    }

    // Scopes
    public function scopeValidees($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeParCompte($query, $compteId)
    {
        return $query->where('compte_bancaire_id', $compteId);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type_transaction', $type);
    }

    public function scopeParPeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_transaction', [$dateDebut, $dateFin]);
    }

    public function scopeArchives($query)
    {
        return $query->where('est_archive', true);
    }

    public function scopeNonArchives($query)
    {
        return $query->where('est_archive', false);
    }
}
