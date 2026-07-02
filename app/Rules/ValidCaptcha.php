<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;
use Closure;

class ValidCaptcha implements ValidationRule
{
    protected ?string $captchaKey;

    public function __construct(?string $captchaKey)
    {
        $this->captchaKey = $captchaKey;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cacheKey = "captcha:{$this->captchaKey}";
        $attemptKey = "captcha_attempts:{$this->captchaKey}";

        $original = Cache::get($cacheKey);

        if (!$original) {
            $fail('Captcha has expired. Please try again.');
            return;
        }

        $attempts = Cache::get($attemptKey, 0);
        if ($attempts >= 3) {
            Cache::forget($cacheKey);
            Cache::forget($attemptKey);
            $fail('Too many failed attempts. Please request a new captcha.');
            return;
        }

        if (strtoupper($value) !== $original) {
            Cache::put($attemptKey, $attempts + 1, now()->addMinutes(3));
            $fail('Captcha does not match.');
            return;
        }

        Cache::forget($cacheKey);
        Cache::forget($attemptKey);
    }
}