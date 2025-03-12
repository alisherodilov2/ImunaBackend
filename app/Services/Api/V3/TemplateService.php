<?php

namespace App\Services\Api\V3;

use App\Models\Template;
use App\Models\TemplateItem;
use App\Models\User;
use App\Services\Api\V3\Contracts\TemplateServiceInterface;
use App\Traits\Crud;
use Illuminate\Support\Facades\Hash;

class TemplateService implements TemplateServiceInterface
{
    public $modelClass = Template::class;
    use Crud;
    public function filter()
    {

        $user = auth()->user();

        return $this->modelClass::with('templateItem.templateCategory')
            ->where('user_id', auth()->id())

            ->get();
    }
    public function add($request)
    {
        $request = $request;
        $id = auth()->id();
        $request['user_id'] = $id;
        $result = $this->store($request);
        if (isset($request->template_item)) {
            $reqDdata = json_decode($request->template_item);
            if (count($reqDdata) > 0) {
                $insertData = array_map(function ($value) use ($id, $result) {
                    return [

                        'template_id' => $result->id,
                        'is_comment' => $value->is_comment ?? 0,
                        'template_category_id' => $value->template_category_id,
                        'value_1' => $value->value_1 ?? '-',
                        'value_2' => $value->value_2 ?? '-',
                        'value_3' => $value->value_3 ?? '-',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $reqDdata);
                TemplateItem::insert($insertData);
            }
        }
        return $this->modelClass::with('templateItem.templateCategory')
            ->where('user_id', auth()->id())
            ->find($result->id);
    }
    public function edit($id, $request)
    {
        $request = $request;
        $request['user_id'] = auth()->id();
        $result = $this->update($id, $request);
        // TemplateItem::where('template_id', $result->id)->delete();
        if (isset($request->template_item)) {
            $reqDdata = json_decode($request->template_item);
            TemplateItem::whereNotIn('id', collect($reqDdata)->filter(function ($item) { return isset($item->id) ? is_int($item->id) : false; })->pluck('id'))
                ->where('template_id', $result->id)
                ->delete();
            if (count($reqDdata) > 0) {
                foreach ($reqDdata as $key => $value) {
                    $tfind = TemplateItem::find($value?->id ?? 0);
                    if ($tfind) {
                        $tfind->update([
                            'template_id' => $result->id,
                            'is_comment' => $value->is_comment ?? 0,
                            'template_category_id' => $value->template_category_id  ?? $tfind->template_category_id,
                            'value_1' => $value->value_1 ?? $tfind->value_1,
                            'value_2' => $value->value_2 ?? $tfind->value_2,
                            'value_3' => $value->value_3 ?? $tfind->value_3,
                        ]);
                    } else {
                        TemplateItem::create([
                            'is_comment' => $value->is_comment ?? 0,
                            'template_id' => $result->id,
                            'template_category_id' => $value->template_category_id,
                            'value_1' => $value->value_1 ?? '-',
                            'value_2' => $value->value_2 ?? '-',
                            'value_3' => $value->value_3 ?? '-',
                        ]);
                    }
                }
                // $insertData = array_map(function ($value) use ($id, $result) {
                //     return [

                //         'template_id' => $result->id,
                //         'template_category_id' => $value->template_category_id,
                //         'value_1' => $value->value_1 ?? '-',
                //         'value_2' => $value->value_2 ?? '-',
                //         'value_3' => $value->value_3 ?? '-',
                //         'created_at' => now(),
                //         'updated_at' => now(),
                //     ];
                // }, $reqDdata);
                // TemplateItem::insert($insertData);
            }
        }
        return $this->modelClass::with('templateItem.templateCategory')
            ->where('user_id', auth()->id())
            ->find($result->id);
    }
    public function delete($id)
    {
        $result = $this->modelClass::find($id);
        Template::where('parent_id', $result->id)->delete();
        $result->delete();
        return $result->id;
    }
}
