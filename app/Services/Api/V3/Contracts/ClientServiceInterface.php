<?php

namespace App\Services\Api\V3\Contracts;

interface ClientServiceInterface
{


    public function filter($request);
    // public function store($request);
    public function receptionFilter($request);
    public function autocomplate($request);
    public function register($request);
    // public function add($request);
    public function update($id, $request);
    public function edit($id, $request);
    public function delete($id);
    public function doctorResult($id, $request);
    public function doctorRoom($request);
    public function clientAllData($request);

    public function counterpartyAllClient($request);
    public function statsianar($request);
    public function statsionarFinish($id, $request);
    public function dierktorDelete($id);
    public function servicePrintChek($request);
    public function certificate($request);
    public function certificateDownload($request);
    public function bloodtest($request);
    public function bloodtestAccept($id, $request);
    public function laboratoryClient($request);
    public function laboratoryClientShow($id);
    public function laboratoryClientSave($id, $request);
    public function laboratoryTemplateResultFiles($id, $request);
    public function laboratoryTemplateResultFilesUpdate($id, $request);
    public function laboratoryTable($request);
    public function laboratoryTableSave($request);
    public function smsSend($id, $request);
    public function alertSoket($id,$request);
    public function cashFilter($request);
    public function doctorStatsianar($request);
    public function doctorClientAll($request);
    
}
