<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Graph\GraphResource;
use App\Models\Graph;
use App\Models\Graphs;
use App\Services\Api\V3\Contracts\GraphServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GraphController extends Controller
{

    public $modelClass = Graph::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, GraphServiceInterface $service)
    {
        $res = ($service->filter($request));
        return $this->success($res);
    }
    public function store(Request $request, GraphServiceInterface $service)
    {
        if (isset($request->use_status) && $request->use_status == 'treatment') {

            return
                $this->success(['graph_achive' => ($service->add($request))]);
        }
        return $this->success(new GraphResource($service->add($request)));
    }
    public function graphItemDelete(Request $request, GraphServiceInterface $service)
    {

        return $this->success(new GraphResource($service->graphItemDelete($request)));
    }
    public function graphClient(Request $request, GraphServiceInterface $service)
    {

        return $this->success(($service->graphClient($request)));
    }
    public function treatmentUpdate(Request $request, $id, GraphServiceInterface $service)
    {

        return $this->success(($service->treatmentUpdate($id, $request)));
    }
    public function treatment(Request $request, GraphServiceInterface $service)
    {

        return $this->success(($service->treatment($request)));
    }
    public function atHomeTreatment(Request $request, GraphServiceInterface $service)
    {

        return $this->success(($service->atHomeTreatment($request)));
    }
    public function graphArchiveShow(Request $request, GraphServiceInterface $service)
    {

        return $this->success(($service->graphArchiveShow($request)));
    }
    public function workingDateCheck(Request $request, GraphServiceInterface $service)
    {

        return $this->success(($service->workingDateCheck($request)));
    }
    public function update(Request $request, $id, GraphServiceInterface $service)
    {
        if (isset($request->use_status) && $request->use_status == 'treatment') {

            return
                $this->success(['graph_achive' => ($service->edit($id, $request))]);
        }
        return $this->success(new GraphResource($service->edit($id, $request)));
    }
    public function delete($id, GraphServiceInterface $service)
    {
        return $this->success([
            'data' =>  $service->delete($id),
        ]);
    }
    public function shelfNumberLimit($id, GraphServiceInterface $service)
    {
        return $this->success($service->shelfNumberLimit($id));
    }
}
