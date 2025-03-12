<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyProduct extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'price',
        'qty',
        'product_id',
        'user_id',
        'expiration_date',
    ];
    // prodcut
    public function prodcut()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
