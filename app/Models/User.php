<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use  HasFactory, HasApiTokens ,  Notifiable, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = "users";

 

    protected $fillable = [
        'id',
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'nci',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    

    protected function password(): Attribute
{
    return Attribute::make(
        set: fn ($value) => Hash::make($value),
    );
}

    public function client(){
        return $this->hasOne(Client::class);
    }

    public function admin(){
        return $this->hasOne(Admin::class);
    }

    public function comptes() {
        return $this->hasMany(Compte::class, 'user_id', 'id');
    }

    public function transactions() {
        return $this->hasManyThrough(Transaction::class, Compte::class, 'user_id', 'compte_id', 'id', 'id');
    }

    public function isClient(): bool {
        return $this->client()->exists();
    }

    public function isAdmin(): bool {
        return $this->admin()->exists();
    }

    


}
