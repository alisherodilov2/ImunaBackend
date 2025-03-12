<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyRepotExpense extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'daily_repot_id',
        'expense_id',
    ];

    public function expense()
    {
        return $this->hasOne(Expense::class, 'id', 'expense_id');
    }
}
