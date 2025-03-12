<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'name',
        'price',
        'alert_min_qty',
        'product_category_id',
        'user_id',
        'expiration_day',
        'alert_dedline_day'
    ];

    public function prodcutCategory()
    {
        return $this->hasOne(ProductCategory::class, 'id', 'product_category_id');
    }
    public function productReceptionItem()
    {
        return $this->hasMany(ProductReceptionItem::class);
    }
}
