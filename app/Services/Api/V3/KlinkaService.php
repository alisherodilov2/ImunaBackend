<?php

namespace App\Services\Api\V3;

use App\Models\CounterpartySetting;
use App\Models\DirectorSetting;
use App\Models\Services;
use App\Models\User;
use App\Models\UserCounterpartyPlan;
use App\Models\UserTemplateItem;
use App\Services\Api\V3\Contracts\KlinkaServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class KlinkaService implements KlinkaServiceInterface
{
    public $modelClass = User::class;
    use Crud;
    public function filter($role)
    {
        // Log::info(auth()->user()->role,$role[0]);
        if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
            return $this->modelClass::whereIn('role', $role)
                ->with(['userTemplateItem.template', 'treatmentService', 'ambulatoryService', 'userCounterpartyPlan.service'])
                ->where('owner_id', auth()->id())
                ->get();
        }
        return     $this->modelClass::whereIn('role', $role)
            ->with('department')
            ->where('owner_id', auth()->id())
            ->get();
    }
    public function add($request, $role)
    {
        $request = $request;
        if (isset($request->password)) {
            $request['password'] = Hash::make($request->password);
        } else {

            $request['password'] = Hash::make('1111');
        }

        $request['role'] = $role;
        $request['owner_id'] = auth()->id();
        $result = $this->store($request);
        if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
            if (isset($request->user_template_item)) {
                $reqDdata = json_decode($request->user_template_item);
                if (count($reqDdata) > 0) {
                    $insertData = array_map(function ($value) use ($result) {
                        return [

                            'user_id' => $result->id,
                            'template_id' => $value->template_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }, $reqDdata);
                    UserTemplateItem::insert($insertData);
                }
            }
        }
        if ($role !== User::USER_ROLE_DIRECTOR) {
            return $this->modelClass::with('department')->find($result->id);
        }
        if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {
            return $this->modelClass::with(['userTemplateItem.template', 'treatmentService', 'ambulatoryService', 'userCounterpartyPlan.service'])->find($result->id);
        }
        return $result;
    }

    public function counterpartySetting($result)
    {
        $res = UserCounterpartyPlan::where(['user_id' =>  $result->id]);
        $ambulatory_data = UserCounterpartyPlan::where(['user_id' =>  $result->id])->where('status', 'ambulatory')->get();
        $treatment_data = UserCounterpartyPlan::where(['user_id' =>  $result->id])->where('status', 'treatment')->get();
        Log::info('treatment_data', [$treatment_data]);
        $ambulatory_service_id = Services::find($ambulatory_data->first()->service_id);
        $treatment_service_id = Services::find($treatment_data->first()->service_id);
        $counterparty_setting = CounterpartySetting::where([
            // 'ambulatory_service_id' => $ambulatory_service_id->id,
            // 'treatment_service_id' => $treatment_service_id->id,
            'user_id' => auth()->id(),
            'counterparty_id' => $result->id
        ])
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->first();
        if ($counterparty_setting) {
            $counterparty_setting->update([
                'treatment_plan_qty' => $result->treatment_plan_qty ?? $counterparty_setting->treatment_plan_qty,
                'ambulatory_plan_qty' => $result->ambulatory_plan_qty ?? $counterparty_setting->ambulatory_plan_qty,
                'treatment_service_price' => $treatment_service_id->price ?? $counterparty_setting->treatment_service_price,
                'treatment_service_kounteragent_price' => $treatment_service_id->kounteragent_contribution_price ?? $counterparty_setting->treatment_service_kounteragent_price,
                'ambulatory_service_price' => $ambulatory_service_id->price ?? $counterparty_setting->ambulatory_service_price,
                'ambulatory_service_kounteragent_price' => $ambulatory_service_id->kounteragent_contribution_price ?? $counterparty_setting->ambulatory_service_kounteragent_price,
                'treatment_service_id' => $result->treatment_service_id ?? $counterparty_setting->treatment_service_id,
                'ambulatory_service_id' => $result->ambulatory_service_id ?? $counterparty_setting->ambulatory_service_id,
                'ambulatory_id_data' => json_encode($ambulatory_data->pluck('service_id')),
                'treatment_id_data' => json_encode($treatment_data->pluck('service_id'))
            ]);
        } else {
            CounterpartySetting::create([
                'counterparty_id' => $result->id,
                'treatment_plan_qty' => $result->treatment_plan_qty ?? 0,
                'ambulatory_plan_qty' => $result->ambulatory_plan_qty ?? 0,
                'treatment_service_price' => $treatment_service_id->price ?? 0,
                'treatment_service_kounteragent_price' => $treatment_service_id->kounteragent_contribution_price ?? 0,
                'ambulatory_service_price' => $ambulatory_service_id->price ?? 0,
                'ambulatory_service_kounteragent_price' => $ambulatory_service_id->kounteragent_contribution_price ?? 0,
                'treatment_service_id' => $ambulatory_data->first()->service_id ?? 0,
                'ambulatory_service_id' => $treatment_data->first()->service_id ?? 0,
                'user_id' => auth()->id(),
                'ambulatory_id_data' => json_encode($ambulatory_data->pluck('service_id')),
                'treatment_id_data' => json_encode($treatment_data->pluck('service_id'))
            ]);
        }
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['owner_id'] = auth()->id();
        if (isset($request->password)) {
            $request['password'] = Hash::make($request->password);
        }
        $result = $this->update($id, $request);

        if (auth()->user()->role === User::USER_ROLE_DIRECTOR && $result->role === User::USER_ROLE_COUNTERPARTY) {
            if (isset($request->is_main) && $request->is_main == 1) {
                User::where('id', '!=', $result->id)->where('owner_id', $result->owner_id)->update(['is_main' => 0]);
            }
            // if (isset($request->user_template_item)) {
            //     $reqDdata = json_decode($request->user_template_item);
            //     if (count($reqDdata) > 0) {
            //         $insertData = array_map(function ($value) use ($result) {
            //             return [

            //                 'user_id' => $result->id,
            //                 'template_id' => $value->template_id,
            //                 'created_at' => now(),
            //                 'updated_at' => now(),
            //             ];
            //         }, $reqDdata);
            //         UserTemplateItem::insert($insertData);
            //     }
            // }
            // UserTemplateItem::whereNotIn('template_id', collect($reqDdata)->pluck('template_id'))->where('user_id', $result->id)->delete();
            if (isset($request->user_template_item)) {
                $reqDdata = json_decode($request->user_template_item);
                UserTemplateItem::where(['user_id' =>  $result->id])
                    ->whereNotIn('template_id', collect($reqDdata)->filter(function ($item) {
                        return isset($item->template_id) ?  is_int($item->template_id)  : false; // Ensure the ID is an integer
                    })->pluck('template_id'))
                    ->delete();
                if (count($reqDdata) > 0) {
                    foreach ($reqDdata as $key => $value) {
                        $dfind = UserTemplateItem::where([
                            'template_id' => $value->template_id,
                            'user_id' => $result->id,
                        ])->first();
                        if ($dfind) {
                            $dfind->update([
                                'user_id' => $result->id,
                                'template_id' => $value->template_id,
                            ]);
                        } else {
                            UserTemplateItem::create([
                                'user_id' => $result->id,
                                'template_id' => $value->template_id,
                            ]);
                        }
                    }
                }
            }
            if (isset($request->user_counterparty_plan) && isset($request->counterparty_setting)) {
                $reqDdata = json_decode($request->user_counterparty_plan);
                UserCounterpartyPlan::where(['user_id' =>  $result->id])
                    ->delete();
                if (count($reqDdata) > 0) {
                    foreach ($reqDdata as $key => $value) {
                        UserCounterpartyPlan::create([
                            'user_id' => $result->id,
                            'service_id' => $value->service_id,
                            'status' => $value->status,
                        ]);

                        // Log::info('reqDdata', [$value]);
                        // $dfind = UserCounterpartyPlan::where([
                        //         'service_id' => $value->service_id,
                        //         'user_id' => $result->id,
                        //         'status' => $value->status,
                        //     ])
                        //     ->whereYear('created_at', date('Y'))
                        //     ->whereMonth('created_at', date('m'))
                        //     ->first();
                        // if ($dfind) {
                        //     $dfind->update([
                        //         'user_id' => $result->id,
                        //         'service_id' => $value->service_id,
                        //         'status' => $value->status,
                        //     ]);
                        // } else {
                        //     UserCounterpartyPlan::create([
                        //         'user_id' => $result->id,
                        //         'service_id' => $value->service_id,
                        //         'status' => $value->status,
                        //     ]);
                        // }
                    }
                }
            }
            $this->counterpartySetting($result);
        }
        if (auth()->user()->role === User::USER_ROLE_DIRECTOR) {

            return $this->modelClass::with(['userTemplateItem.template', 'treatmentService', 'ambulatoryService', 'userCounterpartyPlan.service'])->find($result->id);
        }
        if ($request->role !== User::USER_ROLE_DIRECTOR) {
            return $this->modelClass::with('department')->find($result->id);
        }

        return $result;
    }
    // public function storeExcel($request)
    // {
    //     $dataExcel = json_decode($request?->dataExcel);
    //     if (count($dataExcel) > 0) {
    //         foreach ($dataExcel as $item) {
    //             Klinka::updateOrCreate(
    //                 [
    //                     'name' => $item?->name,
    //                 ], [
    //                     'name' => $item?->name,
    //                     'address' => $item?->name,
    //                     'photo' => $item?->photo,
    //                 ]);
    //         }
    //     }
    //     return $this->modelClass::all();
    // }
    // direktor setting
    public function directorSetting($request)
    {
        $user = auth()->user();

        $find = DirectorSetting::where('user_id', $user->id)->first();
        if (!$find) {

            $find =  DirectorSetting::create([
                'user_id' => $user->id
            ]);
        }
        $find->update($request->all());
        // $find->update(
        //     [
        //         'is_reg_monoblok' => isset($request->is_reg_monoblok) ? $request->is_reg_monoblok :  $find->is_reg_monoblok,
        //         'is_reg_sex' => isset($request->is_reg_sex) ? $request->is_reg_sex :  $find->is_reg_sex,
        //         'is_reg_data_birth' => isset($request->is_reg_data_birth) ? $request->is_reg_data_birth :  $find->is_reg_data_birth,
        //         'is_reg_phone' => isset($request->is_reg_phone) ? $request->is_reg_phone :  $find->is_reg_phone,
        //     ]
        // );
        return $find;
    }


    public function doctor($request)
    {
        return $this->modelClass::where('owner_id', auth()->user()->owner_id)->where('role', User::USER_ROLE_DOCTOR)->get(['id', 'name','full_name']);
    }
}
