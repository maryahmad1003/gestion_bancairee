<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\DB;



class Compte extends BaseModel
{
    use HasFactory, HasApiTokens,  SoftDeletes;

    protected $table = 'comptes';


    protected $fillable = [
        'id',
        'numero_compte',
        'user_id',
        'titulaire',
        'type',
        'devise',
        'statut',
        'derniere_modification',
        'version',
        'code_verification',
        'code_expire_at'
    ];

   
   protected function numeroCompte(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ?: 'ACC-' . strtoupper(Str::random(10))
        );
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($compte) {
            if (!$compte->numero_compte) {
                $compte->numero_compte = 'ACC-' . strtoupper(Str::random(10));
            }
        });
    }

    public function scopeNumero($query, $numero)
    {
        return $query->where('numero_compte', $numero);
    }

   public function scopeFiltrerComptes(Builder $query, $filters = [], $user = null)
{
    $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

    $query->whereIn('type', ['epargne', 'cheque'])
          ->where('statut', 'actif');

    if (!$isAdmin && $user) {
        $query->where('user_id', $user->id);
    }

    if (!empty($filters['type'])) {
        $query->where('type', $filters['type']);
    }

    if (!empty($filters['statut'])) {
        $query->where('statut', $filters['statut']);
    }

    if(!empty($filters['numero_compte'])) {
        $query->where('numero_compte', $filters['numero_compte']);
    }

    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->where('titulaire', 'like', "%{$search}%")
              ->orWhere('numero_compte', 'like', "%{$search}%");
        });
    }

    $sortField = match ($filters['sort'] ?? null) {
        'dateCreation' => 'created_at',
        'solde' => 'solde',
        'titulaire' => 'titulaire',
        default => 'created_at',
    };

    $order = in_array($filters['order'] ?? '', ['asc', 'desc'])
        ? $filters['order']
        : 'desc';

    $query->orderBy($sortField, $order);

    $query->withSum(['transactions as depot_sum' => fn($q) =>
        $q->where('type', 'depot')->where('status', 'validee')
    ], 'montant')
    ->withSum(['transactions as retrait_sum' => fn($q) =>
        $q->where('type', 'retrait')->where('status', 'validee')
    ], 'montant');

    return $query;
}

    public function scopeClient($query, $phone)
    {
        return $query->whereHas('user', fn($q) => $q->where('telephone', $phone));
    }

    

    public static function createCompteWithUser(array $userData, array $compteData): self
    {
        return DB::transaction(function () use ($userData, $compteData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    ...$userData,
                    'password' => bcrypt($userData['password'] ?? Str::random(10))
                ]
            );

            Client::firstOrCreate(['user_id' => $user->id]);

            $compte = self::create([
                'user_id' => $user->id,
                'type' => $compteData['type'],
                'statut' => 'actif',
                'titulaire' => $user->nom . ' ' . $user->prenom,
                'devise' => $compteData['devise'] ?? 'FCFA',
                'version' => 1
            ]);

            if (isset($compteData['solde']) && $compteData['solde'] > 0) {
            Transaction::create([
                'compte_id' => $compte->id,
                'type' => 'depot',
                'montant' => $compteData['solde'],
                'motif' => 'Dépôt initial',
                'statut' => 'success'
            ]);
        }

        return $compte->fresh(['transactions']); 
        });
    }

    protected function solde(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->transactions()
                    ->whereNot('status', 'annulee')
                    ->selectRaw("SUM(CASE 
                        WHEN type = 'depot' THEN montant 
                        WHEN type = 'retrait' THEN -montant 
                        ELSE 0 
                    END) as solde")
                    ->value('solde') ?? 0;
            }
        );
    }



   

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id',  'id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'compte_id', 'id');
    }
}
