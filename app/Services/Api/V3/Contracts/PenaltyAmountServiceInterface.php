<?php

namespace App\Services\Api\V3\Contracts;

interface PenaltyAmountServiceInterface
{

    public function filter();
    public function add($request);
    public function edit($id, $request);
    public function delete($id);
    public function store($request);

    public function storeExcel($request);

    public function update($id, $request);

}
