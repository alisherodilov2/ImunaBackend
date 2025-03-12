<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'user_id',
        'main_branch_id',

    ];
    // branchItem
    public function branchItems()
    {
        return $this->hasMany(BranchItem::class);
    }
    // name
    public function mainBranch()
    {
        return $this->hasOne(User::class, 'id', 'main_branch_id');
    }

}
