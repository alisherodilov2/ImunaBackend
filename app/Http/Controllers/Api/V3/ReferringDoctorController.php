<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferringDoctor\ReferringDoctorResource;
use App\Models\ReferringDoctor;
use App\Services\Api\V3\Contracts\ReferringDoctorServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\CommonApiControllerMethods;
use App\Traits\HistoryTraid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ReferringDoctorController extends Controller
{

    public $modelClass = ReferringDoctor::class;

    use ApiResponse, CommonApiControllerMethods, HistoryTraid;

    public function index(Request $request, ReferringDoctorServiceInterface $service)
    {
        $res = ($service->filter($request));
        return $this->success($res);
    }
    public function store(Request $request, ReferringDoctorServiceInterface $service)
    {

        return $this->success(new ReferringDoctorResource($service->add($request)));
    }
    public function update(Request $request, $id, ReferringDoctorServiceInterface $service)
    {
        return $this->success(new ReferringDoctorResource($service->edit($id, $request)));
    }
    public function serviceShow($id, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->serviceShow($id)));
    }
    public function serviceUpdate(Request $request, $id, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->serviceUpdate($id, $request)));
    }
    public function show(Request $request, $id, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->show($id, $request)));
    }
    public function treatment(Request $request, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->treatment($request)));
    }
    public function referringDoctorBalance(Request $request, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->referringDoctorBalance($request)));
    }
    public function referringDoctorPay(Request $request, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->referringDoctorPay($request)));
    }
    public function doctorPay(Request $request, $id, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->doctorPay($id, $request)));
    }
    public function doctorPayShow(Request $request, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->doctorPayShow($request)));
    }
    public function fileAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upload' => 'required|mimetypes:image/png,image/jpg,image/jpeg,image/svg',
        ]);
        // $serverPhotos = new ServerPhotos();
        if ($validator->fails()) {
            return response()->json(['xatolik' => 'malumot turida xatolik bor']);
        } else {
            if ($request->hasFile('upload')) {
                $file = $request->file('upload');
                $ext = $file->getClientOriginalExtension();
                $fileName = time() . '.' . $ext;
                $file->move('serverPhotos/', $fileName);
                $fileSave  = "serverPhotos/" . $fileName;
            }
            // $serverPhotos->save();
            // $response =  Response::make(readfile(public_path() .
            // "/$serverPhotos->photo", 200))->header('Content-Type', 'image/png');
            return response()->json([
                // 'default' => "http://127.0.0.1:8000/".$serverPhotos->photo,
                'fileName' => $fileName,
                'uploaded' => 1,
                'url' => "http://127.0.0.1:8000/" . $fileSave,
            ]);
        }
    }

    //   public function referringDoctorChangeArchive($request)
    public function showReferringDoctorChangeArchive(Request $request, ReferringDoctorServiceInterface $service)
    {
        return  $this->success($service->referringDoctorChangeArchive($request));
    }
    public function storeExcel(Request $request, ReferringDoctorServiceInterface $service)
    {
        return $this->success(($service->storeExcel($request)));
    }
}
