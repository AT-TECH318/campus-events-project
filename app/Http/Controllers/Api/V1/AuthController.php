<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only([
            'name',
            'email',
            'password',
        ]));

        $user->load('role');

        return $this->tokenResponse($user, 'Registration successful.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $user->load('role');

        return $this->tokenResponse($user, 'Login successful.', 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return response()->json([
                'message' => 'This session cannot be revoked as a bearer token.',
            ], 400);
        }

        $token->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    private function tokenResponse(User $user, string $message, int $status): JsonResponse
    {
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => $message,
            'token' => $token,
            'token_type' => 'Bearer',
            'data' => (new UserResource($user))->resolve(),
        ], $status);
    }
}
