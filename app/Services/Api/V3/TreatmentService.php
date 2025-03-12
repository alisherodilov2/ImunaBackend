<?php

namespace App\Services\Api\V3;

use App\Models\Treatment;
use App\Models\TreatmentServiceItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\TreatmentServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class TreatmentService implements TreatmentServiceInterface
{
    public $modelClass = Treatment::class;
    use Crud;
    public function filter()
    {
        if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
            return $this->modelClass::Where('user_id', auth()->id())
                ->with('treatmentServiceItem.service')
                ->get();
        }
        return $this->modelClass::where('user_id', auth()->user()->owner_id)
            ->with([
                'treatmentServiceItem.service'=>function($q){
                $q->with( ['serviceProduct.product','department']);
            }
            ])
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;

        $result = $this->store($request);
        return $this->modelClass::with('treatmentServiceItem.service')->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);
        if (isset($request->is_setting)) {
            if (isset($request->treatment_service_item)) {
                $reqDdata = json_decode($request->treatment_service_item);
                TreatmentServiceItem::where(['treatment_id' =>  $result->id])
                    ->delete();
                if (count($reqDdata) > 0) {
                    foreach ($reqDdata as $key => $value) {
                        TreatmentServiceItem::create([
                            'treatment_id' => $result->id,
                            'service_id' => $value->value,
                        ]);
                    }
                }
            }
        }
        return $this->modelClass::with('treatmentServiceItem.service')->find($result->id);
    }
}
