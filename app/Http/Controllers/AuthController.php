<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest\LoginUserRequest;
use App\Http\Requests\AuthRequest\RegisterUserRequest;
use App\Models\User;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        // validate request

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
            'status' => 201,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::now()->addDays(7)->toDateTimeString(),
            ]
        ], 201);
    }

    public function login(LoginUserRequest $request)
    {
        // validate request

        // get user
        $user = User::where('email', $request->email)
            ->select('id', 'name', 'email', 'password', 'phone', 'photo', 'date_of_birth', 'gender', 'address', 'status')
            ->first();
        
        // check user and password
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found'
            ], 404);
        } else if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 401,
                'message' => 'Password is incorrect'
            ], 401);
        }

        // generate token expires in 7 days
        $token = $user->createToken('auth_token');

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been logged in',
        ]);

        return response()->json([
            'status' => 200,
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
        // Delete the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Successfully logged out'
        ], 200);
    }
}
