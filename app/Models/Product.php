<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'price', 'description', 'created_at', 'category_id', 'tags', 'name_slug'];

    protected $casts = [
        'created_at' => 'datetime'
    ];


    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (empty($model->name_slug)) {
                $model->name_slug = Str::slug($model->name);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

}