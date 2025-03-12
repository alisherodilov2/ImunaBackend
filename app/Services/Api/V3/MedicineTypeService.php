<?php

namespace App\Services\Api\V3;

use App\Models\Medicine;
use App\Models\MedicineType;
use App\Services\Api\V3\Contracts\MedicineTypeServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Log;

class MedicineTypeService implements MedicineTypeServiceInterface
{
    public $modelClass = MedicineType::class;
    use Crud;
    public function filter()
    {
        return $this->modelClass::where('user_id', auth()->id())
            ->get();
    }
    public function itemAll($request)
    {
        $id =  $request->id;
        return [
            'data' => Medicine::where('medicine_type_id', $id)->get(),
            'id' => $id
        ];
    }
    public function itemStore($request)
    {
        $this->modelClass = Medicine::class;
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $result = $this->store($request);
        return $result;
    }
    public function itemUpdate($id, $request)
    {
        $this->modelClass = Medicine::class;
        Log::info('request', [$request->name]);
        $result = $this->update($id, $request);
        return $result;
    }
    public function itemDelete($id)
    {
        Medicine::destroy($id);
        return $id;
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $result = $this->store($request);
        return $result;
    }
    public function edit($id, $request)
    {
        $request = $request;
        $result = $this->update($id, $request);
        return $result;
    }
    public function delete($id)
    {
        $result = $this->modelClass::find($id);
        $result->delete();
        return $result->id;
    }
    public function itemStoreExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        $data = [];
        $this->modelClass = Medicine::class;
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                $result = $this->modelClass::where(['name' => $item?->name, 'user_id' => auth()->id()])->first();
                if (!$result) {
                    $result = $this->modelClass::create([
                        'user_id' => auth()->id(),
                        'medicine_type_id' => $item?->medicine_type_id,
                        'type' => $item?->type,
                        'day' => $item?->day,
                        'many_day' => $item?->many_day,
                        'qty' => $item?->qty,
                        'comment' => $item?->comment,
                        'name' => $item?->name
                    ]);
                } else {
                    $result->update([
                        'medicine_type_id' => $item?->medicine_type_id ?? $result->medicine_type_id,
                        'type' => $item?->type ?? $result->type,
                        'day' => $item?->day ?? $result->day,
                        'many_day' => $item?->many_day ?? $result->many_day,
                        'qty' => $item?->qty ?? $result->qty,
                        'comment' => $item?->comment ?? $result->comment,
                        'name' => $item?->name ?? $result->name
                    ]);
                }
                $data[] = $result;
            }
        }
        return  $data;
    }
}
