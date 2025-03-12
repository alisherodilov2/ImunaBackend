<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrderBack extends Model
{
    // 'expired' => 'Expired',        // Mudati o'tgan
    // 'unusable' => 'Unusable',      // Ishlatishga yaroqsiz
    // 'return' => 'Return',          // Qaytarish

    use HasFactory, Scopes;
    protected $fillable = [
        'user_id',
        'status',
        'product_reception_item_id',
        'pharmacy_product_id',
        'type',
        'qty',
        'pharmacy_id',
        'product_id',
    ];
    // user
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    // product
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
