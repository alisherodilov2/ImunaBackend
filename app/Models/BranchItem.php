<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchItem extends Model
{
    use HasFactory, Scopes;

    protected $fillable = [
        'branch_id',
        'target_branch_id',
    ];
    // branch
    public function targetBranch()
    {
        return $this->hasOne(User::class,'id', 'target_branch_id');
    }
}
