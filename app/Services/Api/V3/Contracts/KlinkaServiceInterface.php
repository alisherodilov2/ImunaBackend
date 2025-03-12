<?php

namespace App\Services\Api\V3\Contracts;

interface KlinkaServiceInterface
{

    public function filter($role);
    public function store($request);
    public function add($request,$role);
    public function update($id, $request);
    public function edit($id, $request);
    public function directorSetting($request);
    public function doctor($request);
}
