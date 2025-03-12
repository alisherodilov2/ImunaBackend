<?php

namespace App\Services\Api\V3\Contracts;

interface PharmacyProductServiceInterface
{

    public function filter( $request);
    public function store($request);
    public function add($request);
    public function update($id, $request);
    public function edit($id, $request);
    public function show($id, $request);
    public function qrCodeScan($request);
}
