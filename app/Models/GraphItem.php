<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphItem extends Model
{

    use HasFactory, Scopes;
    protected $fillable = [
        'agreement_date',
        'department_id',
        'agreement_time',
        'is_active',
        'is_arrived',
        'graph_id',
    ];
    public function department()
    {
        return $this->hasOne(Departments::class, 'id',  'department_id');
    }
    public function graphItemValue()
    {
        return $this->hasMany(GraphItemValue::class, 'graph_item_id');
    }
}
