<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Scopes\NonSupprimeScope;

class BaseModel extends Model
{
    use HasUuids;
    // public $incrementing = false;
    // protected $keyType = 'string';

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {
    //         if (!$model->getKey()) {
    //             $model->{$model->getKeyName()} = (string) Str::uuid();
    //         }
    //     });

    //     static::addGlobalScope(new NonSupprimeScope());
    // }
}
