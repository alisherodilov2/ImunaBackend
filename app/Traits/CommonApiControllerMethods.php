<?php

namespace App\Traits;

trait CommonApiControllerMethods
{

    public function showResource($id)
    {
        return new $this->resource($this->modelClass::find($id));
    }
    public function show($id)
    {
        return $this->modelClass::find($id);
    }

    public function delete($id)
    {
        $idAll = json_decode($id);
        if (is_array($idAll)) {
            $this->modelClass::whereIn('id', $idAll)->delete();
            return $this->success($idAll);
        }
        $this->modelClass::destroy($id);
        return $this->success($id);

    }
}
