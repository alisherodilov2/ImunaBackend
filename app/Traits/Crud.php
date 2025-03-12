<?php

namespace App\Traits;

trait Crud
{
    public function store($request)
    {
        $model = new $this->modelClass;
        $model = $this->modelClass::create($request->only($model->fillable));
        $model = $this->attachTranslates($model, $request);
        $this->attachFiles($model, $request);
        return $model;
    }

    public function attachTranslates($model, $request)
    {
        if (isset($model->translatable)) {
            if (is_array($model->translatable)) {
                $model->setTranslationsArray($request->only($model->translatable) ?? []);
            }
        }
        return $model;
    }

    public function attachFiles($model, $request)
    {
        if ($model->fileFields) {
            foreach ($model->fileFields as $item) {
                if ($request->has($item)) {
                    if ($request->file($item)) {
                        $model->$item = uploadFile($request->file($item), $model->key, $model->$item);
                    } else {
                        $model->$item = $request->$item;
                    }
                }
            }
            $model->save();
        }
    }

    
    public function update($id, $request)
    {
        $model = $this->modelClass::find($id);
        $model->update($request->only($model->fillable));
        $model = $this->attachTranslates($model, $request);
        $this->attachFiles($model, $request);
        return $model;
    }
    public function uploadFile($key, $request)
    {
        $res = false;
        if ($request->hasFile($key)) {
            $file = $request->file($key);
            $ext = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $ext;
            $file->move(request()->route()->getName().'/', $fileName);
            $res = request()->route()->getName()."/" . $fileName;
        }
        return $res;
    }
}
