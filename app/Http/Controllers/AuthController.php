<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StoreUserRequest;
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
            $token = auth()->user()->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
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

    public function users()
    {
        $users = User::select('id', 'name', 'nomor_induk', 'role')->get();
        return response()->json([
            'message' => 'Daftar pengguna berhasil diambil',
            'data' => UserResource::collection($users),
        ], 200);
    }

    public function register(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'nomor_induk' => $request->nomor_induk,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

    
        return response()->json([
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => new UserResource($user),
            ],
        ], 201);
    }
}
