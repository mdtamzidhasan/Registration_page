<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\CaptchaController;

// Public routes
Route::middleware('throttle:5,1')->group(function () {
    Route::get('/captcha', [CaptchaController::class, 'generate']);
    Route::post('/register', [RegisterController::class, 'store']);
});

// Protected routes (JWT)
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Illuminate\Http\Request $request) {
        return $request->user();
    });
    // অন্যান্য protected routes
});