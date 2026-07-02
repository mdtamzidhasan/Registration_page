<?php

namespace App\Rules;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

class ValidEmployee implements ValidationRule
{
    protected ?string $companyCode;

    public function __construct(?string $companyCode)
    {
        $this->companyCode = $companyCode;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $company = Company::where('code', $this->companyCode)->where('status', true)->first();

        if (!$company) {
            $fail('Invalid company code.');
            return;
        }

        $employee = Employee::where('company_id', $company->id)
            ->where('employee_id', $value)
            ->where('status', true)
            ->first();

        if (!$employee) {
            $fail('Invalid employee information.');
            return;
        }

        // Already registered?
        if ($employee->user()->exists()) {
            $fail('This employee has already registered an account.');
        }
    }
}