<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentTemplateItem extends Model
{
    use HasFactory, Scopes;
    protected $fillable =
    [
        'department_id',
        'template_id',
    ];
    public function template()
    {
        return $this->hasOne(Template::class, 'id', 'template_id');
    }
}
