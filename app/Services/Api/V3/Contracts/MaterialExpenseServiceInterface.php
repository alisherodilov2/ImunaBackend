<?php

namespace App\Services\Api\V3\Contracts;

interface MaterialExpenseServiceInterface
{

    public function filter();
    public function store($request);
    public function add($request);
    public function update($id, $request);
    public function edit($id, $request);
    public function delete($id);
    public function repot($request);
    public function repotShow($request);
    
}
