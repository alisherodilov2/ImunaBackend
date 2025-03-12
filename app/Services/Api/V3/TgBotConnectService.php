<?php

namespace App\Services\Api\V3;

use App\Models\TgBotConnect;
use App\Services\Api\V3\Contracts\TgBotConnectServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Str;
class TgBotConnectService implements TgBotConnectServiceInterface
{
    public $modelClass = TgBotConnect::class;

    use Crud;
    public function filter()
    {
        return $this->modelClass::with('master')->get();
    }

    
    public function tgConnect($request)
    {
        $request = $request;
        $request = $request;
        $uniqueKey = Str::uuid();
       $this->modelClass::create([
            'key'=>$uniqueKey,
            'user_id'=>auth()->id()
        ]);
       
        return $uniqueKey;
    }


    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                TgBotConnect::updateOrCreate(
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
