<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReceptionItem extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'product_reception_id',
        'product_category_id',
        'manufacture_date',
        'product_id',
        'qty',
        'expiration_date',
        'price',
        'use_qty',
        'product_order_id',
        'product_order_item_id',
        'pharmacy_product_id',
        'product_order_item_done_id'
    ];
    public function prodcutCategory()
    {
        return $this->hasOne(ProductCategory::class, 'id', 'product_category_id');
    }
    public function pharmacyProduct()
    {
        return $this->hasOne(PharmacyProduct::class, 'id', 'pharmacy_product_id');
    }
    public function prodcut()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
    public function productReception()
    {
        return $this->hasOne(ProductReception::class, 'id', 'product_reception_id');
    }
}
