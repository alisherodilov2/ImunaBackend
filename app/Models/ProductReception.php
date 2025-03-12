<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReception extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'user_id',
    ];
    public function productReceptionItem()
    {
        return $this->hasMany(ProductReceptionItem::class);
    }
}
