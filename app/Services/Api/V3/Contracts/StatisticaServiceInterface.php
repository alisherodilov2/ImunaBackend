<?php

namespace App\Services\Api\V3\Contracts;

interface StatisticaServiceInterface
{

    public function statistica($request);
    public function statisticaCounterparty($request);
    public function statisticaHome($request);
}
