<?php

namespace App\Services\Api\V3;

use App\Http\Resources\ServiceData\ServiceDataResource;
use App\Models\Departments;
use App\Models\LaboratoryTemplate;
use App\Models\ServiceProduct;
use App\Models\Services;
use App\Models\Servicetypes;
use App\Models\User;
use App\Services\Api\V3\Contracts\ServiceDataServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class ServiceDataService implements ServiceDataServiceInterface
{
    public $modelClass = Services::class;
    use Crud;
    public function filter($request)
    {

        $user = auth()->user();
        if ($user->role == User::USER_ROLE_LABORATORY) {
            return $this->modelClass::where('user_id', $user->owner_id)
                ->whereHas('department', function ($q) {
                    $q->where('probirka', 1);
                })
                ->with(['department', 'servicetype'])
                ->withCount('laboratoryTemplate')
                ->get();
        }
        if ($user->role == User::USER_ROLE_RECEPTION) {
            return $this->modelClass::where('user_id', $user->owner_id)
                ->with(['department', 'servicetype'])
                ->with('serviceProduct.product')
                ->get();
        }
        if ($user->role == User::USER_ROLE_DOCTOR) {
            // if (isset($request->is_all) && $request->is_all == 'all') {
            //     return $this->modelClass::where('user_id', $user->owner_id)
            //         ->with(['department', 'servicetype'])
            //         ->get();
            // }
            return $this->modelClass::where(['user_id' => $user->owner_id, 'department_id' => $user->department_id])
                ->with(['department', 'servicetype'])
                ->get();
        }
        if (isset($request->department_id) && isset($request->servicetype_id)) {
            return $this->modelClass::where(
                [
                    'department_id' => $request->department_id,
                    'servicetype_id' => $request->servicetype_id,
                    'user_id' => auth()->id()
                ]
            )
                ->with(['department', 'servicetype'])
                ->get();
        }
        return $this->modelClass::where('user_id', auth()->id())
            ->with(['department', 'servicetype'])
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->store($request);
        return $this->modelClass::with(['department', 'servicetype'])->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        if (isset($request->service_product_status)) {
            $data = json_decode($request->service_product);
            ServiceProduct::where(['service_id' => $id])
                ->whereNotIn('product_id', collect($data)->pluck('value'))
                ->delete();
            foreach ($data as  $item) {
                $find = ServiceProduct::where([
                    'service_id' => $id,
                    'product_id' => $item->value,
                ])->first();
                if ($find) {
                    $find->update([
                        'service_id' => $id,
                        'product_id' => $item->value,
                        'qty' => $item->qty
                    ]);
                } else {
                    ServiceProduct::create([
                        'service_id' => $id,
                        'product_id' => $item->value,
                        'qty' => $item->qty
                    ]);
                }
            }
        }
        $result = $this->update($id, $request);
        return $this->modelClass::with(['department', 'servicetype'])->find($result->id);
    }

    public function show($id, $request)
    {
        return $this->modelClass::with(['serviceProduct.product'])->find($id);
    }
    function getFirstIndexChar($text)
    {
        // Text UTF-8 formatida ekanligini tekshirish
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        // 1-indeksdagi belgini qaytarish
        return mb_substr($text, 0, 1);
    }
    public function storeExcel($request)
    {
        $dataExcel = json_decode($request?->dataExcel);
        if (count($dataExcel) > 0) {
            foreach ($dataExcel as $item) {
                $dep = Departments::where(['name' => $item?->department, 'user_id' => auth()->id()])->first();
                $st = Servicetypes::where(['type' => $item?->servicetype, 'user_id' => auth()->id()])->first();
                if (!$dep) {
                    $dep = Departments::create([
                        'name' => $item?->department,
                        'user_id' => auth()->id(),
                        'letter' => $this->getFirstIndexChar($item?->department)
                    ]);
                }

                if (!$st) {
                    $st = Servicetypes::where([
                        'type' => $item?->servicetype,
                        'user_id' => auth()->id(),
                        'department_id' => $dep?->id

                    ])->first();
                    if (!$st) {
                        $st = Servicetypes::create([
                            'type' => $item?->servicetype,
                            'user_id' => auth()->id(),
                            'department_id' => $dep?->id
                        ]);
                    }
                }
                $findService = Services::where([
                    'servicetype_id' => $st?->id,
                    'department_id' => $dep?->id,
                    'user_id' => auth()->id(),
                    'name' => $item?->name,
                ])->first();
                if($findService) {
                    $findService->update([
                        'short_name' => $item?->short_name,
                        'price' => $item?->price,
                        'doctor_contribution_price' => $item?->doctor_contribution_price ?? 0,
                        'kounteragent_contribution_price' => $item?->kounteragent_contribution_price ?? 0,
                        'kounteragent_doctor_contribution_price' => $item?->kounteragent_doctor_contribution_price ?? 0,
                    ]);
                }else{
                    Services::create([
                        'servicetype_id' => $st?->id,
                        'department_id' => $dep?->id,
                        'user_id' => auth()->id(),
                        'name' => $item?->name,
                        'short_name' => $item?->short_name,
                        'price' => $item?->price,
                        'doctor_contribution_price' => $item?->doctor_contribution_price ?? 0,
                        'kounteragent_contribution_price' => $item?->kounteragent_contribution_price ?? 0,
                        'kounteragent_doctor_contribution_price' => $item?->kounteragent_doctor_contribution_price ?? 0,
                    ]); 
                }
                // Services::updateOrCreate(
                //     [
                //         'servicetype_id' => $st?->id,
                //         'department_id' => $dep?->id,
                //         'user_id' => auth()->id(),
                //         'name' => $item?->name,
                //     ],
                //     [
                //         'servicetype_id' => $st?->id,
                //         'department_id' => $dep?->id,
                //         'user_id' => auth()->id(),
                //         'name' => $item?->name,
                //         'short_name' => $item?->short_name,
                //         'price' => $item?->price,
                //         'doctor_contribution_price' => $item?->doctor_contribution_price ?? 0,
                //         'kounteragent_contribution_price' => $item?->kounteragent_contribution_price ?? 0,
                //         'kounteragent_doctor_contribution_price' => $item?->kounteragent_doctor_contribution_price ?? 0,

                //     ]
                // );
            }
        }
        return $this->modelClass::where('user_id', auth()->id())
            ->with(['department', 'servicetype'])
            ->get();
    }

    // LaboratoryTemplate
    public function laboratoryTemplate($id, $request)
    {
        $request = $request;
        $data = json_decode($request?->labatoratory_template);


        if (count($data) > 0) {
            LaboratoryTemplate::where('service_id', $id)
                ->whereNotIn('id', collect($data)->filter(function ($item) {
                    return isset($item->id) ?  is_int($item->id)  : false;
                })->pluck('id'))->delete();
            foreach ($data as $item) {
                $find = LaboratoryTemplate::find($item->id);
                if ($find) {
                    $find->update([
                        'service_id' => $id,
                        'name' => $item->name ?? $find->name,
                        'result' => $item->result ?? $find->result,
                        'normal' => $item->normal ?? $find->normal,
                        'extra_column_1' => $item->extra_column_1 ?? $find->extra_column_1,
                        'extra_column_2' => $item->extra_column_2 ?? $find->extra_column_2,
                        'is_result_name' => $item->is_result_name ?? $find->is_result_name
                    ]);
                } else {
                    LaboratoryTemplate::create([
                        'service_id' =>  $id,
                        'user_id' => auth()->id(),
                        'name' => $item->name ?? null,
                        'result' => $item->result ?? null,
                        'normal' => $item->normal ?? null,
                        'extra_column_1' => $item->extra_column_1 ?? null,
                        'extra_column_2' => $item->extra_column_2 ?? null,
                        'is_result_name' => $item->is_result_name ?? null
                    ]);
                }
            }
        }
        return new ServiceDataResource($this->modelClass::with(['department', 'servicetype'])
            ->withCount('laboratoryTemplate')
            ->find($id));
    }
    public function showLaboratoryTemplate($id)
    {
        if (LaboratoryTemplate::where(['service_id' => $id, 'user_id' => auth()->id()])->count() == 0) {
            LaboratoryTemplate::create([
                'service_id' =>  $id,
                'user_id' => auth()->id(),
                'name' => 'Наименование',
                'normal' => 'Норма',
                'result' => 'Результат',
                'extra_column_1' => '',
                'extra_column_2' => '',
            ]);
        }

        return [
            'service' => $this->modelClass::where('id', $id)->first([
                'name',
                'id'
            ]),
            'data' => LaboratoryTemplate::where(['service_id' => $id, 'user_id' => auth()->id()])->get()
        ];
    }
}
