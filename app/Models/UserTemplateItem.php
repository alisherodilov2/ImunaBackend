<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTemplateItem extends Model
{
    use HasFactory,Scopes;
    protected $fillable = 
    [
        'template_id',
        'user_id'
    ];
    public function template()
    {
        return $this->hasOne(Template::class, 'id', 'template_id');
    }
}
