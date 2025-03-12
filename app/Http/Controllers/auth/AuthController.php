<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordChangeRequest;
use App\Http\Requests\Auth\SupperAdminLoginRequest;
use App\Http\Resources\User\ProfileResource;
use App\Models\User;
use App\Services\Api\Contracts\AuthServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    use ApiResponse;
    public $modelClass = User::class;
    public function supperAdminlogin(SupperAdminLoginRequest $request, AuthServiceInterface $service)
    {
        $request->validated();
        return $this->success($service->login($request, User::USER_ROLE_SUPPER_ADMIN));
    }
    public function login(LoginRequest $request, AuthServiceInterface $service)
    {
        $request->validated();
        return $this->success($service->login($request));
    }

    public function logout(Request $request, AuthServiceInterface $service)
    {
        return $this->success($service->logout($request));
    }
    public function profile(Request $request, AuthServiceInterface $service)
    {
        return $this->success(($service->profile($request)));
    }
    // public function profileUpdate(Request $request, UserServiceInterface $service)
    // {
    //     return $this->success(new ProfileResource($service->edit(auth()->id(), $request)));
    // }

    public function passwordChange(PasswordChangeRequest $request, AuthServiceInterface $service)
    {
        $request->validated();
        return $this->success($service->passwordChange($request));
    }
    public function file(Request $request)
    {
       
          $url = $request->query('url');

        $filename = '/storage/branch/1717432385.png';
        $path = public_path($url);
        
        if (!file_exists($path)) {
             $path = public_path(  $filename);
        }
        $mimeType = mime_content_type($path);
        $content = file_get_contents($path);
        return   Response::make($content, 200, [
            'Content-Type' => $mimeType,
           'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }
}
