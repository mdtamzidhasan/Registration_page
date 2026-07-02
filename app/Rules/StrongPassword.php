<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[-@#$%^+=])[A-Za-z\d\-@#$%^+=]{8,15}$/';

        if (!preg_match($pattern, $value)) {
            $fail('The :attribute must be 8-15 characters long and include at least one uppercase letter, one lowercase letter, one digit, and one special character (-@#$%^+=).');
        }
    }
}