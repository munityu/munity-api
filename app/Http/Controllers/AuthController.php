<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();
        $result = User::where(['name' => $data['name']])->get()->all();
        if ($result)
            return response(['error' => 'The name has already been taken'], 422);
        $result = User::where(['email' => $data['email']])->get()->all();
        if ($result)
            return response(['error' => 'The email has already been taken'], 422);
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }
    public function signin(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
        if (!$token = auth('api')->attempt($credentials))
            return response(['error' => 'Unauthorized'], 401);
        return $this->respondWithToken($token);
    }
    public function signout(Request $request)
    {
        auth()->logout();
        return response(['message' => 'Successfully logged out']);
    }
    protected function respondWithToken(mixed $token)
    {
        return response([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
