<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'numero_client',
        'nom',
        'prenom',
        'email',
        'telephone',
        'date_naissance',
        'adresse',
        'ville',
        'code_postal',
        'pays',
        'statut',
        'password',
        'code'
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->numero_client)) {
                $model->numero_client = 'CLI-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relations
    public function comptesBancaires(): HasMany
    {
        return $this->hasMany(CompteBancaire::class, 'client_id');
    }

    public function user()
    {
        return $this->morphOne(User::class, 'authenticatable');
    }

    // Accessors
    public function getNomCompletAttribute(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeParNom($query, $nom)
    {
        return $query->where('nom', 'like', '%' . $nom . '%');
    }
}
