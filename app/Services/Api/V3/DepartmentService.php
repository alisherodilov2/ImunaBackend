<?php

namespace App\Services\Api\V3;

use App\Http\Resources\Department\MonitorResource;
use App\Models\ClientValue;
use App\Models\Departments;
use App\Models\DepartmentTemplateItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\DepartmentServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class DepartmentService implements DepartmentServiceInterface
{
    public $modelClass = Departments::class;
    use Crud;
    public function filter()
    {

        $user = auth()->user();
        if ($user->role == User::USER_ROLE_RECEPTION) {
            return $this->modelClass::with('user')
                ->where('user_id', $user->owner_id)
                ->whereNull('parent_id')
                ->get();
        }
        if ($user->role == User::USER_ROLE_DOCTOR) {
            return $this->modelClass::with('departmentTemplateItem.template.templateItem.templateCategory')
                ->where('user_id', $user->owner_id)
                ->whereNull('parent_id')
                ->get();
        }
        return $this->modelClass::with(['departmentTemplateItem.template', 'departmentValue'])
            ->where('user_id', auth()->id())
            ->whereNull('parent_id')
            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;

        $result = $this->store($request);
        if (isset($request->department_value)) {
            $reqDdata = json_decode($request->department_value);
            if (count($reqDdata) > 0) {
                $insertData = array_map(function ($value) use ($id, $result) {
                    return [
                        'user_id' => $id,
                        'parent_id' => $result->id,
                        'room_number' => $value->room_number,
                        'room_type' => $value->room_type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $reqDdata);
                Departments::insert($insertData);
            }
        }
        return $this->modelClass::with(['departmentTemplateItem.template', 'departmentValue'])

            ->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);
        if (isset($request->department_value)) {
            $reqDdata = json_decode($request->department_value);
            Departments::where(['parent_id' =>  $result->id,  'user_id' => auth()->id()])
                ->whereNotIn('id', collect($reqDdata)->filter(function ($item) {
                    return isset($item->id) ?  is_int($item->id)  : false; // Ensure the ID is an integer
                })->pluck('id'))
                ->delete();
            foreach ($reqDdata as $key => $value) {
                if (isset($value->id)) {
                    $dfind = Departments::find($value->id);
                    if ($dfind) {
                        $dfind->update([
                            'room_number' => $value->room_number ?? $dfind->room_number,
                            'room_type' => $value->room_type ?? $dfind->room_type,
                            'parent_id' => $result->id,
                        ]);
                    } else {
                        Departments::create([
                            'user_id' => auth()->id(),
                            'parent_id' => $result->id,
                            'room_number' => $value->room_number,
                            'room_type' => $value->room_type,
                        ]);
                    }
                } else {
                    Departments::create([
                        'user_id' => auth()->id(),
                        'parent_id' => $result->id,
                        'room_number' => $value->room_number,
                        'room_type' => $value->room_type,
                    ]);
                }
            }
        }
        if (isset($request->is_setting) && $request->is_setting == 1) {
            if (isset($request->department_template_item)) {
                $reqDdata = json_decode($request->department_template_item);
                DepartmentTemplateItem::where(['department_id' =>  $result->id])
                    ->whereNotIn('template_id', collect($reqDdata)->filter(function ($item) {
                        return isset($item->template_id) ?  is_int($item->template_id)  : false; // Ensure the ID is an integer
                    })->pluck('template_id'))
                    ->delete();
                if (count($reqDdata) > 0) {
                    foreach ($reqDdata as $key => $value) {
                        $dfind = DepartmentTemplateItem::where([
                            'template_id' => $value->template_id,
                            'department_id' => $result->id,
                        ])->first();
                        if ($dfind) {
                            $dfind->update([
                                'department_id' => $result->id,
                                'template_id' => $value->template_id,
                            ]);
                        } else {
                            DepartmentTemplateItem::create([
                                'department_id' => $result->id,
                                'template_id' => $value->template_id,
                            ]);
                        }
                    }
                }
            }
        }
        return $this->modelClass::with(['departmentTemplateItem.template', 'departmentValue'])

            ->find($result->id);
    }
    public function delete($id)
    {
        $result = $this->modelClass::find($id);
        Departments::where('parent_id', $result->id)->delete();
        $result->delete();
        return $result->id;
    }

    // queue_number_limit 
    public function queueNumberLimitGenerate($count)
    {
        $result = [];
        for ($i = 1; $i <= $count; $i++) {
            $result[] = $i;
        }
        return $result;
    }


    public function queueNumberLimit($id)
    {
        $result = $this->modelClass::find($id);
        $limitData = $this->queueNumberLimitGenerate($result->queue_number_limit);
        $clintValue = ClientValue::where(['department_id' => $result->id, 'is_active' => 1])
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->pluck('queue_number')
            ->unique()
            ->filter(function ($value) {
                return is_numeric($value) && $value > 0;
            })
            ->values()
            ->toArray();
        return [
            'data' => $clintValue,
            'clintValue'=>ClientValue::where(['department_id' => $result->id, 'is_active' => 1])
           
            // ->whereDate('created_at', now()->format('Y-m-d'))
            
            ->get(),
            'date'=>now()->format('Y-m-d'),
            'limit' => $limitData,
            'department' => $result
        ];
    }


    // monitor uchun xonalar
    public function monitor($request)
    {
        $ids =    json_decode($request->ids);
        $res = $this->modelClass::whereIn('id', $ids)
        ->with(['departmentValue'])
        ->get();
        return MonitorResource::collection($res);
    }
}
