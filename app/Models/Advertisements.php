<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisements extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'user_id',
        'name',

    ];


    public function client()
    {
        return $this->hasMany(Client::class)->whereNull('parent_id');
    }
    public function clientAnaliz()
    {
        return $this->hasMany(Client::class)->whereNotNull('parent_id');
    }
}
