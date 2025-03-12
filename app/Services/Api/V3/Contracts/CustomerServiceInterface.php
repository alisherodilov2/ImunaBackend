<?php

namespace App\Services\Api\V3\Contracts;

interface CustomerServiceInterface
{

    public function filter();

    public function store($request);

    public function storeExcel($request);

    public function update($id, $request);
    
    public function edit($id, $request);

}
