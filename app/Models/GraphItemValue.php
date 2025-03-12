<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphItemValue extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'service_id',
        'graph_item_id',
    ];
    public function service()
    {
        return $this->hasOne(Services::class, 'id',  'service_id');
    }
}
