<?php

namespace App\Services\Api\V3;

use App\Models\TgGroup;
use App\Services\Api\V3\Contracts\TgGroupServiceInterface;
use App\Traits\Crud;

class TgGroupService implements TgGroupServiceInterface
{
    public $modelClass = TgGroup::class;

    use Crud;
    public function filter()
    {
        return $this->modelClass::all();
    }
    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                TgGroup::updateOrCreate(
                    [
                        'name' => $item?->name,
                    ], [
                        'name' => $item?->name,
                        'address' => $item?->name,
                        'photo' => $item?->photo,
                    ]);
            }
        }
        return $this->modelClass::all();
    }

}
