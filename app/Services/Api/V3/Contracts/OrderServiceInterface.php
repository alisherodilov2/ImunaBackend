<?php

namespace App\Services\Api\V3\Contracts;

interface OrderServiceInterface
{

    public function filter($request);

    public function store($request);

    public function add($request);

    public function storeExcel($request);

    public function update($id, $request);
    public function edit($id, $request);

}
