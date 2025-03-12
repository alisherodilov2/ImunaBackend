<?php

namespace App\Services\Api\V3\Contracts;

interface TgBotConnectServiceInterface
{

    public function filter();

    public function store($request);
    
    // public function add($request);

    public function storeExcel($request);

    public function update($id, $request);

    public function tgConnect($request);

}
