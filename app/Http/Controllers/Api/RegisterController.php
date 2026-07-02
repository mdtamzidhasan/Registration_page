<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\RegisterService;

class RegisterController extends Controller
{
    public function __construct(protected RegisterService $registerService) {}

    public function store(RegisterRequest $request)
    {
        $result = $this->registerService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => [
                    'id'        => $result['user']->id,
                    'user_name' => $result['user']->user_name,
                    'email'     => $result['user']->email,
                    'employee'  => $result['user']->employee->name,
                    'company'   => $result['user']->employee->company->name,
                ],
                'access_token' => $result['token'],
                'token_type'   => 'bearer',
            ],
        ], 201);
    }
}