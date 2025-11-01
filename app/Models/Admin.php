<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends BaseModel
{
    use HasFactory;

    protected $table = 'admins';

    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    } 
}
