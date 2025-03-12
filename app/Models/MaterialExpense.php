<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialExpense extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'qty',
        'user_id',
        'product_id',
        'comment',
    ];
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
    public function materialExpenseItem()
    {
        return $this->hasMany(MaterialExpenseItem::class);
    }
}
