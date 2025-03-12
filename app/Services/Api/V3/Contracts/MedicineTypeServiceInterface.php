<?php

namespace App\Services\Api\V3\Contracts;

interface MedicineTypeServiceInterface
{

    public function filter();
    public function store($request);
    public function add($request);
    public function update($id, $request);
    public function edit($id, $request);
    public function delete($id);
    public function itemStore($request);
    public function itemUpdate($id, $request);
    public function itemDelete($id);
    public function itemAll($request);
    public function itemStoreExcel($request);
}
