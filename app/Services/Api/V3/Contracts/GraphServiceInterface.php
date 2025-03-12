<?php

namespace App\Services\Api\V3\Contracts;

interface GraphServiceInterface
{

    public function filter($request);
    public function store($request);
    public function add($request);
    public function update($id, $request);
    public function edit($id, $request);
    public function delete($id);
    public function graphItemDelete($request);
    public function workingDateCheck($request);
    public function graphArchiveShow($request);
    // doctor kanieti uchun
    public function graphClient($request);
    // muolajalar
    public function treatment($request);
    public function treatmentUpdate($id, $request);
    public function atHomeTreatment($request);
    public function shelfNumberLimit($id);
}
