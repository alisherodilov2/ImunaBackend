<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'user_id',
        'price',
        'expense_type_id',
        'pay_type',
        'comment',
    ];
    public function expenseType()
    {
        return $this->hasOne(ExpenseType::class,'id','expense_type_id');
    }
    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }
}
