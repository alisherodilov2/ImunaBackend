<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            // 'password' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            // 'service_category_id' => 'required',
            // 'branch_id' => 'required',
            // 'photo' => 'required',
            'data_birthday' => 'required',
            'phone' => 'required',
            'jinsi' => 'required',
        ];
    }
}
