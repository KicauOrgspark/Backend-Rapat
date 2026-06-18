<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('nomor_induk', $request->nomor_induk)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'nomor induk atau password salah'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'data' => [
                'access_token' => $token,
                'user'         => new UserResource($user),
            ],
        ], 200);
    }

    public function logout()
    {
        try {
            auth()->user()->currentAccessToken()->delete();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout gagal',
                'error' => $e->getMessage(),
            ], 500);
        }
        return response()->json([
            'message' => 'Logout berhasil'
        ], 200);
    }
}
