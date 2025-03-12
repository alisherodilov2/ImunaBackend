<?php

namespace App\Services\api\Contracts;

interface AuthServiceInterface
{
    public function register($request, $role);

    public function login($request, $role = '');

    public function logout($request);

    public function profile($request);

    public function profileUpdate($request);

    public function passwordChange($request);
  
}
