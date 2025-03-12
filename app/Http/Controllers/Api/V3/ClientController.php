<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ClientResource;
use App\Models\Client;
use App\Models\LaboratoryTemplateResultFiles;
use App\Services\Api\V3\Contracts\ClientServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{

    public $modelClass = Client::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ClientServiceInterface $service)
    {
        if (isset($request->client_id)) {
            return $this->success($service->filter($request));
        }
        $res = ($service->filter($request));
        return $this->success($res);
    }
    public function cashFilter(Request $request, ClientServiceInterface $service)
    {
        if (isset($request->client_id)) {
            return $this->success($service->cashFilter($request));
        }
        $res = ($service->cashFilter($request));
        return $this->success($res);
    }

    public function doctorClientAll(Request $request, ClientServiceInterface $service)
    {
        $res = ($service->doctorClientAll($request));
        return $this->success($res);
    }


    public function bloodtest(Request $request, ClientServiceInterface $service)
    {

        $res = ($service->bloodtest($request));
        return $this->success($res);
    }
    public function laboratoryTable(Request $request, ClientServiceInterface $service)
    {

        $res = ($service->laboratoryTable($request));
        return $this->success($res);
    }
    public function laboratoryClient(Request $request, ClientServiceInterface $service)
    {

        $res = ($service->laboratoryClient($request));
        return $this->success($res);
    }
    public function bloodtestAccept(Request $request, $id, ClientServiceInterface $service)
    {

        $res = ($service->bloodtestAccept($id, $request));
        return $this->success($res);
    }
    public function laboratoryClientShow($id, ClientServiceInterface $service)
    {

        $res = ($service->laboratoryClientShow($id));
        return $this->success($res);
    }
    public function laboratoryClientSave(Request $request, $id, ClientServiceInterface $service)
    {

        $res = ($service->laboratoryClientSave($id, $request));
        return $this->success($res);
    }
    public function laboratoryTableSave(Request $request, ClientServiceInterface $service)
    {

        $res = ($service->laboratoryTableSave($request));
        return $this->success($res);
    }

    public function smsSend(Request $request, $id, ClientServiceInterface $service)
    {

        $res = ($service->smsSend($id, $request));
        return $this->success($res);
    }
    public function laboratoryTemplateResultFiles(Request $request, $id, ClientServiceInterface $service)
    {

        $res = ($service->laboratoryTemplateResultFiles($id, $request));
        return $this->success($res);
    }
    public function laboratoryTemplateResultFilesUpdate(Request $request, $id, ClientServiceInterface $service)
    {

        $res = ($service->laboratoryTemplateResultFilesUpdate($id, $request));
        return $this->success($res);
    }


    public function servicePrintChek(Request $request, ClientServiceInterface $service)
    {

        $res = ($service->servicePrintChek($request));
        return $this->success($res);
    }


    public function certificate(Request $request, ClientServiceInterface $service)
    {

        $res = ($service->certificate($request));
        return $this->success($res);
    }
    public function certificateDownload(Request $request, ClientServiceInterface $service)
    {

        $res = ($service->certificateDownload($request));
        return ($res);
    }


    public function alertSoket(Request $request, $id, ClientServiceInterface $service)
    {

        $res = ($service->alertSoket($id, $request));
        return ($res);
    }
    public function receptionFilter(Request $request, ClientServiceInterface $service)
    {

        $res = ($service->receptionFilter($request));
        return $this->success($res);
    }
    public function counterpartyAllClient(Request $request, ClientServiceInterface $service)
    {
        $res = ($service->counterpartyAllClient($request));
        return $this->success($res);
    }
    public function register(Request $request, ClientServiceInterface $service)
    {
        // if ($request->status == 'payed' && $request->id > 0) {

        //     return $this->success(($service->register($request)));
        // }
        // return $this->success(new ClientResource($service->register($request)));
        return $this->success(($service->register($request)));
    }
    public function autocomplate(Request $request, ClientServiceInterface $service)
    {
        return $this->success(($service->autocomplate($request)));
    }
    public function doctorResult(Request $request, $id, ClientServiceInterface $service)
    {
        return $this->success(($service->doctorResult($id, $request)));
    }
    public function doctorRoom(Request $request, ClientServiceInterface $service)
    {
        return $this->success(($service->doctorRoom($request)));
    }
    public function update(Request $request, $id, ClientServiceInterface $service)
    {
        if (isset($request->password)) {
            $check = $this->modelClass::where(['role' => $request->role])
                ->where('id', '!=', $id)
                ->first();
            if ($check) {
                $passwords = Hash::check($request->password, $check->password) ?? false;
                if ($passwords) {
                    return $this->error([
                        'message' => 'Пользователь с таким именем уже существует',
                    ], 422);
                }
            }
        }
        return $this->success(new ClientResource($service->edit($id, $request)));
    }
    public function delete($id, ClientServiceInterface $service)
    {
        return $this->success([
            'data' =>  $service->delete($id),
        ]);
    }
    public function dierktorDelete($id, ClientServiceInterface $service)
    {
        return $this->success([
            'data' =>  $service->dierktorDelete($id),
        ]);
    }
    public function clientAllData(Request $request, ClientServiceInterface $service)
    {
        return $this->success(
            $service->clientAllData($request),
        );
    }
    public function statsianar(Request $request, ClientServiceInterface $service)
    {
        return $this->success(
            $service->statsianar($request),
        );
    }
    public function doctorStatsianar(Request $request, ClientServiceInterface $service)
    {
        return $this->success(
            $service->doctorStatsianar($request),
        );
    }

    public function statsionarFinish(Request  $request, $id, ClientServiceInterface $service)
    {
        return $this->success(
            $service->statsionarFinish($id, $request),
        );
    }

    public function fileDelete($id)
    {
        $this->modelClass = LaboratoryTemplateResultFiles::class;
        $idAll = json_decode($id);
        if (is_array($idAll)) {
            $this->modelClass::whereIn('id', $idAll)->delete();
            return $this->success($idAll);
        }
        $model = new $this->modelClass;
        $find = $this->modelClass::find($id);
        if ($model->fileFields) {
            foreach ($model->fileFields as $item) {
                deleteFile($find[$item]);
            }
        }
        // return $this->modelClass->fileFields;
        $find->delete();
        // $this->modelClass::destroy($id);
        return $this->success($id);
    }
}
