<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;

class BrowserSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = $request->validate([
            'token' => ['required', 'string', 'max:2000'],
        ])['token'];

        if (PersonalAccessToken::findToken($token) === null) {
            return response()->json([
                'message' => 'The sign-in session could not be saved.',
            ], 422);
        }

        $request->session()->put('api_token', Crypt::encryptString($token));
        $request->session()->regenerate();

        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->session()->forget('api_token');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
    }
}
