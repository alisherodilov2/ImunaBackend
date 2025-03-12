<?php

namespace App\Services\Api\V3\Contracts;

interface ProductServiceInterface
{

    public function store($request);
    public function add($request);
    public function update($id, $request);
    public function edit($id, $request);
    public function show($id, $request);
    public function repot($request);
    public function repotShow($request);
    public function filter($request);
    public function reportProductAmbulatorAndTreatment($request);
}
