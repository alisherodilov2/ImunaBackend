<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialExpenseItem extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'qty',
        'material_expense_id',
        'product_id',
        'product_reception_item_id',
    ];
    public function productReceptionItem()
    {
        return $this->hasOne(ProductReceptionItem::class, 'id', 'product_reception_item_id');
    }
}
