<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraphArchive extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'user_id',
        'graph_id',
        'comment',
        'person_id',
        'status',
        'treatment_id',
        'graph_archive_item_count',
        'department_id',
        'came_graph_archive_item_count',
        'referring_doctor_id',
        'use_status',
        'client_id',
        'shelf_number',
        'at_home_client_id'
    ];
    const STATUS_ARCHIVE = 'archive';
    const STATUS_LIVE = 'live';
    const STATUS_FINISH = 'finish';
    public function graph()
    {
        return $this->belongsTo(Graph::class, 'graph_id');
    }

    // at_home_client_id
    public function atHomeClient()
    {
        return $this->hasOne(Client::class, 'id', 'at_home_client_id');
    }
    public function treatment()
    {
        return $this->hasOne(Treatment::class, 'id', 'treatment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function person()
    {
        return $this->belongsTo(Client::class, 'person_id', 'person_id')->whereNull('parent_id');
    }

    public function graphArchiveItem()
    {
        return $this->hasMany(GraphArchiveItem::class);
    }
    public function department()
    {
        return $this->hasOne(Departments::class, 'id', 'department_id');
    }
}
