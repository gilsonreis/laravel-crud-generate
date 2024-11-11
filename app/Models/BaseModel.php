<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

abstract class BaseModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        if (in_array('deleted_at', (new static())->getFillable())) {
            static::addGlobalScope(new SoftDeletingScope);
        }
    }
}