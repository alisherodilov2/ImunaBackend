<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrderItemDone extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'qty',
        'product_id',
        'pharmacy_product_id',
        'product_order_item_id',
        'expiration_date'
    ];
}
