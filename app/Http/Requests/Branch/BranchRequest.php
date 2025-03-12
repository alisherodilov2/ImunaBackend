<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class BranchRequest extends FormRequest
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
            'name' => 'required|min:2',
        ];
    }
    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // protected function failedValidation(Validator $validator)
    // {
    //     if ($this->wantsJson()) {
    //         $response = response()->json([
    //             'success' => false,
    //             'message' => 'Ops! Some errors occurred',
    //             'errors' => $validator->errors(),
    //         ]);
    //     } else {
    //         $response = redirect()
    //             ->route('guest.login')
    //             ->with('message', 'Ops! Some errors occurred')
    //             ->withErrors($validator);
    //     }

    //     throw (new ValidationException($validator, $response))
    //         ->errorBag($this->errorBag)
    //         ->redirectTo($this->getRedirectUrl());
    // }
}
