<?php

namespace App\Services\Api\V3\Contracts;

interface MasterServiceInterface
{

    public function filter();

    public function store($request);

    public function storeExcel($request);

    public function edit($id, $request);

    public function orderShow($request);
    public function masterPay($id);

}
