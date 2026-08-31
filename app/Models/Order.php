<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ref',
        'client_id',
        'items_json',
        'subtotal',
        'promo_discount',
        'promo_free',
        'delivery_fee',
        'distance_km',
        'address',
        'address_lat',
        'address_lng',
        'total',
        'payment_method',
        'status',
        'placed_at',
        'status_at',
    ];

    protected function casts(): array
    {
        return [
            'items_json' => 'array',
            'distance_km' => 'float',
            'address_lat' => 'float',
            'address_lng' => 'float',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
