<?php

namespace App\Http\Resources\Client;

use App\Models\ClientResult;
use App\Models\ClientTime;
use App\Models\Departments;
use App\Models\DirectorSetting;
use App\Models\Services;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceptionClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $directorSetting = DirectorSetting::where('user_id', auth()->user()->owner_id)->first();
        $last = $this->clientItem->last();
        $clientItem = $this->clientItem->filter(function ($item) use ($last) {
            return Carbon::parse($item->created_at)->format('Y-m-d') == Carbon::parse($last->created_at)->format('Y-m-d');
        });
        $clientItemResource = [
            'id' => $last->id,
            'discount' => $last->discount,
            'pay_total_price' => $last->pay_total_price,
            'total_price' => $last->total_price,
            'created_at' => $last->created_at,
        ];
        // clientResult
        $directorSetting = DirectorSetting::where('user_id', auth()->user()->owner_id)->first();

        if ($directorSetting->is_reg_department) {
            $departmentId = $this->clientItem->flatMap(function ($item) {
                return ClientResult::where(['client_id' => $item->id])->pluck('department_id');
            });
            $clientItemResource['department'] = Departments::whereIn('id', $departmentId)->get('name');
        }
        if ($directorSetting->is_reg_queue_number) {
            $departmentId = $this->clientItem->flatMap(function ($item) {
                return $item->clientValue->pluck('service_id');
            });
            $queue_number = [];
            foreach ($clientItem as $value) {
                $departmentId = $value->clientValue->where('is_at_home', 0);
                foreach ($departmentId as $item) {
                    if (!collect($queue_number)->where(['client_item_id' => $value->id, 'department_id' => $item->department_id])->first()) {
                        $dep = Departments::find($item->department_id);
                        if ($dep->is_queue_number) {
                            if ($dep->is_reg_time) {
                                $clintTime = ClientTime::where([
                                    'client_id' => $value->client_id,
                                    'department_id' => $dep->id,
                                ])->first();
                                $queue_number[] = [
                                    // 'id'=>
                                    'client_item_id' => $item->id,
                                    'created_at' => $item->created_at,
                                    'department_id' => $dep->id,
                                    'queue_number' => $clintTime->agreement_time
                                ];
                            } else {
                                $queue_number[] = [
                                    // 'id'=>
                                    'client_item_id' => $item->id,
                                    'created_at' => $item->created_at,
                                    'department_id' => $dep->id,
                                    'queue_number' => $dep->letter . '-' . $item->queue_number
                                ];
                            }
                        }
                    }
                }
            }
            $clientItemResource['queue_number'] = $queue_number;
            // $departmentId = $this->clientItem->flatMap(function ($item) {
            //     return ClientResult::where(['client_id' => $item->id])->pluck('department_id');
            // });
            // $clientItemResource['queue_number'] = Departments::whereIn('id', $departmentId)->get('name');
        }

        if ($directorSetting->is_reg_service) {
            $departmentId = $this->clientItem->flatMap(function ($item) {
                return $item->clientValue->pluck('service_id');
            });
            $clientItemResource['service'] = Services::whereIn('id', $departmentId)->get('name');
        }
        // if ($directorSetting->is_reg_service) {
        //     $data['service'] = Services::whereIn('id', $this->clientValue->pluck('service_id')) ->get('name');
        // }
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'client_item' => [$clientItemResource],
            'balance' => $this->balance,
        ];

        if ($directorSetting->is_reg_phone) {
            $data['phone'] =  $this->phone;
        }
        if ($directorSetting->is_reg_person_id) {
            $data['person_id'] =  $this->person_id;
        }
        if ($directorSetting->is_reg_data_birth) {
            $data['data_birth'] = $this->data_birth;
        }
        if ($directorSetting->is_reg_citizenship) {
            $data['citizenship'] = $this->citizenship;
        }
        if ($directorSetting->is_reg_sex) {
            $data['sex'] =   $this->sex;
        }
        if ($directorSetting->is_reg_use_status) {
            $data['use_status'] = $this->use_status;
        }
        if ($directorSetting->is_reg_address) {
            $data['address'] = $this->address;
        }
        return  $data;
    }
}
