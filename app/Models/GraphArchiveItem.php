<?php

namespace App\Models;

use App\Traits\Scopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GraphArchiveItem extends Model
{
    use HasFactory, Scopes;
    protected $fillable = [
        'agreement_date',
        'agreement_time',
        'client_id',
        'graph_item_id',
        'graph_archive_id',
        'department_id',
        'is_at_home',
        'is_assigned',
    ];

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function graphItem()
    {
        return $this->hasMany(GraphItem::class, 'id', 'graph_item_id');
    }
    protected static function boot()
    {
        parent::boot();

        // create event
        static::creating(function ($item) {
            if ($item->graph_archive_id) {
                $graphArchiveId = $item->graph_archive_id;

                // `graph_archive_item_count` ni yangilash (yangi element qo'shilganini hisobga olib)
                $count = GraphArchiveItem::where('graph_archive_id', $graphArchiveId)->count() + 1;
                GraphArchive::where('id', $graphArchiveId)->update([
                    'graph_archive_item_count' => $count
                ]);

                Log::info('GraphArchiveItem created, updated graph_archive_item_count:', ['graph_archive_id' => $graphArchiveId]);
            }
        });

        // Updating event: element yangilanganida
        static::updating(function ($item) {
            // Agar `graph_archive_id` o'zgargan bo'lsa
            if ($item->isDirty('graph_archive_id')) {
                $oldGraphArchiveId = $item->getOriginal('graph_archive_id');
                $newGraphArchiveId = $item->graph_archive_id;

                // Eski `graph_archive_id` uchun `graph_archive_item_count` ni yangilash
                $oldCount = GraphArchiveItem::where('graph_archive_id', $oldGraphArchiveId)
                    ->where('client_id', '>', 0) // client_id > 0 sharti
                    ->count() - 1; // O'chirilgan elementni hisobga olish
                GraphArchive::where('id', $oldGraphArchiveId)->update([
                    'graph_archive_item_count' => $oldCount
                ]);

                // Yangi `graph_archive_id` uchun `graph_archive_item_count` ni yangilash
                $newCount = GraphArchiveItem::where('graph_archive_id', $newGraphArchiveId)
                    ->where('client_id', '>', 0) // client_id > 0 sharti
                    ->count() + 1; // Yangi qo'shilgan elementni hisobga olish
                GraphArchive::where('id', $newGraphArchiveId)->update([
                    'graph_archive_item_count' => $newCount
                ]);

                Log::info('GraphArchiveItem updated, updated graph_archive_item_count:', [
                    'old_graph_archive_id' => $oldGraphArchiveId,
                    'new_graph_archive_id' => $newGraphArchiveId,
                ]);
            }
        });

        // Deleting event: element o'chirilganda
        static::deleting(function ($item) {
            if ($item->graph_archive_id) {
                $graphArchiveId = $item->graph_archive_id;
                // `graph_archive_item_count` ni yangilash (o'chirilgan elementni hisobdan chiqarib)
                $count = GraphArchiveItem::where('graph_archive_id', $graphArchiveId)->count() - 1;
                GraphArchive::where('id', $graphArchiveId)->update([
                    'graph_archive_item_count' => $count
                ]);
                Log::info('GraphArchiveItem deleted, updated graph_archive_item_count:', ['graph_archive_id' => $graphArchiveId]);
            }
        });
    }

    // Otasining `graph_archive_item_count` ni yangilaydigan funksiya
    public function updateGraphArchiveItemCount()
    {
        // Aloqador `GraphArchive` modelini yuklaydi va `graph_archive_item_count` ni yangilaydi
        // Log::info(['salom']);
        // Log::info([  $this->graphArchive]);
        // // Log::info([ $this->graphArchive->graphArchiveItem()->count()+1]);
        // if ($this->graphArchive) {

        //     $this->graphArchive->graph_archive_item_count = $this->graphArchive->graphArchiveItem()->count();
        //     $this->graphArchive->save();
        // }
    }

    // GraphArchive bilan aloqasi
    public function graphArchive()
    {
        return $this->hasMany(GraphArchive::class, 'id', 'graph_archive_id');
    }
    public function department()
    {
        return $this->hasOne(Departments::class, 'id',  'department_id');
    }
}
