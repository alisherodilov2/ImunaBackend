<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientUseProduct extends Model
{

    use HasFactory, Scopes;

    protected $fillable = [
        'product_reception_item_id',
        'client_value_id',
        'service_id',
        'client_id',
        'product_id',
        'product_category_id',
        'qty',
    ];


    // product_reception_item_id

    public function productReceptionItem()
    {
        return $this->hasOne(ProductReceptionItem::class, 'id', 'product_reception_item_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function prodcutCategory()
    {
        return $this->hasOne(ProductCategory::class, 'id', 'product_category_id');
    }
}
