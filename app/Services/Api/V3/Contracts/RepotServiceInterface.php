<?php

namespace App\Services\Api\V3\Contracts;

interface RepotServiceInterface
{

    public function repot($request);
    public function counterparty($request);
    public function counterpartyShow($id, $request);
    public function counterpartyClientShow($id, $request);
    public function dailyRepot($request);
    function dailyRepotUpdate($request);
    public function dailyRepotShow($id);
    public function doctor($request);
    public function doctorShow($id, $request);
    public function doctorShowService($id);
    function excelRepot($request);
}
