<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompteBancaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comptes_bancaires';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'numero_compte',
        'client_id',
        'type_compte',
        'devise',
        'decouvert_autorise',
        'date_ouverture',
        'statut',
        'commentaires',
        'est_bloque',
        'date_debut_blocage',
        'duree_blocage_jours',
        'date_fin_blocage',
        'motif_blocage',
        'est_archive',
        'date_archivage'
    ];

    protected $casts = [
        'decouvert_autorise' => 'decimal:2',
        'date_ouverture' => 'date',
        'date_debut_blocage' => 'datetime',
        'date_fin_blocage' => 'datetime',
        'date_archivage' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'est_bloque' => 'boolean',
        'est_archive' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->numero_compte)) {
                $model->numero_compte = self::generateNumeroCompte();
            }
        });

        // Observer pour déclencher les événements
        static::created(function ($compte) {
            // Ici on pourrait déclencher un événement si nécessaire
            // event(new CompteBancaireCreated($compte));
        });

        // Scope global pour les comptes non supprimés - désactivé pour les tests
        // static::addGlobalScope('nonSupprimes', function ($builder) {
        //     $builder->where('statut', '!=', 'ferme');
        // });
    }

    /**
     * Générer un numéro de compte unique
     */
    private static function generateNumeroCompte(): string
    {
        do {
            $numero = 'CB-' . strtoupper(Str::random(10));
        } while (self::where('numero_compte', $numero)->exists());

        return $numero;
    }

    // Relations
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'compte_bancaire_id');
    }

    public function transactionsEmises(): HasMany
    {
        return $this->hasMany(Transaction::class, 'compte_bancaire_destinataire_id');
    }

    // Accessors
    public function getSoldeFormateAttribute(): string
    {
        return number_format($this->solde, 2, ',', ' ') . ' ' . $this->devise;
    }

    /**
     * Calcul du solde basé sur les transactions
     * Solde = Somme des crédits - Somme des débits
     */
    public function getSoldeAttribute(): float
    {
        // Crédits : dépôts et virements reçus
        $credits = $this->transactions()
            ->whereIn('type_transaction', ['credit', 'virement_recus'])
            ->where('statut', 'validee')
            ->sum('montant');

        // Débits : retraits et virements émis
        $debits = $this->transactions()
            ->whereIn('type_transaction', ['debit', 'virement_emis'])
            ->where('statut', 'validee')
            ->sum('montant');

        return $credits - $debits;
    }

    // Ancienne méthode supprimée car remplacée par la nouvelle ci-dessous

    public function getPeutDebiterAttribute(): bool
    {
        return ($this->solde + $this->decouvert_autorise) > 0;
    }

    public function getEstBloqueAttribute(): bool
    {
        return $this->est_bloque && $this->date_fin_blocage && now()->lessThanOrEqualTo($this->date_fin_blocage);
    }

    public function getPeutEtreBloqueAttribute(): bool
    {
        return $this->type_compte === 'epargne' && $this->statut === 'actif' && !$this->est_bloque;
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeParClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type_compte', $type);
    }

    public function scopeNumero($query, $numero)
    {
        return $query->where('numero_compte', $numero);
    }

    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('client', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

    public function scopeBloques($query)
    {
        return $query->where('est_bloque', true)
                    ->where('date_fin_blocage', '>', now());
    }

    public function scopeEpargne($query)
    {
        return $query->where('type_compte', 'epargne');
    }

    public function scopeArchives($query)
    {
        return $query->where('est_archive', true);
    }

    public function scopeNonArchives($query)
    {
        return $query->where('est_archive', false);
    }

    /**
     * Bloquer un compte épargne
     */
    public function bloquer(int $dureeJours, ?string $motif = null): bool
    {
        if (!$this->peut_etre_bloque) {
            return false;
        }

        $this->update([
            'est_bloque' => true,
            'date_debut_blocage' => now(),
            'duree_blocage_jours' => $dureeJours,
            'date_fin_blocage' => now()->addDays($dureeJours),
            'motif_blocage' => $motif,
        ]);

        return true;
    }

    /**
     * Débloquer un compte épargne
     */
    public function debloquer(): bool
    {
        if (!$this->est_bloque) {
            return false;
        }

        $this->update([
            'est_bloque' => false,
            'date_debut_blocage' => null,
            'duree_blocage_jours' => null,
            'date_fin_blocage' => null,
            'motif_blocage' => null,
        ]);

        return true;
    }

    /**
     * Archiver un compte et ses transactions
     */
    public function archiver(): bool
    {
        if ($this->est_archive) {
            return false;
        }

        DB::transaction(function () {
            // Archiver le compte
            $this->update([
                'est_archive' => true,
                'date_archivage' => now(),
            ]);

            // Archiver toutes les transactions du compte
            $this->transactions()->update([
                'est_archive' => true,
                'date_archivage' => now(),
            ]);
        });

        return true;
    }

    /**
     * Désarchiver un compte et ses transactions
     */
    public function desarchiver(): bool
    {
        if (!$this->est_archive) {
            return false;
        }

        DB::transaction(function () {
            // Désarchiver le compte
            $this->update([
                'est_archive' => false,
                'date_archivage' => null,
            ]);

            // Désarchiver toutes les transactions du compte
            $this->transactions()->update([
                'est_archive' => false,
                'date_archivage' => null,
            ]);
        });

        return true;
    }
}
