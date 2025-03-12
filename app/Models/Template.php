<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory, Scopes;

   
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'is_photo',
        'is_comment',
    ];
    public function templateItem()
    {
        return $this->hasMany(TemplateItem::class,  'template_id');
    }
}
