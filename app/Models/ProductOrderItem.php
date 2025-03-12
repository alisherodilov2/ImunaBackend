<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrderItem extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'qty',
        'product_id',
        'product_order_id',
    ];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
    public function productOrderItemDone()
    {
        return $this->hasMany(ProductOrderItemDone::class);
    }
}
