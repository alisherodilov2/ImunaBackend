<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProduct extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'service_id',
        'product_id',
        'qty',
    ];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
    public function productReceptionItem()
    {
        return $this->hasMany(ProductReceptionItem::class, 'product_id', 'product_id');
    }
}
