<?php

namespace App\Services\Api\V3\Contracts;

interface ReferringDoctorServiceInterface
{

    public function filter($request);
    public function store($request);
    public function add($request);
    public function update($id, $request);
    public function edit($id, $request);
    public function show($id, $request);
    public function treatment($request);
    public function referringDoctorBalance($request);
    public function referringDoctorPay($request);
    public function doctorPay($id, $request);
    public function doctorPayShow($request);
    public function referringDoctorChangeArchive($request);
    public function serviceShow($id);
    public function serviceUpdate($id, $request);
    public function storeExcel($request);
}
