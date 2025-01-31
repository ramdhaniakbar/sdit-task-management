<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // generate token expires in 7 days
        $token = $user->createToken('auth_token');

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been registered',
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::now()->addDays(7)->toDateTimeString(),
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        // validate request
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // get user
        $user = User::where('email', $request->email)->first();
        
        // check user and password
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        } else if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password is incorrect'], 401);
        }

        // generate token expires in 7 days
        $token = $user->createToken('auth_token');

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been logged in',
        ]);

        return response()->json([
            'message' => 'User logged in successfully',
            'data' => [
                'user' => $user,
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::now()->addDays(7)->toDateTimeString(),
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }
}
