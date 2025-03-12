<?php

namespace App\Services\Api\V3\Contracts;

interface RoomServiceInterface
{

    public function filter();
    public function store($request);
    public function add($request);
    public function update($id, $request);
    public function edit($id, $request);
    public function storeExcel($request);
    public function emptyRoom($request);
}
