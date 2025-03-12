<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyRepot extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'user_id',
        'cash_price',
        'card_price',
        'transfer_price',
        'give_cash_price',
        'give_card_price',
        'give_transfer_price',
        'total_price',
        'status',
        'is_transfer',
        'is_card',
        'is_cash',
        'batch_number',
    ];
    public function owner()
    {
        // return $this->hasOneThrough(
        //     User::class, // Final model (owner)
        //     User::class, // Intermediate model
        //     'id', // Intermediate model foreign key (user_id dan bog'lanadi)
        //     'id', // Final model foreign key (owner_id dan bog'lanadi)
        //     'user_id', // Local key in DailyReport
        //     'owner_id' // Local key in User
        // );
        
    }

    // DailyRepotExpense
    function dailyRepotExpense() {
        return $this->hasMany(DailyRepotExpense::class);
    }
    function dailyRepotClient() {
        return $this->hasMany(DailyRepotClient::class);
    }
    // user
    function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
