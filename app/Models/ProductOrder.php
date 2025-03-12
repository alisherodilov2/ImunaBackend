<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [

        'branch_id',
        'user_id',
        'status',
        'pharmacy_id'
    ];
    // ProductOrderItem
    public function productOrderItem()
    {
        return $this->hasMany(ProductOrderItem::class);
    }
    public function branch()
    {
        return $this->hasOne(User::class, 'id', 'branch_id');
    }
    
}
