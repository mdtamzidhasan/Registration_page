<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class RegisterService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $company = Company::where('code', $data['company_code'])->firstOrFail();

            $employee = Employee::where('company_id', $company->id)
                ->where('employee_id', $data['employee_id'])
                ->firstOrFail();

            $user = User::create([
                'employee_id' => $employee->id,
                'user_name'   => $data['user_name'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'status'      => true,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user'  => $user->load('employee.company'),
                'token' => $token,
            ];
        });
    }
}