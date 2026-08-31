<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'category',
        'name',
        'description',
        'base_price',
        'active',
        'has_flavors',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'has_flavors' => 'boolean',
            'base_price' => 'integer',
        ];
    }
}
