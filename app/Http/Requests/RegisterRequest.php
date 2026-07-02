<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use App\Rules\ValidEmployee;
use App\Rules\ValidCaptcha;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint
    }

    public function rules(): array
    {
        return [
            'user_name'             => ['required', 'string', 'min:3', 'max:50', 'unique:users,user_name'],
            'company_code'          => ['required', 'string', 'exists:companies,code'],
            'employee_id'           => ['required', 'string', new ValidEmployee($this->input('company_code'))],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', new StrongPassword()],
            'password_confirmation' => ['required'],
            'captcha_key'           => ['required', 'string'],
            'captcha_input'         => ['required', 'string', new ValidCaptcha($this->input('captcha_key'))],
            'terms'                 => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must accept the terms and conditions.',
            'password.confirmed' => 'Password and confirm password do not match.',
        ];
    }
}