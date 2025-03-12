<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateItem extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'value_1',
        'value_2',
        'value_3',
        'template_id',
        'user_id',
        'template_category_id',
        'is_comment',
    ];
    public function templateCategory()
    {
        return $this->hasOne(TemplateCategory::class,  'id','template_category_id');
    }
    protected $attributes = [
        'is_comment' => false,
    ];
}
